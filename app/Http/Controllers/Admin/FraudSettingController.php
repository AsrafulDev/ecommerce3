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
        $setting = GeneralSetting::first();
        $setting->fraud_check_enabled = $request->has('fraud_check_enabled') ? 1 : 0;
        $setting->save();

        Toastr::success('Settings updated successfully', 'Success!');
        return redirect()->back();
    }
}
