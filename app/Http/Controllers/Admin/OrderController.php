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
use App\Models\OrderNote;
use App\Models\OrderPayment;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use App\Models\Payment;
use App\Models\PaymentGateway;
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
use App\Models\PosHoldCart;
use Carbon\Carbon;
use App\Models\FundTransaction;
use App\Helpers\FundHelper;
use App\Models\Expense;
use App\Services\RedXService;
use App\Services\StockManagementService;
use App\Enums\OrderStatus as OrderStatusEnum;
use App\Enums\PaymentStatus as PaymentStatusEnum;

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
    | COMMON STOCK HANDLER (Updated for Enum-based status)
    |--------------------------------------------------------------------------
    |
    | activeStatuses = confirmed, picking, packing, packed, shipped, out_for_delivery, delivered, completed
    | cancel => restore stock if old status was active
    |
    */
    protected function handleStockChange(Order $order, $oldStatus, $newStatus)
    {
        $oldEnum = is_int($oldStatus) ? OrderStatusEnum::fromLegacyId($oldStatus) : OrderStatusEnum::tryFrom($oldStatus);
        $newEnum = is_int($newStatus) ? OrderStatusEnum::fromLegacyId($newStatus) : OrderStatusEnum::tryFrom($newStatus);

        if (!$oldEnum || !$newEnum) {
            return;
        }

        $wasActive = $oldEnum->consumesStock();
        $isActive  = $newEnum->consumesStock();

        /** @var StockManagementService $stockService */
        $stockService = app(StockManagementService::class);

        // 1) Entering active status → decrease stock (with batch tracking)
        if ($isActive && !$wasActive) {
            $details = OrderDetails::where('order_id', $order->id)
                ->with('product', 'warrantySale')
                ->get();

            foreach ($details as $row) {
                if (!$row->product) {
                    continue;
                }

                try {
                    // ✅ Pass user-selected batch from WarrantySale if available
                    $preferredBatchId = optional($row->warrantySale)->stock_batch_id;
                    $result = $stockService->stockOut($row->product, (int) $row->qty, [
                        'type' => 'sale',
                        'id'   => $order->id,
                    ], $preferredBatchId);

                    // Store COGS and batch details on the order detail
                    $row->update([
                        'cogs'      => $result['cogs'],
                        'batch_ids' => $result['batch_details'],
                    ]);
                } catch (\RuntimeException $e) {
                    // Fallback: simple stock decrement if batch tracking fails
                    $row->product->decrement('stock', (int) $row->qty);
                    Log::warning('Stock batch deduction failed, used fallback', [
                        'product' => $row->product_id,
                        'order'   => $order->id,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        }

        // 2) Cancelled → restore stock if was active
        if ($newEnum === OrderStatusEnum::CANCELLED && $wasActive) {
            $details = OrderDetails::where('order_id', $order->id)
                ->with('product')
                ->get();

            foreach ($details as $row) {
                if (!$row->product) {
                    continue;
                }

                // Restore stock — create a positive batch entry
                $stockService->stockIn($row->product, [
                    'quantity'       => (int) $row->qty,
                    'unit_cost'      => (float) ($row->product->purchase_price ?? 0),
                    'reference_type' => 'sale_return',
                    'reference_id'   => $order->id,
                ]);

                // Clear COGS since it's reversed
                $row->update([
                    'cogs'       => null,
                    'batch_ids'  => null,
                ]);
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

        // সেটিংস থেকে চেক করা - ফ্রড চেক অন/অফ
        $generalSetting = GeneralSetting::where('status', 1)->first();

        // ফ্রড চেক বন্ধ থাকলে API কল না করেই success return
        if (!$generalSetting || !($generalSetting->fraud_check_enabled ?? 1)) {
            return response()->json(['status' => 'success', 'message' => 'Fraud check is disabled']);
        }

        // ফ্রি API (কোন API Key লাগে না) — same as manual fraud check
        $apiUrl = "https://www.fraudcheck.online/config/check-phone.php?phone=" . urlencode($mobile);

        try {
            $response = Http::timeout(30)->get($apiUrl);
            $res = $response->json();

            // API থেকে valid response আসলেই ডাটা আপডেট হবে
            if ($res && isset($res['mobile_number'])) {

                // --- Transform API response to match frontend buildSummary() format ---
                $apiCouriers = $res['apis'] ?? [];

                // Helper to extract courier stats from API courier data
                $extractStats = function ($courierData) {
                    return [
                        'total_parcel'      => (int) ($courierData['total_parcels'] ?? 0),
                        'success_parcel'    => (int) ($courierData['total_delivered_parcels'] ?? 0),
                        'cancelled_parcel'  => (int) ($courierData['total_cancelled_parcels'] ?? 0),
                    ];
                };

                // Map API courier names → frontend key names
                $transformed = [
                    'pathao'    => $extractStats($apiCouriers['Pathao'] ?? []),
                    'redx'      => $extractStats($apiCouriers['Redex'] ?? []),
                    'steadfast' => $extractStats($apiCouriers['CarryBee'] ?? []),
                    'parceldex' => ['total_parcel' => 0, 'success_parcel' => 0, 'cancelled_parcel' => 0],
                    'paperfly'  => ['total_parcel' => 0, 'success_parcel' => 0, 'cancelled_parcel' => 0],
                ];

                // Summary totals
                $totalParcels   = (int) ($res['total_parcels'] ?? 0);
                $totalDelivered = (int) ($res['total_delivered'] ?? 0);
                $totalCancel    = (int) ($res['total_cancel'] ?? 0);
                $successRate    = $totalParcels > 0 ? round(($totalDelivered / $totalParcels) * 100) : 0;

                // Update all orders for this phone number
                $orders = Order::whereHas('shipping', function ($q) use ($mobile) {
                    $q->where('phone', $mobile);
                })->get();

                foreach ($orders as $order) {
                    // Pathao
                    $order->pathao_success = $transformed['pathao']['success_parcel'];
                    $order->pathao_cancel  = $transformed['pathao']['cancelled_parcel'];
                    $pTotal = $transformed['pathao']['total_parcel'];
                    $order->pathao_rate    = $pTotal > 0 ? round(($transformed['pathao']['success_parcel'] / $pTotal) * 100) : 0;

                    // Redx
                    $order->redx_success = $transformed['redx']['success_parcel'];
                    $order->redx_cancel  = $transformed['redx']['cancelled_parcel'];
                    $rTotal = $transformed['redx']['total_parcel'];
                    $order->redx_rate    = $rTotal > 0 ? round(($transformed['redx']['success_parcel'] / $rTotal) * 100) : 0;

                    // Steadfast (mapped from CarryBee)
                    $order->steadfast_success = $transformed['steadfast']['success_parcel'];
                    $order->steadfast_cancel  = $transformed['steadfast']['cancelled_parcel'];
                    $cTotal = $transformed['steadfast']['total_parcel'];
                    $order->steadfast_rate    = $cTotal > 0 ? round(($transformed['steadfast']['success_parcel'] / $cTotal) * 100) : 0;

                    // Summary / Fraud rate
                    $order->fraud_success = $totalDelivered;
                    $order->fraud_cancel  = $totalCancel;
                    $order->fraud_rate    = $successRate;

                    $order->save();
                }

                return response()->json([
                    'status' => 'success',
                    'data'   => $transformed
                ]);

            } else {
                // API failed / invalid response → set orders to pending with note
                $orders = Order::whereHas('shipping', function ($q) use ($mobile) {
                    $q->where('phone', $mobile);
                })->get();

                foreach ($orders as $order) {
                    $order->order_status = 'pending'; // Use string-based enum value
                    $order->addNote(
                        content: 'Fraud check failed at ' . now()->format('d/m/Y h:i A'),
                        type: 'danger',
                        source: 'system'
                    );
                    $order->save();
                }

                return response()->json([
                    'status' => 'failed', 
                    'message' => 'Fraud check API response invalid. Orders set to pending.'
                ]);
            }
        } catch (\Exception $e) {
            // API exception → set orders to pending with note
            $orders = Order::whereHas('shipping', function ($q) use ($mobile) {
                $q->where('phone', $mobile);
            })->get();

            foreach ($orders as $order) {
                $order->order_status = 'pending'; // Use string-based enum value
                $order->addNote(
                    content: 'Fraud check failed at ' . now()->format('d/m/Y h:i A'),
                    type: 'danger',
                    source: 'system'
                );
                $order->save();
            }

            return response()->json([
                'status' => 'error', 
                'message' => 'API Error: ' . $e->getMessage() . '. Orders set to pending.'
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

        // ফ্রি API (কোন API Key লাগে না)
        $apiUrl = "https://www.fraudcheck.online/config/check-phone.php?phone=" . urlencode($mobile);

        try {
            $response = Http::timeout(30)->get($apiUrl);
            $res = $response->json();

            if ($res && isset($res['mobile_number'])) {
                $data = $res;
                return view('backEnd.fraud.manual_check', compact('mobile', 'data'));
            } else {
                return back()->with('error', 'Fraud check ব্যর্থ হয়েছে');
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

        // সেটিংস থেকে Duplicate Order API কনফিগারেশন নেওয়া
        $generalSetting = GeneralSetting::where('status', 1)->first();
        $apiKey     = $generalSetting->duplicate_order_api_key ?? null;
        $apiUrl     = $generalSetting->duplicate_order_api_url ?? 'https://www.creativedesign.com.bd/api/v1/check-duplicate-order';
        $apiMethod  = strtoupper($generalSetting->duplicate_order_method ?? 'POST');
        $phoneKey   = $generalSetting->duplicate_order_phone_key ?? 'phone';

        if (!$apiKey) {
            return response()->json(['status' => 'failed', 'message' => 'Duplicate Order API Key missing']);
        }

        try {
            // API কল করা (Duplicate Order API)
            $headers = [
                'x-api-key'    => $apiKey,
                'Content-Type' => 'application/json'
            ];

            if ($apiMethod === 'GET') {
                $response = Http::withHeaders($headers)->get($apiUrl, [$phoneKey => $mobile]);
            } else {
                $response = Http::withHeaders($headers)->post($apiUrl, [$phoneKey => $mobile]);
            }

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
        $apiKey     = $generalSetting->duplicate_order_api_key ?? null;
        $apiUrl     = $generalSetting->duplicate_order_api_url ?? 'https://www.creativedesign.com.bd/api/v1/check-duplicate-order';
        $apiMethod  = strtoupper($generalSetting->duplicate_order_method ?? 'POST');
        $phoneKey   = $generalSetting->duplicate_order_phone_key ?? 'phone';

        if (!$apiKey) {
            return back()->with('error', 'Duplicate Order API Key সেটিংস প্যানেলে সেট করা নেই');
        }

        try {
            $headers = [
                'x-api-key'    => $apiKey,
                'Content-Type' => 'application/json'
            ];

            if ($apiMethod === 'GET') {
                $response = Http::withHeaders($headers)->get($apiUrl, [$phoneKey => $mobile]);
            } else {
                $response = Http::withHeaders($headers)->post($apiUrl, [$phoneKey => $mobile]);
            }

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
                    'orderdetails:id,order_id,product_id,product_name,qty,sale_price',
                ]);

            if ($request->keyword) {
                $keyword = $request->keyword;
                $show_data = $show_data->where(function ($query) use ($keyword) {
                    $query->where('invoice_id', 'LIKE', '%' . $keyword . '%')
                        ->orWhereHas('shipping', function ($subQuery) use ($keyword) {
                            $subQuery->where('phone', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('name', 'LIKE', '%' . $keyword . '%');
                        })
                        ->orWhereHas('customer', function ($subQuery) use ($keyword) {
                            $subQuery->where('name', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('phone', 'LIKE', '%' . $keyword . '%');
                        });
                });
            }
            $show_data = $show_data->paginate(10);
        } else {
            // ✅ Cache order status with count
            $order_status = Cache::remember("order_status_{$slug}", 300, function () use ($slug) {
                return OrderStatus::where('slug', $slug)->withCount('orders')->first();
            });
            
            if (!$order_status) {
                // Fallback: try to create a virtual status from enum
                $enum = OrderStatusEnum::tryFrom($slug);
                if ($enum) {
                    $count = Order::where('order_status', $slug)->count();
                    $order_status = (object) [
                        'id'           => $slug,
                        'name'         => $enum->label(),
                        'slug'         => $slug,
                        'orders_count' => $count,
                    ];
                } else {
                    return redirect()->route('admin.orders', 'all')->with('error', 'Order status not found.');
                }
            }
            
            $show_data = Order::where(['order_status' => $order_status->slug])
                ->latest()
                ->with([
                    'shipping:id,order_id,name,phone,address',
                    'status:id,name,slug',
                    'customer:id,name,phone,email',
                    'user:id,name,email',
                    'orderdetails:id,order_id,product_id,product_name,qty,sale_price',
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
                        $order->order_status = 'shipped';
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
            ->with(['orderdetails', 'orderdetails.size', 'orderdetails.color', 'payment', 'shipping', 'customer', 'status', 'notes', 'notes.user'])
            ->firstOrFail();

        $orderstatus = OrderStatus::all();
        $statusOptions = OrderStatusEnum::cases();
        $availableActions = $order->getAvailableActions();
        $pipelineActions = $order->getPipelineActions();

        return view('backEnd.order.invoice', compact('order', 'statusOptions', 'orderstatus', 'availableActions', 'pipelineActions'));
    }

    public function process($invoice_id)
    {
        $data = Order::where(['invoice_id' => $invoice_id])
            ->select('id', 'invoice_id', 'order_status', 'payment_status', 'order_type')
            ->with(['orderdetails', 'orderdetails.size', 'orderdetails.color', 'payment', 'shipping'])
            ->first();

        if (!$data) {
            return redirect()->route('admin.orders', 'all')->with('error', 'Order not found.');
        }

        $shippingcharge = ShippingCharge::where('status', 1)->get();
        $orderstatus = OrderStatus::all();
        $availableActions = $data->getAvailableActions();

        return view('backEnd.order.process', compact('data', 'shippingcharge', 'orderstatus', 'availableActions'));
    }

    /**
     * Update single order status via AJAX (from invoice page)
     */
    public function updateSingleStatus(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_status' => 'required',
        ]);

        $order = Order::findOrFail($request->order_id);
        $oldStatusRaw = $order->order_status;
        $newStatusRaw = $request->order_status;

        $oldStatusEnum = is_numeric($oldStatusRaw)
            ? OrderStatusEnum::fromLegacyId((int) $oldStatusRaw)
            : OrderStatusEnum::tryFrom((string) $oldStatusRaw);

        $newStatusEnum = is_numeric($newStatusRaw)
            ? OrderStatusEnum::fromLegacyId((int) $newStatusRaw)
            : OrderStatusEnum::tryFrom((string) $newStatusRaw);

        if (!$newStatusEnum) {
            return response()->json([
                'status' => 'error',
                'message' => 'Selected status is invalid',
            ], 422);
        }

        $newStatus = $newStatusEnum->value;
        $oldStatus = $oldStatusEnum?->value ?? (string) $oldStatusRaw;

        $order->order_status = $newStatus;
        $order->save();

        // Handle fund transaction if status changed to completed
        // Only if no fund transaction already exists for this order (avoid double-crediting POS)
        if ($newStatus === OrderStatusEnum::COMPLETED->value && $oldStatus !== OrderStatusEnum::COMPLETED->value) {
            $existingTx = FundTransaction::where('source', 'sale')->where('source_id', $order->id)->exists();
            if (!$existingTx) {
                FundTransaction::create([
                    'direction'  => 'in',
                    'source'     => 'sale',
                    'source_id'  => $order->id,
                    'amount'     => $order->amount,
                    'note'       => 'Order complete (#' . $order->invoice_id . ') - Manual update',
                    'created_by' => auth()->id(),
                ]);
            }

            $payment = Payment::where('order_id', $order->id)->first();
            if ($payment && strtolower(trim((string) $payment->payment_status)) !== 'paid') {
                $payment->payment_status = 'paid';
                $payment->save();
            }

            if (strtolower(trim((string) $order->payment_status)) !== 'paid') {
                $order->payment_status = 'paid';
                $order->save();
            }
        }

        // Handle stock change
        $this->handleStockChange($order, $oldStatus, $newStatus);

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
        // Support both legacy int ID and new enum string
        if (is_numeric($request->status)) {
            $link = OrderStatus::find($request->status)?->slug ?? 'all';
        } else {
            $link = $request->status;
        }

        $order     = Order::find($request->id);
        $oldStatus = $order->order_status;
        $newStatus = $request->status; // Can be int (legacy) or string (enum)

        // If integer, convert to enum value
        if (is_numeric($newStatus)) {
            $newEnum = OrderStatusEnum::fromLegacyId((int) $newStatus);
            $newStatus = $newEnum->value;
        }

        $order->order_status = $newStatus;

        // Add admin note if provided
        if ($request->filled('admin_note')) {
            $order->addNote(
                content: $request->admin_note,
                type: 'info',
                source: 'admin',
                userId: auth()->id(),
                metadata: ['old_status' => $oldStatus, 'new_status' => $newStatus]
            );
        }

        if ($newStatus === OrderStatusEnum::COMPLETED->value && $oldStatus !== OrderStatusEnum::COMPLETED->value) {
            FundTransaction::create([
                'direction'  => 'in',
                'source'     => 'sale',
                'source_id'  => $order->id,
                'amount'     => $order->amount,
                'note'       => 'Order complete (#' . $order->invoice_id . ') via process page',
                'created_by' => auth()->id(),
            ]);

        }

        $order->save();

        // স্টক হ্যান্ডেল
        $this->handleStockChange($order, $oldStatus, $newStatus);

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
        
        // ✅ Resolve target status: support both enum string and legacy int ID
        if (is_numeric($orderStatus)) {
            $targetEnum = OrderStatusEnum::fromLegacyId((int) $orderStatus);
        } else {
            $targetEnum = OrderStatusEnum::tryFrom($orderStatus);
        }
        
        if (!$targetEnum) {
            return response()->json([
                'status' => 'error',
                'message' => 'Selected status is invalid',
                'errors' => ['order_status' => ['Selected status is invalid']]
            ], 422);
        }
        
        $targetStatusValue = $targetEnum->value;
        $targetLabel = $targetEnum->label();
        
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

        $orders = Order::whereIn('id', $validOrderIds)
            ->with('customer:id,id,name,phone')
            ->get();

        foreach ($orders as $order) {
            $oldStatus = $order->order_status;
            $oldEnum = OrderStatusEnum::tryFrom($oldStatus);

            $order->order_status = $targetStatusValue;
            $order->save();
            
            // Auto-note the status change
            $order->addNote(
                content: "Bulk status change: " . ($oldEnum?->label() ?? $oldStatus) . " → {$targetLabel}",
                type: 'info',
                source: 'system',
                userId: auth()->id()
            );

            // Fund transaction if completing
            if ($targetEnum === OrderStatusEnum::COMPLETED && $oldStatus !== OrderStatusEnum::COMPLETED->value) {
                $exists = FundTransaction::where('source', 'sale')->where('source_id', $order->id)->exists();
                if (!$exists) {
                    FundTransaction::create([
                        'direction'  => 'in',
                        'source'     => 'sale',
                        'source_id'  => $order->id,
                        'amount'     => $order->amount,
                        'note'       => 'Order complete (#' . $order->invoice_id . ') - Bulk update',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Stock handling (pass string values, handleStockChange converts internally)
            $this->handleStockChange($order, $oldStatus, $targetStatusValue);

            // SMS notification
            if ($sms_gateway && $order->customer) {
                $url  = $sms_gateway->url;
                $data = [
                    "api_key"  => $sms_gateway->api_key,
                    "number"   => $order->customer->phone,
                    "type"     => 'text',
                    "senderid" => $sms_gateway->serderid,
                    "message"  => "Dear {$order->customer->name},\r\n"
                        . "Your order (Order ID: {$order->invoice_id}) status has been updated to: "
                        . "{$targetLabel}.\r\n"
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
                        $order->order_status = 'shipped';
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
                    $order->order_status   = 'packed';
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
        // ✅ Limit products for POS dropdown to avoid memory issues
        // Active → visible everywhere; Private → POS only (draft hidden everywhere)
        $products = Product::select('id', 'name', 'new_price','stock', 'product_code')
            ->where(function ($q) {
                $q->where('status', 1)
                  ->orWhere('publish_status', Product::STATUS_PRIVATE);
            })
            ->limit(100)
            ->get();

        $cartinfo       = Cart::instance('pos_shopping')->content();
        $shippingcharge = ShippingCharge::where('status', 1)->get();
        $paymentGateways = PaymentGateway::where('status', 1)->orderBy('type')->get();

        return view('backEnd.order.create', compact(
            'products',
            'cartinfo',
            'shippingcharge',
            'paymentGateways'
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

        // 🛡️ Calculate warranty charges from POS cart
        $warrantyCharge = 0;
        foreach (Cart::instance('pos_shopping')->content() as $item) {
            $warrantyCharge += (float)($item->options->warranty_adjustment ?? 0) * $item->qty;
        }

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
        // ✅ Cart::subtotal() already includes warranty_adjustment in price, so don't add $warrantyCharge again
        $order->amount          = ($subtotal + (isset($shippingfee->amount) ? $shippingfee->amount : 0)) - $discount;
        $order->discount        = $discount ? $discount : 0;
        $order->shipping_charge = isset($shippingfee->amount) ? $shippingfee->amount : 0;
        $order->customer_id     = $customer_id;

        // 🆕 Payment type: paid | partial | cod (from POS form)
        $paymentType            = strtolower(trim((string) $request->input('payment_type', 'paid')));
        $paymentSubMethod       = trim((string) $request->input('payment_method', 'Cash'));
        $paymentNote            = trim((string) $request->input('payment_note', ''));

        // 💰 Compute paid / due
        $total = (float) $order->amount;
        $paid  = min((float) ($request->paid_amount ?? $total), $total);
        $due   = max(0, $total - $paid);

        $order->paid_amount   = $paid;
        $order->due_amount    = $due;
        $order->order_type    = $paymentType === 'cod' ? 'cod' : 'pos';
        // POS fully paid → completed immediately (skip fulfillment)
        // POS partial → completed (goods given) but still has due
        // POS COD → pending (will complete on delivery/payment)
        $order->order_status  = $paymentType === 'cod'
            ? OrderStatusEnum::PENDING->value
            : OrderStatusEnum::COMPLETED->value;
        $order->payment_status = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
        $order->note           = $request->note;
        $order->save();

        log_activity('order', 'create', 'Created order #' . $order->invoice_id . ' — ৳' . number_format($order->amount, 2) . ' (' . $order->payment_status . ')', $order, [
            'customer_id'    => $order->customer_id,
            'amount'         => $order->amount,
            'paid'           => $order->paid_amount,
            'due'            => $order->due_amount,
            'payment_status' => $order->payment_status,
            'order_status'   => $order->order_status,
        ]);

        // Record order note with payment info
        $order->addNote(
            content: 'POS order created | Payment: ' . strtoupper($paymentType)
                . ' | Paid: ৳' . number_format($paid, 2)
                . ($due > 0 ? ' | Due: ৳' . number_format($due, 2) : '')
                . ' | Method: ' . $paymentSubMethod
                . ($paymentNote ? ' | Ref: ' . $paymentNote : ''),
            type: 'info',
            source: 'system',
            userId: auth()->id()
        );

        $shipping              = new Shipping();
        $shipping->order_id    = $order->id;
        $shipping->customer_id = $customer_id;
        $shipping->name        = $request->name;
        $shipping->phone       = $request->phone;
        $shipping->address     = $request->address;
        $shipping->area        = isset($shippingfee->name) ? $shippingfee->name : ($request->area == '0' ? 'Store Pickup' : '');
        $shipping->save();

        // 🆕 Payment history ledger (one row per collection)
        if ($paid > 0) {
            OrderPayment::create([
                'order_id'       => $order->id,
                'customer_id'    => $customer_id,
                'amount'         => $paid,
                'payment_method' => $paymentSubMethod,
                'trx_note'       => $paymentNote ?: null,
                'created_by'     => auth()->id(),
            ]);
        }

        $payment                 = new Payment();
        $payment->order_id       = $order->id;
        $payment->customer_id    = $customer_id;
        $payment->payment_method = $this->resolvePaymentMethodLabel($paymentSubMethod);
        $payment->amount         = $paid;
        $payment->payment_status = $order->payment_status;
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
            $order_details->product_id       = $cart->options->product_id ?? $cart->id;
            $order_details->product_name     = $cart->name;
            $order_details->purchase_price   = isset($cart->options->purchase_price) ? $cart->options->purchase_price : 0;
            $order_details->product_discount = isset($cart->options->product_discount) ? $cart->options->product_discount : 0;
            $order_details->sale_price       = $cart->price;
            $order_details->qty              = $cart->qty;
            $order_details->product_size     = $savedSize;
            $order_details->product_color    = $savedColor;

            // 🛡️ Warranty
            if ($cart->options->warranty_tier_id ?? null) {
                $tier = \App\Models\ProductWarrantyTier::find($cart->options->warranty_tier_id);
                if ($tier && $tier->is_active) {
                    $order_details->warranty_tier_id = $tier->id;
                    $order_details->warranty_price   = (float)($tier->additional_cost ?? 0);
                }
            }

            $order_details->save();

            // � SN uniqueness validation
            $serialNumbers = $cart->options->serial_numbers ?? [];
            if (!empty($serialNumbers)) {
                foreach ($serialNumbers as $sn) {
                    $exists = \App\Models\WarrantySale::where('product_id', $order_details->product_id)
                        ->whereJsonContains('serial_numbers', $sn)
                        ->whereIn('status', ['active', 'claimed'])
                        ->exists();
                    if ($exists) {
                        throw new \RuntimeException("Serial number '{$sn}' is already registered for this product.");
                    }
                }
            }

            // 🛡️ Always create/update WarrantySale (for SN tracking even without warranty)
            $warrantyData = [
                'order_id'       => $order->id,
                'customer_id'    => $customer_id,
                'product_id'     => $order_details->product_id,
                'serial_numbers' => $serialNumbers,
                'stock_batch_id' => $cart->options->batch_id ?? null,
                'purchase_id'    => $this->resolvePurchaseId($order_details->product_id, $cart->options->batch_id ?? null),
                'sold_by'        => auth()->id(),
                'warranty_type'  => 'none',
                'status'         => \App\Enums\WarrantySaleStatus::ACTIVE->value,
            ];

            if ($order_details->warranty_tier_id) {
                $tier = \App\Models\ProductWarrantyTier::find($order_details->warranty_tier_id);
                if ($tier && $tier->warranty_days > 0) {
                    $startDate = now();
                    $endDate   = now()->addDays($tier->warranty_days);
                    $supplierWarrantyId = null;

                    if ($tier->warranty_type === 'supplier_warranty') {
                        $sw = \App\Models\SupplierWarranty::where('product_id', $order_details->product_id)
                            ->where('is_transferable', true)
                            ->where('warranty_end_date', '>', now())
                            ->orderBy('warranty_end_date', 'asc')
                            ->first();
                        if ($sw) {
                            $supplierWarrantyId = $sw->id;
                            $endDate = $sw->warranty_end_date;
                        }
                    }

                    $warrantyData = array_merge($warrantyData, [
                        'product_warranty_tier_id' => $tier->id,
                        'supplier_warranty_id'     => $supplierWarrantyId,
                        'warranty_type'            => $tier->warranty_type,
                        'warranty_days'            => $tier->warranty_days,
                        'warranty_start_date'      => $startDate,
                        'warranty_end_date'        => $endDate,
                        'warranty_price'           => (float)($tier->additional_cost ?? 0),
                    ]);
                }
            }

            \App\Models\WarrantySale::updateOrCreate(
                ['order_detail_id' => $order_details->id],
                $warrantyData
            );
        }

        // নতুন অর্ডার প্লেস করলে স্টক কমানো (pass string enum value, NOT cast to int)
        $this->handleStockChange($order, 0, $order->order_status);

        // 💰 Payment received হলে ফান্ডে টাকা যোগ করুন (only the paid amount)
        if ($paid > 0) {
            FundTransaction::create([
                'direction' => 'in',
                'source'    => 'sale',
                'source_id' => $order->id,
                'amount'    => $paid,
                'note'      => 'POS Order #' . $order->invoice_id,
                'created_by'=> auth()->id(),
            ]);
        }

        Cart::instance('pos_shopping')->destroy();
        Session::forget(['pos_shipping', 'pos_discount', 'pos_coupon_code']);

        Toastr::success('Thanks, Your order place successfully', 'Success!');
        // 🆕 Stay on the POS page — show the Sale Complete panel (no page move)
        Session::flash('just_created', $order->invoice_id);
        if ($request->expectsJson()) {
            return response()->json([
                'status'     => 'success',
                'message'    => 'Order placed successfully',
                'invoice_id' => $order->invoice_id,
            ]);
        }
        return redirect()->route('admin.order.create');
    }

    public function cart_add(Request $request)
    {
        $product = Product::select('id', 'name', 'stock', 'new_price', 'old_price', 'purchase_price', 'slug')
            ->where(['id' => $request->id])->first();

        // ✅ Merge only when ALL unconfigured: same product + variant, no warranty, no batch, no SN
        $existing = Cart::instance('pos_shopping')->search(function ($cartItem) use ($product) {
            $itemProductId = $cartItem->options->product_id ?? $cartItem->id;
            $sizeId     = $cartItem->options->size_id ?? null;
            $colorId    = $cartItem->options->color_id ?? null;
            $warrantyId = $cartItem->options->warranty_tier_id ?? null;
            $batchId    = $cartItem->options->batch_id ?? null;
            $sn         = $cartItem->options->serial_numbers ?? [];

            return $itemProductId == $product->id
                && $sizeId === null
                && $colorId === null
                && $warrantyId === null
                && $batchId === null
                && empty($sn);
        })->first();

        if ($existing) {
            $newQty = $existing->qty + 1;
            Cart::instance('pos_shopping')->update($existing->rowId, [
                'qty' => $newQty,
                'options' => $existing->options->toArray(),
            ]);
            $cartinfo = Cart::instance('pos_shopping')->content();
            return response()->json(compact('cartinfo'));
        }

        $qty      = 1;
        $cartinfo = Cart::instance('pos_shopping')->add([
            'id'      => $product->id,
            'name'    => $product->name,
            'qty'     => $qty,
            'price'   => $product->new_price,
            'options' => [
                'product_id'      => $product->id,
                'slug'            => $product->slug,
                'image'           => (isset($product->image) && isset($product->image->image)) ? $product->image->image : null,
                'old_price'       => $product->old_price,
                'purchase_price'  => $product->purchase_price,
                'product_size'    => null,
                'product_color'   => null,
                'size_id'         => null,
                'color_id'        => null,
                'product_discount'=> 0,
                'warranty_tier_id'=> null,
                'warranty_adjustment' => 0,
                'base_price'      => $product->new_price,
                'batch_id'        => null,
                'serial_numbers'  => [],
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
            // Customer-facing note: keep on orders table
            if (Schema::hasColumn('orders', 'order_note')) {
                $order->order_note = $request->note;
            } else {
                $order->note = $request->note;
            }
            $order->save();
        } else {
            // Admin note: use OrderNote model for history
            if (!empty($request->note)) {
                $order->addNote(
                    content: $request->note,
                    type: 'info',
                    source: 'admin',
                    userId: auth()->id()
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'note'   => $request->note,
        ]);
    }

    public function cart_content()
    {
        $cartinfo = Cart::instance('pos_shopping')->content();

        // ✅ Recalculate per-item product_discount session on every cart refresh
        $productDisc = 0;
        foreach ($cartinfo as $item) {
            $productDisc += (float)($item->options->product_discount ?? 0) * $item->qty;
        }
        Session::put('product_discount', $productDisc);

        return view('backEnd.order.cart_content', compact('cartinfo'));
    }

    public function cart_details()
    {
        $cartinfo = Cart::instance('pos_shopping')->content();

        // ✅ Recalculate per-item product_discount session
        $productDisc = 0;
        foreach ($cartinfo as $item) {
            $productDisc += (float)($item->options->product_discount ?? 0) * $item->qty;
        }
        Session::put('product_discount', $productDisc);

        return view('backEnd.order.cart_details', compact('cartinfo'));
    }

    /**
     * Single AJAX endpoint — returns both cart table + details in one response.
     */
    public function cart_refresh()
    {
        $cartinfo = Cart::instance('pos_shopping')->content()
            ->sortBy(fn($item) => ($item->options->details_id ?? $item->id));

        $productDisc = 0;
        foreach ($cartinfo as $item) {
            $productDisc += (float)($item->options->product_discount ?? 0) * $item->qty;
        }
        Session::put('product_discount', $productDisc);

        return response()->json([
            'cart_html'    => view('backEnd.order.cart_table_rows', compact('cartinfo'))->render(),
            'details_html' => view('backEnd.order.cart_details', compact('cartinfo'))->render(),
        ]);
    }

    public function cart_increment(Request $request)
    {
        $qty  = $request->qty + 1;
        $cart = Cart::instance('pos_shopping')->content()->where('rowId', $request->id)->first();

        $cartinfo = Cart::instance('pos_shopping')->update($request->id, [
            'qty'     => $qty,
            'options' => [
                'product_id'      => $cart->options->product_id ?? $cart->id,
                'slug'            => $cart->options->slug,
                'image'           => $cart->options->image,
                'old_price'       => $cart->options->old_price,
                'purchase_price'  => $cart->options->purchase_price,
                'product_size'    => $cart->options->product_size,
                'product_color'   => $cart->options->product_color,
                'size_id'         => $cart->options->size_id ?? null,
                'color_id'        => $cart->options->color_id ?? null,
                'product_discount'=> $cart->options->product_discount ?? 0,
                'warranty_tier_id'=> $cart->options->warranty_tier_id ?? null,
                'warranty_adjustment' => $cart->options->warranty_adjustment ?? 0,
                'base_price'      => $cart->options->base_price ?? $cart->price,
                'batch_id'        => $cart->options->batch_id ?? null,
                'serial_numbers'  => $cart->options->serial_numbers ?? [],
                'details_id'      => $cart->options->details_id ?? null,
                '_unique_key'     => $cart->options->_unique_key ?? null,
                'product_color_name' => $cart->options->product_color_name ?? null,
                'product_size_name'  => $cart->options->product_size_name ?? null,
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
                'product_id'      => $cart->options->product_id ?? $cart->id,
                'slug'            => $cart->options->slug,
                'image'           => $cart->options->image,
                'old_price'       => $cart->options->old_price,
                'purchase_price'  => $cart->options->purchase_price,
                'product_size'    => $cart->options->product_size,
                'product_color'   => $cart->options->product_color,
                'size_id'         => $cart->options->size_id ?? null,
                'color_id'        => $cart->options->color_id ?? null,
                'product_discount'=> $cart->options->product_discount ?? 0,
                'warranty_tier_id'=> $cart->options->warranty_tier_id ?? null,
                'warranty_adjustment' => $cart->options->warranty_adjustment ?? 0,
                'base_price'      => $cart->options->base_price ?? $cart->price,
                'batch_id'        => $cart->options->batch_id ?? null,
                'serial_numbers'  => $cart->options->serial_numbers ?? [],
                'details_id'      => $cart->options->details_id ?? null,
                '_unique_key'     => $cart->options->_unique_key ?? null,
                'product_color_name' => $cart->options->product_color_name ?? null,
                'product_size_name'  => $cart->options->product_size_name ?? null,
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
                'product_id'      => $cart->options->product_id ?? $cart->id,
                'slug'            => $cart->options->slug,
                'image'           => $cart->options->image,
                'old_price'       => $cart->options->old_price,
                'purchase_price'  => $cart->options->purchase_price,
                'product_discount'=> $request->discount,
                'product_size'    => $cart->options->product_size,
                'product_color'   => $cart->options->product_color,
                'size_id'         => $cart->options->size_id ?? null,
                'color_id'        => $cart->options->color_id ?? null,
                'warranty_tier_id'=> $cart->options->warranty_tier_id ?? null,
                'warranty_adjustment' => $cart->options->warranty_adjustment ?? 0,
                'base_price'      => $cart->options->base_price ?? $cart->price,
                'batch_id'        => $cart->options->batch_id ?? null,
                'serial_numbers'  => $cart->options->serial_numbers ?? [],
                'details_id'      => $cart->options->details_id ?? null,
                '_unique_key'     => $cart->options->_unique_key ?? null,
                'product_color_name' => $cart->options->product_color_name ?? null,
                'product_size_name'  => $cart->options->product_size_name ?? null,
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

        // rowId stale after Cart::update? Find by product + full criteria match
        if (!$cartItem && $request->product_id) {
            $warrantyId = $request->warranty_tier_id ?? null;
            $batchId    = $request->batch_id ?? null;
            $sn         = $request->serial_numbers ?? null;

            $cartItem = Cart::instance('pos_shopping')->content()
                ->filter(function ($item) use ($request, $warrantyId, $batchId, $sn) {
                    $itemProductId = $item->options->product_id ?? $item->id;
                    if ($itemProductId != $request->product_id) return false;
                    // Match warranty, batch, SN to find the right row (not just first)
                    if ($warrantyId && ($item->options->warranty_tier_id ?? null) != $warrantyId) return false;
                    if ($batchId && ($item->options->batch_id ?? null) != $batchId) return false;
                    if ($sn && ($item->options->serial_numbers ?? []) != array_filter(array_map('trim', explode(',', $sn)))) return false;
                    return true;
                })
                ->first();

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

        $pid = $cartItem->options->product_id ?? $cartItem->id;
        $product = Product::find($pid);
        // ✅ Start from current price (base + warranty) — recalculate only when size/color/warranty changes
        $newPrice = (float)(($cartItem->options->base_price ?? $cartItem->price) + ($cartItem->options->warranty_adjustment ?? 0));
        $sizeName = null;
        $colorName = null;

        // Only recalculate price when size or color is explicitly being changed
        $isVariantChange = $request->has('size_id') || $request->has('color_id') || $request->has('product_size') || $request->has('product_color');

        if ($product && $isVariantChange) {
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
            'product_id'      => $cartItem->options->product_id ?? $cartItem->id,
            'product_size'    => $sizeName ?? $cartItem->options->product_size,
            'product_color'   => $colorName ?? $cartItem->options->product_color,
            'product_color_name' => $cartItem->options->product_color_name ?? null,
            'product_size_name'  => $cartItem->options->product_size_name ?? null,
            'size_id'         => $sizeId,
            'color_id'        => $colorId,
            'slug'            => $cartItem->options->slug,
            'image'           => $cartItem->options->image,
            'old_price'       => $cartItem->options->old_price,
            'purchase_price'  => $cartItem->options->purchase_price,
            'product_discount'=> $cartItem->options->product_discount ?? 0,
            'details_id'      => $cartItem->options->details_id ?? null,
            '_unique_key'     => $cartItem->options->_unique_key ?? null,  // ✅ Preserve unique key
            'warranty_tier_id' => $request->warranty_tier_id ?? $cartItem->options->warranty_tier_id ?? null,
            'batch_id'        => $request->batch_id ?? $cartItem->options->batch_id ?? null,
            'serial_numbers'  => $request->has('serial_numbers') 
                ? array_filter(array_map('trim', explode(',', $request->serial_numbers))) 
                : ($cartItem->options->serial_numbers ?? []),
        ];

        // 🛡️ Apply warranty adjustment — recalculate from product base price
        $effectiveWarrantyId = $request->warranty_tier_id ?? $cartItem->options->warranty_tier_id ?? null;
        $warrantyAdjustment = (float)($cartItem->options->warranty_adjustment ?? 0);
        if ($request->filled('warranty_tier_id')) {
            $tier = \App\Models\ProductWarrantyTier::find($request->warranty_tier_id);
            if ($tier && $tier->is_active) {
                $warrantyAdjustment = (float) ($tier->additional_cost ?? 0);
                $productBasePrice = $product->new_price ?? $product->old_price ?? $cartItem->price;
                $newPrice = $productBasePrice + $warrantyAdjustment;
            } else {
                $warrantyAdjustment = 0;
            }
        }
        $options['warranty_adjustment'] = $warrantyAdjustment;
        $options['base_price']          = $newPrice - $warrantyAdjustment;

        $updatedItem = Cart::instance('pos_shopping')->update($rowId, ['price' => $newPrice, 'options' => $options]);

        Log::channel('single')->info('[POS cart_update] Saved', [
            'rowId' => $updatedItem ? $updatedItem->rowId : $rowId,
            'sizeId' => $sizeId,
            'colorId' => $colorId,
            'sizeName' => $sizeName,
            'colorName' => $colorName,
            'batch_id' => $options['batch_id'] ?? null,
            'warranty_tier_id' => $options['warranty_tier_id'] ?? null,
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

        // Compare dates as Carbon (valid_from/valid_to are cast to date), not as
        // string vs Carbon — otherwise a coupon valid_from = today is wrongly rejected.
        $now = Carbon::now()->startOfDay();
        if (
            ($coupon->valid_from && $coupon->valid_from->startOfDay()->gt($now)) ||
            ($coupon->valid_to && $coupon->valid_to->startOfDay()->lt($now))
        ) {
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
        $paymentGateways = PaymentGateway::where('status', 1)->orderBy('type')->get();

        $this->buildCartFromOrder($order);

        $cartinfo     = Cart::instance('pos_shopping')->content();
        $shippinginfo = Shipping::where('order_id', $order->id)->first();

        return view('backEnd.order.edit', compact(
            'products',
            'cartinfo',
            'shippingcharge',
            'shippinginfo',
            'order',
            'paymentGateways'
        ));
    }

    /**
     * Load an existing order's items into the POS cart (used by edit + AJAX load).
     */
    protected function buildCartFromOrder(Order $order): void
    {
        Cart::instance('pos_shopping')->destroy();

        $shippinginfo = Shipping::where('order_id', $order->id)->first();
        Session::put('product_discount', $order->discount);
        Session::put('pos_shipping', $order->shipping_charge);

        $orderdetails = OrderDetails::where('order_id', $order->id)
            ->with(['image', 'color', 'size', 'warrantySale'])
            ->get();

        foreach ($orderdetails as $ordetails) {
            // 🛡️ Resolve warranty tier from saved order detail
            $warrantyTierId = $ordetails->warranty_tier_id ?? null;
            $tier = $warrantyTierId ? \App\Models\ProductWarrantyTier::find($warrantyTierId) : null;
            $warrantyAdjustment = ($tier && $tier->is_active) ? (float)($tier->additional_cost ?? 0) : 0;
            // ✅ sale_price is the actual price paid. Use as-is for display.
            $actualPrice = $ordetails->sale_price;

            // 📦 Resolve batch from saved batch_ids (may be scalar IDs OR batch_details arrays from stockOut)
            $batchId = null;
            if (!empty($ordetails->batch_ids)) {
                $batchIds = is_array($ordetails->batch_ids) ? $ordetails->batch_ids : json_decode($ordetails->batch_ids, true);
                $first = is_array($batchIds) ? reset($batchIds) : $batchIds;
                $batchId = is_array($first) ? ($first['batch_id'] ?? null) : $first;
            }

            // 🎨 Resolve size_id / color_id from saved values
            $sizeId = null;
            $colorId = null;
            if (is_numeric($ordetails->product_size)) {
                $sizeId = $ordetails->product_size;
            }
            if (is_numeric($ordetails->product_color)) {
                $colorId = $ordetails->product_color;
            }

            Cart::instance('pos_shopping')->add([
                'id'      => $ordetails->id,           // ✅ Use OrderDetail ID for unique cart rowId
                'name'    => $ordetails->product_name,
                'qty'     => $ordetails->qty,
                'price'   => $actualPrice,
                'options' => [
                    'product_id'        => $ordetails->product_id,
                    'image'             => (isset($ordetails->image) && isset($ordetails->image->image) ? $ordetails->image->image : 'public/no-image.png'),
                    'purchase_price'    => $ordetails->purchase_price,
                    'product_discount'  => $ordetails->product_discount,
                    'details_id'        => $ordetails->id,
                    'product_color'     => $ordetails->product_color,
                    'product_size'      => $ordetails->product_size,
                    'product_color_name'=> isset($ordetails->color->name) ? $ordetails->color->name : (isset($ordetails->product_color) ? $ordetails->product_color : 'N/A'),
                    'product_size_name' => isset($ordetails->size->name) ? $ordetails->size->name : (isset($ordetails->product_size) ? $ordetails->product_size : 'N/A'),
                    'size_id'           => $sizeId,
                    'color_id'          => $colorId,
                    'warranty_tier_id'  => $warrantyTierId,
                    'warranty_adjustment' => $warrantyAdjustment,
                    'base_price'        => $actualPrice - $warrantyAdjustment,
                    'batch_id'          => $batchId,
                    'serial_numbers'    => optional($ordetails->warrantySale)->serial_numbers ?? [],
                ],
            ]);
        }
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

        // 🛡️ Calculate warranty charges from POS cart
        $warrantyCharge = 0;
        foreach (Cart::instance('pos_shopping')->content() as $item) {
            $warrantyCharge += (float)($item->options->warranty_adjustment ?? 0) * $item->qty;
        }

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
        // ✅ Cart::subtotal() already includes warranty_adjustment in price, so don't add $warrantyCharge again
        $order->amount          = ($subtotal + (isset($shippingfee->amount) ? $shippingfee->amount : 0)) - $discount;
        $order->discount        = isset($discount) ? $discount : 0;
        $order->shipping_charge = isset($shippingfee->amount) ? $shippingfee->amount : 0;
        $order->customer_id     = $customer->id;
        $oldOrderStatus         = $order->order_status;
        $paymentGatewayInput    = strtolower(trim((string) $request->input('payment_gateway', 'cod')));
        $paymentStatusInput     = strtolower(trim((string) $request->input('payment_status', 'pending')));
        $order->order_type      = $paymentGatewayInput === 'cod' ? 'cod' : 'pos';
        if (in_array($paymentStatusInput, ['paid', 'completed', 'success', 'approved'], true)) {
            $order->order_status = 'completed';
        }
        $order->note            = $request->note;

        // 💰 Recompute paid / due on the new total (keep existing paid unless new payment given)
        $total = (float) $order->amount;
        $alreadyPaid = (float) ($order->paid_amount ?? 0);
        $newPaid = min((float) ($request->paid_amount ?? 0), max(0, $total - $alreadyPaid));
        $order->paid_amount = $alreadyPaid + $newPaid;
        $order->due_amount  = max(0, $total - $order->paid_amount);
        $order->payment_status = $order->due_amount <= 0
            ? ($order->paid_amount > 0 ? 'paid' : 'pending')
            : ($order->paid_amount > 0 ? 'partial' : 'pending');
        $order->save();

        log_activity('order', 'update', 'Updated order #' . $order->invoice_id . ' — status ' . $oldOrderStatus . ' → ' . $order->order_status, $order, [
            'old_status'     => $oldOrderStatus,
            'new_status'     => $order->order_status,
            'amount'         => $order->amount,
            'payment_status' => $order->payment_status,
        ]);

        $shipping           = Shipping::where('order_id', $order->id)->firstOrFail();
        $shipping->name     = $request->name;
        $shipping->phone    = $request->phone;
        $shipping->address  = $request->address;
        $shipping->area     = isset($shippingfee->name) ? $shippingfee->name : ($request->area == '0' ? 'Store Pickup' : $shipping->area);
        $shipping->save();

        // 🆕 Record new partial payment in history ledger
        if ($newPaid > 0) {
            OrderPayment::create([
                'order_id'       => $order->id,
                'customer_id'    => $customer->id,
                'amount'         => $newPaid,
                'payment_method' => $this->resolvePaymentMethodLabel($paymentGatewayInput),
                'trx_note'       => $request->payment_note ?: null,
                'created_by'     => auth()->id(),
            ]);
        }

        $payment                 = Payment::where('order_id', $order->id)->firstOrNew(['order_id' => $order->id]);
        $payment->customer_id    = $customer->id;
        $payment->payment_method = $this->resolvePaymentMethodLabel($paymentGatewayInput);
        $payment->amount         = $order->paid_amount;
        $payment->payment_status = $order->payment_status;
        $payment->save();

        if ($newPaid > 0) {
            $exists = FundTransaction::where('source', 'sale')->where('source_id', $order->id)
                ->where('amount', $newPaid)->exists();
            if (!$exists) {
                FundTransaction::create([
                    'direction'  => 'in',
                    'source'     => 'sale',
                    'source_id'  => $order->id,
                    'amount'     => $newPaid,
                    'note'       => 'Payment received — Order #' . $order->invoice_id,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        $existingDetails = OrderDetails::where('order_id', $order->id)->pluck('id')->toArray();
        $updatedIds      = [];
        $batchAdjustments = [];

        // 📦 Snapshot old batch + qty per detail — used to re-adjust stock when batch changes
        $oldDetailData = [];
        foreach ($existingDetails as $did) {
            $d = OrderDetails::find($did);
            if ($d) {
                $oldDetailData[$did] = [
                    'qty'       => (int) $d->qty,
                    'batch_ids' => $d->batch_ids ?: [],
                ];
            }
        }

        foreach (Cart::instance('pos_shopping')->content() as $cart) {
            $detailsId = $cart->options->details_id ?? null;
            // Old batch/qty for this cart line (if it existed before this update)
            $oldDetailRow = $oldDetailData[$detailsId] ?? null;

            if (!empty($detailsId) && in_array($detailsId, $existingDetails)) {
                $detail = OrderDetails::find($detailsId);
            } else {
                $detail              = new OrderDetails();
                $detail->order_id    = $order->id;
                $detail->product_id  = $cart->options->product_id ?? $cart->id;
                $detail->product_name= $cart->name;
            }

            $detail->purchase_price   = isset($cart->options->purchase_price) ? $cart->options->purchase_price : 0;
            $detail->product_discount = isset($cart->options->product_discount) ? $cart->options->product_discount : 0;
            $detail->product_color    = isset($cart->options->product_color) ? $cart->options->product_color : null;
            $detail->product_size     = isset($cart->options->product_size) ? $cart->options->product_size : null;
            $detail->sale_price       = $cart->price;
            $detail->qty              = $cart->qty;

            // 🛡️ Warranty
            if ($cart->options->warranty_tier_id ?? null) {
                $tier = \App\Models\ProductWarrantyTier::find($cart->options->warranty_tier_id);
                $detail->warranty_tier_id = $tier->id ?? null;
                $detail->warranty_price   = $tier ? (float)($tier->additional_cost ?? 0) : 0;
            } else {
                $detail->warranty_tier_id = null;
                $detail->warranty_price   = 0;
            }

            // 📦 Save batch_ids for re-opening in edit
            // NOTE: batch_ids has an `array` cast — assign the array directly,
            // never json_encode() (that double-encodes and breaks the cast).
            $detail->batch_ids = $cart->options->batch_id ? [$cart->options->batch_id] : null;

            $detail->save();

            // � SN uniqueness validation (skip for existing details — updating their own SN is fine)
            $serialNumbers = $cart->options->serial_numbers ?? [];
            if (!empty($serialNumbers) && empty($detail->id)) {
                foreach ($serialNumbers as $sn) {
                    $exists = \App\Models\WarrantySale::where('product_id', $detail->product_id)
                        ->whereJsonContains('serial_numbers', $sn)
                        ->whereIn('status', ['active', 'claimed'])
                        ->exists();
                    if ($exists) {
                        throw new \RuntimeException("Serial number '{$sn}' is already registered for this product.");
                    }
                }
            }

            // 🛡️ Always create/update WarrantySale (for SN tracking even without warranty)
            $warrantyData = [
                'order_id'       => $order->id,
                'customer_id'    => $order->customer_id,
                'product_id'     => $detail->product_id,
                'serial_numbers' => $serialNumbers,
                'stock_batch_id' => $cart->options->batch_id ?? null,
                'purchase_id'    => $this->resolvePurchaseId($detail->product_id, $cart->options->batch_id ?? null),
                'sold_by'        => auth()->id(),
                'warranty_type'  => 'none',
                'status'         => \App\Enums\WarrantySaleStatus::ACTIVE->value,
            ];

            if ($detail->warranty_tier_id) {
                $tier = \App\Models\ProductWarrantyTier::find($detail->warranty_tier_id);
                if ($tier && $tier->warranty_days > 0) {
                    $startDate = now();
                    $endDate   = now()->addDays($tier->warranty_days);
                    $supplierWarrantyId = null;

                    if ($tier->warranty_type === 'supplier_warranty') {
                        $sw = \App\Models\SupplierWarranty::where('product_id', $detail->product_id)
                            ->where('is_transferable', true)
                            ->where('warranty_end_date', '>', now())
                            ->orderBy('warranty_end_date', 'asc')
                            ->first();
                        if ($sw) {
                            $supplierWarrantyId = $sw->id;
                            $endDate = $sw->warranty_end_date;
                        }
                    }

                    $warrantyData = array_merge($warrantyData, [
                        'product_warranty_tier_id' => $tier->id,
                        'supplier_warranty_id'     => $supplierWarrantyId,
                        'warranty_type'            => $tier->warranty_type,
                        'warranty_days'            => $tier->warranty_days,
                        'warranty_start_date'      => $startDate,
                        'warranty_end_date'        => $endDate,
                        'warranty_price'           => (float)($tier->additional_cost ?? 0),
                    ]);
                }
            }

            \App\Models\WarrantySale::updateOrCreate(
                ['order_detail_id' => $detail->id],
                $warrantyData
            );

            $updatedIds[] = $detail->id;

            // 🆕 Track batch change for stock re-allocation (only when status is unchanged,
            // i.e. stock was already consumed and needs to move to the newly selected batch).
            if ($oldDetailRow && $oldOrderStatus === $order->order_status) {
                $batchAdjustments[] = [
                    'detail'        => $detail,
                    'old_batch_ids' => $oldDetailRow['batch_ids'] ?? null,
                    'old_qty'       => (int) ($oldDetailRow['qty'] ?? $detail->qty),
                    'new_batch_id'  => $cart->options->batch_id ?? null,
                ];
            }
        }

        if ($oldOrderStatus !== $order->order_status) {
            $this->handleStockChange($order, $oldOrderStatus, $order->order_status);
        }

        // 🆕 Status unchanged → but user may have changed batch/qty. Move the stock
        // allocation to the newly selected batch (restore old batch, deduct new batch).
        if ($oldOrderStatus === $order->order_status) {
            $this->reAdjustStockForBatchChange($order, $batchAdjustments);

            // Items removed from the order → restore their old batch stock
            foreach (array_diff($existingDetails, $updatedIds) as $removedId) {
                $oldRow = $oldDetailData[$removedId] ?? null;
                if (!$oldRow) {
                    continue;
                }
                $oldQty = max(1, (int) ($oldRow['qty'] ?? 1));
                foreach ($this->normalizeBatchEntries($oldRow['batch_ids'] ?? [], $oldQty) as $e) {
                    \App\Models\StockBatch::where('id', $e['batch_id'])
                        ->increment('remaining_qty', max(1, $e['qty']));
                }
            }
        }

        OrderDetails::where('order_id', $order->id)
            ->whereNotIn('id', $updatedIds)
            ->delete();

        Cart::instance('pos_shopping')->destroy();
        Session::forget(['pos_shipping', 'pos_discount', 'product_discount']);

        Toastr::success('Order updated successfully!', 'Success!');
        // 🆕 Stay on the POS page (no page move)
        Session::flash('just_updated', $order->invoice_id);
        if ($request->expectsJson()) {
            return response()->json([
                'status'     => 'success',
                'message'    => 'Order updated successfully',
                'invoice_id' => $order->invoice_id,
            ]);
        }
        return redirect()->route('admin.order.create');
    }

    protected function resolvePaymentMethodLabel(string $gateway): string
    {
        $gateway = trim(strtolower($gateway));

        if ($gateway === '' || in_array($gateway, ['cod', 'cash on delivery', 'cash_on_delivery'], true)) {
            return 'Cash On Delivery';
        }

        return ucwords(str_replace(['-', '_'], ' ', $gateway));
    }

    /**
     * Resolve the purchase_id from a stock batch.
     */
    protected function resolvePurchaseId($productId, $batchId): ?int
    {
        if ($batchId) {
            $batch = \App\Models\StockBatch::find($batchId);
            $pid = $batch?->purchase_id;
            if ($pid && \App\Models\Purchase::where('id', $pid)->exists()) {
                return $pid;
            }
        }
        // Fallback: find the most recent purchase that includes this product
        $purchaseItem = \App\Models\PurchaseItem::where('product_id', $productId)
            ->latest()
            ->first();
        $pid = $purchaseItem?->purchase_id;
        if ($pid && \App\Models\Purchase::where('id', $pid)->exists()) {
            return $pid;
        }
        return null;
    }

    /**
     * Normalize order-detail batch_ids (stored as batch_details arrays,
     * scalar ID arrays, or a JSON string) into a list of
     * ['batch_id' => int, 'qty' => int] entries.
     */
    protected function normalizeBatchEntries($raw, int $fallbackQty = 0): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        $entries = [];
        foreach ((array) $raw as $entry) {
            if (is_array($entry)) {
                $entries[] = [
                    'batch_id' => (int) ($entry['batch_id'] ?? 0),
                    'qty'      => (int) ($entry['qty'] ?? $fallbackQty),
                ];
            } elseif (is_numeric($entry)) {
                $entries[] = [
                    'batch_id' => (int) $entry,
                    'qty'      => $fallbackQty,
                ];
            }
        }

        return array_values(array_filter($entries, fn($e) => $e['batch_id'] > 0));
    }

    /**
     * When an order is updated WITHOUT a status change (stock already consumed),
     * re-allocate the deducted stock to the newly selected batch.
     *
     * Each adjustment carries the freshly-saved detail plus the OLD batch data,
     * so this works even when the update creates a brand-new OrderDetails row.
     *
     * - Restores stock to the old batch(es) the line previously drew from.
     * - Deducts from the newly selected batch (or auto-selection when "Auto").
     *
     * @param Order $order
     * @param array $adjustments  [[detail, old_batch_ids, old_qty, new_batch_id], ...]
     */
    protected function reAdjustStockForBatchChange(Order $order, array $adjustments): void
    {
        $newEnum = OrderStatusEnum::tryFrom($order->order_status);
        if (!$newEnum || !$newEnum->consumesStock()) {
            return;
        }

        /** @var StockManagementService $stockService */
        $stockService = app(StockManagementService::class);

        foreach ($adjustments as $adj) {
            $detail = $adj['detail'] ?? null;
            if (!$detail || !$detail->product) {
                continue;
            }

            $oldQty = max(1, (int) ($adj['old_qty'] ?? $detail->qty));
            $oldEntries = $this->normalizeBatchEntries($adj['old_batch_ids'] ?? [], $oldQty);
            $newBatchId = $adj['new_batch_id'] ? (int) $adj['new_batch_id'] : null;
            $qty = max(1, (int) $detail->qty);

            $oldBatchIds = array_column($oldEntries, 'batch_id');

            // Skip when the batch did not change
            if ($oldBatchIds === ($newBatchId ? [$newBatchId] : [])) {
                continue;
            }

            // 1) Restore stock to the old batch(es)
            foreach ($oldEntries as $e) {
                \App\Models\StockBatch::where('id', $e['batch_id'])
                    ->increment('remaining_qty', max(1, $e['qty']));
            }

            // 2) Deduct from the new batch (or auto-select when null)
            try {
                $result = $stockService->stockOut($detail->product, $qty, [
                    'type' => 'sale',
                    'id'   => $order->id,
                ], $newBatchId);

                $detail->update([
                    'cogs'      => $result['cogs'],
                    'batch_ids' => $result['batch_details'],
                ]);
            } catch (\RuntimeException $e) {
                Log::warning('Batch re-adjust failed, used fallback', [
                    'product' => $detail->product_id,
                    'order'   => $order->id,
                    'error'   => $e->getMessage(),
                ]);
                $detail->product->decrement('stock', $qty);
            }

            // 3) Sync warranty sale batch + purchase
            \App\Models\WarrantySale::where('order_detail_id', $detail->id)->update([
                'stock_batch_id' => $newBatchId,
                'purchase_id'    => $this->resolvePurchaseId($detail->product_id, $newBatchId),
            ]);
        }
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

        // 💰 ফান্ড আপডেট — পেমেন্ট পেইড হলে ফান্ডে যোগ, আনপেইড/ফেইল হলে কোন পরিবর্তন নয়
        $paidStatuses = ['paid', 'completed', 'success', 'approved'];
        $refundStatuses = ['refunded', 'refund', 'returned'];
        
        if (in_array(strtolower($request->payment_status), $paidStatuses)) {
            $exists = FundTransaction::where('source', 'sale')->where('source_id', $order->id)->exists();
            if (!$exists) {
                FundTransaction::create([
                    'direction'  => 'in',
                    'source'     => 'sale',
                    'source_id'  => $order->id,
                    'amount'     => $order->amount,
                    'note'       => 'Payment received — Order #' . $order->invoice_id,
                    'created_by' => auth()->id(),
                ]);
            }
        } elseif (in_array(strtolower($request->payment_status), $refundStatuses)) {
            $exists = FundTransaction::where('source', 'refund')->where('source_id', $order->id)->exists();
            if (!$exists) {
                FundTransaction::create([
                    'direction'  => 'out',
                    'source'     => 'refund',
                    'source_id'  => $order->id,
                    'amount'     => $order->amount,
                    'note'       => 'Refund processed — Order #' . $order->invoice_id,
                    'created_by' => auth()->id(),
                ]);
            }
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

    // ═══════════════════════════════════════════════════════════
    // 🌟 NEW: Action-Based Order Management (System-Driven)
    // Each action automatically transitions status + records note
    // ═══════════════════════════════════════════════════════════

    /**
     * Add an admin note to an order (without status change).
     */
    public function addOrderNote(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'required|string|min:1',
            'type'     => 'nullable|in:info,warning,success,danger',
        ]);

        $order = Order::findOrFail($request->order_id);
        
        $note = $order->addNote(
            content: $request->note,
            type: $request->type ?? 'info',
            source: 'admin',
            userId: auth()->id()
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Note added successfully',
            'note'    => [
                'id'         => $note->id,
                'content'    => $note->content,
                'type'       => $note->type,
                'user_name'  => auth()->user()->name ?? 'System',
                'created_at' => $note->created_at->format('d M Y, h:i A'),
                'created_at_diff' => $note->created_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Confirm order (Pending → Confirmed).
     */
    public function confirmOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $userId = auth()->id();

        // Handle stock: entering active status
        $oldStatus = $order->order_status;
        $success = $order->confirm($request->note, $userId);

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot confirm this order. Invalid status transition.',
            ], 422);
        }

        $this->handleStockChange($order, $oldStatus, $order->getOriginal('order_status'));

        return response()->json([
            'status'      => 'success',
            'message'     => 'Order confirmed successfully',
            'new_status'  => $order->order_status,
            'status_label'=> OrderStatusEnum::tryFrom($order->order_status)?->label() ?? $order->order_status,
            'badge_class' => OrderStatusEnum::tryFrom($order->order_status)?->badgeClass() ?? 'secondary',
            'actions'     => $order->getAvailableActions(),
        ]);
    }

    /**
     * Start picking (Confirmed → Picking).
     */
    public function startPicking(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->startPicking($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot start picking. Invalid status transition.',
            ], 422);
        }

        return $this->actionSuccessResponse($order, 'Picking started');
    }

    /**
     * Start packing (Picking → Packing).
     */
    public function startPacking(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->startPacking($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot start packing. Invalid status transition.',
            ], 422);
        }

        return $this->actionSuccessResponse($order, 'Packing started');
    }

    /**
     * Mark as packed (Packing → Packed).
     */
    public function markPacked(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->markPacked($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot mark as packed. Invalid status transition.',
            ], 422);
        }

        return $this->actionSuccessResponse($order, 'Order packed');
    }

    /**
     * Ship order (Packed → Shipped).
     */
    public function shipOrder(Request $request)
    {
        $request->validate([
            'order_id'        => 'required|integer|exists:orders,id',
            'note'            => 'nullable|string',
            'courier_type'    => 'nullable|string|max:255',
            'courier_tracking_id' => 'nullable|string|max:255',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->ship(
            courierType: $request->courier_type,
            trackingId: $request->courier_tracking_id,
            note: $request->note,
            userId: auth()->id()
        );

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot ship this order. Invalid status transition.',
            ], 422);
        }

        return $this->actionSuccessResponse($order, 'Order shipped');
    }

    /**
     * Mark out for delivery (Shipped → Out for Delivery).
     */
    public function markOutForDelivery(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->markOutForDelivery($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot mark out for delivery. Invalid status transition.',
            ], 422);
        }

        return $this->actionSuccessResponse($order, 'Out for delivery');
    }

    /**
     * Mark as delivered (Out for Delivery → Delivered).
     */
    public function markDelivered(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->markDelivered($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot mark as delivered. Invalid status transition.',
            ], 422);
        }

        // COD: auto-update payment to paid on delivery
        if ($order->isCodOrder() && $order->payment_status === 'pending') {
            $order->payment_status = 'paid';
            $order->save();

            $payment = Payment::where('order_id', $order->id)->first();
            if ($payment) {
                $payment->payment_status = 'paid';
                $payment->save();
            }

            // Fund transaction
            $exists = FundTransaction::where('source', 'sale')->where('source_id', $order->id)->exists();
            if (!$exists) {
                FundTransaction::create([
                    'direction'  => 'in',
                    'source'     => 'sale',
                    'source_id'  => $order->id,
                    'amount'     => $order->amount,
                    'note'       => 'COD payment received — Order #' . $order->invoice_id,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        return $this->actionSuccessResponse($order, 'Order delivered');
    }

    /**
     * Complete order (Delivered → Completed).
     */
    public function completeOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->markCompleted($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot complete this order. Invalid status transition.',
            ], 422);
        }

        // Fund transaction on completion
        $exists = FundTransaction::where('source', 'sale')->where('source_id', $order->id)->exists();
        if (!$exists) {
            FundTransaction::create([
                'direction'  => 'in',
                'source'     => 'sale',
                'source_id'  => $order->id,
                'amount'     => $order->amount,
                'note'       => 'Order complete (#' . $order->invoice_id . ')',
                'created_by' => auth()->id(),
            ]);
        }

        return $this->actionSuccessResponse($order, 'Order completed');
    }

    /**
     * Request return (Delivered/Completed → Return Requested).
     */
    public function requestReturn(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->requestReturn($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot request return. Invalid status transition.',
            ], 422);
        }

        return $this->actionSuccessResponse($order, 'Return requested');
    }

    /**
     * Approve return (Return Requested → Return Approved).
     */
    public function approveReturn(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->approveReturn($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot approve return. Invalid status transition.',
            ], 422);
        }

        return $this->actionSuccessResponse($order, 'Return approved');
    }

    /**
     * Mark item returned (Return Approved → Returned).
     */
    public function markReturned(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->markReturned($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot mark as returned. Invalid status transition.',
            ], 422);
        }

        return $this->actionSuccessResponse($order, 'Item returned');
    }

    /**
     * Close order (Returned → Closed).
     */
    public function closeOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $success = $order->closeOrder($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot close this order. Invalid status transition.',
            ], 422);
        }

        return $this->actionSuccessResponse($order, 'Order closed');
    }

    /**
     * Cancel order.
     */
    public function cancelOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'note'     => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $oldStatus = $order->order_status;
        $success = $order->cancel($request->note, auth()->id());

        if (!$success) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot cancel this order. It may already be shipped or completed.',
            ], 422);
        }

        // Restore stock on cancellation
        $this->handleStockChange($order, $oldStatus, OrderStatusEnum::CANCELLED->value);

        return $this->actionSuccessResponse($order, 'Order cancelled');
    }

    /**
     * Standardized success response for action methods.
     */
    private function actionSuccessResponse(Order $order, string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status'       => 'success',
            'message'      => $message . ' successfully',
            'new_status'   => $order->order_status,
            'status_label' => OrderStatusEnum::tryFrom($order->order_status)?->label() ?? $order->order_status,
            'badge_class'  => OrderStatusEnum::tryFrom($order->order_status)?->badgeClass() ?? 'secondary',
            'actions'      => $order->getAvailableActions(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | BARCODE SCANNING
    |--------------------------------------------------------------------------
    */
    public function scanBarcode($barcode)
    {
        $barcode = trim($barcode);

        // Try to find by product barcode OR product code (SKU)
        $product = Product::where(function ($q) use ($barcode) {
            $q->where('barcode', $barcode)
              ->orWhere('product_code', $barcode);
        })->first();

        // If not found, try variant barcode OR variant SKU
        if (!$product) {
            $variant = ProductVariantPrice::where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)
                  ->orWhere('sku', $barcode);
            })->with('product')->first();
            if ($variant && $variant->product) {
                $product = $variant->product;
            }
        }

        if (!$product) {
            return response()->json(['error' => 'Product not found for barcode/code: ' . $barcode], 404);
        }

        // Add to cart (same logic as clicking a product card)
        $qty = 1;
        $cartinfo = Cart::instance('pos_shopping')->add([
            'id'      => $product->id,
            'name'    => $product->name,
            'qty'     => $qty,
            'price'   => $product->new_price ?? $product->old_price ?? 0,
            'options' => [
                'product_id'      => $product->id,
                'slug'            => $product->slug,
                'image'           => optional($product->image)->image ?? null,
                'old_price'       => $product->old_price,
                'purchase_price'  => $product->purchase_price,
                'product_size'    => null,
                'product_color'   => null,
                'size_id'         => null,
                'color_id'        => null,
                'product_discount'=> 0,
                'warranty_tier_id'=> null,
                'warranty_adjustment' => 0,
                'base_price'      => $product->new_price ?? $product->old_price ?? 0,
                'batch_id'        => null,
                'serial_numbers'  => [],
                'barcode'         => $barcode,
            ],
        ]);

        return response()->json([
            'success'  => true,
            'product'  => ['id' => $product->id, 'name' => $product->name, 'stock' => $product->stock],
            'cartinfo' => $cartinfo,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POS HOLD CART
    |--------------------------------------------------------------------------
    */
    public function holdCart(Request $request)
    {
        $cartData = Cart::instance('pos_shopping')->content();

        if ($cartData->count() <= 0) {
            return response()->json(['success' => false, 'message' => 'Cart is empty']);
        }

        $subtotalRaw = Cart::instance('pos_shopping')->subtotal();
        $subtotal   = (float) preg_replace('/[^\d.]/', '', (string) $subtotalRaw);
        $discount   = (float) (Session::get('pos_discount') ?? 0);
        $shipping   = (float) (Session::get('pos_shipping') ?? 0);

        // 🛡️ Calculate warranty charges from POS cart
        $warrantyCharge = 0;
        foreach (Cart::instance('pos_shopping')->content() as $item) {
            $warrantyCharge += (float)($item->options->warranty_adjustment ?? 0) * $item->qty;
        }
        // ✅ Cart::subtotal() already includes warranty in price, so don't add again
        $grandTotal = ($subtotal + $shipping) - $discount;

        $hold = PosHoldCart::create([
            'customer_name'  => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'cart_data'      => array_values($cartData->toArray()),
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'shipping_charge'=> $shipping,
            'grand_total'    => $grandTotal,
            'note'           => $request->note,
            'held_by'        => Auth::id(),
            'held_at'        => now(),
            'status'         => 'held',
        ]);

        // Clear current cart
        Cart::instance('pos_shopping')->destroy();
        Session::forget(['pos_shipping', 'pos_discount', 'pos_coupon_code']);

        return response()->json([
            'success' => true,
            'message' => 'Cart held successfully! Reference: #' . $hold->id,
            'hold'    => $hold,
        ]);
    }

    public function heldCarts()
    {
        $heldCarts = PosHoldCart::where('status', 'held')
            ->where('held_by', Auth::id())
            ->orderBy('held_at', 'desc')
            ->get();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json($heldCarts);
        }

        return view('backEnd.order.held_carts', compact('heldCarts'));
    }

    public function restoreHold($id)
    {
        $heldCart = PosHoldCart::where('id', $id)
            ->where('status', 'held')
            ->where('held_by', Auth::id())
            ->firstOrFail();

        // Clear current cart
        Cart::instance('pos_shopping')->destroy();
        Session::forget(['pos_shipping', 'pos_discount', 'pos_coupon_code']);

        // Restore cart data
        $cartItems = $heldCart->cart_data;
        if (is_array($cartItems)) {
            foreach ($cartItems as $item) {
                Cart::instance('pos_shopping')->add([
                    'id'      => $item['id'],
                    'name'    => $item['name'],
                    'qty'     => $item['qty'],
                    'price'   => $item['price'],
                    'options' => (array) ($item['options'] ?? []),
                ]);
            }
        }

        // Restore session data
        if ($heldCart->discount > 0) {
            Session::put('pos_discount', $heldCart->discount);
        }
        if ($heldCart->shipping_charge > 0) {
            Session::put('pos_shipping', $heldCart->shipping_charge);
        }

        // Restore customer info to session for pre-filling POS form
        Session::put('pos_customer_name', $heldCart->customer_name);
        Session::put('pos_customer_phone', $heldCart->customer_phone);

        // Mark as restored
        $heldCart->update([
            'status'      => 'restored',
            'restored_at' => now(),
        ]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Cart restored successfully']);
        }

        Toastr::success('Held cart restored successfully', 'Success!');
        return redirect()->route('admin.order.create');
    }

    public function deleteHold($id)
    {
        $heldCart = PosHoldCart::where('id', $id)
            ->where('held_by', Auth::id())
            ->firstOrFail();

        $heldCart->update(['status' => 'cancelled']);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Held cart deleted']);
        }

        Toastr::success('Held cart removed', 'Success!');
        return redirect()->back();
    }

    /*
    |--------------------------------------------------------------------------
    | 🖨️ ONE-PAGE POS: PRINT / RECENT / LOAD / RECEIVE PAYMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Print a single order as POS receipt or A4 invoice (standalone page).
     */
    public function printInvoice($invoice_id, Request $request)
    {
        $order = Order::where('invoice_id', $invoice_id)
            ->with(['orderdetails', 'orderdetails.size', 'orderdetails.color', 'payment', 'shipping', 'customer'])
            ->firstOrFail();

        $type = $request->get('type', 'pos'); // 'pos' | 'a4'

        $generalsetting = \App\Models\GeneralSetting::first();
        $contact        = \App\Models\Contact::first();

        return view('backEnd.order.print_invoice', compact('order', 'type', 'generalsetting', 'contact'));
    }

    /**
     * Recent orders for the bottom drawer (AJAX search + filter).
     */
    public function recentOrders(Request $request)
    {
        $q      = trim((string) $request->get('q'));
        $filter = $request->get('filter', 'all');

        $orders = Order::latest()
            ->with(['shipping:id,order_id,name,phone', 'orderdetails:id,order_id,qty'])
            ->limit(10);

        if ($q !== '') {
            $orders->where(function ($query) use ($q) {
                $query->where('invoice_id', 'LIKE', "%{$q}%")
                    ->orWhereHas('shipping', fn ($s) => $s->where('name', 'LIKE', "%{$q}%")->orWhere('phone', 'LIKE', "%{$q}%"))
                    ->orWhereHas('customer', fn ($s) => $s->where('name', 'LIKE', "%{$q}%")->orWhere('phone', 'LIKE', "%{$q}%"));
            });
        }

        $orders = match ($filter) {
            'pos'       => $orders->where('order_type', 'pos'),
            'cod'       => $orders->where('order_type', 'cod'),
            'paid'      => $orders->where('payment_status', 'paid'),
            'partial'   => $orders->where('payment_status', 'partial'),
            'due'       => $orders->where('due_amount', '>', 0),
            'pending'   => $orders->where('order_status', 'pending'),
            'completed' => $orders->where('order_status', 'completed'),
            default     => $orders,
        };

        return response()->json([
            'status' => 'success',
            'html'   => view('backEnd.order.partials.recent_orders_rows', ['orders' => $orders->get()])->render(),
        ]);
    }

    /**
     * Load an existing order into the POS cart (AJAX edit mode, no page move).
     */
    public function loadOrderIntoCart($invoice_id)
    {
        $order = Order::where('invoice_id', $invoice_id)
            ->with(['shipping'])
            ->firstOrFail();

        $this->buildCartFromOrder($order);

        $shipping = $order->shipping;

        return response()->json([
            'status'   => 'success',
            'order_id' => $order->id,
            'invoice_id' => $order->invoice_id,
            'customer' => [
                'name'    => $shipping->name ?? '',
                'phone'   => $shipping->phone ?? '',
                'address' => $shipping->address ?? '',
                'area'    => $shipping->area ?? '',
            ],
            'payment_status' => $order->payment_status,
            'paid_amount'    => (float) $order->paid_amount,
            'due_amount'     => (float) $order->due_amount,
            'cart_html'      => view('backEnd.order.cart_table_rows', ['cartinfo' => Cart::instance('pos_shopping')->content()])->render(),
        ]);
    }

    /**
     * Collect remaining due on a saved order (AJAX).
     */
    public function receivePayment(Request $request)
    {
        $request->validate([
            'order_id'       => 'required|exists:orders,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string',
            'trx_note'       => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $amount = (float) $request->amount;
        $remaining = max(0, (float) $order->due_amount);

        if ($amount > $remaining) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid amount (max due ৳' . number_format($remaining, 2) . ')',
            ], 422);
        }

        $method = trim((string) $request->input('payment_method', 'Cash'));

        // 1) History ledger
        OrderPayment::create([
            'order_id'       => $order->id,
            'customer_id'    => $order->customer_id,
            'amount'         => $amount,
            'payment_method' => $method,
            'trx_note'       => $request->trx_note ?: null,
            'created_by'     => auth()->id(),
        ]);

        // 2) Recalc + status
        $order->paid_amount += $amount;
        $order->due_amount   = max(0, (float) $order->amount - $order->paid_amount);
        $order->payment_status = $order->due_amount > 0 ? 'partial' : 'paid';
        $order->save();

        // 3) Sync payments current-state row
        $payment = Payment::where('order_id', $order->id)->first();
        if ($payment) {
            $payment->amount         = $order->paid_amount;
            $payment->payment_status = $order->payment_status;
            $payment->payment_method = $this->resolvePaymentMethodLabel($method);
            $payment->save();
        }

        // 4) Fund credit
        FundTransaction::create([
            'direction'  => 'in',
            'source'     => 'sale',
            'source_id'  => $order->id,
            'amount'     => $amount,
            'note'       => 'Payment received — Order #' . $order->invoice_id,
            'created_by' => auth()->id(),
        ]);

        // 5) Note
        $order->addNote(
            content: 'Payment received ৳' . number_format($amount, 2) . ' (' . $method . ')' . ($request->trx_note ? ' | Ref: ' . $request->trx_note : ''),
            type: 'success',
            source: 'system',
            userId: auth()->id()
        );

        return response()->json([
            'status'   => 'success',
            'message'  => 'Payment received',
            'due'      => $order->due_amount,
            'paid'     => $order->paid_amount,
            'pay_status' => $order->payment_status,
        ]);
    }
}
