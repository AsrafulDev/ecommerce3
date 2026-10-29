<?php

namespace App\Services\Landing;

/**
 * The landing templates: which sections each template has, which element ids may be
 * stored for it, and the content a new landing page starts with.
 *
 * Element ids are stable strings. Two forms exist:
 *   section.element              e.g. hero.title
 *   section.item.N.field         e.g. problem.item.2.text (numbered list rows)
 *
 * Adding a template means adding one entry here, its Blade skeleton and its CSS.
 * The luxury template is provisional until the designs arrive (plan D1).
 */
final class TemplateRegistry
{
    public const LUXURY = 'luxury';

    /** Template keys that can be used for a new landing page. */
    public static function keys(): array
    {
        return array_keys(self::specs());
    }

    /** The name shown to the user for a template key. */
    public static function label(string $key): string
    {
        return [self::LUXURY => 'Luxury'][$key] ?? $key;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::specs());
    }

    /**
     * @return array{sections: array<string, array>}
     */
    public static function schema(string $key): array
    {
        return ['sections' => self::spec($key)['sections']];
    }

    /** The content a new landing page of this template starts with. */
    public static function defaultContent(string $key): array
    {
        $spec = self::spec($key);

        $content = [
            'theme' => $spec['theme'],
            'sections' => [],
            'elements' => [],
        ];

        foreach ($spec['sections'] as $section => $def) {
            $content['sections'][$section] = ['enabled' => true];

            foreach ($def['elements'] ?? [] as $id => $element) {
                $content['elements'][$id] = ['value' => $element['default']];
            }

            foreach ($def['lists'] ?? [] as $list => $listDef) {
                foreach ($listDef['defaults'] as $row => $fields) {
                    foreach ($fields as $field => $value) {
                        $content['elements']["{$section}.{$list}." . ($row + 1) . ".{$field}"] = ['value' => $value];
                    }
                }
            }
        }

        return $content;
    }

    /** @return array{theme: array<string, string>, sections: array<string, array>} */
    private static function spec(string $key): array
    {
        $specs = self::specs();

        if (!array_key_exists($key, $specs)) {
            throw new \InvalidArgumentException("Unknown landing template '{$key}'.");
        }

        return $specs[$key];
    }

    private static function specs(): array
    {
        return [
            self::LUXURY => [
                'theme' => [
                    'primary' => '#b08d57',
                    'accent' => '#1c1917',
                    'background' => '#faf8f5',
                    'text' => '#1c1917',
                ],
                'sections' => [
                    'hero' => [
                        'elements' => [
                            'hero.eyebrow' => ['type' => 'text', 'max' => 120, 'default' => 'Limited time offer'],
                            'hero.title' => ['type' => 'text', 'max' => 200, 'default' => 'Your headline here'],
                            'hero.subtitle' => ['type' => 'rich', 'max' => 1000, 'default' => 'Explain the offer in one or two lines.'],
                            'hero.image' => ['type' => 'image', 'max' => 255, 'default' => ''],
                            'hero.cta_label' => ['type' => 'text', 'max' => 60, 'default' => 'Order now'],
                        ],
                    ],
                    'problem' => [
                        'elements' => [
                            'problem.heading' => ['type' => 'text', 'max' => 200, 'default' => 'The problem'],
                        ],
                        'lists' => [
                            'item' => [
                                'max_items' => 6,
                                'fields' => [
                                    'title' => ['type' => 'text', 'max' => 150],
                                    'text' => ['type' => 'text', 'max' => 500],
                                ],
                                'defaults' => [
                                    ['title' => 'First problem', 'text' => 'Describe the pain point.'],
                                    ['title' => 'Second problem', 'text' => 'Describe another pain point.'],
                                ],
                            ],
                        ],
                    ],
                    'solution' => [
                        'elements' => [
                            'solution.heading' => ['type' => 'text', 'max' => 200, 'default' => 'The solution'],
                            'solution.body' => ['type' => 'rich', 'max' => 2000, 'default' => 'Explain how the product solves it.'],
                        ],
                    ],
                    'features' => [
                        'elements' => [
                            'features.heading' => ['type' => 'text', 'max' => 200, 'default' => "What's inside"],
                        ],
                        'lists' => [
                            'item' => [
                                'max_items' => 8,
                                'fields' => [
                                    'title' => ['type' => 'text', 'max' => 150],
                                    'text' => ['type' => 'text', 'max' => 500],
                                    'image' => ['type' => 'image', 'max' => 255],
                                ],
                                'defaults' => [
                                    ['title' => 'Feature one', 'text' => 'What it does for the customer.'],
                                ],
                            ],
                        ],
                    ],
                    'reviews' => [
                        'elements' => [
                            'reviews.heading' => ['type' => 'text', 'max' => 200, 'default' => 'What people say'],
                        ],
                        'lists' => [
                            'item' => [
                                'max_items' => 10,
                                'fields' => [
                                    'name' => ['type' => 'text', 'max' => 100],
                                    'rating' => ['type' => 'text', 'max' => 2],
                                    'text' => ['type' => 'text', 'max' => 500],
                                ],
                                'defaults' => [
                                    ['name' => 'Customer', 'rating' => '5', 'text' => 'Great product.'],
                                ],
                            ],
                        ],
                    ],
                    'faq' => [
                        'elements' => [
                            'faq.heading' => ['type' => 'text', 'max' => 200, 'default' => 'Questions'],
                        ],
                        'lists' => [
                            'item' => [
                                'max_items' => 10,
                                'fields' => [
                                    'q' => ['type' => 'text', 'max' => 250],
                                    'a' => ['type' => 'rich', 'max' => 1000],
                                ],
                                'defaults' => [
                                    ['q' => 'How long is delivery?', 'a' => 'Usually within two to three days.'],
                                ],
                            ],
                        ],
                    ],
                    // Always on. The order form cannot be switched off (plan D11).
                    'checkout' => [
                        'locked' => true,
                        'elements' => [
                            'checkout.heading' => ['type' => 'text', 'max' => 200, 'default' => 'Order now'],
                            'checkout.submit' => ['type' => 'text', 'max' => 60, 'default' => 'Confirm order'],
                            'checkout.note' => ['type' => 'text', 'max' => 300, 'default' => 'Cash on delivery.'],
                            'checkout.variants_label' => ['type' => 'text', 'max' => 120, 'default' => 'Choose an option'],
                            'checkout.label_name' => ['type' => 'text', 'max' => 60, 'default' => 'Name'],
                            'checkout.label_phone' => ['type' => 'text', 'max' => 60, 'default' => 'Mobile number'],
                            'checkout.label_address' => ['type' => 'text', 'max' => 60, 'default' => 'Address'],
                            'checkout.label_area' => ['type' => 'text', 'max' => 60, 'default' => 'Delivery area'],
                            'checkout.label_price' => ['type' => 'text', 'max' => 60, 'default' => 'Price'],
                            'checkout.placeholder_name' => ['type' => 'text', 'max' => 120, 'default' => 'Your name'],
                            'checkout.placeholder_phone' => ['type' => 'text', 'max' => 120, 'default' => '01XXXXXXXXX'],
                            'checkout.placeholder_address' => ['type' => 'text', 'max' => 120, 'default' => 'House, road, area'],
                            'checkout.placeholder_area' => ['type' => 'text', 'max' => 120, 'default' => 'Select area'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
