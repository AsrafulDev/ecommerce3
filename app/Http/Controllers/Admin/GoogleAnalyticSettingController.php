<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleAnalyticSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Toastr;

class GoogleAnalyticSettingController extends Controller
{
    /**
     * Show edit form (single settings row).
     */
    public function edit()
    {
        $setting = GoogleAnalyticSetting::first();

        if (!$setting) {
            $setting = new GoogleAnalyticSetting([
                'status' => 1,
            ]);
        }

        return view('backEnd.settings.google_analytics', compact('setting'));
    }

    /**
     * Save / update GA4 credentials.
     */
    public function update(Request $request)
    {
        $request->validate([
            'measurement_id' => 'required|string|max:50',
            'api_secret'     => 'nullable|string|max:255',
            'status'         => 'nullable|boolean',
        ]);

        $data = [
            'measurement_id' => $request->measurement_id,
            'api_secret'     => $request->api_secret,
            'status'         => $request->has('status') ? 1 : 0,
        ];

        $setting = GoogleAnalyticSetting::first();
        if ($setting) {
            $setting->update($data);
        } else {
            GoogleAnalyticSetting::create($data);
        }

        Cache::forget('google_analytics_settings');

        Toastr::success('Google Analytics 4 settings updated successfully', 'Success');

        return redirect()->route('admin.google_analytics.edit');
    }
}
