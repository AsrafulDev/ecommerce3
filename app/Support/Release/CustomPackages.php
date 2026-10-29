<?php

namespace App\Support\Release;

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

    /** The folder a package takes inside a release ZIP: softmit/bd-double-entry becomes softmit-bd-double-entry. */
    public static function folderName(string $composerName): string
    {
        return str_replace('/', '-', $composerName);
    }
}
