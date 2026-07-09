@extends('backEnd.layouts.master')
@section('title', 'Print Barcode')

@section('css')
<style>
    body { font-family: 'Courier New', monospace; }
    .barcode-page { text-align: center; padding: 20px; }
    .barcode-label {
        display: inline-block;
        border: 1px dashed #ccc;
        padding: 10px 15px;
        margin: 8px;
        text-align: center;
        width: 250px;
        page-break-inside: avoid;
    }
    .barcode-label .product-name { font-size: 11px; font-weight: 600; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .barcode-label .price { font-size: 14px; font-weight: 700; color: #e74a3b; margin: 4px 0; }
    .barcode-label .sku { font-size: 9px; color: #666; }
    @media print {
        .no-print { display: none !important; }
        .barcode-label { border: 1px dashed #999; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2 no-print">
        <h4 class="fw-bold text-gray-800 mb-0">
            <i data-feather="tag" class="text-primary me-1"></i> Print Barcode Labels
        </h4>
        <div>
            <button onclick="window.print()" class="btn btn-primary rounded-pill shadow-sm me-2">
                <i data-feather="printer" class="me-1" style="width:14px;"></i> Print
            </button>
            <a href="{{ route('admin.stock.dashboard') }}" class="btn btn-outline-secondary rounded-pill btn-sm">
                <i data-feather="arrow-left" class="me-1" style="width:14px;"></i> Back
            </a>
        </div>
    </div>

    <div class="no-print card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.stock.barcode.print') }}" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-control select2" required>
                        <option value="">Select Product</option>
                        @foreach(\App\Models\Product::orderBy('name')->get(['id', 'name', 'barcode']) as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} {{ $p->barcode ? '('.$p->barcode.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Labels per page</label>
                    <input type="number" name="quantity" class="form-control" value="{{ request('quantity', 10) }}" min="1" max="100">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary rounded-pill w-100">
                        <i data-feather="eye" style="width:14px;"></i> Preview
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(request('product_id'))
        <div class="barcode-page">
            @for($i = 0; $i < $quantity; $i++)
                <div class="barcode-label">
                    <div class="product-name">{{ $product->name }}</div>
                    <div>
                            @php
                                $generator = new \Picqer\Barcode\BarcodeGeneratorHTML();
                                $barcodeValue = $product->barcode ?: (string)$product->id;
                                echo $generator->getBarcode($barcodeValue, $generator::TYPE_CODE_128, 2, 40);
                            @endphp
                        </div>
                    <div class="price">৳{{ number_format($product->new_price ?? $product->purchase_price ?? 0, 2) }}</div>
                    <div class="sku">{{ $product->barcode ?: 'ID: '.$product->id }}</div>
                </div>
            @endfor
        </div>
    @else
        <div class="text-center py-5 text-muted">
            <i data-feather="tag" style="width:48px;height:48px;"></i>
            <p class="mt-2">Select a product and quantity, then click Preview to generate barcode labels.</p>
        </div>
    @endif
</div>
@endsection

@section('script')
<script>
    // Barcode labels print automatically
    window.onload = function() {
        // No extra JS needed - picqer generates HTML barcodes server-side
    };
</script>
@endsection
