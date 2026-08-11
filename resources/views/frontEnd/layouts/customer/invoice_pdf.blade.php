<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->invoice_id }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 0; }
        .inv-box { padding: 24px 30px; }

        .inv-header { width: 100%; margin-bottom: 18px; }
        .inv-header table { width: 100%; border-collapse: collapse; }
        .inv-header td { vertical-align: top; }
        .inv-logo img { width: 140px; max-height: 60px; }
        .inv-title-bar { background: #0d6efd; color: #fff; text-align: right; padding: 12px 18px; border-radius: 4px; }
        .inv-title-bar h1 { margin: 0; font-size: 26px; font-weight: 700; }
        .inv-meta { background: #eef4ff; border: 1px solid #cfe0ff; border-radius: 4px; padding: 8px 14px; text-align: right; margin-top: 8px; font-size: 13px; color: #1a3a5c; }
        .inv-meta strong { color: #0d6efd; }

        .from-to { width: 100%; margin-bottom: 18px; }
        .from-to table { width: 100%; border-collapse: collapse; }
        .from-to td { vertical-align: top; width: 50%; }
        .info-label { font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; margin-bottom: 4px; }
        .info-val { font-size: 12px; line-height: 1.5; }
        .info-val p { margin: 2px 0; }

        table.items { width: 100%; border-collapse: collapse; margin: 14px 0; }
        table.items th { background: #0d6efd; color: #fff; padding: 8px 6px; font-size: 11px; text-align: left; }
        table.items td { padding: 8px 6px; border-bottom: 1px solid #e5e7eb; font-size: 12px; vertical-align: top; }
        table.items tr:nth-child(even) td { background: #f8fafc; }
        .num { text-align: right; }

        table.summary { width: 330px; float: right; border-collapse: collapse; margin-bottom: 20px; }
        table.summary td { padding: 7px 12px; font-size: 12px; }
        table.summary .sum-head { background: #0d6efd; color: #fff; font-weight: 700; }
        table.summary tr.total-row td { font-weight: 800; font-size: 14px; border-top: 2px solid #0d6efd; }
        .bg-green { background: #27ae60; color: #fff; font-weight: 700; }
        .bg-red { background: #c0392b; color: #fff; font-weight: 700; }
        .bg-green2 { background: #2ecc71; color: #fff; font-weight: 700; }

        .terms { clear: both; text-align: center; padding-top: 16px; }
        .terms h5 { font-style: italic; margin: 0 0 4px; }
        .terms p { font-size: 11px; color: #666; font-style: italic; margin: 0; }

        .size-color { display: block; font-size: 11px; color: #666; }
        .warranty-line { display: block; font-size: 11px; color: #15803d; }
        .clearfix { clear: both; }
    </style>
</head>
<body>
@php
    // ১. পেমেন্ট ইনফো নেওয়া (Latest payment)
    $payment = \App\Models\Payment::where('order_id', $order->id)->orderBy('id','desc')->first();

    $gateway_status = $payment ? strtolower(trim($payment->payment_status)) : '';
    $payment_method = $payment ? strtolower(trim($payment->payment_method)) : strtolower(trim($order->payment_method ?? ''));

    $admin_status   = strtolower(trim($order->payment_status ?? ''));
    $order_status   = $order->status ? strtolower(trim($order->status->slug)) : ($order->order_status ?? '');

    $grand_total = $order->amount;
    $paid_amount = 0;

    if ($payment && !in_array($gateway_status, ['failed', 'cancel', 'cancelled', 'rejected'])) {
        $paid_amount = $payment->amount;
    }

    $is_cod = in_array($payment_method, ['cod', 'cash', 'cash_on_delivery', 'hand cash']);
    $is_order_completed = in_array($order_status, ['completed', 'delivered']) || in_array($admin_status, ['completed', 'delivered']);

    if ($is_cod && !$is_order_completed) {
        if ($paid_amount >= $grand_total) {
            $paid_amount = 0;
        }
    }

    if ($is_order_completed) {
        $paid_amount = $grand_total;
    }
    elseif (($paid_amount == 0 || !$payment) && in_array($admin_status, ['paid', 'success', 'approved'])) {
        $paid_amount = $grand_total;
    }

    // 💰 Totals
    $totalProductDiscount = 0;
    $totalWarrantyCharge = 0;
    $totalItemSubtotal = 0;
    foreach ($order->orderdetails as $item) {
        $totalProductDiscount += ((float) ($item->product_discount ?? 0)) * $item->qty;
        $totalWarrantyCharge += ((float) ($item->warranty_price ?? 0)) * $item->qty;
        $totalItemSubtotal += (float)($item->sale_price ?? 0) * $item->qty;
    }

    $grand_total = $totalItemSubtotal + (float)$order->shipping_charge - (float)$order->discount;
    $due_amount = max(0, $grand_total - $paid_amount);

    $subtotal = 0;
    foreach ($order->orderdetails as $item) {
        $basePrice = (float)($item->sale_price ?? 0) - (float)($item->warranty_price ?? 0) + (float)($item->product_discount ?? 0);
        $subtotal += $basePrice * $item->qty;
    }
@endphp

<div class="inv-box">
    {{-- HEADER --}}
    <div class="inv-header">
        <table>
            <tr>
                <td style="width:40%;">
                    <div class="inv-logo">
                        @php
                            // Dompdf can't render WEBP/SVG and the stored path may carry a 'public/' prefix,
                            // so resolve the real file and rasterize it to a PNG data URI via GD.
                            $logoPath = $generalsetting->white_logo ?? $generalsetting->dark_logo ?? '';
                            $logoPath = ltrim((string) $logoPath, '/');
                            if (str_starts_with($logoPath, 'public/')) {
                                $logoPath = substr($logoPath, 7);
                            }
                            $logoAbs = public_path($logoPath);
                            $logoImg = '';
                            if (is_file($logoAbs) && function_exists('imagecreatefromstring')) {
                                $img = @imagecreatefromstring((string) file_get_contents($logoAbs));
                                if ($img !== false) {
                                    // Downscale to keep the PDF small (logo displays at ~140px)
                                    $maxW = 300;
                                    $w = imagesx($img);
                                    $h = imagesy($img);
                                    if ($w > $maxW) {
                                        $nw = $maxW;
                                        $nh = (int) round($h * ($maxW / $w));
                                        $dst = imagecreatetruecolor($nw, $nh);
                                        imagealphablending($dst, false);
                                        imagesavealpha($dst, true);
                                        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                                        imagedestroy($img);
                                        $img = $dst;
                                    }
                                    ob_start();
                                    imagepng($img);
                                    $logoImg = 'data:image/png;base64,' . base64_encode((string) ob_get_clean());
                                    imagedestroy($img);
                                }
                            }
                        @endphp
                        @if($logoImg)
                            <img src="{{ $logoImg }}">
                        @else
                            <div style="font-size:24px; font-weight:bold; color:#0d6efd;">{{ $generalsetting->name ?? '' }}</div>
                        @endif
                    </div>
                    <div class="info-val" style="margin-top:14px;">
                        <p><strong>Payment Method:</strong> {{ $payment_method }}</p>
                        <p>
                            <strong>Status:</strong>
                            @if($paid_amount >= $grand_total)
                                <span style="color:green;font-weight:bold;text-transform:uppercase;">PAID</span>
                            @elseif($paid_amount > 0)
                                <span style="color:#007bff;font-weight:bold;text-transform:uppercase;">PARTIAL PAID</span>
                            @else
                                <span style="color:red;font-weight:bold;text-transform:uppercase;">UNPAID</span>
                            @endif
                        </p>
                    </div>
                </td>
                <td style="width:60%;">
                    <div class="inv-title-bar"><h1>INVOICE</h1></div>
                    <div class="inv-meta">
                        Invoice Date: <strong>{{ $order->created_at->format('d-m-y') }}</strong>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Invoice No: <strong>#{{ $order->invoice_id }}</strong>
                    </div>

                    {{-- 📦 Barcode of invoice number --}}
                    @php
                        $bc = new \Picqer\Barcode\BarcodeGeneratorPNG();
                        $bcUri = 'data:image/png;base64,' . base64_encode($bc->getBarcode((string) $order->invoice_id, $bc::TYPE_CODE_128, 2, 45));
                    @endphp
                    <div style="text-align:right; margin-top:8px;">
                        <img src="{{ $bcUri }}" style="height:36px;">
                        <div style="font-size:12px; font-weight:bold; letter-spacing:3px; margin-top:2px;">#{{ $order->invoice_id }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- FROM / TO --}}
    <div class="from-to">
        <table>
            <tr>
                <td>
                    <div class="info-label">Invoice From</div>
                    <div class="info-val">
                        <p><strong>{{ $generalsetting->name ?? '' }}</strong></p>
                        <p>{{ $contact->phone ?? '' }}</p>
                        <p>{{ $contact->email ?? '' }}</p>
                        <p>{{ $contact->address ?? '' }}</p>
                    </div>
                </td>
                <td>
                    <div class="info-label">Invoice To</div>
                    <div class="info-val">
                        <p><strong>{{ $order->shipping?->name }}</strong></p>
                        <p>{{ $order->shipping?->phone }}</p>
                        <p>{{ $order->shipping?->address }}</p>
                        <p>{{ $order->shipping?->area }}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($order->order_note) || !empty($order->note))
        <div class="info-val" style="margin-bottom:10px;">
            <strong>Order Note:</strong> {{ $order->order_note ?? $order->note }}
        </div>
    @endif

    {{-- ITEMS --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:4%;">SL</th>
                <th>Product</th>
                <th class="num" style="width:13%;">Unit Price</th>
                <th class="num" style="width:11%;">Discount</th>
                <th class="num" style="width:11%;">Warranty</th>
                <th class="num" style="width:7%;">Qty</th>
                <th class="num" style="width:13%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderdetails as $value)
            @php
                $productDiscount = (float) ($value->product_discount ?? 0);
                $warrantyPrice   = (float) ($value->warranty_price ?? 0);
                $lineSubtotal    = $value->sale_price * $value->qty;
                $sizeDisplay = null; $colorDisplay = null;
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
                $warrantyLine = '';
                if ($value->warranty_tier_id) {
                    $wt = \App\Models\ProductWarrantyTier::find($value->warranty_tier_id);
                    $ws = \App\Models\WarrantySale::where('order_detail_id', $value->id)->first();
                    if ($wt && $wt->warranty_days > 0) {
                        $warrantyLine = $wt->tier_name . ' (' . $wt->warranty_days . ' Days)';
                        if ($ws && $ws->warranty_end_date) {
                            $warrantyLine .= ' — Expires: ' . $ws->warranty_end_date->format('d M, Y');
                        }
                    }
                }
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    {{ $value->product_name }}
                    @if($sizeDisplay)<span class="size-color">Size: {{ $sizeDisplay }}</span>@endif
                    @if($colorDisplay)<span class="size-color">Color: {{ $colorDisplay }}</span>@endif
                    @if($warrantyLine)<span class="warranty-line">Warranty: {{ $warrantyLine }}</span>@endif
                </td>
                <td class="num">Tk {{ number_format($value->sale_price - $warrantyPrice, 2) }}</td>
                <td class="num">@if($productDiscount > 0)-Tk {{ number_format($productDiscount, 2) }}@else — @endif</td>
                <td class="num">@if($warrantyPrice > 0)+Tk {{ number_format($warrantyPrice, 2) }}@else — @endif</td>
                <td class="num">{{ $value->qty }}</td>
                <td class="num">Tk {{ number_format($lineSubtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SUMMARY --}}
    <table class="summary">
        <tbody>
            <tr class="sum-head">
                <td><strong>Summary</strong></td>
                <td></td>
            </tr>
            <tr>
                <td>SubTotal</td>
                <td>Tk {{ number_format($subtotal, 2) }}</td>
            </tr>
            @if($totalProductDiscount > 0)
            <tr>
                <td>Wholesale Discount (-)</td>
                <td>-Tk {{ number_format($totalProductDiscount, 2) }}</td>
            </tr>
            @endif
            @if($totalWarrantyCharge > 0)
            <tr>
                <td>Warranty Charge (+)</td>
                <td>+Tk {{ number_format($totalWarrantyCharge, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td>Shipping (+)</td>
                <td>Tk {{ number_format($order->shipping_charge, 2) }}</td>
            </tr>
            <tr>
                <td>Coupon Discount (-)</td>
                <td>Tk {{ number_format($order->discount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Amount</td>
                <td>Tk {{ number_format($grand_total, 2) }}</td>
            </tr>

            @if($paid_amount > 0 && $due_amount > 0)
                <tr class="bg-green"><td>Paid / Advance</td><td>Tk {{ number_format($paid_amount, 2) }}</td></tr>
                <tr class="bg-red"><td>Due Amount</td><td>Tk {{ number_format($due_amount, 2) }}</td></tr>
            @elseif($paid_amount >= $grand_total)
                <tr class="bg-green"><td>Paid Amount</td><td>Tk {{ number_format($paid_amount, 2) }}</td></tr>
                <tr class="bg-green2"><td>Due Amount</td><td>Tk 0.00</td></tr>
            @else
                <tr class="bg-red"><td>Paid Amount</td><td>Tk 0.00</td></tr>
                <tr class="bg-red"><td>Due Amount</td><td>Tk {{ number_format($grand_total, 2) }}</td></tr>
            @endif
        </tbody>
    </table>

    <div class="clearfix"></div>

    <div class="terms">
        <h5>Terms &amp; Conditions</h5>
        <p>* This is a computer generated invoice.</p>
    </div>
</div>
</body>
</html>
