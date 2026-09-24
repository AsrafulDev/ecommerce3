<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\User;
use App\Support\HeaderFooterComponents;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Header/Footer builder — 3 fixed row zones, up to 6 columns per row, and
 * per-column widths on 4 responsive tiers (desktop / laptop / tablet / phone).
 */
class HeaderFooterBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    /** Replace the stored layout and make it visible to the view layer. */
    private function storeComponents(string $type, array $payload): void
    {
        $setting = GeneralSetting::orderBy('id', 'desc')->first();
        $setting->update([
            $type . '_components' => $payload,
            $type . '_style'      => 'custom',
        ]);

        Cache::forget('general_setting');
        view()->share('generalsetting', $setting->fresh());
    }

    private function renderBuilder(string $type, array $rows): string
    {
        return view('frontEnd.layouts.partials.hf-builder', [
            'type' => $type,
            'rows' => $rows,
        ])->render();
    }

    // ───────────────────────── model ─────────────────────────

    public function test_legacy_flat_list_is_upgraded_to_full_width_columns_in_main(): void
    {
        $legacy = [
            ['id' => 'topbar', 'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => false]],
            ['id' => 'logo'],
            'search',
        ];

        $rows = HeaderFooterComponents::normalize($legacy, 'header')['rows'];

        // Always exactly 3 zones, in order.
        $this->assertSame(['top', 'main', 'bottom'], array_column($rows, 'id'));

        $main = $rows[1];
        $this->assertCount(3, $main['columns'], 'each legacy widget becomes its own column');

        foreach ($main['columns'] as $col) {
            $this->assertSame(
                ['desktop' => 12, 'laptop' => 12, 'tablet' => 12, 'phone' => 12],
                $col['widths']
            );
            $this->assertCount(1, $col['widgets']);
        }

        $this->assertSame(['topbar', 'logo', 'search'], HeaderFooterComponents::ids(['rows' => $rows]));

        // Legacy 3-tier visibility upgrades: mobile → phone, laptop ← desktop.
        $topbar = $main['columns'][0]['widgets'][0];
        $this->assertTrue($topbar['visibility']['desktop']);
        $this->assertTrue($topbar['visibility']['laptop'], 'laptop inherits the old desktop tier');
        $this->assertTrue($topbar['visibility']['tablet']);
        $this->assertFalse($topbar['visibility']['phone'], 'mobile maps to phone');
    }

    public function test_new_shape_round_trips(): void
    {
        $payload = [
            'rows' => [
                ['id' => 'top', 'columns' => []],
                ['id' => 'main', 'columns' => [
                    ['widths' => ['desktop' => 3, 'laptop' => 4, 'tablet' => 6, 'phone' => 12],
                     'widgets' => [['id' => 'logo']]],
                    ['widths' => ['desktop' => 9, 'laptop' => 8, 'tablet' => 6, 'phone' => 12],
                     'widgets' => [['id' => 'search'], ['id' => 'cart']]],
                ]],
                ['id' => 'bottom', 'columns' => []],
            ],
        ];

        $rows = HeaderFooterComponents::normalize($payload, 'header')['rows'];

        $this->assertCount(2, $rows[1]['columns']);
        $this->assertSame(['desktop' => 3, 'laptop' => 4, 'tablet' => 6, 'phone' => 12], $rows[1]['columns'][0]['widths']);
        $this->assertCount(2, $rows[1]['columns'][1]['widgets'], 'multiple widgets in one column');
        $this->assertEmpty($rows[0]['columns']);
    }

    public function test_unknown_rows_widgets_and_duplicates_are_dropped(): void
    {
        $payload = [
            'rows' => [
                ['id' => 'nonsense', 'columns' => [['widgets' => [['id' => 'logo']]]]],
                ['id' => 'main', 'columns' => [
                    ['widgets' => [['id' => 'logo'], ['id' => 'nope'], ['id' => 'cart']]],
                ]],
            ],
        ];

        $rows = HeaderFooterComponents::normalize($payload, 'header')['rows'];

        // Unknown row id → its widgets are parked in main, not lost.
        $ids = HeaderFooterComponents::ids(['rows' => $rows]);
        $this->assertSame(['logo', 'cart'], $ids, 'unknown widget dropped, duplicate logo removed');
        $this->assertEmpty($rows[0]['columns'], 'no row invented for the bogus id');
    }

    public function test_columns_are_capped_and_widths_clamped(): void
    {
        // Two out-of-range spans + missing tiers on the first column.
        $columns = [];
        foreach (['topbar', 'logo', 'search', 'nav', 'cart', 'all_categories'] as $i => $id) {
            $columns[] = $i === 0
                ? ['widths' => ['desktop' => 99, 'tablet' => 0], 'widgets' => [['id' => $id]]]
                : ['widgets' => [['id' => $id]]];
        }
        // A 7th column — over MAX_COLUMNS, and its widget is already used.
        $columns[] = ['widgets' => [['id' => 'logo']]];

        $rows = HeaderFooterComponents::normalize(['rows' => [['id' => 'main', 'columns' => $columns]]], 'header')['rows'];
        $main = $rows[1];

        $this->assertCount(
            HeaderFooterComponents::MAX_COLUMNS,
            $main['columns'],
            'a row may hold at most ' . HeaderFooterComponents::MAX_COLUMNS . ' columns'
        );

        $this->assertSame(12, $main['columns'][0]['widths']['desktop'], 'over-large span clamped to 12');
        $this->assertSame(1, $main['columns'][0]['widths']['tablet'], 'zero span clamped to 1');
        $this->assertSame(12, $main['columns'][0]['widths']['phone'], 'missing span defaults to 12');
    }

    public function test_empty_columns_are_dropped(): void
    {
        $rows = HeaderFooterComponents::normalize([
            'rows' => [['id' => 'main', 'columns' => [
                ['widgets' => []],
                ['widgets' => [['id' => 'logo']]],
            ]]],
        ], 'header')['rows'];

        $this->assertCount(1, $rows[1]['columns']);
    }

    public function test_defaults_are_full_width_in_main_and_exclude_all_categories(): void
    {
        $defaults = HeaderFooterComponents::defaults('header');
        $ids = HeaderFooterComponents::ids($defaults);

        $this->assertSame(['topbar', 'logo', 'search', 'nav', 'cart'], $ids);
        $this->assertNotContains('all_categories', $ids, 'promoted widgets must stay opt-in');
        $this->assertCount(5, $defaults['rows'][1]['columns']);
        $this->assertEmpty($defaults['rows'][0]['columns']);
    }

    // ───────────────────────── responsive output ─────────────────────────

    public function test_column_classes_map_each_tier_to_its_bootstrap_breakpoint(): void
    {
        $this->assertSame(
            'col-12 col-sm-6 col-lg-4 col-xl-3',
            HeaderFooterComponents::columnClasses(['desktop' => 3, 'laptop' => 4, 'tablet' => 6, 'phone' => 12])
        );
        $this->assertSame(
            'col-12 col-sm-12 col-lg-12 col-xl-12',
            HeaderFooterComponents::columnClasses([])
        );
    }

    public function test_visibility_classes_for_each_tier(): void
    {
        $cases = [
            'all'           => [true, true, true, true, ''],
            'none'          => [false, false, false, false, 'd-none'],
            'phone only'    => [false, false, false, true, 'd-sm-none'],
            'tablet only'   => [false, false, true, false, 'd-none d-sm-block d-lg-none'],
            'laptop only'   => [false, true, false, false, 'd-none d-sm-block d-xl-none'],
            'desktop only'  => [true, false, false, false, 'd-none d-sm-block'],
            'desktop+laptop' => [true, true, false, false, 'd-none d-sm-block'],
        ];

        foreach ($cases as $label => [$d, $l, $t, $p, $expected]) {
            $this->assertSame(
                $expected,
                HeaderFooterComponents::visibilityClasses(['desktop' => $d, 'laptop' => $l, 'tablet' => $t, 'phone' => $p]),
                "failed for: {$label}"
            );
        }
    }

    public function test_column_visibility_is_the_union_of_its_widgets(): void
    {
        $vis = HeaderFooterComponents::columnVisibility([
            ['visibility' => ['desktop' => true,  'laptop' => false, 'tablet' => false, 'phone' => false]],
            ['visibility' => ['desktop' => false, 'laptop' => false, 'tablet' => true,  'phone' => false]],
        ]);

        $this->assertTrue($vis['desktop']);
        $this->assertTrue($vis['tablet']);
        $this->assertFalse($vis['laptop']);
        $this->assertFalse($vis['phone']);
    }

    // ───────────────────────── storefront render ─────────────────────────

    public function test_storefront_renders_rows_columns_and_widget_classes(): void
    {
        $rows = HeaderFooterComponents::normalize([
            'rows' => [
                ['id' => 'main', 'columns' => [
                    ['widths' => ['desktop' => 3, 'laptop' => 4, 'tablet' => 6, 'phone' => 12],
                     'widgets' => [['id' => 'logo']]],
                    ['widths' => ['desktop' => 9, 'laptop' => 8, 'tablet' => 6, 'phone' => 12],
                     'widgets' => [
                         ['id' => 'search', 'visibility' => ['desktop' => true, 'laptop' => true, 'tablet' => true, 'phone' => false]],
                         ['id' => 'cart'],
                     ]],
                ]],
            ],
        ], 'header')['rows'];

        $html = $this->renderBuilder('header', $rows);

        $this->assertStringContainsString('hf-builder-header', $html);
        $this->assertStringContainsString('hf-row-main', $html);
        $this->assertStringContainsString('col-12 col-sm-6 col-lg-4 col-xl-3', $html, 'first column tier classes');
        $this->assertStringContainsString('col-12 col-sm-6 col-lg-8 col-xl-9', $html, 'second column tier classes');
        $this->assertStringContainsString('data-hf-widget="logo"', $html);
        $this->assertStringContainsString('data-hf-widget="cart"', $html);

        // The phone-hidden widget carries the sm-and-up display utility.
        $this->assertMatchesRegularExpression(
            '/hf-part d-none d-sm-block"[^>]*data-hf-widget="search"/',
            $html
        );
    }

    public function test_empty_rows_render_no_markup(): void
    {
        $rows = HeaderFooterComponents::normalize([
            'rows' => [['id' => 'main', 'columns' => [['widgets' => [['id' => 'logo']]]]]],
        ], 'header')['rows'];

        $html = $this->renderBuilder('header', $rows);

        $this->assertStringContainsString('hf-row-main', $html);
        $this->assertStringNotContainsString('hf-row-top', $html, 'empty top zone must not render');
        $this->assertStringNotContainsString('hf-row-bottom', $html, 'empty bottom zone must not render');
    }

    public function test_legacy_stored_data_still_renders_one_full_width_column_per_widget(): void
    {
        // Exactly what an existing live site has in the DB today.
        $this->storeComponents('header', [
            ['id' => 'topbar', 'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true]],
            ['id' => 'logo',   'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true]],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('hf-row-main', $html);
        $this->assertStringContainsString('col-12 col-sm-12 col-lg-12 col-xl-12', $html);
    }

    // ───────────────────────── persistence + preview ─────────────────────────

    public function test_update_persists_the_row_shape(): void
    {
        $payload = json_encode([
            'rows' => [
                ['id' => 'main', 'columns' => [
                    ['widths' => ['desktop' => 4, 'laptop' => 4, 'tablet' => 6, 'phone' => 12],
                     'widgets' => [['id' => 'logo']]],
                    ['widths' => ['desktop' => 8, 'laptop' => 8, 'tablet' => 6, 'phone' => 12],
                     'widgets' => [['id' => 'cart']]],
                ]],
            ],
        ]);

        $this->post(route('headerfooter.update'), [
            'header_style'      => 'custom',
            'header_components' => $payload,
        ])->assertRedirect();

        $stored = GeneralSetting::orderBy('id', 'desc')->first()->header_components;
        $rows = $stored['rows'];

        $this->assertSame(['top', 'main', 'bottom'], array_column($rows, 'id'));
        $this->assertSame(4, $rows[1]['columns'][0]['widths']['desktop']);
        $this->assertSame(['logo', 'cart'], HeaderFooterComponents::ids($stored));
    }

    public function test_update_still_accepts_a_legacy_flat_payload(): void
    {
        $this->post(route('headerfooter.update'), [
            'header_style'      => 'custom',
            'header_components' => json_encode(['logo', 'cart']),
        ])->assertRedirect();

        $stored = GeneralSetting::orderBy('id', 'desc')->first()->header_components;

        $this->assertSame(['logo', 'cart'], HeaderFooterComponents::ids($stored));
        $this->assertSame(12, $stored['rows'][1]['columns'][0]['widths']['desktop'], 'upgraded as full-width');
    }

    public function test_an_explicitly_empty_canvas_stays_empty(): void
    {
        $this->post(route('headerfooter.update'), [
            'header_style'      => 'custom',
            'header_components' => json_encode(['rows' => []]),
        ])->assertRedirect();

        $stored = GeneralSetting::orderBy('id', 'desc')->first()->header_components;

        $this->assertFalse(
            HeaderFooterComponents::hasWidgets($stored),
            'defaults must not be re-seeded over an intentionally empty canvas'
        );
    }

    public function test_builder_page_renders_three_row_zones_for_both_types(): void
    {
        $response = $this->get(route('headerfooter.index'));
        $response->assertStatus(200);

        $html = $response->getContent();

        foreach (['header', 'footer'] as $type) {
            foreach (HeaderFooterComponents::ROWS as $zone) {
                $this->assertStringContainsString(
                    'data-list="'.$type.'" data-zone="'.$zone.'"',
                    $html,
                    "missing drop zone {$type}/{$zone}"
                );
            }
        }

        $this->assertStringContainsString('hf-add-col', $html);
        $this->assertStringContainsString('data-w-type="header"', $html);
        $this->assertStringContainsString('data-w-type="footer"', $html);
        // All four device buttons, incl. the new laptop tier.
        foreach (HeaderFooterComponents::DEVICES as $device) {
            $this->assertStringContainsString('data-device="'.$device.'"', $html);
        }
    }

    public function test_preview_endpoint_returns_row_and_column_markup(): void
    {
        $response = $this->postJson(route('headerfooter.preview'), [
            'type'       => 'header',
            'style'      => 'custom',
            'components' => [
                'rows' => [
                    ['id' => 'main', 'columns' => [
                        ['widths' => ['desktop' => 6, 'laptop' => 6, 'tablet' => 6, 'phone' => 12],
                         'widgets' => [['id' => 'logo']]],
                    ]],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $html = $response->json('html');

        $this->assertStringContainsString('hf-row-main', $html);
        $this->assertStringContainsString('col-12 col-sm-6 col-lg-6 col-xl-6', $html);
        $this->assertStringContainsString('data-hf-widget="logo"', $html);
    }
}
