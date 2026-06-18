<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Brian2694\Toastr\Facades\Toastr;

class FraudSettingController extends Controller
{
    public function index()
    {
        $data = GeneralSetting::first();
        return view('backEnd.fraud_setting.index', compact('data'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'duplicate_order_api_key' => 'nullable',
            'duplicate_order_api_url' => 'nullable',
            'duplicate_order_method' => 'nullable|string|max:10',
            'duplicate_order_phone_key' => 'nullable|string|max:50',
        ]);

        $setting = GeneralSetting::first();
        $setting->fraud_check_enabled = $request->has('fraud_check_enabled') ? 1 : 0;
        $setting->duplicate_order_api_key = $request->duplicate_order_api_key ?? null;
        $setting->duplicate_order_api_url = $request->duplicate_order_api_url ?? null;
        $setting->duplicate_order_method = $request->duplicate_order_method ?? 'POST';
        $setting->duplicate_order_phone_key = $request->duplicate_order_phone_key ?? 'phone';
        $setting->save();

        Toastr::success('API settings updated successfully', 'Success!');
        return redirect()->back();
    }
}
