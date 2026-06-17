<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Http;

class ResellerFraudController extends Controller
{
    /**
     * Display manual fraud check page for reseller.
     *
     * @return \Illuminate\View\View
     */
    public function manualFraudCheckPage()
    {
        $user = Auth::guard('admin')->user();

        // Verify reseller
        if (!$user || (!$user->hasRole('reseller') && $user->role !== 'reseller')) {
            return redirect()->route('reseller.dashboard');
        }

        return view('reseller.fraud.manual_check', compact('user'));
    }

    /**
     * Perform manual fraud check.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function manualFraudCheck(Request $request)
    {
        $user = Auth::guard('admin')->user();

        // Verify reseller
        if (!$user || (!$user->hasRole('reseller') && $user->role !== 'reseller')) {
            return redirect()->route('reseller.dashboard');
        }

        $mobile = $request->input('mobile');

        if (!$mobile) {
            return back()->with('error', 'দয়া করে একটি মোবাইল নাম্বার লিখুন');
        }

        // ফ্রি API (কোন API Key লাগে না)
        $apiUrl = "https://www.fraudcheck.online/config/check-phone.php?phone=" . urlencode($mobile);

        try {
            $response = Http::timeout(30)->get($apiUrl);
            $res = $response->json();

            if ($res && isset($res['mobile_number'])) {
                $data = $res;
                return view('reseller.fraud.manual_check', compact('mobile', 'data', 'user'));
            } else {
                return back()->with('error', 'Fraud check ব্যর্থ হয়েছে');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'API Error: ' . $e->getMessage());
        }
    }
}
