<?php

namespace App\Services\Landing;

/**
 * The colour themes a landing page can be given from its editor. A theme is a named set of the four page colours. Choosing
 * one fills the four colour pickers; the page is changed only when it is saved, like any other edit. Every colour is a
 * six-digit hex value, the same rule ContentValidator applies on save.
 */
final class LandingThemes
{
    /**
     * The themes an editor can choose from: the built-in presets, then the custom themes. Each entry has a key the editor
     * uses ('builtin-gold', 'custom-3'), a label, the four colours, and whether it is a custom theme.
     *
     * @return array<string, array{key: string, label: string, colours: array<string, string>, custom: bool, id: ?int}>
     */
    public static function all(): array
    {
        $themes = [];
        foreach (self::presets() as $key => $preset) {
            $themes['builtin-'.$key] = [
                'key' => 'builtin-'.$key, 'label' => $preset['label'], 'colours' => $preset['colours'], 'custom' => false, 'id' => null,
            ];
        }
        foreach (\App\Models\LandingTheme::orderBy('name')->get() as $theme) {
            $themes['custom-'.$theme->id] = [
                'key' => 'custom-'.$theme->id, 'label' => $theme->name, 'colours' => $theme->colours(), 'custom' => true, 'id' => $theme->id,
            ];
        }

        return $themes;
    }

    /** @return array<string, array{label: string, colours: array{primary: string, accent: string, background: string, text: string}}> */
    public static function presets(): array
    {
        return [
            'gold' => [
                'label' => 'Gold & ink',
                'colours' => ['primary' => '#b08d57', 'accent' => '#1c1917', 'background' => '#faf8f5', 'text' => '#1c1917'],
            ],
            'rose' => [
                'label' => 'Rose & cocoa',
                'colours' => ['primary' => '#9b5c52', 'accent' => '#2b2622', 'background' => '#faf7f2', 'text' => '#2b2622'],
            ],
            'sage' => [
                'label' => 'Sage & linen',
                'colours' => ['primary' => '#5f7a5c', 'accent' => '#2f3d2e', 'background' => '#f6f4ee', 'text' => '#2f3d2e'],
            ],
            'ocean' => [
                'label' => 'Ocean blue',
                'colours' => ['primary' => '#2f6f8f', 'accent' => '#1b3a4b', 'background' => '#f4f9fb', 'text' => '#1b3a4b'],
            ],
            'midnight' => [
                'label' => 'Midnight gold',
                'colours' => ['primary' => '#c9a24b', 'accent' => '#f5f1e8', 'background' => '#15181f', 'text' => '#f5f1e8'],
            ],
        ];
    }
}
