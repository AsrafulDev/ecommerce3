@extends('frontEnd.layouts.master')
@section('title', '{{ __('Customer') }} {{ __('Check') }}out')
@php
    $generalsetting = \App\Models\GeneralSetting::first();
    $pageType = 'checkout';
    $cart{{ __('{{ __('Item') }}s') }} = [];
    $checkout{{ __('Value') }} = 0;
    foreach ({{ __('Cart') }}::instance('shopping')->content() as $item) {
        $cart{{ __('{{ __('Item') }}s') }}[] = [
            'item_id' => (string) $item->id,
            'item_name' => $item->name,
            'price' => (float) $item->price,
            'quantity' => (int) $item->qty,
            'item_category' => '',
        ];
        $checkout{{ __('Value') }} += $item->price * $item->qty;
    }
    $contentIds = array_map(function ($i) { return $i['item_id']; }, $cart{{ __('{{ __('Item') }}s') }});
    $tiktokContents = array_map(function ($i) {
        return [
            'content_id' => $i['item_id'],
            'content_name' => $i['item_name'],
            'quantity' => $i['quantity'],
            'price' => $i['price'],
        ];
    }, $cart{{ __('{{ __('Item') }}s') }});
@endphp
@push('dataLayer')
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    event: 'begin_checkout',
    ecommerce: {
        currency: 'BDT',
        value: {{ $checkout{{ __('Value') }} }},
        items: @json($cart{{ __('{{ __('Item') }}s') }})
    }
});
if (typeof fbq === 'function') {
    fbq('track', 'Initiate{{ __('Check') }}out', {
        value: {{ $checkout{{ __('Value') }} }},
        currency: 'BDT',
        num_items: {{ count($cart{{ __('{{ __('Item') }}s') }}) }},
        content_ids: @json($contentIds),
        content_type: 'product'
    });
}
if (typeof ttq !== 'undefined') {
    ttq.track('Initiate{{ __('Check') }}out', {
        value: {{ $checkout{{ __('Value') }} }},
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
        font-size: {{ __('14px') }};
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

    /* --- {{ __('Payment {{ __('Method') }}') }}s (Interactive Box) --- */
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

    /* --- {{ __('Cart') }} {{ __('{{ __('Item') }}s') }} ({{ __('Scrollable') }}) --- */
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
        font-size: {{ __('14px') }};
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
        font-size: {{ __('14px') }};
        text-transform: uppercase;
        cursor: pointer;
        transition: 0.3s;
    }
    .coupon-btn-modern:hover {
        background: var(--secondary-color);
    }

    /* --- {{ __('Total') }}s {{ __('Area') }} --- */
    .summary-{{ __('total') }}s {
        padding: 24px;
        background: #fff;
    }
    .{{ __('total') }}-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 15px;
        color: var(--text-dark);
    }
    .{{ __('total') }}-row.final {
        border-top: 2px dashed #e5e7eb;
        margin-top: 15px;
        padding-top: 15px;
        font-size: 20px;
        font-weight: 800;
        color: var(--primary-color);
    }
    
    /* Advance/{{ __('Due') }} Alert */
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
        .mobile-{{ __('submit') }}-btn { display: block !important; margin-top: 25px; }
        .desktop-{{ __('submit') }}-btn { display: none !important; }
    }
    @media (min-width: 992px) {
        .mobile-{{ __('submit') }}-btn { display: none !important; }
        .desktop-{{ __('submit') }}-btn { display: block !important; }
    }
</style>
@endpush

@section('content')
<section class="checkout-section">
    @php
        // ==============================================================
        //  {{ __('PHP') }} LOGIC: CART, SHIPPING, DISCOUNT, ADVANCE (UNCHANGED)
        // ==============================================================
        $sub{{ __('total') }} = {{ __('Cart') }}::instance('shopping')->sub{{ __('total') }}();
        $sub{{ __('total') }} = str_replace(',', '', $sub{{ __('total') }});
        $sub{{ __('total') }} = str_replace('.00', '', $sub{{ __('total') }});
        $sub{{ __('total') }} = (float) $sub{{ __('total') }};

        // ✅ শিপিং লজিক চেক
        $requires_shipping = false;
        foreach ({{ __('Cart') }}::instance('shopping')->content() as $item) {
            $product = \App\Models\{{ __('Product') }}::find($item->{{ __('id)') }};
            if ($product && $product->is_digital != 1) {
                $requires_shipping = true;
                break;
            }
        }

        // ✅ শিপিং চার্জ সেট
        // ⭐ {{ __('Free Delivery') }} {{ __('Check') }} - যদি সব {{ __('{{ __('Product') }}s') }} free delivery eligible {{ __('bn_290a7f61') }}, shipping charge 0
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\{{ __('Shop') }}pingController::hasAllFreeDelivery{{ __('Product') }}s();
        
        if ($requires_shipping && !$hasAllFreeDelivery) {
            $shipping = {{ __('Session') }}::get('shipping') ? {{ __('Session') }}::get('shipping') : 0;
        } else {
            $shipping = 0;
            {{ __('Session') }}::put('shipping', 0);
        }

        $discount = {{ __('Session') }}::get('discount', 0);
        // ⭐ {{ __('Grand {{ __('Total') }}') }} Calculation - Free delivery হলে shipping charge 0
        $grand_{{ __('total') }} = $sub{{ __('total') }} + $shipping - $discount;

        // ✅ JS ডেটা অ্যারে
        $cart{{ __('{{ __('Item') }}s') }}ForJs = [];
        $hasDigital = false;
        foreach ({{ __('Cart') }}::instance('shopping')->content() as $item) {
            $p = \App\Models\{{ __('Product') }}::find($item->{{ __('id)') }};
            if ($p && $p->is_digital == 1) { $hasDigital = true; }
            $cart{{ __('{{ __('Item') }}s') }}ForJs[] = [
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
        $advance_amount = \App\Http\Controllers\Frontend\{{ __('Shop') }}pingController::get{{ __('Cart') }}Advance{{ __('Amount') }}();
        $hasAdvance     = $advance_amount > 0 ? true : false;
        $payable_now    = $hasAdvance ? $advance_amount : $grand_{{ __('total') }};
        $due_amount     = $hasAdvance ? ($grand_{{ __('total') }} - $advance_amount) : 0;
    @endphp

    <div class="container">
        {{-- মেইন ফর্ম --}}
        <form id="checkout-form" action="{{ route('customer.ordersave') }}" method={{ __('"{{ __('POST') }}"') }} data-parsley-validate="">
            @csrf
            
            <div class="row">
                
                {{-- LEFT COLUMN: {{ __('Shipping') }} & Payment --}}
                <div class="col-lg-7 col-md-12 cus-order-2">
                    
                    {{-- 1. SHIPPING INFO CARD --}}
                    <div class="checkout-card">
                        <div class="checkout-header">
                            <i class="fas fa-truck-moving"></i>
                            <h6>{{ __('bn_e19de9e7') }}</h6>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">{{ __('Your {{ __('Name') }}') }}</label>
                                        <input type="text" name="name" class="form-control-custom" 
                                            value="{{ Auth::guard('customer')->user()->name ?? old('name') }}" placeholder="সম্পূর্ণ নাম লিখুন" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">{{ __('{{ __('Mobile') }} Number') }}</label>
                                        <input type="text" name="{{ __('phone') }}" class="form-control-custom" minlength="11" maxlength="11" pattern="0[0-9]+" 
                                            value="{{ Auth::guard('customer')->user()->{{ __('phone') }} ?? old('{{ __('phone') }}') }}" placeholder="{{ __('017xxxxxxxx') }}" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">{{ __('bn_aa5e2d70') }}</label>
                                        <input type="text" name="address" class="form-control-custom" 
                                            value="{{ Auth::guard('customer')->user()->address ?? old('address') }}" placeholder="বাসা নং, রোড নং, এলাকা, জেলা" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">{{ __('bn_e223e9e1') }}</label>
                                        @if($requires_shipping)
                                            <select id="area" class="form-control-custom select2" name="area" required>
                                                <option value="">{{ __('bn_f35b28fb') }}</option>
                                                @foreach ($shippingcharge as $value)
                                                    <option value="{{ $value->id }}" data-charge="{{ $value->amount }}"
                                                        {{ {{ __('Session') }}::get('shipping_id') == $value->id ? 'selected' : '' }}>
                                                        {{ $value->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="form-control-custom" value="{{ __('Digital {{ __('Product') }}') }} (No {{ __('{{ __('Shipping') }} Charge') }})" readonly disabled style="background:#f3f4f6">
                                            <input type="hidden" name="area" value="free_shipping"> 
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">{{ __('bn_76267855') }}</label>
                                        <textarea name="order_note" id="order_note" class="form-control-custom" rows="2" style="height:auto; resize:none;" 
                                            placeholder="{{ __('bn_a62e1d5e') }} সম্পর্কে বিশেষ কিছু বলার থাকলে লিখুন...">{{ $order_note ?? '' }}</textarea>
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
        <h6>{{ __('bn_b28bdfec') }}</h6>
    </div>
    <div class="card-body-custom">
        
        @if($hasAdvance)
            <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-left: 5px solid #ffc107 !important; background-color: #fff8e1;">
                <div class="d-flex gap-3 align-items-center">
                    <i class="fas fa-exclamation-triangle text-warning fs-4"></i>
                    <div>
                        <strong>{{ __('bn_c58ba16f') }}</strong>
                        <p class="mb-0 small">{{ __('Orders') }} <b>৳ {{ number_format($advance_amount,2) }}</b> {{ __('bn_6982b91e') }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Payment Options List --}}
        <div class="payment-options-list">
            
            {{-- {{ __('COD') }} Option --}}
            @if(!$hasDigital && !$hasAdvance)
                <label class="payment-option-label">
                    <input type="radio" name="payment_method" value="cod" checked required>
                    <div class="payment-content">
                        <div class="text-center" style="width: 40px;"><i class="fas fa-truck text-success fs-2"></i></div>
                        <div class="pay-info">
                            <strong>{{ __('{{ __('Cash') }} On Delivery') }}</strong>
                            <small>{{ __('bn_3bec6524') }}</small>
                        </div>
                    </div>
                    <div class="check-circle"></div>
                </label>
            @endif

            {{-- {{ __('Bkash') }} --}}
            @if($bkash_gateway)
                <label class="payment-option-label">
                    {{-- required যুক্ত করা হয়েছে --}}
                    <input type="radio" name="payment_method" value="bkash" required> 
                    <div class="payment-content">
                        <img src="{{ asset('public/frontEnd/images/bkash.svg') }}" class="pay-logo" alt="{{ __('bKash') }}">
                        <div class="pay-info">
                            <strong>{{ __('{{ __('bKash') }} Payment') }}</strong>
                            <small>{{ __('bn_ee716c0e') }}</small>
                        </div>
                    </div>
                    <div class="check-circle"></div>
                </label>
            @endif

            {{-- {{ __('ShurjoPay') }} --}}
            @if($shurjopay_gateway)
                <label class="payment-option-label">
                    {{-- required যুক্ত করা হয়েছে --}}
                    <input type="radio" name="payment_method" value="shurjopay" required>
                    <div class="payment-content">
                        <img src="{{ asset('public/frontEnd/images/shurjoPay.png') }}" class="pay-logo" alt="{{ __('ShurjoPay') }}">
                        <div class="pay-info">
                            <strong>{{ __('Online Payment') }}</strong>
                            <small>{{ __('{{ __('ShurjoPay') }} (Card/{{ __('Mobile') }} Banking)') }}</small>
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
                            <small>{{ __('bn_d84fe118') }}</small>
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
                            <small>{{ __('bn_5d6d6149') }}</small>
                        </div>
                    </div>
                    <div class="check-circle"></div>
                </label>
            @endif

        </div>
        {{-- Error {{ __('message') }} placeholder --}}
        <div id="payment-error" class="text-danger fw-bold mt-2 text-center" style="display:none;">
            <i class="fas fa-exclamation-circle"></i> অনুগ্রহ করে একটি {{ __('bn_f0a1817c') }} {{ __('bn_ad0b92c2') }} সিলেক্ট করুন।
        </div>
    </div>
</div>

                    {{-- MOBILE SUBMIT BUTTON (Only Visible on {{ __('Mobile') }}) --}}
                    <div class="mobile-{{ __('submit') }}-btn">
                        <button type="{{ __('submit') }}" class="btn-place-order">
                            অর্ডার নিশ্চিত করুন <i class="fas fa-arrow-right"></i>
                        </button>
                        <div class="text-center text-muted small mt-3">
                            <i class="fas fa-shield-alt"></i> ১০০% {{ __('bn_8704a028') }} এবং সিকিউর চেকআউট
                        </div>
                    </div>

                </div>

                {{-- RIGHT SIDE: Order {{ __('Summary') }} --}}
                <div class="col-lg-5 col-md-12 cust-order-1">
                    <div class="sticky-sidebar">
                        <div class="checkout-card">
                            <div class="checkout-header">
                                <i class="fas fa-shopping-bag"></i>
                                <h6>অর্ডার সামারি ({{ {{ __('Cart') }}::instance('shopping')->count() }})</h6>
                            </div>
                            
                            <div class="card-body-custom p-0">
                                {{-- {{ __('Product') }}s List ({{ __('Scrollable') }}) --}}
                                <div class="cart-items-scroll px-4 pt-3 cartlist" style="max-height: 400px; overflow-y: auto;">
                                    @foreach ({{ __('Cart') }}::instance('shopping')->content() as $value)
                                        <div class="checkout-item">
                                            {{-- Remove --}}
                                            <a class="remove-item-btn cart_remove" data-id="{{ $value->rowId }}" title="Remove {{ __('Item') }}">
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
                                                    @if($value->options->product_color) | {{ __('Color') }}: {{$value->options->product_color}} @endif
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
    @if(!{{ __('Session') }}::has('coupon_code'))
        <div class="coupon-group-modern">
            {{-- ভিজ্যুয়াল ইনপুট (এটি কোনো ফর্মের অংশ নয়, শুধু ডাটা নেওয়ার জন্য) --}}
            <input type="text" id="coupon_input" class="coupon-input-modern" placeholder="{{ __('{{ __('Coupon') }} Code') }} আছে? এখানে লিখুন...">
            <button type="button" class="coupon-btn-modern" onclick="{{ __('submit') }}{{ __('Coupon') }}()">{{ __('APPLY') }}</button>
        </div>
    @else
        <div class="alert alert-success d-flex justify-content-between align-items-center m-0 py-3 px-3 border-0 rounded shadow-sm">
            <span><i class="fas fa-check-circle"></i> {{ __('Coupon') }} <b>{{ {{ __('Session') }}::get('coupon_code') }}</b> {{ __('Applied!') }}</span>
            <a href="{{ route('coupon.remove') }}" class="text-danger fw-bold text-decoration-none px-2">{{ __('REMOVE') }}</a>
        </div>
    @endif
</div>

                                {{-- Calculation --}}
                                <div class="summary-{{ __('total') }}s">
                                    <div class="{{ __('total') }}-row"><span>{{ __('Sub{{ __('total') }}') }}</span> <span id="sub{{ __('total') }}{{ __('Amount') }}">৳ {{ number_format($sub{{ __('total') }}, 2) }}</span></div>
                                    <div class="{{ __('total') }}-row"><span>{{ __('bn_99838c8f') }}</span> <span id="shipping{{ __('Amount') }}">৳ {{ number_format($shipping, 2) }}</span></div>
                                    @if($discount > 0)
                                        <div class="{{ __('total') }}-row text-success"><span>{{ __('bn_a13a244a') }}</span> <span id="discount{{ __('Amount') }}">- ৳ {{ number_format($discount, 2) }}</span></div>
                                    @endif
                                    <div class="{{ __('total') }}-row final"><span>{{ __('{{ __('Total') }}') }}</span> <span id="grand{{ __('Total') }}{{ __('Amount') }}">৳ {{ number_format($grand_{{ __('total') }}, 2) }}</span></div>

                                    @if($hasAdvance)
                                        <div class="advance-alert">
                                            <div class="{{ __('total') }}-row text-success fw-bold"><span>{{ __('bn_5fa719d8') }}:</span> <span id="advance{{ __('Amount') }}Cell">৳ {{ number_format($advance_amount,2) }}</span></div>
                                            <div class="{{ __('total') }}-row text-danger fw-bold mb-0"><span>{{ __('bn_ce7135b7') }}:</span> <span id="due{{ __('Amount') }}Cell">৳ {{ number_format($due_amount,2) }}</span></div>
                                        </div>
                                    @endif
                                </div>

                                {{-- DESKTOP SUBMIT BUTTON (Only Visible on {{ __('Desktop') }}) --}}
                                <div class="desktop-{{ __('submit') }}-btn p-4">
                                    <button type="{{ __('submit') }}" class="btn-place-order">
                                        অর্ডার নিশ্চিত করুন <i class="fas fa-check-circle"></i>
                                    </button>
                                    <div class="text-center text-muted small mt-3">
                                        <i class="fas fa-lock"></i> ১০০% {{ __('bn_8704a028') }} চেকআউট প্রসেস
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
        <form id="coupon-form" action="{{ route('coupon.apply') }}" method={{ __('"{{ __('POST') }}"') }} style="display:none;">
            @csrf
            <input type="hidden" name="coupon_code" id="hidden_coupon_code">
        </form>

        {{-- কুপন সাবমিট করার জাভাস্ক্রিপ্ট --}}
        <script>
            function {{ __('submit') }}{{ __('Coupon') }}() {
                var code = document.getElementById('coupon_input').value;
                if(code) {
                    document.getElementById('hidden_coupon_code').value = code;
                    document.getElementById('coupon-form').{{ __('submit') }}();
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
    // গ্লোবাল ভেরিয়েবল (Global {{ __('Variable') }}s)
    let incompleteOrderTimer;
    let isSubmitting = false; // অর্ডার সাবমিট হচ্ছে কিনা তা চেক করার জন্য

    $(document).ready(function() {
        // Select2 Initialize
        $(".select2").select2({ width: '100%' });

        // ==========================================
        // 1. CART LOGIC ({{ __('REMOVE') }}, INCREASE, DECREASE)
        // ==========================================
        
        // Remove {{ __('Item') }}
        $(document).on('click', '.cart_remove', function(e) {
            e.preventDefault(); e.stopImmediatePropagation();
            var id = $(this).data("id");
            if ({{ __('id)') }} {
                $("#loading").show();
                $.ajax({
                    type: "{{ __('GET') }}",
                    url: "{{ route('cart.remove') }}",
                    data: { id: id },
                    success: function() { toastr.success('Success', '{{ __('Item') }} removed'); window.location.reload(); },
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
        
        const base{{ __('Sub{{ __('total') }}') }} = parseFloat("{{ $sub{{ __('total') }} ?? 0 }}");
        const base{{ __('Discount') }} = parseFloat("{{ $discount ?? 0 }}");
        const advance{{ __('Amount') }} = parseFloat("{{ $advance_amount ?? 0 }}");
        const hasAdvance = @json($hasAdvance ?? false);
        const requires{{ __('Shipping') }} = @json($requires_shipping ?? false);
        const cart{{ __('{{ __('Item') }}s') }} = @json($cart{{ __('{{ __('Item') }}s') }}ForJs ?? []);
        const hasAllFreeDelivery = @json($hasAllFreeDelivery ?? false);

        // ⭐ {{ __('Free Delivery') }} {{ __('Check') }} Function
        function checkFreeDelivery() {
            // {{ __('Check') }} if all physical products have free_delivery = 1
            let allFreeDelivery = true;
            for (let i = 0; i < cart{{ __('{{ __('Item') }}s') }}.length; i++) {
                let item = cart{{ __('{{ __('Item') }}s') }}[i];
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
            
            // ⭐ {{ __('Free Delivery') }} {{ __('Check') }} - যদি সব {{ __('{{ __('Product') }}s') }} free delivery eligible {{ __('bn_290a7f61') }}, shipping charge 0
            var isFreeDelivery = checkFreeDelivery();
            var shippingCharge = isFreeDelivery ? 0 : selectedCharge;
            
            var grand{{ __('Total') }} = base{{ __('Sub{{ __('total') }}') }} + shippingCharge - base{{ __('Discount') }};
            var due{{ __('Amount') }} = hasAdvance ? (grand{{ __('Total') }} - advance{{ __('Amount') }}) : 0;

            // টেক্সট আপডেট
            $('#shipping{{ __('Amount') }}').text('৳ ' + shippingCharge.to{{ __('Fixed') }}(2));
            $('#grand{{ __('Total') }}{{ __('Amount') }}').text('৳ ' + grand{{ __('Total') }}.to{{ __('Fixed') }}(2));
            
            if (hasAdvance) {
                $('#due{{ __('Amount') }}Cell').text('৳ ' + due{{ __('Amount') }}.to{{ __('Fixed') }}(2));
                $('#due{{ __('Amount') }}{{ __('Text') }}').text(due{{ __('Amount') }}.to{{ __('Fixed') }}(2));
            }

            // ব্যাকএন্ডে শিপিং চার্জ সেট করা (free delivery হলে 0 পাঠাবে)
            if (isFreeDelivery) {
                $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' });
            } else {
                $.get('{{ route("shipping.charge") }}', { id: $(this).val() });
            }
            
            // এরিয়া চেঞ্জ করলেও ইন{{ __('bn_e35fe987') }} আপডেট হবে (যদি নাম/ফোন/ঠিকানা থাকে)
            saveIncompleteOrder();
        });

        // ⭐ Page Load হওয়ার সময় {{ __('Free Delivery') }} {{ __('Check') }} করে Initial {{ __('{{ __('Shipping') }} Charge') }} Set করা
        $(document).ready(function() {
            // {{ __('Check') }} free delivery on page load
            var isFreeDeliveryOnLoad = hasAllFreeDelivery || checkFreeDelivery();
            
            if (isFreeDeliveryOnLoad) {
                var shippingCharge = 0;
                var grand{{ __('Total') }} = base{{ __('Sub{{ __('total') }}') }} + shippingCharge - base{{ __('Discount') }};
                var due{{ __('Amount') }} = hasAdvance ? (grand{{ __('Total') }} - advance{{ __('Amount') }}) : 0;

                // টেক্সট আপডেট
                $('#shipping{{ __('Amount') }}').text('৳ ' + shippingCharge.to{{ __('Fixed') }}(2));
                $('#grand{{ __('Total') }}{{ __('Amount') }}').text('৳ ' + grand{{ __('Total') }}.to{{ __('Fixed') }}(2));
                
                if (hasAdvance) {
                    $('#due{{ __('Amount') }}Cell').text('৳ ' + due{{ __('Amount') }}.to{{ __('Fixed') }}(2));
                    $('#due{{ __('Amount') }}{{ __('Text') }}').text(due{{ __('Amount') }}.to{{ __('Fixed') }}(2));
                }

                // ব্যাকএন্ডে শিপিং চার্জ 0 সেট করা
                $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' });
            } else {
                // Free delivery না হলে current shipping charge use করবে
                var current{{ __('Shipping') }} = parseFloat($('#shipping{{ __('Amount') }}').text().replace(/[৳,\s]/g, '').trim()) || 0;
                var grand{{ __('Total') }} = base{{ __('Sub{{ __('total') }}') }} + current{{ __('Shipping') }} - base{{ __('Discount') }};
                var due{{ __('Amount') }} = hasAdvance ? (grand{{ __('Total') }} - advance{{ __('Amount') }}) : 0;
                
                // Grand {{ __('total') }} update
                $('#grand{{ __('Total') }}{{ __('Amount') }}').text('৳ ' + grand{{ __('Total') }}.to{{ __('Fixed') }}(2));
                
                if (hasAdvance) {
                    $('#due{{ __('Amount') }}Cell').text('৳ ' + due{{ __('Amount') }}.to{{ __('Fixed') }}(2));
                    $('#due{{ __('Amount') }}{{ __('Text') }}').text(due{{ __('Amount') }}.to{{ __('Fixed') }}(2));
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
                var {{ __('phone') }} = $('input[name="{{ __('phone') }}"]').val();
                var address = $('input[name="address"]').val();
                
                // ২. লজিক: নাম, ফোন এবং ঠিকানা - তিনটিই থাকতে হবে। 
                // যদি একাও মিসিং থাকে, তাহলে ইন{{ __('bn_e35fe987') }} সেভ হবে না।
                if (!name || !{{ __('phone') }} || !{{ __('address)') }} {
                    return; 
                }

                // ক্যালকুলেশন
                var selectedCharge = parseFloat($('#area option:selected').attr('data-charge')) || 0;
                // ⭐ {{ __('Free Delivery') }} {{ __('Check') }}
                var isFreeDelivery = checkFreeDelivery();
                var shippingCharge = isFreeDelivery ? 0 : selectedCharge;
                var {{ __('total') }} = (base{{ __('Sub{{ __('total') }}') }} + shippingCharge - base{{ __('Discount') }}).to{{ __('Fixed') }}(2);

                // ডাটা পাঠানো
                $.ajax({
                    url: '{{ route("incomplete.order.store") }}',
                    type: '{{ __('POST') }}',
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: JSON.stringify({
                        name: name,
                        {{ __('phone') }}: {{ __('phone') }},
                        address: address,
                        items: cart{{ __('{{ __('Item') }}s') }},
                        {{ __('total') }}_amount: {{ __('total') }}
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

        // পেজ ছেড়ে যাওয়ার সময় ইন{{ __('bn_e35fe987') }} সেভ (যাতে অ্যাডমিন প্যানেলে দেখায়)
        function saveIncompleteOrderSync() {
            if (isSubmitting) return;
            var name = $('input[name="name"]').val();
            var {{ __('phone') }} = $('input[name="{{ __('phone') }}"]').val();
            var address = $('input[name="address"]').val();
            if (!name || !{{ __('phone') }} || !{{ __('address)') }} return;
            var selectedCharge = parseFloat($('#area option:selected').attr('data-charge')) || 0;
            var isFreeDelivery = typeof checkFreeDelivery === 'function' ? checkFreeDelivery() : false;
            var shippingCharge = isFreeDelivery ? 0 : selectedCharge;
            var {{ __('total') }} = (base{{ __('Sub{{ __('total') }}') }} + shippingCharge - base{{ __('Discount') }}).to{{ __('Fixed') }}(2);
            var payload = JSON.stringify({
                name: name, {{ __('phone') }}: {{ __('phone') }}, address: address,
                items: cart{{ __('{{ __('Item') }}s') }}, {{ __('total') }}_amount: {{ __('total') }},
                _token: $('meta[name="csrf-token"]').attr('content')
            });
            navigator.sendBeacon('{{ route("incomplete.order.store") }}', new Blob([payload], {type: 'application/json'}));
        }
        $(window).on('beforeunload pagehide', function() { saveIncompleteOrderSync(); });

        // ==========================================
        // 4. FORM SUBMISSION & VALIDATION
        // ==========================================

        $('#checkout-form').on('{{ __('submit') }}', function(e) {
            // {{ __('bn_f0a1817c') }} {{ __('bn_ad0b92c2') }} চেক
            var payment{{ __('Method') }} = $('input[name="payment_method"]:checked').val();
            
            if (!payment{{ __('Method') }}) {
                e.preventDefault();
                toastr.error('অর্ডার সম্পন্ন করতে {{ __('bn_b28bdfec') }}।', 'Error');
                $('#payment-error').show();
                $('html, body').animate({ scrollTop: $(".checkout-card .fa-wallet").offset().top - 150 }, 500);
                $('.btn-place-order').prop('disabled', false);
                return false;
            } else {
                $('#payment-error').hide();

                // ৩. অর্ডার সাবমিট হচ্ছে, তাই ইনকমপ্লিট টাইমার {{ __('Close') }} করে দেওয়া হলো
                isSubmitting = true; 
                if(incompleteOrderTimer) {
                    clearTimeout(incompleteOrderTimer);
                }
                
                // ফর্ম সাবমিট হতে দিন...
            }
        });

        // {{ __('bn_f0a1817c') }} সিলেক্ট করলে এরর হাইড হবে
        $('input[name="payment_method"]').on('change', function() {
            $('#payment-error').hide();
        });
    });
</script>
{{-- 🔹 GA4 + Facebook Pixel {{ __('{{ __('Track') }}ing') }} for {{ __('Check') }}out --}}
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];

    (function () {
        const items        = @json($cart{{ __('{{ __('Item') }}s') }}ForJs);
        const hasAdvance   = @json($hasAdvance);
        const advance{{ __('Amount') }}= parseFloat("{{ $advance_amount }}") || 0;
        const grand{{ __('Total') }}   = parseFloat("{{ $grand_{{ __('total') }} }}") || 0;
        const payableNow   = hasAdvance ? advance{{ __('Amount') }} : grand{{ __('Total') }};
        const coupon       = @json({{ __('Session') }}::get('coupon_code', null));

        const ga4{{ __('{{ __('Item') }}s') }} = items.map(function (item, index) {
            return {
                item_id: {{ __('String') }}(item.{{ __('id)') }},
                item_name: item.name,
                quantity: Number(item.qty),
                price: Number(item.price),
                index: index
            };
        });

        // GA4: begin_checkout
        if (ga4{{ __('{{ __('Item') }}s') }}.length) {
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                event: "begin_checkout",
                ecommerce: {
                    currency: "BDT",
                    value: payableNow,
                    coupon: coupon,
                    items: ga4{{ __('{{ __('Item') }}s') }}
                }
            });
        }

        // Facebook Pixel: Initiate{{ __('Check') }}out
        if (typeof fbq === "function" && items.length) {
            fbq("track", "Initiate{{ __('Check') }}out", {
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

        // On form {{ __('submit') }}: GA4 add_payment_info + Pixel AddPaymentInfo
        document.addEventListener("DOMContentLoaded", function () {
            var form = document.getElementById("checkout-form");
            if (!form) return;

            form.addEventListener("{{ __('submit') }}", function () {
                var paymentInput  = form.querySelector('input[name="payment_method"]:checked');
                var payment{{ __('Method') }} = paymentInput ? paymentInput.value : null;

                // GA4 add_payment_info
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: "add_payment_info",
                    payment_type: payment{{ __('Method') }},
                    ecommerce: {
                        currency: "BDT",
                        value: payableNow,
                        coupon: coupon,
                        items: ga4{{ __('{{ __('Item') }}s') }}
                    }
                });

                // Facebook Pixel: AddPaymentInfo
                if (typeof fbq === "function" && items.length) {
                    fbq("track", "AddPaymentInfo", {
                        value: payableNow,
                        currency: "BDT",
                        payment_method: payment{{ __('Method') }},
                        num_items: items.length,
                        content_ids: items.map(function(i){ return i.id; })
                    });
                }
            });
        });
    })();

</script>
@endpush