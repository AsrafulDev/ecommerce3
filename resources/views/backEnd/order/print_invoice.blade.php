<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->invoice_id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        .no-print { text-align: center; padding: 10px; background: #222; position: sticky; top: 0; z-index: 99; }
        .no-print button { padding: 7px 24px; background: #28a745; color: #fff; border: none; cursor: pointer; font-size: 14px; border-radius: 4px; margin: 0 4px; }

        /* ═══ POS RECEIPT (80mm) ═══ */
        .pos-receipt { display: none; background: #fff; width: 302px; margin: 18px auto; padding: 8px 10px 12px; border: 1px solid #999; font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; }
        @page { size: 80mm auto; margin: 3mm 4mm; }
        .pos-receipt .rh { text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 5px; }
        .pos-receipt .rh .shop { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .pos-receipt .rh p { font-size: 10px; margin-top: 2px; }
        .pos-receipt .rt { text-align: center; font-size: 12px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; margin: 4px 0; letter-spacing: 3px; }
        .pos-receipt .rm { font-size: 11px; margin-bottom: 3px; }
        .pos-receipt .rm .fl { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .pos-receipt table { width: 100%; border-collapse: collapse; font-size: 10px; margin: 4px 0; }
        .pos-receipt table thead th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px; font-weight: 700; text-align: left; }
        .pos-receipt table thead th.r { text-align: right; }
        .pos-receipt table tbody td { padding: 3px 2px; vertical-align: top; }
        .pos-receipt table tbody tr:last-child td { border-bottom: 1px solid #000; }
        .pos-receipt table .pname { font-weight: 700; }
        .pos-receipt .rs { display: flex; justify-content: space-between; font-size: 11px; padding: 2px 0; }
        .pos-receipt .rtotal { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0; margin: 3px 0; }
        .pos-receipt .rp { font-size: 11px; margin-top: 3px; }
        .pos-receipt .rp .fl { display: flex; justify-content: space-between; padding: 2px 0; }
        .pos-receipt .ptotal { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; padding-top: 4px; margin-top: 3px; }
        .pos-receipt .dash { border: none; border-top: 1px dashed #666; margin: 5px 0; }
        .pos-receipt .rf { text-align: center; border-top: 1px dashed #666; margin-top: 10px; padding-top: 7px; }
        .pos-receipt .rf .ty { font-size: 14px; font-weight: 700; }
        .pos-receipt .rf small { font-size: 9px; color: #666; font-style: italic; margin-top: 3px; display: block; }

        /* ═══ A4 INVOICE ═══ */
        .customer-invoice { display: none; margin: 25px auto; width: 760px; background: #fff; padding: 30px; }
        .customer-invoice p { margin: 0; }
        .customer-invoice td { font-size: 16px; }
        .a4-bar { background: #4DBC60; transform: skew(38deg); width: 100%; margin-left: 65px; padding: 20px 60px; }
        .a4-bar p { font-size: 30px; color: #fff; transform: skew(-38deg); text-transform: uppercase; text-align: right; font-weight: bold; }
        .a4-bar2 { background: #fff; transform: skew(36deg); width: 72%; margin-left: 182px; padding: 12px 32px; margin-top: 6px; }
        .a4-bar2 p { font-size: 15px; color: #222; font-weight: bold; transform: skew(-36deg); text-align: right; padding-right: 18px; }

        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            body.print-pos .customer-invoice { display: none !important; }
            body.print-pos .pos-receipt { display: block !important; }
            body.print-a4 .pos-receipt { display: none !important; }
            body.print-a4 .customer-invoice { display: block !important; }
            .pos-receipt { width: 100% !important; margin: 0 !important; border: none !important; padding: 2mm 1mm !important; }
            .customer-invoice { width: 100% !important; margin: 0 auto !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="print-{{ $type === 'a4' ? 'a4' : 'pos' }}">

<div class="no-print">
    <button onclick="printPOS()">🖨 Print POS (80mm)</button>
    <button onclick="printA4()">🖨 Print A4</button>
    <button onclick="window.close()">✖ Close</button>
</div>

{{-- ══════════ POS RECEIPT ══════════ --}}
@php
    $payStatus = $order->payment_status;
    $payMethod = $order->payment->payment_method ?? 'N/A';
    $paid      = (float) $order->paid_amount;
    $due       = (float) $order->due_amount;
    $subtotal  = $order->orderdetails->sum(fn($od) => $od->sale_price * $od->qty);
@endphp
<div class="pos-receipt">
    <div class="rh">
        <div class="shop">{{ $generalsetting->name ?? config('app.name') }}</div>
        @if($contact && $contact->address) <p>{{ $contact->address }}</p> @endif
        @if($contact && $contact->phone) <p>Phone: {{ $contact->phone }}</p> @endif
    </div>
    <div class="rt">POS INVOICE</div>
    <div class="rm">
        <div class="fl"><span>Bill No. : <strong>{{ $order->invoice_id }}</strong></span><span>{{ $order->created_at->format('H:i') }} hrs</span></div>
        <div class="fl"><span>Date : <strong>{{ $order->created_at->format('d-m-Y') }}</strong></span></div>
        @if($order->shipping && $order->shipping->name)
        <div class="fl"><span>Buyer : <strong>{{ $order->shipping->name }}</strong></span></div>
        @endif
        @if($order->shipping && $order->shipping->phone)
        <div class="fl"><span>Phone : {{ $order->shipping->phone }}</span></div>
        @endif
        @if($order->shipping && $order->shipping->address)
        <div class="fl"><span>Address : {{ $order->shipping->address }}</span></div>
        @endif
        @if($order->shipping && $order->shipping->area)
        <div class="fl"><span>Area : {{ $order->shipping->area }}</span></div>
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="r">Qty</th>
                <th class="r">Price</th>
                <th class="r">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderdetails as $od)
            <tr>
                <td class="pname">{{ $od->product_name }}
                    @if($od->product_size) <br><small>({{ $od->product_size }})</small> @endif
                    @if($od->product_color) <small>/{{ $od->product_color }}</small> @endif
                </td>
                <td class="r">{{ $od->qty }}</td>
                <td class="r">{{ number_format($od->sale_price, 0) }}</td>
                <td class="r">{{ number_format($od->sale_price * $od->qty, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="rs"><span>Sub Total</span><span>৳{{ number_format($subtotal, 0) }}</span></div>
    @if($order->discount > 0)
    <div class="rs"><span>Discount</span><span>- ৳{{ number_format($order->discount, 0) }}</span></div>
    @endif
    <div class="rs"><span>Shipping</span><span>৳{{ number_format($order->shipping_charge, 0) }}</span></div>
    <div class="rtotal"><span>Grand Total</span><span>৳{{ number_format($order->amount, 0) }}</span></div>
    <div class="rp">
        <div class="fl"><span>Payment Method</span><span>{{ strtoupper($payMethod) }}</span></div>
        <div class="fl"><span>Status</span><span>{{ strtoupper($payStatus) }}</span></div>
        @if($paid > 0)
        <div class="fl"><span>Paid</span><span>৳{{ number_format($paid, 0) }}</span></div>
        @endif
        @if($due > 0)
        <div class="ptotal"><span>Due</span><span>৳{{ number_format($due, 0) }}</span></div>
        @endif
    </div>
    <hr class="dash">
    <div class="rf">
        <div class="ty">Thank You!</div>
        <small>Goods sold are not returnable / exchangeable</small>
    </div>
</div>

{{-- ══════════ A4 INVOICE ══════════ --}}
<div class="customer-invoice">
    <table style="width:100%">
        <tr>
            <td style="width:40%; float:left; padding-top:15px;">
                @if($generalsetting && $generalsetting->white_logo)
                <img src="{{ asset($generalsetting->white_logo) }}" width="190px" alt="">
                @endif
                <p style="font-size:14px; color:#222; margin:20px 0;">
                    <strong>Payment Method:</strong>
                    <span style="text-transform:uppercase;">{{ $payMethod }}</span>
                </p>
                <p style="font-size:14px; color:#222;"><strong>Payment Status:</strong> {{ strtoupper($payStatus) }}</p>
                @if($paid > 0)<p style="font-size:14px; color:#222;"><strong>Paid:</strong> ৳{{ number_format($paid, 2) }}</p>@endif
                @if($due > 0)<p style="font-size:14px; color:#d00;"><strong>Due:</strong> ৳{{ number_format($due, 2) }}</p>@endif
                <div class="invoice_form" style="margin-top:30px;">
                    <p style="font-size:16px; line-height:1.8; color:#222;"><strong>Invoice From:</strong></p>
                    <p style="font-size:16px; line-height:1.8; color:#222;">{{ $generalsetting->name ?? '' }}</p>
                    <p style="font-size:16px; line-height:1.8; color:#222;">{{ $contact->phone ?? '' }}</p>
                    <p style="font-size:16px; line-height:1.8; color:#222;">{{ $contact->email ?? '' }}</p>
                </div>
            </td>
            <td style="width:60%; float:left;">
                <div class="a4-bar"><p>Invoice</p></div>
                <div class="a4-bar2">
                    <p>Invoice ID : <strong>#{{ $order->invoice_id }}</strong></p>
                    <p>Invoice Date: <strong>{{ $order->created_at->format('d-m-y') }}</strong></p>
                </div>

                {{-- 📦 Invoice number barcode (A4) --}}
                @php
                    $bcGen = new \Picqer\Barcode\BarcodeGeneratorHTML();
                    $barcodeHtml = $bcGen->getBarcode((string) $order->invoice_id, $bcGen::TYPE_CODE_128, 2, 42);
                @endphp
                <div class="invoice-barcode" style="text-align:right; margin-top:10px; padding-right:18px;">
                    <div style="display:inline-block; text-align:center; background:#fff; padding:4px 8px; border:1px solid #dcdcdc; border-radius:4px;">
                        <div style="line-height:0;">{!! $barcodeHtml !!}</div>
                        <div style="font-size:14px; color:#222; font-weight:bold; letter-spacing:3px; margin-top:3px;">#{{ $order->invoice_id }}</div>
                    </div>
                </div>

                <div style="padding-top:20px; text-align:right;">
                    <p style="font-size:16px; line-height:1.8; color:#222;"><strong>Invoice To:</strong></p>
                    <p style="font-size:16px; line-height:1.8; color:#222;">{{ $order->shipping->name ?? '' }}</p>
                    <p style="font-size:16px; line-height:1.8; color:#222;">{{ $order->shipping->phone ?? '' }}</p>
                    <p style="font-size:16px; line-height:1.8; color:#222;">{{ $order->shipping->address ?? '' }}</p>
                    <p style="font-size:16px; line-height:1.8; color:#222;">{{ $order->shipping->area ?? '' }}</p>
                </div>
            </td>
        </tr>
    </table>
    <table class="table" style="width:100%; margin-top:30px; border-collapse:collapse;">
        <thead style="background:#4DBC60; color:#fff;">
            <tr>
                <th style="padding:8px; text-align:left;">SL</th>
                <th style="padding:8px; text-align:left;">Product</th>
                <th style="padding:8px; text-align:right;">Unit Price</th>
                <th style="padding:8px; text-align:right;">Qty</th>
                <th style="padding:8px; text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderdetails as $i => $od)
            <tr>
                <td style="padding:8px; border-bottom:1px solid #eee;">{{ $i + 1 }}</td>
                <td style="padding:8px; border-bottom:1px solid #eee;">{{ $od->product_name }}
                    @if($od->product_size) <small>({{ $od->product_size }})</small> @endif
                </td>
                <td style="padding:8px; border-bottom:1px solid #eee; text-align:right;">৳{{ number_format($od->sale_price, 2) }}</td>
                <td style="padding:8px; border-bottom:1px solid #eee; text-align:right;">{{ $od->qty }}</td>
                <td style="padding:8px; border-bottom:1px solid #eee; text-align:right;">৳{{ number_format($od->sale_price * $od->qty, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:20px; margin-left:auto; width:50%; text-align:right;">
        <p style="display:flex; justify-content:space-between; padding:4px 0;"><span>Sub Total</span><span>৳{{ number_format($subtotal, 2) }}</span></p>
        @if($order->discount > 0)
        <p style="display:flex; justify-content:space-between; padding:4px 0;"><span>Discount</span><span>- ৳{{ number_format($order->discount, 2) }}</span></p>
        @endif
        <p style="display:flex; justify-content:space-between; padding:4px 0;"><span>Shipping</span><span>৳{{ number_format($order->shipping_charge, 2) }}</span></p>
        <p style="display:flex; justify-content:space-between; padding:8px 0; border-top:2px solid #4DBC60; font-size:18px; font-weight:bold;"><span>Grand Total</span><span>৳{{ number_format($order->amount, 2) }}</span></p>
        @if($due > 0)
        <p style="display:flex; justify-content:space-between; padding:4px 0; color:#d00; font-weight:bold;"><span>Due</span><span>৳{{ number_format($due, 2) }}</span></p>
        @endif
    </div>
</div>

<script>
    function printPOS() {
        document.body.classList.remove('print-a4');
        document.body.classList.add('print-pos');
        window.print();
    }
    function printA4() {
        document.body.classList.remove('print-pos');
        document.body.classList.add('print-a4');
        window.print();
    }
    window.onload = function () {
        // Auto-print once when opened with ?autoprint=1
        if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
            setTimeout(function () { window.print(); }, 300);
        }
    };
</script>
</body>
</html>
