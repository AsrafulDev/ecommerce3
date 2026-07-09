@extends('backEnd.layouts.master')
@section('title','Point of Sale')

@section('css')
<style>
    .pos-shell{
        padding:10px 0 25px;
    }
    .pos-card{
        background:#ffffff;
        border-radius:14px;
        box-shadow:0 15px 30px rgba(15,23,42,0.08);
        padding:14px 14px 10px;
        border:1px solid rgba(148,163,184,0.25);
    }
    .pos-header-bar{
        background:linear-gradient(135deg,#4f46e5,#6366f1);
        color:#fff;
        border-radius:12px;
        padding:10px 14px;
        margin-bottom:10px;
        display:flex;
        justify-content:space-between;
        align-items:center;
    }
    .pos-header-bar h5{
        margin:0;
        font-size:16px;
        font-weight:600;
        letter-spacing:.3px;
    }
    .pos-badge-soft{
        padding:3px 10px;
        border-radius:999px;
        background:rgba(15,23,42,.18);
        font-size:12px;
    }

    /* LEFT – CART TABLE */
    .pos-cart-table thead{
        background:#f9fafb;
    }
    .pos-cart-table th{
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.03em;
        color:#64748b;
        border-bottom:1px solid #e2e8f0;
    }
    .pos-cart-table td{
        vertical-align:middle;
    }
    .pos-cart-table th:nth-child(2),
    .pos-cart-table td:nth-child(2){
        min-width:220px;
    }

    .qty-cart .quantity{
        display:flex;
        align-items:center;
        justify-content:center;
    }
    .qty-cart .quantity button{
        border:1px solid #cbd5f5;
        background:#e5edff;
        width:28px;
        height:28px;
        border-radius:6px;
        line-height:26px;
        text-align:center;
        padding:0;
        font-weight:600;
        color:#4f46e5;
    }
    .qty-cart .quantity input{
        width:40px;
        text-align:center;
        border:0;
        background:transparent;
        font-weight:600;
    }

    /* CUSTOMER + TOTAL CARD */
    .pos-section-title{
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.12em;
        color:#94a3b8;
        font-weight:600;
        margin-bottom:6px;
    }
    .pos-summary-table td{
        padding:6px 10px;
        font-size:14px;
    }
    .pos-summary-table tr:last-child td{
        border-top:1px dashed #e2e8f0;
        font-size:15px;
        font-weight:700;
    }
    .pos-grand-total{
        font-size:18px !important;
        color:#16a34a;
    }

    .btn-pos-primary{
        background:linear-gradient(135deg,#4f46e5,#6366f1);
        border:none;
        padding:9px 22px;
        border-radius:999px;
        font-weight:600;
        font-size:14px;
        box-shadow:0 10px 20px rgba(79,70,229,.35);
        color:#fff;
    }
    .btn-pos-primary:hover{
        opacity:.94;
        box-shadow:0 16px 30px rgba(79,70,229,.45);
    }

    /* RIGHT – PRODUCTS GRID */
    .pos-products-wrapper{
        max-height:520px;
        overflow-y:auto;
        padding-right:4px;
    }
    .pos-product-card{
        border-radius:12px;
        padding:8px 8px 10px;
        margin-bottom:10px;
        text-align:center;
        cursor:pointer;
        transition:.18s all;
        background:linear-gradient(145deg,#f9fafb,#e5edff);
        border:1px solid rgba(148,163,184,.35);
        position:relative;
    }
    .pos-product-card:hover{
        transform:translateY(-2px);
        box-shadow:0 12px 24px rgba(15,23,42,.15);
    }
    .pos-product-img{
        height:72px;
        object-fit:contain;
        margin-bottom:4px;
    }
    .pos-product-name{
        font-size:13px;
        font-weight:600;
        min-height:34px;
        color:#111827;
    }
    .pos-product-price{
        font-size:14px;
        font-weight:700;
        color:#16a34a;
    }
    .pos-stock-badge{
        position:absolute;
        top:6px;
        left:8px;
        background:rgba(30,64,175,.12);
        color:#1d4ed8;
        font-size:11px;
        padding:2px 6px;
        border-radius:999px;
    }

    .pos-search-bar input{
        border-radius:999px;
        border:1px solid #cbd5f5;
        font-size:13px;
        padding-left:32px;
    }
    .pos-search-bar .icon{
        position:absolute;
        top:50%;
        left:10px;
        transform:translateY(-50%);
        color:#94a3b8;
        font-size:13px;
    }
</style>
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="container-fluid pos-shell">

    {{-- TOP BAR --}}
    <div class="row mb-2">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0"> {{ __('Point of Sale') }} </h4>
                    {{-- 🆕 Held Orders Button --}}
                    <button type="button" id="btn-held-orders" class="btn btn-sm btn-info rounded-pill" title="Held Orders">
                        <i class="fas fa-pause-circle me-1"></i> {{ __('Held Orders') }}
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    {{-- 🆕 Barcode Scanner Input --}}
                    <div class="input-group input-group-sm" style="max-width:260px;">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-barcode"></i></span>
                        <input type="text"
                               id="barcode_input"
                               class="form-control form-control-sm border-start-0"
                               placeholder="Scan barcode..."
                               autofocus>
                    </div>
                    <span id="barcode_msg" class="small text-muted" style="min-width:100px;"></span>
                    <form method="get" action="{{route('admin.order.cart_clear')}}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill delete-confirm" title="Clear Cart">
                            <i class="fas fa-trash-alt"></i> Cart Clear
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- ================= LEFT COLUMN ================= --}}
        <div class="col-lg-7">
            <div class="pos-card h-100">

                {{-- POS HEADER STRIP --}}
                <div class="pos-header-bar mb-3">
                    <div>
                        <h5> {{ __('Shop Store') }} </h5>
                        <small class="pos-badge-soft"> {{ __('Walk-in Customer POS') }} </small>
                    </div>
                    <div class="text-end">
                        <div style="font-size:12px;opacity:.8;"> {{ __('Session') }} </div>
                        <div style="font-weight:600;">SL-{{ date('dmy-His') }}</div>
                    </div>
                </div>

                {{-- CART TABLE --}}
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm mb-0 pos-cart-table">
                        <thead>
                            <tr>
                                <th>{{ __('Image') }}</th>
                                <th> {{ __('Item') }} </th>
                                <th>{{ __('Qty') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Subtotal') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartTable">
                            @include('backEnd.order.cart_table_rows')
                        </tbody>
                    </table>
                </div>

                {{-- COUPON SECTION --}}
                <div class="mb-3 p-3 rounded" style="background:#f8fafc; border:1px solid #e2e8f0;">
                    <div class="pos-section-title mb-2">কুপন কোড</div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <input type="text" id="pos_coupon_code" class="form-control form-control-sm" placeholder="কুপন কোড লিখুন" value="{{ Session::get('pos_coupon_code', '') }}" style="max-width:180px">
                        <button type="button" id="pos_apply_coupon" class="btn btn-sm btn-success rounded-pill">
                            <i class="fas fa-tag me-1"></i> অ্যাপ্লাই
                        </button>
                        <button type="button" id="pos_remove_coupon" class="btn btn-sm btn-outline-secondary rounded-pill" style="{{ Session::has('pos_coupon_code') ? '' : 'display:none' }}">
                            <i class="fas fa-times me-1"></i> রিমুভ
                        </button>
                        <span id="pos_coupon_msg" class="small text-muted"></span>
                    </div>
                </div>

                {{-- CUSTOMER + TOTAL --}}
                <form action="{{route('admin.order.store')}}" method="POST" class="row pos_form" data-parsley-validate="" enctype="multipart/form-data" id="pos_order_form">
                    @csrf
                    <input type="hidden" name="coupon_code" value="{{ Session::get('pos_coupon_code', '') }}">
                    <input type="hidden" name="order_type" value="pos">

                    {{-- CUSTOMER --}}
                    <div class="col-md-6">
                        <div class="pos-section-title"> {{ __('Customer') }} </div>

                        <div class="mb-2">
                            <input type="text"
                                   id="name"
                                   class="form-control form-control-sm @error('name') is-invalid @enderror"
                                   placeholder="Customer Name"
                                   name="name" required
                                   value="{{ Session::pull('pos_customer_name', old('name', '')) }}">
                            @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>

                        <div class="mb-2">
                            <input type="number"
                                   id="phone"
                                   class="form-control form-control-sm @error('phone') is-invalid @enderror"
                                   placeholder="Mobile Number"
                                   name="phone" required
                                   value="{{ Session::pull('pos_customer_phone', old('phone', '')) }}">
                            @error('phone')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>

                        <div class="mb-2">
                            <input type="text"
                                   id="address"
                                   class="form-control form-control-sm @error('address') is-invalid @enderror"
                                   placeholder="{{ __('Address') }}"
                                   name="address" required>
                            @error('address')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>

                        <div class="mb-2">
                            <select id="area"
                                    class="form-control form-control-sm @error('area') is-invalid @enderror"
                                    name="area" required>
                                <option value="">ডেলিভারি এরিয়া নির্বাচন করুন...</option>
                                @foreach($shippingcharge ?? [] as $area)
                                <option value="{{ $area->id }}" {{ old('area') == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }} (৳{{ $area->amount }})
                                </option>
                                @endforeach
                            </select>
                            @error('area')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>

                        {{-- 🆕 Payment Gateway Selection --}}
                        <div class="mb-2">
                            <label class="form-label small mb-1"><strong>Payment Method:</strong></label>
                            <select id="pos_payment_type"
                                    class="form-control form-control-sm"
                                    name="payment_type">
                                <option value="paid">{{ __('Paid (Cash/Card/Bank/MFS)') }}</option>
                                <option value="cod">{{ __('Cash on Delivery (COD)') }}</option>
                            </select>
                        </div>

                        {{-- 🆕 Payment Sub-Method (shown for both paid and COD) --}}
                        <div class="mb-2" id="pos_payment_sub_method_wrap">
                            <label class="form-label small mb-1"><strong>Payment Sub-Method:</strong></label>
                            <select id="pos_payment_sub_method"
                                    class="form-control form-control-sm"
                                    name="payment_method">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Bank">Bank Transfer</option>
                                <option value="MFS">MFS (bKash/Nagad/Rocket)</option>
                            </select>
                        </div>

                        {{-- 🆕 Payment Note / Transaction Info --}}
                        <div class="mb-2">
                            <label class="form-label small mb-1"><strong>Transaction Note (optional):</strong></label>
                            <input type="text"
                                   id="pos_payment_note"
                                   class="form-control form-control-sm"
                                   placeholder="Transaction ID / Reference"
                                   name="payment_note">
                        </div>

                    </div>

                    {{-- SUMMARY --}}
                    <div class="col-md-6">
                        <div class="pos-section-title"> {{ __('Summary') }} </div>

                        @php
                            $subtotal = Cart::instance('pos_shopping')->subtotal();
                            $subtotal = str_replace(',','',$subtotal);
                            $subtotal = str_replace('.00', '',$subtotal);
                            $shipping = Session::get('pos_shipping');
                            $couponDiscount = Session::get('pos_discount', 0);
                            $grand = ($subtotal + $shipping) - $couponDiscount;
                        @endphp

                        <table class="table table-borderless pos-summary-table mb-2" id="cart_details">
                            <tr>
                                <td> {{ __('Sub Total') }} </td>
                                <td class="text-end">৳{{$subtotal}}</td>
                            </tr>
                            <tr>
                                <td> {{ __('Shipping Fee') }} </td>
                                <td class="text-end">৳{{$shipping}}</td>
                            </tr>
                            <tr>
                                <td>কুপন ডিস্কাউন্ট</td>
                                <td class="text-end">৳{{$couponDiscount}}</td>
                            </tr>
                            <tr>
                                <td> {{ __('Grand Total') }} </td>
                                <td class="text-end pos-grand-total">৳{{$grand}}</td>
                            </tr>
                        </table>

                        <div class="text-end mt-1 d-flex gap-2 justify-content-end">
                            {{-- 🆕 Hold Cart Button --}}
                            <button type="button" id="btn-hold-cart" class="btn btn-warning rounded-pill btn-sm">
                                <i class="fas fa-pause me-1"></i> {{ __('Hold Cart') }}
                            </button>
                            <button type="submit" class="btn btn-pos-primary">
                                Complete Sale
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        {{-- ================= RIGHT COLUMN – PRODUCT LIST ================= --}}
        <div class="col-lg-5">
            <div class="pos-card h-100">

                {{-- SEARCH BAR --}}
                <div class="mb-2">
                    <div class="pos-section-title">{{ __('Products') }}</div>
                    <div class="pos-search-bar position-relative">
                        <span class="icon"><i class="fa fa-search"></i></span>
                        <input type="text"
                               id="product_search"
                               class="form-control form-control-sm"
                               placeholder="Search product by name...">
                    </div>
                </div>

                <div class="pos-products-wrapper">
                    <div class="row">
                        @foreach($products as $p)
                            @php $img = optional($p->image)->image ?? 'public/no-image.png'; @endphp
                            <div class="col-6 mb-2 pos-product-wrapper" data-name="{{ strtolower($p->name) }}">
                                <div class="pos-product-card pos-add-product" data-id="{{ $p->id }}">
                                    <span class="pos-stock-badge">Stock: {{ $p->stock}}</span>
                                    <img src="{{ asset($img) }}" class="pos-product-img" alt="">
                                    <div class="pos-product-name">{{ $p->name }}</div>
                                    <div class="pos-product-price">
                                        TK {{ $p->new_price ?? $p->old_price }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-validation.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-advanced.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs//summernote/summernote-lite.min.js"></script>

<script>
    $(".summernote").summernote({
        placeholder: "Enter Your Text Here",
    });
</script>

<script type="text/javascript">
    $(document).ready(function () {
        $(".select2").select2();
    });

    // -------- CART CONTENT LOADERS ----------
    function cart_content() {
        $.ajax({
            type: "GET",
            url: "{{route('admin.order.cart_content')}}",
            dataType: "html",
            success: function (cartinfo) {
                $("#cartTable").html(cartinfo);
            },
        });
    }
    function cart_details() {
        $.ajax({
            type: "GET",
            url: "{{route('admin.order.cart_details')}}",
            dataType: "html",
            success: function (cartinfo) {
                $("#cart_details").html(cartinfo);
            },
        });
    }

    // -------- PRODUCT CLICK -> ADD TO CART ----------
    $(document).on("click", ".pos-add-product", function (e) {
        e.preventDefault();
        var id = $(this).data("id");
        if (id) {
            $.ajax({
                cache: false,
                type: "GET",
                data: { id: id },
                url: "{{route('admin.order.cart_add')}}",
                dataType: "json",
                success: function (cartinfo) {
                    cart_content();
                    cart_details();
                },
            });
        }
    });

    // -------- CART QTY + / - (Delegated) ----------
    $(document).on("click", ".cart_increment", function (e) {
        e.preventDefault();
        var id = $(this).data("id");
        var qty = $(this).val();
        if (id) {
            $.ajax({
                cache: false,
                data: { id: id, qty: qty },
                type: "GET",
                url: "{{route('admin.order.cart_increment')}}",
                dataType: "json",
                success: function (cartinfo) {
                    cart_content();
                    cart_details();
                },
            });
        }
    });

    $(document).on("click", ".cart_decrement", function (e) {
        e.preventDefault();
        var id = $(this).data("id");
        var qty = $(this).val();
        if (id) {
            $.ajax({
                cache: false,
                type: "GET",
                data: { id: id, qty: qty },
                url: "{{route('admin.order.cart_decrement')}}",
                dataType: "json",
                success: function (cartinfo) {
                    cart_content();
                    cart_details();
                },
            });
        }
    });

    // -------- CART REMOVE ----------
    $(document).on("click", ".cart_remove", function (e) {
        e.preventDefault();
        var id = $(this).data("id");
        if (id) {
            $.ajax({
                cache: false,
                type: "GET",
                data: { id: id },
                url: "{{route('admin.order.cart_remove')}}",
                dataType: "json",
                success: function (cartinfo) {
                    cart_content();
                    cart_details();
                },
            });
        }
    });

    // -------- COUPON APPLY ----------
    $("#pos_apply_coupon").on("click", function () {
        var code = $("#pos_coupon_code").val().trim();
        if (!code) {
            $("#pos_coupon_msg").removeClass("text-success").addClass("text-danger").text("কুপন কোড লিখুন");
            return;
        }
        $.ajax({
            type: "POST",
            url: "{{ route('admin.order.pos.apply_coupon') }}",
            data: { _token: "{{ csrf_token() }}", coupon_code: code },
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    $("#pos_coupon_msg").removeClass("text-danger").addClass("text-success").text(res.message);
                    $("#pos_remove_coupon").show();
                    cart_details();
                } else {
                    $("#pos_coupon_msg").removeClass("text-success").addClass("text-danger").text(res.message || "কুপন বৈধ নয়");
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "ত্রুটি হয়েছে";
                $("#pos_coupon_msg").removeClass("text-success").addClass("text-danger").text(msg);
            }
        });
    });

    // -------- COUPON REMOVE ----------
    $("#pos_remove_coupon").on("click", function () {
        $.ajax({
            type: "GET",
            url: "{{ route('admin.order.pos.remove_coupon') }}",
            dataType: "json",
            success: function () {
                $("#pos_coupon_code").val("");
                $("#pos_coupon_msg").text("");
                $("#pos_remove_coupon").hide();
                cart_details();
            }
        });
    });

    // -------- SHIPPING CHANGE ----------
    $(document).on("change", "#area", function () {
        var id = $(this).val();
        $.ajax({
            type: "GET",
            data: { id: id },
            url: "{{route('admin.order.cart_shipping')}}",
            dataType: "json",
            success: function () {
                cart_content();
                cart_details();
            },
        });
    });

    // -------- SIZE / COLOR SELECT (variant price update) ----------
    function updateCartVariant(rowId, productId, sizeId, colorId) {
        var $row = $('.cart-size-selector[data-id="'+rowId+'"]').closest('tr');
        if (!$row.length) $row = $('.cart-color-selector[data-id="'+rowId+'"]').closest('tr');
        var $sizeSelect = $row.find('.cart-size-selector');
        var $colorSelect = $row.find('.cart-color-selector');
        var sId = sizeId !== undefined ? sizeId : ($sizeSelect.length ? $sizeSelect.val() : '');
        var cId = colorId !== undefined ? colorId : ($colorSelect.length ? $colorSelect.val() : '');
        var pid = productId || $row.find('.cart-size-selector, .cart-color-selector').first().data('product-id') || '';
        $.ajax({
            cache: false,
            type: "GET",
            data: { id: rowId, product_id: pid, size_id: sId || '', color_id: cId || '' },
            url: "{{ route('admin.order.cart.update') }}",
            dataType: "json",
            success: function () {
                cart_content();
                cart_details();
            },
        });
    }
    $(document).on("change", ".cart-size-selector", function () {
        var rowId = $(this).data("id");
        var productId = $(this).data("product-id");
        var sizeId = $(this).val();
        updateCartVariant(rowId, productId, sizeId, undefined);
    });
    $(document).on("change", ".cart-color-selector", function () {
        var rowId = $(this).data("id");
        var productId = $(this).data("product-id");
        var colorId = $(this).val();
        updateCartVariant(rowId, productId, undefined, colorId);
    });

    // -------- FORM SUBMIT - আগে Size/Color সিঙ্ক করুন --------
    var posFormSubmitting = false;
    $("#pos_order_form").on("submit", function (e) {
        if (posFormSubmitting) return;
        e.preventDefault();
        var form = this;
        var rows = [];
        $(".cart-size-selector, .cart-color-selector").each(function () {
            var rowId = $(this).data("id");
            if (rowId && rows.indexOf(rowId) === -1) rows.push(rowId);
        });
        if (rows.length === 0) {
            posFormSubmitting = true;
            form.submit();
            return;
        }
        var promises = [];
        rows.forEach(function (rowId) {
            var $row = $('.cart-size-selector[data-id="'+rowId+'"]').closest('tr');
            if (!$row.length) $row = $('.cart-color-selector[data-id="'+rowId+'"]').closest('tr');
            var sId = $row.find('.cart-size-selector').val() || '';
            var cId = $row.find('.cart-color-selector').val() || '';
            var productId = $row.find('.cart-size-selector, .cart-color-selector').first().data('product-id') || '';
            promises.push($.ajax({
                type: "GET",
                url: "{{ route('admin.order.cart.update') }}",
                data: { id: rowId, product_id: productId, size_id: sId, color_id: cId },
                dataType: "json"
            }));
        });
        $.when.apply($, promises).always(function () {
            posFormSubmitting = true;
            setTimeout(function () { form.submit(); }, 150);
        });
    });

    // -------- PRODUCT SEARCH (Right side) ----------
    $("#product_search").on("keyup", function () {
        var q = $(this).val().toLowerCase();
        $(".pos-product-wrapper").each(function(){
            var name = $(this).data("name");
            if(name.indexOf(q) !== -1){
                $(this).show();
            }else{
                $(this).hide();
            }
        });
    });

    // ============================================================
    // 🆕 BARCODE SCANNING
    // ============================================================
    $("#barcode_input").on("keypress", function (e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            var barcode = $(this).val().trim();
            if (!barcode) return;

            $.ajax({
                type: "GET",
                url: "{{ route('admin.order.scan_barcode', ['barcode' => '__BARCODE__']) }}".replace('__BARCODE__', encodeURIComponent(barcode)),
                dataType: "json",
                beforeSend: function () {
                    $("#barcode_msg").removeClass("text-danger text-success").text("Searching...");
                },
                success: function (res) {
                    if (res.success) {
                        $("#barcode_msg").removeClass("text-danger").addClass("text-success")
                            .text("✓ " + res.product.name);
                        cart_content();
                        cart_details();
                        $("#barcode_input").val("").focus();
                    }
                },
                error: function (xhr) {
                    var msg = "Product not found!";
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        msg = xhr.responseJSON.error;
                    }
                    $("#barcode_msg").removeClass("text-success").addClass("text-danger").text(msg);
                    $("#barcode_input").val("").focus();
                }
            });
        }
    });

    // Auto-focus barcode input unless user is typing elsewhere
    $(document).on("focusin", function (e) {
        var tag = e.target.tagName;
        if (tag === "INPUT" || tag === "SELECT" || tag === "TEXTAREA") {
            if (e.target.id !== "barcode_input") {
                window._barcodeAutoFocus = false;
            }
        }
    });
    $(document).on("focusout", function (e) {
        var tag = e.target.tagName;
        if (tag === "INPUT" || tag === "SELECT" || tag === "TEXTAREA") {
            window._barcodeAutoFocus = true;
            setTimeout(function () {
                if (window._barcodeAutoFocus !== false) {
                    $("#barcode_input").focus();
                }
            }, 100);
        }
    });

    // ============================================================
    // 🆕 HOLD CART FUNCTIONALITY
    // ============================================================
    $("#btn-hold-cart").on("click", function () {
        var customerName = $("#name").val() || "Walk-in Customer";
        var customerPhone = $("#phone").val() || "";
        var note = "";

        $.ajax({
            type: "POST",
            url: "{{ route('admin.order.hold_cart') }}",
            data: {
                _token: "{{ csrf_token() }}",
                customer_name: customerName,
                customer_phone: customerPhone,
                note: note
            },
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    toastr ? toastr.success(res.message) : alert(res.message);
                    cart_content();
                    cart_details();
                    $("#name, #phone, #address").val("");
                    $("#pos_coupon_code").val("");
                    $("#pos_coupon_msg").text("");
                    $("#pos_remove_coupon").hide();
                } else {
                    alert(res.message || "Failed to hold cart");
                }
            },
            error: function () {
                alert("An error occurred while holding the cart.");
            }
        });
    });

    // 🆕 Held Orders Modal/Panel
    $("#btn-held-orders").on("click", function () {
        $.ajax({
            type: "GET",
            url: "{{ route('admin.order.held_carts') }}",
            dataType: "json",
            success: function (carts) {
                var html = '<div class="modal fade" id="heldOrdersModal" tabindex="-1">' +
                    '<div class="modal-dialog modal-lg">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header"><h5 class="modal-title">Held Orders</h5>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
                    '<div class="modal-body">';

                if (!carts || carts.length === 0) {
                    html += '<p class="text-muted text-center">No held orders found.</p>';
                } else {
                    html += '<div class="table-responsive"><table class="table table-sm table-bordered">' +
                        '<thead><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Held At</th><th>Action</th></tr></thead><tbody>';

                    $.each(carts, function (i, c) {
                        var items = Array.isArray(c.cart_data) ? c.cart_data.length : (typeof c.cart_data === 'object' && c.cart_data ? Object.keys(c.cart_data).length : 0);
                        var heldAt = c.held_at ? new Date(c.held_at).toLocaleString() : '-';
                        html += '<tr>' +
                            '<td>' + (i+1) + '</td>' +
                            '<td>' + (c.customer_name || '-') + '<br><small>' + (c.customer_phone || '') + '</small></td>' +
                            '<td>' + items + ' items</td>' +
                            '<td>৳' + parseFloat(c.grand_total).toFixed(2) + '</td>' +
                            '<td><small>' + heldAt + '</small></td>' +
                            '<td>' +
                            '<button class="btn btn-sm btn-success restore-held me-1" data-id="' + c.id + '">Restore</button>' +
                            '<button class="btn btn-sm btn-danger delete-held" data-id="' + c.id + '">Delete</button>' +
                            '</td></tr>';
                    });

                    html += '</tbody></table></div>';
                }

                html += '</div></div></div></div>';
                $("body").append(html);
                $("#heldOrdersModal").modal("show");
                $("#heldOrdersModal").on("hidden.bs.modal", function () { $(this).remove(); });
            }
        });
    });

    // Restore held cart
    $(document).on("click", ".restore-held", function () {
        var id = $(this).data("id");
        if (!confirm("Restore this held cart? Current cart will be cleared.")) return;

        $.ajax({
            type: "POST",
            url: "{{ route('admin.order.restore_hold', ['id' => '__HOLD_ID__']) }}".replace('__HOLD_ID__', id),
            data: { _token: "{{ csrf_token() }}" },
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    $("#heldOrdersModal").modal("hide");
                    // Refresh cart UI via AJAX instead of full reload
                    cart_content();
                    cart_details();
                    // Re-fetch held orders count
                    $.get("{{ route('admin.order.held_carts') }}", function (data) {
                        if (data.success) {
                            $("#held-orders-count").text(data.heldCarts.length);
                        }
                    });
                } else {
                    alert("Failed to restore cart");
                }
            },
            error: function () {
                alert("Error restoring cart");
            }
        });
    });

    // Delete held cart
    $(document).on("click", ".delete-held", function () {
        var id = $(this).data("id");
        if (!confirm("Delete this held cart?")) return;

        $.ajax({
            type: "POST",
            url: "{{ route('admin.order.delete_hold', ['id' => '__HOLD_ID__']) }}".replace('__HOLD_ID__', id),
            data: { _token: "{{ csrf_token() }}", _method: "DELETE" },
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    $("#heldOrdersModal").modal("hide");
                    setTimeout(function () { $("#btn-held-orders").click(); }, 300);
                } else {
                    alert("Failed to delete cart");
                }
            },
            error: function () {
                alert("Error deleting cart");
            }
        });
    });
</script>
@endsection
