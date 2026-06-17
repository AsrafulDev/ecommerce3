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
            'fraud_api_key' => 'required',
            'duplicate_order_api_key' => 'nullable',
        ]);

        $setting = GeneralSetting::first();
        $setting->fraud_api_key = $request->fraud_api_key;
        $setting->duplicate_order_api_key = $request->duplicate_order_api_key ?? null;
        $setting->save();

        Toastr::success('API settings updated successfully', 'Success!');
        return redirect()->back();
    }
}
