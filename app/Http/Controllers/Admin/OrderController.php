<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Customer;
use App\Models\District;
use App\Models\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Courierapi;
use App\Models\SmsGateway;
use App\Models\GeneralSetting;
use App\Models\Color;
use App\Models\Size;
use App\Models\ProductVariantPrice;
use App\Models\Coupon;
use Carbon\Carbon;
use App\Models\FundTransaction;
use App\Models\Vendor;
use App\Models\VendorWallet;
use App\Models\VendorWalletTransaction;
use App\Helpers\FundHelper;
use App\Models\Expense;
use App\Services\RedXService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Gloudemans\Shoppingcart\Facades\Cart;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | COMMON STOCK HANDLER
    |--------------------------------------------------------------------------
    |
    | activeStatuses = 1,2,3,5,6,8  => স্টক মাইনাস
    | newStatus = 11 এবং oldStatus active হলে => স্টক প্লাস
    |
    */
    protected function handleStockChange(Order $order, int $oldStatus, int $newStatus)
    {
        $activeStatuses = [1, 2, 3, 5, 6, 8];

        // 1) প্রথমবার active status এ ঢুকলে স্টক কমবে
        if (in_array($newStatus, $activeStatuses) && !in_array($oldStatus, $activeStatuses)) {
            $details = OrderDetails::where('order_id', $order->id)
                ->with('product:id,stock') // ✅ Eager load products to avoid N+1
                ->get();

            foreach ($details as $row) {
                if ($row->product) {
                    $row->product->stock = max(0, $row->product->stock - $row->qty);
                    $row->product->save();
                }
            }
        }

        // 2) cancel (11) হলে, যদি আগেরটা active group এ থাকে -> স্টক রিস্টোর
        if ($newStatus == 11 && in_array($oldStatus, $activeStatuses)) {
            $details = OrderDetails::where('order_id', $order->id)
                ->with('product:id,stock') // ✅ Eager load products to avoid N+1
                ->get();

            foreach ($details as $row) {
                if ($row->product) {
                    $row->product->stock = $row->product->stock + $row->qty;
                    $row->product->save();
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FRAUD CHECK PART
    |--------------------------------------------------------------------------
    */

    public function fraudCheck(Request $request)
    {
        $mobile = $request->input('mobile');

        if (!$mobile) {
            return response()->json(['status' => 'failed', 'message' => 'Mobile number missing']);
        }

        // সেটিংস থেকে API Key নেওয়া - manual check-এর মতোই same approach
        $generalSetting = GeneralSetting::where('status', 1)->first();
        $apiKey = isset($generalSetting->fraud_api_key) ? $generalSetting->fraud_api_key : null;

        if (!$apiKey) {
            return response()->json(['status' => 'failed', 'message' => 'Fraud API Key missing']);
        }

        $apiUrl = "https://www.creativedesign.com.bd/api/v1/check-fraud";

        try {
            // Manual check-এর মতোই same API call (timeout ছাড়া)
            $response = Http::withHeaders([
                'x-api-key'    => $apiKey,
                'Content-Type' => 'application/json'
            ])->post($apiUrl, [
                'phone' => $mobile,
            ]);

            $res = $response->json();

            if (isset($res['status']) && $res['status'] === 'success') {
                
                // ⭐ মূল পরিবর্তন: শুধুমাত্র একটি অর্ডার নয়, এই মোবাইল নাম্বারের সব অর্ডার খুঁজে বের করা
                $orders = Order::whereHas('shipping', function ($q) use ($mobile) {
                    $q->where('phone', $mobile);
                })->get();

                if ($orders->isEmpty()) {
                    return response()->json(['status' => 'success', 'data' => $res]);
                }

                // সব অর্ডারে লুপ চালিয়ে ডাটা আপডেট করা
                foreach ($orders as $order) {
                    
                    if (isset($res['is_fraud']) && $res['is_fraud'] === true) {
                        $order->fraud_rate = 0; 
                    } 
                    elseif (isset($res['data'])) {
                        $cData = $res['data'];

                        $order->pathao_success = isset($cData['pathao']['success_parcel']) ? $cData['pathao']['success_parcel'] : 0;
                        $order->pathao_cancel  = isset($cData['pathao']['cancelled_parcel']) ? $cData['pathao']['cancelled_parcel'] : 0;
                        $order->pathao_rate    = isset($cData['pathao']['success_ratio']) ? $cData['pathao']['success_ratio'] : 0;

                        $order->redx_success   = isset($cData['redx']['success_parcel']) ? $cData['redx']['success_parcel'] : 0;
                        $order->redx_cancel    = isset($cData['redx']['cancelled_parcel']) ? $cData['redx']['cancelled_parcel'] : 0;
                        $order->redx_rate      = isset($cData['redx']['success_ratio']) ? $cData['redx']['success_ratio'] : 0;

                        $order->steadfast_success = isset($cData['steadfast']['success_parcel']) ? $cData['steadfast']['success_parcel'] : 0;
                        $order->steadfast_cancel  = isset($cData['steadfast']['cancelled_parcel']) ? $cData['steadfast']['cancelled_parcel'] : 0;
                        $order->steadfast_rate    = isset($cData['steadfast']['success_ratio']) ? $cData['steadfast']['success_ratio'] : 0;

                        if(isset($cData['summary'])) {
                             $order->fraud_success = isset($cData['summary']['success_parcel']) ? $cData['summary']['success_parcel'] : 0;
                             $order->fraud_cancel  = isset($cData['summary']['cancelled_parcel']) ? $cData['summary']['cancelled_parcel'] : 0;
                             $order->fraud_rate    = isset($cData['summary']['success_ratio']) ? $cData['summary']['success_ratio'] : 0;
                        }
                    }
                    $order->save();
                }

                return response()->json([
                    'status' => 'success',
                    'data'   => $res
                ]);
            } else {
                return response()->json([
                    'status' => 'failed', 
                    'message' => isset($res['message']) ? $res['message'] : 'Fraud check ব্যর্থ হয়েছে'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'API Error: ' . $e->getMessage()
            ]);
        }
    }

    public function manualFraudCheckPage()
    {
        return view('backEnd.fraud.manual_check');
    }

    public function manualFraudCheck(Request $request)
    {
        $mobile = $request->input('mobile');

        if (!$mobile) {
            return back()->with('error', 'দয়া করে একটি মোবাইল নাম্বার লিখুন');
        }

        // 1. ডাটাবেস থেকে সেটিংস আনা
        $generalSetting = GeneralSetting::where('status', 1)->first();
        $apiKey = isset($generalSetting->fraud_api_key) ? $generalSetting->fraud_api_key : null;

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
                    $data = isset($res['data']) ? $res['data'] : [];
                }
                
                return view('backEnd.fraud.manual_check', compact('mobile', 'data'));

            } else {
                return back()->with('error', isset($res['message']) ? $res['message'] : 'Fraud check ব্যর্থ হয়েছে');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'API Error: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DUPLICATE ORDER CHECK PART
    |--------------------------------------------------------------------------
    */

    public function duplicateOrderCheck(Request $request)
    {
        $mobile = $request->input('mobile');

        if (!$mobile) {
            return response()->json(['status' => 'failed', 'message' => 'Mobile number missing']);
        }

        // সেটিংস থেকে Duplicate Order API Key নেওয়া
        $generalSetting = GeneralSetting::where('status', 1)->first();
        $apiKey = isset($generalSetting->duplicate_order_api_key) ? $generalSetting->duplicate_order_api_key : null;

        if (!$apiKey) {
            return response()->json(['status' => 'failed', 'message' => 'Duplicate Order API Key missing']);
        }

        try {
            // API কল করা (Duplicate Order API)
            $response = Http::withHeaders([
                'x-api-key'    => $apiKey,
                'Content-Type' => 'application/json'
            ])->post("https://www.creativedesign.com.bd/api/v1/check-duplicate-order", [
                'phone' => $mobile,
            ]);

            $res = $response->json();

            if (isset($res['status']) && $res['status'] === 'success') {
                
                // এই মোবাইল নাম্বারের সব অর্ডার খুঁজে বের করা
                $orders = Order::whereHas('shipping', function ($q) use ($mobile) {
                    $q->where('phone', $mobile);
                })->get();

                if ($orders->isEmpty()) {
                    return response()->json(['status' => 'failed', 'message' => 'Order not found for this mobile']);
                }

                // সব অর্ডারে লুপ চালিয়ে ডাটা আপডেট করা
                foreach ($orders as $order) {
                    
                    if (isset($res['is_duplicate']) && $res['is_duplicate'] === true) {
                        $order->is_duplicate_order = 1; 
                        $order->duplicate_order_count = isset($res['duplicate_count']) ? $res['duplicate_count'] : 0;
                        $order->duplicate_order_rate = isset($res['duplicate_rate']) ? $res['duplicate_rate'] : 0;
                        $order->last_duplicate_order_date = isset($res['last_duplicate_date']) ? \Carbon\Carbon::parse($res['last_duplicate_date']) : null;
                    } 
                    elseif (isset($res['data'])) {
                        $cData = $res['data'];

                        // Duplicate order related data
                        $order->is_duplicate_order = isset($cData['is_duplicate']) && $cData['is_duplicate'] === true ? 1 : 0;
                        $order->duplicate_order_count = isset($cData['duplicate_count']) ? $cData['duplicate_count'] : 0;
                        $order->duplicate_order_rate = isset($cData['duplicate_rate']) ? $cData['duplicate_rate'] : 0;
                        $order->last_duplicate_order_date = isset($cData['last_duplicate_date']) ? \Carbon\Carbon::parse($cData['last_duplicate_date']) : null;
                    }
                    $order->save();
                }

                return response()->json([
                    'status' => 'success',
                    'data'   => $res
                ]);
            } else {
                return response()->json(['status' => 'failed', 'message' => 'API Error']);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function manualDuplicateOrderCheckPage()
    {
        return view('backEnd.duplicate_order.manual_check');
    }

    public function manualDuplicateOrderCheck(Request $request)
    {
        $mobile = $request->input('mobile');

        if (!$mobile) {
            return back()->with('error', 'দয়া করে একটি মোবাইল নাম্বার লিখুন');
        }

        // 1. ডাটাবেস থেকে সেটিংস আনা
        $generalSetting = GeneralSetting::where('status', 1)->first();
        $apiKey = isset($generalSetting->duplicate_order_api_key) ? $generalSetting->duplicate_order_api_key : null;

        if (!$apiKey) {
            return back()->with('error', 'Duplicate Order API Key সেটিংস প্যানেলে সেট করা নেই');
        }

        $apiUrl = "https://www.creativedesign.com.bd/api/v1/check-duplicate-order";

        try {
            $response = Http::withHeaders([
                'x-api-key'    => $apiKey,
                'Content-Type' => 'application/json'
            ])->post($apiUrl, [
                'phone' => $mobile,
            ]);

            $res = $response->json();

            if (isset($res['status']) && $res['status'] === 'success') {
                
                if (isset($res['is_duplicate']) && $res['is_duplicate'] === true) {
                    $data = [
                        'is_duplicate' => true,
                        'message'  => isset($res['message']) ? $res['message'] : 'Duplicate order detected',
                        'duplicate_count' => isset($res['duplicate_count']) ? $res['duplicate_count'] : 0
                    ];
                } else {
                    $data = isset($res['data']) ? $res['data'] : [];
                }
                
                return view('backEnd.duplicate_order.manual_check', compact('mobile', 'data'));

            } else {
                return back()->with('error', isset($res['message']) ? $res['message'] : 'Duplicate order check ব্যর্থ হয়েছে');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'API Error: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ORDER LIST
    |--------------------------------------------------------------------------
    */

    public function index($slug, Request $request)
    {
        if ($slug == 'all') {
            // ✅ Cache order count for 5 minutes
            $orders_count = Cache::remember('orders_count_all', 300, function () {
                return Order::count();
            });
            
            $order_status = (object) [
                'name'         => 'All',
                'orders_count' => $orders_count,
            ];

            $show_data = Order::latest()
                ->with([
                    'shipping:id,order_id,name,phone,address',
                    'status:id,name,slug',
                    'customer:id,name,phone,email',
                    'user:id,name,email',
                    'orderdetails:id,order_id,product_id,vendor_id,product_name,qty,sale_price',
                    'orderdetails.vendor:id,shop_name,owner_name'
                ]);

            if ($request->keyword) {
                $show_data = $show_data->where(function ($query) use ($request) {
                    $query->orWhere('invoice_id', 'LIKE', '%' . $request->keyword . '%')
                        ->orWhereHas('shipping', function ($subQuery) use ($request) {
                            $subQuery->where('phone', $request->keyword);
                        });
                });
            }
            $show_data = $show_data->paginate(10);
        } else {
            // ✅ Cache order status with count
            $order_status = Cache::remember("order_status_{$slug}", 300, function () use ($slug) {
                return OrderStatus::where('slug', $slug)->withCount('orders')->first();
            });
            
            $show_data = Order::where(['order_status' => $order_status->id])
                ->latest()
                ->with([
                    'shipping:id,order_id,name,phone,address',
                    'status:id,name,slug',
                    'customer:id,name,phone,email',
                    'user:id,name,email',
                    'orderdetails:id,order_id,product_id,vendor_id,product_name,qty,sale_price',
                    'orderdetails.vendor:id,shop_name,owner_name'
                ])
                ->paginate(10);
        }

        // ✅ Cache users dropdown for 10 minutes
        $users = Cache::remember('users_dropdown', 600, function () {
            return User::select('id', 'name')->limit(100)->get();
        });
        
        // ✅ Cache courier APIs for 30 minutes
        $steadfast = Cache::remember('courier_steadfast', 1800, function () {
            return Courierapi::where(['status' => 1, 'type' => 'steadfast'])->first();
        });
        
        $pathao_info = Cache::remember('courier_pathao', 1800, function () {
            return Courierapi::where(['status' => 1, 'type' => 'pathao'])
                ->select('id', 'type', 'url', 'token', 'status')
                ->first();
        });

        // ✅ Cache Pathao API responses for 10 minutes (API calls are slow)
        if ($pathao_info && $pathao_info->token) {
            $pathaocities = Cache::remember('pathao_cities', 600, function () use ($pathao_info) {
                try {
                    $baseUrl = rtrim($pathao_info->url, '/');
                    $baseUrl = preg_replace('#/aladdin/?$#', '', $baseUrl);
                    
                    $response = Http::timeout(5)->withHeaders([
                        'Authorization' => 'Bearer ' . $pathao_info->token,
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json'
                    ])->get($baseUrl . '/aladdin/api/v1/city-list');
                    
                    return $response->json() ?? [];
                } catch (\Exception $e) {
                    \Log::error('Pathao cities fetch failed', ['error' => $e->getMessage()]);
                    return [];
                }
            });

            $pathaostore = Cache::remember('pathao_stores', 600, function () use ($pathao_info) {
                try {
                    $baseUrl = rtrim($pathao_info->url, '/');
                    $baseUrl = preg_replace('#/aladdin/?$#', '', $baseUrl);
                    
                    $response2 = Http::timeout(5)->withHeaders([
                        'Authorization' => 'Bearer ' . $pathao_info->token,
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json'
                    ])->get($baseUrl . '/aladdin/api/v1/stores');
                    
                    return $response2->json() ?? [];
                } catch (\Exception $e) {
                    \Log::error('Pathao stores fetch failed', ['error' => $e->getMessage()]);
                    return [];
                }
            });
        } else {
            $pathaocities = [];
            $pathaostore  = [];
        }

        // ✅ Cache RedX API responses for 10 minutes
        $redx_info = Cache::remember('courier_redx', 1800, function () {
            return Courierapi::where(['status' => 1, 'type' => 'redx'])->first();
        });
        
        $redxAreas = [];
        $redxPickupStores = [];
        
        if ($redx_info && $redx_info->token) {
            $redxAreas = Cache::remember('redx_areas', 600, function () use ($redx_info) {
                try {
                    $redxService = new RedXService();
                    $areasResult = $redxService->getAreas();
                    return $areasResult && isset($areasResult['areas']) ? $areasResult['areas'] : [];
                } catch (\Exception $e) {
                    \Log::error('RedX areas fetch failed', ['error' => $e->getMessage()]);
                    return [];
                }
            });
            
            $redxPickupStores = Cache::remember('redx_pickup_stores', 600, function () use ($redx_info) {
                try {
                    $redxService = new RedXService();
                    $storesResult = $redxService->getPickupStores();
                    return $storesResult && isset($storesResult['pickup_stores']) ? $storesResult['pickup_stores'] : [];
                } catch (\Exception $e) {
                    \Log::error('RedX stores fetch failed', ['error' => $e->getMessage()]);
                    return [];
                }
            });
        }

        // ✅ Cache blocked IPs for 5 minutes
        $blockedIps = Cache::remember('blocked_ips', 300, function () {
            return \App\Models\IpBlock::pluck('ip_no')->toArray();
        });
        
        // ✅ Cache order statuses for 30 minutes
        $orderstatus = Cache::remember('order_statuses_list', 1800, function () {
            return OrderStatus::orderBy('id')->get();
        });

        return view('backEnd.order.index', compact('show_data', 'order_status', 'users', 'steadfast', 'pathaostore', 'pathaocities', 'blockedIps', 'pathao_info', 'redx_info', 'redxAreas', 'redxPickupStores', 'orderstatus'));
    }

    public function pathaocity(Request $request)
    {
        $pathao_info = Courierapi::where(['status' => 1, 'type' => 'pathao'])
            ->select('id', 'type', 'url', 'token', 'status')->first();

        if ($pathao_info && $pathao_info->token && $request->city_id) {
            // Clean up URL - remove trailing slashes and /aladdin if present
            $baseUrl = rtrim($pathao_info->url, '/');
            $baseUrl = preg_replace('#/aladdin/?$#', '', $baseUrl);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $pathao_info->token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ])->get($baseUrl . '/aladdin/api/v1/cities/' . $request->city_id . '/zone-list');
            
            $pathaozones = $response->json();
            return response()->json($pathaozones);
        } else {
            return response()->json([
                'message' => 'Pathao configuration not found or token missing',
                'type' => 'error',
                'code' => 400,
                'data' => []
            ], 400);
        }
    }

    public function pathaozone(Request $request)
    {
        $pathao_info = Courierapi::where(['status' => 1, 'type' => 'pathao'])
            ->select('id', 'type', 'url', 'token', 'status')->first();

        if ($pathao_info && $pathao_info->token && $request->zone_id) {
            // Clean up URL - remove trailing slashes and /aladdin if present
            $baseUrl = rtrim($pathao_info->url, '/');
            $baseUrl = preg_replace('#/aladdin/?$#', '', $baseUrl);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $pathao_info->token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ])->get($baseUrl . '/aladdin/api/v1/zones/' . $request->zone_id . '/area-list');
            
            $pathaoareas = $response->json();
            return response()->json($pathaoareas);
        } else {
            return response()->json([
                'message' => 'Pathao configuration not found or token missing',
                'type' => 'error',
                'code' => 400,
                'data' => []
            ], 400);
        }
    }

    /**
     * Get RedX Areas (AJAX)
     */
    public function redxAreas(Request $request)
    {
        $redx_info = Courierapi::where(['status' => 1, 'type' => 'redx'])->first();

        if (!$redx_info || !$redx_info->token) {
            return response()->json([
                'status' => 'error',
                'message' => 'RedX configuration not found or token missing',
            ], 400);
        }

        try {
            $redxService = new RedXService();
            
            $postCode = $request->input('post_code');
            $districtName = $request->input('district_name');
            
            $result = $redxService->getAreas($postCode, $districtName);
            
            if ($result && isset($result['areas'])) {
                return response()->json([
                    'status' => 'success',
                    'areas' => $result['areas']
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch areas'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RedX Pickup Stores (AJAX)
     */
    public function redxPickupStores(Request $request)
    {
        $redx_info = Courierapi::where(['status' => 1, 'type' => 'redx'])->first();

        if (!$redx_info || !$redx_info->token) {
            return response()->json([
                'status' => 'error',
                'message' => 'RedX configuration not found or token missing',
            ], 400);
        }

        try {
            $redxService = new RedXService();
            $result = $redxService->getPickupStores();
            
            if ($result && isset($result['pickup_stores'])) {
                return response()->json([
                    'status' => 'success',
                    'pickup_stores' => $result['pickup_stores']
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch pickup stores'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function order_pathao(Request $request)
    {
        // Handle both array and comma-separated string
        $orders_id = isset($request->order_ids) ? $request->order_ids : [];
        if (is_string($orders_id)) {
            $orders_id = array_filter(array_map('trim', explode(',', $orders_id)));
        }
        if (!is_array($orders_id)) {
            $orders_id = [];
        }

        if (empty($orders_id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No orders selected.'
            ], 400);
        }

        $pathao_info = Courierapi::where(['status' => 1, 'type' => 'pathao'])->first();

        if (!$pathao_info) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pathao courier not configured.'
            ], 400);
        }
        
        // Token নেই বা expired হলে নতুন token generate করুন
        if (empty($pathao_info->token) && !empty($pathao_info->client_id) && !empty($pathao_info->client_secret)) {
            try {
                // Clean up URL
                $apiUrl = isset($pathao_info->url) ? $pathao_info->url : 'https://api-hermes.pathao.com';
                $apiUrl = rtrim($apiUrl, '/');
                $apiUrl = preg_replace('#/aladdin/?$#', '', $apiUrl);
                
                $tokenResponse = $this->generatePathaoToken(
                    $pathao_info->client_id,
                    $pathao_info->client_secret,
                    $apiUrl,
                    $pathao_info->username,
                    $pathao_info->password
                );
                
                if ($tokenResponse && isset($tokenResponse['access_token'])) {
                    $pathao_info->token = $tokenResponse['access_token'];
                    $pathao_info->save();
                }
            } catch (\Exception $e) {
                \Log::error('Pathao token generation failed: ' . $e->getMessage());
            }
        }
        
        if (empty($pathao_info->token)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pathao access token not available. Please generate token first.'
            ], 400);
        }

        $results = ['success' => [], 'failed' => []];

        foreach ($orders_id as $order_id) {
            $order = Order::with('shipping')->find($order_id);
            if (!$order) {
                $results['failed'][] = ['order_id' => $order_id, 'message' => 'Order not found'];
                continue;
            }

            try {
                // Clean up URL - remove trailing slashes and /aladdin if present
                $baseUrl = rtrim($pathao_info->url, '/');
                $baseUrl = preg_replace('#/aladdin/?$#', '', $baseUrl);
                
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $pathao_info->token,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ])->post($baseUrl . '/aladdin/api/v1/orders', [
                    'store_id'           => $request->pathaostore,
                    'merchant_order_id'  => $order->invoice_id,
                    'sender_name'        => 'Test',
                    'sender_phone'       => $order->shipping ? $order->shipping->phone : '',
                    'recipient_name'     => $order->shipping ? $order->shipping->name : '',
                    'recipient_phone'    => $order->shipping ? $order->shipping->phone : '',
                    'recipient_address'  => $order->shipping ? $order->shipping->address : '',
                    'recipient_city'     => $request->pathaocity,
                    'recipient_zone'     => $request->pathaozone,
                    'recipient_area'     => $request->pathaoarea,
                    'delivery_type'      => 48,
                    'item_type'          => 2,
                    'special_instruction'=> 'Special note- product must be check after delivery',
                    'item_quantity'      => 1,
                    'item_weight'        => 0.5,
                    'amount_to_collect'  => !empty($order->customer_payable_amount) 
                        ? round($order->customer_payable_amount) 
                        : round($order->amount),
                    'item_description'   => 'Special note- product must be check after delivery',
                ]);

                if ($response->successful()) {
                    $res = $response->json();
                    $consignmentId = isset($res['data']['consignment_id']) ? $res['data']['consignment_id'] : (isset($res['consignment']['consignment_id']) ? $res['consignment']['consignment_id'] : (isset($res['consignment_id']) ? $res['consignment_id'] : null));
                    if ($consignmentId) {
                        $order->courier_type = 'pathao';
                        $order->courier_tracking_id = $consignmentId;
                        $order->courier_sent_at = now();
                        $order->consignment_id = $consignmentId;
                        $order->order_status = 5;
                        $order->save();

                        $results['success'][] = [
                            'order_id' => $order_id,
                            'consignment_id' => $consignmentId,
                        ];
                    } else {
                        $results['failed'][] = [
                            'order_id' => $order_id,
                            'message' => 'No consignment id in response',
                            'raw' => $res,
                        ];
                    }
                } else {
                    $results['failed'][] = [
                        'order_id' => $order_id,
                        'http_status' => $response->status(),
                        'body' => $response->body(),
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'order_id' => $order_id,
                    'message'  => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'result' => $results,
        ]);
    }
    
    /**
     * Generate Pathao Access Token
     */
    private function generatePathaoToken($clientId, $clientSecret, $baseUrl = 'https://api-hermes.pathao.com')
    {
        try {
            // Method 1: Try standard OAuth endpoint
            $response = Http::asForm()->post($baseUrl . '/aladdin/api/v1/issue-token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $clientId,
                'password' => $clientSecret,
                'grant_type' => 'password'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    return $data;
                }
            }
            
            // Method 2: Try alternative endpoint
            $response2 = Http::asForm()->post($baseUrl . '/aladdin/api/v1/authentication/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);
            
            if ($response2->successful()) {
                $data = $response2->json();
                if (isset($data['access_token'])) {
                    return $data;
                }
            }
            
            // Method 3: Try with JSON
            $response3 = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($baseUrl . '/aladdin/api/v1/issue-token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials'
            ]);
            
            if ($response3->successful()) {
                $data = $response3->json();
                if (isset($data['access_token'])) {
                    return $data;
                }
            }
            
            throw new \Exception('Token generation failed. Please check your credentials.');
        } catch (\Exception $e) {
            \Log::error('Pathao token generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INVOICE / PROCESS
    |--------------------------------------------------------------------------
    */

    public function invoice($invoice_id)
    {
        $order = Order::where(['invoice_id' => $invoice_id])
            ->with(['orderdetails', 'orderdetails.size', 'orderdetails.color', 'payment', 'shipping', 'customer'])
            ->firstOrFail();

        $orderstatus = OrderStatus::all();

        return view('backEnd.order.invoice', compact('order', 'orderstatus'));
    }

    public function process($invoice_id)
    {
        $data = Order::where(['invoice_id' => $invoice_id])
            ->select('id', 'invoice_id', 'order_status')
            ->with(['orderdetails', 'orderdetails.size', 'orderdetails.color'])
            ->first();

        $shippingcharge = ShippingCharge::where('status', 1)->get();

        return view('backEnd.order.process', compact('data', 'shippingcharge'));
    }

    /**
     * Update single order status via AJAX (from invoice page)
     */
    public function updateSingleStatus(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_status' => 'required|exists:order_statuses,id',
        ]);

        $order = Order::findOrFail($request->order_id);
        $oldStatus = (int) $order->order_status;
        $newStatus = (int) $request->order_status;

        $order->order_status = $newStatus;
        $order->save();

        // Handle fund transaction if status changed to completed (6)
        if ($newStatus == 6 && $oldStatus != 6) {
            FundTransaction::create([
                'direction'  => 'in',
                'source'     => 'sale',
                'source_id'  => $order->id,
                'amount'     => $order->amount,
                'note'       => 'Order complete (#' . $order->invoice_id . ') - Manual update',
                'created_by' => auth()->id(),
            ]);

            // Credit vendors for their items
            $this->distributeVendorEarnings($order);
            
            // Credit reseller wallet if this is a reseller order
            $this->creditResellerWallet($order);
        }

        // Handle stock change
        $this->handleStockChange($order, $oldStatus, $newStatus);

        if ($newStatus == 11) {
            \App\Helpers\ResellerOrderHelper::deductDeliveryChargeOnCancel($order);
        }

        \Log::info('Order status manually updated', [
            'order_id' => $order->id,
            'invoice_id' => $order->invoice_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'order_status' => $newStatus,
            'order_status_name' => isset($order->status->name) ? $order->status->name : 'N/A',
        ]);
    }

    public function order_process(Request $request)
    {
        $link = OrderStatus::find($request->status)->slug;

        $order     = Order::find($request->id);
        $oldStatus = (int) $order->order_status;
        $newStatus = (int) $request->status;

        $order->order_status = $newStatus;
        $order->admin_note   = $request->admin_note;

        if ($newStatus == 6 && $oldStatus != 6) {
            FundTransaction::create([
                'direction'  => 'in',
                'source'     => 'sale',
                'source_id'  => $order->id,
                'amount'     => $order->amount,
                'note'       => 'Order complete (#' . $order->invoice_id . ') via process page',
                'created_by' => auth()->id(),
            ]);

            // Credit vendors for their items
            $this->distributeVendorEarnings($order);
            
            // Credit reseller wallet if this is a reseller order
            $this->creditResellerWallet($order);
        }

        $order->save();

        // স্টক হ্যান্ডেল
        $this->handleStockChange($order, $oldStatus, $newStatus);

        if ($newStatus == 11) {
            \App\Helpers\ResellerOrderHelper::deductDeliveryChargeOnCancel($order);
        }

        $shipping_update = Shipping::where('order_id', $order->id)->first();
        $shippingfee     = ShippingCharge::find($request->area);

        if ($shippingfee && ($shippingfee->name != $request->area)) {
            $total                = $order->amount + ($shippingfee->amount - $order->shipping_charge);
            $order->shipping_charge = $shippingfee->amount;
            $order->amount          = $total;
            $order->save();
        }

        if ($shipping_update) {
            $shipping_update->name    = $request->name;
            $shipping_update->phone   = $request->phone;
            $shipping_update->address = $request->address;
            $shipping_update->area    = isset($shippingfee->name) ? $shippingfee->name : $shipping_update->area;
            $shipping_update->save();
        }

        if ($newStatus == 5 && $oldStatus != 5) {
            $courier_info = Courierapi::where(['status' => 1, 'type' => 'steadfast'])->first();
            if ($courier_info) {
                // For reseller orders: use customer_payable_amount (reseller selling price + shipping)
                // For normal orders: use amount (main price + shipping)
                $codAmount = !empty($order->customer_payable_amount) 
                    ? $order->customer_payable_amount 
                    : $order->amount;
                    
                $consignmentData = [
                    'invoice'          => $order->invoice_id,
                    'recipient_name'   => $order->shipping ? $order->shipping->name : 'InboxHat',
                    'recipient_phone'  => $order->shipping ? $order->shipping->phone : '01750578495',
                    'recipient_address'=> $order->shipping ? $order->shipping->address : '01750578495',
                    'cod_amount'       => $codAmount
                ];
                $client   = new Client();
                $response = $client->post($courier_info->url, [
                    'json'    => $consignmentData,
                    'headers' => [
                        'Api-Key'    => $courier_info->api_key,
                        'Secret-Key' => $courier_info->secret_key,
                        'Accept'     => 'application/json',
                    ],
                ]);

                $responseData = json_decode($response->getBody(), true);
                
                // Save courier information
                if ($responseData) {
                    $consignment_id = null;
                    if (isset($responseData['consignment']['consignment_id']) && $responseData['consignment']['consignment_id']) {
                        $consignment_id = $responseData['consignment']['consignment_id'];
                    } elseif (isset($responseData['data']['consignment_id']) && $responseData['data']['consignment_id']) {
                        $consignment_id = $responseData['data']['consignment_id'];
                    } elseif (isset($responseData['consignment_id']) && $responseData['consignment_id']) {
                        $consignment_id = $responseData['consignment_id'];
                    } elseif (isset($responseData['consignment']['id']) && $responseData['consignment']['id']) {
                        $consignment_id = $responseData['consignment']['id'];
                    } elseif (isset($responseData['data']['id']) && $responseData['data']['id']) {
                        $consignment_id = $responseData['data']['id'];
                    } elseif (isset($responseData['id']) && $responseData['id']) {
                        $consignment_id = $responseData['id'];
                    } elseif (isset($responseData['tracking_id']) && $responseData['tracking_id']) {
                        $consignment_id = $responseData['tracking_id'];
                    } elseif (isset($responseData['data']['tracking_id']) && $responseData['data']['tracking_id']) {
                        $consignment_id = $responseData['data']['tracking_id'];
                    } elseif (isset($responseData['consignment']['tracking_id']) && $responseData['consignment']['tracking_id']) {
                        $consignment_id = $responseData['consignment']['tracking_id'];
                    }
                    
                    if ($consignment_id) {
                        $order->courier_type = 'steadfast';
                        $order->courier_tracking_id = (string) $consignment_id;
                        $order->courier_sent_at = now();
                        $order->consignment_id = (string) $consignment_id; // Keep for backward compatibility
                        $order->save();
                        
                        \Log::info('Steadfast courier info saved from order_status_change', [
                            'order_id' => $order->id,
                            'tracking_id' => $consignment_id
                        ]);
                    }
                }
            }
        }

        Toastr::success('Success', 'Order status change successfully');
        return redirect('admin/order/' . $link);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE / BULK DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request)
    {
        Order::where('id', $request->id)->delete();
        OrderDetails::where('order_id', $request->id)->delete();
        Shipping::where('order_id', $request->id)->delete();
        Payment::where('order_id', $request->id)->delete();

        Toastr::success('Success', 'Order delete success successfully');
        return redirect()->back();
    }

    public function bulk_destroy(Request $request)
    {
        $orders_id = isset($request->order_ids) ? $request->order_ids : [];
        foreach ($orders_id as $order_id) {
            Order::where('id', $order_id)->delete();
            OrderDetails::where('order_id', $order_id)->delete();
            Shipping::where('order_id', $order_id)->delete();
            Payment::where('order_id', $order_id)->delete();
        }
        return response()->json(['status' => 'success', 'message' => 'Order delete successfully']);
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGN / BULK COURIER / PRINT
    |--------------------------------------------------------------------------
    */

    public function order_assign(Request $request)
    {
        Order::whereIn('id', $request->input('order_ids', []))
            ->update(['user_id' => $request->user_id]);

        return response()->json(['status' => 'success', 'message' => 'Order user id assign']);
    }

    // ✅ Bulk status change + stock handle
    public function order_status(Request $request)
    {
        // Check if this is AJAX request
        if (!$request->ajax() && !$request->wantsJson()) {
            // For non-AJAX requests, validate and return JSON anyway
        }
        
        // Manual validation to avoid redirect
        $orderStatus = $request->input('order_status');
        $orderIds = $request->input('order_ids', []);
        
        if (empty($orderStatus) || $orderStatus === '' || $orderStatus === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select a status',
                'errors' => ['order_status' => ['Please select a status']]
            ], 422);
        }
        
        if (empty($orderIds) || !is_array($orderIds) || count($orderIds) === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please select at least one order',
                'errors' => ['order_ids' => ['Please select at least one order']]
            ], 422);
        }
        
        // Validate status exists
        $orderStatusModel = OrderStatus::find($orderStatus);
        if (!$orderStatusModel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Selected status is invalid',
                'errors' => ['order_status' => ['Selected status is invalid']]
            ], 422);
        }
        
        // Validate order IDs exist
        $validOrderIds = Order::whereIn('id', $orderIds)->pluck('id')->toArray();
        if (count($validOrderIds) !== count($orderIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'One or more selected orders are invalid',
                'errors' => ['order_ids' => ['One or more selected orders are invalid']]
            ], 422);
        }
        
        $sms_gateway  = SmsGateway::where('status', 1)->first();
        $site_setting = GeneralSetting::where('status', 1)->first();

        $targetStatus = (int) $orderStatus;
        
        // Use validated order IDs
        $orderIdsToProcess = $validOrderIds;

        // ✅ Eager load customers to avoid N+1 query
        $orders = Order::whereIn('id', $orderIdsToProcess)
            ->with('customer:id,id,name,phone')
            ->get();

        foreach ($orders as $order) {

            $oldStatus = (int) $order->order_status;

            $order->order_status = $targetStatus;
            $order->update();

            if ($targetStatus == 6 && $oldStatus != 6) {
                FundTransaction::create([
                    'direction'  => 'in',
                    'source'     => 'sale',
                    'source_id'  => $order->id,
                    'amount'     => $order->amount,
                    'note'       => 'Order complete (#' . $order->invoice_id . ')',
                    'created_by' => auth()->id(),
                ]);

                // Credit vendors for their items
                $this->distributeVendorEarnings($order);
                
                // Credit reseller wallet if this is a reseller order
                $this->creditResellerWallet($order);
            }

            // স্টক হ্যান্ডেল
            $this->handleStockChange($order, $oldStatus, $targetStatus);

            if ($targetStatus == 11) {
                \App\Helpers\ResellerOrderHelper::deductDeliveryChargeOnCancel($order);
            }

            // ✅ Use eager loaded customer instead of find()
            if ($sms_gateway && $order->customer) {
                $url  = $sms_gateway->url;
                $data = [
                    "api_key"  => $sms_gateway->api_key,
                    "number"   => $order->customer->phone,
                    "type"     => 'text',
                    "senderid" => $sms_gateway->serderid,
                    "message"  => "Dear {$order->customer->name},\r\n"
                        . "Your order (Order ID: {$order->invoice_id}) status has been updated to: "
                        . "{$orderStatusModel->name}.\r\n"
                        . "Thank you for using " . (isset($site_setting->name) ? $site_setting->name : 'our service') . "!",
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                curl_close($ch);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Order status change successfully'
        ]);
    }

    public function order_print(Request $request)
    {
        $orders = Order::whereIn('id', $request->input('order_ids', []))
            ->with('orderdetails.color', 'orderdetails.size', 'orderdetails.image', 'payment', 'shipping', 'customer')
            ->get();

        if ($request->input('type') === 'label') {
            $view = view('backEnd.order.label', ['orders' => $orders])->render();
        } else {
            $view = view('backEnd.order.print', ['orders' => $orders])->render();
        }

        return response()->json(['status' => 'success', 'view' => $view]);
    }

    public function bulk_courier($slug, Request $request)
    {
        $courier_info = Courierapi::where(['status' => 1, 'type' => $slug])->first();

        if (!$courier_info) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Courier information not found.'
            ]);
        }

        $orders_ids = isset($request->order_ids) ? $request->order_ids : [];
        if (empty($orders_ids)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No orders selected.'
            ]);
        }

        $successOrders = [];
        $failedOrders  = [];

        foreach ($orders_ids as $order_id) {
            $order = Order::with('shipping', 'orderdetails')->find($order_id);
            if (!$order) continue;

            try {
                // RedX API uses different structure
                if ($slug === 'redx') {
                    // Verify RedX is configured
                    $redxConfig = Courierapi::where(['status' => 1, 'type' => 'redx'])->first();
                    if (!$redxConfig || empty($redxConfig->token)) {
                        $failedOrders[] = [
                            'order_id' => $order_id,
                            'message' => 'RedX API not configured or token missing. Please configure RedX in API Integration settings.',
                        ];
                        continue;
                    }
                    
                    $redxService = new RedXService();
                    
                    // Verify service initialized properly
                    if (!$redxService->isConfigured()) {
                        $configStatus = $redxService->getConfigStatus();
                        \Log::error('RedX Service not configured', [
                            'order_id' => $order_id,
                            'config_status' => $configStatus
                        ]);
                        
                        $failedOrders[] = [
                            'order_id' => $order_id,
                            'message' => 'RedX service not configured. Please check API token and URL in settings.',
                        ];
                        continue;
                    }
                    
                    // Get delivery area ID from shipping area
                    // Note: You may need to map shipping area to RedX area_id
                    $deliveryAreaId = isset($request->delivery_area_id) ? $request->delivery_area_id : 1; // Default or from request
                    $pickupStoreId = isset($request->pickup_store_id) ? $request->pickup_store_id : null;
                    
                    // Calculate parcel weight (in grams)
                    $parcelWeight = 500; // Default 500g, you can calculate from order details
                    if ($order->orderdetails && $order->orderdetails->count() > 0) {
                        // Calculate weight from products if available
                        $parcelWeight = $order->orderdetails->sum(function($detail) {
                            return ((isset($detail->product) && isset($detail->product->weight) ? $detail->product->weight : 0) * $detail->qty);
                        });
                        if ($parcelWeight < 100) $parcelWeight = 500; // Minimum 500g
                    }
                    
                    // Prepare parcel details JSON
                    $parcelDetailsJson = [];
                    if ($order->orderdetails) {
                        foreach ($order->orderdetails as $detail) {
                            $parcelDetailsJson[] = [
                                'name' => isset($detail->product_name) ? $detail->product_name : 'Product',
                                'category' => (isset($detail->product) && isset($detail->product->category) && isset($detail->product->category->name) ? $detail->product->category->name : 'General'),
                                'value' => (int)(isset($detail->sale_price) ? $detail->sale_price : 0)
                            ];
                        }
                    }
                    
                    // Validate required fields
                    $customerName = trim(isset($order->shipping->name) ? $order->shipping->name : 'Unknown');
                    $customerPhone = trim(isset($order->shipping->phone) ? $order->shipping->phone : '00000000000');
                    $customerAddress = trim(isset($order->shipping->address) ? $order->shipping->address : 'No address');
                    
                    if (empty($customerName) || $customerName === 'Unknown') {
                        $failedOrders[] = [
                            'order_id' => $order_id,
                            'message' => 'Customer name is required',
                        ];
                        continue;
                    }
                    
                    if (empty($customerPhone) || $customerPhone === '00000000000') {
                        $failedOrders[] = [
                            'order_id' => $order_id,
                            'message' => 'Customer phone is required',
                        ];
                        continue;
                    }
                    
                    if (empty($customerAddress) || $customerAddress === 'No address') {
                        $failedOrders[] = [
                            'order_id' => $order_id,
                            'message' => 'Customer address is required',
                        ];
                        continue;
                    }
                    
                    // For reseller orders: use customer_payable_amount (reseller selling price + shipping)
                    // For normal orders: use amount (main price + shipping)
                    $codAmount = !empty($order->customer_payable_amount) 
                        ? $order->customer_payable_amount 
                        : $order->amount;
                    
                    $data = [
                        'customer_name' => $customerName,
                        'customer_phone' => $customerPhone,
                        'delivery_area' => isset($order->shipping->area) ? $order->shipping->area : 'Unknown',
                        'delivery_area_id' => (int)$deliveryAreaId,
                        'customer_address' => $customerAddress,
                        'merchant_invoice_id' => $order->invoice_id,
                        'cash_collection_amount' => (string)$codAmount,
                        'parcel_weight' => (string)$parcelWeight, // API expects string
                        'instruction' => isset($order->note) ? $order->note : '',
                        'value' => (string)$codAmount,
                    ];
                    
                    // Add parcel_details_json only if not empty
                    if (!empty($parcelDetailsJson)) {
                        $data['parcel_details_json'] = $parcelDetailsJson;
                    }
                    
                    if ($pickupStoreId) {
                        $data['pickup_store_id'] = $pickupStoreId;
                    }
                    
                    $result = $redxService->createParcel($data);
                    
                    \Log::info('RedX Create Parcel Response', [
                        'order_id' => $order_id,
                        'invoice_id' => $order->invoice_id,
                        'result' => $result
                    ]);
                    
                    if ($result && isset($result['tracking_id'])) {
                        $consignment_id = $result['tracking_id'];
                        
                        $order->courier_type = 'redx';
                        $order->courier_tracking_id = $consignment_id;
                        $order->courier_sent_at = now();
                        $order->consignment_id = $consignment_id;
                        $order->order_status = 5;
                        $order->save();
                        
                        \Log::info('✅ RedX parcel created successfully', [
                            'order_id' => $order_id,
                            'invoice_id' => $order->invoice_id,
                            'tracking_id' => $consignment_id
                        ]);
                        
                        $successOrders[] = [
                            'order_id' => $order_id,
                            'consignment_id' => $consignment_id,
                            'message' => 'RedX parcel created successfully',
                        ];
                    } else {
                        $errorMessage = 'Failed to create RedX parcel';
                        if (isset($result['error'])) {
                            $errorMessage .= ': ' . $result['error'];
                        }
                        if (isset($result['message'])) {
                            $errorMessage .= ' - ' . $result['message'];
                        }
                        if (isset($result['status'])) {
                            $errorMessage .= ' (Status: ' . $result['status'] . ')';
                        }
                        
                        \Log::error('❌ RedX parcel creation failed', [
                            'order_id' => $order_id,
                            'invoice_id' => $order->invoice_id,
                            'result' => $result,
                            'data_sent' => $data
                        ]);
                        
                        $failedOrders[] = [
                            'order_id' => $order_id,
                            'message' => $errorMessage,
                            'details' => $result
                        ];
                    }
                    
                    continue; // Skip to next order
                }
                
                // For other couriers (Steadfast, etc.)
                // For reseller orders: use customer_payable_amount (reseller selling price + shipping)
                // For normal orders: use amount (main price + shipping)
                $codAmount = !empty($order->customer_payable_amount) 
                    ? $order->customer_payable_amount 
                    : $order->amount;
                    
                $data = [
                    'invoice'          => $order->invoice_id,
                    'recipient_name'   => isset($order->shipping->name) ? $order->shipping->name : 'Unknown',
                    'recipient_phone'  => isset($order->shipping->phone) ? $order->shipping->phone : '00000000000',
                    'recipient_address'=> isset($order->shipping->address) ? $order->shipping->address : 'No address',
                    'cod_amount'       => $codAmount,
                ];

                // Clean up URL - remove spaces and trailing slashes
                $apiUrl = trim($courier_info->url);
                $apiUrl = rtrim($apiUrl, '/');
                $apiUrl = str_replace(' ', '', $apiUrl); // Remove any spaces in URL
                
                $client   = new \GuzzleHttp\Client();
                $response = $client->post($apiUrl, [
                    'json'    => $data,
                    'headers' => [
                        'Api-Key'    => $courier_info->api_key,
                        'Secret-Key' => $courier_info->secret_key,
                        'Accept'     => 'application/json',
                    ],
                ]);

                // Get response body as string first
                $responseBody = $response->getBody()->getContents();
                $res = json_decode($responseBody, true);
                
                // Log full response for debugging
                \Log::info('Courier Response for ' . $slug, [
                    'order_id' => $order_id,
                    'invoice_id' => $order->invoice_id,
                    'response' => $res,
                    'response_keys' => is_array($res) ? array_keys($res) : 'not_array',
                    'status_code' => $response->getStatusCode(),
                    'raw_response' => $responseBody
                ]);

                // Try multiple ways to get consignment_id from Steadfast/RedX response
                $consignment_id = null;
                
                // Check various response structures
                if (is_array($res)) {
                    // Method 1: consignment.consignment_id
                    if (isset($res['consignment']['consignment_id'])) {
                        $consignment_id = $res['consignment']['consignment_id'];
                    }
                    // Method 2: data.consignment_id
                    elseif (isset($res['data']['consignment_id'])) {
                        $consignment_id = $res['data']['consignment_id'];
                    }
                    // Method 3: consignment_id (direct)
                    elseif (isset($res['consignment_id'])) {
                        $consignment_id = $res['consignment_id'];
                    }
                    // Method 4: consignment.id
                    elseif (isset($res['consignment']['id'])) {
                        $consignment_id = $res['consignment']['id'];
                    }
                    // Method 5: data.id
                    elseif (isset($res['data']['id'])) {
                        $consignment_id = $res['data']['id'];
                    }
                    // Method 6: id (direct)
                    elseif (isset($res['id'])) {
                        $consignment_id = $res['id'];
                    }
                    // Method 7: tracking_id
                    elseif (isset($res['tracking_id'])) {
                        $consignment_id = $res['tracking_id'];
                    }
                    // Method 8: data.tracking_id
                    elseif (isset($res['data']['tracking_id'])) {
                        $consignment_id = $res['data']['tracking_id'];
                    }
                    // Method 9: consignment.tracking_id
                    elseif (isset($res['consignment']['tracking_id'])) {
                        $consignment_id = $res['consignment']['tracking_id'];
                    }
                    // Method 10: Check if response has success and data structure
                    elseif (isset($res['success']) && isset($res['data'])) {
                        $consignment_id = isset($res['data']['consignment_id']) ? $res['data']['consignment_id'] : (isset($res['data']['id']) ? $res['data']['id'] : (isset($res['data']['tracking_id']) ? $res['data']['tracking_id'] : null));
                    }
                }

                // Convert to string if found
                if ($consignment_id !== null) {
                    $consignment_id = (string) $consignment_id;
                }

                if ($consignment_id) {
                    // Save courier information
                    $order->courier_type = $slug; // steadfast, redx, etc
                    $order->courier_tracking_id = $consignment_id;
                    $order->courier_sent_at = now();
                    $order->consignment_id = $consignment_id; // Keep for backward compatibility
                    $order->order_status   = 5;
                    $order->save();

                    \Log::info('✅ Courier info saved successfully', [
                        'order_id' => $order_id,
                        'invoice_id' => $order->invoice_id,
                        'courier_type' => $slug,
                        'tracking_id' => $consignment_id
                    ]);

                    $successOrders[] = [
                        'order_id'       => $order_id,
                        'consignment_id' => $consignment_id,
                        'message'        => isset($res['message']) ? $res['message'] : 'Order placed successfully',
                    ];
                } else {
                    // Log full response structure for debugging
                    \Log::error('❌ No consignment_id found in response', [
                        'order_id' => $order_id,
                        'invoice_id' => $order->invoice_id,
                        'courier' => $slug,
                        'response' => $res,
                        'response_structure' => is_array($res) ? json_encode($res, JSON_PRETTY_PRINT) : 'not_array'
                    ]);
                    
                    // Also return response in error message for debugging
                    $errorMessage = 'No consignment_id found in response. ';
                    if (is_array($res)) {
                        $errorMessage .= 'Response keys: ' . implode(', ', array_keys($res));
                    } else {
                        $errorMessage .= 'Response: ' . json_encode($res);
                    }
                    
                    $failedOrders[] = [
                        'order_id' => $order_id,
                        'message'  => $errorMessage,
                        'response' => $res,
                        'response_keys' => is_array($res) ? array_keys($res) : null,
                    ];
                }
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                // Handle 4xx errors (401, 403, 404, etc.)
                $response = $e->getResponse();
                $statusCode = $response ? $response->getStatusCode() : 0;
                $responseBody = $response ? $response->getBody()->getContents() : '';
                $errorData = json_decode($responseBody, true);
                
                $errorMessage = $e->getMessage();
                if ($errorData && isset($errorData['message'])) {
                    $errorMessage = $errorData['message'];
                } elseif ($responseBody) {
                    $errorMessage = $responseBody;
                }
                
                \Log::error('Courier API Error (ClientException)', [
                    'order_id' => $order_id,
                    'courier' => $slug,
                    'status_code' => $statusCode,
                    'error_message' => $errorMessage,
                    'response_body' => $responseBody
                ]);
                
                $failedOrders[] = [
                    'order_id' => $order_id,
                    'message'  => $errorMessage . ' (Status: ' . $statusCode . ')',
                    'status_code' => $statusCode
                ];
            } catch (\GuzzleHttp\Exception\ServerException $e) {
                // Handle 5xx errors
                $response = $e->getResponse();
                $statusCode = $response ? $response->getStatusCode() : 0;
                $responseBody = $response ? $response->getBody()->getContents() : '';
                
                \Log::error('Courier API Error (ServerException)', [
                    'order_id' => $order_id,
                    'courier' => $slug,
                    'status_code' => $statusCode,
                    'response_body' => $responseBody
                ]);
                
                $failedOrders[] = [
                    'order_id' => $order_id,
                    'message'  => 'Server error: ' . $e->getMessage() . ' (Status: ' . $statusCode . ')',
                    'status_code' => $statusCode
                ];
            } catch (\Exception $e) {
                \Log::error('Courier API Error (General)', [
                    'order_id' => $order_id,
                    'courier' => $slug,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $failedOrders[] = [
                    'order_id' => $order_id,
                    'message'  => $e->getMessage(),
                ];
            }
        }

        // Return detailed response for debugging
        return response()->json([
            'status'  => 'success',
            'message' => 'Courier processed successfully',
            'success' => $successOrders,
            'failed'  => $failedOrders,
            'debug' => [
                'courier_type' => $slug,
                'total_orders' => count($orders_ids),
                'success_count' => count($successOrders),
                'failed_count' => count($failedOrders)
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK REPORT / ORDER REPORT
    |--------------------------------------------------------------------------
    */

    public function stock_report(Request $request)
    {
        $products = Product::select('id', 'name', 'new_price', 'stock')
            ->where('status', 1);

        if ($request->keyword) {
            $products = $products->where('name', 'LIKE', '%' . $request->keyword . "%");
        }
        if ($request->category_id) {
            $products = $products->where('category_id', $request->category_id);
        }
        if ($request->start_date && $request->end_date) {
            $products = $products->whereBetween('updated_at', [$request->start_date, $request->end_date]);
        }

        $total_purchase = $products->sum(\DB::raw('purchase_price * stock'));
        $total_stock    = $products->sum('stock');
        $total_price    = $products->sum(\DB::raw('new_price * stock'));

        $products   = $products->paginate(10);
        $categories = Category::where('status', 1)->get();

        return view('backEnd.reports.stock', compact(
            'products',
            'categories',
            'total_purchase',
            'total_stock',
            'total_price'
        ));
    }

    public function order_report(Request $request)
    {
        $users = User::where('status', 1)->get();

        $orders = OrderDetails::with('shipping', 'order')
            ->whereHas('order', function ($query) {
                $query->where('order_status', 6);
            });

        if ($request->keyword) {
            $orders = $orders->where('name', 'LIKE', '%' . $request->keyword . "%");
        }
        if ($request->user_id) {
            $orders = $orders->whereHas('order', function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            });
        }
        if ($request->start_date && $request->end_date) {
            $orders = $orders->whereBetween('updated_at', [$request->start_date, $request->end_date]);
        }

        $total_purchase = $orders->sum(\DB::raw('purchase_price * qty'));
        $total_item     = $orders->sum('qty');
        $total_sales    = $orders->sum(\DB::raw('sale_price * qty'));
        $orders         = $orders->paginate(10);

        return view('backEnd.reports.order', compact(
            'orders',
            'users',
            'total_purchase',
            'total_item',
            'total_sales'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | POS ORDER CREATE / UPDATE
    |--------------------------------------------------------------------------
    */

    public function order_create()
    {
        Cart::instance('pos_shopping')->destroy();

        // ✅ Limit products for POS dropdown to avoid memory issues
        $products = Product::select('id', 'name', 'new_price','stock', 'product_code')
            ->where(['status' => 1])
            ->limit(100)
            ->get();

        $cartinfo       = Cart::instance('pos_shopping')->content();
        $shippingcharge = ShippingCharge::where('status', 1)->get();

        return view('backEnd.order.create', compact(
            'products',
            'cartinfo',
            'shippingcharge'
        ));
    }

    public function order_store(Request $request)
    {
        $this->validate($request, [
            'name'    => 'required',
            'phone'   => 'required',
            'address' => 'required',
            'area'    => 'required',
        ]);

        if (Cart::instance('pos_shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');
            return redirect()->back();
        }

        $subtotalRaw = Cart::instance('pos_shopping')->subtotal();
        $subtotal   = (float) preg_replace('/[^\d.]/', '', (string) $subtotalRaw);
        $discount   = (float) (Session::get('pos_discount') ?? 0);
        $shippingfee = ShippingCharge::find($request->area);

        $exits_customer = Customer::where('phone', $request->phone)
            ->select('phone', 'id')->first();

        if ($exits_customer) {
            $customer_id = $exits_customer->id;
        } else {
            $password        = rand(111111, 999999);
            $store           = new Customer();
            $store->name     = $request->name;
            $store->slug     = $request->name;
            $store->phone    = $request->phone;
            $store->password = bcrypt($password);
            $store->verify   = 1;
            $store->status   = 'active';
            $store->save();
            $customer_id = $store->id;
        }

        $order                  = new Order();
        $order->invoice_id      = rand(11111, 99999);
        $order->amount          = ($subtotal + (isset($shippingfee->amount) ? $shippingfee->amount : 0)) - $discount;
        $order->discount        = $discount ? $discount : 0;
        $order->shipping_charge = isset($shippingfee->amount) ? $shippingfee->amount : 0;
        $order->customer_id     = $customer_id;
        $order->order_status    = 1;
        $order->note            = $request->note;
        $order->save();

        $shipping              = new Shipping();
        $shipping->order_id    = $order->id;
        $shipping->customer_id = $customer_id;
        $shipping->name        = $request->name;
        $shipping->phone       = $request->phone;
        $shipping->address     = $request->address;
        $shipping->area        = isset($shippingfee->name) ? $shippingfee->name : '';
        $shipping->save();

        $payment                 = new Payment();
        $payment->order_id       = $order->id;
        $payment->customer_id    = $customer_id;
        $payment->payment_method = 'Cash On Delivery';
        $payment->amount         = $order->amount;
        $payment->payment_status = 'pending';
        $payment->save();

        foreach (Cart::instance('pos_shopping')->content() as $cart) {
            $sizeId   = $cart->options->size_id ?? null;
            $sizeName = $cart->options->product_size ?? null;
            $colorId   = $cart->options->color_id ?? null;
            $colorName = $cart->options->product_color ?? null;

            Log::channel('single')->info('[POS order_store] Cart options', [
                'product_id' => $cart->id,
                'product_name' => $cart->name,
                'size_id' => $sizeId,
                'product_size' => $sizeName,
                'color_id' => $colorId,
                'product_color' => $colorName,
                'options_raw' => $cart->options ? json_decode(json_encode($cart->options), true) : [],
            ]);

            if (!$sizeName && $sizeId) {
                $s = Size::find($sizeId);
                $sizeName = $s ? ($s->sizeName ?? $s->size_name ?? null) : null;
            }
            if (!$colorName && $colorId) {
                $c = Color::find($colorId);
                $colorName = $c ? ($c->getAttribute('colorName') ?? $c->getAttribute('color_name') ?? $c->colorName ?? null) : null;
            }

            $savedSize  = $sizeId ?: $sizeName;
            $savedColor = $colorId ?: $colorName;
            Log::channel('single')->info('[POS order_store] Saving to order_details', [
                'product_id' => $cart->id,
                'product_size' => $savedSize,
                'product_color' => $savedColor,
            ]);

            $order_details                   = new OrderDetails();
            $order_details->order_id         = $order->id;
            $order_details->product_id       = $cart->id;
            $order_details->product_name     = $cart->name;
            $order_details->purchase_price   = isset($cart->options->purchase_price) ? $cart->options->purchase_price : 0;
            $order_details->product_discount = isset($cart->options->product_discount) ? $cart->options->product_discount : 0;
            $order_details->sale_price       = $cart->price;
            $order_details->qty              = $cart->qty;
            $order_details->product_size     = $savedSize;
            $order_details->product_color    = $savedColor;
            $order_details->save();
        }

        // নতুন অর্ডার প্লেস করলে স্টক কমানো (oldStatus = 0, newStatus = 1)
        $this->handleStockChange($order, 0, (int) $order->order_status);

        Cart::instance('pos_shopping')->destroy();
        Session::forget(['pos_shipping', 'pos_discount', 'pos_coupon_code']);

        Toastr::success('Thanks, Your order place successfully', 'Success!');
        return redirect('admin/order/pending');
    }

    public function cart_add(Request $request)
    {
        $product = Product::select('id', 'name', 'stock', 'new_price', 'old_price', 'purchase_price', 'slug')
            ->where(['id' => $request->id])->first();

        $qty      = 1;
        $cartinfo = Cart::instance('pos_shopping')->add([
            'id'      => $product->id,
            'name'    => $product->name,
            'qty'     => $qty,
            'price'   => $product->new_price,
            'options' => [
                'slug'            => $product->slug,
                'image'           => (isset($product->image) && isset($product->image->image)) ? $product->image->image : null,
                'old_price'       => $product->old_price,
                'purchase_price'  => $product->purchase_price,
                'product_size'    => null,
                'product_color'   => null,
                'size_id'         => null,
                'color_id'        => null,
            ],
        ]);
        return response()->json(compact('cartinfo'));
    }

    public function updateNote(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note_type'=> 'required|in:order,admin',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($request->note_type === 'order') {
            if (Schema::hasColumn('orders', 'order_note')) {
                $order->order_note = $request->note;
            } else {
                $order->note = $request->note;
            }
        } else {
            $order->admin_note = $request->note;
        }

        $order->save();

        return response()->json([
            'status' => 'success',
            'note'   => $request->note,
        ]);
    }

    public function cart_content()
    {
        $cartinfo = Cart::instance('pos_shopping')->content();
        return view('backEnd.order.cart_content', compact('cartinfo'));
    }

    public function cart_details()
    {
        $cartinfo = Cart::instance('pos_shopping')->content();
        return view('backEnd.order.cart_details', compact('cartinfo'));
    }

    public function cart_increment(Request $request)
    {
        $qty  = $request->qty + 1;
        $cart = Cart::instance('pos_shopping')->content()->where('rowId', $request->id)->first();

        $cartinfo = Cart::instance('pos_shopping')->update($request->id, [
            'qty'     => $qty,
            'options' => [
                'slug'            => $cart->options->slug,
                'image'           => $cart->options->image,
                'old_price'       => $cart->options->old_price,
                'purchase_price'  => $cart->options->purchase_price,
                'product_size'    => $cart->options->product_size,
                'product_color'   => $cart->options->product_color,
                'size_id'         => $cart->options->size_id ?? null,
                'color_id'        => $cart->options->color_id ?? null,
            ],
        ]);
        return response()->json($cartinfo);
    }

    public function cart_decrement(Request $request)
    {
        $qty  = max(1, $request->qty - 1);
        $cart = Cart::instance('pos_shopping')->content()->where('rowId', $request->id)->first();

        $cartinfo = Cart::instance('pos_shopping')->update($request->id, [
            'qty'     => $qty,
            'options' => [
                'slug'            => $cart->options->slug,
                'image'           => $cart->options->image,
                'old_price'       => $cart->options->old_price,
                'purchase_price'  => $cart->options->purchase_price,
                'product_size'    => $cart->options->product_size,
                'product_color'   => $cart->options->product_color,
                'size_id'         => $cart->options->size_id ?? null,
                'color_id'        => $cart->options->color_id ?? null,
            ],
        ]);

        return response()->json($cartinfo);
    }

    public function cart_remove(Request $request)
    {
        Cart::instance('pos_shopping')->remove($request->id);
        $cartinfo = Cart::instance('pos_shopping')->content();
        return response()->json($cartinfo);
    }

    public function product_discount(Request $request)
    {
        $cart = Cart::instance('pos_shopping')->content()->where('rowId', $request->id)->first();

        $cartinfo = Cart::instance('pos_shopping')->update($request->id, [
            'options' => [
                'slug'            => $cart->options->slug,
                'image'           => $cart->options->image,
                'old_price'       => $cart->options->old_price,
                'purchase_price'  => $cart->options->purchase_price,
                'product_discount'=> $request->discount,
                'product_size'    => $cart->options->product_size,
                'product_color'   => $cart->options->product_color,
                'size_id'         => $cart->options->size_id ?? null,
                'color_id'        => $cart->options->color_id ?? null,
            ],
        ]);
        return response()->json($cartinfo);
    }

    public function cart_update(Request $request)
    {
        Log::channel('single')->info('[POS cart_update] Request', [
            'id' => $request->id,
            'size_id' => $request->size_id,
            'color_id' => $request->color_id,
            'all' => $request->all(),
        ]);

        $rowId = $request->id;
        $cartItem = Cart::instance('pos_shopping')->content()->where('rowId', $rowId)->first();

        // rowId দিয়ে না পেলে product_id দিয়ে খুঁজুন (update এর পর rowId বদলে যেতে পারে)
        if (!$cartItem && $request->product_id) {
            $cartItem = Cart::instance('pos_shopping')->content()->firstWhere('id', $request->product_id);
            if ($cartItem) {
                $rowId = $cartItem->rowId;
            }
        }

        if (!$cartItem) {
            Log::channel('single')->warning('[POS cart_update] Cart item not found', ['rowId' => $rowId, 'product_id' => $request->product_id]);
            return response()->json(['error' => 'Cart item not found']);
        }

        $sizeId  = $request->size_id ?: ($request->product_size ?: null);
        $colorId = $request->color_id ?: ($request->product_color ?: null);

        $product = Product::find($cartItem->id);
        $newPrice = $cartItem->price;
        $sizeName = null;
        $colorName = null;

        if ($product) {
            $variant = ProductVariantPrice::where('product_id', $product->id)
                ->when($sizeId, fn($q) => $q->where('size_id', $sizeId))
                ->when($colorId, fn($q) => $q->where('color_id', $colorId))
                ->first();

            if ($variant && $variant->price > 0) {
                $newPrice = $variant->price;
            } else {
                $newPrice = $product->new_price ?? $product->old_price ?? $cartItem->price;
            }

            if ($sizeId) {
                $size = Size::find($sizeId);
                $sizeName = $size ? ($size->sizeName ?? $size->size_name ?? null) : null;
            }
            if ($colorId) {
                $color = Color::find($colorId);
                $colorName = $color ? ($color->getAttribute('colorName') ?? $color->getAttribute('color_name') ?? $color->colorName ?? null) : null;
            }
        }

        $options = [
            'product_size'    => $sizeName ?? $cartItem->options->product_size,
            'product_color'   => $colorName ?? $cartItem->options->product_color,
            'size_id'         => $sizeId,
            'color_id'        => $colorId,
            'slug'            => $cartItem->options->slug,
            'image'           => $cartItem->options->image,
            'old_price'       => $cartItem->options->old_price,
            'purchase_price'  => $cartItem->options->purchase_price,
        ];
        $updatedItem = Cart::instance('pos_shopping')->update($rowId, ['price' => $newPrice, 'options' => $options]);

        Log::channel('single')->info('[POS cart_update] Saved', [
            'rowId' => $updatedItem ? $updatedItem->rowId : $rowId,
            'sizeId' => $sizeId,
            'colorId' => $colorId,
            'sizeName' => $sizeName,
            'colorName' => $colorName,
        ]);

        // update() options বদলালে rowId বদলে যায়, তাই Cart::get($rowId) ব্যর্থ হয়; update এর রিটার্ন ব্যবহার করুন
        return response()->json($updatedItem ?? Cart::instance('pos_shopping')->content()->firstWhere('id', $cartItem->id));
    }

    public function cart_shipping(Request $request)
    {
        $shippingcharge = ShippingCharge::where(['status' => 1, 'id' => $request->id])->first();
        $shipping = ($shippingcharge && isset($shippingcharge->amount)) ? $shippingcharge->amount : 0;

        Session::put('pos_shipping', $shipping);
        return response()->json($shipping);
    }

    public function posApplyCoupon(Request $request)
    {
        $request->validate(['coupon_code' => 'required']);
        $code = trim($request->coupon_code);

        $coupon = Coupon::where('code', $code)->where('status', 1)->first();
        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'কুপন কোড বৈধ নয়']);
        }

        $today = Carbon::now()->format('Y-m-d');
        if (($coupon->valid_from && $today < $coupon->valid_from) || ($coupon->valid_to && $today > $coupon->valid_to)) {
            return response()->json(['success' => false, 'message' => 'কুপন মেয়াদ শেষ অথবা এখনো চালু হয়নি']);
        }

        $subtotalRaw = Cart::instance('pos_shopping')->subtotal();
        $subtotal = (float) preg_replace('/[^\d.]/', '', (string) $subtotalRaw);
        if ($subtotal <= 0) {
            return response()->json(['success' => false, 'message' => 'কার্টে প্রোডাক্ট যোগ করুন']);
        }

        $minPurchase = (float) ($coupon->min_purchase ?? 0);
        if ($minPurchase > 0 && $subtotal < $minPurchase) {
            return response()->json(['success' => false, 'message' => "ন্যূনতম ক্রয় ৳{$minPurchase} প্রয়োজন"]);
        }

        $type = strtolower((string) ($coupon->type ?? 'flat'));
        $value = (float) ($coupon->value ?? 0);
        if ($type === 'percent' || $type === 'percentage') {
            $discount = $subtotal * ($value / 100);
        } else {
            $discount = $value;
        }
        $discount = round(min($discount, $subtotal), 2);
        Session::put('pos_coupon_code', $coupon->code);
        Session::put('pos_discount', $discount);

        return response()->json([
            'success' => true,
            'message' => 'কুপন অ্যাপ্লাই হয়েছে! বাঁচালেন ৳' . $discount,
        ]);
    }

    public function posRemoveCoupon()
    {
        Session::forget(['pos_coupon_code', 'pos_discount']);
        return response()->json(['success' => true]);
    }

    public function cart_clear(Request $request)
    {
        Cart::instance('pos_shopping')->destroy();
        Session::forget(['pos_shipping', 'pos_discount', 'pos_coupon_code']);
        return redirect()->back();
    }

    /*
    |--------------------------------------------------------------------------
    | ORDER EDIT / UPDATE (POS)
    |--------------------------------------------------------------------------
    */

    public function order_edit($invoice_id)
    {
        // ✅ Limit products for POS dropdown to avoid memory issues
        $products = Product::select('id', 'name', 'new_price', 'product_code')
            ->where(['status' => 1])
            ->limit(100)
            ->get();

        $shippingcharge = ShippingCharge::where('status', 1)->get();
        $order          = Order::where('invoice_id', $invoice_id)->firstOrFail();

        Cart::instance('pos_shopping')->destroy();

        $shippinginfo = Shipping::where('order_id', $order->id)->first();
        Session::put('product_discount', $order->discount);
        Session::put('pos_shipping', $order->shipping_charge);

        $orderdetails = OrderDetails::where('order_id', $order->id)
            ->with(['image', 'color', 'size'])
            ->get();

        foreach ($orderdetails as $ordetails) {
            Cart::instance('pos_shopping')->add([
                'id'      => $ordetails->product_id,
                'name'    => $ordetails->product_name,
                'qty'     => $ordetails->qty,
                'price'   => $ordetails->sale_price,
                'options' => [
                    'image'             => (isset($ordetails->image) && isset($ordetails->image->image) ? $ordetails->image->image : 'public/no-image.png'),
                    'purchase_price'    => $ordetails->purchase_price,
                    'product_discount'  => $ordetails->product_discount,
                    'details_id'        => $ordetails->id,
                    'product_color'     => $ordetails->product_color,
                    'product_size'      => $ordetails->product_size,
                    'product_color_name'=> isset($ordetails->color->name) ? $ordetails->color->name : (isset($ordetails->product_color) ? $ordetails->product_color : 'N/A'),
                    'product_size_name' => isset($ordetails->size->name) ? $ordetails->size->name : (isset($ordetails->product_size) ? $ordetails->product_size : 'N/A'),
                ],
            ]);
        }

        $cartinfo = Cart::instance('pos_shopping')->content();

        return view('backEnd.order.edit', compact(
            'products',
            'cartinfo',
            'shippingcharge',
            'shippinginfo',
            'order'
        ));
    }

    public function order_update(Request $request)
    {
        $this->validate($request, [
            'name'    => 'required',
            'phone'   => 'required',
            'address' => 'required',
            'area'    => 'required',
        ]);

        if (Cart::instance('pos_shopping')->count() <= 0) {
            Toastr::error('Your shopping cart is empty', 'Failed!');
            return redirect()->back();
        }

        $subtotal    = str_replace([',', '.00'], '', Cart::instance('pos_shopping')->subtotal());
        $discount    = Session::get('pos_discount', 0) + Session::get('product_discount', 0);
        $shippingfee = ShippingCharge::find($request->area);

        $customer = Customer::firstOrCreate(
            ['phone' => $request->phone],
            [
                'name'     => $request->name,
                'slug'     => $request->name,
                'password' => bcrypt(rand(111111, 999999)),
                'verify'   => 1,
                'status'   => 'active'
            ]
        );

        $order                  = Order::findOrFail($request->order_id);
        $order->amount          = ($subtotal + (isset($shippingfee->amount) ? $shippingfee->amount : 0)) - $discount;
        $order->discount        = isset($discount) ? $discount : 0;
        $order->shipping_charge = isset($shippingfee->amount) ? $shippingfee->amount : 0;
        $order->customer_id     = $customer->id;
        $order->order_status    = 1; // এখানে চাইলে স্টক হ্যান্ডেল করতে চাইলে handleStockChange আরও কেয়ারফুললি ব্যবহার করতে হবে
        $order->note            = $request->note;
        $order->save();

        $shipping           = Shipping::where('order_id', $order->id)->firstOrFail();
        $shipping->name     = $request->name;
        $shipping->phone    = $request->phone;
        $shipping->address  = $request->address;
        $shipping->area     = isset($shippingfee->name) ? $shippingfee->name : $shipping->area;
        $shipping->save();

        $payment                 = Payment::where('order_id', $order->id)->firstOrNew(['order_id' => $order->id]);
        $payment->customer_id    = $customer->id;
        $payment->payment_method = 'Cash On Delivery';
        $payment->amount         = $order->amount;
        $payment->payment_status = 'pending';
        $payment->save();

        $existingDetails = OrderDetails::where('order_id', $order->id)->pluck('id')->toArray();
        $updatedIds      = [];

        foreach (Cart::instance('pos_shopping')->content() as $cart) {
            if (!empty($cart->options->details_id) && in_array($cart->options->details_id, $existingDetails)) {
                $detail = OrderDetails::find($cart->options->details_id);
            } else {
                $detail              = new OrderDetails();
                $detail->order_id    = $order->id;
                $detail->product_id  = $cart->id;
                $detail->product_name= $cart->name;
            }

            $detail->purchase_price   = isset($cart->options->purchase_price) ? $cart->options->purchase_price : 0;
            $detail->product_discount = isset($cart->options->product_discount) ? $cart->options->product_discount : 0;
            $detail->product_color    = isset($cart->options->product_color) ? $cart->options->product_color : null;
            $detail->product_size     = isset($cart->options->product_size) ? $cart->options->product_size : null;
            $detail->sale_price       = $cart->price;
            $detail->qty              = $cart->qty;
            $detail->save();

            $updatedIds[] = $detail->id;
        }

        OrderDetails::where('order_id', $order->id)
            ->whereNotIn('id', $updatedIds)
            ->delete();

        Cart::instance('pos_shopping')->destroy();
        Session::forget(['pos_shipping', 'pos_discount', 'product_discount']);

        Toastr::success('Order updated successfully!', 'Success!');
        return redirect()->route('admin.orders', 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT STATUS UPDATE
    |--------------------------------------------------------------------------
    */

/*
    |--------------------------------------------------------------------------
    | PAYMENT STATUS UPDATE (With Digital Product Generation)
    |--------------------------------------------------------------------------
    */
    public function updatePaymentStatus(Request $request)
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Order not found!',
            ]);
        }

        // ১. অর্ডার টেবিলে স্ট্যাটাস আপডেট
        $order->payment_status = $request->payment_status;
        $order->save();

        // ২. পেমেন্ট টেবিলে স্ট্যাটাস আপডেট
        $payment = Payment::where('order_id', $order->id)->first();
        if ($payment) {
            $payment->payment_status = $request->payment_status;
            $payment->save();
        }

        // ==============================================================
        // ⭐ NEW LOGIC: জেনারেট ডিজিটাল ডাউনলোড (যদি পেইড হয়)
        // ==============================================================
        $paid_keywords = ['paid', 'completed', 'success', 'approved'];

        if (in_array(strtolower($request->payment_status), $paid_keywords)) {
            
            $orderDetails = OrderDetails::where('order_id', $order->id)
                ->with('product:id,is_digital,digital_file,download_limit,download_expire_days')
                ->get();

            foreach ($orderDetails as $detail) {
                $product = $detail->product;

                if ($product) {
                    // চেক করি: এই প্রোডাক্টের জন্য ইতিমধ্যে ডাউনলোড লিংক আছে কিনা?
                    $alreadyExists = \App\Models\DigitalDownload::where('order_id', $order->id)
                                    ->where('product_id', $product->id)
                                    ->exists();

                    // যদি লিংক না থাকে এবং প্রোডাক্টটি ডিজিটাল হয় (আপনার লজিক অনুযায়ী চেক বসাতে পারেন)
                    // আমি এখানে ধরে নিচ্ছি আপনি সব প্রোডাক্টের জন্যই জেনারেট করতে চান, অথবা 
                    // যদি আপনার প্রোডাক্ট টেবিলে 'type' == 'digital' থাকে তবে সেই কন্ডিশনও দিতে পারেন।
                    
                    if (!$alreadyExists) {
                         // নতুন ডাউনলোড লিংক তৈরি করা হচ্ছে
                         \App\Models\DigitalDownload::create([
                            'order_id'    => $order->id,
                            'customer_id' => $order->customer_id,
                            'product_id'  => $product->id,
                            'token'       => \Illuminate\Support\Str::random(64), // ইউনিক টোকেন
                            'file_path'   => isset($product->digital_file) ? $product->digital_file : 'default_file', // ফাইলের নাম বা পাথ
                            'remaining_downloads' => 9999, // আনলিমিটেড বা নির্দিষ্ট সংখ্যা
                            'expires_at'  => null,
                        ]);
                    }
                }
            }
        }
        // ==============================================================

        return response()->json([
            'status'  => 'success',
            'message' => 'Payment status updated & Digital assets generated successfully!',
        ]);
    }

    /**
     * Distribute vendor earnings and admin commission for completed orders.
     */
    private function distributeVendorEarnings(Order $order): void
    {
        $details = $order->orderdetails()
            ->with([
                'product:id,vendor_id,name',
                'product.vendor:id,commission_rate'
            ])
            ->get();

        foreach ($details as $item) {
            $product = $item->product;
            if (!$product || !$product->vendor_id) {
                continue;
            }

            // Skip if already processed
            if ($item->vendor_paid_at) {
                continue;
            }

            $vendorId = $product->vendor_id;
            $vendor   = $product->vendor;

            // Vendor must be loaded; if missing skip to avoid extra query/N+1
            if (!$vendor) {
                \Log::warning('Vendor not loaded for product: ' . $product->id);
                continue;
            }

            $commissionRate = isset($vendor->commission_rate) ? $vendor->commission_rate : config('app.vendor_commission', 10);
            $lineTotal      = (float) (isset($item->sale_price) ? $item->sale_price : 0) * (float) (isset($item->qty) ? $item->qty : 0);

            $adminCommission = round($lineTotal * ($commissionRate / 100), 2);
            $vendorEarning   = max(0, round($lineTotal - $adminCommission, 2));

            // Update order detail record
            $item->update([
                'vendor_id'        => $vendorId,
                'commission_rate'  => $commissionRate,
                'admin_commission' => $adminCommission,
                'vendor_earning'   => $vendorEarning,
                'vendor_paid_at'   => now(),
            ]);

            // Update wallet
            $wallet = VendorWallet::firstOrCreate(['vendor_id' => $vendorId]);
            $wallet->balance       += $vendorEarning;
            $wallet->total_earned  += $vendorEarning;
            $wallet->save();

            VendorWalletTransaction::create([
                'vendor_id'   => $vendorId,
                'type'        => 'earning',
                'status'      => 'completed',
                'amount'      => $vendorEarning,
                'source_type' => 'order',
                'source_id'   => $item->id,
                'note'        => 'Order #' . $order->invoice_id . ' item earning',
            ]);

            // Add admin commission to fund transaction
            if ($adminCommission > 0) {
                \App\Models\FundTransaction::create([
                    'direction'  => 'in',
                    'source'     => 'vendor_commission',
                    'source_id'  => $order->id,
                    'amount'     => $adminCommission,
                    'note'       => 'Vendor commission from Order #' . $order->invoice_id . ' - Product: ' . $item->product_name,
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Credit reseller wallet when order is delivered.
     * Only credits if order has reseller_profit and hasn't been credited before.
     */
    private function creditResellerWallet(Order $order): void
    {
        // Check if this is a reseller order
        if (!$order->reseller_profit || $order->reseller_profit <= 0) {
            return;
        }

        // Get reseller user from order
        // First check user_id (if reseller placed order directly)
        $resellerUser = null;
        if ($order->user_id) {
            $resellerUser = User::find($order->user_id);
            // Verify it's a reseller
            if ($resellerUser && 
                ($resellerUser->hasRole('reseller') || 
                 (isset($resellerUser->role) && strtolower($resellerUser->role) === 'reseller'))) {
                // Reseller found via user_id
            } else {
                $resellerUser = null;
            }
        }

        // Fallback: Check customer email (for old orders)
        if (!$resellerUser && $order->customer && $order->customer->email) {
            $resellerUser = User::where('email', $order->customer->email)
                ->where(function($query) {
                    $query->where('role', 'reseller')
                          ->orWhereHas('roles', function($q) {
                              $q->where('name', 'reseller');
                          });
                })
                ->first();
        }

        if (!$resellerUser) {
            return;
        }

        // Check if already credited (to avoid double credit)
        if ($order->reseller_wallet_credited) {
            return;
        }

        $resellerProfit = (float) $order->reseller_profit;
        
        if ($resellerProfit > 0) {
            // Update reseller wallet balance
            $resellerUser->wallet_balance = (isset($resellerUser->wallet_balance) ? $resellerUser->wallet_balance : 0) + $resellerProfit;
            $resellerUser->save();

            \App\Models\ResellerWalletTransaction::log(
                $resellerUser->id, 'order_profit', $resellerProfit,
                'Order', $order->id,
                'অর্ডার #' . ($order->invoice_id ?? $order->id) . ' প্রফিট'
            );

            // Mark order as credited to avoid double credit
            $order->reseller_wallet_credited = true;
            $order->save();

            // Optional: Log the transaction (if you have a reseller wallet transaction table)
            // You can create a similar table like VendorWalletTransaction for resellers
        }
    }
}
