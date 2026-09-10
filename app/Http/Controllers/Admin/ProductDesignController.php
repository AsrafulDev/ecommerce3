<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Toastr;

class ProductDesignController extends Controller
{
    public const DESIGNS = [
        'default' => 'Premium (Default)',
        'minimal' => 'Minimal',
        'classic' => 'Classic',
        'dark' => 'Dark',
        'rounded' => 'Rounded',
        'gradient' => 'Gradient',
    ];

    public const ROW_LIMIT_FIELDS_HOME = [
        'desktop' => 5,
        'laptop' => 4,
        'tablet' => 3,
        'phone' => 2,
    ];

    public const ROW_LIMIT_FIELDS_OTHER = [
        'desktop' => 4,
        'laptop' => 3,
        'tablet' => 3,
        'phone' => 2,
    ];

    public function __construct()
    {
        $this->middleware('permission:theme-list|theme-create|theme-edit|theme-delete');
    }

    public function index()
    {
        $setting = GeneralSetting::first();
        $designs = self::DESIGNS;
        $activeDesign = $setting->product_card_style ?? 'default';

        return view('backEnd.productdesign.index', compact('setting', 'designs', 'activeDesign'));
    }

    public function store(Request $request)
    {
        $fields = [];
        foreach (['home', 'other'] as $group) {
            foreach (['desktop', 'laptop', 'tablet', 'phone'] as $device) {
                $fields["pc_{$group}_{$device}"] = 'required|integer|min:1|max:8';
            }
        }

        $validated = $request->validate(array_merge([
            'style' => 'required|in:' . implode(',', array_keys(self::DESIGNS)),
            'pc_title_lines' => 'required|integer|min:1|max:5',
            'pc_image_height' => 'required|integer|min:80|max:500',
        ], $fields));

        $setting = GeneralSetting::first();
        if (!$setting) {
            Toastr::error('General settings not found!', 'Error');
            return back();
        }

        $setting->product_card_style = $validated['style'];
        $setting->pc_title_lines = (int) $validated['pc_title_lines'];
        $setting->pc_image_height = (int) $validated['pc_image_height'];
        foreach ($fields as $field => $rule) {
            $setting->{$field} = (int) $validated[$field];
        }
        $setting->save();
        Cache::forget('general_setting');

        Toastr::success('Product card design updated successfully!', 'Success');
        return redirect()->route('product.design');
    }
}