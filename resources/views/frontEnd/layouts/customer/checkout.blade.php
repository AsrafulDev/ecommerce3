@extends('frontEnd.layouts.master')
@section('title', 'Customer Checkout')
@php
    $generalsetting = \App\Models\GeneralSetting::first();
    $pageType = 'checkout';
    $cartItems = [];
    $checkoutValue = 0;
    foreach (Cart::instance('shopping')->content() as $item) {
        $cartItems[] = [
            'item_id' => (string) $item->id,
            'item_name' => $item->name,
            'price' => (float) $item->price,
            'quantity' => (int) $item->qty,
            'item_category' => '',
        ];
        $checkoutValue += $item->price * $item->qty;
    }
    $contentIds = array_map(function ($i) { return $i['item_id']; }, $cartItems);
    $tiktokContents = array_map(function ($i) {
        return [
            'content_id' => $i['item_id'],
            'content_name' => $i['item_name'],
            'quantity' => $i['quantity'],
            'price' => $i['price'],
        ];
    }, $cartItems);
@endphp
@push('dataLayer')
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    event: 'begin_checkout',
    ecommerce: {
        currency: 'BDT',
        value: {{ $checkoutValue }},
        items: @json($cartItems)
    }
});
if (typeof fbq === 'function') {
    fbq('track', 'InitiateCheckout', {
        value: {{ $checkoutValue }},
        currency: 'BDT',
        num_items: {{ count($cartItems) }},
        content_ids: @json($contentIds),
        content_type: 'product'
    });
}
if (typeof ttq !== 'undefined') {
    ttq.track('InitiateCheckout', {
        value: {{ $checkoutValue }},
        currency: 'BDT',
        contents: @json($tiktokContents),
        content_type: 'product'
    });
}
@endpush
@push('css')
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/select2.min.css') }}" />
<style>
    /* ================================================================
       MODERN CHECKOUT STYLES - PROFESSIONAL E-COMMERCE LOOK
    ================================================================ */
    :root {
        --primary-color: #0f3460;
        --secondary-color: #e94560;
        --success-color: #28a745;
        --border-color: #e5e7eb;
        --bg-color: #f8f9fa;
        --text-dark: #1f2937;
        --text-light: #6b7280;
    }

    .checkout-section {
        background-color: var(--bg-color);
        padding: 60px 0;
        font-family: 'Poppins', sans-serif;
    }

    /* --- Card Design --- */
    .checkout-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
        overflow: hidden;
    }

    .checkout-header {
        background: #fff;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .checkout-header i {
        color: var(--secondary-color);
        font-size: 22px;
    }
    .checkout-header h6 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-body-custom {
        padding: 30px;
    }

    /* --- Form Inputs --- */
    .form-group { margin-bottom: 20px; }
    .form-label-custom {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
        display: block;
    }
    .form-control-custom {
        width: 100%;
        height: 50px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 0 16px;
        font-size: 15px;
        color: #333;
        transition: all 0.2s;
        background-color: #fff;
    }
    .form-control-custom:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(15, 52, 96, 0.08);
        outline: none;
    }
    textarea.form-control-custom {
        height: auto;
        padding: 15px;
        line-height: 1.5;
    }

    /* --- Payment Methods (Interactive Box) --- */
    .payment-option-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 2px solid var(--border-color);
        border-radius: 10px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 15px;
        background: #fff;
        position: relative;
    }
    .payment-option-label:hover {
        border-color: #9ca3af;
        background: #f9fafb;
    }
    .payment-option-label input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }
    /* Selected State */
    .payment-option-label:has(input:checked) {
        border-color: var(--primary-color);
        background-color: #f0f5ff;
        box-shadow: 0 0 0 1px var(--primary-color);
    }
    .payment-content {
        display: flex;
        align-items: center;
        gap: 15px;
        width: 100%;
    }
    .pay-logo {
        width: 40px;
        height: 40px;
        object-fit: contain;
    }
    .pay-info strong {
        display: block;
        font-size: 16px;
        color: var(--text-dark);
    }
    .pay-info small {
        font-size: 13px;
        color: var(--text-light);
    }
    .check-circle {
        width: 22px;
        height: 22px;
        border: 2px solid #ccc;
        border-radius: 50%;
        position: relative;
        flex-shrink: 0;
    }
    .payment-option-label input:checked ~ .check-circle {
        border-color: var(--primary-color);
        background: var(--primary-color);
    }
    .payment-option-label input:checked ~ .check-circle::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 8px;
        height: 8px;
        background: #fff;
        border-radius: 50%;
    }

    /* --- Cart Items (Scrollable) --- */
    .sticky-sidebar {
        position: sticky;
        top: 100px;
    }
    .cart-items-scroll {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 5px;
    }
    .cart-items-scroll::-webkit-scrollbar { width: 5px; }
    .cart-items-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
    .cart-items-scroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 5px; }

    .checkout-item {
        display: flex;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px dashed var(--border-color);
        position: relative;
        align-items: center;
    }
    .checkout-item:last-child { border-bottom: none; }
    
    .checkout-pro-img {
        width: 70px;
        height: 70px;
        border-radius: 8px;
        border: 1px solid #eee;
        object-fit: cover;
    }
    .checkout-pro-info h6 {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 5px;
        line-height: 1.4;
    }
    .checkout-pro-info .meta {
        font-size: 12px;
        color: var(--text-light);
    }
    .remove-item-btn {
        color: #ef4444;
        cursor: pointer;
        font-size: 16px;
        position: absolute;
        top: 15px;
        right: 0;
        transition: 0.2s;
    }
    .remove-item-btn:hover { color: #dc2626; transform: scale(1.1); }

    /* Quantity Control */
    .qty-box {
        display: flex;
        align-items: center;
        background: #f3f4f6;
        border-radius: 6px;
        padding: 3px;
        margin-top: 8px;
        width: fit-content;
    }
    .qty-btn {
        width: 28px;
        height: 28px;
        border: none;
        background: #fff;
        border-radius: 4px;
        color: var(--primary-color);
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .qty-btn:hover { background: var(--primary-color); color: #fff; }
    .qty-val {
        width: 30px;
        text-align: center;
        font-size: 14px;
        font-weight: 600;
    }

    /* --- COUPON BOX (LARGER & MODERN) --- */
    .coupon-wrapper {
        background: #f8fafc;
        padding: 20px;
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
    }
    .coupon-group-modern {
        display: flex;
        width: 100%;
        height: 55px; /* Bigger Height */
        border: 2px solid #d1d5db;
        border-radius: 8px;
        overflow: hidden;
        transition: 0.3s;
        background: #fff;
    }
    .coupon-group-modern:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(15, 52, 96, 0.05);
    }
    .coupon-input-modern {
        flex-grow: 1;
        border: none;
        padding: 0 20px;
        font-size: 15px;
        color: #333;
        outline: none;
    }
    .coupon-btn-modern {
        background: var(--text-dark);
        color: #fff;
        border: none;
        padding: 0 30px;
        font-weight: 700;
        font-size: 14px;
        text-transform: uppercase;
        cursor: pointer;
        transition: 0.3s;
    }
    .coupon-btn-modern:hover {
        background: var(--secondary-color);
    }

    /* --- Totals Area --- */
    .summary-totals {
        padding: 24px;
        background: #fff;
    }
    .total-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 15px;
        color: var(--text-dark);
    }
    .total-row.final {
        border-top: 2px dashed #e5e7eb;
        margin-top: 15px;
        padding-top: 15px;
        font-size: 20px;
        font-weight: 800;
        color: var(--primary-color);
    }
    
    /* Advance/Due Alert */
    .advance-alert {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        text-align: center;
    }

    /* --- Submit Button --- */
    .btn-place-order {
        background: var(--secondary-color);
        color: #fff;
        width: 100%;
        border: none;
        padding: 18px;
        border-radius: 10px;
        font-size: 17px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: 0.3s;
        box-shadow: 0 10px 25px rgba(233, 69, 96, 0.3);
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
    .btn-place-order:hover {
        background: var(--primary-color);
        transform: translateY(-2px);
    }

    /* --- Responsive Fixes --- */
    @media (max-width: 991px) {
        .cus-order-2 { order: 2; }
        .cust-order-1 { order: 1; margin-bottom: 30px; }
        .mobile-submit-btn { display: block !important; margin-top: 25px; }
        .desktop-submit-btn { display: none !important; }
    }
    @media (min-width: 992px) {
        .mobile-submit-btn { display: none !important; }
        .desktop-submit-btn { display: block !important; }
    }
</style>
@endpush

@section('content')
<section class="checkout-section">
    @php
        // ==============================================================
        //  PHP LOGIC: CART, SHIPPING, DISCOUNT, ADVANCE (UNCHANGED)
        // ==============================================================
        $subtotal = Cart::instance('shopping')->subtotal();
        $subtotal = str_replace(',', '', $subtotal);
        $subtotal = str_replace('.00', '', $subtotal);
        $subtotal = (float) $subtotal;

        // ✅ শিপিং লজিক চেক
        $requires_shipping = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = \App\Models\Product::find($item->id);
            if ($product && $product->is_digital != 1) {
                $requires_shipping = true;
                break;
            }
        }

        // ✅ শিপিং চার্জ সেট
        // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();
        
        if ($requires_shipping && !$hasAllFreeDelivery) {
            $shipping = Session::get('shipping') ? Session::get('shipping') : 0;
        } else {
            $shipping = 0;
            Session::put('shipping', 0);
        }

        $discount = Session::get('discount', 0);
        // ⭐ Grand Total Calculation - Free delivery হলে shipping charge 0
        $grand_total = $subtotal + $shipping - $discount;

        // ✅ JS ডেটা অ্যারে
        $cartItemsForJs = [];
        $hasDigital = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $p = \App\Models\Product::find($item->id);
            if ($p && $p->is_digital == 1) { $hasDigital = true; }
            $cartItemsForJs[] = [
                'id'    => $item->id,
                'name'  => $item->name,
                'qty'   => $item->qty,
                'price' => (float) $item->price,
                'image' => asset($item->options->image ?? ''),
                'link'  => isset($item->options->slug) ? url('/product/' . $item->options->slug) : '#',
                'is_digital' => (int) ($p->is_digital ?? 0),
                'free_delivery' => (int) ($p->free_delivery ?? 0),
            ];
        }

        // ✅ Advance Logic
        $advance_amount = \App\Http\Controllers\Frontend\ShoppingController::getCartAdvanceAmount();
        $hasAdvance     = $advance_amount > 0 ? true : false;
        $payable_now    = $hasAdvance ? $advance_amount : $grand_total;
        $due_amount     = $hasAdvance ? ($grand_total - $advance_amount) : 0;
    @endphp

    <div class="container">
        {{-- মেইন ফর্ম --}}
        <form id="checkout-form" action="{{ route('customer.ordersave') }}" method="POST" data-parsley-validate="">
            @csrf
            
            <div class="row">
                
                {{-- LEFT COLUMN: Shipping & Payment --}}
                <div class="col-lg-7 col-md-12 cus-order-2">
                    
                    {{-- 1. SHIPPING INFO CARD --}}
                    <div class="checkout-card">
                        <div class="checkout-header">
                            <i class="fas fa-truck-moving"></i>
                            <h6>শিপিং এবং বিলিং তথ্য</h6>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">আপনার নাম *</label>
                                        <input type="text" name="name" class="form-control-custom" 
                                            value="{{ Auth::guard('customer')->user()->name ?? old('name') }}" placeholder="সম্পূর্ণ নাম লিখুন" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">{{ __('Mobile Number') }} *</label>
                                        <input type="text" name="phone" class="form-control-custom" minlength="11" maxlength="11" pattern="0[0-9]+" 
                                            value="{{ Auth::guard('customer')->user()->phone ?? old('phone') }}" placeholder="017xxxxxxxx" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">সম্পূর্ণ ঠিকানা *</label>
                                        <input type="text" name="address" class="form-control-custom" 
                                            value="{{ Auth::guard('customer')->user()->address ?? old('address') }}" placeholder="বাসা নং, রোড নং, এলাকা, জেলা" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">ডেলিভারি এরিয়া *</label>
                                        @if($requires_shipping)
                                            <select id="area" class="form-control-custom select2" name="area" required>
                                                <option value="">এরিয়া নির্বাচন করুন...</option>
                                                @foreach ($shippingcharge as $value)
                                                    <option value="{{ $value->id }}" data-charge="{{ $value->amount }}"
                                                        {{ Session::get('shipping_id') == $value->id ? 'selected' : '' }}>
                                                        {{ $value->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="form-control-custom" value="Digital Product (No Shipping Charge)" readonly disabled style="background:#f3f4f6">
                                            <input type="hidden" name="area" value="free_shipping"> 
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">অর্ডার নোট (ঐচ্ছিক)</label>
                                        <textarea name="order_note" id="order_note" class="form-control-custom" rows="2" style="height:auto; resize:none;" 
                                            placeholder="ডেলিভারি সম্পর্কে বিশেষ কিছু বলার থাকলে লিখুন...">{{ $order_note ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. PAYMENT METHOD CARD --}}
                    

{{-- 2. PAYMENT METHOD CARD --}}
<div class="checkout-card">
    <div class="checkout-header">
        <i class="fas fa-wallet"></i>
        <h6>পেমেন্ট মেথড নির্বাচন করুন</h6>
    </div>
    <div class="card-body-custom">
        
        @if($hasAdvance)
            <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-left: 5px solid #ffc107 !important; background-color: #fff8e1;">
                <div class="d-flex gap-3 align-items-center">
                    <i class="fas fa-exclamation-triangle text-warning fs-4"></i>
                    <div>
                        <strong>অগ্রিম পেমেন্ট প্রয়োজন!</strong>
                        <p class="mb-0 small">এই অর্ডারে <b>৳ {{ number_format($advance_amount,2) }}</b> অগ্রিম পেমেন্ট করতে হবে।</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Payment Options List --}}
        <div class="payment-options-list">
            
            {{-- COD Option --}}
            @if(!$hasDigital && !$hasAdvance)
                <label class="payment-option-label">
                    <input type="radio" name="payment_method" value="cod" checked required>
                    <div class="payment-content">
                        <div class="text-center" style="width: 40px;"><i class="fas fa-truck text-success fs-2"></i></div>
                        <div class="pay-info">
                            <strong>{{ __('Cash On Delivery') }}</strong>
                            <small>পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন</small>
                        </div>
                    </div>
                    <div class="check-circle"></div>
                </label>
            @endif

            {{-- Bkash --}}
            @if($bkash_gateway)
                <label class="payment-option-label">
                    {{-- required যুক্ত করা হয়েছে --}}
                    <input type="radio" name="payment_method" value="bkash" required> 
                    <div class="payment-content">
                        <img src="{{ asset('public/frontEnd/images/bkash.svg') }}" class="pay-logo" alt="bKash">
                        <div class="pay-info">
                            <strong>{{ __('bKash Payment') }}</strong>
                            <small>বিকাশ অ্যাপ বা গেটওয়ে দ্বারা পেমেন্ট</small>
                        </div>
                    </div>
                    <div class="check-circle"></div>
                </label>
            @endif

            {{-- ShurjoPay --}}
            @if($shurjopay_gateway)
                <label class="payment-option-label">
                    {{-- required যুক্ত করা হয়েছে --}}
                    <input type="radio" name="payment_method" value="shurjopay" required>
                    <div class="payment-content">
                        <img src="{{ asset('public/frontEnd/images/shurjoPay.png') }}" class="pay-logo" alt="ShurjoPay">
                        <div class="pay-info">
                            <strong>{{ __('Online Payment') }}</strong>
                            <small>{{ __('ShurjoPay (Card/Mobile Banking)') }}</small>
                        </div>
                    </div>
                    <div class="check-circle"></div>
                </label>
            @endif

            {{-- {{ __('UddoktaPay') }} --}}
            @if($uddoktapay_gateway)
                <label class="payment-option-label">
                    {{-- required যুক্ত করা হয়েছে --}}
                    <input type="radio" name="payment_method" value="uddoktapay" required>
                    <div class="payment-content">
                        <img src="{{ asset('public/frontEnd/images/uddokta.png') }}" class="pay-logo" alt="{{ __('UddoktaPay') }}">
                        <div class="pay-info">
                            <strong>{{ __('UddoktaPay') }}</strong>
                            <small>মোবাইল ব্যাংকিং পেমেন্ট গেটওয়ে</small>
                        </div>
                    </div>
                    <div class="check-circle"></div>
                </label>
            @endif

            {{-- {{ __('aamarPay') }} --}}
            @if($aamarpay_gateway)
                <label class="payment-option-label">
                    <input type="radio" name="payment_method" value="aamarpay" required>
                    <div class="payment-content">
                        <img src="{{ asset('public/frontEnd/images/aamarpay.png') }}" class="pay-logo" alt="{{ __('aamarPay') }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="pay-info" style="display: none;">
                            <i class="fas fa-credit-card text-primary fs-4"></i>
                        </div>
                        <div class="pay-info">
                            <strong>{{ __('aamarPay') }}</strong>
                            <small>কার্ড ও মোবাইল ব্যাংকিং পেমেন্ট</small>
                        </div>
                    </div>
                    <div class="check-circle"></div>
                </label>
            @endif

        </div>
        {{-- Error message placeholder --}}
        <div id="payment-error" class="text-danger fw-bold mt-2 text-center" style="display:none;">
            <i class="fas fa-exclamation-circle"></i> অনুগ্রহ করে একটি পেমেন্ট মেথড সিলেক্ট করুন।
        </div>
    </div>
</div>

                    {{-- MOBILE SUBMIT BUTTON (Only Visible on Mobile) --}}
                    <div class="mobile-submit-btn">
                        <button type="submit" class="btn-place-order">
                            অর্ডার নিশ্চিত করুন <i class="fas fa-arrow-right"></i>
                        </button>
                        <div class="text-center text-muted small mt-3">
                            <i class="fas fa-shield-alt"></i> ১০০% নিরাপদ এবং সিকিউর চেকআউট
                        </div>
                    </div>

                </div>

                {{-- RIGHT SIDE: Order Summary --}}
                <div class="col-lg-5 col-md-12 cust-order-1">
                    <div class="sticky-sidebar">
                        <div class="checkout-card">
                            <div class="checkout-header">
                                <i class="fas fa-shopping-bag"></i>
                                <h6>অর্ডার সামারি ({{ Cart::instance('shopping')->count() }})</h6>
                            </div>
                            
                            <div class="card-body-custom p-0">
                                {{-- Products List (Scrollable) --}}
                                <div class="cart-items-scroll px-4 pt-3 cartlist" style="max-height: 400px; overflow-y: auto;">
                                    @foreach (Cart::instance('shopping')->content() as $value)
                                        <div class="checkout-item">
                                            {{-- Remove --}}
                                            <a class="remove-item-btn cart_remove" data-id="{{ $value->rowId }}" title="Remove Item">
                                                <i class="far fa-trash-alt"></i>
                                            </a>

                                            {{-- Image --}}
                                            <a href="{{ route('product', $value->options->slug) }}">
                                                <img src="{{ asset($value->options->image) }}" class="checkout-pro-img">
                                            </a>

                                            {{-- Info --}}
                                            <div class="checkout-pro-info flex-grow-1">
                                                <a href="{{ route('product', $value->options->slug) }}" class="text-dark text-decoration-none">
                                                    <h6>{{ Str::limit($value->name, 35) }}</h6>
                                                </a>
                                                <div class="meta text-muted small mb-1">
                                                    @if($value->options->product_size) Size: {{$value->options->product_size}} @endif
                                                    @if($value->options->product_color) | Color: {{$value->options->product_color}} @endif
                                                </div>
                                                
                                                {{-- Price & Qty --}}
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="qty-box checkout-qty" data-rowid="{{ $value->rowId }}">
                                                        <button type="button" class="qty-btn minus"><i class="fas fa-minus" style="font-size:10px;"></i></button>
                                                        <span class="qty-val qty-value">{{ $value->qty }}</span>
                                                        <button type="button" class="qty-btn plus"><i class="fas fa-plus" style="font-size:10px;"></i></button>
                                                    </div>
                                                    <div class="fw-bold text-dark">৳ {{ number_format($value->price * $value->qty, 0) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- COUPON SECTION --}}
<div class="coupon-wrapper">
    @if(!Session::has('coupon_code'))
        <div class="coupon-group-modern">
            {{-- ভিজ্যুয়াল ইনপুট (এটি কোনো ফর্মের অংশ নয়, শুধু ডাটা নেওয়ার জন্য) --}}
            <input type="text" id="coupon_input" class="coupon-input-modern" placeholder="কুপন কোড আছে? এখানে লিখুন...">
            <button type="button" class="coupon-btn-modern" onclick="submit{{ __('Coupon') }}()">{{ __('APPLY') }}</button>
        </div>
    @else
        <div class="alert alert-success d-flex justify-content-between align-items-center m-0 py-3 px-3 border-0 rounded shadow-sm">
            <span><i class="fas fa-check-circle"></i> {{ __('Coupon') }} <b>{{ Session::get('coupon_code') }}</b> {{ __('Applied!') }}</span>
            <a href="{{ route('coupon.remove') }}" class="text-danger fw-bold text-decoration-none px-2">{{ __('REMOVE') }}</a>
        </div>
    @endif
</div>

                                {{-- Calculation --}}
                                <div class="summary-totals">
                                    <div class="total-row"><span>সাবটোটাল</span> <span id="subtotalAmount">৳ {{ number_format($subtotal, 2) }}</span></div>
                                    <div class="total-row"><span>ডেলিভারি চার্জ</span> <span id="shippingAmount">৳ {{ number_format($shipping, 2) }}</span></div>
                                    @if($discount > 0)
                                        <div class="total-row text-success"><span>{{ __('{{ __('Coupon') }} Discount') }}</span> <span id="discountAmount">- ৳ {{ number_format($discount, 2) }}</span></div>
                                    @endif
                                    <div class="total-row final"><span>{{ __('Total') }}</span> <span id="grandTotalAmount">৳ {{ number_format($grand_total, 2) }}</span></div>

                                    @if($hasAdvance)
                                        <div class="advance-alert">
                                            <div class="total-row text-success fw-bold"><span>অগ্রিম (পেইড):</span> <span id="advanceAmountCell">৳ {{ number_format($advance_amount,2) }}</span></div>
                                            <div class="total-row text-danger fw-bold mb-0"><span>বাকি (ডিউ):</span> <span id="dueAmountCell">৳ {{ number_format($due_amount,2) }}</span></div>
                                        </div>
                                    @endif
                                </div>

                                {{-- DESKTOP SUBMIT BUTTON (Only Visible on Desktop) --}}
                                <div class="desktop-submit-btn p-4">
                                    <button type="submit" class="btn-place-order">
                                        অর্ডার নিশ্চিত করুন <i class="fas fa-check-circle"></i>
                                    </button>
                                    <div class="text-center text-muted small mt-3">
                                        <i class="fas fa-lock"></i> ১০০% নিরাপদ চেকআউট প্রসেস
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</section>
@endsection

@push('script')
<script src="{{ asset('public/frontEnd/js/select2.min.js') }}"></script>

{{-- ============================================================== --}}
{{--  JAVASCRIPT LOGIC (EXACT COPY - NO FUNCTIONALITY {{ __('REMOVE') }}D)  --}}
{{-- ============================================================== --}}
</form> 
        
        {{-- ========================================================= --}}
        {{--  🔴 এই অংশটুকু আপনার কোডে মিসিং ছিল, তাই কাজ করছিল না   --}}
        {{-- ========================================================= --}}
        
        {{-- হিডেন কুপন ফর্ম (এটি অবশ্যই মেইন ফর্মের বাইরে থাকতে হবে) --}}
        <form id="coupon-form" action="{{ route('coupon.apply') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="coupon_code" id="hidden_coupon_code">
        </form>

        {{-- কুপন সাবমিট করার জাভাস্ক্রিপ্ট --}}
        <script>
            function submit{{ __('Coupon') }}() {
                var code = document.getElementById('coupon_input').value;
                if(code) {
                    document.getElementById('hidden_coupon_code').value = code;
                    document.getElementById('coupon-form').submit();
                } else {
                    // টোস্টার থাকলে টোস্টার, নাহলে এলার্ট
                    if(typeof toastr !== 'undefined') {
                        toastr.error('Please enter a coupon code');
                    } else {
                        alert('Please enter a coupon code');
                    }
                }
            }
        </script>
<script>
    // গ্লোবাল ভেরিয়েবল (Global Variables)
    let incompleteOrderTimer;
    let isSubmitting = false; // অর্ডার সাবমিট হচ্ছে কিনা তা চেক করার জন্য

    $(document).ready(function() {
        // Select2 Initialize
        $(".select2").select2({ width: '100%' });

        // ==========================================
        // 1. CART LOGIC ({{ __('REMOVE') }}, INCREASE, DECREASE)
        // ==========================================
        
        // Remove Item
        $(document).on('click', '.cart_remove', function(e) {
            e.preventDefault(); e.stopImmediatePropagation();
            var id = $(this).data("id");
            if (id) {
                $("#loading").show();
                $.ajax({
                    type: "GET",
                    url: "{{ route('cart.remove') }}",
                    data: { id: id },
                    success: function() { toastr.success('Success', 'Item removed'); window.location.reload(); },
                    error: function() { window.location.reload(); }
                });
            }
        });

        // Quantity Increment
        $('.checkout-qty .plus').on('click', function() {
            var rowId = $(this).closest('.checkout-qty').data('rowid');
            $("#loading").show();
            $.get("{{ route('cart.increment') }}", { id: rowId }, function() { window.location.reload(); });
        });

        // Quantity Decrement
        $('.checkout-qty .minus').on('click', function() {
            var rowId = $(this).closest('.checkout-qty').data('rowid');
            $("#loading").show();
            $.get("{{ route('cart.decrement') }}", { id: rowId }, function() { window.location.reload(); });
        });

        // ==========================================
        // 2. SHIPPING & TOTAL CALCULATION
        // ==========================================
        
        const baseSubtotal = parseFloat("{{ $subtotal ?? 0 }}");
        const baseDiscount = parseFloat("{{ $discount ?? 0 }}");
        const advanceAmount = parseFloat("{{ $advance_amount ?? 0 }}");
        const hasAdvance = @json($hasAdvance ?? false);
        const requiresShipping = @json($requires_shipping ?? false);
        const cartItems = @json($cartItemsForJs ?? []);
        const hasAllFreeDelivery = @json($hasAllFreeDelivery ?? false);

        // ⭐ Free Delivery Check Function
        function checkFreeDelivery() {
            // Check if all physical products have free_delivery = 1
            let allFreeDelivery = true;
            for (let i = 0; i < cartItems.length; i++) {
                let item = cartItems[i];
                // Skip digital products
                if (item.is_digital == 1) {
                    continue;
                }
                // If any physical product doesn't have free_delivery, return false
                if (item.free_delivery != 1) {
                    allFreeDelivery = false;
                    break;
                }
            }
            return allFreeDelivery;
        }

        // এরিয়া পরিবর্তন হলে শিপিং চার্জ আপডেট
        $('#area').on('change', function () {
            var selectedCharge = parseFloat($('option:selected', this).attr('data-charge')) || 0;
            
            // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
            var isFreeDelivery = checkFreeDelivery();
            var shippingCharge = isFreeDelivery ? 0 : selectedCharge;
            
            var grandTotal = baseSubtotal + shippingCharge - baseDiscount;
            var dueAmount = hasAdvance ? (grandTotal - advanceAmount) : 0;

            // টেক্সট আপডেট
            $('#shippingAmount').text('৳ ' + shippingCharge.toFixed(2));
            $('#grandTotalAmount').text('৳ ' + grandTotal.toFixed(2));
            
            if (hasAdvance) {
                $('#dueAmountCell').text('৳ ' + dueAmount.toFixed(2));
                $('#dueAmountText').text(dueAmount.toFixed(2));
            }

            // ব্যাকএন্ডে শিপিং চার্জ সেট করা (free delivery হলে 0 পাঠাবে)
            if (isFreeDelivery) {
                $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' });
            } else {
                $.get('{{ route("shipping.charge") }}', { id: $(this).val() });
            }
            
            // এরিয়া চেঞ্জ করলেও ইনকমপ্লিট অর্ডার আপডেট হবে (যদি নাম/ফোন/ঠিকানা থাকে)
            saveIncompleteOrder();
        });

        // ⭐ Page Load হওয়ার সময় Free Delivery Check করে Initial Shipping Charge Set করা
        $(document).ready(function() {
            // Check free delivery on page load
            var isFreeDeliveryOnLoad = hasAllFreeDelivery || checkFreeDelivery();
            
            if (isFreeDeliveryOnLoad) {
                var shippingCharge = 0;
                var grandTotal = baseSubtotal + shippingCharge - baseDiscount;
                var dueAmount = hasAdvance ? (grandTotal - advanceAmount) : 0;

                // টেক্সট আপডেট
                $('#shippingAmount').text('৳ ' + shippingCharge.toFixed(2));
                $('#grandTotalAmount').text('৳ ' + grandTotal.toFixed(2));
                
                if (hasAdvance) {
                    $('#dueAmountCell').text('৳ ' + dueAmount.toFixed(2));
                    $('#dueAmountText').text(dueAmount.toFixed(2));
                }

                // ব্যাকএন্ডে শিপিং চার্জ 0 সেট করা
                $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' });
            } else {
                // Free delivery না হলে current shipping charge use করবে
                var currentShipping = parseFloat($('#shippingAmount').text().replace(/[৳,\s]/g, '').trim()) || 0;
                var grandTotal = baseSubtotal + currentShipping - baseDiscount;
                var dueAmount = hasAdvance ? (grandTotal - advanceAmount) : 0;
                
                // Grand total update
                $('#grandTotalAmount').text('৳ ' + grandTotal.toFixed(2));
                
                if (hasAdvance) {
                    $('#dueAmountCell').text('৳ ' + dueAmount.toFixed(2));
                    $('#dueAmountText').text(dueAmount.toFixed(2));
                }
            }
        });

        // ==========================================
        // 3. INCOMPLETE ORDER LOGIC (MAIN REQUEST)
        // ==========================================

        function saveIncompleteOrder() {
            // ১. যদি ইউজার অর্ডার সাবমিট করে ফেলে, তাহলে আর সেভ হবে না
            if (isSubmitting) return;

            // পুরনো টাইমার ক্লিয়ার করা (যাতে বারবার রিকোয়েস্ট না যায়)
            if (incompleteOrderTimer) clearTimeout(incompleteOrderTimer);

            // ২ সেকেন্ড পর চেক করবে
            incompleteOrderTimer = setTimeout(() => {
                var name = $('input[name="name"]').val();
                var phone = $('input[name="phone"]').val();
                var address = $('input[name="address"]').val();
                
                // ২. লজিক: নাম, ফোন এবং ঠিকানা - তিনটিই থাকতে হবে। 
                // যদি একাও মিসিং থাকে, তাহলে ইনকমপ্লিট অর্ডার সেভ হবে না।
                if (!name || !phone || !address) {
                    return; 
                }

                // ক্যালকুলেশন
                var selectedCharge = parseFloat($('#area option:selected').attr('data-charge')) || 0;
                // ⭐ Free Delivery Check
                var isFreeDelivery = checkFreeDelivery();
                var shippingCharge = isFreeDelivery ? 0 : selectedCharge;
                var total = (baseSubtotal + shippingCharge - baseDiscount).toFixed(2);

                // ডাটা পাঠানো
                $.ajax({
                    url: '{{ route("incomplete.order.store") }}',
                    type: 'POST',
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: JSON.stringify({
                        name: name,
                        phone: phone,
                        address: address,
                        items: cartItems,
                        total_amount: total
                    })
                });
            }, 2000); // ২ সেকেন্ড ডিলে
        }

        // ফর্মের যেকোনো ইনপুট চেঞ্জ হলে এই ফাংশন কল হবে
        $('#checkout-form input, #checkout-form select, #checkout-form textarea').on('input change', function() {
             if($(this).attr('name') !== 'payment_method') {
                 saveIncompleteOrder();
             }
        });

        // পেজ ছেড়ে যাওয়ার সময় ইনকমপ্লিট অর্ডার সেভ (যাতে অ্যাডমিন প্যানেলে দেখায়)
        function saveIncompleteOrderSync() {
            if (isSubmitting) return;
            var name = $('input[name="name"]').val();
            var phone = $('input[name="phone"]').val();
            var address = $('input[name="address"]').val();
            if (!name || !phone || !address) return;
            var selectedCharge = parseFloat($('#area option:selected').attr('data-charge')) || 0;
            var isFreeDelivery = typeof checkFreeDelivery === 'function' ? checkFreeDelivery() : false;
            var shippingCharge = isFreeDelivery ? 0 : selectedCharge;
            var total = (baseSubtotal + shippingCharge - baseDiscount).toFixed(2);
            var payload = JSON.stringify({
                name: name, phone: phone, address: address,
                items: cartItems, total_amount: total,
                _token: $('meta[name="csrf-token"]').attr('content')
            });
            navigator.sendBeacon('{{ route("incomplete.order.store") }}', new Blob([payload], {type: 'application/json'}));
        }
        $(window).on('beforeunload pagehide', function() { saveIncompleteOrderSync(); });

        // ==========================================
        // 4. FORM SUBMISSION & VALIDATION
        // ==========================================

        $('#checkout-form').on('submit', function(e) {
            // পেমেন্ট মেথড চেক
            var paymentMethod = $('input[name="payment_method"]:checked').val();
            
            if (!paymentMethod) {
                e.preventDefault();
                toastr.error('অর্ডার সম্পন্ন করতে পেমেন্ট মেথড নির্বাচন করুন।', 'Error');
                $('#payment-error').show();
                $('html, body').animate({ scrollTop: $(".checkout-card .fa-wallet").offset().top - 150 }, 500);
                $('.btn-place-order').prop('disabled', false);
                return false;
            } else {
                $('#payment-error').hide();

                // ৩. অর্ডার সাবমিট হচ্ছে, তাই ইনকমপ্লিট টাইমার বন্ধ করে দেওয়া হলো
                isSubmitting = true; 
                if(incompleteOrderTimer) {
                    clearTimeout(incompleteOrderTimer);
                }
                
                // ফর্ম সাবমিট হতে দিন...
            }
        });

        // পেমেন্ট সিলেক্ট করলে এরর হাইড হবে
        $('input[name="payment_method"]').on('change', function() {
            $('#payment-error').hide();
        });
    });
</script>
{{-- 🔹 GA4 + Facebook Pixel Tracking for Checkout --}}
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];

    (function () {
        const items        = @json($cartItemsForJs);
        const hasAdvance   = @json($hasAdvance);
        const advanceAmount= parseFloat("{{ $advance_amount }}") || 0;
        const grandTotal   = parseFloat("{{ $grand_total }}") || 0;
        const payableNow   = hasAdvance ? advanceAmount : grandTotal;
        const coupon       = @json(Session::get('coupon_code', null));

        const ga4Items = items.map(function (item, index) {
            return {
                item_id: String(item.id),
                item_name: item.name,
                quantity: Number(item.qty),
                price: Number(item.price),
                index: index
            };
        });

        // GA4: begin_checkout
        if (ga4Items.length) {
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                event: "begin_checkout",
                ecommerce: {
                    currency: "BDT",
                    value: payableNow,
                    coupon: coupon,
                    items: ga4Items
                }
            });
        }

        // Facebook Pixel: InitiateCheckout
        if (typeof fbq === "function" && items.length) {
            fbq("track", "InitiateCheckout", {
                value: payableNow,
                currency: "BDT",
                num_items: items.length,
                content_ids: items.map(function(i){ return i.id; }),
                contents: items.map(function(i){
                    return {id: i.id, quantity: i.qty, item_price: i.price};
                }),
                coupon: coupon || undefined
            });
        }

        // On form submit: GA4 add_payment_info + Pixel AddPaymentInfo
        document.addEventListener("DOMContentLoaded", function () {
            var form = document.getElementById("checkout-form");
            if (!form) return;

            form.addEventListener("submit", function () {
                var paymentInput  = form.querySelector('input[name="payment_method"]:checked');
                var paymentMethod = paymentInput ? paymentInput.value : null;

                // GA4 add_payment_info
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: "add_payment_info",
                    payment_type: paymentMethod,
                    ecommerce: {
                        currency: "BDT",
                        value: payableNow,
                        coupon: coupon,
                        items: ga4Items
                    }
                });

                // Facebook Pixel: AddPaymentInfo
                if (typeof fbq === "function" && items.length) {
                    fbq("track", "AddPaymentInfo", {
                        value: payableNow,
                        currency: "BDT",
                        payment_method: paymentMethod,
                        num_items: items.length,
                        content_ids: items.map(function(i){ return i.id; })
                    });
                }
            });
        });
    })();

</script>
@endpush