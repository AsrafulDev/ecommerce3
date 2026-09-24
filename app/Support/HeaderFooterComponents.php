<?php

namespace App\Support;

/**
 * Header/Footer builder model.
 *
 * Storage (general_settings.header_components / footer_components) is:
 *
 *   { "rows": [
 *       { "id": "main", "columns": [
 *           { "widths": { "desktop": 3, "laptop": 4, "tablet": 6, "phone": 12 },
 *             "widgets": [ { "id": "logo", "visibility": {
 *                 "desktop": true, "laptop": true, "tablet": true, "phone": true } } ] }
 *       ] }
 *   ] }
 *
 * Three fixed row zones (top / main / bottom); each row holds up to MAX_COLUMNS
 * columns; each column holds one or more widgets. Widths are Bootstrap 12-grid
 * spans, set INDEPENDENTLY per device tier.
 *
 * ┌─ Device tiers (mobile-first) ────────────────────────────────────────┐
 * │ phone   < 576px   → col-*                                             │
 * │ tablet  ≥ 576px   → col-sm-*                                          │
 * │ laptop  ≥ 992px   → col-lg-*                                          │
 * │ desktop ≥ 1200px  → col-xl-*                                          │
 * └──────────────────────────────────────────────────────────────────────┘
 *
 * LEGACY FORMAT — older installs stored a FLAT list with 3 tiers
 * (desktop/tablet/mobile):
 *   [{id, visibility:{desktop,tablet,mobile}}, ...]  or  ["topbar", ...]
 * normalize() upgrades it automatically: every widget becomes its own
 * full-width column in `main`, and `mobile` maps to `phone`, while the new
 * `laptop` tier inherits the old `desktop` value (the old desktop tier already
 * covered ≥1024px, i.e. laptops).
 */
class HeaderFooterComponents
{
    /** Fixed row zones, in render order. */
    public const ROWS = ['top', 'main', 'bottom'];

    /** Device tiers, widest → narrowest (UI order). */
    public const DEVICES = ['desktop', 'laptop', 'tablet', 'phone'];

    /** Hard cap on columns inside a single row. */
    public const MAX_COLUMNS = 6;

    /** Full-width span (Bootstrap 12-grid). */
    public const FULL_WIDTH = 12;

    public static function pool(string $type): array
    {
        return $type === 'header' ? self::headerComponents() : self::footerComponents();
    }

    public static function headerComponents(): array
    {
        return [
            'topbar' => ['name' => 'Top Bar', 'icon' => 'mdi-page-layout-header', 'description' => 'Announcement strip above the header.'],
            'logo'   => ['name' => 'Logo Area', 'icon' => 'mdi-image', 'description' => 'Store logo and tagline.'],
            'search' => ['name' => 'Search Bar', 'icon' => 'mdi-magnify', 'description' => 'Product search input with suggestions.'],
            'nav'    => ['name' => 'Navigation Menu', 'icon' => 'mdi-menu', 'description' => 'Main category navigation and pages.'],
            'cart'   => ['name' => 'Cart & Icons', 'icon' => 'mdi-cart', 'description' => 'Wishlist, account and cart icons.'],
            // Draggable, but deliberately NOT part of defaults() so existing
            // layouts don't silently gain a button they never added.
            'all_categories' => ['name' => 'All Categories', 'icon' => 'mdi-view-grid-plus', 'description' => 'Categories dropdown / mega menu button.'],
        ];
    }

    public static function footerComponents(): array
    {
        return [
            'about'      => ['name' => 'About Section', 'icon' => 'mdi-information', 'description' => 'Store description and contact info.'],
            'links'      => ['name' => 'Quick Links', 'icon' => 'mdi-link-variant', 'description' => 'Useful-link column.'],
            'support'    => ['name' => 'Support Links', 'icon' => 'mdi-headset', 'description' => 'Customer support link column.'],
            'newsletter' => ['name' => 'Newsletter', 'icon' => 'mdi-email', 'description' => 'Email subscription form.'],
            'social'     => ['name' => 'Social Icons', 'icon' => 'mdi-share-variant', 'description' => 'Social media profile icons.'],
            'copyright'  => ['name' => 'Copyright Bar', 'icon' => 'mdi-copyright', 'description' => 'Copyright text and payment badges.'],
        ];
    }

    /**
     * Widgets seeded into a fresh/empty canvas.
     *
     * Explicit on purpose — deriving this from array_keys(pool()) would inject
     * every newly-promoted widget (e.g. all_categories) into existing sites.
     */
    public static function defaultWidgetIds(string $type): array
    {
        return $type === 'header'
            ? ['topbar', 'logo', 'search', 'nav', 'cart']
            : ['about', 'links', 'support', 'newsletter', 'social', 'copyright'];
    }

    // ───────────────────────── normalize ─────────────────────────

    /**
     * Normalize a stored/draft payload to the canonical row/column structure.
     * Unknown ids, duplicates, malformed entries and empty columns are dropped.
     *
     * @return array{rows: array<int, array{id: string, columns: array}>}
     */
    public static function normalize(?array $raw, string $type): array
    {
        $pool = self::pool($type);
        $seen = [];

        // Canonical shape, or upgrade a legacy flat list.
        $inputRows = (is_array($raw) && is_array($raw['rows'] ?? null))
            ? $raw['rows']
            : self::upgradeLegacy($raw);

        $rows = [];
        foreach (self::ROWS as $rowId) {
            $rows[$rowId] = ['id' => $rowId, 'columns' => []];
        }

        foreach ($inputRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rowId = $row['id'] ?? null;
            if (!is_string($rowId) || !isset($rows[$rowId])) {
                // Unknown row id — keep the widgets by parking them in `main`
                // rather than silently dropping the whole row.
                $rowId = 'main';
            }

            foreach ((array) ($row['columns'] ?? []) as $col) {
                if (!is_array($col)) {
                    continue;
                }
                if (count($rows[$rowId]['columns']) >= self::MAX_COLUMNS) {
                    break;
                }

                $colWidgets = [];
                foreach ((array) ($col['widgets'] ?? []) as $widget) {
                    $normalized = self::normalizeWidget($widget, $pool, $seen);
                    if ($normalized !== null) {
                        $colWidgets[] = $normalized;
                    }
                }

                if ($colWidgets === []) {
                    continue; // a column with no widgets is not rendered
                }

                $rows[$rowId]['columns'][] = [
                    'widths'  => self::normalizeWidths($col['widths'] ?? []),
                    'widgets' => $colWidgets,
                ];
            }
        }

        return ['rows' => array_values($rows)];
    }

    /**
     * Upgrade a legacy flat component list (3 device tiers) into rows.
     * Every entry becomes its own full-width column inside `main`.
     */
    private static function upgradeLegacy(?array $raw): array
    {
        $columns = [];

        foreach ($raw ?? [] as $item) {
            if (is_string($item)) {
                $columns[] = ['widths' => self::fullWidths(), 'widgets' => [['id' => $item]]];
                continue;
            }
            if (is_array($item) && is_string($item['id'] ?? null)) {
                $columns[] = ['widths' => self::fullWidths(), 'widgets' => [$item]];
            }
        }

        return $columns === [] ? [] : [['id' => 'main', 'columns' => $columns]];
    }

    /** Validate one widget entry; records its id in $seen to enforce uniqueness. */
    private static function normalizeWidget($widget, array $pool, array &$seen): ?array
    {
        if (is_string($widget)) {
            $widget = ['id' => $widget];
        }
        if (!is_array($widget)) {
            return null;
        }

        $id = $widget['id'] ?? null;
        if (!is_string($id) || !isset($pool[$id]) || isset($seen[$id])) {
            return null;
        }

        $visibility = is_array($widget['visibility'] ?? null) ? $widget['visibility'] : [];
        $seen[$id] = true;

        return [
            'id' => $id,
            'visibility' => self::normalizeVisibility($visibility),
        ];
    }

    /**
     * Canonical 4-tier visibility map.
     *
     * Legacy 3-tier payloads map: mobile → phone, and laptop inherits the old
     * desktop value (the old desktop tier already covered laptops).
     */
    public static function normalizeVisibility($visibility): array
    {
        $visibility = is_array($visibility) ? $visibility : [];

        $desktop = (bool) ($visibility['desktop'] ?? true);
        $legacy  = [
            'desktop' => $desktop,
            'laptop'  => (bool) ($visibility['laptop'] ?? $desktop),
            'tablet'  => (bool) ($visibility['tablet'] ?? true),
            'phone'   => (bool) ($visibility['phone'] ?? ($visibility['mobile'] ?? true)),
        ];

        return $legacy;
    }

    /** Clamp the per-device grid spans into 1..12. */
    public static function normalizeWidths($widths): array
    {
        $widths = is_array($widths) ? $widths : [];
        $out = [];

        foreach (self::DEVICES as $device) {
            $span = (int) ($widths[$device] ?? self::FULL_WIDTH);
            $out[$device] = max(1, min(self::FULL_WIDTH, $span));
        }

        return $out;
    }

    public static function fullWidths(): array
    {
        return array_fill_keys(self::DEVICES, self::FULL_WIDTH);
    }

    // ───────────────────────── accessors ─────────────────────────

    /** Canonical rows for a raw payload, falling back to defaults when empty. */
    public static function rows(?array $raw, string $type): array
    {
        $normalized = self::normalize($raw, $type);

        return self::hasWidgets($normalized) ? $normalized['rows'] : self::defaults($type)['rows'];
    }

    /** True when the normalized layout contains at least one widget. */
    public static function hasWidgets(?array $normalized): bool
    {
        foreach ((array) ($normalized['rows'] ?? []) as $row) {
            foreach ((array) ($row['columns'] ?? []) as $col) {
                if (!empty($col['widgets'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Default layout = the default widgets, each full-width in the `main` row. */
    public static function defaults(string $type): array
    {
        $columns = [];
        foreach (self::defaultWidgetIds($type) as $id) {
            $columns[] = ['widths' => self::fullWidths(), 'widgets' => [['id' => $id]]];
        }

        return self::normalize([
            'rows' => [['id' => 'main', 'columns' => $columns]],
        ], $type);
    }

    /** Flat widget id list, in render order (for available-pool diffing). */
    public static function ids(?array $normalized): array
    {
        $ids = [];

        foreach ((array) ($normalized['rows'] ?? []) as $row) {
            foreach ((array) ($row['columns'] ?? []) as $col) {
                foreach ((array) ($col['widgets'] ?? []) as $widget) {
                    if (is_string($widget['id'] ?? null)) {
                        $ids[] = $widget['id'];
                    }
                }
            }
        }

        return $ids;
    }

    // ───────────────────────── responsive output ─────────────────────────

    /**
     * Bootstrap grid classes for a column's per-device spans.
     * phone → col-*, tablet → col-sm-*, laptop → col-lg-*, desktop → col-xl-*
     */
    public static function columnClasses(array $widths): string
    {
        $w = self::normalizeWidths($widths);

        return "col-{$w['phone']} col-sm-{$w['tablet']} col-lg-{$w['laptop']} col-xl-{$w['desktop']}";
    }

    /**
     * A column is visible on a device only when at least one of its widgets is —
     * so a column whose widgets are all hidden on a tier collapses instead of
     * eating grid space.
     */
    public static function columnVisibility(array $widgets): array
    {
        if ($widgets === []) {
            return array_fill_keys(self::DEVICES, true);
        }

        $visible = array_fill_keys(self::DEVICES, false);

        foreach ($widgets as $widget) {
            foreach (self::DEVICES as $device) {
                if (!empty($widget['visibility'][$device])) {
                    $visible[$device] = true;
                }
            }
        }

        return $visible;
    }

    /**
     * Bootstrap display utilities for a 4-tier visibility map.
     *
     * Walks the tiers mobile-first and only emits the classes that CHANGE the
     * effective display at each boundary (base → sm → lg → xl), so all 16
     * combinations are handled without a 16-arm table.
     */
    public static function visibilityClasses(array $visibility): string
    {
        $vis = self::normalizeVisibility($visibility);

        $phone   = $vis['phone'];
        $tablet  = $vis['tablet'] || $vis['laptop'] || $vis['desktop'];
        $laptop  = $vis['laptop'] || $vis['desktop'];
        $desktop = $vis['desktop'];

        $classes = [];

        // base (<576): visible by default unless phone is off
        if (! $phone) {
            $classes[] = 'd-none';
        }

        // ≥576 (sm)
        if ($phone !== $tablet) {
            $classes[] = $tablet ? 'd-sm-block' : 'd-sm-none';
        }

        // ≥992 (lg)
        if ($tablet !== $laptop) {
            $classes[] = $laptop ? 'd-lg-block' : 'd-lg-none';
        }

        // ≥1200 (xl)
        if ($laptop !== $desktop) {
            $classes[] = $desktop ? 'd-xl-block' : 'd-xl-none';
        }

        return implode(' ', $classes);
    }
}
