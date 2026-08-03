<?php

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
