<?php

namespace App\Services\SingleLayout;

use App\Models\GeneralSetting;
use App\Models\SingleLayout as Layout;
use App\Models\Theme;
use App\Services\Landing\LandingThemes;
use Illuminate\Support\Facades\Cache;

/**
 * The product detail page design: colours, button and badge shapes, which sections show, the gallery side, whether the
 * buy box sticks, an optional extra text and optional custom buttons under the buy box.
 *
 * Each button has four colours: its text and background, and both again on hover. The page applies a layout with a class
 * list, CSS variables, the extra text and the custom buttons on one wrapper. The same classes and variables are used by
 * the editor's live preview, so what the editor shows is what the page shows.
 */
final class SingleLayout
{
    /** The four default button types: Order Now (primary), Add to Cart (secondary), Phone and WhatsApp. */
    public const BUTTON_KEYS = ['order_now', 'add_to_cart', 'phone', 'whatsapp'];

    /** The four colours of a button: background, hover background, text, and hover text. */
    public const BUTTON_PARTS = [
        '' => 'Background',
        '_hover' => 'Hover background',
        '_text' => 'Text',
        '_hover_text' => 'Hover text',
    ];

    public const COLOUR_KEYS = [
        'primary', 'accent', 'badge_hover', 'background', 'text',
        'order_now', 'order_now_hover', 'order_now_text', 'order_now_hover_text',
        'add_to_cart', 'add_to_cart_hover', 'add_to_cart_text', 'add_to_cart_hover_text',
        'phone', 'phone_hover', 'phone_text', 'phone_hover_text',
        'whatsapp', 'whatsapp_hover', 'whatsapp_text', 'whatsapp_hover_text',
    ];

    public const SECTION_KEYS = ['description', 'reviews', 'write_review', 'related', 'order_now', 'call', 'whatsapp', 'shipping'];

    /** The corner shapes for the buttons and for the discount badge. */
    public const SHAPES = ['square', 'rounded', 'circle'];

    public const SHAPE_KEYS = ['button', 'badge'];

    public const COLOUR_LABELS = [
        'primary' => 'Links and highlights',
        'accent' => 'Discount badge',
        'badge_hover' => 'Discount badge on hover',
        'background' => 'Page background',
        'text' => 'Text',
    ];

    public const BUTTON_LABELS = [
        'order_now' => 'Order Now (primary button)',
        'add_to_cart' => 'Add to Cart (secondary button)',
        'phone' => 'Phone button',
        'whatsapp' => 'WhatsApp button',
    ];

    public const SECTION_LABELS = [
        'description' => 'Product description',
        'reviews' => 'Customer reviews',
        'write_review' => 'Write a review button',
        'related' => 'Related products',
        'order_now' => 'Order Now button',
        'call' => 'Call button',
        'whatsapp' => 'WhatsApp button',
        'shipping' => 'Shipping info',
    ];

    public const SHAPE_LABELS = ['button' => 'Buttons', 'badge' => 'Discount badge'];

    public const SHAPE_NAMES = ['square' => 'Square', 'rounded' => 'Rounded', 'circle' => 'Circle'];

    public const EXTRA_TEXT_MAX = 200;

    /** How many custom buttons a layout can have. */
    public const CUSTOM_BUTTON_MAX = 3;

    /** A custom button opens a web address, a phone number or an email address. */
    public const LINK_PATTERN = '#^(https?://\S+|tel:\S+|mailto:\S+)$#i';

    /** The colour of the WhatsApp and phone buttons the product page has always used. */
    private const CONTACT_GREEN = '#198754';

    /** The discount badge colour the product page has always used. */
    private const BADGE_COLOUR = '#e82165';

    private const CUSTOM_BUTTON_COLOUR = '#374151';

    private const FALLBACK_COLOURS = [
        'primary' => '#6658dd', 'background' => '#ffffff', 'text' => '#1f2937',
    ];

    /**
     * The site's theme, found the way the storefront finds it: the theme chosen in the general settings, else the default
     * theme. The is_active flag is not used, as several themes can carry it.
     */
    public static function activeTheme(): ?Theme
    {
        $themeId = GeneralSetting::where('status', 1)->value('theme_id');

        return ($themeId ? Theme::find($themeId) : null) ?? Theme::where('is_default', true)->first();
    }

    /** The first layout starts from the site's active theme, so the page looks the same until someone changes it. */
    public static function defaults(): array
    {
        $theme = self::activeTheme();
        $primary = self::hex($theme?->primary_color, self::FALLBACK_COLOURS['primary']);

        return [
            'colours' => self::fullSet(
                [
                    'primary' => $primary,
                    'accent' => self::BADGE_COLOUR,
                    'badge_hover' => self::darken(self::BADGE_COLOUR),
                    'background' => self::hex($theme?->body_bg_color, self::FALLBACK_COLOURS['background']),
                    'text' => self::hex($theme?->text_color, self::FALLBACK_COLOURS['text']),
                ],
                [
                    'order_now' => $primary,
                    'add_to_cart' => self::hex($theme?->button_bg_color, $primary),
                    'phone' => self::CONTACT_GREEN,
                    'whatsapp' => self::CONTACT_GREEN,
                ]
            ),
            'shapes' => ['button' => 'rounded', 'badge' => 'circle'],
            'sections' => array_fill_keys(self::SECTION_KEYS, true),
            'layout' => ['gallery' => 'left', 'sticky_info' => false],
            'extra_text' => '',
            'custom_buttons' => [],
        ];
    }

    /** The settings from a validated form. Missing switches are off, as an unticked box is not sent. */
    public static function fromInput(array $data): array
    {
        $colours = [];
        foreach (self::COLOUR_KEYS as $key) {
            $colours[$key] = strtolower((string) $data['colours'][$key]);
        }

        $shapes = [];
        foreach (self::SHAPE_KEYS as $key) {
            $shapes[$key] = (string) $data['shapes'][$key];
        }

        $sections = [];
        foreach (self::SECTION_KEYS as $key) {
            $sections[$key] = (bool) ($data['sections'][$key] ?? false);
        }

        // A custom button without a text is left out, so an empty row in the editor is simply not saved.
        $customButtons = [];
        foreach ($data['custom_buttons'] ?? [] as $button) {
            $label = trim((string) ($button['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $background = strtolower((string) ($button['background'] ?? self::CUSTOM_BUTTON_COLOUR));
            $hoverBackground = self::darken($background);
            $customButtons[] = [
                'label' => $label,
                'link' => trim((string) ($button['link'] ?? '')),
                'background' => $background,
                'hover_background' => strtolower((string) ($button['hover_background'] ?? $hoverBackground)),
                'text' => strtolower((string) ($button['text'] ?? self::contrast($background))),
                'hover_text' => strtolower((string) ($button['hover_text'] ?? self::contrast($hoverBackground))),
            ];
        }

        return [
            'colours' => $colours,
            'shapes' => $shapes,
            'sections' => $sections,
            'layout' => [
                'gallery' => $data['layout']['gallery'] === 'right' ? 'right' : 'left',
                'sticky_info' => (bool) ($data['layout']['sticky_info'] ?? false),
            ],
            'extra_text' => trim((string) ($data['extra_text'] ?? '')),
            'custom_buttons' => $customButtons,
        ];
    }

    /**
     * The settings of the active layout, or the defaults when none is active yet. The stored settings are merged onto the
     * defaults on every read, so a layout saved (or cached) before a switch or colour existed still gets its default.
     */
    public static function active(): array
    {
        $stored = Cache::remember('single_layout.active', 600, function () {
            return Layout::where('is_active', true)->first()?->settings ?? [];
        });

        return array_replace_recursive(self::defaults(), $stored);
    }

    public static function forgetActive(): void
    {
        Cache::forget('single_layout.active');
    }

    /** The class list, the CSS variables, the extra text and the custom buttons the product detail page wraps itself in. */
    public static function forPage(): array
    {
        $settings = self::active();

        return [
            'classes' => self::classes($settings),
            'style' => self::style($settings),
            'extra_text' => $settings['extra_text'],
            'custom_buttons' => $settings['custom_buttons'],
        ];
    }

    public static function classes(array $settings): string
    {
        $classes = ['sl-scope'];
        if ($settings['layout']['gallery'] === 'right') {
            $classes[] = 'sl-gallery-right';
        }
        if ($settings['layout']['sticky_info']) {
            $classes[] = 'sl-sticky-info';
        }
        foreach (self::SHAPE_KEYS as $key) {
            $classes[] = 'sl-'.$key.'-'.$settings['shapes'][$key];
        }
        foreach (self::SECTION_KEYS as $key) {
            if (! $settings['sections'][$key]) {
                $classes[] = 'sl-hide-'.$key;
            }
        }

        return implode(' ', $classes);
    }

    public static function style(array $settings): string
    {
        $parts = [];
        foreach (self::COLOUR_KEYS as $key) {
            $parts[] = '--sl-'.str_replace('_', '-', $key).': '.$settings['colours'][$key];
        }

        return implode('; ', $parts).';';
    }

    /** A colour a little darker, for the hover state. */
    public static function darken(string $hex, float $amount = 0.15): string
    {
        $channels = sscanf($hex, '#%02x%02x%02x');

        return sprintf('#%02x%02x%02x', ...array_map(fn (int $value) => (int) round($value * (1 - $amount)), $channels));
    }

    /** Black or white, whichever reads better on the colour. */
    public static function contrast(string $hex): string
    {
        [$red, $green, $blue] = sscanf($hex, '#%02x%02x%02x');
        $brightness = (0.299 * $red + 0.587 * $green + 0.114 * $blue) / 255;

        return $brightness > 0.6 ? '#111111' : '#ffffff';
    }

    /**
     * The product page colours for a landing page palette. The palette sets the page colours, and each button takes one
     * of them: Order Now the primary, Add to Cart the accent, Phone a darker primary, WhatsApp the default green.
     */
    public static function colourSetFromPalette(array $palette): array
    {
        return self::fullSet(
            [
                'primary' => $palette['primary'],
                'accent' => $palette['accent'],
                'badge_hover' => self::darken($palette['accent']),
                'background' => $palette['background'],
                'text' => $palette['text'],
            ],
            [
                'order_now' => $palette['primary'],
                'add_to_cart' => $palette['accent'],
                'phone' => self::darken($palette['primary']),
                'whatsapp' => self::CONTACT_GREEN,
            ]
        );
    }

    /**
     * The first item of the Theme panel: the active site theme. Its colours are the defaults, with the discount badge taking
     * the theme's sale badge colour.
     */
    public static function siteThemePreset(): array
    {
        $theme = self::activeTheme();
        $colours = self::defaults()['colours'];

        $badge = $theme?->sale_badge_bg;
        if (is_string($badge) && preg_match('/^#[0-9a-fA-F]{6}$/', $badge)) {
            $colours['accent'] = strtolower($badge);
            $colours['badge_hover'] = self::darken($colours['accent']);
        }

        return [
            'label' => $theme ? __('Site theme: :name', ['name' => $theme->name]) : __('Site theme'),
            'colours' => $colours,
        ];
    }

    /** The Theme panel's presets: the active site theme first, then the landing page palettes as full colour sets. */
    public static function themePresets(): array
    {
        $presets = ['site' => self::siteThemePreset()];
        foreach (LandingThemes::presets() as $key => $preset) {
            $presets[$key] = ['label' => $preset['label'], 'colours' => self::colourSetFromPalette($preset['colours'])];
        }

        return $presets;
    }

    /** Makes sure one layout exists and is active. */
    public static function ensureDefault(): Layout
    {
        $active = Layout::where('is_active', true)->first();
        if ($active) {
            return $active;
        }

        // A "Default" that exists but is not active is reused, so the name stays unique.
        $layout = Layout::where('name', 'Default')->first()
            ?? Layout::create(['name' => 'Default', 'settings' => self::defaults()]);
        $layout->update(['is_active' => true]);
        self::forgetActive();

        return $layout;
    }

    /** The page colours, then the four colours of each button, in the order the editor shows them. */
    private static function fullSet(array $page, array $backgrounds): array
    {
        $colours = $page;
        foreach (self::BUTTON_KEYS as $key) {
            $colours = array_merge($colours, self::buttonSet($key, $backgrounds[$key]));
        }

        return $colours;
    }

    /** A button's four colours from its background: its hover background, and the text on each. */
    private static function buttonSet(string $key, string $background): array
    {
        $hover = self::darken($background);

        return [
            $key => $background,
            $key.'_hover' => $hover,
            $key.'_text' => self::contrast($background),
            $key.'_hover_text' => self::contrast($hover),
        ];
    }

    /** A hex colour in lower case, or the fallback when the value is not one. */
    private static function hex(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
    }
}
