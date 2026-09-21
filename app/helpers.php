<?php

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;

if (! function_exists('warranty_enabled')) {
    function warranty_enabled(): bool
    {
        return (bool) Cache::remember('warranty_enabled', 1800, function () {
            return GeneralSetting::query()->value('warranty_enabled') ?? true;
        });
    }
}

if (!function_exists('color_luminance')) {
    /**
     * Compute relative luminance (0-255) of a hex color.
     */
    function color_luminance(?string $hex): float
    {
        if (!$hex) return 0;
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) return 0;
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return (0.299 * $r + 0.587 * $g + 0.114 * $b);
    }
}

if (!function_exists('get_contrast_color')) {
    /**
     * Return black (#000) or white (#fff) depending on background luminance.
     * Uses WCAG-style threshold: luminance < 140 → white text, else black.
     */
    function get_contrast_color(?string $bgHex): string
    {
        return color_luminance($bgHex) < 140 ? '#ffffff' : '#111111';
    }
}

if (!function_exists('ensure_text_contrast')) {
    /**
     * Ensure a text color contrasts with its background.
     * If both are dark or both are light, return auto-computed safe color.
     */
    function ensure_text_contrast(?string $textHex, ?string $bgHex): string
    {
        $textHex = $textHex ?: get_contrast_color($bgHex);
        $bgLum = color_luminance($bgHex);
        $textLum = color_luminance($textHex);
        
        // If background is light (lum >= 140), text should be dark (lum < 140)
        // If background is dark (lum < 140), text should be light (lum >= 140)
        $bgIsLight = $bgLum >= 140;
        $textIsLight = $textLum >= 140;
        
        // Both light or both dark = poor contrast → use safe alternative
        if ($bgIsLight === $textIsLight) {
            return $bgIsLight ? '#111111' : '#ffffff';
        }
        return $textHex;
    }
}

if (!function_exists('is_color_dark')) {
    /**
     * Determine if a hex color is "dark" (luminance < 128).
     * Used to set data-leftbar-color / data-topbar-color on admin body.
     */
    function is_color_dark(?string $hex): bool
    {
        return color_luminance($hex) < 128;
    }
}

if (! function_exists('normalize_phone')) {
    /**
     * Canonicalise a phone number for block matching.
     *
     * Handles the common Bangladeshi spellings so one block entry catches them
     * all: 01712345678 / +8801712345678 / 8801712345678 → "01712345678".
     * Returns '' when there are no digits to work with.
     */
    function normalize_phone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '' || $digits === null) {
            return '';
        }

        // Strip the country code ("880" first so it wins over "88").
        foreach (['880', '88'] as $code) {
            if (str_starts_with($digits, $code)) {
                $digits = substr($digits, strlen($code));
                break;
            }
        }

        // Canonical local form: 11 digits beginning with 0.
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}

if (! function_exists('is_phone_blocked')) {
    /**
     * Return the matching PhoneBlock entry for a number, or null when allowed.
     * Cached briefly (like the IP check) so every checkout isn't a DB hit —
     * block/unblock clears the cache immediately.
     */
    function is_phone_blocked(?string $phone): ?\App\Models\PhoneBlock
    {
        $normalized = normalize_phone($phone);

        if ($normalized === '') {
            return null;
        }

        return Cache::remember("phone_block_{$normalized}", 300, function () use ($normalized) {
            return \App\Models\PhoneBlock::where('phone_normalized', $normalized)->first();
        });
    }
}

if (! function_exists('forget_phone_block_cache')) {
    /**
     * Drop the cached verdict for one number (or every number when null) so a
     * block/unblock takes effect on the very next request.
     */
    function forget_phone_block_cache(?string $phone = null): void
    {
        if ($phone === null) {
            Cache::forget('phone_blocks_all');
            return;
        }

        $normalized = normalize_phone($phone);

        if ($normalized !== '') {
            Cache::forget("phone_block_{$normalized}");
        }
    }
}

if (!function_exists('log_activity')) {
    /**
     * Record a security/audit log entry for a user action.
     * Never throws — logging must not break the main request flow.
     *
     * @param string      $module      e.g. product, order, stock, warranty, purchase
     * @param string      $action      e.g. create, update, delete, price_change, status, stock_in, stock_out
     * @param string      $description human-readable summary
     * @param object|null $model       optional related model
     * @param array       $data        optional structured detail (old/new values, etc.)
     */
    function log_activity(string $module, string $action, string $description, $model = null, array $data = []): void
    {
        try {
            $user = auth('admin')->user() ?? auth()->user();

            \App\Models\ActivityLog::create([
                'user_id'     => $user?->id,
                'user_name'   => $user?->name ?? 'System',
                'module'      => $module,
                'action'      => $action,
                'description' => \Illuminate\Support\Str::limit($description, 500),
                'model_type'  => $model ? get_class($model) : null,
                'model_id'    => $model ? $model->getKey() : null,
                'data'        => $data ?: null,
                'ip'          => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // silently ignore
        }
    }
}
