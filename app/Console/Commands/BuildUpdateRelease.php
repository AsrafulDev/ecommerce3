<?php

namespace App\Console\Commands;

use App\Models\GeneralSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class BuildUpdateRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:release
        {version? : New version number (e.g. 1.1.0). If omitted, auto-bumped from the current version.}
        {--bump=patch : Auto-bump part when no version is given: patch|minor|major}
        {--upload : Upload the built package to the license server (requires --secret)}
        {--server= : License server URL override (default: config updater.api_url)}
        {--secret= : License server upload secret (from the WordPress plugin settings)}
        {--changelog= : Release notes (text, or a file path that exists)}
        {--requires-migration : Mark the release as requiring a DB migration (default: true)}
        {--include=* : Extra files/directories (relative to project root) to include in the package}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build a new version update package (.zip) from the current code and optionally upload it to the license server.';

    /**
     * Directories copied by the client installer (UpdateController::copyUpdateFiles).
     */
    private const CORE_DIRS = ['app', 'routes', 'resources', 'config', 'database/migrations'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $version = $this->argument('version');

        if (empty($version)) {
            $current = $this->currentVersion();
            $version = $this->bumpVersion($current, (string) $this->option('bump'));
            $this->info("No version given — bumping {$current} → {$version}");
        }
        $version = ltrim((string) $version, 'v');
        if (! preg_match('/^[\w.\-]+$/', $version)) {
            $this->error("Invalid version format: {$version}");
            return self::FAILURE;
        }

        $changelog = $this->resolveChangelog();
        $requiresMigration = (bool) $this->option('requires-migration') || true;

        // Build the package.
        $zipPath = $this->buildPackage($version);
        if (! $zipPath) {
            return self::FAILURE;
        }

        // Advance the stored version so the next release bumps correctly.
        $this->setDbVersion($version);

        // Print upload instructions.
        $this->line('');
        $this->line('Next step — upload this file to your license server:');
        $this->line('  1. WordPress admin → License Manager → Updates → Add New');
        $this->line('  2. Select product, version ' . $version . ', upload the ZIP, changelog, active ✓');
        $this->line('  --or-- run this command with --upload --secret=YOUR_SECRET to push it automatically.');

        // Optional auto-upload.
        if ($this->option('upload')) {
            $this->upload($zipPath, $version, $changelog, $requiresMigration);
        }

        return self::SUCCESS;
    }

    /**
     * Build the update ZIP in the client-installer format.
     *
     * @return string|null Absolute path to the built zip.
     */
    private function buildPackage(string $version): ?string
    {
        $outDir = storage_path('app/updates');
        File::ensureDirectoryExists($outDir);
        $zipPath = $outDir . '/update-' . $version . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Could not create the zip file.');
            return null;
        }

        $base = base_path();
        $fileCount = 0;

        // Core directories (the client installer copies these recursively).
        foreach (self::CORE_DIRS as $dir) {
            $abs = $base . '/' . $dir;
            if (File::isDirectory($abs)) {
                $fileCount += $this->addDirToZip($zip, $abs, $dir);
            } else {
                $this->warn("Missing core directory: {$dir}");
            }
        }

        // Extra includes → written into files.txt so the client copies them individually.
        $extraFiles = [];
        foreach ((array) $this->option('include') as $rel) {
            $rel = ltrim((string) $rel, '/');
            $abs = $base . '/' . $rel;
            if (! File::exists($abs)) {
                $this->warn("Include not found (skipped): {$rel}");
                continue;
            }
            if (File::isDirectory($abs)) {
                $fileCount += $this->addDirToZip($zip, $abs, $rel);
                foreach ($this->listRelativeFiles($abs, $rel) as $f) {
                    $extraFiles[] = $f;
                }
            } else {
                $zip->addFile($abs, $rel);
                $extraFiles[] = $rel;
                $fileCount++;
            }
        }

        if ($extraFiles) {
            $zip->addFromString('files.txt', implode("\n", array_unique($extraFiles)) . "\n");
        }

        // Manifest (informational; the installer itself always runs migrations).
        $manifest = [
            'version'            => $version,
            'script_name'        => (string) config('updater.script_name'),
            'requires_migration' => true,
            'build_date'         => now()->toDateTimeString(),
            'changelog'          => $this->resolveChangelog(),
            'file_count'         => $fileCount,
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $zip->close();

        $size = file_exists($zipPath) ? filesize($zipPath) : 0;
        $this->info("✔ Update package built: {$zipPath}");
        $this->info("  Files: {$fileCount} | Size: {$this->humanSize($size)} | Version: {$version}");

        return $zipPath;
    }

    /**
     * Recursively add a directory to the zip preserving the relative prefix.
     *
     * @return int Number of files added.
     */
    private function addDirToZip(ZipArchive $zip, string $absDir, string $zipPrefix): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            $rel = $zipPrefix . '/' . substr($file->getPathname(), strlen(rtrim($absDir, '/')) + 1);
            $zip->addFile($file->getPathname(), $rel);
            $count++;
        }
        return $count;
    }

    /**
     * List all files under a directory as relative paths (prefix preserved).
     *
     * @return string[]
     */
    private function listRelativeFiles(string $absDir, string $prefix): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            $files[] = $prefix . '/' . substr($file->getPathname(), strlen(rtrim($absDir, '/')) + 1);
        }
        return $files;
    }

    /**
     * Current installed version (DB app_version → config fallback).
     */
    private function currentVersion(): string
    {
        try {
            $s = GeneralSetting::where('status', 1)->first();
            if ($s && isset($s->app_version) && trim((string) $s->app_version) !== '') {
                return trim((string) $s->app_version);
            }
        } catch (\Exception $e) {
            // ignore
        }
        return (string) config('updater.current_version', '1.0.0');
    }

    /**
     * Auto-bump a semantic version.
     */
    private function bumpVersion(string $current, string $part): string
    {
        $parts = explode('.', $current);
        $parts = array_pad($parts, 3, '0');

        if ('major' === $part) {
            $parts[0]++;
            $parts[1] = 0;
            $parts[2] = 0;
        } elseif ('minor' === $part) {
            $parts[1]++;
            $parts[2] = 0;
        } else {
            $parts[2]++;
        }

        return implode('.', $parts);
    }

    /**
     * Resolve changelog from --changelog (text or existing file path).
     */
    private function resolveChangelog(): string
    {
        $value = (string) $this->option('changelog');
        if ('' !== $value && File::exists(base_path($value))) {
            return (string) File::get(base_path($value));
        }
        return $value;
    }

    /**
     * Store the new version in the database.
     */
    private function setDbVersion(string $version): void
    {
        try {
            $s = GeneralSetting::where('status', 1)->first();
            if ($s) {
                $s->app_version = $version;
                $s->save();
            }
            Cache::forget('general_setting');
            $this->info("✔ Stored app_version = {$version} in general_settings");
        } catch (\Exception $e) {
            $this->warn('Could not update app_version in the database: ' . $e->getMessage());
        }
    }

    /**
     * Upload the built package to the license server (WordPress plugin endpoint).
     */
    private function upload(string $zipPath, string $version, string $changelog, bool $requiresMigration): void
    {
        $server = rtrim((string) ($this->option('server') ?: config('updater.api_url')), '/');
        $secret = (string) $this->option('secret');

        if ('' === $secret) {
            $this->error('Upload skipped: no --secret provided. Get it from the WordPress plugin Settings → Secret Key.');
            return;
        }

        $url = $server . '/wp-json/softmit/v1/updates/upload';
        $this->info("Uploading {$zipPath} → {$url}");

        try {
            $response = Http::withHeaders(['X-Softmit-Secret' => $secret])
                ->timeout(180)
                ->attach('file', fopen($zipPath, 'r'), basename($zipPath))
                ->post($url, [
                    'product'            => (string) config('updater.script_name'),
                    'version'            => $version,
                    'changelog'          => $changelog,
                    'requires_migration' => $requiresMigration ? '1' : '0',
                ]);

            if ($response->successful()) {
                $this->info('✔ Uploaded successfully: ' . $response->body());
            } else {
                $this->error('Upload failed (HTTP ' . $response->status() . '): ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('Upload error: ' . $e->getMessage());
        }
    }

    /**
     * Human readable file size.
     */
    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
