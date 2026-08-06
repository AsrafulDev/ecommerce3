@extends('backEnd.layouts.master')
@section('title', 'Stock Valuation')

@section('css')
<style>
    .card { border: none; border-radius: 10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.1); }
    .card-header { background: #fff; border-bottom: 1px solid #e3e6f0; padding: 1rem 1.35rem; font-weight: 700; border-radius: 10px 10px 0 0 !important; }
    .table thead th { background: #f8f9fc; color: #4e73df; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e3e6f0; padding: 0.75rem; }
    .table tbody td { font-size: 0.85rem; vertical-align: middle; padding: 0.6rem; color: #5a5c69; }
    .stat-summary { background: linear-gradient(45deg, #4e73df, #224abe); color: #fff; border-radius: 10px; padding: 1.5rem; }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <h4 class="fw-bold text-gray-800 mb-0">
            <i data-feather="dollar-sign" class="text-success me-1"></i> Stock Valuation
        </h4>
        <a href="{{ route('admin.stock.dashboard') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i data-feather="arrow-left" class="me-1" style="width:14px;"></i> Dashboard
        </a>
    </div>

    {{-- TOTAL VALUE --}}
    <div class="stat-summary mb-4">
        <div class="row align-items-center">
            <div class="col">
                <small class="text-white-50 text-uppercase fw-bold">Total Inventory Value</small>
                <h2 class="text-white mb-0 fw-bold">৳{{ number_format($totalValue, 2) }}</h2>
            </div>
            <div class="col-auto">
                <i data-feather="trending-up" style="width:48px;height:48px;opacity:0.5;"></i>
            </div>
        </div>
    </div>

    {{-- PRODUCT VALUATION TABLE --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i data-feather="list" class="me-1" style="width:16px;"></i> Product-wise Valuation</span>
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search product..." value="{{ request('search') }}" style="width:200px;">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill"><i data-feather="search" style="width:14px;"></i></button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Costing Method</th>
                            <th>Stock Qty</th>
                            <th>Avg Cost</th>
                            <th>Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $products->firstItem() + $loop->index }}</td>
                                <td>{{ Str::limit($product->name, 35) }}</td>
                                <td>
                                    @php $b = match($product->costing_method) { 'fifo' => 'info', 'lifo' => 'warning', 'average' => 'success', default => 'secondary' }; @endphp
                                    <span class="badge bg-{{ $b }}">{{ strtoupper($product->costing_method ?: 'N/A') }}</span>
                                </td>
                                <td>{{ $product->stock }}</td>
                                <td>৳{{ $product->stock > 0 ? number_format($product->valuation / $product->stock, 2) : '0.00' }}</td>
                                <td class="fw-bold">৳{{ number_format($product->valuation, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No products found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">{{ $products->withQueryString()->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection
