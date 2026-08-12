<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    /**
     * Show license status/info page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function licenseInfo(Request $request)
    {
        $result = LicenseService::verify($request->has('refresh'));

        $cfg = LicenseService::config();
        $key = LicenseService::licenseKey();

        return view('backEnd.license.info', [
            'isValid'        => $result['valid'],
            'licenseData'    => $result['data'],
            'message'        => $result['message'],
            'domain'         => LicenseService::domain(),
            'licenseKey'     => $key,
            'maskedKey'      => LicenseService::maskKey($key),
            'apiUrl'         => $cfg['api_url'],
            'scriptName'     => $cfg['script_name'],
            'currentVersion' => $cfg['current_version'],
            'isLocal'        => LicenseService::isLocal(),
            'isMaster'       => LicenseService::isMaster(),
            'refreshed'      => $request->has('refresh'),
        ]);
    }

    /**
     * Force a fresh license check and redirect back to the license page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function recheck()
    {
        LicenseService::verify(true);

        return redirect()->route('admin.license.info');
    }

    /**
     * Save the license key from the admin License page.
     * Stored in general_settings.license_key (DB) so the admin can update it
     * without touching code. Empty value = fall back to the hardcoded default.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveLicenseKey(Request $request)
    {
        $request->validate([
            'license_key' => ['nullable', 'string', 'max:100'],
        ]);

        $key = strtoupper(trim((string) $request->input('license_key', '')));

        if (! \Illuminate\Support\Facades\Schema::hasColumn('general_settings', 'license_key')) {
            return redirect()->route('admin.license.info')->with('license_error', __('Please run "php artisan migrate" first — the license_key column is missing.'));
        }

        $setting = \App\Models\GeneralSetting::where('status', 1)->first();
        if ($setting) {
            $setting->license_key = $key;
            $setting->save();
        } else {
            \App\Models\GeneralSetting::create([
                'status'      => 1,
                'license_key' => $key,
            ]);
        }

        // Clear caches so the new key takes effect immediately.
        \Illuminate\Support\Facades\Cache::forget('general_setting');
        LicenseService::verify(true);

        return redirect()->route('admin.license.info')->with('license_saved', __('License key saved.'));
    }
}
