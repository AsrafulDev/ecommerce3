<?php

namespace Tests\Feature;

use App\Models\BannerCategory;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Auto Sync Missing" on the Banner & Sliders pages.
 *
 * The storefront resolves banner sections by FIXED category ids, so a deleted
 * row silently kills that section. Sync must put the missing ones back with the
 * right ids, and leave existing rows (including renamed ones) alone.
 */
class BannerCategorySyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    /** Canonical ids → names, with integer keys, for comparison. */
    private function storedCanonical(): array
    {
        return BannerCategory::whereIn('id', array_keys(BannerCategory::CANONICAL))
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn ($c) => [(int) $c->id => $c->name])
            ->all();
    }

    public function test_sync_creates_every_missing_category_with_its_fixed_id(): void
    {
        BannerCategory::query()->delete();

        $this->post(route('banner_category.sync'))->assertRedirect();

        $this->assertSame(
            BannerCategory::CANONICAL,
            $this->storedCanonical(),
            'every canonical category must exist under its fixed id'
        );
    }

    public function test_sync_only_fills_the_gaps(): void
    {
        // Start from a complete set, then remove two.
        BannerCategory::query()->delete();
        $this->post(route('banner_category.sync'));
        $this->assertSame(count(BannerCategory::CANONICAL), BannerCategory::count());

        BannerCategory::whereIn('id', [6, 8])->delete();
        $this->assertSame(count(BannerCategory::CANONICAL) - 2, BannerCategory::count());

        $this->post(route('banner_category.sync'))->assertRedirect();

        $this->assertTrue(BannerCategory::where('id', 6)->exists());
        $this->assertTrue(BannerCategory::where('id', 8)->exists());
        $this->assertSame(
            count(BannerCategory::CANONICAL),
            BannerCategory::count(),
            'only the two gaps may be filled — nothing duplicated'
        );
    }

    public function test_sync_is_idempotent(): void
    {
        BannerCategory::query()->delete();

        $this->post(route('banner_category.sync'));
        $afterFirst = BannerCategory::count();

        $this->post(route('banner_category.sync'));

        $this->assertSame($afterFirst, BannerCategory::count(), 'a second sync must not duplicate anything');
        $this->assertSame(count(BannerCategory::CANONICAL), $afterFirst);
    }

    public function test_sync_does_not_overwrite_a_renamed_existing_category(): void
    {
        BannerCategory::query()->delete();
        BannerCategory::create(['id' => 1, 'name' => 'My Custom Sliders', 'status' => 1]);

        $this->post(route('banner_category.sync'))->assertRedirect();

        $this->assertSame('My Custom Sliders', BannerCategory::find(1)->name, 'existing rows must be left untouched');
        $this->assertSame(count(BannerCategory::CANONICAL), BannerCategory::whereIn('id', array_keys(BannerCategory::CANONICAL))->count());
    }

    public function test_sync_leaves_an_existing_inactive_category_alone(): void
    {
        // A row that exists but is switched off still counts as present.
        BannerCategory::query()->delete();
        BannerCategory::create(['id' => 7, 'name' => 'Campaign Ads', 'status' => 0]);

        $this->post(route('banner_category.sync'))->assertRedirect();

        $this->assertSame(0, (int) BannerCategory::find(7)->status, 'sync must not flip status');
        $this->assertSame(count(BannerCategory::CANONICAL), BannerCategory::count());
    }

    public function test_both_pages_render_the_auto_sync_button(): void
    {
        $this->get(route('banner_category.index'))
            ->assertStatus(200)
            ->assertSee('Auto Sync Missing', false);

        $this->get(route('banners.index'))
            ->assertStatus(200)
            ->assertSee('Auto Sync Category', false);
    }
}
