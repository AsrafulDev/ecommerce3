<?php

namespace Tests\Feature\Vat;

use App\Models\User;
use App\Services\Vat\HsCodeLookup;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HsCodeSearchTest extends TestCase
{
    use RefreshDatabase;

    private string $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');

        $this->fixture = sys_get_temp_dir().'/hs-tariff-'.uniqid().'.json';
        file_put_contents($this->fixture, json_encode([
            ['code' => '01012100', 'description' => 'Pure-bred breeding animals of horses', 'cd' => 5, 'sd' => 0, 'vat' => 0, 'ait' => 0, 'rd' => 0, 'at' => 0],
            ['code' => '02011010', 'description' => 'Carcasses and half-carcasses of bovine animals, fresh or chilled', 'cd' => 25, 'sd' => 0, 'vat' => 15, 'ait' => 5, 'rd' => 0, 'at' => 0],
            ['code' => '85171300', 'description' => 'Smartphones', 'cd' => 0, 'sd' => 0, 'vat' => 15, 'ait' => 0, 'rd' => 0, 'at' => 0],
        ]));
        config(['vat.hs_tariff_path' => $this->fixture]);
    }

    protected function tearDown(): void
    {
        @unlink($this->fixture);
        parent::tearDown();
    }

    public function test_searches_by_code_prefix_and_by_description(): void
    {
        $lookup = new HsCodeLookup($this->fixture);

        $this->assertSame(['85171300'], array_column($lookup->search('8517'), 'code'));
        $this->assertSame(['85171300'], array_column($lookup->search('smartphone'), 'code'), 'case-insensitive text match');
        $this->assertSame(['85171300'], array_column($lookup->search('8517 1300'), 'code'), 'spaces in codes are ignored');
    }

    public function test_an_empty_query_returns_nothing_and_a_missing_file_is_not_guessed(): void
    {
        $this->assertSame([], (new HsCodeLookup($this->fixture))->search(''));
        $this->assertSame([], (new HsCodeLookup('/no/such/file.json'))->search('8517'));
        $this->assertFalse((new HsCodeLookup('/no/such/file.json'))->loaded());
    }

    public function test_the_endpoint_returns_matches_with_the_tariff_vat_figure(): void
    {
        $response = $this->get(route('admin.vat.hs_codes', ['q' => 'bovine']))->assertOk();

        $response->assertJson(['loaded' => true]);
        $this->assertSame('02011010', $response->json('results.0.code'));
        $this->assertSame(15, (int) $response->json('results.0.vat'));
    }

    public function test_a_fixed_taka_duty_is_not_offered_as_a_percentage(): void
    {
        file_put_contents($this->fixture, json_encode([
            ['code' => '89080000', 'description' => 'Vessels and other floating structures for breaking up.', 'cd' => 0, 'sd' => 0, 'vat' => 1800, 'ait' => 600, 'at' => 0, 'rd' => 0, 'tti' => 2400, 'specific_duty' => true],
        ]));

        $response = $this->get(route('admin.vat.hs_codes', ['q' => '89080000']))->assertOk();

        $this->assertSame('89080000', $response->json('results.0.code'));
        $this->assertNull($response->json('results.0.vat'), 'a Taka amount must not be shown as a percentage');
        $this->assertTrue($response->json('results.0.specific_duty'));
    }

    public function test_the_endpoint_says_when_the_tariff_is_not_loaded(): void
    {
        config(['vat.hs_tariff_path' => '/no/such/file.json']);

        $this->get(route('admin.vat.hs_codes', ['q' => '8517']))
            ->assertOk()
            ->assertJson(['loaded' => false, 'results' => []]);
    }

    public function test_a_product_form_carries_the_search_field(): void
    {
        $this->get(route('products.create'))
            ->assertOk()
            ->assertSee('hs-code-search', false)
            ->assertSee(route('admin.vat.hs_codes'), false);
    }
}
