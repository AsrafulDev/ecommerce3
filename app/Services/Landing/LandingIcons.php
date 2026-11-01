<?php

namespace App\Services\Landing;

/**
 * The Font Awesome icons a landing page can show. A stored icon is the name without the "fa-"
 * prefix, and only names in this list are accepted, so the page never prints an arbitrary class.
 * Every name here must exist in public/frontEnd/css/all.min.css (Font Awesome 6 free, solid).
 */
final class LandingIcons
{
    private const ICONS = [
        'leaf' => 'Leaf',
        'feather' => 'Feather',
        'shirt' => 'Shirt',
        'gem' => 'Gem',
        'wand-magic-sparkles' => 'Sparkles',
        'hand-sparkles' => 'Hand sparkles',
        'palette' => 'Palette',
        'scissors' => 'Scissors',
        'ruler' => 'Ruler',
        'heart' => 'Heart',
        'hand-holding-heart' => 'Hand with heart',
        'star' => 'Star',
        'award' => 'Award',
        'medal' => 'Medal',
        'certificate' => 'Certificate',
        'shield-halved' => 'Shield',
        'circle-check' => 'Check',
        'thumbs-up' => 'Thumbs up',
        'truck' => 'Delivery',
        'box-open' => 'Box',
        'gift' => 'Gift',
        'recycle' => 'Recycle',
        'sun' => 'Sun',
        'snowflake' => 'Snowflake',
        'droplet' => 'Droplet',
        'spa' => 'Spa',
        'location-dot' => 'Location',
        'bowl-food' => 'Bowl',
        'seedling' => 'Seedling',
        'filter' => 'Filter',
        'barcode' => 'Barcode',
        'ban' => 'No additives',
    ];

    /** @return array<string, string> icon name => label, in picker order */
    public static function all(): array
    {
        return self::ICONS;
    }

    public static function has(string $name): bool
    {
        return array_key_exists($name, self::ICONS);
    }
}
