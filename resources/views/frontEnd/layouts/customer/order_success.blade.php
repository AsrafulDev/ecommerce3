@extends('frontEnd.layouts.master')
@section('title','{{ __('{{ __('Inv') }}oice') }} #' . $order->invoice_{{ __('id)') }}
@section('content')

@php
    // ১. {{ __('bn_f0a1817c') }} ইনফো নেওয়া
    $payment = \App\Models\Payment::w{{ __('here') }}('order_id', $order->{{ __('id)') }}->orderBy('id','desc')->first();

    $gateway_status = $payment ? strtolower(trim($payment->payment_status)) : ''; 
    $payment_method = $payment ? strtolower(trim($payment->payment_method)) : strtolower(trim($order->payment_method ?? ''));
    
    $admin_status   = strtolower(trim($order->payment_status ?? ''));
    $order_status   = strtolower(trim($order->status ?? ''));

    $grand_{{ __('total') }} = $order->amount;
    $paid_amount = 0;

    // {{ __('bn_f0a1817c') }} রেকর্ড থেকে আসল টাকাটা বের করি
    if ($payment && !in_array($gateway_status, ['failed','cancel','cancelled','rejected'])) {
        $paid_amount = $payment->amount;
    }

    // {{ __('COD') }} FIX
    $is_cod = in_array($payment_method, ['cod','cash','cash_on_delivery','hand cash','hand_cash']);

    $is_order_completed =
        in_array($order_status, ['completed','delivered']) ||
        in_array($admin_status, ['completed','delivered']);

    if ($is_cod && !$is_order_completed) {
        if ($paid_amount >= $grand_{{ __('total') }}) {
            $paid_amount = 0;
        }
    }

    // ADMIN PRIORITY
    if ($is_order_completed) {
        $paid_amount = $grand_{{ __('total') }};
    }
    elseif (($paid_amount == 0 || !$payment) && in_array($admin_status, ['paid','success','approved'])) {
        $paid_amount = $grand_{{ __('total') }};
    }

    // {{ __('Due') }}
    $due_amount = max(0, $grand_{{ __('total') }} - $paid_amount);
    $sub{{ __('total') }} = ($order->amount + $order->discount) - $order->shipping_charge;

    // ⭐ ডিজিটাল {{ __('Download') }} লজিক — যদি ফুল {{ __('bn_623a38a3') }} হয় তবেই {{ __('Download') }} লিংক দেখাবে
    $is_fully_paid = ($paid_amount >= $grand_{{ __('total') }});
    $downloads = $is_fully_paid ? \App\Models\DigitalDownload::w{{ __('here') }}('order_id', $order->{{ __('id)') }}->get() : collect();
@endphp

<style>
    @import url('{{ __('https://') }}fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

    .invoice-wrapper { background: #f8fafc; padding: 30px 15px; font-family: 'Plus Jakarta Sans', sans-serif; }
    #invoice-pdf-area { background: #fff; max-width: 850px; margin: 0 auto; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .inv-container { padding: 40px; }
    .inv-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px; margin-bottom: 40px; }
    .inv-logo img { width: 150px; height: auto; margin-bottom: 15px; }
    .inv-title h1 { font-size: 28px; font-weight: 800; color: #0f172a; margin: 0; }
    .inv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .info-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px; }
    .info-val { font-size: {{ __('14px') }}; color: #1e293b; line-height: 1.5; }
    .table-responsive { margin: 30px 0; }
    .inv-table { width: 100%; border-collapse: collapse; }
    .inv-table th { background: #f1f5f9; padding: 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569; }
    .inv-table td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: {{ __('14px') }}; }
    .sum-wrapper { display: flex; justify-content: flex-end; }
    .sum-box { width: 100%; max-width: 320px; }
    .sum-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: {{ __('14px') }}; }
    .{{ __('total') }}-row { border-top: 2px solid #0f172a; margin-top: 10px; padding-top: 15px; font-weight: 800; font-size: 18px; color: #000; }
    .payment-badge-box { background: #0f172a; color: #fff; padding: 15px; border-radius: 8px; margin-top: 15px; }
    .status-tag { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; margin-top: 8px; }
    .bg-paid-light { background: #dcfce7; color: #15803d; }
    .bg-due-light { background: #fee2e2; color: #b91c1c; }

    /* ডিজিটাল আইটেম {{ __('Download') }} বক্স */
    .digital-download-box {
        max-width: 850px;
        margin: 20px auto;
        background: #f0f9ff;
        border: 1px dashed #0ea5e9;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    .dl-btn {
        display: inline-block;
        background: #0284c7;
        color: #fff;
        padding: 10px 25px;
        border-radius: 50px;
        text-decoration: none !important;
        font-weight: 700;
        margin-top: 10px;
        transition: 0.3s;
    }
    .dl-btn:hover { background: #0369a1; transform: translateY(-2px); }

    @media print {
        .no-print { display: none !important; }
        body { background: white; }
        .invoice-wrapper { padding: 0; }
        #invoice-pdf-area { box-shadow: none; width: 100%; }
    }
</style>

<div class="invoice-wrapper">
    <div class="container no-print mb-4">
        <div class="d-flex justify-content-center gap-2">
            <a href="{{route('customer.orders')}}" class="btn btn-dark btn-sm rounded-pill px-4">
               <i class="fa fa-arrow-left me-1"></i>{{ __('Back') }}</a>
            <button onclick="downloadPDF()" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                <i class="fa fa-download me-1"></i>{{ __('Download {{ __('{{ __('Inv') }}oice') }}') }}</button>
        </div>
    </div>

    {{-- ⭐ ডিজিটাল আইটেম {{ __('Download') }} সেকশন (শুধুমাত্র {{ __('bn_623a38a3') }} হলে দেখাবে) ⭐ --}}
    @if($is_fully_paid && $downloads->count() > 0)
    <div class="digital-download-box no-print">
        <h6 class="fw-bold text-dark mb-1"><i class="fa fa-cloud-download me-2"></i> {{ __('bn_8a7f32b2') }}</h6>
        <p class="small text-muted mb-3">{{ __('bn_50a15ade') }}</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            @foreach($downloads as $dl)
                <a href="{{ route('digital.download', $dl->token) }}" class="dl-btn">
                    Download: {{ $dl->product->name }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <div id="invoice-pdf-area">
        <div class="inv-container">
            <div class="inv-header">
                <div class="inv-logo">
                    <img src="{{asset($generalsetting->white_logo)}}" alt="Logo">
                    <div class="info-val">
                        <strong>{{$generalsetting->name}}</strong><br>
                        <span class="text-muted small">{{$contact->address}}</span><br>
                        <span class="text-muted small">{{ __('Phone') }}: {{$contact->{{ __('phone') }}}}</span>
                    </div>
                </div>
                <div class="text-end">
                    <div class="inv-title"><h1>{{ __('INVOICE') }}</h1></div>
                    <p class="mb-0 fw-bold">#{{$order->invoice_id}}</p>
                    <p class="text-muted small">{{ __('Date') }}: {{$order->created_at->format('d M, Y')}}</p>
                </div>
            </div>

            <hr class="my-4" style="opacity: 0.1;">

            <div class="inv-grid">
                <div>
                    <span class="info-label">{{ __('{{ __('Customer') }} Details') }}</span>
                    <div class="info-val">
                        <strong class="d-block mb-1">{{$order->shipping ? $order->shipping->name : '{{ __('N/A') }}'}}</strong>
                        {{$order->shipping ? $order->shipping->{{ __('phone') }} : ''}}<br>
                        {{$order->shipping ? $order->shipping->address : ''}}
                    </div>
                </div>
                <div class="text-end">
                    <span class="info-label">{{ __('{{ __('Payment Info') }}rmation') }}</span>
                    <div class="info-val text-uppercase fw-bold">{{ $payment_method }}</div>
                    <span class="status-tag {{ $paid_amount >= $grand_{{ __('total') }} ? 'bg-paid-light' : 'bg-due-light' }}">
                        {{ $paid_amount >= $grand_{{ __('total') }} ? '✔ {{ __('Verified') }} {{ __('Paid') }}' : '✘ Payment Outstanding' }}
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>{{ __('{{ __('Product') }} Description') }}</th>
                            <th class="text-center">{{ __('Price') }}</th>
                            <th class="text-center">{{ __('Qty') }}</th>
                            <th class="text-end">{{ __('{{ __('Total') }}') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->orderdetails as $item)
                        <tr>
                            <td>
                                <span class="fw-bold d-block">{{$item->product_name}}</span>
                                @php
                                    $sizeDisplay = $item->size ? ($item->size->size{{ __('Name') }} ?? $item->size->size_name ?? $item->size->name ?? null) : null;
                                    $colorDisplay = $item->color ? ($item->color->getDisplay{{ __('Name') }}() ?? $item->color->color{{ __('Name') }} ?? $item->color->color_name ?? $item->color->name ?? null) : null;
                                    if (!$sizeDisplay && $item->product_size) {
                                        $s = \App\Models\Size::find($item->product_size);
                                        $sizeDisplay = $s ? ($s->size{{ __('Name') }} ?? $s->size_name ?? null) : null;
                                    }
                                    if (!$colorDisplay && $item->product_color) {
                                        $c = \App\Models\{{ __('Color') }}::find($item->product_color);
                                        $colorDisplay = $c ? ($c->getDisplay{{ __('Name') }}() ?? $c->color{{ __('Name') }} ?? $c->color_name ?? null) : null;
                                    }
                                @endphp
                                @if($sizeDisplay)<small class="text-muted d-block">Size: {{ $sizeDisplay }}</small>@endif
                                @if($colorDisplay)<small class="text-muted d-block">{{ __('Color') }}: {{ $colorDisplay }}</small>@endif
                            </td>
                            <td class="text-center">৳{{number_format($item->sale_price, 2)}}</td>
                            <td class="text-center">{{$item->qty}}</td>
                            <td class="text-end fw-bold">৳{{number_format($item->sale_price * $item->qty, 2)}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sum-wrapper">
                <div class="sum-box">
                    <div class="sum-row">
                        <span class="text-muted">{{ __('Sub{{ __('total') }}') }}</span>
                        <span>৳{{number_format($sub{{ __('total') }}, 2)}}</span>
                    </div>
                    <div class="sum-row">
                        <span class="text-muted">{{ __('{{ __('Shipping') }} Fee') }}</span>
                        <span>৳{{number_format($order->shipping_charge, 2)}}</span>
                    </div>
                    @if($order->discount > 0)
                    <div class="sum-row text-danger">
                        <span>{{ __('Discount') }}</span>
                        <span>-৳{{number_format($order->discount, 2)}}</span>
                    </div>
                    @endif
                    
                    <div class="sum-row {{ __('total') }}-row">
                        <span>{{ __('Grand {{ __('Total') }}') }}</span>
                        <span>৳{{number_format($grand_{{ __('total') }}, 2)}}</span>
                    </div>

                    <div class="payment-badge-box">
                        <div class="sum-row border-0 p-0 mb-2">
                            <span style="color: #4ade80;">{{ __('{{ __('Paid') }} {{ __('Amount') }}') }}</span>
                            <span class="fw-bold">৳{{ number_format($paid_amount, 2) }}</span>
                        </div>
                        <div class="sum-row border-0 p-0">
                            <span style="color: #fb7185;">{{ __('Remaining {{ __('Due') }}') }}</span>
                            <span class="fw-bold">৳{{ number_format($due_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-4 text-center border-top">
                <p class="small text-muted mb-0">{{ __('Thank you for your business! This is a computer-generated invoice.') }}</p>
                <p class="fw-bold small text-uppercase mt-1">{{$generalsetting->name}}</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
(function () {
    var orderId    = '{{ $order->invoice_id }}';
    var storageKey = 'purchase_fired_' + orderId;

    // একই order refresh করলে duplicate fire {{ __('Close') }} করতে localStorage চেক
    if (localStorage.get{{ __('Item') }}(storageKey)) return;
    localStorage.set{{ __('Item') }}(storageKey, '1');

    @php
        $purchase{{ __('{{ __('Item') }}s') }} = [];
        foreach ($order->orderdetails as $item) {
            $purchase{{ __('{{ __('Item') }}s') }}[] = [
                'item_id'       => (string) ($item->product_id ?? $item->{{ __('id)') }},
                'item_name'     => $item->product_name,
                'price'         => (float) $item->sale_price,
                'quantity'      => (int)   $item->qty,
                'item_category' => optional($item->product?->category)->name ?? '',
            ];
        }
        $couponCode  = $order->coupon_code ?? null;
        $eventId     = 'purchase_' . $order->invoice_id;
        $cust{{ __('Phone') }}   = $order->shipping?->{{ __('phone') }} ?? '';
        $cust{{ __('Name') }}    = $order->shipping?->name  ?? '';
        $custAddress = $order->shipping?->address ?? '';
        $cust{{ __('Area') }}    = $order->shipping?->area ?? '';
    @endphp

    var purchase{{ __('{{ __('Item') }}s') }}  = @json($purchase{{ __('{{ __('Item') }}s') }});
    var grand{{ __('Total') }}     = parseFloat("{{ $order->amount }}") || 0;
    var shippingCharge = parseFloat("{{ $order->shipping_charge }}") || 0;
    var discount       = parseFloat("{{ $order->discount }}") || 0;
    var coupon         = @json($couponCode);
    var payment{{ __('Method') }}  = "{{ $payment_method }}";
    var eventId        = "{{ $eventId }}";

    // Facebook browser cookies (CAPI deduplication-এর জন্য)
    var fbp = getCookie('_fbp');
    var fbc = getCookie('_fbc');
    function getCookie(name) {
        var m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return m ? m.pop() : '';
    }

    // ── GTM / GA4 — purchase event (full ecommerce + customer data) ──
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    window.dataLayer.push({
        event: 'purchase',
        ecommerce: {
            transaction_id: orderId,
            value:          grand{{ __('Total') }},
            tax:            0,
            shipping:       shippingCharge,
            currency:       'BDT',
            coupon:         coupon,
            payment_method: payment{{ __('Method') }},
            items:          purchase{{ __('{{ __('Item') }}s') }}
        },
        user_data: {
            customer_id:  '{{ $order->customer_id }}',
            name:         "{{ addslashes($cust{{ __('Name') }}) }}",
            {{ __('phone') }}:        "{{ $cust{{ __('Phone') }} }}",
            address:      "{{ addslashes($custAddress) }}",
            area:         "{{ addslashes($cust{{ __('Area') }}) }}",
            fbp:          fbp,
            fbc:          fbc,
            client_ip:    '{{ request()->ip() }}'
        },
        order_info: {
            invoice_id:     orderId,
            order_id:       '{{ $order->id }}',
            payment_method: payment{{ __('Method') }},
            payment_status: '{{ $payment ? $payment->payment_status : "" }}',
            grand_{{ __('total') }}:    grand{{ __('Total') }},
            shipping:       shippingCharge,
            discount:       discount,
            coupon:         coupon,
            item_count:     purchase{{ __('{{ __('Item') }}s') }}.length
        }
    });

    // ── Facebook Pixel — Purchase (browser-side, event_id দিয়ে CAPI-র সাথে deduplicate) ──
    if (typeof fbq === 'function') {
        fbq('track', 'Purchase', {
            value:        grand{{ __('Total') }},
            currency:     'BDT',
            content_ids:  purchase{{ __('{{ __('Item') }}s') }}.map(function(i){ return i.item_id; }),
            contents:     purchase{{ __('{{ __('Item') }}s') }}.map(function(i){ return { id: i.item_id, quantity: i.quantity, item_price: i.price }; }),
            content_type: 'product',
            num_items:    purchase{{ __('{{ __('Item') }}s') }}.length,
            order_id:     orderId
        }, { eventID: eventId });
    }

    // ── TikTok Pixel — {{ __('Complete') }}Payment ──
    if (typeof ttq !== 'undefined') {
        ttq.track('{{ __('Complete') }}Payment', {
            content_type: 'product',
            quantity:     purchase{{ __('{{ __('Item') }}s') }}.length,
            value:        grand{{ __('Total') }},
            currency:     'BDT',
            order_id:     orderId,
            contents:     purchase{{ __('{{ __('Item') }}s') }}.map(function(i){
                return { content_id: i.item_id, content_name: i.item_name, quantity: i.quantity, price: i.price };
            })
        });
    }
})();
</script>
<script src="{{ __('https://') }}cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('invoice-pdf-area');
    const invoice_id = "{{ $order->invoice_id }}";
    const opt = {
        margin: [10, 10, 10, 10],
        filename: '{{ __('{{ __('Inv') }}oice') }}-' + invoice_id + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
@endpush