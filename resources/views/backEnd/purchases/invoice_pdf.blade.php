<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $purchase->invoice_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #2563eb; padding-bottom: 15px; }
        .header h2 { color: #2563eb; font-size: 22px; }
        .header .invoice-meta { text-align: right; font-size: 13px; }
        .section { margin-bottom: 20px; }
        .section h5 { font-size: 14px; color: #555; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .section p { margin-bottom: 2px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table thead th { background: #f3f4f6; padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #d1d5db; }
        table tbody td { padding: 10px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        table tbody td.text-end, table thead th.text-end { text-align: right; }
        .summary { width: 350px; margin-left: auto; }
        .summary table td, .summary table th { padding: 6px 10px; font-size: 13px; }
        .summary table th { text-align: left; color: #555; }
        .summary table td { text-align: right; }
        .summary .grand-row th, .summary .grand-row td { font-size: 15px; font-weight: bold; border-top: 2px solid #2563eb; }
        .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h2>Purchase Invoice</h2>
            @if($generalsetting->white_logo)
                <img src="{{asset($generalsetting->white_logo)}}" alt="{{ $generalsetting->site_name ?? 'CurlBazar' }}" style="max-height:45px;">
            @else
                <h3>{{ $generalsetting->site_name ?? 'CurlBazar' }}</h3>
            @endif
        </div>
        <div class="invoice-meta">
            <p><strong>Date:</strong> {{ $purchase->purchase_date }}</p>
            <p><strong>Invoice #:</strong> {{ $purchase->invoice_no }}</p>
        </div>
    </div>

    <div class="section">
        <h5>Supplier</h5>
        <p><strong>{{ optional($purchase->supplier)->name }}</strong></p>
        <p>{{ optional($purchase->supplier)->phone }}</p>
        <p>{{ optional($purchase->supplier)->address }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Cost</th>
                <th class="text-end">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ optional($item->product)->name }}</td>
                <td class="text-end">{{ $item->qty }}</td>
                <td class="text-end">{{ number_format($item->unit_cost, 2) }}</td>
                <td class="text-end">{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr><th>Subtotal</th><td>{{ number_format($purchase->subtotal, 2) }} ৳</td></tr>
            <tr><th>Discount</th><td>{{ number_format($purchase->discount, 2) }} ৳</td></tr>
            <tr><th>Shipping</th><td>{{ number_format($purchase->shipping_cost, 2) }} ৳</td></tr>
            <tr class="grand-row"><th>Grand Total</th><td>{{ number_format($purchase->grand_total, 2) }} ৳</td></tr>
            <tr><th>Paid</th><td class="text-success">{{ number_format($purchase->paid_amount, 2) }} ৳</td></tr>
            <tr><th>Due</th><td class="text-danger">{{ number_format($purchase->due_amount, 2) }} ৳</td></tr>
        </table>
    </div>

    @if($purchase->note)
    <div class="section" style="margin-top:20px;">
        <h5>Note</h5>
        <p>{{ $purchase->note }}</p>
    </div>
    @endif

    <div class="footer">
        Generated on {{ now()->format('d M Y, h:i A') }}
    </div>

</body>
</html>
