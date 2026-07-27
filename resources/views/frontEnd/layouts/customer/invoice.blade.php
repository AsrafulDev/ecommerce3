@extends('frontEnd.layouts.master')
@section('title','Customer Invoice')
@section('content')

@php
    // ১. পেমেন্ট ইনফো নেওয়া (Latest payment)
    $payment = \App\Models\Payment::where('order_id', $order->id)->orderBy('id','desc')->first();

    // ২. স্ট্যাটাস লোয়ারকেস করা
    $gateway_status = $payment ? strtolower(trim($payment->payment_status)) : ''; 
    $payment_method = $payment ? strtolower(trim($payment->payment_method)) : strtolower(trim($order->payment->payment_method ?? ''));
    
    $admin_status   = strtolower(trim($order->payment_status ?? ''));
    $order_status   = $order->status ? strtolower(trim($order->status->slug)) : ($order->order_status ?? '');
    $order_status_label = \App\Enums\OrderStatus::tryFrom($order->order_status)?->label() ?? ($order->status->name ?? 'Processing');

    // ৩. গ্র্যান্ড টোটাল
    $grand_total = $order->amount;
    $paid_amount = 0;

    // ======================================================
    // ⭐ ইনভয়েস ক্যালকুলেশন লজিক (Exact Logic from Account Page)
    // ======================================================

    // ১. পেমেন্ট রেকর্ড থেকে আসল টাকাটা বের করি
    if ($payment && !in_array($gateway_status, ['failed', 'cancel', 'cancelled', 'rejected'])) {
        $paid_amount = $payment->amount;
    }

    // ২. COD ফিক্স: COD হলে এবং অর্ডার কমপ্লিট না হলে টাকা ০ দেখাবে (ভুল এড়াতে)
    $is_cod = in_array($payment_method, ['cod', 'cash', 'cash_on_delivery', 'hand cash']);
    $is_order_completed = in_array($order_status, ['completed', 'delivered']) || in_array($admin_status, ['completed', 'delivered']);

    if ($is_cod && !$is_order_completed) {
        if ($paid_amount >= $grand_total) {
            $paid_amount = 0; 
        }
    }

    // ৩. ফোর্স ফুল পেইড (Admin Priority):
    if ($is_order_completed) {
        $paid_amount = $grand_total;
    } 
    elseif (($paid_amount == 0 || !$payment) && in_array($admin_status, ['paid', 'success', 'approved'])) {
        $paid_amount = $grand_total;
    }

    // ৪. ডিউ ক্যালকুলেশন
    $due_amount = max(0, $grand_total - $paid_amount);

    // ৫. স্ট্যাটাস চেক (ডিসপ্লে এর জন্য)
    $is_failed = false;
    if ($paid_amount == 0 && in_array($gateway_status, ['failed', 'cancel', 'cancelled'])) {
        $is_failed = true;
    }
@endphp

<style>
    .customer-invoice { margin: 25px 0; }
    .invoice_btn{ margin-bottom: 15px; }
    td{ font-size: 16px; }
    
    #invoice-pdf-area { 
        background: var(--card-bg, #fff); 
        max-width: 850px; 
        margin: 0 auto; 
        border-radius: 12px; 
        box-shadow: 0 5px 15px color-mix(in srgb, var(--primary-color, #0d6efd) 8%, transparent); 
        padding: 30px;
    }
    #invoice-pdf-area table { width: 100%; border-collapse: collapse; }
    #invoice-pdf-area .invoice-bar { background: var(--primary-color, #00aef0); padding: 20px 40px; }
    #invoice-pdf-area .invoice-bar p { color: #fff; font-weight: bold; }
    #invoice-pdf-area .invoice_form p { margin: 3px 0; font-size: 14px; }
    #invoice-pdf-area .invoice_to p { margin: 3px 0; font-size: 14px; }

   @page { size: a4;  margin: 0mm; background:#fff }
   @media print {
        td{ font-size: 18px; }
        header,footer,nav,.no-print,.sidebar,#sidebar,.navbar,.main-nav,.top-header,.header-section,.footer-section,
        .page-header,.main-footer,.site-header,.site-footer,.mobile-nav,.top-bar { display: none !important; }
        body { background: #fff !important; margin: 0; padding: 0; }
        .container { width: 100% !important; max-width: 100% !important; padding: 0 !important; }
        #invoice-pdf-area { width: 100% !important; max-width: 100% !important; box-shadow: none !important; margin: 0 !important; }
        .invoice-innter { width: 100% !important; }
        table { width: 100% !important; }
        .customer-invoice { padding: 0 !important; }
   }
</style>

<section class="customer-invoice">
    <div class="container">
        <div class="row">

            <div class="col-sm-6">
                <a href="{{route('customer.orders')}}">
                    <strong><i class="fa-solid fa-arrow-left"></i> Back To Order</strong>
                </a>
            </div>

            <div class="col-sm-6 text-end">
                <button onclick="downloadPDF()" class="no-print invoice_btn btn btn-success me-2">
                    <i class="fa fa-download"></i>{{ __('Download Invoice') }}</button>
                <button onclick="printFunction()" class="no-print invoice_btn btn btn-primary">
                    <i class="fa fa-print"></i>{{ __('Print') }}</button>
            </div>

            <div class="col-sm-12">

                <div id="invoice-pdf-area" style="width: 800px; max-width: 100%; margin: 0 auto; background: #fff; padding: 20px; font-size: 14px;">

                    {{-- ===================== INVOICE HEADER ===================== --}}
                    <table style="width:100%">
                        <tr>
                            <td style="width: 40%; float: left; padding-top: 15px;">

                                <img src="{{asset($generalsetting->white_logo)}}" style="margin-top:25px !important;width:150px">

                                <div style="margin: 20px 0;">
                                    <p style="font-size: 14px; color: var(--heading-color, #222); margin-bottom: 5px;">
                                        <strong>Payment Method:</strong> 
                                        <span style="text-transform: uppercase;">{{ $payment_method }}</span>
                                    </p>
                                    
                                    {{-- পেমেন্ট স্ট্যাটাস ডিসপ্লে --}}
                                    <p style="font-size: 14px; color: var(--heading-color, #222);">
                                        <strong>Status:</strong>
                                        @if($paid_amount >= $grand_total)
                                            <span style="color: green; font-weight: bold; text-transform: uppercase;">PAID</span>
                                        @elseif($is_failed)
                                            <span style="color: red; font-weight: bold; text-transform: uppercase;">FAILED</span>
                                        @elseif($paid_amount > 0)
                                            <span style="color: #007bff; font-weight: bold; text-transform: uppercase;">PARTIAL PAID</span>
                                        @else
                                            <span style="color: red; font-weight: bold; text-transform: uppercase;">UNPAID</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="invoice_form">
                                    <p><strong>Invoice From:</strong></p>
                                    <p>{{$generalsetting->name}}</p>
                                    <p>{{ $contact->phone ?? '' }}</p>
                                    <p>{{ $contact->email ?? '' }}</p>
                                    <p>{{ $contact->address ?? '' }}</p>
                                    
                                    @if(!empty($order->order_note) || !empty($order->note))
                                        <p style="font-size:16px; line-height:1.8; color:#222; margin-top: 10px;">
                                            <strong>Order Note:</strong> {{ $order->order_note ?? $order->note }}
                                        </p>
                                    @endif
                                </div>
                            </td>

                            <td style="width:60%;float: left;">
                                <div class="invoice-bar" style="background:var(--primary-color, #00aef0); padding: 15px 30px; border-radius: 4px;">
                                    <p style="font-size: 30px; color: #fff; text-align: right; font-weight: bold; margin: 0;">{{ __('Invoice') }}</p>
                                </div>

                                <div class="invoice-bar" style="background:color-mix(in srgb, var(--primary-color, #00aef0) 8%, transparent); padding: 12px 20px; margin-top: 6px; text-align:right; border-radius: 4px; border: 1px solid color-mix(in srgb, var(--primary-color, #00aef0) 25%, transparent);">
                                   <p style="display:inline-block; margin: 0 10px; font-size: 15px; color: var(--heading-color, #1a3a5c);">Invoice Date: <strong style="color: var(--primary-color, #0d6efd);">{{$order->created_at->format('d-m-y')}}</strong></p>
                                   <p style="display:inline-block; margin: 0 10px; font-size: 15px; color: var(--heading-color, #1a3a5c);">Invoice No: <strong style="color: var(--primary-color, #0d6efd);">{{$order->invoice_id}}</strong></p>
                                </div>

                                <div class="invoice_to" style="padding-top: 20px;">
                                    <p><strong>Invoice To:</strong></p>
                                    <p>{{$order->shipping?$order->shipping->name:''}}</p>
                                    <p>{{$order->shipping?$order->shipping->phone:''}}</p>
                                    <p>{{$order->shipping?$order->shipping->address:''}}</p>
                                    <p>{{$order->shipping?$order->shipping->area:''}}</p>
                                </div>
                            </td>
                        </tr>
                    </table>

                    {{-- ===================== PRODUCTS TABLE ===================== --}}
                    <table class="table" style="margin-top: 30px;">
                        <thead style="background: var(--primary-color, #00aef0); color: #fff;">
                            <tr>
                                <th>{{ __('SL') }}</th>
                                <th>Product</th>
                                <th>{{ __('Unit Price') }}</th>
                                <th>{{ __('Discount') }}</th>
                                <th>{{ __('Warranty') }}</th>
                                <th>{{ __('Qty') }}</th>
                                <th>{{ __('Subtotal') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($order->orderdetails as $value)
                            @php
                                $productDiscount = (float) ($value->product_discount ?? 0);
                                $warrantyPrice   = (float) ($value->warranty_price ?? 0);
                                $lineSubtotal    = $value->sale_price * $value->qty;
                            @endphp
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>
                                    {{$value->product_name}} <br>
                                    @php
                                        $sizeDisplay = null;
                                        $colorDisplay = null;
                                        if ($value->size) {
                                            $sizeDisplay = $value->size->sizeName ?? $value->size->size_name ?? $value->size->name ?? null;
                                        } elseif ($value->product_size) {
                                            $s = \App\Models\Size::find($value->product_size);
                                            $sizeDisplay = $s ? ($s->sizeName ?? $s->size_name ?? null) : null;
                                        }
                                        if ($value->color) {
                                            $colorDisplay = $value->color->getDisplayName() ?? $value->color->colorName ?? $value->color->color_name ?? $value->color->name ?? null;
                                        } elseif ($value->product_color) {
                                            $c = \App\Models\Color::find($value->product_color);
                                            $colorDisplay = $c ? ($c->getDisplayName() ?? $c->colorName ?? $c->color_name ?? null) : null;
                                        }
                                    @endphp
                                    @if($sizeDisplay) <small>Size: {{ $sizeDisplay }}</small> @endif
                                    @if($colorDisplay) <small>Color: {{ $colorDisplay }}</small> @endif
                                    {{-- 🛡️ Warranty --}}
                                    @if($value->warranty_tier_id)
                                        @php
                                            $wt = \App\Models\ProductWarrantyTier::find($value->warranty_tier_id);
                                            $ws = \App\Models\WarrantySale::where('order_detail_id', $value->id)->first();
                                        @endphp
                                        @if($wt && $wt->warranty_days > 0)
                                            <br><small class="text-success">
                                                🛡️ {{ $wt->tier_name }} ({{ $wt->warranty_days }} Days)
                                                @if($ws && $ws->warranty_end_date)
                                                    — Expires: {{ $ws->warranty_end_date->format('d M, Y') }}
                                                    ({{ max(0, (int) now()->diffInDays($ws->warranty_end_date)) }} days left)
                                                @endif
                                            </small>
                                        @endif
                                    @endif
                                </td>
                                <td>৳{{ number_format($value->sale_price, 2) }}</td>
                                <td>
                                    @if($productDiscount > 0)
                                        <span class="text-danger">-৳{{ number_format($productDiscount, 2) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($warrantyPrice > 0)
                                        <span class="text-success">+৳{{ number_format($warrantyPrice, 2) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{$value->qty}}</td>
                                <td>৳{{ number_format($lineSubtotal, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- ===================== TOTAL CALCULATION ===================== --}}
                    @php
                        $shipping = $order->shipping_charge;
                        $discount = $order->discount;

                        // 💰 Total product-level discount (wholesale)
                        $totalProductDiscount = 0;
                        foreach ($order->orderdetails as $item) {
                            $totalProductDiscount += ((float) ($item->product_discount ?? 0)) * $item->qty;
                        }

                        // 🛡️ Total warranty charges
                        $totalWarrantyCharge = 0;
                        foreach ($order->orderdetails as $item) {
                            $totalWarrantyCharge += ((float) ($item->warranty_price ?? 0)) * $item->qty;
                        }

                        // ⭐ Subtotal = product prices only (warranty excluded — shown as separate line)
                        $subtotal = ($order->amount + $order->discount) - $order->shipping_charge - $totalWarrantyCharge;
                    @endphp

                    <div class="invoice-bottom">
                        <table class="table" style="width: 350px; float: right; margin-bottom: 30px;">
                            <tbody style="background:var(--primary-color, #00aef0); color:#fff;">

                                <tr>
                                    <td><strong>SubTotal</strong></td>
                                    <td><strong>৳{{ number_format($subtotal, 2) }}</strong></td>
                                </tr>

                                @if($totalProductDiscount > 0)
                                <tr style="background:color-mix(in srgb, var(--primary-color, #00aef0) 70%, #dc3545);">
                                    <td><strong>Wholesale Discount(-)</strong></td>
                                    <td><strong>-৳{{ number_format($totalProductDiscount, 2) }}</strong></td>
                                </tr>
                                @endif

                                @if($totalWarrantyCharge > 0)
                                <tr style="background:color-mix(in srgb, var(--primary-color, #00aef0) 70%, #28a745);">
                                    <td><strong>🛡️ Warranty Charge(+)</strong></td>
                                    <td><strong>+৳{{ number_format($totalWarrantyCharge, 2) }}</strong></td>
                                </tr>
                                @endif

                                <tr>
                                    <td><strong>Shipping(+)</strong></td>
                                    <td><strong>৳{{ number_format($shipping, 2) }}</strong></td>
                                </tr>

                                <tr>
                                    <td><strong>Coupon Discount(-)</strong></td>
                                    <td><strong>৳{{ number_format($discount, 2) }}</strong></td>
                                </tr>

                                <tr>
                                    <td><strong>Total Amount</strong></td>
                                    <td><strong>৳{{ number_format($grand_total, 2) }}</strong></td>
                                </tr>

                                {{-- ========== Paid & Due Display ========== --}}
                                @if($paid_amount > 0 && $due_amount > 0)
                                    {{-- পার্শিয়াল পেমেন্ট (Advance) --}}
                                    <tr style="background:#27ae60;">
                                        <td><strong>Paid / Advance</strong></td>
                                        <td><strong>৳{{ number_format($paid_amount, 2) }}</strong></td>
                                    </tr>
                                    <tr style="background:#c0392b;">
                                        <td><strong>Due Amount</strong></td>
                                        <td><strong>৳{{ number_format($due_amount, 2) }}</strong></td>
                                    </tr>
                                @elseif($paid_amount >= $grand_total)
                                    {{-- ফুল পেইড --}}
                                    <tr style="background:#27ae60;">
                                        <td><strong>Paid Amount</strong></td>
                                        <td><strong>৳{{ number_format($paid_amount, 2) }}</strong></td>
                                    </tr>
                                    <tr style="background:#2ecc71;">
                                        <td><strong>Due Amount</strong></td>
                                        <td><strong>৳0.00</strong></td>
                                    </tr>
                                @else
                                    {{-- আনপেইড --}}
                                    <tr style="background:#e74c3c;">
                                        <td><strong>Paid Amount</strong></td>
                                        <td><strong>৳0.00</strong></td>
                                    </tr>
                                    <tr style="background:#c0392b;">
                                        <td><strong>Due Amount</strong></td>
                                        <td><strong>৳{{ number_format($grand_total, 2) }}</strong></td>
                                    </tr>
                                @endif

                            </tbody>
                        </table>

                        <div class="terms-condition" style="overflow: hidden; width: 100%; text-align: center; padding: 20px 0;">
                            <h5 style="font-style: italic;">
                                <a href="{{route('page',['slug'=>'terms-condition'])}}">{{ __('Terms & Conditions') }}</a>
                            </h5>
                            <p style="text-align: center; font-style: italic; font-size: 15px;">* This is a computer generated invoice.</p>
                        </div>
                    </div>

                </div> 
            </div>
        </div>
    </div>
</section>

<script>
    function printFunction() {
        window.print();
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('invoice-pdf-area');
    const invoice_id = "{{ $order->invoice_id }}";
    const opt = {
        margin: [10, 10, 10, 10],
        filename: 'Invoice-' + invoice_id + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] }
    };
    
    // Clone & clean the DOM for PDF (remove transforms, fix widths)
    const clone = element.cloneNode(true);
    clone.style.width = '800px';
    clone.style.maxWidth = '100%';
    clone.style.transform = 'none';
    // Remove skew transforms
    clone.querySelectorAll('[style*="skew"]').forEach(el => {
        el.style.transform = 'none';
    });
    // Fix float-right tables
    clone.querySelectorAll('[style*="float"]').forEach(el => {
        el.style.float = 'none';
        el.style.width = '100%';
    });
    // Fix margin-left on bars
    clone.querySelectorAll('[style*="margin-left"]').forEach(el => {
        el.style.marginLeft = '0';
    });
    // Temporarily replace
    const parent = element.parentNode;
    parent.insertBefore(clone, element);
    element.style.display = 'none';
    
    html2pdf().set(opt).from(clone).save().then(() => {
        clone.remove();
        element.style.display = '';
    });
}
</script>

@endsection