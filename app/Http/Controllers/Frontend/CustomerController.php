<?php

namespace App\Http\Controllers\Frontend;

// use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use App\Mail\OrderPlace;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Intervention\Image\Facades\Image;
use App\Models\Customer;
use App\Models\District;
use App\Models\Order;
use App\Models\ShippingCharge;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Shipping;
use App\Models\Review;
use App\Models\PaymentGateway;
use App\Models\SmsGateway;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\IncompleteOrder;
use App\Models\Product;          // স্টক কমানোর জন্য
use App\Models\DigitalDownload;  // ⭐ ডিজিটাল ডাউনলোড মডেল
use Barryvdh\DomPDF\Facade\Pdf;  // 📄 ইনভয়েস PDF ডাউনলোড

use Session;
use Hash;
use Auth;
use Cart;
use Mail;
use Str;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash as HashFacade;
use Illuminate\Support\Facades\File;
use App\Helpers\OrderHelper;
use App\Services\FacebookCapiService;

class CustomerController extends Controller
{
    protected $facebookCapiService;

    function __construct(FacebookCapiService $facebookCapiService)
    {
        $this->facebookCapiService = $facebookCapiService;
        $this->middleware('customer', ['except' => [
            'register','store','verify','resendotp','account_verify',
            'login','signin','logout','checkout','forgot_password',
            'forgot_verify','forgot_reset','forgot_store','forgot_resend',
            'order_save','order_success','order_track','order_track_result',
            'downloadInvoicePdf'
        ]]);
    }

    public function review(Request $request)
    {
        $this->validate($request,[
            'ratting'=>'required',
            'review'=>'required',
        ]);

        $review = new Review();
        $review->name = Auth::guard('customer')->user()->name ?? 'N / A';
        $review->email = Auth::guard('customer')->user()->email ?? 'N / A';
        $review->product_id = $request->product_id;
        $review->review = $request->review;
        $review->ratting = $request->ratting;
        $review->customer_id = Auth::guard('customer')->user()->id;
        $review->status = 'pending';
        $review->save();

        Toastr::success('Thanks, Your review send successfully', 'Success!');
        return redirect()->back();
    }

    public function login()
    {
        return view('frontEnd.layouts.customer.login');
    }

    public function signin(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login    = $request->input('login');
        $password = $request->input('password');

        // Try customer login by phone
        if (preg_match('/^[0-9+]+$/', $login)) {
            $customerExists = Customer::where('phone', $login)->exists();
            if ($customerExists && Auth::guard('customer')->attempt(['phone' => $login, 'password' => $password])) {
                Toastr::success('You are login successfully', 'success!');
                if (Cart::instance('shopping')->count() > 0) {
                    return redirect()->route('customer.checkout');
                }
                return redirect()->intended('customer/account');
            }
        }

        // Try customer login by email
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $customerExists = Customer::where('email', $login)->exists();
            if ($customerExists && Auth::guard('customer')->attempt(['email' => $login, 'password' => $password])) {
                Toastr::success('You are login successfully', 'success!');
                if (Cart::instance('shopping')->count() > 0) {
                    return redirect()->route('customer.checkout');
                }
                return redirect()->intended('customer/account');
            }
        }

        // Failed
        Toastr::error('Opps! your credentials are wrong', 'Error');
        return redirect()->back()->withInput($request->only('login'));
    }

    public function register()
    {
        return view('frontEnd.layouts.customer.register');
    }

    public function store(Request $request)
    {
        // Customer registration
        $this->validate($request, [
            'name'     => 'required',
            'phone'    => 'required|unique:customers|regex:/^01[3-9]\d{8}$/',
            'email'    => 'nullable|email|unique:customers',
            'password' => 'required|min:6'
        ], [
            'phone.regex'  => 'একটি বৈধ বাংলাদেশী মোবাইল নাম্বার দিন (যেমন: 017xxxxxxxx)',
            'phone.unique' => 'এই মোবাইল নাম্বারটি ইতিমধ্যে রেজিস্টার করা আছে',
            'email.unique' => 'এই ইমেইলটি ইতিমধ্যে রেজিস্টার করা আছে',
            'email.email'  => 'একটি বৈধ ইমেইল ঠিকানা দিন',
        ]);

        $last_id = Customer::orderBy('id', 'desc')->first();
        $last_id = $last_id?$last_id->id+1:1;

        $store = new Customer();
        $store->name = $request->name;
        $store->slug = strtolower(Str::slug($request->name.'-'.$last_id));
        $store->phone = $request->phone;
        $store->email = $request->email ?? null;
        $store->password = bcrypt($request->password);
        $store->verify = 1;
        $store->status = 'active';
        $store->save();

        // Assign customer role
        $customerRole = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'customer', 'guard_name' => 'customer'],
            ['name' => 'customer', 'guard_name' => 'customer']
        );
        $store->assignRole($customerRole);

        Toastr::success('Success','Account Create Successfully');
        return redirect()->route('customer.login');
    }

    public function verify()
    {
        return view('frontEnd.layouts.customer.verify');
    }

    public function resendotp(Request $request)
    {
        $customer_info = Customer::where('phone',session::get('verify_phone'))->first();
        $customer_info->verify = rand(1111,9999);
        $customer_info->save();
        $site_setting = GeneralSetting::where('status', 1)->first();
        $sms_gateway = SmsGateway::where('status', 1)->first();

        if($sms_gateway) {
            $message = "Dear $customer_info->name!\r\nYour account verify OTP is $customer_info->verify \r\nThank you for using $site_setting->name";
            $this->sendSms($sms_gateway, $customer_info->phone, $message);
        }

        Toastr::success('Success','Resend code send successfully');
        return redirect()->back();
    }

    public function account_verify(Request $request)
    {
        $this->validate($request,['otp' => 'required']);
        $customer_info = Customer::where('phone',session::get('verify_phone'))->first();

        if($customer_info->verify != $request->otp){
            Toastr::error('Success','Your OTP not match');
            return redirect()->back();
        }

        $customer_info->verify = 1;
        $customer_info->status = 'active';
        $customer_info->save();
        Auth::guard('customer')->loginUsingId($customer_info->id);
        return redirect()->route('customer.account');
    }

    public function forgot_password()
    {
        return view('frontEnd.layouts.customer.forgot_password');
    }

    public function forgot_verify(Request $request)
    {
        $phone = $request->phone;
        $customer_info = Customer::where('phone', $phone)->first();

        if(!$customer_info){
            Log::warning('Forgot password — phone not found', ['phone' => $phone, 'ip' => $request->ip()]);
            Toastr::error('Your phone number not found');
            return back();
        }

        $customer_info->forgot = rand(1111,9999);
        $customer_info->save();

        $site_setting = GeneralSetting::where('status', 1)->first();
        $sms_gateway = SmsGateway::where(['status'=> 1, 'forget_pass'=>1])->first();
        
        $otp = $customer_info->forgot;
        $name = $customer_info->name;
        
        Log::info('Forgot password OTP generated', [
            'customer_id' => $customer_info->id,
            'phone' => $phone,
            'otp' => $otp,
            'ip' => $request->ip()
        ]);
        
        if($sms_gateway) {
            $message = "Dear $name!\r\nYour forgot password verify OTP is $otp \r\nThank you for using $site_setting->name";
            $this->sendSms($sms_gateway, $phone, $message);
            Log::info('Forgot OTP SMS sent', ['phone' => $phone]);
        } else {
            Log::warning('Forgot OTP — no SMS gateway configured for forgot password', ['phone' => $phone]);
        }

        Session::put('verify_phone', $phone);
        Toastr::success('OTP sent successfully to your phone');
        return redirect()->route('customer.forgot.reset');
    }

    public function forgot_resend(Request $request)
    {
        $phone = Session::get('verify_phone');
        $customer_info = Customer::where('phone', $phone)->first();

        if(!$customer_info){
            Log::warning('Forgot OTP resend failed — customer not found', ['phone' => $phone]);
            Toastr::error('Something went wrong');
            return redirect()->route('customer.forgot.password');
        }

        $customer_info->forgot = rand(1111,9999);
        $customer_info->save();
        $name = $customer_info->name;
        $otp = $customer_info->forgot;

        Log::info('Forgot OTP resent', [
            'customer_id' => $customer_info->id,
            'phone' => $phone,
            'otp' => $otp,
            'ip' => $request->ip()
        ]);

        $site_setting = GeneralSetting::where('status', 1)->first();
        $sms_gateway = SmsGateway::where(['status'=> 1])->first();

        if($sms_gateway) {
            $message = "Dear $name!\r\nYour forgot password verify OTP is $otp \r\nThank you for using $site_setting->name";
            $this->sendSms($sms_gateway, $phone, $message);
            Log::info('Forgot OTP SMS sent', ['phone' => $phone]);
        } else {
            Log::warning('Forgot OTP — no active SMS gateway', ['phone' => $phone]);
        }

        Toastr::success('Success','Resend code send successfully');
        return redirect()->back();
    }

    public function forgot_reset()
    {
        if(!Session::get('verify_phone')){
          Toastr::error('Something wrong please try again');
          return redirect()->route('customer.forgot.password'); 
        }
        return view('frontEnd.layouts.customer.forgot_reset');
    }

    public function forgot_store(Request $request)
    {
        $phone = Session::get('verify_phone');
        $customer_info = Customer::where('phone', $phone)->first();

        if(!$customer_info || $customer_info->forgot != $request->otp){
            Toastr::error('Your OTP not match');
            return redirect()->back();
        }

        $customer_info->forgot = 1;
        $customer_info->password = bcrypt($request->password);
        $customer_info->save();

        if(Auth::guard('customer')->attempt(['phone' => $customer_info->phone, 'password' => $request->password])) {
            Session::forget('verify_phone');
            Toastr::success('Password reset successfully. You are logged in!', 'Success!');
            return redirect()->intended('customer/account');
        }

        Toastr::error('Something went wrong');
        return redirect()->route('customer.forgot.password');
    }

    public function account()
    {
        return view('frontEnd.layouts.customer.account');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        Toastr::success('You are logout successfully', 'success!');
        return redirect()->route('customer.login');
    }

    public function checkout()
    {
        $shippingcharge = ShippingCharge::where('status',1)->get();
        $bkash_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'bkash'])->first();
        $shurjopay_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'shurjopay'])->first();
        $uddoktapay_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'uddoktapay'])->first();
        $aamarpay_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'aamarpay'])->first();

        // ⭐ Districts for the district → area shipping selector
        $districts = District::distinct()->orderBy('district')->pluck('district');

        // ⭐ area_id → shipping fee map (resolved through the shipping_charge_district pivot)
        $areaChargeMap = District::with('shippingCharges')->get()
            ->mapWithKeys(function ($area) {
                $charge = $area->shippingCharges->where('status', 1)->first();
                return [$area->id => (float) ($charge->amount ?? 0)];
            });

        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        // ⭐ Advance Check - যদি কার্টে advance amount > 0 থাকে, তাহলে advance payment option দেখানো হবে
        // ⭐ Digital Product Check - যদি কার্টে ডিজিটাল প্রোডাক্ট থাকে, তাহলে COD অপশন hide করা হবে
        // ⭐ Display-only initial shipping (from session). SECURITY: the final charged fee is
        //   ALWAYS recomputed from the DB in order_save() — never from this session value.
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();
        $shippingAmount = $hasAllFreeDelivery ? 0 : (float) Session::get('shipping', 0);
        Session::put('shipping', $shippingAmount);

        $advanceTotal = \App\Http\Controllers\Frontend\ShoppingController::getCartAdvanceAmount();
        $hasAdvance   = $advanceTotal > 0;

        // ⭐ কার্টে ডিজিটাল প্রোডাক্ট আছে কি না
        $hasDigital = \App\Http\Controllers\Frontend\ShoppingController::hasDigitalProductInCart();

        // Facebook CAPI InitiateCheckout (server-side)
        if (Cart::instance('shopping')->count() > 0) {
            try {
                $cartContent = Cart::instance('shopping')->content();
                $contentIds = $cartContent->pluck('id')->map(fn($id) => (string)$id)->values()->toArray();
                $contents = $cartContent->map(fn($i) => [
                    'id' => (string)$i->id,
                    'quantity' => (int)$i->qty,
                    'item_price' => (float)$i->price,
                ])->values()->toArray();
                $checkoutValue = (float) str_replace([',','.00'], '', Cart::instance('shopping')->subtotal());
                $capiUserData = [
                    'client_ip_address' => request()->ip(),
                    'client_user_agent' => request()->userAgent(),
                ];
                if (isset($_COOKIE['_fbp'])) $capiUserData['fbp'] = $_COOKIE['_fbp'];
                if (isset($_COOKIE['_fbc'])) $capiUserData['fbc'] = $_COOKIE['_fbc'];
                app(\App\Services\FacebookCapiService::class)->sendInitiateCheckout([
                    'currency'     => 'BDT',
                    'value'        => $checkoutValue,
                    'content_ids'  => $contentIds,
                    'contents'     => $contents,
                    'num_items'    => $cartContent->count(),
                    'content_type' => 'product',
                ], $capiUserData, [
                    'event_id'        => 'ico_' . time(),
                    'event_source_url' => request()->fullUrl(),
                ]);
            } catch (\Throwable $e) {
                // non-blocking
            }
        }

        return view('frontEnd.layouts.customer.checkout',compact(
            'shippingcharge',
            'districts',
            'areaChargeMap',
            'bkash_gateway',
            'shurjopay_gateway',
            'uddoktapay_gateway',
            'aamarpay_gateway',
            'advanceTotal',
            'hasAdvance',
            'hasDigital',
            'hasAllFreeDelivery'
        ));
    }

public function order_save(Request $request)
    {
        $this->validate($request,[
            'name'=>'required',
            'phone'=>'required',
            'address'=>'required',
            'area'=>'required',
        ]);

        // ⭐ ক্যাম্পেইন পেজ থেকে নির্দিষ্ট প্রোডাক্ট সিলেক্ট করা হলে কার্টে সেই প্রোডাক্ট সেট করি
        if ($request->filled('product')) {
            $campaignProduct = Product::with('image')->find($request->product);
            if ($campaignProduct) {
                // 🛡️ Warranty tier from campaign order form
                $warrantyAdjustment = 0;
                $warrantyTierId = null;
                if ($request->filled('warranty_tier_id')) {
                    $warrantyTier = \App\Models\ProductWarrantyTier::find($request->warranty_tier_id);
                    if ($warrantyTier && $warrantyTier->is_active) {
                        $warrantyAdjustment = (float) ($warrantyTier->additional_cost ?? 0);
                        $warrantyTierId = $warrantyTier->id;
                    }
                }

                $campaignPrice = (float) ($campaignProduct->new_price ?? $campaignProduct->old_price ?? 1) + $warrantyAdjustment;

                Cart::instance('shopping')->destroy();
                Cart::instance('shopping')->add([
                    'id'   => $campaignProduct->id,
                    'name' => $campaignProduct->name,
                    'qty'  => 1,
                    'price'=> $campaignPrice,
                    'options' => [
                        'slug'           => $campaignProduct->slug,
                        'image'          => $campaignProduct->image->image ?? 'public/uploads/default.webp',
                        'old_price'      => (float) ($campaignProduct->old_price ?? 0),
                        'purchase_price' => (float) ($campaignProduct->purchase_price ?? 0),

                        // 🔥 Advance
                        'advance_amount' => (float) ($campaignProduct->advance_amount ?? 0),

                        // 🔥 Digital flag
                        'is_digital'     => (int) ($campaignProduct->is_digital ?? 0),

                        // 🔥 Free Delivery flag
                        'free_delivery'  => (int) ($campaignProduct->free_delivery ?? 0),

                        // 🏷️ Original prices
                        'regular_price'       => (float) ($campaignProduct->old_price ?? 0),
                        'sale_price'          => (float) ($campaignProduct->new_price ?? 0),
                        'base_price'          => (float) ($campaignProduct->new_price ?? $campaignProduct->old_price ?? 1),

                        // 🛡️ Warranty
                        'warranty_tier_id'    => $warrantyTierId,
                        'warranty_adjustment' => $warrantyAdjustment,
                        'wholesale_discount'  => 0,
                    ],
                ]);
            }
        }

        if(Cart::instance('shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');
            return redirect()->back();
        }

        // ⭐ কার্টে ডিজিটাল প্রোডাক্ট আছে কি না চেক
        $hasDigital = \App\Http\Controllers\Frontend\ShoppingController::hasDigitalProductInCart();

        if ($hasDigital && $request->payment_method === 'cod') {
            Toastr::error('ডিজিটাল প্রোডাক্টের জন্য Cash On Delivery পাওয়া যায় না, অনুগ্রহ করে অনলাইন পেমেন্ট সিলেক্ট করুন।', 'Failed!');
            return redirect()->back();
        }

        // Amount ক্যালকুলেশন
        $subtotal = (float) str_replace([',','.00'],'',Cart::instance('shopping')->subtotal());
        $discount = Session::get('discount', 0);
        
        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();
        $shipping_area = null;
        $shippingAreaRow = null;
        
        if ($hasAllFreeDelivery) {
            $shippingfee = 0;
            Session::put('shipping', 0);
        } else {
            // ✅ SECURITY: final shipping fee ALWAYS resolved server-side from DB.
            // NEVER trust Session::get('shipping') for the charged amount — a user
            // could manipulate their session / AJAX to set it to anything.
            $shippingAreaRow = null;
            $shipping_area   = null;

            if (is_numeric($request->area)) {
                // (a) Checkout: area = district/area row id → fee via shipping_charge_district pivot
                $shippingAreaRow = District::find((int) $request->area);
                if ($shippingAreaRow) {
                    $shipping_area = $shippingAreaRow->shippingCharges()->where('status', 1)->first();
                }
                // (b) Legacy (campaign page): area = ShippingCharge id
                if (!$shipping_area) {
                    $shipping_area = ShippingCharge::where('id', (int) $request->area)->where('status', 1)->first();
                }
            }

            // (c) No charge linked to the area → 0 (NOT the session value)
            $shippingfee = $shipping_area ? (float) $shipping_area->amount : 0;
            Session::put('shipping', $shippingfee);
        }

        // কার্টের advance item গুলোর মোট
        $advanceTotal = \App\Http\Controllers\Frontend\ShoppingController::getCartAdvanceAmount();

        // 🛡️ Calculate warranty charges from cart
        $warrantyCharge = 0;
        foreach (Cart::instance('shopping')->content() as $item) {
            $warrantyCharge += (float)($item->options->warranty_adjustment ?? 0) * $item->qty;
        }

        // ইনভয়েসে দেখানোর মোট (Grand Total)
        // $subtotal ইতিমধ্যেই cart price-তে warranty সহ calculated, তাই আলাদা করে warranty যোগ করার দরকার নেই
        $grandTotal = $subtotal + $shippingfee - $discount;

        // =========================================================
        // ⭐ ফিক্সড লজিক: গেটওয়েতে কত টাকা পাঠাবো?
        // =========================================================
        // যদি এডভান্স থাকে, তাহলে শুধু এডভান্স এমাউন্ট পে করতে হবে।
        // যদি না থাকে, তাহলে পুরো গ্র্যান্ড টোটাল পে করতে হবে।
        $payable_amount = ($advanceTotal > 0) ? $advanceTotal : $grandTotal;

        // Customer ঠিক করা
        if(Auth::guard('customer')->user()){
            $customer_id = Auth::guard('customer')->user()->id;
        }else{
            $exist = Customer::where('phone',$request->phone)->select('id')->first();
            if($exist){
                $customer_id = $exist->id;
            }else{
                $password = rand(111111,999999);
                $store = new Customer();
                $store->name = $request->name;
                $store->slug = Str::slug($request->name);
                $store->phone = $request->phone;
                $store->password = bcrypt($password);
                $store->verify = 1;
                $store->status = 'active';
                $store->save();
                $customer_id = $store->id;
            }
        }

        // Main Order save
        $order = new Order();
        $order->invoice_id      = rand(11111,99999);
        $order->amount          = $grandTotal; // অর্ডারে সবসময় টোটাল এমাউন্ট থাকবে
        $order->shipping_charge = $shippingfee;
        $order->customer_id     = $customer_id;
        $order->order_status    = \App\Enums\OrderStatus::PENDING->value;
        $order->order_type      = $request->payment_method === 'cod' ? 'cod' : 'online';
        $order->note            = $request->note;
        $order->order_note      = $request->order_note;
        $order->payment_status  = 'pending';
        $order->coupon_code     = Session::get('coupon_code') ?? null;
        $order->discount        = $discount ?? 0;
        $order->ip_address      = $request->ip();
        
        $order->save();

        // Shipping info
        $shipping = new Shipping();
        $shipping->order_id    = $order->id;
        $shipping->customer_id = $customer_id;
        $shipping->name        = $request->name;
        $shipping->phone       = $request->phone;
        $shipping->address     = $request->address;
        
        if ($shippingAreaRow) {
            $shipping->area = $shippingAreaRow->area_name . ', ' . $shippingAreaRow->district;
        } elseif ($shipping_area) {
            $shipping->area = $shipping_area->name;
        } else {
            $shipping->area = 'Digital / Free Shipping';
        }
        $shipping->save();

        // Payment info
        $payment = new Payment();
        $payment->order_id       = $order->id;
        $payment->customer_id    = $customer_id;
        $payment->payment_method = $request->payment_method;

        // =========================================================
        // ⭐ ফিক্সড লজিক: ডাটাবেসে কত টাকা সেভ করব?
        // =========================================================
        if (in_array($request->payment_method, ['bkash', 'shurjopay', 'uddoktapay', 'aamarpay'])) {
            // অনলাইন পেমেন্ট: শুরুতে ০ রাখব। পেমেন্ট ক্যান্সেল হলে ০ থাকবে (Unpaid দেখাবে)।
            // পেমেন্ট সাকসেস হলে IPN/Callback এসে এই ০ কে আপডেট করে $payable_amount বসিয়ে দিবে।
            $payment->amount = 0; 
        } else {
            // COD: এখানে সরাসরি এমাউন্ট বসিয়ে দিব
            $payment->amount = $payable_amount;
        }

        $payment->payment_status = 'pending';
        $payment->save();

        // Order details save
        OrderHelper::saveOrderDetails($order);

        // 🛡️ Stock reduce — batch-tracked (FIFO/LIFO/Average)
        $details = OrderDetails::where('order_id', $order->id)->with('product')->get();
        try {
            $stockService = app(\App\Services\StockManagementService::class);
            foreach ($details as $row) {
                if ($row->product && $row->qty > 0) {
                    $stockService->stockOut($row->product, $row->qty, [
                        'type' => 'sale',
                        'id'   => $order->id,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Batch stock-out failed for order #'.$order->id.': '.$e->getMessage());
            foreach ($details as $row) {
                if ($row->product) {
                    $row->product->stock = max(0, ($row->product->stock ?? 0) - $row->qty);
                    $row->product->save();
                }
            }
        }

 // === Customer SMS ===
        try {
            $sms_gateway = SmsGateway::where(['status' => 1, 'order' => 1])->first();
            if(!$sms_gateway){
                $sms_gateway = SmsGateway::where('status', 1)->first();
            }

            if($sms_gateway) {
                $customerPhone = isset($shipping) && $shipping->phone ? $shipping->phone : ($request->phone ?? ($order->customer->phone ?? null));
                $customerName  = isset($shipping) && $shipping->name ? $shipping->name : ($request->name ?? ($order->customer->name ?? 'Customer'));
                $site_setting = GeneralSetting::where('status', 1)->first();

                if($customerPhone) {
                    $customerMessage = "প্রিয় {$customerName}! আপনার অর্ডার #{$order->invoice_id} সফলভাবে গ্রহণ করা হয়েছে। মোট: {$order->amount} Tk. {$site_setting->name}";
                    $phone = preg_replace('/[^0-9+]/','', $customerPhone);
                    $resp = $this->sendSms($sms_gateway, $phone, $customerMessage, $sms_gateway->serderid ?? $sms_gateway->senderid ?? '');
                    \Log::info("Customer SMS to {$phone}: resp=" . substr($resp ?? '',0,200));
                } else {
                    \Log::warning("Customer SMS skipped: no phone for order {$order->id}");
                }
            }
        } catch(\Exception $e) {
            \Log::error("Customer SMS error for order {$order->id}: " . $e->getMessage());
        }

        // === Admin SMS ===
        try {
            $sms_gateway = SmsGateway::where('status', 1)->first();
            if($sms_gateway) {
                $adminPhones = env('ADMIN_PHONE_LIST', null);
                if(!$adminPhones && isset($sms_gateway->admin_phone)){
                    $adminPhones = $sms_gateway->admin_phone;
                }
                if(!$adminPhones){
                    $contact = Contact::first();
                    $adminPhones = $contact->phone ?? null;
                }

                $site_setting = GeneralSetting::where('status', 1)->first();
                $customerName = isset($request->name) ? $request->name : ($order->customer->name ?? 'Customer');
                $customerPhone = isset($request->phone) ? $request->phone : ($order->customer->phone ?? '');
                $adminMessage = "নতুন অর্ডার এসেছে!\nOrder#: {$order->invoice_id}\nকাস্টমার: {$customerName}\nমোবাইল: {$customerPhone}\nমোট: {$order->amount} Tk {$site_setting->name}";

                if($adminPhones){
                    $numbers = array_filter(array_map('trim', explode(',', $adminPhones)));
                    foreach($numbers as $adminPhone){
                        $adminPhone = preg_replace('/[^0-9+]/', '', $adminPhone);
                        $senderid = $sms_gateway->serderid ?? $sms_gateway->senderid ?? '';
                        $resp = $this->sendSms($sms_gateway, $adminPhone, $adminMessage, $senderid);
                        \Log::info("Admin SMS to {$adminPhone}: resp=" . substr($resp ?? '',0,200));
                    }
                }
            }
        } catch(\Exception $e){
            \Log::error('Admin SMS send failed: '.$e->getMessage());
        }

        // Incomplete order delete
        IncompleteOrder::where('phone', $request->phone)->delete();

        // =========================================================
        // ⭐ পেমেন্ট গেটওয়ে রিডাইরেক্ট (FIXED)
        // =========================================================
        
        // Bkash এবং UddoktaPay এর জন্য সেশনে এমাউন্ট সেট করে দিচ্ছি 
        // যাতে ওই কন্ট্রোলারগুলো সঠিক এমাউন্ট পায়
        Session::put('payable_amount', $payable_amount);

        if($request->payment_method == 'bkash'){
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect('/bkash/checkout-url/create?order_id='.$order->id);

        } elseif($request->payment_method == 'shurjopay'){

            $info = [
                'currency'        => "BDT",
                'amount'          => $payable_amount, // ✅ এখানে ফিক্স করা হলো: এডভান্স থাকলে এডভান্স, না হলে ফুল
                'order_id'        => uniqid(),
                'client_ip'       => $request->ip(),
                'customer_name'   => $request->name,
                'customer_phone'  => $request->phone,
                'email'           => "customer@gmail.com",
                'customer_address'=> $request->address,
                'customer_city'   => $request->area,
                'customer_country'=> "BD",
                'value1'          => $order->id
            ];

            Session::forget('coupon_code');
            Session::forget('discount');

            $sp = new ShurjopayController();
            return $sp->checkout($info);

        } elseif($request->payment_method == 'uddoktapay'){
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect()->route('uddoktapay.checkout',['order_id'=>$order->id]);

        } elseif($request->payment_method == 'aamarpay'){
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect()->route('aamarpay.checkout',['order_id'=>$order->id]);

        } else {
            // Cash On Delivery
            $this->createDigitalDownloads($order);
            
            // Send Facebook Purchase event for COD orders (async - don't block order submission)
            try {
                $customer = Customer::find($customer_id);
                $userData = [];
                
                // Get customer email or phone
                if ($customer && $customer->email) {
                    $userData['email'] = $customer->email;
                } elseif ($request->phone) {
                    $userData['phone'] = $request->phone;
                } elseif ($customer && $customer->phone) {
                    $userData['phone'] = $customer->phone;
                }
                
                // Get Facebook Pixel cookies if available
                if (isset($_COOKIE['_fbp'])) {
                    $userData['fbp'] = $_COOKIE['_fbp'];
                }
                if (isset($_COOKIE['_fbc'])) {
                    $userData['fbc'] = $_COOKIE['_fbc'];
                }
                
                // Send Purchase event after response is sent (non-blocking)
                // Use register_shutdown_function to send after response is sent to user
                register_shutdown_function(function () use ($order, $userData, $request) {
                    try {
                        $orderDetails = $order->orderdetails ?? \App\Models\Order::with('orderdetails')->find($order->id)?->orderdetails ?? collect();
                        $contentIds  = $orderDetails->pluck('product_id')->map(fn($id) => (string)$id)->values()->toArray();
                        $contents    = $orderDetails->map(fn($i) => ['id' => (string)$i->product_id, 'quantity' => (int)$i->qty, 'item_price' => (float)$i->sale_price])->values()->toArray();
                        app(\App\Services\FacebookCapiService::class)->sendEvent('Purchase', [
                            'currency'     => 'BDT',
                            'value'        => $order->amount,
                            'order_id'     => $order->invoice_id ?? $order->id,
                            'content_ids'  => $contentIds,
                            'contents'     => $contents,
                            'num_items'    => count($contents),
                            'content_type' => 'product',
                        ], $userData, [
                            'event_id'          => 'purchase_' . ($order->invoice_id ?? $order->id),
                            'event_source_url'  => $request->fullUrl(),
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Facebook CAPI Purchase event failed for order ' . $order->id . ': ' . $e->getMessage());
                    }
                });
            } catch (\Exception $e) {
                \Log::error('Facebook CAPI setup failed for order ' . $order->id . ': ' . $e->getMessage());
            }
            
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect('customer/order-success/'.$order->id);
        }
    }


    public function orders()
    {
        $orders = Order::where('customer_id',Auth::guard('customer')->user()->id)
            ->with(['status', 'orderdetails.product.image', 'orderdetails.image'])
            ->latest()
            ->paginate(10);

        return view('frontEnd.layouts.customer.orders',compact('orders'));
    }

    public function order_success($id)
    {
        $order = Order::with(['orderdetails.size', 'orderdetails.color', 'shipping'])
            ->where('id', $id)
            ->firstOrFail();
        return view('frontEnd.layouts.customer.order_success', compact('order'));
    }

    public function invoice(Request $request)
    {
        $order = Order::where([
                'id'=>$request->id,
                'customer_id'=>Auth::guard('customer')->user()->id
            ])
            ->with(['orderdetails.size', 'orderdetails.color', 'payment', 'shipping', 'customer'])
            ->firstOrFail();

        return view('frontEnd.layouts.customer.invoice',compact('order'));
    }

    /**
     * 📄 Direct PDF download of the customer invoice (Dompdf).
     */
    public function downloadInvoicePdf($id)
    {
        $order = Order::where('id', $id)
            ->with(['orderdetails.size', 'orderdetails.color', 'payment', 'shipping', 'customer'])
            ->firstOrFail();

        // No authorization — direct download (public invoice link, same as order tracking)
        $generalsetting = \App\Models\GeneralSetting::where('status', 1)->first();
        $contact = \App\Models\Contact::where('status', 1)->first();

        $pdf = Pdf::loadView('frontEnd.layouts.customer.invoice_pdf', compact('order', 'generalsetting', 'contact'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download('Invoice-' . $order->invoice_id . '.pdf');
    }

    public function order_note(Request $request)
    {
        $order = Order::where([
                'id'=>$request->id,
                'customer_id'=>Auth::guard('customer')->user()->id
            ])->firstOrFail();

        return view('frontEnd.layouts.customer.order_note',compact('order'));
    }

    public function profile_edit(Request $request)
    {
        $profile_edit = Customer::where(['id'=>Auth::guard('customer')->user()->id])->firstOrFail();
        $districts = District::distinct()->select('district')->get();
        $areas = District::where(['district'=>$profile_edit->district])->select('area_name','id')->get();
        if ($areas->isEmpty() && !$profile_edit->district) {
            $areas = District::select('area_name','id')->orderBy('area_name')->get();
        }
        
        // Refresh the model to get latest data
        $profile_edit->refresh();
        
        return view('frontEnd.layouts.customer.profile_edit',compact('profile_edit','districts','areas'));
    }

    public function profile_update(Request $request)
    {
        $update_data = Customer::where(['id'=>Auth::guard('customer')->user()->id])->firstOrFail();

        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:customers,email,'.$update_data->id,
            'address' => 'required|string|max:500',
            'district' => 'required|string|max:100',
            'area' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $image = $request->file('image');
        if($image){
            try {
                // Delete old image if exists
                if ($update_data->image) {
                    $oldImagePath = public_path($update_data->image);
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }

                $name =  time().'-'.$image->getClientOriginalName();
                $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name);
                $name = strtolower(Str::slug($name));
                
                // Directory path with public/ prefix
                $uploadpath = 'public/uploads/customer/';
                $uploadFullPath = public_path($uploadpath);
                
                // Create directory if not exists
                if (!file_exists($uploadFullPath)) {
                    \Illuminate\Support\Facades\File::makeDirectory($uploadFullPath, 0755, true);
                }
                
                // Full path for saving
                $imageUrl = $uploadFullPath . $name;
                
                // Process and save image
                $img = Image::make($image->getRealPath());
                $img->encode('webp', 90);
                $img->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save($imageUrl);
                
                // Verify image was saved
                if (!file_exists($imageUrl)) {
                    throw new \Exception('Image file was not saved successfully');
                }
                
                // Save path in database (with public/ prefix for asset() helper)
                $imageUrl = $uploadpath . $name;
            } catch (\Exception $e) {
                Toastr::error('Image upload failed: ' . $e->getMessage(), 'Error!');
                return redirect()->back()->withInput();
            }
        }else{
            $imageUrl = $update_data->image;
        }

        $update_data->name = $request->name;
        $update_data->phone = $request->phone;
        $update_data->email = $request->email;
        $update_data->address = $request->address;
        $update_data->district = $request->district;
        $update_data->area = $request->area;
        $update_data->image = $imageUrl;
        $update_data->save();

        // Refresh the model to get updated attributes
        $update_data->refresh();

        Toastr::success('আপনার প্রোফাইল সফলভাবে আপডেট হয়েছে', 'সফল!');
        return redirect()->route('customer.profile_edit');
    }

   public function order_track_result(Request $request)
    {
        $phone = $request->phone;
        $invoice_id = $request->invoice_id;

        // ১. ভ্যালিডেশন: অন্তত একটি ইনপুট থাকতে হবে
        if (!$phone && !$invoice_id) {
            Toastr::error('অনুগ্রহ করে মোবাইল নাম্বার অথবা ইনভয়েস আইডি দিন', 'Error');
            return redirect()->back();
        }

        // ২. কুয়েরি শুরু (Order মডেল ব্যবহার করে)
        $query = Order::query();

        // যদি ইনভয়েস আইডি দেওয়া থাকে
        if ($invoice_id) {
            $query->where('invoice_id', $invoice_id);
        }

        // যদি ফোন নম্বর দেওয়া থাকে
        if ($phone) {
            // আমরা Shipping টেবিল চেক করব কারণ অর্ডারের ফোন নম্বর সেখানেই থাকে
            $query->whereHas('shipping', function($q) use ($phone){
                $q->where('phone', $phone);
            });
        }

        // ৩. ডাটা নিয়ে আসা (Eager Loading সহ)
        // latest() দিলে নতুন অর্ডার আগে দেখাবে
        $order = $query->with(['shipping', 'status', 'orderdetails'])->latest()->get();

        // ৪. যদি কোনো অর্ডার না পাওয়া যায়
        if ($order->count() == 0) {
            Toastr::error('দুঃখিত! কোনো অর্ডার পাওয়া যায়নি।', 'Failed');
            return redirect()->back();
        }

        // ৫. ভিউতে ডাটা পাঠানো
        // আপনার কন্ট্রোলারে ভিউয়ের নাম 'tracking_result' দেওয়া আছে, তাই সেটিই রাখলাম।
        // কিন্তু নিশ্চিত হোন আপনার ব্লেড ফাইলের নাম tracking_result.blade.php নাকি track_order.blade.php
        return view('frontEnd.layouts.customer.tracking_result', compact('order'));
    }
// এই ফাংশনটি মিসিং থাকার কারণেই এরর আসছিল
    public function order_track()
    {
        return view('frontEnd.layouts.customer.order_track');
    }
    public function change_pass()
    {
        return view('frontEnd.layouts.customer.change_password');
    }

    public function password_update(Request $request)
    {
        $this->validate($request, [
            'old_password'=>'required',
            'new_password'=>'required',
            'confirm_password' => 'required_with:new_password|same:new_password|'
        ]);

        $customer = Customer::find(Auth::guard('customer')->user()->id);
        $hashPass = $customer->password;

        if (Hash::check($request->old_password, $hashPass)) {
            $customer->fill([
                'password' => Hash::make($request->new_password)
            ])->save();

            Toastr::success('Success', 'Password changed successfully!');
            return redirect()->route('customer.account');
        }else{
            Toastr::error('Failed', 'Old password not match!');
            return redirect()->back();
        }
    }

    // =====================================
    // ⭐ DIGITAL DOWNLOAD CREATOR (HELPER)
    // =====================================
    private function createDigitalDownloads(Order $order)
    {
        // orderdetails থেকে product_id নিয়ে Product লোড করব
        $items = OrderDetails::where('order_id', $order->id)->get();

        foreach ($items as $item) {
            $product = Product::find($item->product_id);

            if ($product && $product->is_digital == 1 && $product->digital_file) {

                // একই order+product+customer এর জন্য ডুপ্লিকেট না হয়
                DigitalDownload::firstOrCreate(
                    [
                        'order_id'    => $order->id,
                        'product_id'  => $product->id,
                        'customer_id' => $order->customer_id,
                    ],
                    [
                        'token'               => Str::uuid(),
                        'file_path'           => $product->digital_file,
                        'remaining_downloads' => $product->download_limit ?? 5,
                        'expires_at'          => $product->download_expire_days
                                                    ? now()->addDays($product->download_expire_days)
                                                    : null,
                    ]
                );
            }
        }
    }

    /**
     * Send SMS using the configured gateway (supports dynamic method & phone_key).
     */
    private function sendSms($sms_gateway, $phone, $message, $senderid = null)
    {
        $phoneKey   = $sms_gateway->phone_key ?? 'number';
        $messageKey = $sms_gateway->message_key ?? 'message';
        $method     = strtoupper($sms_gateway->method ?? 'POST');
        $senderid   = $senderid ?? $sms_gateway->serderid ?? '';

        $params = [
            'api_key' => $sms_gateway->api_key,
            $phoneKey => $phone,
            'type'    => 'text',
            'senderid'=> $senderid,
            $messageKey => $message,
        ];

        $ch = curl_init();

        if ($method === 'GET') {
            $url = $sms_gateway->url . '?' . http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $sms_gateway->url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        return $response;
    }

    public function complaints()
    {
        return view('frontEnd.layouts.customer.complaints');
    }
}
