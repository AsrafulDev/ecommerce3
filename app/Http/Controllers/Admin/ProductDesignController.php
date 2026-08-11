<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Toastr;

class ProductDesignController extends Controller
{
    /**
     * Available product-card designs (key => human label).
     * The key is used as body class: body.product-card-<key>.
     *
     * "default" is the NEW premium layout (unique markup format).
     * "legacy" preserves the ORIGINAL card (renamed from the old "default").
     * overlay / ribbon / glass are additional unique structural formats.
     * minimal / classic / dark / rounded / gradient are CSS-only restyles
     * of the classic structure.
     */
    const DESIGNS = [
        'default'  => 'Premium (Default)',
        'overlay'  => 'Overlay',
        'ribbon'   => 'Ribbon',
        'glass'    => 'Glassmorphism',
        'legacy'   => 'Legacy (Original)',
        'minimal'  => 'Minimal',
        'classic'  => 'Classic',
        'dark'     => 'Dark',
        'rounded'  => 'Rounded',
        'gradient' => 'Gradient',
    ];

    function __construct()
    {
        $this->middleware('permission:theme-list|theme-create|theme-edit|theme-delete', ['only' => ['index', 'store']]);
    }

    /**
     * Per-device row limits (cards per row) for the FRONT page (no sidebar).
     */
    const ROW_LIMIT_FIELDS_HOME = [
        'pc_home_desktop' => 5,
        'pc_home_laptop'  => 4,
        'pc_home_tablet'  => 3,
        'pc_home_phone'   => 2,
    ];

    /**
     * Per-device row limits (cards per row) for OTHER pages (shop/category/
     * brand/search/related/account) which usually have a sidebar.
     */
    const ROW_LIMIT_FIELDS_OTHER = [
        'pc_other_desktop' => 4,
        'pc_other_laptop'  => 3,
        'pc_other_tablet'  => 3,
        'pc_other_phone'   => 2,
    ];

    public function index()
    {
        $setting = GeneralSetting::first();
        $activeDesign = $setting->product_card_style ?? 'default';
        $designs = self::DESIGNS;
        return view('backEnd.productdesign.index', compact('designs', 'activeDesign', 'setting'));
    }

    public function store(Request $request)
    {
        $rowFields = array_merge(
            array_keys(self::ROW_LIMIT_FIELDS_HOME),
            array_keys(self::ROW_LIMIT_FIELDS_OTHER)
        );

        $this->validate($request, [
            'style'            => 'required|in:' . implode(',', array_keys(self::DESIGNS)),
            'pc_title_lines'   => 'required|integer|min:1|max:5',
            'pc_image_height'  => 'required|integer|min:80|max:500',
        ] + collect($rowFields)->mapWithKeys(fn ($f) => [$f => 'required|integer|min:1|max:8'])->all());

        $setting = GeneralSetting::first();
        if (!$setting) {
            Toastr::error('General settings not found!', 'Error');
            return redirect()->back();
        }

        $setting->product_card_style = $request->style;
        $setting->pc_title_lines     = (int) $request->pc_title_lines;
        $setting->pc_image_height    = (int) $request->pc_image_height;
        foreach ($rowFields as $field) {
            $setting->{$field} = (int) $request->{$field};
        }
        $setting->save();

        // general_setting + active_theme are cached; purge so the frontend picks it up
        Cache::forget('general_setting');

        Toastr::success('Product card design updated to "' . (self::DESIGNS[$request->style] ?? $request->style) . '"!', 'Success');
        return redirect()->route('product.design');
    }
}
