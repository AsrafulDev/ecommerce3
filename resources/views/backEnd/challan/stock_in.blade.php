<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock-In Challan {{ $purchase->invoice_no ?? $purchase->id }}</title>
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
                <span class="doc">STOCK-IN CHALLAN</span><br>
                <strong>Purchase Ref:</strong> {{ $purchase->invoice_no ?? $purchase->id }}<br>
                <strong>Date:</strong> {{ $purchase->purchase_date ? \Illuminate\Support\Carbon::parse($purchase->purchase_date)->format('d M Y') : '—' }}
            </td>
        </tr>
    </table>

    <div class="party">
        <strong>RECEIVED FROM SUPPLIER:</strong><br>
        <table>
            <tr><td class="label">Supplier:</td><td>{{ $purchase->supplier->name ?? '—' }}</td></tr>
            <tr><td class="label">Phone:</td><td>{{ $purchase->supplier->phone ?? '—' }}</td></tr>
            <tr><td class="label">Address:</td><td>{{ $purchase->supplier->address ?? '—' }}</td></tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Product</th>
                <th>Product code</th>
                <th class="num">Qty received</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $item->product->name ?? '—' }}</td>
                    <td>{{ $item->product->product_code ?? '—' }}</td>
                    <td class="num">{{ (int) $item->qty }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="right">Total quantity</th>
                <th class="num">{{ (int) $purchase->items->sum('qty') }}</th>
            </tr>
        </tfoot>
    </table>

    <table class="signs">
        <tr>
            <td>Received by (store)</td>
            <td>Checked by</td>
            <td>Supplier representative</td>
        </tr>
    </table>
</div>
</body>
</html>
