<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Toastr;

/**
 * VAT (Mushak) switches and business identity. Kept on its own page so the general
 * settings form is untouched. VAT cannot be turned on until the owner has chosen
 * whether prices include VAT, because the calculation depends on it.
 */
class VatSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:setting-edit', ['only' => ['edit', 'update']]);
    }

    public function edit()
    {
        $settings = GeneralSetting::orderBy('id', 'desc')->first();

        if (!$settings) {
            return redirect()->route('settings.create');
        }

        return view('backEnd.settings.vat', [
            'settings'             => $settings,
            'productsWithoutRate'  => Product::whereNull('vat_rate')->count(),
            'productsWithRate'     => Product::whereNotNull('vat_rate')->count(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'vat_enabled'            => 'nullable|boolean',
            'vat_prices_include_vat' => 'nullable|in:0,1',
            'vat_bin'                => 'nullable|digits:12',
            'vat_business_name'      => 'nullable|string|max:191',
            'vat_business_address'   => 'nullable|string|max:500',
        ]);

        $settings = GeneralSetting::orderBy('id', 'desc')->first();
        if (!$settings) {
            Toastr::error('No settings record found. Create one first.', 'Error');
            return redirect()->route('settings.create');
        }

        $enabled = $request->boolean('vat_enabled');
        $inclusive = $request->input('vat_prices_include_vat');

        if ($enabled && ($inclusive === null || $inclusive === '')) {
            return back()->withInput()->with('error', 'Choose whether prices include VAT before turning VAT on.');
        }

        if ($enabled && blank($request->input('vat_bin'))) {
            return back()->withInput()->with('error', 'Enter the 12-digit VAT registration number (BIN) before turning VAT on.');
        }

        $settings->update([
            'vat_enabled'            => $enabled,
            'vat_prices_include_vat' => $inclusive === null || $inclusive === '' ? null : (bool) (int) $inclusive,
            'vat_bin'                => $request->input('vat_bin') ?: null,
            'vat_business_name'      => $request->input('vat_business_name') ?: null,
            'vat_business_address'   => $request->input('vat_business_address') ?: null,
        ]);

        Cache::forget('general_setting');

        log_activity('vat', 'update', $enabled ? 'VAT switched on' : 'VAT switched off', $settings);
        Toastr::success('VAT settings saved.', 'Success');

        return redirect()->route('admin.vat.edit');
    }
}
