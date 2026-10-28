<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use Tests\TestCase;

/**
 * The footer copyright line comes from one place: GeneralSetting::copyright_text.
 * {year} and {name} are filled in, and a blank field falls back to the default line.
 */
class CopyrightTextTest extends TestCase
{
    public function test_blank_copyright_uses_the_default_line_with_the_current_year(): void
    {
        $setting = new GeneralSetting(['name' => 'Giftshop', 'copyright' => '  ']);

        $this->assertSame('© ' . date('Y') . ' Giftshop. All rights reserved.', $setting->copyright_text);
    }

    public function test_year_and_name_tokens_are_filled_in(): void
    {
        $setting = new GeneralSetting(['name' => 'Giftshop', 'copyright' => 'Rights reserved {year} – {name}']);

        $this->assertSame('Rights reserved ' . date('Y') . ' – Giftshop', $setting->copyright_text);
    }

    public function test_every_footer_shows_the_saved_copyright_text(): void
    {
        $setting = new GeneralSetting(['name' => 'Giftshop', 'copyright' => 'Custom line {year}']);
        $expected = 'Custom line ' . date('Y');

        foreach (['classic', 'columns', 'modern', 'dark', 'minimal', 'default'] as $style) {
            $html = view('frontEnd.layouts.footers.' . $style, [
                'generalsetting' => $setting,
                'contact' => null,
                'categories' => collect(),
                'pages' => collect(),
                'footerComponents' => [],
            ])->render();

            $this->assertStringContainsString($expected, $html, "footer '{$style}' must show the copyright text");
        }
    }
}
