<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery Challan {{ $order->invoice_display ?? $order->invoice_id }}</title>
    @include('backEnd.challan._style')
</head>
<body>
@include('backEnd.challan._toolbar')

<div class="sheet">
    <table class="head">
        <tr>
            <td>
                <span class="title">{{ $generalsetting->name ?? config('app.name') }}</span><br>
                {{ $contact?->address ?? '' }}<br>
                Phone: {{ $contact?->phone ?? '' }}
            </td>
            <td class="right">
                <span class="doc">DELIVERY CHALLAN</span><br>
                <strong>Challan No:</strong> {{ $order->invoice_display ?? $order->invoice_id }}<br>
                <strong>Date:</strong> {{ optional($order->created_at)->format('d M Y') }}
            </td>
        </tr>
    </table>

    <div class="party">
        <strong>DELIVER TO:</strong><br>
        <table>
            <tr><td class="label">Name:</td><td>{{ $order->shipping->name ?? $order->customer?->name ?? '—' }}</td></tr>
            <tr><td class="label">Phone:</td><td>{{ $order->shipping->phone ?? $order->customer?->phone ?? '—' }}</td></tr>
            <tr><td class="label">Address:</td><td>{{ trim(($order->shipping->address ?? '') . (($order->shipping->area ?? '') ? ', ' . $order->shipping->area : '')) ?: '—' }}</td></tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Product</th>
                <th>Colour / Size</th>
                <th class="num">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderdetails as $item)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ collect([optional($item->color)->name, optional($item->size)->name])->filter()->implode(' / ') ?: '—' }}</td>
                    <td class="num">{{ (int) $item->qty }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="right">Total quantity</th>
                <th class="num">{{ (int) $order->orderdetails->sum('qty') }}</th>
            </tr>
        </tfoot>
    </table>

    <table class="signs">
        <tr>
            <td>Prepared by</td>
            <td>Delivered by</td>
            <td>Received by (customer)</td>
        </tr>
    </table>
</div>
</body>
</html>
