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

        // Get API key from settings
        $generalSetting = GeneralSetting::where('status', 1)->first();
        $apiKey = $generalSetting->fraud_api_key ?? null;

        if (!$apiKey) {
            return back()->with('error', 'Fraud API Key সেটিংস প্যানেলে সেট করা নেই');
        }

        $apiUrl = "https://www.creativedesign.com.bd/api/v1/check-fraud";

        try {
            $response = Http::withHeaders([
                'x-api-key'    => $apiKey,
                'Content-Type' => 'application/json'
            ])->post($apiUrl, [
                'phone' => $mobile,
            ]);

            $res = $response->json();

            if (isset($res['status']) && $res['status'] === 'success') {
                
                if (isset($res['is_fraud']) && $res['is_fraud'] === true) {
                    $data = [
                        'is_fraud' => true,
                        'message'  => $res['message']
                    ];
                } else {
                    $data = $res['data'] ?? [];
                }
                
                return view('reseller.fraud.manual_check', compact('mobile', 'data', 'user'));

            } else {
                return back()->with('error', $res['message'] ?? 'Fraud check ব্যর্থ হয়েছে');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'API Error: ' . $e->getMessage());
        }
    }
}
