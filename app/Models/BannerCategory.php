<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerCategory extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * Canonical banner & slider categories.
     *
     * The storefront resolves each banner section by these FIXED ids
     * (FrontendController: 1 = sliders, 5 = slider bottom, 6 = footer top,
     * 7 = campaign, 8 = reviews, 9 = hot-deal, 10/11 = homepage ads), so the ids
     * are part of the contract. Create the missing ones with syncCanonical();
     * never renumber them.
     */
    public const CANONICAL = [
        1  => 'Sliders',
        5  => 'Slider Bottom Ads',
        6  => 'Footer Top Ads',
        7  => 'Campaign Ads',
        8  => 'Customer Reviews',
        9  => 'Hot Deal Banners',
        10 => 'Homepage Ads',
        11 => 'Homepage Ads 2',
    ];

    /**
     * Create whichever canonical categories are missing.
     *
     * Safe to run repeatedly: rows that already exist are left untouched (so a
     * renamed category keeps its name). Returns the names that were created.
     */
    public static function syncCanonical(): array
    {
        $existingIds = static::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $created     = [];

        foreach (static::CANONICAL as $id => $name) {
            if (in_array($id, $existingIds, true)) {
                continue;
            }

            // Explicit id — the sections are wired to these ids, so AUTO_INCREMENT
            // must not pick one.
            static::create(['id' => $id, 'name' => $name, 'status' => 1]);

            $created[] = $name;
        }

        return $created;
    }
}
