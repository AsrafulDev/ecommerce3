<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\Payment;
use App\Models\OrderDetails;
use App\Models\PaymentGateway;
use App\Models\DigitalDownload;
use Gloudemans\Shoppingcart\Facades\Cart;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Str;
use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use App\Http\Controllers\Frontend\ShoppingController;

class ResellerCheckoutController extends Controller
{
    /**
     * Display reseller checkout page.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $user = Auth::guard('admin')->user();

        // Verify reseller
        if (!$user || (!$user->hasRole('reseller') && $user->role !== 'reseller')) {
            Toastr::error('আপনি রিসেলার নন', 'Error');
            return redirect()->route('reseller.dashboard');
        }

        // Check verification status
        if ($user->verification_status !== 'approved') {
            Toastr::error('আপনার একাউন্ট এখনও ভেরিফাই হয়নি। অর্ডার করার জন্য আপনার একাউন্ট ভেরিফাই করা আবশ্যক।', 'Account Not Verified');
            return redirect()->route('reseller.verification.index');
        }

        // Check wallet deposit - প্রথমে ডিপোজিট করতে হবে
        if (($user->wallet_balance ?? 0) <= 0) {
            return redirect()->route('reseller.wallet')->with('deposit_required', true);
        }

        // Check if cart is empty
        if (Cart::instance('shopping')->count() <= 0) {
            Toastr::warning('আপনার কার্ট খালি', 'Warning');
            return redirect()->route('reseller.products.index');
        }

        // Check if items have reseller_price (either in cart options or product table)
        $hasResellerPrice = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            // Check if reseller_price is in cart options
            if (isset($item->options->reseller_price) && $item->options->reseller_price > 0) {
                $hasResellerPrice = true;
                break;
            }
            // Or check if product has reseller_price
            $product = Product::find($item->id);
            if ($product && $product->reseller_price && $product->reseller_price > 0) {
                $hasResellerPrice = true;
                break;
            }
        }

        if (!$hasResellerPrice) {
            Toastr::error('আপনার কার্টে কোনো প্রোডাক্টের রিসেলার প্রাইস নেই', 'Error');
            return redirect()->route('reseller.products.index');
        }

        // Calculate totals
        $subtotal = Cart::instance('shopping')->subtotal();
        $subtotal = str_replace(',', '', $subtotal);
        $subtotal = str_replace('.00', '', $subtotal);
        $subtotal = (float) $subtotal;

        // Check if shipping is required
        $requires_shipping = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = Product::find($item->id);
            if ($product && $product->is_digital != 1) {
                $requires_shipping = true;
                break;
            }
        }

        // Get shipping charges
        $shippingcharge = [];
        $select_charge = null;
        if ($requires_shipping) {
            $shippingcharge = ShippingCharge::where('status', 1)->get();
            $select_charge = ShippingCharge::where('status', 1)->first();
        }

        // Set default shipping charge (same as customer checkout)
        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = ShoppingController::hasAllFreeDeliveryProducts();
        
        if ($hasAllFreeDelivery) {
            Session::put('shipping', 0);
            Session::put('shipping_id', null);
        } elseif ($select_charge && $requires_shipping) {
            Session::put('shipping', $select_charge->amount);
            Session::put('shipping_id', $select_charge->id);
        } else {
            Session::put('shipping', 0);
        }

        // Get payment gateways
        $bkash_gateway = PaymentGateway::where(['status' => 1, 'type' => 'bkash'])->first();
        $shurjopay_gateway = PaymentGateway::where(['status' => 1, 'type' => 'shurjopay'])->first();
        $uddoktapay_gateway = PaymentGateway::where(['status' => 1, 'type' => 'uddoktapay'])->first();
        $aamarpay_gateway = PaymentGateway::where(['status' => 1, 'type' => 'aamarpay'])->first();

        // Check if digital product exists
        $hasDigital = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = Product::find($item->id);
            if ($product && $product->is_digital == 1) {
                $hasDigital = true;
                break;
            }
        }

        // Get shipping from session
        $shipping = Session::get('shipping', 0);
        $discount = Session::get('discount', 0);
        $grandTotal = $subtotal + $shipping - $discount;

        // Calculate total reseller price
        $totalResellerPrice = 0;
        foreach (Cart::instance('shopping')->content() as $cartItem) {
            if (isset($cartItem->options->reseller_price) && $cartItem->options->reseller_price > 0) {
                $totalResellerPrice += ($cartItem->options->reseller_price * $cartItem->qty);
            } else {
                $product = Product::find($cartItem->id);
                if ($product && $product->reseller_price) {
                    $totalResellerPrice += ($product->reseller_price * $cartItem->qty);
                }
            }
        }

        // Calculate advance payment amount
        $advanceAmount = ShoppingController::getCartAdvanceAmount();
        $hasAdvance = $advanceAmount > 0;
        $payableNow = $hasAdvance ? $advanceAmount : $grandTotal;
        $dueAmount = $hasAdvance ? ($grandTotal - $advanceAmount) : 0;

        return view('reseller.checkout', compact(
            'user',
            'shippingcharge',
            'subtotal',
            'shipping',
            'discount',
            'grandTotal',
            'totalResellerPrice',
            'requires_shipping',
            'bkash_gateway',
            'shurjopay_gateway',
            'uddoktapay_gateway',
            'aamarpay_gateway',
            'hasDigital',
            'advanceAmount',
            'hasAdvance',
            'payableNow',
            'dueAmount',
            'hasAllFreeDelivery'
        ));
    }

    /**
     * Save reseller order.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = Auth::guard('admin')->user();

        // Verify reseller
        if (!$user || (!$user->hasRole('reseller') && $user->role !== 'reseller')) {
            Toastr::error('আপনি রিসেলার নন', 'Error');
            return redirect()->route('reseller.dashboard');
        }

        // Check verification status
        if ($user->verification_status !== 'approved') {
            Toastr::error('আপনার একাউন্ট এখনও ভেরিফাই হয়নি। অর্ডার করার জন্য আপনার একাউন্ট ভেরিফাই করা আবশ্যক।', 'Account Not Verified');
            return redirect()->route('reseller.verification.index');
        }

        // Check wallet deposit - প্রথমে ডিপোজিট করতে হবে
        if (($user->wallet_balance ?? 0) <= 0) {
            return redirect()->route('reseller.wallet')->with('deposit_required', true);
        }

        // Check if cart is empty
        if (Cart::instance('shopping')->count() <= 0) {
            Toastr::warning('আপনার কার্ট খালি', 'Warning');
            return redirect()->route('reseller.products.index');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'area' => 'required',
            'payment_method' => 'required|string',
            'custom_price' => 'required|numeric|min:0',
        ]);

        // Check if digital product exists and COD is selected
        $hasDigital = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = Product::find($item->id);
            if ($product && $product->is_digital == 1) {
                $hasDigital = true;
                break;
            }
        }

        if ($hasDigital && $request->payment_method === 'cod') {
            Toastr::error('ডিজিটাল প্রোডাক্টের জন্য Cash On Delivery পাওয়া যায় না, অনুগ্রহ করে অনলাইন পেমেন্ট সিলেক্ট করুন।', 'Failed!');
            return redirect()->back();
        }

        // Check advance payment requirement
        $advanceAmount = ShoppingController::getCartAdvanceAmount();
        $hasAdvance = $advanceAmount > 0;
        
        if ($hasAdvance && in_array($request->payment_method, ['cod'])) {
            Toastr::error('এই অর্ডারে অগ্রিম পেমেন্ট প্রয়োজন। অনুগ্রহ করে অনলাইন পেমেন্ট মেথড সিলেক্ট করুন।', 'Failed!');
            return redirect()->back();
        }

        // Calculate totals
        $subtotal = Cart::instance('shopping')->subtotal();
        $subtotal = str_replace(',', '', $subtotal);
        $subtotal = str_replace('.00', '', $subtotal);
        $subtotal = (float) $subtotal;

        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = ShoppingController::hasAllFreeDeliveryProducts();
        $shippingfee = 0;
        if (!$hasAllFreeDelivery && $request->area && $request->area != 'free_shipping') {
            $shippingArea = ShippingCharge::find($request->area);
            if ($shippingArea) {
                $shippingfee = $shippingArea->amount;
            }
        }
        Session::put('shipping', $shippingfee);

        $discount = Session::get('discount', 0);
        $grandTotal = ($subtotal + $shippingfee) - $discount;

        // Calculate reseller profit
        $totalResellerPrice = 0;
        foreach (Cart::instance('shopping')->content() as $cartItem) {
            $resellerPrice = null;
            if (isset($cartItem->options->reseller_price) && $cartItem->options->reseller_price > 0) {
                $resellerPrice = (float) $cartItem->options->reseller_price;
            } else {
                $product = Product::find($cartItem->id);
                if ($product && $product->reseller_price) {
                    $resellerPrice = (float) $product->reseller_price;
                }
            }
            if ($resellerPrice) {
                $totalResellerPrice += ($resellerPrice * $cartItem->qty);
            }
        }

        $customPrice = (float) $request->custom_price;
        $resellerProfit = $customPrice - $totalResellerPrice;
        // Customer payable amount = custom price + shipping charge
        $customerPayableAmount = $customPrice + $shippingfee;

        // Calculate advance payment
        $advanceAmount = ShoppingController::getCartAdvanceAmount();
        $hasAdvance = $advanceAmount > 0;
        
        // Payment amount logic - if advance exists, only advance amount needs to be paid
        // Otherwise, full customer payable amount
        $payableAmount = $hasAdvance ? $advanceAmount : $customerPayableAmount;

        // Create or get customer
        $customer = Customer::where('phone', $request->phone)->first();
        if (!$customer) {
            $password = rand(111111, 999999);
            $customer = Customer::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'phone' => $request->phone,
                'password' => bcrypt($password),
                'verify' => 1,
                'status' => 'active',
            ]);
        }

        // Create order
        $order = Order::create([
            'invoice_id' => rand(11111, 99999),
            'amount' => $grandTotal,
            'shipping_charge' => $shippingfee,
            'customer_id' => $customer->id,
            'order_status' => 1,
            'note' => $request->note ?? null,
            'order_note' => $request->order_note ?? null,
            'payment_status' => 'pending',
            'coupon_code' => Session::get('coupon_code'),
            'discount' => $discount ?? 0,
            'ip_address' => $request->ip(),
            'user_id' => $user->id, // Reseller user_id
            'reseller_profit' => $resellerProfit,
            'customer_payable_amount' => $customerPayableAmount,
        ]);

        // Create order details
        foreach (Cart::instance('shopping')->content() as $cartItem) {
            $product = Product::find($cartItem->id);
            
            OrderDetails::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->id,
                'product_name' => $cartItem->name,
                'purchase_price' => $product->purchase_price ?? 0,
                'sale_price' => $cartItem->price, // reseller_price
                'product_discount' => 0,
                'product_size' => $cartItem->options->product_size ?? null,
                'product_color' => $cartItem->options->product_color ?? null,
                'variant_price_id' => $cartItem->options->variant_price_id ?? null,
                'qty' => $cartItem->qty,
            ]);
        }

        // Create shipping
        $shippingArea = ShippingCharge::find($request->area);
        Shipping::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'area' => $shippingArea ? $shippingArea->name : 'Free Shipping',
        ]);

        // Create payment
        // For payment gateways, amount will be set after payment confirmation
        // For COD, if advance exists, only advance amount is paid now
        $paymentAmount = in_array($request->payment_method, ['bkash', 'shurjopay', 'uddoktapay', 'aamarpay']) ? 0 : $payableAmount;
        
        $payment = Payment::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'amount' => $paymentAmount,
        ]);

        // Stock reduce
        foreach ($order->orderdetails as $detail) {
            $product = Product::find($detail->product_id);
            if ($product && $product->stock >= $detail->qty) {
                $product->stock -= $detail->qty;
                $product->save();
            }
        }

        // Clear cart
        Cart::instance('shopping')->destroy();
        Session::forget(['shipping', 'discount', 'coupon_code']);

        // Payment gateway redirects - use payable amount (advance or full)
        Session::put('payable_amount', $payableAmount);

        if ($request->payment_method == 'bkash') {
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect('/bkash/checkout-url/create?order_id=' . $order->id);

        } elseif ($request->payment_method == 'shurjopay') {
            $info = [
                'currency'        => "BDT",
                'amount'          => $payableAmount,
                'order_id'        => uniqid(),
                'client_ip'       => $request->ip(),
                'customer_name'   => $request->name,
                'customer_phone'  => $request->phone,
                'email'           => "customer@gmail.com",
                'customer_address'=> $request->address,
                'customer_city'   => $shippingArea ? $shippingArea->name : 'Free Shipping',
                'customer_country'=> "BD",
                'value1'          => $order->id
            ];

            Session::forget('coupon_code');
            Session::forget('discount');

            $sp = new ShurjopayController();
            return $sp->checkout($info);

        } elseif ($request->payment_method == 'uddoktapay') {
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect()->route('uddoktapay.checkout', ['order_id' => $order->id]);

        } elseif ($request->payment_method == 'aamarpay') {
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect()->route('aamarpay.checkout', ['order_id' => $order->id]);

        } else {
            // Cash On Delivery
            // For COD, if order is fully paid (no advance), create digital downloads
            // Otherwise, digital downloads will be created when payment is completed
            if (!$hasAdvance) {
                $this->createDigitalDownloads($order);
            }
            
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect()->route('reseller.order.success', $order->id);
        }
    }

    /**
     * Create digital downloads for digital products after payment is complete.
     *
     * @param Order $order
     * @return void
     */
    private function createDigitalDownloads(Order $order)
    {
        // Load order details with product relationship
        $order->loadMissing('orderdetails.product');

        foreach ($order->orderdetails as $item) {
            $product = $item->product;

            // Check if product is digital and has digital file
            if ($product && $product->is_digital == 1 && $product->digital_file) {
                // Create digital download entry (avoid duplicates)
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
}
