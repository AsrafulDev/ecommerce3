<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->invoice_id }}</title>
    <style>
        @page { size: {{ $type === 'a4' ? 'A4' : '80mm auto' }}; margin: {{ $type === 'a4' ? '10mm' : '3mm' }}; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: Arial, sans-serif; font-size: 12px; }
        .receipt { width: {{ $type === 'a4' ? '100%' : '72mm' }}; margin: 0 auto; }
        .center { text-align: center; }
        h1 { font-size: {{ $type === 'a4' ? '24px' : '16px' }}; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 12px 0 6px; border-bottom: 1px solid #222; padding-bottom: 4px; }
        p { margin: 3px 0; }
        .meta, .summary { display: flex; justify-content: space-between; gap: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 5px 2px; border-bottom: 1px solid #ddd; text-align: left; }
        th:last-child, td:last-child { text-align: right; }
        .summary { border-bottom: 1px solid #222; padding: 5px 0; }
        .total { font-size: 15px; font-weight: 700; border-top: 1px solid #222; padding-top: 7px; }
        .due { color: #b42318; font-weight: 700; }
        .actions { text-align: center; margin: 18px 0; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
<div class="receipt">
    <div class="center">
        @if($generalsetting && $generalsetting->white_logo)
            <img src="{{ asset($generalsetting->white_logo) }}" alt="Logo" style="max-width:180px; max-height:55px; margin-bottom:8px;">
        @endif
        <h1>{{ $generalsetting->name ?? config('app.name') }}</h1>
        <p>{{ $contact->phone ?? '' }}</p>
        <p>{{ $contact->email ?? '' }}</p>
    </div>

    <h2>Invoice</h2>
    <div class="meta"><span>Invoice: <strong>#{{ $order->invoice_id }}</strong></span><span>{{ optional($order->created_at)->format('d-m-Y h:i A') }}</span></div>

    <h2>Customer</h2>
    <p>{{ $order->shipping->name ?? 'Walk-in Guest' }}</p>
    <p>{{ $order->shipping->phone ?? '' }}</p>
    <p>{{ $order->shipping->address ?? '' }}</p>

    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead>
        <tbody>
        @foreach($order->orderdetails as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->qty }}</td>
                <td>৳{{ number_format((float) $item->sale_price * (int) $item->qty, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @php
        $paid = (float) ($order->payment->amount ?? 0);
        $total = (float) $order->amount;
        $due = max($total - $paid, 0);
    @endphp
    <div class="summary"><span>Payment method</span><span>{{ $order->payment->payment_method ?? 'N/A' }}</span></div>
    <div class="summary"><span>Total</span><span>৳{{ number_format($total, 2) }}</span></div>
    <div class="summary"><span>Paid</span><span>৳{{ number_format($paid, 2) }}</span></div>
    <div class="summary due"><span>Due</span><span>৳{{ number_format($due, 2) }}</span></div>
    <p class="center" style="margin-top:18px;">Thank you for your purchase.</p>

    <div class="actions">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</div>
</body>
</html>
