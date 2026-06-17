@extends('reseller.layouts.app')

@section('title', 'চেকআউট')
@section('page-title', 'চেকআউট')

@push('styles')
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

    /* --- Reseller Custom Price Section --- */
    .reseller-section {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 2px solid #3b82f6;
        border-radius: 12px;
        padding: 24px;
        margin: 20px 0;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1);
    }
    
    .reseller-section h6 {
        color: #1e40af;
        font-weight: 700;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
    }
    
    #resellerProfitInfo {
        background: #ecfdf5;
        border-radius: 8px;
        border-left: 4px solid #10b981;
        padding: 15px;
        margin-top: 15px;
    }

    /* --- Responsive Fixes --- */
    @media (max-width: 991px) {
        .checkout-section {
            padding: 30px 0;
        }
        .cus-order-2 { order: 2; }
        .cust-order-1 { order: 1; margin-bottom: 30px; }
        .mobile-submit-btn { display: block !important; margin-top: 25px; }
        .desktop-submit-btn { display: none !important; }
        .checkout-card {
            margin-bottom: 20px;
        }
        .card-body-custom {
            padding: 20px;
        }
        .checkout-header {
            padding: 15px 20px;
        }
    }
    @media (min-width: 992px) {
        .mobile-submit-btn { display: none !important; }
        .desktop-submit-btn { display: block !important; }
        .sticky-sidebar {
            position: sticky;
            top: 100px;
        }
    }
</style>
@endpush

@section('content')

<section class="checkout-section">
    <div class="container" style="max-width: 1200px;">
        <form id="checkout-form" action="{{ route('reseller.checkout.store') }}" method="POST">
            @csrf
            
            <div class="row">
                {{-- LEFT COLUMN: Shipping & Payment --}}
                <div class="col-lg-7 col-md-12 cus-order-2">
                    {{-- SHIPPING INFO CARD --}}
                    <div class="checkout-card">
                        <div class="checkout-header">
                            <i class="fas fa-truck-moving"></i>
                            <h6>কাস্টমার তথ্য</h6>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">কাস্টমারের নাম *</label>
                                        <input type="text" name="name" class="form-control-custom" 
                                            value="{{ old('name') }}" placeholder="সম্পূর্ণ নাম" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label-custom">মোবাইল নাম্বার *</label>
                                        <input type="text" name="phone" class="form-control-custom" minlength="11" maxlength="11" pattern="0[0-9]+" 
                                            value="{{ old('phone') }}" placeholder="017xxxxxxxx" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label-custom">সম্পূর্ণ ঠিকানা *</label>
                                        <input type="text" name="address" class="form-control-custom" 
                                            value="{{ old('address') }}" placeholder="বাসা নং, রোড নং, এলাকা, জেলা" required>
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
                                        <textarea name="order_note" class="form-control-custom" rows="2" style="height:auto; resize:none;" 
                                            placeholder="ডেলিভারি সম্পর্কে বিশেষ কিছু বলার থাকলে লিখুন...">{{ old('order_note') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PAYMENT METHOD CARD --}}
                    <div class="checkout-card">
                        <div class="checkout-header">
                            <i class="fas fa-wallet"></i>
                            <h6>পেমেন্ট মেথড নির্বাচন করুন</h6>
                        </div>
                        <div class="card-body-custom">
                            {{-- Advance Payment Warning --}}
                            @if($hasAdvance)
                                <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-left: 5px solid #ffc107 !important; background-color: #fff8e1;">
                                    <div class="d-flex gap-3 align-items-center">
                                        <i class="fas fa-exclamation-triangle text-warning fs-4"></i>
                                        <div>
                                            <strong>অগ্রিম পেমেন্ট প্রয়োজন!</strong>
                                            <p class="mb-0 small">এই অর্ডারে <b>৳ {{ number_format($advanceAmount, 2) }}</b> অগ্রিম পেমেন্ট করতে হবে।</p>
                                            <p class="mb-0 small mt-1">অবশিষ্ট <b>৳ {{ number_format($dueAmount, 2) }}</b> ডেলিভারির সময় পরিশোধ করতে হবে।</p>
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
                                                <strong>Cash On Delivery</strong>
                                                <small>পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @elseif(!$hasDigital && $hasAdvance)
                                    {{-- COD disabled for advance payment --}}
                                    <label class="payment-option-label" style="opacity: 0.5; cursor: not-allowed;">
                                        <input type="radio" name="payment_method" value="cod" disabled>
                                        <div class="payment-content">
                                            <div class="text-center" style="width: 40px;"><i class="fas fa-truck text-muted fs-2"></i></div>
                                            <div class="pay-info">
                                                <strong>Cash On Delivery</strong>
                                                <small class="text-danger">অগ্রিম পেমেন্টের জন্য COD পাওয়া যায় না</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endif

                                {{-- Bkash --}}
                                @if($bkash_gateway)
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="bkash" {{ $hasAdvance ? 'checked' : '' }} required> 
                                        <div class="payment-content">
                                            <img src="{{ asset('public/frontEnd/images/bkash.svg') }}" class="pay-logo" alt="bKash">
                                            <div class="pay-info">
                                                <strong>bKash Payment</strong>
                                                <small>বিকাশ অ্যাপ বা গেটওয়ে দ্বারা পেমেন্ট</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endif

                                {{-- ShurjoPay --}}
                                @if($shurjopay_gateway)
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="shurjopay" {{ $hasAdvance && !$bkash_gateway ? 'checked' : '' }} required>
                                        <div class="payment-content">
                                            <img src="{{ asset('public/frontEnd/images/shurjoPay.png') }}" class="pay-logo" alt="ShurjoPay">
                                            <div class="pay-info">
                                                <strong>Online Payment</strong>
                                                <small>ShurjoPay (Card/Mobile Banking)</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endif

                                {{-- UddoktaPay --}}
                                @if($uddoktapay_gateway)
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="uddoktapay" {{ $hasAdvance && !$bkash_gateway && !$shurjopay_gateway ? 'checked' : '' }} required>
                                        <div class="payment-content">
                                            <img src="{{ asset('public/frontEnd/images/uddokta.png') }}" class="pay-logo" alt="UddoktaPay">
                                            <div class="pay-info">
                                                <strong>UddoktaPay</strong>
                                                <small>মোবাইল ব্যাংকিং পেমেন্ট গেটওয়ে</small>
                                            </div>
                                        </div>
                                        <div class="check-circle"></div>
                                    </label>
                                @endif

                                {{-- aamarPay --}}
                                @if($aamarpay_gateway)
                                    <label class="payment-option-label">
                                        <input type="radio" name="payment_method" value="aamarpay" {{ $hasAdvance && !$bkash_gateway && !$shurjopay_gateway && !$uddoktapay_gateway ? 'checked' : '' }} required>
                                        <div class="payment-content">
                                            <img src="{{ asset('public/frontEnd/images/aamarpay.png') }}" class="pay-logo" alt="aamarPay" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="pay-info" style="display: none;">
                                                <i class="fas fa-credit-card text-primary fs-4"></i>
                                            </div>
                                            <div class="pay-info">
                                                <strong>aamarPay</strong>
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
                                <div class="cart-items-scroll px-4 pt-3" style="max-height: 400px; overflow-y: auto;">
                                @foreach (Cart::instance('shopping')->content() as $value)
                                    @php
                                        $itemResellerPrice = null;
                                        if (isset($value->options->reseller_price) && $value->options->reseller_price > 0) {
                                            $itemResellerPrice = (float) $value->options->reseller_price;
                                        } else {
                                            $product = \App\Models\Product::find($value->id);
                                            if ($product && $product->reseller_price) {
                                                $itemResellerPrice = (float) $product->reseller_price;
                                            }
                                        }
                                    @endphp
                                    <div class="checkout-item">
                                        {{-- Remove --}}
                                        <a class="remove-item-btn cart_remove" data-id="{{ $value->rowId }}" title="Remove Item">
                                            <i class="far fa-trash-alt"></i>
                                        </a>

                                        {{-- Image --}}
                                        <img src="{{ asset($value->options->image ?? 'public/uploads/default.webp') }}" class="checkout-pro-img" alt="{{ $value->name }}">

                                        {{-- Info --}}
                                        <div class="checkout-pro-info flex-grow-1">
                                            <h6>{{ Str::limit($value->name, 35) }}</h6>
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
                                                <div class="fw-bold text-dark">
                                                    @if($itemResellerPrice)
                                                        <div class="text-primary">৳{{ number_format($itemResellerPrice * $value->qty, 0) }}</div>
                                                        <small class="text-muted" style="font-size: 11px;">রিসেলার প্রাইস</small>
                                                    @else
                                                        ৳{{ number_format($value->price * $value->qty, 0) }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Reseller Custom Price Field --}}
                            <div class="reseller-section">
                                <h6 style="color: #1e40af; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-user-tie"></i> রিসেলার অর্ডার
                                </h6>
                                
                                <div class="form-group">
                                    <label class="form-label-custom" for="custom_price">
                                        কাস্টমারকে কত টাকা নিবেন? (Custom Price) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           id="custom_price" 
                                           name="custom_price" 
                                           class="form-control-custom" 
                                           step="0.01" 
                                           min="0" 
                                           placeholder="কাস্টমারকে যে দামে বিক্রি করবেন" 
                                           required
                                           oninput="calculateResellerProfit()">
                                    <small class="text-muted d-block mt-2">
                                        <i class="fas fa-info-circle"></i> 
                                        রিসেলার প্রাইস: ৳{{ number_format($totalResellerPrice, 2) }}
                                    </small>
                                </div>
                                
                                <div id="resellerProfitInfo" style="display: none; margin-top: 15px; padding: 15px; background: #ecfdf5; border-radius: 8px; border-left: 4px solid #10b981;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span style="font-weight: 600; color: #065f46;">আপনার লাভ:</span>
                                        <span id="resellerProfitAmount" style="font-size: 20px; font-weight: 700; color: #10b981;">৳0.00</span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calculator"></i> 
                                        লাভ = কাস্টমার প্রাইস - রিসেলার প্রাইস
                                    </small>
                                </div>
                            </div>

                            {{-- Calculation --}}
                            <div class="summary-totals">
                                <div class="total-row">
                                    <span>রিসেলার প্রাইস</span> 
                                    <span id="totalResellerPriceDisplay">৳{{ number_format($totalResellerPrice, 2) }}</span>
                                </div>
                                <div class="total-row">
                                    <span>ডেলিভারি চার্জ</span> 
                                    <span id="shippingAmount">
                                        @php
                                            // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
                                            $hasAllFreeDelivery = $hasAllFreeDelivery ?? false;
                                            
                                            // ⭐ Important: Use session shipping first (already calculated with free delivery check)
                                            $displayShipping = Session::get('shipping', 0);
                                            
                                            // If free delivery eligible, always show 0
                                            if ($hasAllFreeDelivery) {
                                                $displayShipping = 0;
                                            } else {
                                                // If shipping is 0 or not set, get from selected option
                                                $selectedShippingId = Session::get('shipping_id');
                                                
                                                if ($selectedShippingId) {
                                                    // Convert to collection if it's an array
                                                    $shippingCollection = is_array($shippingcharge) ? collect($shippingcharge) : $shippingcharge;
                                                    
                                                    if ($shippingCollection) {
                                                        $selectedShipping = $shippingCollection->firstWhere('id', $selectedShippingId);
                                                        if ($selectedShipping) {
                                                            $displayShipping = $selectedShipping->amount;
                                                        }
                                                    }
                                                }
                                                
                                                // If still 0 and no selected shipping, use first active shipping charge (default)
                                                if ($displayShipping == 0) {
                                                    $shippingCollection = is_array($shippingcharge) ? collect($shippingcharge) : $shippingcharge;
                                                    if ($shippingCollection && $shippingCollection->count() > 0) {
                                                        $firstShipping = $shippingCollection->first();
                                                        if ($firstShipping) {
                                                            $displayShipping = $firstShipping->amount;
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp
                                        ৳{{ number_format($displayShipping, 2) }}
                                    </span>
                                </div>
                                
                                @if($hasAdvance)
                                <div class="total-row" style="background: #fff8e1; padding: 12px; border-left: 4px solid #ffc107;">
                                    <span><strong>অগ্রিম পেমেন্ট</strong></span> 
                                    <span id="advanceAmountDisplay" style="color: #f59e0b; font-weight: 700;">৳{{ number_format($advanceAmount, 2) }}</span>
                                </div>
                                <div class="total-row" style="color: #6b7280; font-size: 0.9em;">
                                    <span>অবশিষ্ট পরিশোধ</span> 
                                    <span id="dueAmountDisplay">৳{{ number_format($dueAmount, 2) }}</span>
                                </div>
                                @endif
                                
                                <div class="total-row final">
                                    <span><strong>@if($hasAdvance) এখন পেমেন্ট করতে হবে @else কাস্টমার পেমেন্ট @endif</strong></span> 
                                    <span id="customerPayableAmount" style="font-size: 1.2em; font-weight: 700;">
                                        @if($hasAdvance)
                                            ৳{{ number_format($advanceAmount, 2) }}
                                        @else
                                            ৳{{ number_format($displayShipping, 2) }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- SUBMIT BUTTON --}}
                            <div class="p-4">
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
        </form>
    </div>
</section>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('public/frontEnd/js/select2.min.js') }}"></script>
<script>
    // Store total reseller price globally
    var totalResellerPrice = {{ $totalResellerPrice }};
    var advanceAmount = {{ $advanceAmount ?? 0 }};
    var hasAdvance = {{ $hasAdvance ? 'true' : 'false' }};
    var hasAllFreeDelivery = @json($hasAllFreeDelivery ?? false);
    
    // Function to update all calculations
    function updateCalculations() {
        var customPrice = parseFloat($('#custom_price').val()) || 0;
        
        // ⭐ Free Delivery Check - Get shipping charge from display text (backend already calculated with free delivery check)
        var shippingText = $('#shippingAmount').text().replace(/[৳,\s]/g, '').trim();
        var shipping = parseFloat(shippingText) || 0;
        
        // ⭐ Important: If hasAllFreeDelivery is true, shipping must be 0
        if (hasAllFreeDelivery) {
            shipping = 0;
        } else {
            // If shipping is 0 or NaN from display, try from selected option
            if (isNaN(shipping) || shippingText === '' || shipping === 0) {
                var selectedOption = $('#area option:selected');
                if (selectedOption.length && selectedOption.val()) {
                    shipping = parseFloat(selectedOption.attr('data-charge')) || 0;
                }
            }
        }
        
        var profit = customPrice - totalResellerPrice;
        // ⭐ Customer Payable = Custom Price + Shipping (shipping will be 0 if free delivery)
        var customerPayable = customPrice + shipping;
        
        // If advance payment exists, calculate payable now and due amount
        if (hasAdvance && advanceAmount > 0) {
            var payableNow = advanceAmount;
            var dueAmount = customerPayable - advanceAmount;
            
            $('#advanceAmountDisplay').text('৳' + advanceAmount.toFixed(2));
            $('#dueAmountDisplay').text('৳' + dueAmount.toFixed(2));
            $('#customerPayableAmount').text('৳' + payableNow.toFixed(2));
        } else {
            // No advance, full payment
            $('#customerPayableAmount').text('৳' + customerPayable.toFixed(2));
        }
        
        if (customPrice > 0) {
            $('#resellerProfitInfo').show();
            $('#resellerProfitAmount').text('৳' + profit.toFixed(2));
        } else {
            $('#resellerProfitInfo').hide();
        }
    }

    // Calculate reseller profit when custom price changes
    function calculateResellerProfit() {
        updateCalculations();
    }

    // Function to handle shipping charge update
    function updateShippingCharge() {
        var selectedOption = $('#area option:selected');
        var shippingCharge = parseFloat(selectedOption.attr('data-charge')) || 0;
        
        if (shippingCharge >= 0) {
            var formattedCharge = shippingCharge.toFixed(2);
            $('#shippingAmount').text('৳' + formattedCharge);
            // Update customer payable amount when shipping changes
            updateCalculations();
        }
    }

    $(document).ready(function() {
        // Initialize Select2 - EXACT SAME AS CUSTOMER CHECKOUT
        $(".select2").select2({ width: '100%' });

        // Store base values
        const baseResellerPrice = {{ $totalResellerPrice }};
        
        // ⭐ Free Delivery Check - Backend already checked (variable already defined globally above)
        var initialShipping = @json($shipping ?? 0);

        // Shipping charge calculation - EXACT SAME AS CUSTOMER CHECKOUT
        $('#area').on('change', function () {
            var selectedCharge = parseFloat($('option:selected', this).attr('data-charge')) || 0;
            
            // ⭐ Free Delivery Check - যদি সব প্রোডাক্ট free delivery eligible হয়, shipping charge 0
            var shippingCharge = hasAllFreeDelivery ? 0 : selectedCharge;
            
            // Backend will check free delivery and set shipping charge accordingly
            // Backend-এ shipping charge set করা - EXACT SAME AS CUSTOMER CHECKOUT
            if (hasAllFreeDelivery) {
                $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' }).done(function() {
                    $('#shippingAmount').text('৳ 0.00');
                    updateCalculations();
                });
            } else {
                $.get('{{ route("shipping.charge") }}', { id: $(this).val() }).done(function(response) {
                    $('#shippingAmount').text('৳ ' + shippingCharge.toFixed(2));
                    updateCalculations();
                });
            }
        });
        
        // Initialize calculations on page load
        setTimeout(function() {
            // ⭐ Set initial shipping charge (already calculated by backend with free delivery check)
            var initialCharge = hasAllFreeDelivery ? 0 : initialShipping;
            
            // Update shipping display immediately
            $('#shippingAmount').text('৳ ' + initialCharge.toFixed(2));
            
            // If area is selected, update backend session
            var selectedOption = $('#area option:selected');
            if (selectedOption.length && selectedOption.val()) {
                if (hasAllFreeDelivery) {
                    // Free delivery হলে backend এ 0 set করা
                    $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' }).done(function() {
                        $('#shippingAmount').text('৳ 0.00');
                        updateCalculations();
                    });
                } else {
                    // Normal shipping হলে backend এ selected charge set করা
                    $.get('{{ route("shipping.charge") }}', { id: selectedOption.val() }).done(function() {
                        var selectedCharge = parseFloat(selectedOption.attr('data-charge')) || 0;
                        $('#shippingAmount').text('৳ ' + selectedCharge.toFixed(2));
                        updateCalculations();
                    });
                }
            } else {
                // No area selected, use initial charge
                updateCalculations();
            }
        }, 300);
    });

    // Cart remove - EXACT SAME AS CUSTOMER CHECKOUT
    $(document).on('click', '.cart_remove', function(e) {
        e.preventDefault(); 
        e.stopImmediatePropagation();
        var id = $(this).data("id");
        if (id) {
            if (confirm('আপনি কি এই প্রোডাক্টটি কার্ট থেকে সরাতে চান?')) {
                $.ajax({
                    type: "GET",
                    url: "{{ route('cart.remove') }}",
                    data: { id: id },
                    success: function() { 
                        window.location.reload(); 
                    },
                    error: function() { 
                        window.location.reload(); 
                    }
                });
            }
        }
    });

    // Quantity Increment
    $('.checkout-qty .plus').on('click', function() {
        var rowId = $(this).closest('.checkout-qty').data('rowid');
        $("#loading").show();
        $.get("{{ route('cart.increment') }}", { id: rowId }, function() { 
            window.location.reload(); 
        });
    });

    // Quantity Decrement
    $('.checkout-qty .minus').on('click', function() {
        var rowId = $(this).closest('.checkout-qty').data('rowid');
        $("#loading").show();
        $.get("{{ route('cart.decrement') }}", { id: rowId }, function() { 
            window.location.reload(); 
        });
    });
</script>
@endpush
