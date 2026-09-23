<?php

namespace App\Support;

/**
 * APP_KEY self-healing.
 *
 * A missing APP_KEY makes Laravel throw MissingAppKeyException and render the
 * "Your app key is missing" page. That happens while the encrypter is resolved
 * (cookies / session), i.e. BEFORE any route — including the installer and the
 * updater — can run. So the key has to be repaired at bootstrap, not in a
 * controller.
 *
 * Deliberately one-way: a key that already exists is NEVER replaced. Rotating it
 * would invalidate every encrypted cookie, session and encrypted column on the
 * site, so `ensure()` only ever fills a blank.
 */
class AppKey
{
    /**
     * Make sure APP_KEY is set, generating and persisting one when missing.
     *
     * @param  string|null  $envPath  Override .env location (used by tests).
     * @return bool  True when a new key was generated.
     */
    public static function ensure(?string $envPath = null): bool
    {
        try {
            $path = $envPath ?: static::envPath();

            if (! is_file($path) || ! is_readable($path)) {
                // Nothing safe to write to. `php artisan key:generate` remains
                // the manual fallback.
                return false;
            }

            $fromFile = static::readFromEnvFile($path);

            // A blank line in .env is not a key, but a real process env var wins
            // over .env — treat either as "already configured".
            if (($fromFile !== null && $fromFile !== '') || static::current() !== '') {
                return false;
            }

            $key     = static::generate();
            $updated = static::writeKeyToEnvContents(
                (string) file_get_contents($path),
                $key
            );

            if (@file_put_contents($path, $updated) === false) {
                return false; // read-only .env — leave it to key:generate
            }

            // Make it usable for THIS request too — Dotenv/Env already cached
            // the blank value before we got here.
            putenv('APP_KEY='.$key);
            $_ENV['APP_KEY']    = $key;
            $_SERVER['APP_KEY'] = $key;

            return true;
        } catch (\Throwable $e) {
            // Never let key repair break the boot.
            return false;
        }
    }

    /**
     * The key currently visible to the process (env var, $_ENV, $_SERVER).
     */
    public static function current(): string
    {
        foreach ([
            $_SERVER['APP_KEY'] ?? null,
            $_ENV['APP_KEY'] ?? null,
            getenv('APP_KEY') ?: null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    /**
     * A fresh key in the exact format `php artisan key:generate` produces.
     */
    public static function generate(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    /**
     * The APP_KEY value written in a .env file.
     *
     * @return string|null  '' when the line exists but is blank, null when the
     *                      file has no APP_KEY line at all.
     */
    public static function readFromEnvFile(string $path): ?string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        if (preg_match('/^[ \t]*APP_KEY[ \t]*=[ \t]*(.*)$/m', $contents, $matches)) {
            return trim($matches[1], " \t\"'");
        }

        return null;
    }

    /**
     * Replace the APP_KEY line, or append one when the file has none.
     */
    public static function writeKeyToEnvContents(string $contents, string $key): string
    {
        $updated = preg_replace(
            '/^[ \t]*APP_KEY[ \t]*=.*$/m',
            'APP_KEY='.$key,
            $contents,
            1,
            $count
        );

        if ($count > 0 && $updated !== null) {
            return $updated;
        }

        return rtrim($contents, "\r\n").PHP_EOL.'APP_KEY='.$key.PHP_EOL;
    }

    /**
     * Project root resolved WITHOUT the container — this runs before the
     * application exists, so base_path() is not available yet.
     */
    public static function envPath(): string
    {
        // <root>/app/Support/AppKey.php → <root>/.env
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'.env';
    }
}
