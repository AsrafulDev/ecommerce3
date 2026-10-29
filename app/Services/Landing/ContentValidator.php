<?php

namespace App\Services\Landing;

/**
 * Checks a landing page's JSON content against its template, and returns the cleaned copy.
 *
 * Rules (plan 4.2):
 *  - every element id must exist in the template (unknown ids are refused)
 *  - each value matches its type: text is plain, rich keeps basic tags only, image is a media path
 *  - colours are six-digit hex values, and only the four theme keys are allowed
 *  - the checkout section is always on
 *  - at least one section besides checkout stays on
 */
final class ContentValidator
{
    private const THEME_KEYS = ['primary', 'accent', 'background', 'text'];

    private const RICH_TAGS = '<b><i><u><br><p><ul><li><strong><em>';

    /**
     * @param  array<string, mixed>  $content
     * @return array{theme: array<string, string>, sections: array<string, array{enabled: bool}>, elements: array<string, array{value: string}>}
     *
     * @throws InvalidLandingContent
     */
    public static function clean(array $content, string $templateKey): array
    {
        $schema = TemplateRegistry::schema($templateKey);
        $defaults = TemplateRegistry::defaultContent($templateKey);
        $errors = [];

        $theme = self::cleanTheme($content['theme'] ?? [], $defaults['theme'], $errors);
        $sections = self::cleanSections($content['sections'] ?? [], $schema['sections'], $errors);
        $elements = self::cleanElements($content['elements'] ?? [], $schema['sections'], $errors);

        $contentOn = array_filter(
            $sections,
            fn (array $section, string $key) => empty($schema['sections'][$key]['locked']) && $section['enabled'],
            ARRAY_FILTER_USE_BOTH
        );
        if ($contentOn === []) {
            $errors[] = 'At least one section besides checkout must stay on.';
        }

        if ($errors !== []) {
            throw new InvalidLandingContent($errors);
        }

        return ['theme' => $theme, 'sections' => $sections, 'elements' => $elements];
    }

    /** @return array<string, string> */
    private static function cleanTheme(mixed $theme, array $defaults, array &$errors): array
    {
        if (!is_array($theme)) {
            $errors[] = 'Colours must be a list of four colours.';

            return $defaults;
        }

        foreach (array_keys($theme) as $key) {
            if (!in_array($key, self::THEME_KEYS, true)) {
                $errors[] = "Unknown colour '{$key}'.";
            }
        }

        $clean = [];
        foreach (self::THEME_KEYS as $key) {
            $value = $theme[$key] ?? $defaults[$key];
            if (!is_string($value) || !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                $errors[] = "Colour '{$key}' must be a hex value such as #4f46e5.";
                $value = $defaults[$key];
            }
            $clean[$key] = strtolower($value);
        }

        return $clean;
    }

    /** @return array<string, array{enabled: bool}> */
    private static function cleanSections(mixed $sections, array $schema, array &$errors): array
    {
        if (!is_array($sections)) {
            $errors[] = 'Sections must be a list of section switches.';
            $sections = [];
        }

        foreach (array_keys($sections) as $key) {
            if (!array_key_exists($key, $schema)) {
                $errors[] = "Unknown section '{$key}'.";
            }
        }

        $clean = [];
        foreach ($schema as $key => $def) {
            // A section missing from the input stays on, as in a new landing page.
            $enabled = true;
            if (array_key_exists($key, $sections)) {
                $raw = is_array($sections[$key]) ? ($sections[$key]['enabled'] ?? null) : null;
                $enabled = $raw === null ? null : filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }

            if ($enabled === null) {
                $errors[] = "Section '{$key}' must be switched on or off.";
                $enabled = true;
            }

            if (!empty($def['locked']) && $enabled !== true) {
                $errors[] = 'The checkout section cannot be switched off.';
                $enabled = true;
            }

            $clean[$key] = ['enabled' => $enabled];
        }

        return $clean;
    }

    /** @return array<string, array{value: string}> */
    private static function cleanElements(mixed $elements, array $schema, array &$errors): array
    {
        if (!is_array($elements)) {
            $errors[] = 'Elements must be a list of values.';

            return [];
        }

        $clean = [];
        foreach ($elements as $id => $value) {
            $spec = self::spec($schema, (string) $id);
            if ($spec === null) {
                $errors[] = "Unknown element '{$id}'.";
                continue;
            }

            $clean[(string) $id] = ['value' => self::cleanValue($spec, $value, (string) $id, $errors)];
        }

        return $clean;
    }

    /** The definition of an element id, or null when the template has no such element. */
    private static function spec(array $schema, string $id): ?array
    {
        $parts = explode('.', $id);
        $section = $parts[0];

        if (!isset($schema[$section])) {
            return null;
        }

        if (count($parts) === 2) {
            return $schema[$section]['elements'][$id] ?? null;
        }

        // section.list.N.field
        if (count($parts) === 4 && isset($schema[$section]['lists'][$parts[1]]) && ctype_digit($parts[2])) {
            $list = $schema[$section]['lists'][$parts[1]];
            $row = (int) $parts[2];

            if ($row < 1 || $row > $list['max_items']) {
                return null;
            }

            return $list['fields'][$parts[3]] ?? null;
        }

        return null;
    }

    /**
     * Basic formatting only: the allowed tags stay, their attributes go, so nothing like onclick survives.
     * Used when saving and again when the page is shown.
     */
    public static function sanitizeRich(string $text): string
    {
        $text = strip_tags($text, self::RICH_TAGS);

        return preg_replace('/<(\/?)([a-zA-Z]+)[^>]*>/', '<$1$2>', $text);
    }

    private static function cleanValue(array $spec, mixed $value, string $id, array &$errors): string
    {
        $raw = is_array($value) ? ($value['value'] ?? null) : $value;

        if (!is_string($raw)) {
            $errors[] = "'{$id}' must be text.";

            return '';
        }

        $text = trim($raw);

        if ($spec['type'] === 'image') {
            $inMediaLibrary = str_starts_with($text, 'uploads/') || str_starts_with($text, 'public/uploads/');
            if ($text !== '' && (!$inMediaLibrary || str_contains($text, '..') || strlen($text) > 255)) {
                $errors[] = "'{$id}' must be an image from the media library.";

                return '';
            }

            return $text;
        }

        if ($spec['type'] === 'rich') {
            $text = self::sanitizeRich($text);
        } else {
            $text = strip_tags($text);
        }

        if (mb_strlen($text) > $spec['max']) {
            $errors[] = "'{$id}' is longer than {$spec['max']} characters.";
        }

        return $text;
    }
}
