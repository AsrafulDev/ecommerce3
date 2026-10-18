<?php

namespace Tests\Feature\Vat;

use App\Models\GeneralSetting;
use App\Models\User;
use App\Services\Vat\VatCalculator;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    public function test_page_opens(): void
    {
        $this->get(route('admin.vat.edit'))->assertOk()->assertSee('VAT Settings');
    }

    public function test_cannot_switch_vat_on_without_the_inclusive_choice(): void
    {
        $this->post(route('admin.vat.update'), [
            'vat_enabled' => 1, 'vat_bin' => '123456789012',
        ])->assertSessionHas('error');

        $this->assertFalse(app(VatCalculator::class)->enabled());
    }

    public function test_cannot_switch_vat_on_without_a_bin(): void
    {
        $this->post(route('admin.vat.update'), [
            'vat_enabled' => 1, 'vat_prices_include_vat' => 0,
        ])->assertSessionHas('error');

        $this->assertFalse(app(VatCalculator::class)->enabled());
    }

    public function test_switching_on_with_a_choice_and_bin_turns_calculation_on(): void
    {
        $this->post(route('admin.vat.update'), [
            'vat_enabled' => 1, 'vat_prices_include_vat' => 1, 'vat_bin' => '123456789012',
            'vat_business_name' => 'Test Shop', 'vat_business_address' => 'Dhaka',
        ])->assertRedirect(route('admin.vat.edit'));

        $settings = GeneralSetting::orderBy('id', 'desc')->first();
        $this->assertTrue((bool) $settings->vat_enabled);
        $this->assertTrue((bool) $settings->vat_prices_include_vat);
        $this->assertSame('123456789012', $settings->vat_bin);
        $this->assertTrue(app(VatCalculator::class)->enabled());
    }

    public function test_a_bin_must_be_twelve_digits(): void
    {
        $this->post(route('admin.vat.update'), ['vat_bin' => '12345'])
            ->assertSessionHasErrors('vat_bin');
    }

    public function test_switching_off_keeps_the_identity_and_other_settings(): void
    {
        $before = GeneralSetting::orderBy('id', 'desc')->first();

        $this->post(route('admin.vat.update'), [
            'vat_enabled' => 1, 'vat_prices_include_vat' => 0, 'vat_bin' => '123456789012',
        ]);
        $this->post(route('admin.vat.update'), ['vat_bin' => '123456789012']);

        $after = GeneralSetting::orderBy('id', 'desc')->first();
        $this->assertFalse((bool) $after->vat_enabled);
        $this->assertSame($before->name, $after->name, 'the general settings are not touched');
        $this->assertFalse(app(VatCalculator::class)->enabled());
    }

    private function productPayload(array $extra = []): array
    {
        $category = \App\Models\Category::firstOrCreate(
            ['slug' => 'vat-cat'],
            ['name' => 'VAT Category', 'status' => 1, 'parent_id' => 0]
        );

        return array_merge([
            'name' => 'VAT Form Product', 'category_id' => $category->id, 'product_type' => 'simple',
            'description' => 'Product saved from the form', 'new_price' => 200, 'purchase_price' => 100,
            'stock' => 0, 'status' => 1, 'warranty_method' => 'active',
        ], $extra);
    }

    public function test_product_form_saves_vat_rate_category_and_hs_code(): void
    {
        $this->post(route('products.store'), $this->productPayload([
            'vat_rate' => 15, 'vat_category' => 'standard', 'hs_code' => '8517.13',
        ]))->assertSessionHasNoErrors();

        $product = \App\Models\Product::where('name', 'VAT Form Product')->firstOrFail();
        $this->assertEqualsWithDelta(15.0, (float) $product->vat_rate, 0.001);
        $this->assertSame('standard', $product->vat_category);
        $this->assertSame('8517.13', $product->hs_code);
    }

    public function test_a_blank_vat_rate_means_no_vat(): void
    {
        $this->post(route('products.store'), $this->productPayload(['vat_rate' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull(\App\Models\Product::where('name', 'VAT Form Product')->value('vat_rate'));
    }

    public function test_vat_rate_above_one_hundred_is_refused(): void
    {
        $this->post(route('products.store'), $this->productPayload(['vat_rate' => 150]))
            ->assertSessionHasErrors('vat_rate');
    }

    public function test_editing_a_product_changes_its_vat_rate(): void
    {
        $this->post(route('products.store'), $this->productPayload(['vat_rate' => 15]))->assertSessionHasNoErrors();
        $product = \App\Models\Product::where('name', 'VAT Form Product')->firstOrFail();

        $this->post(route('products.update'), $this->productPayload([
            'id' => $product->id, 'name' => 'VAT Form Product', 'vat_rate' => 5, 'vat_category' => 'reduced',
        ]))->assertSessionHasNoErrors();

        $fresh = $product->fresh();
        $this->assertEqualsWithDelta(5.0, (float) $fresh->vat_rate, 0.001);
        $this->assertSame('reduced', $fresh->vat_category);
    }
}
