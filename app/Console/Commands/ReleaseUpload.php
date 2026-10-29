<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

/**
 * Uploads a release ZIP built by release:build to the license server. The release notes and the
 * migration flag come from the ZIP's release.json, so what is published is what was built.
 */
class ReleaseUpload extends Command
{
    protected $signature = 'release:upload
        {version : The release version that was built, e.g. 2.2.0}
        {--secret= : The license server upload secret (WordPress plugin → Settings → Secret Key)}
        {--server= : License server URL (default: config updater.api_url)}';

    protected $description = 'Upload a release ZIP built by release:build to the license server.';

    public function handle(): int
    {
        $version = ltrim((string) $this->argument('version'), 'v');
        $zipPath = storage_path("app/updates/update-{$version}.zip");

        if (! File::exists($zipPath)) {
            $this->error("No built release at {$zipPath}. Run: php artisan release:build {$version}");
            return self::FAILURE;
        }

        $secret = (string) $this->option('secret');
        if ($secret === '') {
            $this->error('Upload needs --secret (WordPress plugin → Settings → Secret Key).');
            return self::FAILURE;
        }

        $manifest = $this->manifest($zipPath);
        if ($manifest === null) {
            $this->error('The ZIP has no release.json. Rebuild it with release:build.');
            return self::FAILURE;
        }
        if (($manifest['version'] ?? null) !== $version) {
            $this->error("The ZIP is version {$manifest['version']}, not {$version}.");
            return self::FAILURE;
        }

        $server = rtrim((string) ($this->option('server') ?: config('updater.api_url')), '/');
        $url = $server . '/wp-json/softmit/v1/updates/upload';
        $this->info("Uploading {$zipPath} → {$url}");

        try {
            $handle = fopen($zipPath, 'r');
            $response = Http::withHeaders(['X-Softmit-Secret' => $secret])
                ->timeout(180)
                ->attach('file', $handle, basename($zipPath))
                ->post($url, [
                    'product' => (string) config('updater.script_name'),
                    'version' => $version,
                    'changelog' => (string) ($manifest['changelog'] ?? ''),
                    'requires_migration' => ($manifest['requires_migration'] ?? true) ? '1' : '0',
                ]);
            if (is_resource($handle)) {
                fclose($handle);
            }

            if (! $response->successful()) {
                $this->error('Upload failed (HTTP ' . $response->status() . '): ' . $response->body());
                return self::FAILURE;
            }

            $this->info('✔ Uploaded ' . $version . ': ' . $response->body());
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Upload error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /** @return array<string, mixed>|null */
    private function manifest(string $zipPath): ?array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }
        $json = $zip->getFromName('release.json');
        $zip->close();

        return $json === false ? null : json_decode($json, true);
    }
}
