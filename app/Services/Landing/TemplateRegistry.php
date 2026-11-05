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
    public const FASHION = 'fashion';
    public const GADGET = 'gadget';
    public const GROCERY = 'grocery';

    /** Template keys that can be used for a new landing page. */
    public static function keys(): array
    {
        return array_keys(self::specs());
    }

    /** The name shown to the user for a template key. */
    public static function label(string $key): string
    {
        return [
            self::LUXURY => 'Luxury',
            self::FASHION => 'Fashion',
            self::GADGET => 'Gadget',
            self::GROCERY => 'Grocery & organic',
        ][$key] ?? $key;
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
                    // Always on, like the other templates' header.
                    'nav' => [
                        'locked' => true,
                        'elements' => [
                            'nav.brand' => ['type' => 'text', 'max' => 40, 'default' => 'আপনার ব্র্যান্ড'],
                            'nav.brand_sub' => ['type' => 'text', 'max' => 60, 'default' => 'LUXURY COLLECTION'],
                            'nav.cta' => ['type' => 'text', 'max' => 40, 'default' => 'অর্ডার করুন'],
                        ],
                    ],
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
                    // Its own section, separate from the features: a main photo with thumbnails to browse the rest.
                    // The heading starts empty, so the section stays off the live page until photos are added.
                    'gallery' => [
                        'elements' => [
                            'gallery.heading' => ['type' => 'text', 'max' => 200, 'default' => ''],
                        ],
                        'lists' => [
                            'item' => [
                                'max_items' => 8,
                                'fields' => [
                                    'image' => ['type' => 'image', 'max' => 255],
                                    'title' => ['type' => 'text', 'max' => 120],
                                    'text' => ['type' => 'text', 'max' => 300],
                                ],
                                'defaults' => [],
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
                            // Photo reviews: an image only, shown in a slider. Only the photos that are set appear on the live page.
                            'photo' => [
                                'max_items' => 10,
                                'fields' => [
                                    'image' => ['type' => 'image', 'max' => 255],
                                ],
                                'defaults' => [],
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
                    'checkout' => self::checkout(),
                ],
            ],
            self::FASHION => self::fashionSpec(),
            self::GADGET => self::gadgetSpec(),
            self::GROCERY => self::grocerySpec(),
        ];
    }

    /** One list-driven section schema: heading plus repeatable items. */
    private static function item(int $max, array $fields, array $defaults): array
    {
        return ['max_items' => $max, 'fields' => $fields, 'defaults' => $defaults];
    }

    private static function text(string $default, int $max = 200): array
    {
        return ['type' => 'text', 'max' => $max, 'default' => $default];
    }

    private static function rich(string $default, int $max = 1000): array
    {
        return ['type' => 'rich', 'max' => $max, 'default' => $default];
    }

    /**
     * The order form, shared by every template: the labels, placeholders and wording are page content,
     * so each template's form can be edited. Its layout is the shared partial (partials/landing_checkout).
     */
    private static function checkout(): array
    {
        return [
            'locked' => true,
            'elements' => [
                'checkout.eyebrow' => self::text('অর্ডার ফর্ম', 80),
                'checkout.heading' => self::text('আপনার অর্ডার দিন', 200),
                'checkout.subtitle' => self::text('নিচের তথ্যগুলো দিন, আমাদের প্রতিনিধি ফোন করে অর্ডার নিশ্চিত করবেন।', 300),
                'checkout.section_product' => self::text('১. প্রোডাক্ট অপশন', 120),
                'checkout.label_color' => self::text('রঙ', 60),
                'checkout.label_size' => self::text('সাইজ', 60),
                'checkout.size_help' => self::text('নিশ্চিত না হলে সাইজ চার্ট দেখুন', 200),
                'checkout.label_quantity' => self::text('পরিমাণ', 60),
                'checkout.label_note' => self::text('অর্ডার নোট (ঐচ্ছিক)', 120),
                'checkout.placeholder_note' => self::text('যেমন: ২টি সেট — একটি সেজ M, একটি আইভরি L', 200),
                'checkout.section_info' => self::text('২. আপনার তথ্য', 120),
                'checkout.label_name' => self::text('আপনার নাম', 60),
                'checkout.placeholder_name' => self::text('যেমন: নুসরাত জাহান', 120),
                'checkout.label_phone' => self::text('মোবাইল নম্বর', 60),
                'checkout.placeholder_phone' => self::text('01XXXXXXXXX', 120),
                'checkout.phone_hint' => self::text('এই নম্বরে ফোন করে অর্ডার কনফার্ম করা হবে', 200),
                'checkout.label_address' => self::text('পূর্ণ ঠিকানা', 60),
                'checkout.placeholder_address' => self::text('বাসা/ফ্ল্যাট, রোড, এলাকা, থানা, জেলা', 160),
                'checkout.section_area' => self::text('৩. ডেলিভারি এলাকা', 120),
                'checkout.label_subtotal' => self::text('সাবটোটাল', 60),
                'checkout.label_delivery' => self::text('ডেলিভারি চার্জ', 60),
                'checkout.delivery_pick' => self::text('এলাকা নির্বাচন করুন', 80),
                'checkout.label_price' => self::text('সর্বমোট', 60),
                'checkout.submit' => self::text('অর্ডার কনফার্ম করুন', 60),
                'checkout.note' => self::text('পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন', 300),
            ],
        ];
    }

    /** Hero used by both new templates: eyebrow, headline, lead, image and the order button. */
    private static function hero(string $eyebrow, string $title, string $subtitle): array
    {
        return [
            'elements' => [
                'hero.eyebrow' => self::text($eyebrow, 120),
                'hero.title' => self::text($title, 200),
                'hero.subtitle' => self::rich($subtitle, 1000),
                'hero.image' => ['type' => 'image', 'max' => 255, 'default' => ''],
                'hero.cta_label' => self::text('Order now', 60),
            ],
        ];
    }

    private static function fashionSpec(): array
    {
        $item = fn (int $max, array $fields, array $defaults) => self::item($max, $fields, $defaults);
        $t = fn (string $d, int $max = 200) => self::text($d, $max);

        return [
            'theme' => ['primary' => '#9b5c52', 'accent' => '#2b2622', 'background' => '#faf7f2', 'text' => '#2b2622'],
            'sections' => [
                'nav' => [
                    'locked' => true,
                    'elements' => [
                        'nav.brand' => $t('ÉLAN', 40),
                        'nav.brand_sub' => $t('STUDIO · DHAKA', 60),
                        'nav.cta' => $t('অর্ডার করুন', 40),
                    ],
                ],
                'hero' => [
                    'elements' => [
                        'hero.eyebrow' => $t('নতুন কালেকশন · THE SAANJH EDIT', 120),
                        'hero.title' => $t('সাঁঝ লিনেন থ্রি-পিস', 200),
                        'hero.tagline' => $t('quiet, everyday grace', 120),
                        'hero.subtitle' => self::rich('নরম লিনেন-কটন কাপড়, নেকলাইনে হাতের মতো সূক্ষ্ম টোনাল এমব্রয়ডারি আর হালকা অরগাঞ্জা দোপাট্টা — অফিস থেকে দাওয়াত, এক সেটেই।', 1000),
                        'hero.price' => $t('৳১,৮৯০', 40),
                        'hero.old_price' => $t('৳২,৪৫০', 40),
                        'hero.badge' => $t('লঞ্চ অফার · ২৩% ছাড়', 80),
                        'hero.note' => $t('২টি নিলে অতিরিক্ত ৫% ছাড় · ৩টি নিলে ১০% ছাড় ও ফ্রি ডেলিভারি', 200),
                        'hero.cta_label' => $t('অর্ডার করুন', 60),
                        'hero.cta_secondary' => $t('বিস্তারিত দেখুন', 60),
                        'hero.image' => ['type' => 'image', 'max' => 255, 'default' => ''],
                    ],
                    'lists' => ['perk' => $item(4, ['text' => ['type' => 'text', 'max' => 80]], [
                        ['text' => 'ক্যাশ অন ডেলিভারি'],
                        ['text' => 'সাইজ এক্সচেঞ্জ সুবিধা'],
                        ['text' => 'সারা দেশে ডেলিভারি'],
                    ])],
                ],
                'benefits' => [
                    'elements' => [
                        'benefits.eyebrow' => $t('কেন ভালো লাগবে', 80),
                        'benefits.heading' => $t('পরতে আরাম, দেখতে পরিপাটি', 200),
                    ],
                    'lists' => ['item' => $item(4, [
                        'icon' => ['type' => 'icon', 'max' => 40],
                        'title' => ['type' => 'text', 'max' => 150],
                        'text' => ['type' => 'text', 'max' => 300],
                    ], [
                        ['icon' => 'leaf', 'title' => 'হালকা ও আরামদায়ক', 'text' => 'লিনেন-কটন মিশ্রণের কাপড় শরীরে ভারী লাগে না, সারাদিন পরে থাকা সহজ।'],
                        ['icon' => 'shirt', 'title' => 'রিলাক্সড স্ট্রেইট কাট', 'text' => 'ঢিলেঢালা কিন্তু গোছানো ফিট — শালীন ও আধুনিক, দুটোই।'],
                        ['icon' => 'wand-magic-sparkles', 'title' => 'সূক্ষ্ম টোনাল এমব্রয়ডারি', 'text' => 'কাপড়ের রঙেই সুতার কাজ, তাই জমকালো না হয়েও নজরকাড়া।'],
                        ['icon' => 'palette', 'title' => '৪টি নরম রঙ', 'text' => 'আইভরি, ডাস্টি রোজ, সেজ ও চারকোল — প্রতিটি রঙ সহজে মেলানো যায়।'],
                    ])],
                ],
                'details' => [
                    'elements' => [
                        'details.eyebrow' => $t('প্রোডাক্ট ডিটেইলস', 80),
                        'details.heading' => $t('সেটে যা যা থাকছে', 200),
                        'details.body' => self::rich('তিন পিসের পূর্ণ সেট: এমব্রয়ডারি করা কামিজ, ম্যাচিং স্ট্রেইট প্যান্ট এবং অরগাঞ্জা দোপাট্টা। প্রতিটি সেট আলাদা প্যাকেটে, ভাঁজ করে পাঠানো হয়।', 1000),
                        'details.image' => ['type' => 'image', 'max' => 255, 'default' => ''],
                        'details.image_2' => ['type' => 'image', 'max' => 255, 'default' => ''],
                    ],
                    'lists' => [
                        'row' => $item(8, [
                            'label' => ['type' => 'text', 'max' => 60],
                            'value' => ['type' => 'text', 'max' => 200],
                        ], [
                            ['label' => 'কাপড়', 'value' => '৬০% লিনেন, ৪০% কটন (কামিজ ও প্যান্ট)'],
                            ['label' => 'দোপাট্টা', 'value' => 'সফট অরগাঞ্জা, টোনাল বুটি'],
                            ['label' => 'কাজ', 'value' => 'নেকলাইন ও হাতায় টোনাল সুতার এমব্রয়ডারি'],
                            ['label' => 'ফিট', 'value' => 'রিলাক্সড স্ট্রেইট কাট, কিছোল নেকলাইন'],
                            ['label' => 'কামিজের দৈর্ঘ্য', 'value' => '৪৪ ইঞ্চি (সব সাইজে)'],
                            ['label' => 'প্যান্ট', 'value' => 'ইলাস্টিক ও ফিতাসহ কোমর, স্ট্রেইট লেগ'],
                        ]),
                    ],
                ],
                'care' => [
                    'elements' => ['care.heading' => $t('যত্নের নিয়ম', 200)],
                    'lists' => ['item' => $item(6, ['text' => ['type' => 'text', 'max' => 200]], [
                        ['text' => 'প্রথমবার আলাদা করে ঠান্ডা পানিতে ধুয়ে নিন'],
                        ['text' => 'হালকা ডিটার্জেন্ট ব্যবহার করুন, ব্লিচ নয়'],
                        ['text' => 'উল্টো করে ছায়ায় শুকান'],
                        ['text' => 'মাঝারি তাপে উল্টো পাশ থেকে ইস্ত্রি করুন'],
                    ])],
                ],
                'craft' => [
                    'elements' => [
                        'craft.eyebrow' => $t('আমাদের প্রতিশ্রুতি', 80),
                        'craft.heading' => $t('যত্ন করে বানানো, সৎভাবে বর্ণনা করা', 200),
                        'craft.body' => self::rich('আমরা যা বলি, প্যাকেট খুলে ঠিক তাই পাবেন। প্রতিটি তথ্য ডেলিভারির আগে আবার মিলিয়ে দেখা হয়।', 1000),
                    ],
                    'lists' => ['item' => $item(4, [
                        'title' => ['type' => 'text', 'max' => 150],
                        'text' => ['type' => 'text', 'max' => 300],
                    ], [
                        ['title' => 'প্রতিটি পিস হাতে চেক করা', 'text' => 'প্যাকিংয়ের আগে সেলাই, এমব্রয়ডারি ও মাপ মিলিয়ে দেখা হয়।'],
                        ['title' => 'পরিপাটি ফিনিশিং', 'text' => 'ভেতরের সেলাই ওভারলক করা, যাতে ধোয়ার পরও সুতা না বের হয়।'],
                        ['title' => 'আসল রঙের ছবি', 'text' => 'ছবিগুলো প্রাকৃতিক আলোয় তোলা; স্ক্রিনভেদে সামান্য পার্থক্য হতে পারে।'],
                        ['title' => 'উপহার-উপযোগী প্যাকেজিং', 'text' => 'টিস্যু র‍্যাপ ও বক্সে সাজিয়ে পাঠানো হয়।'],
                    ])],
                ],
                'gallery' => [
                    'elements' => [
                        'gallery.eyebrow' => $t('লুকবুক', 80),
                        'gallery.heading' => $t('কাছ থেকে দেখুন', 200),
                    ],
                    // No preset slots: photos are added as needed, up to 24.
                    'lists' => ['item' => $item(24, [
                        'image' => ['type' => 'image', 'max' => 255],
                        'title' => ['type' => 'text', 'max' => 120],
                    ], [])],
                ],
                'reviews' => [
                    'elements' => [
                        'reviews.eyebrow' => $t('ক্রেতাদের অভিজ্ঞতা', 80),
                        'reviews.heading' => $t('রিভিউ ও ছবি', 200),
                        'reviews.empty_title' => $t('এই কালেকশনটি একদম নতুন', 200),
                        'reviews.empty_text' => $t('এখনো কোনো যাচাইকৃত রিভিউ নেই। আমরা শুধু প্রকৃত ক্রেতাদের রিভিউ ও ছবি এখানে প্রকাশ করি — আপনার অভিজ্ঞতা জানালে সবার আগে এখানে দেখা যাবে।', 400),
                    ],
                    'lists' => [
                        'item' => $item(8, [
                            'name' => ['type' => 'text', 'max' => 100],
                            'rating' => ['type' => 'text', 'max' => 2],
                            'text' => ['type' => 'text', 'max' => 500],
                        ], []),
                        'photo' => $item(8, [
                            'image' => ['type' => 'image', 'max' => 255],
                            'name' => ['type' => 'text', 'max' => 100],
                            'rating' => ['type' => 'text', 'max' => 2],
                        ], []),
                    ],
                ],
                'offer' => [
                    'elements' => [
                        'offer.eyebrow' => $t('দাম ও অফার', 80),
                        'offer.heading' => $t('আপনার সেট বেছে নিন', 200),
                        'offer.product_name' => $t('সাঁঝ লিনেন থ্রি-পিস', 120),
                        'offer.product_sub' => $t('Saanjh Linen Three-Piece', 120),
                        'offer.price' => $t('৳১,৮৯০', 40),
                        'offer.old_price' => $t('৳২,৪৫০', 40),
                        'offer.cta_label' => $t('এই অপশনে অর্ডার করুন', 80),
                        'offer.note' => self::rich('লঞ্চ অফার স্টক থাকা পর্যন্ত প্রযোজ্য। একাধিক সেটে ভিন্ন রং বা সাইজ চাইলে অর্ডার নোটে লিখে দিন। XXL সাইজে প্রতি সেটে ৳৮০০ অতিরিক্ত।', 600),
                        'offer.image' => ['type' => 'image', 'max' => 255, 'default' => ''],
                    ],
                    'lists' => ['tier' => $item(3, [
                        'title' => ['type' => 'text', 'max' => 100],
                        'text' => ['type' => 'text', 'max' => 200],
                    ], [
                        ['title' => '১টি সেট', 'text' => 'লঞ্চ অফার মূল্য'],
                        ['title' => '২টি সেট', 'text' => 'মোট দামে অতিরিক্ত ৫% ছাড়'],
                        ['title' => '৩টি বা বেশি', 'text' => '১০% ছাড় + ফ্রি ডেলিভারি'],
                    ])],
                ],
                'policy' => [
                    'elements' => [
                        'policy.eyebrow' => $t('ডেলিভারি ও পলিসি', 80),
                        'policy.heading' => $t('অর্ডারের আগে জেনে নিন', 200),
                        'policy.delivery_title' => $t('ডেলিভারি চার্জ ও সময়', 120),
                        'policy.payment_title' => $t('পেমেন্ট পদ্ধতি', 120),
                    ],
                    'lists' => [
                        'zone' => $item(4, [
                            'label' => ['type' => 'text', 'max' => 100],
                            'days' => ['type' => 'text', 'max' => 60],
                            'price' => ['type' => 'text', 'max' => 20],
                        ], [
                            ['label' => 'ঢাকা সিটির ভেতরে', 'days' => '১–২ কর্মদিবস', 'price' => '৳৭০'],
                            ['label' => 'ঢাকার আশেপাশে', 'days' => 'সাভার, গাজীপুর, নারায়ণগঞ্জ · ২–৩ কর্মদিবস', 'price' => '৳১০০'],
                            ['label' => 'ঢাকার বাইরে', 'days' => 'সারা বাংলাদেশ · ৩–৫ কর্মদিবস', 'price' => '৳১৩০'],
                        ]),
                        'payment' => $item(4, ['text' => ['type' => 'text', 'max' => 120]], [
                            ['text' => 'ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে মূল্য পরিশোধ'],
                            ['text' => 'বিকাশ — অর্ডার কনফার্মের সময় নম্বর জানানো হবে'],
                            ['text' => 'নগদ — অর্ডার কনফার্মের সময় নম্বর জানানো হবে'],
                        ]),
                        'item' => $item(4, [
                            'title' => ['type' => 'text', 'max' => 120],
                            'text' => ['type' => 'text', 'max' => 300],
                        ], [
                            ['title' => 'সাইজ এক্সচেঞ্জ', 'text' => 'পণ্য হাতে পাওয়ার ৩ দিনের মধ্যে একবার সাইজ বদল করা যাবে। পণ্য অব্যবহৃত, ট্যাগসহ থাকতে হবে।'],
                            ['title' => 'রিটার্ন', 'text' => 'ভুল বা ক্রটিপূর্ণ পণ্য পেলে ডেলিভারিম্যানের সামনেই চেক করে ফেরত দিতে পারবেন।'],
                            ['title' => 'ডেলিভারির সময় চেক', 'text' => 'পার্সেল খুলে রঙ ও সাইজ মিলিয়ে নিন। গরমিল হলে শুধু ডেলিভারি চার্জ দিয়ে ফেরত দিতে পারবেন।'],
                            ['title' => 'অর্ডার কনফার্মেশন', 'text' => 'অর্ডারের পর আমাদের প্রতিনিধি ফোন করে বিস্তারিত নিশ্চিত করবেন, তারপর পার্সেল পাঠানো হবে।'],
                        ]),
                    ],
                ],
                'faq' => [
                    'elements' => [
                        'faq.eyebrow' => $t('প্রশ্ন ও উত্তর', 80),
                        'faq.heading' => $t('সাধারণ জিজ্ঞাসা', 200),
                    ],
                    'lists' => ['item' => $item(8, [
                        'q' => ['type' => 'text', 'max' => 250],
                        'a' => ['type' => 'rich', 'max' => 1000],
                    ], [
                        ['q' => 'আমার সাইজ কীভাবে বুঝব?', 'a' => 'সাইজ চার্টের বুক ও কোমরের মাপ মিলিয়ে নিন।'],
                        ['q' => 'কাপড় কি গরমে আরামদায়ক?', 'a' => 'হ্যাঁ, লিনেন-কটন মিশ্রণ গরমে হালকা থাকে।'],
                        ['q' => 'ডেলিভারি কত দিনে পাব?', 'a' => 'ঢাকার ভেতরে ১–২ কর্মদিবস, ঢাকার বাইরে ৩–৫ কর্মদিবস।'],
                    ])],
                ],
                'final' => [
                    'elements' => [
                        'final.eyebrow' => $t('সীমিত স্টক', 80),
                        'final.heading' => $t('এই মৌসুমের সবচেয়ে সহজ সাজ', 200),
                        'final.text' => $t('পছন্দের রঙ ও সাইজ বেছে নিন — বাকিটা আমরা দেখছি।', 300),
                        'final.price' => $t('৳১,৮৯০', 40),
                        'final.old_price' => $t('৳২,৪৫০', 40),
                        'final.cta_label' => $t('এখনই অর্ডার করুন', 60),
                        'final.image' => ['type' => 'image', 'max' => 255, 'default' => ''],
                    ],
                ],
                'checkout' => self::fashionCheckout(),
            ],
        ];
    }

    /** The Fashion order form, with the design's Bangla heading and button text. */
    private static function fashionCheckout(): array
    {
        $checkout = self::checkout();
        $checkout['elements']['checkout.heading']['default'] = 'আপনার অর্ডার দিন';
        $checkout['elements']['checkout.submit']['default'] = 'অর্ডার কনফার্ম করুন';
        $checkout['elements']['checkout.note']['default'] = 'নিচের তথ্যগুলো দিন, আমাদের প্রতিনিধি ফোন করে অর্ডার নিশ্চিত করবেন।';

        return $checkout;
    }

    /** Grocery & organic (the Matir Ghran design): cream and green, a wood-pressed oil product page. */
    private static function grocerySpec(): array
    {
        $item = fn (int $max, array $fields, array $defaults) => self::item($max, $fields, $defaults);
        $t = fn (string $d, int $max = 200) => self::text($d, $max);
        $icon = ['type' => 'icon', 'max' => 40];

        return [
            'theme' => ['primary' => '#2f6b3f', 'accent' => '#1d3a25', 'background' => '#f8f4ea', 'text' => '#1d2b20'],
            'sections' => [
                'nav' => [
                    'locked' => true,
                    'elements' => [
                        'nav.notice' => $t('নমুনা কনটেন্ট — পণ্যের যাচাইকৃত তথ্য, ছবি ও মূল্য দিয়ে প্রতিস্থাপন করুন', 200),
                        'nav.brand' => $t('মাটির ঘ্রাণ', 40),
                        'nav.brand_sub' => $t('MATIR GHRAN', 60),
                        'nav.cta' => $t('অর্ডার করুন', 40),
                    ],
                ],
                'hero' => [
                    'elements' => [
                        'hero.eyebrow' => $t('রান্নার তেল · COOKING OIL', 120),
                        'hero.title' => $t('রান্নায় চেনা ঝাঁজ, বোতলে স্পষ্ট তথ্য', 200),
                        'hero.tagline' => $t('ঘানি-ভাঙা সরিষার তেল · Wood-Pressed Mustard Oil', 160),
                        'hero.subtitle' => self::rich('কাঠের ঘানিতে ধীরে ভাঙা সরিষা থেকে তৈরি। লেবেলে উপাদান, ব্যাচ নম্বর, উৎপাদন ও মেয়াদের তারিখ — সবকিছু লেখা থাকে, যাতে আপনি জেনে-বুঝে কিনতে পারেন।', 1000),
                        'hero.price' => $t('৳৫২০', 40),
                        'hero.old_price' => $t('৳৬০০', 40),
                        'hero.badge' => $t('লঞ্চ অফার · ২৩% ছাড়', 80),
                        'hero.size_note' => $t('সাইজ: ৫০০ মিলি · ১ লিটার · ২ লিটার', 120),
                        'hero.cta_label' => $t('অর্ডার করুন', 60),
                        'hero.cta_secondary' => $t('বিস্তারিত দেখুন', 60),
                        'hero.image' => ['type' => 'image', 'max' => 255, 'default' => ''],
                    ],
                    'lists' => ['perk' => $item(4, ['text' => ['type' => 'text', 'max' => 80]], [
                        ['text' => 'ক্যাশ অন ডেলিভারি'],
                        ['text' => 'একক উপাদান: সরিষা বীজ'],
                        ['text' => 'সারা দেশে ডেলিভারি'],
                    ])],
                ],
                'benefits' => [
                    'elements' => [
                        'benefits.eyebrow' => $t('মূল বৈশিষ্ট্য', 80),
                        'benefits.heading' => $t('যা লেবেলে লেখা, তাই বোতলে', 200),
                        'benefits.subtitle' => $t('পণ্যের যাচাইযোগ্য বৈশিষ্ট্যগুলো — কোনো অতিরঞ্জন ছাড়া।', 200),
                    ],
                    'lists' => ['item' => $item(4, [
                        'icon' => $icon,
                        'title' => ['type' => 'text', 'max' => 150],
                        'text' => ['type' => 'text', 'max' => 300],
                    ], [
                        ['icon' => 'leaf', 'title' => 'একক উপাদান', 'text' => 'লেবেল অনুযায়ী উপাদান কেবল সরিষা বীজ। কোনো রং বা সুগন্ধি যোগ করা হয় না।'],
                        ['icon' => 'location-dot', 'title' => 'উৎসের তথ্য লেবেলে', 'text' => 'বীজ সংগ্রহের অঞ্চল ও ব্যাচ নম্বর প্রতিটি বোতলে মুদ্রিত থাকে।'],
                        ['icon' => 'box-open', 'title' => 'আলো-রোধী বোতল', 'text' => 'অ্যাম্বার কাচের বোতল ও সিল করা ক্যাপ — সংরক্ষণ ও বহনে সুবিধাজনক।'],
                        ['icon' => 'bowl-food', 'title' => 'চেনা ঝাঁজ ও ঘ্রাণ', 'text' => 'ভর্তা, ভাজি, আচার, মাছ রান্না — দেশি রান্নার প্রতিদিনের ব্যবহারের জন্য।'],
                    ])],
                ],
                'details' => [
                    'elements' => [
                        'details.eyebrow' => $t('পণ্যের বিস্তারিত', 80),
                        'details.heading' => $t('ঘানি-ভাঙা সরিষার তেল', 200),
                        'details.body' => self::rich('কেনার আগে যা জানা দরকার, সব এক জায়গায়। নিচের তথ্য পণ্যের লেবেলের সাথে মিলিয়ে নিন।', 1000),
                        'details.ingredient_title' => $t('উপাদান', 60),
                        'details.ingredient' => $t('সরিষা বীজ (Brassica) — ১০০%', 160),
                        'details.care_title' => $t('সংরক্ষণ নির্দেশনা', 80),
                        'details.label_title' => $t('লেবেলের তথ্য', 80),
                    ],
                    'lists' => [
                        'row' => $item(8, [
                            'label' => ['type' => 'text', 'max' => 60],
                            'value' => ['type' => 'text', 'max' => 200],
                        ], [
                            ['label' => 'নিট পরিমাণ', 'value' => '৫০০ মিলি / ১ লিটার / ২ লিটার'],
                            ['label' => 'উৎপাদন পদ্ধতি', 'value' => 'কাঠের ঘানিতে ভাঙা, ছাঁকা ও বোতলজাত'],
                            ['label' => 'বীজের উৎস', 'value' => 'সিরাজগঞ্জ, বাংলাদেশ (নমুনা — যাচাইকৃত তথ্য দিন)'],
                            ['label' => 'প্যাকেজিং', 'value' => 'অ্যাম্বার কাচের বোতল, সিল করা ক্যাপ'],
                            ['label' => 'সর্বোত্তম ব্যবহার', 'value' => 'উৎপাদনের তারিখ থেকে ১২ মাস (লেবেল দেখুন)'],
                        ]),
                        'care' => $item(6, ['text' => ['type' => 'text', 'max' => 200]], [
                            ['text' => 'ঠাণ্ডা ও শুকনো জায়গায়, সরাসরি রোদ থেকে দূরে রাখুন।'],
                            ['text' => 'ব্যবহারের পর ঢাকনা শক্ত করে বন্ধ করুন।'],
                            ['text' => 'শীতে তেল কিছুটা ঘন হওয়া স্বাভাবিক।'],
                        ]),
                        'label' => $item(6, [
                            'label' => ['type' => 'text', 'max' => 60],
                            'value' => ['type' => 'text', 'max' => 200],
                        ], [
                            ['label' => 'উৎপাদন ও মেয়াদের তারিখ', 'value' => 'প্রতিটি বোতলের লেবেলে মুদ্রিত'],
                            ['label' => 'ব্যাচ নম্বর', 'value' => 'লেবেলে মুদ্রিত'],
                            ['label' => 'অ্যালার্জেন', 'value' => 'সরিষা (Mustard)'],
                            ['label' => 'লাইসেন্স / নিবন্ধন', 'value' => 'প্রয়োজ্য নম্বর এখানে লিখুন'],
                        ]),
                    ],
                ],
                'concerns' => [
                    'elements' => [
                        'concerns.eyebrow' => $t('আপনার প্রয়োজন', 80),
                        'concerns.heading' => $t('যে প্রশ্নগুলো নিয়ে আমরা শুরু করেছিলাম', 200),
                        'concerns.subtitle' => $t('সাধারণ রান্নার প্রয়োজন, সহজ সমাধান। কোনো রোগ নিরাময়ের দাবি নয়।', 200),
                    ],
                    'lists' => ['item' => $item(6, [
                        'concern' => ['type' => 'text', 'max' => 200],
                        'answer' => ['type' => 'text', 'max' => 250],
                    ], [
                        ['concern' => 'খোলা তেলে উপাদান, উৎস বা তারিখ জানার উপায় থাকে না।', 'answer' => 'লেবেলে উপাদান, বীজের অঞ্চল, ব্যাচ ও মেয়াদের তারিখ স্পষ্টভাবে লেখা।'],
                        ['concern' => 'দেশি রান্নায় সরিষার চেনা ঝাঁজ ও ঘ্রাণ খুঁজছেন।', 'answer' => 'ঘানি-ভাঙা তেলের স্বাভাবিক ঝাঁজ — ভর্তা থেকে মাছের ঝোল পর্যন্ত।'],
                        ['concern' => 'বড় টিন সামলানো ও সংরক্ষণ ঝামেলার।', 'answer' => '৫০০ মিলি থেকে ২ লিটার — পরিবারের প্রয়োজন মতো সাইজ বেছে নিন।'],
                        ['concern' => 'উপহার দেওয়ার মতো গোছানো কিছু চাই।', 'answer' => 'কাচের বোতল ও কার্টন প্যাকেজিং — উপহার হিসেবেও মানানসই।'],
                    ])],
                ],
                'gallery' => [
                    'elements' => [
                        'gallery.eyebrow' => $t('কাছ থেকে দেখুন', 80),
                        'gallery.heading' => $t('রং, ঘ্রাণ আর রান্নাঘরের গল্প', 200),
                    ],
                    // No preset slots: photos are added as needed, up to 6.
                    'lists' => ['item' => $item(6, [
                        'image' => ['type' => 'image', 'max' => 255],
                        'title' => ['type' => 'text', 'max' => 120],
                    ], [])],
                ],
                'trust' => [
                    'elements' => [
                        'trust.eyebrow' => $t('কেন এই পণ্য', 80),
                        'trust.heading' => $t('যা আমরা দেখাতে পারি, শুধু তাই বলি', 200),
                        'trust.subtitle' => $t('প্রতিটি পার্থক্যের পেছনে কী প্রমাণ আছে, তা পাশে উল্লেখ করা হয়েছে।', 200),
                    ],
                    'lists' => ['item' => $item(4, [
                        'icon' => $icon,
                        'title' => ['type' => 'text', 'max' => 150],
                        'text' => ['type' => 'text', 'max' => 300],
                    ], [
                        ['icon' => 'seedling', 'title' => 'উৎপাদন পদ্ধতি', 'text' => 'সরিষা কাঠের ঘানিতে ধীরে ভাঙা হয়, এরপর ছেঁকে বোতলজাত করা হয়।'],
                        ['icon' => 'filter', 'title' => 'ব্যাচভিত্তিক মান যাচাই', 'text' => 'প্রতি ব্যাচ বোতলজাতের আগে রং, ঘ্রাণ ও স্বচ্ছতা দেখে নেওয়া হয়।'],
                        ['icon' => 'barcode', 'title' => 'ট্রেসযোগ্য ব্যাচ নম্বর', 'text' => 'লেবেলের ব্যাচ নম্বর দিয়ে উৎপাদনের তারিখ ও বীজের লট মেলানো যায়।'],
                        ['icon' => 'ban', 'title' => 'যোগ করা কিছু নেই', 'text' => 'লেবেল অনুযায়ী কোনো রং, ফ্লেভার বা অন্য তেল মেশানো হয় না।'],
                    ])],
                ],
                'reviews' => [
                    'elements' => [
                        'reviews.eyebrow' => $t('গ্রাহকের মতামত', 80),
                        'reviews.heading' => $t('যাঁরা কিনেছেন, তাঁরা কী বলছেন', 200),
                        'reviews.subtitle' => $t('শুধু প্রকৃত ক্রেতাদের মতামত, তাঁদের অনুমতি নিয়ে প্রকাশিত।', 200),
                        'reviews.empty_title' => $t('এখনো কোনো রিভিউ প্রকাশ করা হয়নি', 200),
                        'reviews.empty_text' => $t('আমরা বানানো রিভিউ দেখাই না। পণ্য হাতে পাওয়ার পর আপনার অভিজ্ঞতা ও ছবি জানালে, অনুমতি নিয়ে এখানে প্রকাশ করা হবে।', 400),
                    ],
                    'lists' => ['item' => $item(10, [
                        'name' => ['type' => 'text', 'max' => 100],
                        'rating' => ['type' => 'text', 'max' => 2],
                        'text' => ['type' => 'text', 'max' => 500],
                    ], [])],
                ],
                'offer' => [
                    'elements' => [
                        'offer.eyebrow' => $t('মূল্য ও অফার', 80),
                        'offer.heading' => $t('আপনার প্রয়োজনমতো সাইজ বেছে নিন', 200),
                        'offer.subtitle' => $t('সাইজ নির্বাচন করলে অর্ডার ফর্মে স্বয়ংক্রিয়ভাবে যুক্ত হবে।', 200),
                        'offer.bundle_title' => $t('বান্ডেল ছাড়', 80),
                        'offer.bundle_1' => $t('২+ বোতল → ৫% ছাড়', 60),
                        'offer.bundle_2' => $t('৪+ বোতল → ৮% ছাড়', 60),
                        'offer.note' => self::rich('ছাড় শুধু পণ্যমূল্যে প্রযোজ্য, ডেলিভারি চার্জে নয়।<br>অফার স্টক থাকা পর্যন্ত বা পরবর্তী ঘোষণা পর্যন্ত চলবে।<br>অন্য কোনো অফারের সাথে একত্রে প্রযোজ্য নয়।', 600),
                        'offer.cta_label' => $t('অর্ডার করুন', 80),
                    ],
                    'lists' => ['size' => $item(3, [
                        'name' => ['type' => 'text', 'max' => 60],
                        'size' => ['type' => 'text', 'max' => 40],
                        'price' => ['type' => 'text', 'max' => 20],
                        'old_price' => ['type' => 'text', 'max' => 20],
                        'unit' => ['type' => 'text', 'max' => 40],
                        'saving' => ['type' => 'text', 'max' => 40],
                        'badge' => ['type' => 'text', 'max' => 20],
                    ], [
                        ['name' => 'ছোট বোতল', 'size' => '৫০০ মিলি', 'price' => '৳২৮০', 'old_price' => '৳৩২০', 'unit' => 'প্রতি বোতল', 'saving' => '৳৪০ সাশ্রয়', 'badge' => ''],
                        ['name' => 'পরিবারের জন্য', 'size' => '১ লিটার', 'price' => '৳৫২০', 'old_price' => '৳৬০০', 'unit' => 'প্রতি বোতল', 'saving' => '৳৮০ সাশ্রয়', 'badge' => 'জনপ্রিয়'],
                        ['name' => 'সাশ্রয়ী সাইজ', 'size' => '২ লিটার', 'price' => '৳১,০০০', 'old_price' => '৳১,১৮০', 'unit' => 'প্রতি বোতল', 'saving' => '৳১৮০ সাশ্রয়', 'badge' => 'সাশ্রয়ী'],
                    ])],
                ],
                'policy' => [
                    'elements' => [
                        'policy.eyebrow' => $t('ডেলিভারি, পেমেন্ট ও নীতিমালা', 80),
                        'policy.heading' => $t('অর্ডারের আগে সব শর্ত পরিষ্কার', 200),
                        'policy.delivery_title' => $t('ডেলিভারি চার্জ ও সময়', 120),
                        'policy.payment_title' => $t('পেমেন্ট পদ্ধতি', 120),
                    ],
                    'lists' => [
                        'zone' => $item(4, [
                            'label' => ['type' => 'text', 'max' => 100],
                            'days' => ['type' => 'text', 'max' => 60],
                            'price' => ['type' => 'text', 'max' => 20],
                        ], [
                            ['label' => 'ঢাকা সিটির ভিতরে', 'days' => '১–২ কর্মদিবস', 'price' => '৳৭০'],
                            ['label' => 'ঢাকার আশেপাশে (সাভার, গাজীপুর, নারায়ণগঞ্জ)', 'days' => '২–৩ কর্মদিবস', 'price' => '৳১০০'],
                            ['label' => 'ঢাকার বাইরে', 'days' => '৩–৫ কর্মদিবস', 'price' => '৳১৩০'],
                        ]),
                        'payment' => $item(4, [
                            'text' => ['type' => 'text', 'max' => 120],
                            'status' => ['type' => 'text', 'max' => 20],
                        ], [
                            ['text' => 'ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন', 'status' => 'চালু'],
                            ['text' => 'বিকাশ — পেমেন্ট গেটওয়ে যুক্ত হলে চালু হবে', 'status' => 'শীঘ্রই'],
                            ['text' => 'নগদ — পেমেন্ট গেটওয়ে যুক্ত হলে চালু হবে', 'status' => 'শীঘ্রই'],
                        ]),
                        'item' => $item(6, [
                            'title' => ['type' => 'text', 'max' => 120],
                            'text' => ['type' => 'rich', 'max' => 800],
                        ], [
                            ['title' => 'ডেলিভারি', 'text' => '<ul><li>সারা বাংলাদেশে কুরিয়ারের মাধ্যমে হোম ডেলিভারি।</li><li>অর্ডার নিশ্চিত করতে আমাদের প্রতিনিধি ফোন করবেন।</li><li>শুক্রবার ও সরকারি ছুটির দিনে ডেলিভারি সময় বাড়তে পারে।</li></ul>'],
                            ['title' => 'প্যাকেজিং', 'text' => '<ul><li>বোতল বাবল-র‍্যাপ ও শক্ত কার্টনে মুড়িয়ে পাঠানো হয়।</li><li>ক্যাপে সিল থাকে — সিল ভাঙা থাকলে পণ্য গ্রহণ করবেন না।</li></ul>'],
                            ['title' => 'রিটার্ন ও রিপ্লেসমেন্ট', 'text' => '<ul><li>ডেলিভারিম্যানের সামনে পণ্য দেখে নিন; সমস্যা থাকলে তখনই ফেরত দিন।</li><li>ভুল পণ্য বা সাইজ পেলে ৪৮ ঘণ্টার মধ্যে জানালে বদলে দেওয়া হবে।</li></ul>'],
                            ['title' => 'ক্ষতিগ্রস্ত পণ্য', 'text' => '<ul><li>বোতল ভাঙা বা লিক হলে আনবক্সিংয়ের ছবি/ভিডিওসহ ৪৮ ঘণ্টার মধ্যে জানান।</li><li>যাচাইয়ের পর বিনামূল্যে রিপ্লেসমেন্ট বা রিফান্ড দেওয়া হবে।</li></ul>'],
                            ['title' => 'খাদ্যপণ্যের শর্ত', 'text' => '<ul><li>স্বাস্থ্যগত কারণে খোলা বা ব্যবহৃত খাদ্যপণ্য ফেরত নেওয়া হয় না।</li><li>স্বাদ বা ঝাঁজ ব্যক্তিভেদে ভিন্ন লাগতে পারে — এটি ফেরতের কারণ হিসেবে গণ্য নয়।</li></ul>'],
                            ['title' => 'স্বচ্ছতা', 'text' => '<ul><li>এই পেজের সব তথ্য পণ্যের লেবেলের সাথে মিলিয়ে দেখুন।</li><li>কোনো তথ্য অস্পষ্ট লাগলে অর্ডার নিশ্চিতকরণের কলে জিজ্ঞেস করুন।</li></ul>'],
                        ]),
                    ],
                ],
                'faq' => [
                    'elements' => [
                        'faq.eyebrow' => $t('সাধারণ প্রশ্ন', 80),
                        'faq.heading' => $t('প্রায়ই যা জানতে চান', 200),
                        'faq.subtitle' => $t('উপাদান, সংরক্ষণ, ডেলিভারি ও রিটার্ন নিয়ে উত্তর।', 200),
                    ],
                    'lists' => ['item' => $item(10, [
                        'q' => ['type' => 'text', 'max' => 250],
                        'a' => ['type' => 'rich', 'max' => 1000],
                    ], [
                        ['q' => 'এই তেলে কী কী উপাদান আছে?', 'a' => 'একক উপাদান: শুধু সরিষা বীজ। কোনো রং, সুগন্ধি বা অন্য তেল মেশানো হয় না।'],
                        ['q' => 'সরিষা কোথা থেকে সংগ্রহ করা হয়?', 'a' => 'বীজ সংগ্রহের অঞ্চল প্রতিটি বোতলের লেবেলে লেখা থাকে।'],
                        ['q' => 'কী ধরনের বোতলে আসে?', 'a' => 'অ্যাম্বার কাচের বোতল ও সিল করা ক্যাপ — আলো থেকে তেল রক্ষা করে।'],
                        ['q' => 'কীভাবে সংরক্ষণ করব?', 'a' => 'ঠাণ্ডা ও শুকনো জায়গায়, সরাসরি রোদ থেকে দূরে রাখুন। ব্যবহারের পর ঢাকনা শক্ত করে বন্ধ করুন।'],
                        ['q' => 'কতদিন ভালো থাকে?', 'a' => 'উৎপাদনের তারিখ থেকে ১২ মাস। সঠিক মেয়াদ লেবেলে দেখুন।'],
                        ['q' => 'কোন কোন সাইজ পাওয়া যায়?', 'a' => '৫০০ মিলি, ১ লিটার ও ২ লিটার। ২+ বোতল নিলে বান্ডেল ছাড় প্রযোজ্য।'],
                        ['q' => 'ডেলিভারি পেতে কতদিন লাগে?', 'a' => 'ঢাকার ভেতরে ১–২ কর্মদিবস, ঢাকার আশেপাশে ২–৩ এবং ঢাকার বাইরে ৩–৫ কর্মদিবস।'],
                        ['q' => 'পণ্য ভাঙা বা লিক অবস্থায় পেলে কী করব?', 'a' => 'ডেলিভারির ৪৮ ঘণ্টার মধ্যে ছবি বা ভিডিওসহ জানান। যাচাইয়ের পর বিনামূল্যে রিপ্লেসমেন্ট বা রিফান্ড দেওয়া হবে।'],
                    ])],
                ],
                'final' => [
                    'elements' => [
                        'final.eyebrow' => $t('WOOD-PRESSED MUSTARD OIL', 80),
                        'final.heading' => $t('আজই রান্নাঘরে আনুন চেনা ঝাঁজ', 200),
                        'final.cta_label' => $t('এখনই অর্ডার করুন', 60),
                        'final.cta_note' => $t('ক্যাশ অন ডেলিভারি · সারা বাংলাদেশে', 120),
                    ],
                    'lists' => ['point' => $item(4, ['text' => ['type' => 'text', 'max' => 120]], [
                        ['text' => 'একক উপাদান: সরিষা বীজ'],
                        ['text' => 'লেবেলে ব্যাচ ও মেয়াদের তারিখ'],
                        ['text' => 'ক্যাশ অন ডেলিভারি'],
                    ])],
                ],
                'checkout' => self::groceryCheckout(),
            ],
        ];
    }

    /** The Grocery order form: the shared form with the design's Bangla heading, labels and button text. */
    private static function groceryCheckout(): array
    {
        $checkout = self::checkout();
        $checkout['elements']['checkout.heading']['default'] = 'অর্ডার করুন — পণ্য হাতে পেয়ে মূল্য দিন';
        $checkout['elements']['checkout.submit']['default'] = 'অর্ডার নিশ্চিত করুন';
        $checkout['elements']['checkout.note']['default'] = 'তথ্য দিয়ে ফর্মটি পূরণ করুন। অর্ডার নিশ্চিত করতে আমরা আপনাকে ফোন করব।';

        return $checkout;
    }

    private static function gadgetSpec(): array
    {
        $hero = self::hero('Smartwatch, AMOLED display', 'Your health and fitness, one tap away', 'Track workouts, heart rate and sleep on a bright AMOLED display.');

        return [
            'theme' => ['primary' => '#2f7cff', 'accent' => '#0b1220', 'background' => '#070b14', 'text' => '#e6edf7'],
            'sections' => [
                'hero' => $hero,
                'benefits' => [
                    'elements' => ['benefits.heading' => self::text('Why people choose it', 200)],
                    'lists' => ['item' => self::item(6, [
                        'title' => ['type' => 'text', 'max' => 150],
                        'text' => ['type' => 'text', 'max' => 500],
                    ], [
                        ['title' => 'Health tracking', 'text' => 'Heart rate, SpO2 and sleep, all day.'],
                        ['title' => 'Long battery', 'text' => 'Up to seven days on one charge.'],
                        ['title' => 'Water resistant', 'text' => 'Swim-friendly 5 ATM rating.'],
                        ['title' => 'Smart alerts', 'text' => 'Calls and messages on your wrist.'],
                    ])],
                ],
                'specs' => [
                    'elements' => ['specs.heading' => self::text('Specifications', 200)],
                    'lists' => ['item' => self::item(10, [
                        'label' => ['type' => 'text', 'max' => 80],
                        'value' => ['type' => 'text', 'max' => 200],
                    ], [
                        ['label' => 'Display', 'value' => '1.43" AMOLED'],
                        ['label' => 'Battery', 'value' => '400 mAh, up to 7 days'],
                        ['label' => 'Bluetooth', 'value' => '5.3'],
                        ['label' => 'Sensors', 'value' => 'Heart rate, SpO2, sleep'],
                        ['label' => 'Water resistance', 'value' => '5 ATM'],
                        ['label' => 'Compatibility', 'value' => 'Android 8+ / iOS 14+'],
                        ['label' => 'Weight', 'value' => '42 g'],
                        ['label' => 'Warranty', 'value' => '1 year'],
                    ])],
                ],
                'gallery' => [
                    'elements' => ['gallery.heading' => self::text('Every angle', 200)],
                    'lists' => ['item' => self::item(8, [
                        'image' => ['type' => 'image', 'max' => 255],
                        'title' => ['type' => 'text', 'max' => 120],
                    ], [
                        ['image' => '', 'title' => 'Front'],
                        ['image' => '', 'title' => 'Side'],
                        ['image' => '', 'title' => 'Box'],
                        ['image' => '', 'title' => 'Charger'],
                    ])],
                ],
                'compare' => [
                    'elements' => [
                        'compare.heading' => self::text('Why choose this product?', 200),
                        'compare.col_ours' => self::text('Our product', 60),
                        'compare.col_other' => self::text('Other smartwatches', 60),
                    ],
                    'lists' => ['item' => self::item(6, [
                        'feature' => ['type' => 'text', 'max' => 120],
                        'ours' => ['type' => 'text', 'max' => 60],
                        'other' => ['type' => 'text', 'max' => 60],
                    ], [
                        ['feature' => 'AMOLED display', 'ours' => 'Yes', 'other' => 'No'],
                        ['feature' => 'Heart rate tracking', 'ours' => 'Yes', 'other' => 'Yes'],
                        ['feature' => 'Seven-day battery', 'ours' => 'Yes', 'other' => 'No'],
                        ['feature' => 'Water resistant', 'ours' => 'Yes', 'other' => 'Limited'],
                    ])],
                ],
                'reviews' => [
                    'elements' => ['reviews.heading' => self::text('Customer reviews', 200)],
                    'lists' => ['item' => self::item(8, [
                        'name' => ['type' => 'text', 'max' => 100],
                        'rating' => ['type' => 'text', 'max' => 2],
                        'text' => ['type' => 'text', 'max' => 500],
                    ], [
                        ['name' => 'Customer', 'rating' => '5', 'text' => 'Battery lasts all week and the screen is very clear.'],
                    ])],
                ],
                'policy' => [
                    'elements' => ['policy.heading' => self::text('Delivery, payment and warranty', 200)],
                    'lists' => ['item' => self::item(6, [
                        'title' => ['type' => 'text', 'max' => 150],
                        'text' => ['type' => 'text', 'max' => 500],
                    ], [
                        ['title' => 'Delivery', 'text' => 'Inside Dhaka in one to two days.'],
                        ['title' => 'Payment', 'text' => 'Cash on delivery, no advance needed.'],
                        ['title' => 'Warranty', 'text' => 'One-year warranty on every unit.'],
                        ['title' => 'Returns', 'text' => 'Seven-day return for a faulty product.'],
                    ])],
                ],
                'faq' => [
                    'elements' => ['faq.heading' => self::text('Frequently asked questions', 200)],
                    'lists' => ['item' => self::item(8, [
                        'q' => ['type' => 'text', 'max' => 250],
                        'a' => ['type' => 'rich', 'max' => 1000],
                    ], [
                        ['q' => 'Does it work with Android and iPhone?', 'a' => 'Yes, with Android 8+ and iOS 14+.'],
                        ['q' => 'How long does the battery last?', 'a' => 'Up to seven days with normal use.'],
                    ])],
                ],
                'final' => [
                    'elements' => [
                        'final.heading' => self::text('Start your fitness journey today', 200),
                        'final.text' => self::text('Get it today with cash on delivery.', 300),
                        'final.cta_label' => self::text('Order now', 60),
                    ],
                ],
                'checkout' => self::checkout(),
            ],
        ];
    }
}
