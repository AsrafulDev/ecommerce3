<?php

namespace App\Console\Commands;

use App\Support\Release\CustomPackages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Builds a release ZIP: Lara's app folders, the custom packages (set to the same version),
 * and a release.json manifest the client installer reads. Nothing is uploaded here; see release:upload.
 */
class ReleaseBuild extends Command
{
    protected $signature = 'release:build
        {version : The release version, e.g. 2.2.0}
        {--changelog= : Release notes as text, or a path to a file}
        {--dry-run : Show what would be packed and changed, without writing anything}';

    protected $description = 'Build a release ZIP with Lara and its custom packages at one version.';

    /** Lara folders packed into every release. */
    private const APP_DIRS = ['app', 'routes', 'resources', 'config', 'database/migrations'];

    /** Files a package needs for the installer to use it. */
    private const PACKAGE_ITEMS = ['src', 'composer.json', 'LICENSE', 'README.md'];

    public function handle(): int
    {
        $version = ltrim((string) $this->argument('version'), 'v');
        if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $this->error("Version must look like 2.2.0, got: {$version}");
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $changelog = $this->changelog();

        $packages = [];
        foreach (CustomPackages::all() as $name => $path) {
            if (! File::isDirectory($path) || ! File::exists($path . '/composer.json')) {
                $this->error("Custom package not found for {$name}: {$path}");
                return self::FAILURE;
            }
            $packages[$name] = $path;
        }

        $this->info("Release {$version}" . ($dry ? ' (dry run)' : ''));
        $this->line('Lara folders: ' . implode(', ', self::APP_DIRS));
        foreach ($packages as $name => $path) {
            $this->line("Package: {$name} → packages/" . CustomPackages::folderName($name) . " (version {$version}, from {$path})");
        }

        if ($dry) {
            return self::SUCCESS;
        }

        foreach ($packages as $name => $path) {
            $this->setPackageVersion($path, $version);
        }
        $this->setAppVersion($version);

        $zipPath = storage_path("app/updates/update-{$version}.zip");
        File::ensureDirectoryExists(dirname($zipPath));
        File::delete($zipPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Could not create {$zipPath}");
            return self::FAILURE;
        }

        $files = 0;
        foreach (self::APP_DIRS as $dir) {
            $files += $this->addDirectory($zip, base_path($dir), $dir);
        }
        foreach ($packages as $name => $path) {
            $folder = 'packages/' . CustomPackages::folderName($name);
            foreach (self::PACKAGE_ITEMS as $item) {
                $abs = $path . '/' . $item;
                if (File::isDirectory($abs)) {
                    $files += $this->addDirectory($zip, $abs, $folder . '/' . $item);
                } elseif (File::exists($abs)) {
                    $zip->addFile($abs, $folder . '/' . $item);
                    $files++;
                }
            }
        }

        $manifest = [
            'version' => $version,
            'changelog' => $changelog,
            'requires_migration' => true,
            'packages' => array_map(fn () => $version, $packages),
        ];
        $zip->addFromString('release.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        $zip->close();

        $this->info(sprintf('✔ Built %s (%d files, %s)', $zipPath, $files + 1, $this->humanSize((int) File::size($zipPath))));
        $this->line("Next: php artisan release:upload {$version} --secret=… to publish it.");

        return self::SUCCESS;
    }

    /** The release notes: the given text, or the contents of the file it names. */
    private function changelog(): string
    {
        $value = (string) $this->option('changelog');
        if ($value !== '' && File::exists($value)) {
            return (string) File::get($value);
        }

        return $value;
    }

    private function addDirectory(ZipArchive $zip, string $absDir, string $prefix): int
    {
        if (! File::isDirectory($absDir)) {
            return 0;
        }

        $count = 0;
        $base = rtrim(str_replace('\\', '/', $absDir), '/');
        foreach (File::allFiles($absDir) as $file) {
            $relative = ltrim(str_replace($base, '', str_replace('\\', '/', $file->getPathname())), '/');
            $zip->addFile($file->getPathname(), $prefix . '/' . $relative);
            $count++;
        }

        return $count;
    }

    /** Sets the version in the package's own composer.json, so the package says which release it shipped in. */
    private function setPackageVersion(string $path, string $version): void
    {
        $file = $path . '/composer.json';
        $json = json_decode((string) File::get($file), true, 512, JSON_THROW_ON_ERROR);
        $json['version'] = $version;
        File::put($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }

    /** Sets the version Lara reports in config/app.php. */
    private function setAppVersion(string $version): void
    {
        $file = config_path('app.php');
        $contents = (string) File::get($file);
        $updated = preg_replace("/'version' => \"[^\"]*\"/", "'version' => \"{$version}\"", $contents, 1, $count);
        if ($count === 1) {
            File::put($file, $updated);
        }
    }

    private function humanSize(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024, 1) . ' KB';
    }
}
