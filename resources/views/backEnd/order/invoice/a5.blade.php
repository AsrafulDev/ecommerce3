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
        @page { 
            size: A5 portrait; 
            margin: 6mm; 
        }
        @media print {
            html, body { 
                width: 148mm;  /* Fixed: A5 Portrait Width */
                height: 210mm; /* Fixed: A5 Portrait Height */
            }
            .no-print { display: none; }
        }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            color: #111827; 
            margin: 0; 
            padding: 5px; /* Reduced to maximize vertical canvas space */
            line-height: 1.3; 
            font-size: 11px; 
            background: #fff;
        }
        .container { width: 100%; max-width: 100%; margin: 0 auto; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border-bottom: 1.5px solid #9CA3AF; }
        .header-table td { padding: 3px 0; vertical-align: top; }
        .title { font-size: 15px; font-weight: bold; }
        .bill-to { margin-bottom: 12px; border-bottom: 1px solid #E5E7EB; padding-bottom: 10px; }
        .bill-to table { width: 100%; border-collapse: collapse; }
        .bill-to td { padding: 2px 0; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .items-table th { border-top: 1px solid #9CA3AF; border-bottom: 1px solid #9CA3AF; background: #F3F4F6; color: #111827; text-align: left; padding: 5px 3px; font-size: 11px; }
        .items-table td { padding: 4px 3px; vertical-align: top; }
        .items-table tr.details-row td { padding-top: 0; padding-bottom: 6px; font-size: 10px; color: #4B5563; }
        
        /* Stack sections vertically if table is too wide, or keep clean spacing */
        .financial-section { width: 100%; display: flex; justify-content: space-between; margin-bottom: 12px; page-break-inside: avoid; }
        .payment-info { width: 50%; font-size: 10px; }
        .payment-history-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10px; }
        .payment-history-table th { border-bottom: 1px solid #9CA3AF; background: #F8FAFC; text-align: left; padding: 2px; font-weight: bold; }
        .payment-history-table td { padding: 4px 2px; border-bottom: 1px solid #E5E7EB; }
        
        .totals-table { width: 46%; border-collapse: collapse; margin-left: auto; text-align: right; font-size: 11px; }
        .totals-table td { padding: 2px; }
        .border-top { border-top: 1px solid #9CA3AF; }
        .border-double { border-top: 1.5px solid #6B7280; }
        
        .terms { font-size: 10px; margin-bottom: 20px; line-height: 1.2; border-top: 1px solid #9CA3AF; padding-top: 5px; }
        .footer-signatures { width: 100%; margin-top: 40px; display: flex; justify-content: space-between; page-break-inside: avoid; }
        .sig-line { width: 140px; border-top: 1px solid #6B7280; text-align: center; padding-top: 3px; font-size: 10px; }
        .btn-print { background: #000; color: #fff; padding: 6px 14px; border: none; cursor: pointer; margin-bottom: 12px; font-weight: bold; font-size: 11px;}
    </style>
</head>
<body>
    <div class="container">
        <button class="btn-print no-print" onclick="window.print()">Print Invoice (A5)</button>
        
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
                <tr><td style="width:100px;">Customer Name:</td><td>{{ $shipping->name ?? $order->customer?->name ?? '—' }}</td></tr>
                <tr><td>Phone Number:</td><td>{{ $shipping->phone ?? $order->customer?->phone ?? '—' }}</td></tr>
                <tr><td>Address:</td><td>{{ trim(($shipping->address ?? '').(($shipping->area ?? '') ? ', '.$shipping->area : '')) ?: '—' }}</td></tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 48%;">ITEM DESCRIPTION & DETAILS</th>
                    <th style="width: 10%; text-align: center;">QTY</th>
                    <th style="width: 16%; text-align: right;">PRICE</th>
                    <th style="width: 10%; text-align: center;">DISC</th>
                    <th style="width: 16%; text-align: right;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
            @foreach($order->orderdetails as $item)
            @php $lineTotal=(float)$item->sale_price*(int)$item->qty; $itemDiscount=(float)($item->product_discount??0); $warranty=$item->warrantySale; @endphp
                <tr><td>{{ $loop->iteration }}. {{ $item->product_name }}</td><td style="text-align:center;">{{ $item->qty }}</td><td style="text-align:right;">৳{{ number_format((float)$item->sale_price,2) }}</td><td style="text-align:right;">{{ $itemDiscount>0?'৳'.number_format($itemDiscount,2):'—' }}</td><td style="text-align:right;">৳{{ number_format($lineTotal,2) }}</td></tr>
                @if($item->size?->sizeName || $item->color?->colorName || $warranty?->serial_numbers || ($warranty && $warranty->warranty_days > 0))
                <tr class="details-row"><td colspan="5">@if($item->size?->sizeName)* Size: {{ $item->size->sizeName }} @endif @if($item->color?->colorName)| * Color: {{ $item->color->colorName }} @endif @if($warranty?->serial_numbers)| * SL: {{ implode(', ', $warranty->serial_numbers) }} @endif @if($warranty && $warranty->warranty_days > 0)| * Warranty: {{ $warranty->warranty_days }}D @endif</td></tr>
                @endif
            @endforeach
            {{--
                <tr>
                    <td>1. Laptop Pro 15"</td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;">1,200.00</td>
                    <td style="text-align: center;">5%</td>
                    <td style="text-align: right;">1,140.00</td>
                </tr>
                <tr class="details-row">
                    <td colspan="5">
                        &nbsp;&nbsp;* SL: SN-987654321 &nbsp;&nbsp;|&nbsp;&nbsp; * Warranty: 2 Years
                    </td>
                </tr>
                <tr>
                    <td>2. Wireless Mouse X1</td>
                    <td style="text-align: center;">2</td>
                    <td style="text-align: right;">50.00</td>
                    <td style="text-align: center;">0%</td>
                    <td style="text-align: right;">100.00</td>
                </tr>
                <tr class="details-row">
                    <td colspan="5">
                        &nbsp;&nbsp;* SL: SN-554321990, SN-554321901 &nbsp;&nbsp;|&nbsp;&nbsp; * Warranty: 1 Year
                    </td>
                </tr>
                <tr>
                    <td>3. Mouse Pad</td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;">50.00</td>
                    <td style="text-align: center;">0%</td>
                    <td style="text-align: right;">50.00</td>
                </tr>
                <tr class="details-row"><td colspan="5"></td></tr>
            --}}
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
                        @forelse($payments as $payment)<tr><td>{{ optional($payment->created_at)->format('d M Y') }}</td><td>{{ $payment->payment_method ?? 'Payment' }}</td><td>{{ $payment->trx_note ?: '—' }}</td><td style="text-align:right;">৳{{ number_format((float)$payment->amount,2) }}</td></tr>@empty<tr><td colspan="4">No payments recorded</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            
            <table class="totals-table">
                <tr><td>Gross Subtotal:</td><td>৳{{ number_format($subtotal,2) }}</td></tr>
                @if($discount>0)<tr><td>Total Discount:</td><td>-৳{{ number_format($discount,2) }}</td></tr>@endif
                @if((float)$order->shipping_charge>0)<tr><td>Shipping:</td><td>+৳{{ number_format($order->shipping_charge,2) }}</td></tr>@endif
                <tr class="border-top" style="font-weight:bold;"><td>Total Invoice Value:</td><td>৳{{ number_format((float)$order->amount,2) }}</td></tr>
                <tr style="font-weight:bold;"><td>Total Paid (To Date):</td><td>৳{{ number_format($paid,2) }}</td></tr>
                <tr class="border-double" style="font-weight:bold;"><td>Net Balance Due:</td><td>৳{{ number_format($due,2) }}</td></tr>
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
