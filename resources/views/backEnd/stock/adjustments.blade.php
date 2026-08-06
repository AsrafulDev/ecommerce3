@extends('backEnd.layouts.master')
@section('title', 'Stock Adjustments')

@section('css')
<style>
    .card { border: none; border-radius: 10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.1); }
    .card-header { background: #fff; border-bottom: 1px solid #e3e6f0; padding: 1rem 1.35rem; font-weight: 700; border-radius: 10px 10px 0 0 !important; }
    .table thead th { background: #f8f9fc; color: #4e73df; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e3e6f0; padding: 0.75rem; }
    .table tbody td { font-size: 0.85rem; vertical-align: middle; padding: 0.6rem; }
    .table-hover tbody tr:hover { background: #f8f9fc; }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <h4 class="fw-bold text-gray-800 mb-0">
            <i data-feather="edit" class="text-warning me-1"></i> Stock Adjustments
        </h4>
        <a href="{{ route('admin.stock.adjustments.create') }}" class="btn btn-warning rounded-pill shadow-sm">
            <i data-feather="plus" class="me-1" style="width:14px;"></i> New Adjustment
        </a>
    </div>

    {{-- FILTERS --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="product_id" class="form-control form-select">
                        <option value="">All Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-control form-select">
                        <option value="">All Types</option>
                        <option value="addition" {{ request('type') === 'addition' ? 'selected' : '' }}>Addition</option>
                        <option value="reduction" {{ request('type') === 'reduction' ? 'selected' : '' }}>Reduction</option>
                        <option value="correction" {{ request('type') === 'correction' ? 'selected' : '' }}>Correction</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary rounded-pill"><i data-feather="search" style="width:14px;"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Product</th><th>Type</th><th>Previous</th><th>New</th><th>Changed</th><th>Reason</th><th>By</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $adj)
                            <tr>
                                <td>{{ $adj->id }}</td>
                                <td>{{ Str::limit($adj->product->name ?? '—', 25) }}</td>
                                <td>
                                    @php $b = match($adj->type) { 'addition' => 'success', 'reduction' => 'danger', 'correction' => 'info', default => 'secondary' }; @endphp
                                    <span class="badge bg-{{ $b }}">{{ ucfirst($adj->type) }}</span>
                                </td>
                                <td>{{ $adj->current_stock }}</td>
                                <td>{{ $adj->new_stock }}</td>
                                <td>{{ $adj->quantity }}</td>
                                <td><small class="text-muted">{{ Str::limit($adj->reason, 30) }}</small></td>
                                <td><small>{{ $adj->creator->name ?? '—' }}</small></td>
                                <td><small>{{ $adj->created_at->format('d/m/Y h:i A') }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No adjustments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">{{ $adjustments->withQueryString()->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection
