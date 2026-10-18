<?php

namespace App\Support;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * The admin login address. Stored in general_settings.admin_login_path; the
 * default "login" keeps the stock /login URL. Routes read it when they load, so
 * a change applies on the next request (unless routes are cached, see the
 * General Settings save message).
 */
final class AdminLoginPath
{
    public const DEFAULT = 'login';

    private const CACHE_KEY = 'admin_login_path';

    /** 5–64 chars: lowercase letters, digits and inner dashes. */
    private const PATTERN = '/^[a-z0-9][a-z0-9-]{3,62}[a-z0-9]$/';

    /** Addresses the application or its tooling already uses, even when no route matches them yet. */
    private const RESERVED = [
        'admin', 'api', 'storage', 'public', 'css', 'js', 'images', 'uploads', 'lang',
        'install', 'installer', 'customer', 'password', 'register', 'logout', 'sanctum',
        'up', '_ignition', 'sitemap', 'robots', 'favicon', 'style', 'responsive', 'dynamic-theme',
    ];

    /** Trimmed, lowercased form of the posted value, or null when blank (= keep current). */
    public static function normalize(?string $raw): ?string
    {
        $value = strtolower(trim((string) $raw));

        return $value === '' ? null : $value;
    }

    /** The login address in use now. Falls back to the default whenever the value cannot be read. */
    public static function current(): string
    {
        try {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_string($cached)) {
                return $cached;
            }

            // Install, or an older database without the column: the stock URL applies.
            if (! Schema::hasTable('general_settings') || ! Schema::hasColumn('general_settings', 'admin_login_path')) {
                return self::DEFAULT;
            }

            $stored = self::normalize(GeneralSetting::query()->orderByDesc('id')->value('admin_login_path'));
            $resolved = $stored !== null && preg_match(self::PATTERN, $stored) ? $stored : self::DEFAULT;

            Cache::forever(self::CACHE_KEY, $resolved);

            return $resolved;
        } catch (Throwable) {
            // Never cache a failed read: the next request tries again.
            return self::DEFAULT;
        }
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Why this address cannot be used, or null when it is free. Only call after routes are loaded. */
    public static function validationError(string $slug): ?string
    {
        if (! preg_match(self::PATTERN, $slug)) {
            return __('Use 5–64 lowercase letters, numbers or dashes; start and end with a letter or number.');
        }

        if (in_array($slug, self::RESERVED, true)) {
            return __('This address is reserved. Choose another.');
        }

        foreach (app('router')->getRoutes() as $route) {
            $first = explode('/', trim($route->uri(), '/'))[0] ?? '';
            if ($first === $slug) {
                return __('Another page already uses this address.');
            }
        }

        return null;
    }
}
