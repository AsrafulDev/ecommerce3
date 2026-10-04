@php
    $shipping = $order->shipping;
    $payments = $order->paymentHistory ?? collect();
    $paid = (float) ($order->paid_amount ?? 0);
    $due = max(0, (float) ($order->due_amount ?? ((float) $order->amount - $paid)));
    $total = (float) $order->amount;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->invoice_display ?? $order->invoice_id }}</title>
    <style>
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
        }
        html, body { width: 80mm; margin: 0; padding: 0; }
        body { font-family: 'Courier New', Courier, monospace; width: 72mm; margin: 0; padding: 5px; font-size: 12px; color: #111827; font-variant-ligatures: none; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px solid #9CA3AF; margin: 5px 0; }
        .double-line { border-top: 1.5px solid #6B7280; margin: 5px 0; }
        .flex-row { display: flex; justify-content: space-between; }
        .item-details { padding-left: 10px; font-size: 11px; color: #4B5563; }
        .payment-meta { font-size: 11px; padding-left: 5px; font-style: italic; }
        .btn-print { background: #000; color: #fff; width: 100%; padding: 5px; border: none; cursor: pointer; margin-bottom: 10px; font-weight: bold;}
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">Print Receipt</button>

    <div class="center bold" style="font-size: 15px;">{{ $generalsetting->name ?? config('app.name') }}</div>
    <div class="center">{{ $contact->address ?? '' }}{{ $contact?->phone ? ' | '.$contact->phone : '' }}</div>
    <div class="double-line"></div>
    
    <div class="flex-row"><span>Inv: {{ $order->invoice_display ?? '#'.$order->invoice_id }}</span><span>{{ optional($order->created_at)->format('d-m-Y') }}</span></div>
    <div class="line"></div>
    
    <div class="flex-row bold">
        <span style="width: 60%;">ITEM / DETAILS</span>
        <span style="width: 15%; text-align: center;">QTY</span>
        <span style="width: 25%; text-align: right;">PRICE</span>
    </div>
    <div class="line"></div>

    @foreach($order->orderdetails as $item)
    @php
        $size = $item->size?->sizeName;
        if (!$size && $item->product_size && !is_numeric($item->product_size)) $size = $item->product_size;
        $color = $item->color?->colorName;
        if (!$color && $item->product_color && !is_numeric($item->product_color)) $color = $item->product_color;
        $warranty = $item->warrantySale;
        $serials = $warranty?->serial_numbers ?? [];
        $lineTotal = (float) $item->sale_price * (int) $item->qty;
    @endphp
    <div class="flex-row">
        <span style="width: 60%;">{{ \Illuminate\Support\Str::limit($item->product_name, 32) }}</span>
        <span style="width: 15%; text-align: center;">{{ $item->qty }}</span>
        <span style="width: 25%; text-align: right;">{{ number_format($lineTotal, 2) }}</span>
    </div>
    <div class="item-details">
        @if($size) * Size: {{ $size }}<br>@endif
        @if($color) * Color: {{ $color }}<br>@endif
        @if(!empty($serials)) * S/N: {{ implode(', ', $serials) }}<br>@endif
        @if($warranty && $warranty->warranty_end_date)
            * Wnty: {{ $warranty->warranty_days ? $warranty->warranty_days.'D' : 'Yes' }} / Exp: {{ $warranty->warranty_end_date->format('d-m-Y') }}<br>
        @endif
        @if((float) ($item->product_discount ?? 0) > 0) * Disc: {{ number_format((float) $item->product_discount, 2) }} / unit @endif
    </div>
    <br>
    @endforeach

    <div class="line"></div>
    <div class="flex-row bold"><span>TOTAL BDT:</span><span>{{ number_format($total, 2) }}</span></div>
    
    <div class="line"></div>
    <div class="flex-row bold"><span>PAID:</span><span>{{ number_format($paid, 2) }}</span></div>
    <div class="payment-meta">
        @forelse($payments as $payment)
            {{ optional($payment->created_at)->format('d-m-Y') }} {{ strtoupper($payment->payment_method ?? 'PAYMENT') }}: {{ number_format((float) $payment->amount, 2) }}@if($payment->trx_note) ({{ $payment->trx_note }})@endif<br>
        @empty
            No payment recorded<br>
        @endforelse
    </div>
    
    <div class="line"></div>
    <div class="flex-row bold"><span>DUE BALANCE:</span><span>{{ number_format($due, 2) }}</span></div>
    <div class="double-line"></div>

    <div style="font-size: 10px;">
        Terms: Warranty claims require this slip and valid serial number.
    </div>
    <div class="double-line"></div>
    <div class="center bold">THANK YOU!</div>
</body>
@if(request()->boolean('autoprint'))
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 250); });</script>
@endif
</html>
