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

    /** Template keys that can be used for a new landing page. */
    public static function keys(): array
    {
        return array_keys(self::specs());
    }

    /** The name shown to the user for a template key. */
    public static function label(string $key): string
    {
        return [self::LUXURY => 'Luxury', self::FASHION => 'Fashion', self::GADGET => 'Gadget'][$key] ?? $key;
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
            self::FASHION => self::fashionSpec(),
            self::GADGET => self::gadgetSpec(),
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

    /** The order form. Identical across templates, so the labels and placeholders stay editable everywhere. */
    private static function checkout(): array
    {
        return [
            'locked' => true,
            'elements' => [
                'checkout.heading' => self::text('Order now', 200),
                'checkout.submit' => self::text('Confirm order', 60),
                'checkout.note' => self::text('Cash on delivery.', 300),
                'checkout.variants_label' => self::text('Choose an option', 120),
                'checkout.label_name' => self::text('Name', 60),
                'checkout.label_phone' => self::text('Mobile number', 60),
                'checkout.label_address' => self::text('Address', 60),
                'checkout.label_area' => self::text('Delivery area', 60),
                'checkout.label_price' => self::text('Price', 60),
                'checkout.placeholder_name' => self::text('Your name', 120),
                'checkout.placeholder_phone' => self::text('01XXXXXXXXX', 120),
                'checkout.placeholder_address' => self::text('House, road, area', 120),
                'checkout.placeholder_area' => self::text('Select area', 120),
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
                        'title' => ['type' => 'text', 'max' => 150],
                        'text' => ['type' => 'text', 'max' => 300],
                    ], [
                        ['title' => 'হালকা ও আরামদায়ক', 'text' => 'লিনেন-কটন মিশ্রণের কাপড় শরীরে ভারী লাগে না, সারাদিন পরে থাকা সহজ।'],
                        ['title' => 'রিলাক্সড স্ট্রেইট কাট', 'text' => 'ঢিলেঢালা কিন্তু গোছানো ফিট — শালীন ও আধুনিক, দুটোই।'],
                        ['title' => 'সূক্ষ্ম টোনাল এমব্রয়ডারি', 'text' => 'কাপড়ের রঙেই সুতার কাজ, তাই জমকালো না হয়েও নজরকাড়া।'],
                        ['title' => '৪টি নরম রঙ', 'text' => 'আইভরি, ডাস্টি রোজ, সেজ ও চারকোল — প্রতিটি রঙ সহজে মেলানো যায়।'],
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
                        'colour' => $item(4, [
                            'name' => ['type' => 'text', 'max' => 40],
                            'hex' => ['type' => 'text', 'max' => 7],
                        ], [
                            ['name' => 'আইভরি', 'hex' => '#efe6d6'],
                            ['name' => 'ডাস্টি রোজ', 'hex' => '#b98a84'],
                            ['name' => 'সেজ', 'hex' => '#8fa58a'],
                            ['name' => 'চারকোল', 'hex' => '#2f3338'],
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
