@php
    $shipping = $order->shipping;
    $payments = $order->paymentHistory ?? collect();
    $paid = (float) ($order->paid_amount ?? 0);
    $due = max(0, (float) ($order->due_amount ?? ((float) $order->amount - $paid)));
    $subtotal = $order->orderdetails->sum(fn ($item) => (float) $item->sale_price * (int) $item->qty);
    $discount = $order->orderdetails->sum(fn ($item) => (float) ($item->product_discount ?? 0) * (int) $item->qty);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->invoice_display ?? $order->invoice_id }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            body { margin: 0; background: #fff; }
            .no-print { display: none; }
        }
        body { font-family: 'Courier New', Courier, monospace; color: #111827; margin: 20px; line-height: 1.4; font-size: 14px; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 1.5px solid #9CA3AF; }
        .header-table td { padding: 5px 0; vertical-align: top; }
        .title { font-size: 22px; font-weight: bold; }
        .bill-to { margin-bottom: 20px; border-bottom: 1px solid #E5E7EB; padding-bottom: 10px; }
        .bill-to table { width: 100%; border-collapse: collapse; }
        .bill-to td { padding: 4px 0; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { border-top: 1px solid #9CA3AF; border-bottom: 1px solid #9CA3AF; background: #F3F4F6; color: #111827; text-align: left; padding: 8px 4px; }
        .items-table td { padding: 6px 4px; vertical-align: top; }
        .items-table tr.details-row td { padding-top: 0; padding-bottom: 10px; font-size: 12px; color: #4B5563; }
        .financial-section { width: 100%; display: flex; justify-content: space-between; margin-bottom: 20px; page-break-inside: avoid; }
        .payment-info { width: 55%; font-size: 13px; }
        .payment-history-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        .payment-history-table th { border-bottom: 1px solid #9CA3AF; background: #F8FAFC; text-align: left; padding: 4px; font-weight: bold; }
        .payment-history-table td { padding: 6px 4px; border-bottom: 1px solid #E5E7EB; }
        .totals-table { width: 40%; border-collapse: collapse; margin-left: auto; text-align: right; }
        .totals-table td { padding: 4px; }
        .border-top { border-top: 1px solid #9CA3AF; }
        .border-double { border-top: 1.5px solid #6B7280; }
        .terms { font-size: 12px; margin-bottom: 40px; }
        .footer-signatures { width: 100%; margin-top: 60px; display: flex; justify-content: space-between; page-break-inside: avoid; }
        .sig-line { width: 200px; border-top: 1px solid #6B7280; text-align: center; padding-top: 5px; }
        .btn-print { background: #000; color: #fff; padding: 10px 20px; border: none; cursor: pointer; margin-bottom: 20px; font-weight: bold;}
    </style>
</head>
<body>
    <div class="container">
        <button class="btn-print no-print" onclick="window.print()">Print Invoice</button>
        
        <table class="header-table">
            <tr>
                <td>
                    <span class="title">{{ $generalsetting->name ?? config('app.name') }}</span><br>
                    {{ $contact->address ?? '' }}<br>
                    Phone: {{ $contact->phone ?? '' }}<br>
                    Email: {{ $contact->email ?? '' }}
                </td>
                <td style="text-align: right;">
                    <span class="title">SALES INVOICE</span><br>
                    <strong>Invoice No:</strong> {{ $order->invoice_display ?? $order->invoice_id }}<br>
                    <strong>Date:</strong> {{ optional($order->created_at)->format('d M Y') }}
                </td>
            </tr>
        </table>

        <div class="bill-to">
            <strong>BILL TO:</strong><br>
            <table>
                <tr><td style="width:130px;">Customer Name:</td><td>{{ $shipping->name ?? $order->customer?->name ?? '—' }}</td></tr>
                <tr><td>Phone Number:</td><td>{{ $shipping->phone ?? $order->customer?->phone ?? '—' }}</td></tr>
                <tr><td>Address:</td><td>{{ trim(($shipping->address ?? '').(($shipping->area ?? '') ? ', '.$shipping->area : '')) ?: '—' }}</td></tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">ITEM DESCRIPTION & DETAILS</th>
                    <th style="width: 10%; text-align: center;">QTY</th>
                    <th style="width: 15%; text-align: right;">PRICE</th>
                    <th style="width: 10%; text-align: center;">DISC</th>
                    <th style="width: 15%; text-align: right;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
            @foreach($order->orderdetails as $item)
                @php
                    $lineTotal = (float) $item->sale_price * (int) $item->qty;
                    $itemDiscount = (float) ($item->product_discount ?? 0);
                    $warranty = $item->warrantySale;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}. {{ $item->product_name }}</td>
                    <td style="text-align:center;">{{ $item->qty }}</td>
                    <td style="text-align:right;">৳{{ number_format((float) $item->sale_price, 2) }}</td>
                    <td style="text-align:right;">{{ $itemDiscount > 0 ? '৳'.number_format($itemDiscount, 2) : '—' }}</td>
                    <td style="text-align:right;">৳{{ number_format($lineTotal, 2) }}</td>
                </tr>
                @if($item->size?->sizeName || $item->color?->colorName || $warranty?->serial_numbers || ($warranty && $warranty->warranty_days > 0))
                <tr class="details-row"><td colspan="5">
                    @if($item->size?->sizeName) * Size: {{ $item->size->sizeName }} @endif
                    @if($item->color?->colorName) | * Color: {{ $item->color->colorName }} @endif
                    @if($warranty?->serial_numbers) | * SL: {{ implode(', ', $warranty->serial_numbers) }} @endif
                    @if($warranty && $warranty->warranty_days > 0) | * Warranty: {{ $warranty->warranty_days }}D @if($warranty->warranty_end_date) · Exp: {{ $warranty->warranty_end_date->format('d M Y') }}@endif @endif
                </td></tr>
                @endif
            @endforeach
            </tbody>
        </table>

        <div class="financial-section">
            <div class="payment-info">
                <strong>PAYMENT HISTORY / GATEWAY LOG:</strong>
                <table class="payment-history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method / Gateway</th>
                            <th>Transaction ID (TxID)</th>
                            <th style="text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr><td>{{ optional($payment->created_at)->format('d M Y') }}</td><td>{{ $payment->payment_method ?? 'Payment' }}</td><td>{{ $payment->trx_note ?: '—' }}</td><td style="text-align:right;">৳{{ number_format((float) $payment->amount, 2) }}</td></tr>
                        @empty
                        <tr><td colspan="4">No payments recorded</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <table class="totals-table">
                <tr><td>Gross Subtotal:</td><td>৳{{ number_format($subtotal, 2) }}</td></tr>
                @if($discount > 0)<tr><td>Total Discount:</td><td>-৳{{ number_format($discount, 2) }}</td></tr>@endif
                @if((float) $order->shipping_charge > 0)<tr><td>Shipping:</td><td>+৳{{ number_format($order->shipping_charge, 2) }}</td></tr>@endif
                <tr class="border-top" style="font-weight:bold;"><td>Total Invoice Value:</td><td>৳{{ number_format((float) $order->amount, 2) }}</td></tr>
                <tr style="font-weight:bold;"><td>Total Paid (To Date):</td><td>৳{{ number_format($paid, 2) }}</td></tr>
                <tr class="border-double" style="font-weight:bold;"><td>Net Balance Due:</td><td>৳{{ number_format($due, 2) }}</td></tr>
            </table>
        </div>

        <div class="terms">
            <strong>TERMS & CONDITIONS:</strong><br>
            Goods sold are not returnable / exchangeable. Keep this invoice for warranty claims.
        </div>

        <div class="footer-signatures">
            <div class="sig-line">Customer Signature</div>
            <div class="sig-line">Authorized Store Signature</div>
        </div>
    </div>
</body>
@if(request()->boolean('autoprint'))
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 250); });</script>
@endif
</html>
