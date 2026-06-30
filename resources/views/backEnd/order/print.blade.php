<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('{{ __('{{ __('Inv') }}oice') }} Print') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: 80mm auto;
            margin: 3mm 4mm;
        }

        body {
            background: #ccc;
            font-family: '{{ __('Courier') }} {{ __('New') }}', {{ __('Courier') }}, monospace;
            font-size: 12px;
            color: #000;
        }

        .no-print {
            text-align: center;
            padding: 10px;
            background: #222;
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .no-print button {
            padding: 7px 28px;
            background: #28a745;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: {{ __('14px') }};
            border-radius: 4px;
        }

        .receipt {
            background: #fff;
            width: 302px;
            margin: 18px auto;
            padding: 8px 10px 12px;
            border: 1px solid #999;
        }

        /* Header */
        .rh { text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 5px; }
        .rh .shop { font-size: {{ __('14px') }}; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .rh p { font-size: 10px; margin-top: 2px; }

        /* {{ __('{{ __('Title') }} *') }}/
        .rt { text-align: center; font-size: 12px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; margin: 4px 0; letter-spacing: 3px; }

        /* Meta */
        .rm { font-size: 11px; margin-bottom: 3px; }
        .rm .fl { display: flex; justify-content: space-between; margin-bottom: 2px; }

        /* Table */
        .rtbl { width: 100%; border-collapse: collapse; font-size: 10px; margin: 4px 0; }
        .rtbl thead th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 2px; font-weight: 700; text-align: left; }
        .rtbl thead th.r { text-align: right; }
        .rtbl tbody td { padding: 3px 2px; vertical-align: top; }
        .rtbl tbody tr:last-child td { border-bottom: 1px solid #000; }
        .rtbl .pname { font-weight: 700; }
        .rtbl .pmeta { font-size: 9px; color: #555; }

        /* {{ __('Summary') }} */
        .rs { display: flex; justify-content: space-between; font-size: 11px; padding: 2px 0; }
        .r{{ __('total') }} { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0; margin: 3px 0; }

        /* Payment */
        .rp { font-size: 11px; margin-top: 3px; }
        .rp .fl { display: flex; justify-content: space-between; padding: 2px 0; }
        .rp .p{{ __('total') }} { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; padding-top: 4px; margin-top: 3px; }

        /* Dividers */
        .dash { border: none; border-top: 1px dashed #666; margin: 5px 0; }

        /* Footer */
        .rf { text-align: center; border-top: 1px dashed #666; margin-top: 10px; padding-top: 7px; }
        .rf .ty { font-size: {{ __('14px') }}; font-weight: 700; }
        .rf small { font-size: 9px; color: #666; font-style: italic; margin-top: 3px; display: block; }

        /* Print */
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .receipt {
                width: 100% !important;
                margin: 0 !important;
                border: none !important;
                padding: 2mm 1mm !important;
                page-break-after: always;
                break-after: page;
            }
            .receipt:last-child {
                page-break-after: avoid;
                break-after: avoid;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">{{ __('&#128438; Print All') }}</button>
</div>

@foreach($orders as $order)
@php
    $isReseller  = !empty($order->customer_payable_amount);
    $sub{{ __('total') }}    = 0;
    foreach ($order->orderdetails as $od) { $sub{{ __('total') }} += ($od->sale_price * $od->qty); }
    if ($isReseller && $order->customer_payable_amount) {
        $sub{{ __('total') }} = $order->customer_payable_amount - $order->shipping_charge;
    }
    $final{{ __('Total') }}  = $isReseller ? $order->customer_payable_amount : $order->amount;
    $totalQty    = $order->orderdetails->sum('qty');
    $pay{{ __('Method') }}   = strtoupper($order->payment_gateway ?? ($order->payment ? $order->payment->payment_method : '{{ __('N/A') }}'));
    $pay{{ __('Status') }}   = $order->payment_status ?? ($order->payment ? $order->payment->payment_status : 'pending');
    $trackingId  = $order->courier_tracking_id ?? $order->consignment_id ?? null;
    $courierType = $order->courier_type ?? ($trackingId ? 'steadfast' : null);
@endphp

<div class="receipt">

    {{-- Header --}}
    <div class="rh">
        <div class="shop">{{ $generalsetting->name }}</div>
        @if($contact->{{ __('address)') }}<p>{{ $contact->address }}</p>@endif
        @if($contact->{{ __('{{ __('phone') }})') }}<p>{{ __('Phone') }}: {{ $contact->{{ __('phone') }} }}</p>@endif
        @if($contact->{{ __('email)') }}<p>{{ $contact->email }}</p>@endif
    </div>

    {{-- {{ __('Title') }} --}}
    <div class="rt">{{ __('POS {{ __('{{ __('Inv') }}oice') }}') }}</div>

    {{-- Meta --}}
    <div class="rm">
        <div class="fl">
            <span>{{ __('Bill No.') }} : <strong>{{ $order->invoice_id }}</strong></span>
            <span>{{ $order->created_at->format('H:i') }} hrs</span>
        </div>
        <div class="fl"><span>{{ __('{{ __('Date') }} &nbsp;&nbsp') }};: <strong>{{ $order->created_at->format('d-m-Y') }}</strong></span></div>
        @if($order->shipping && $order->shipping->name)
        <div class="fl"><span>{{ __('Buyer &nbsp;&nbsp') }};: <strong>{{ $order->shipping->name }}</strong></span></div>
        @endif
        @if($order->shipping && $order->shipping->{{ __('{{ __('phone') }})') }}
        <div class="fl"><span>{{ __('Phone') }} &nbsp;&nbsp;: {{ $order->shipping->{{ __('phone') }} }}</span></div>
        @endif
        @if($order->shipping && ($order->shipping->address || $order->shipping->area))
        <div class="fl"><span>Address : {{ $order->shipping->address }}{{ $order->shipping->area ? ', '.$order->shipping->area : '' }}</span></div>
        @endif
    </div>

    {{-- {{ __('Product') }}s --}}
    <table class="rtbl">
        <thead>
            <tr>
                <th style="width:{{ __('14px') }};">#</th>
                <th>{{ __('Product') }}</th>
                <th style="width:22px;text-align:center;">{{ __('Qty') }}</th>
                <th style="width:44px;" class="r">{{ __('Rate') }}</th>
                <th style="width:48px;" class="r">{{ __('{{ __('Total') }}') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderdetails as $item)
            @php
                if ($isReseller && $order->customer_payable_amount && $sub{{ __('total') }} > 0) {
                    $tv   = $item->sale_price * $item->qty;
                    $dp   = (($tv / ($sub{{ __('total') }} + $order->discount)) * $sub{{ __('total') }}) / $item->qty;
                } else {
                    $dp = $item->sale_price;
                }
                $sz = $item->size ? ($item->size->size{{ __('Name') }} ?? null) : null;
                if (!$sz && $item->product_size) {
                    $szm = \App\Models\Size::find($item->product_size);
                    $sz  = $szm ? ($szm->size{{ __('Name') }} ?? null) : (is_numeric($item->product_size) ? null : $item->product_size);
                }
                $cl = $item->color ? ($item->color->color{{ __('Name') }} ?? null) : null;
                if (!$cl && $item->product_color && !is_numeric($item->product_color)) { $cl = $item->product_color; }
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    <span class="pname">{{ $item->product_name }}</span>
                    @if($sz || $cl)
                    <div class="pmeta">@if($sz)Sz:{{ $sz }}@endif @if($sz && $cl)| @endif @if($cl){{ $cl }}@endif</div>
                    @endif
                </td>
                <td style="text-align:center;">{{ $item->qty }}</td>
                <td style="text-align:right;">{{ number_format($dp, 2) }}</td>
                <td style="text-align:right;">{{ number_format($dp * $item->qty, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- {{ __('Summary') }} --}}
    <div class="rs"><span>{{ __('Sub{{ __('total') }}') }}</span><span>{{ number_format($sub{{ __('total') }}, 2) }}</span></div>
    @if($order->discount > 0)
    <div class="rs"><span>{{ __('Discount') }} (–)</span><span>{{ number_format($order->discount, 2) }}</span></div>
    @endif
    @if($order->shipping_charge > 0)
    <div class="rs"><span>{{ __('Delivery (+)') }}</span><span>{{ number_format($order->shipping_charge, 2) }}</span></div>
    @endif

    <div class="r{{ __('total') }}">
        <span>{{ __('Total') }} &nbsp; {{ $totalQty }} {{ $totalQty > 1 ? 'Nos' : 'No' }}</span>
        <span>&#2547; {{ number_format($final{{ __('Total') }}, 2) }}</span>
    </div>

    {{-- Payment --}}
    <div class="rp">
        <div class="fl"><span>{{ __('{{ __('Method') }} &nbsp;&nbsp') }};:</span><span><strong>{{ $pay{{ __('Method') }} }}</strong></span></div>
        <div class="fl">
            <span>{{ __('Pay {{ __('Status') }}') }} :</span>
            <span><strong>
                @if($pay{{ __('Status') }}=='paid') {{ __('PAID') }}
                @elseif($pay{{ __('Status') }}=='pending') PENDING
                @elseif($pay{{ __('Status') }}=='failed') {{ __('FAILED') }}
                @else {{ strtoupper($pay{{ __('Status') }}) }}
                @endif
            </strong></span>
        </div>
        @if($courierType)
        <hr class="dash">
        <div class="fl"><span>{{ __('{{ __('Courier') }} &nbsp;&nbsp') }};:</span><span><strong>{{ ucfirst($courierType) }}</strong></span></div>
        @if($trackingId)<div class="fl"><span>{{ __('{{ __('{{ __('Track') }}ing') }} &nbsp') }};:</span><span>{{ $trackingId }}</span></div>@endif
        @if($order->courier_sent_at)<div class="fl"><span>{{ __('Sent &nbsp;&nbsp;&nbsp;&nbsp;&nbsp') }};:</span><span>{{ \Carbon\Carbon::parse($order->courier_sent_at)->format('d M Y') }}</span></div>@endif
        @endif
        <div class="p{{ __('total') }}">
            <span>{{ __('{{ __('Total') }} {{ __('Paid') }}') }}</span>
            <span>&#2547; {{ number_format($final{{ __('Total') }}, 2) }}</span>
        </div>
    </div>

    {{-- Order status --}}
    <hr class="dash">
    <div class="rs"><span>{{ __('Order {{ __('Status') }}') }} :</span><span><strong>{{ $order->status ? $order->status->name : 'Processing' }}</strong></span></div>

    {{-- Footer --}}
    <div class="rf">
        <div class="ty">{{ __('Thank You!') }}</div>
        <div>{{ __('Visit Again!') }}</div>
        <small>{{ __('* Computer generated invoice. No signature required.') }}</small>
    </div>

</div>
@endforeach

</body>
</html>
