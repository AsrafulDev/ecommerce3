<?php

namespace App\Support\Release;

use Illuminate\Support\Facades\File;

/**
 * The custom packages that ship with every Lara release, keyed by their composer name.
 * The double-entry package is developed in a sibling folder next to Lara; set
 * DOUBLE_ENTRY_PATH if it lives somewhere else.
 */
final class CustomPackages
{
    /** @return array<string, string> composer name => absolute path of the package folder */
    public static function all(): array
    {
        return [
            'softmit/bd-double-entry' => (string) env('DOUBLE_ENTRY_PATH', dirname(base_path()) . '/bd-double-entry'),
        ];
    }

    /** Sets the version in a package's own composer.json, so the package says which release it shipped in. */
    public static function setVersion(string $path, string $version): void
    {
        $file = $path . '/composer.json';
        $json = json_decode((string) File::get($file), true, 512, JSON_THROW_ON_ERROR);
        $json['version'] = $version;
        File::put($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }

    /** The folder a package takes inside a release ZIP: softmit/bd-double-entry becomes softmit-bd-double-entry. */
    public static function folderName(string $composerName): string
    {
        return str_replace('/', '-', $composerName);
    }
}
