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
}
