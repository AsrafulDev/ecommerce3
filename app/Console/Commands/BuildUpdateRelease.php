<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
        {--full : Include the complete deployable source tree, dependencies, language files, and demo presets}
        {--lts : Build both the regular update ZIP and a separate full LTS ZIP}
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

    private const FULL_DIRS = [
        'bootstrap',
        'database/factories',
        'database/seeders',
        'docker',
        'lang',
        'public',
        'storage/app/demo-presets',
        'vendor',
    ];

    private const FULL_FILES = [
        '.editorconfig',
        '.env.example',
        '.htaccess',
        'artisan',
        'composer.json',
        'composer.lock',
        'favicon.ico',
        'index.php',
        'package-lock.json',
        'package.json',
        'phpunit.xml',
        'robots.txt',
        'server.php',
        'sail',
        'vite.config.js',
    ];

    private const FULL_EXCLUDED_PATHS = [
        'public/uploads',
    ];

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

        // Update config/app.php before packaging so the installed version inside
        // the update ZIP matches the release version.
        $previousAppConfig = $this->setAppVersion($version);
        if ($this->option('lts') && $this->option('full')) {
            $this->error('Use either --full or --lts, not both.');
            $this->restoreAppConfig($previousAppConfig);
            return self::FAILURE;
        }

        $zipPath = $this->buildPackage($version, (bool) $this->option('full'));
        if (! $zipPath) {
            $this->restoreAppConfig($previousAppConfig);
            return self::FAILURE;
        }

        $this->info("✔ Updated config/app.php version = {$version}");

        // Print upload instructions.
        $this->line('');
        $this->line('Next step — upload this file to your license server:');
        $this->line('  1. WordPress admin → License Manager → Updates → Add New');
        $this->line('  2. Select product, version ' . $version . ', upload the ZIP, changelog, active ✓');
        $this->line('  --or-- run this command with --upload --secret=YOUR_SECRET to push it automatically.');

        $fullZipPath = null;
        if ($this->option('lts')) {
            $fullZipPath = $this->buildPackage($version, true, '-full');
            if (! $fullZipPath) {
                return self::FAILURE;
            }
        }

        // Optional auto-upload.
        if ($this->option('upload')) {
            $this->upload($zipPath, $version, $changelog, $requiresMigration, $fullZipPath);
        }

        return self::SUCCESS;
    }

    /**
     * Build the update ZIP in the client-installer format.
     *
     * @return string|null Absolute path to the built zip.
     */
    private function buildPackage(string $version, ?bool $full = null, string $suffix = ''): ?string
    {
        $outDir = storage_path('app/updates');
        File::ensureDirectoryExists($outDir);
        $zipPath = $outDir . '/update-' . $version . $suffix . '.zip';

        $full = $full ?? (bool) $this->option('full');

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Could not create the zip file.');
            return null;
        }

        $base = base_path();
        $fileCount = 0;
        $extraFiles = [];

        // Core directories (the client installer copies these recursively).
        foreach (self::CORE_DIRS as $dir) {
            $abs = $base . '/' . $dir;
            if (File::isDirectory($abs)) {
                $fileCount += $this->addDirToZip($zip, $abs, $dir);
            } else {
                $this->warn("Missing core directory: {$dir}");
            }
        }

        if ($full) {
            $this->line('Building full release package (vendor included; uploads and runtime data excluded)...');

            foreach (self::FULL_DIRS as $dir) {
                $abs = $base . '/' . $dir;
                if (File::isDirectory($abs)) {
                    $fileCount += $this->addDirToZip($zip, $abs, $dir, self::FULL_EXCLUDED_PATHS);
                    foreach ($this->listRelativeFiles($abs, $dir, self::FULL_EXCLUDED_PATHS) as $file) {
                        $extraFiles[] = $file;
                    }
                } else {
                    $this->warn("Missing full-release directory: {$dir}");
                }
            }

            foreach (self::FULL_FILES as $rel) {
                $abs = $base . '/' . $rel;
                if (File::exists($abs)) {
                    $zip->addFile($abs, $rel);
                    $extraFiles[] = $rel;
                    $fileCount++;
                }
            }
        }

        // Extra includes → written into files.txt so the client copies them individually.
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
    private function addDirToZip(ZipArchive $zip, string $absDir, string $zipPrefix, array $excludedPaths = []): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isDir() || ! $file->isFile() || ! is_readable($file->getPathname())) {
                continue;
            }
            $rel = $zipPrefix . '/' . substr($file->getPathname(), strlen(rtrim($absDir, '/')) + 1);
            if ($this->isExcludedPath($rel, $excludedPaths)) {
                continue;
            }
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
    private function listRelativeFiles(string $absDir, string $prefix, array $excludedPaths = []): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isDir() || ! $file->isFile() || ! is_readable($file->getPathname())) {
                continue;
            }
            $rel = $prefix . '/' . substr($file->getPathname(), strlen(rtrim($absDir, '/')) + 1);
            if (! $this->isExcludedPath($rel, $excludedPaths)) {
                $files[] = $rel;
            }
        }
        return $files;
    }

    private function isExcludedPath(string $path, array $excludedPaths): bool
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        foreach ($excludedPaths as $excluded) {
            $excluded = trim(str_replace('\\', '/', $excluded), '/');
            if ($path === $excluded || str_starts_with($path, $excluded . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Current installed version from config/app.php.
     */
    private function currentVersion(): string
    {
        return (string) config('app.version', '1.0.0');
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
     * Set the installed version in config/app.php and return its old contents.
     */
    private function setAppVersion(string $version): string
    {
        $path = config_path('app.php');
        $contents = File::get($path);
        $updated = preg_replace_callback(
            '/([\'\"]version[\'\"]\s*=>\s*)([\'\"])([^\'\"]*)(\2\s*,)/',
            static fn (array $match): string => $match[1] . $match[2] . $version . $match[4],
            $contents,
            1,
            $count
        );

        if ($count !== 1 || $updated === null) {
            throw new \RuntimeException('Could not find the version entry in config/app.php.');
        }

        File::put($path, $updated);
        return $contents;
    }

    private function restoreAppConfig(string $contents): void
    {
        File::put(config_path('app.php'), $contents);
        $this->warn('Package build failed; restored the previous config/app.php version.');
    }

    /**
     * Upload the built package to the license server (WordPress plugin endpoint).
     */
    private function upload(string $zipPath, string $version, string $changelog, bool $requiresMigration, ?string $fullZipPath = null): void
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
            $request = Http::withHeaders(['X-Softmit-Secret' => $secret])
                ->timeout(180)
                ->attach('file', fopen($zipPath, 'r'), basename($zipPath))
                ;
            if ($fullZipPath && file_exists($fullZipPath)) {
                $request = $request->attach('full_file', fopen($fullZipPath, 'r'), basename($fullZipPath));
            }
            $response = $request->post($url, [
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
