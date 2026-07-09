@extends('backEnd.layouts.master')
@section('title', 'Stock Batches')

@section('css')
<style>
    .card { border: none; border-radius: 10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.1); }
    .card-header { background: #fff; border-bottom: 1px solid #e3e6f0; padding: 1rem 1.35rem; font-weight: 700; border-radius: 10px 10px 0 0 !important; }
    .form-label { font-weight: 600; font-size: 0.85rem; color: #5a5c69; }
    .table thead th { background: #f8f9fc; color: #4e73df; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e3e6f0; padding: 0.75rem; }
    .table tbody td { font-size: 0.85rem; vertical-align: middle; padding: 0.6rem; color: #5a5c69; }
    .table-hover tbody tr:hover { background: #f8f9fc; }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <h4 class="fw-bold text-gray-800 mb-0">
            <i data-feather="layers" class="text-primary me-1"></i> Stock Batches
        </h4>
        <div>
            <a href="{{ route('admin.stock.dashboard') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i data-feather="arrow-left" class="me-1" style="width:14px;"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="card mb-4">
        <div class="card-header">
            <i data-feather="filter" class="me-1" style="width:16px;"></i> Filter Batches
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-control form-select">
                        <option value="">All Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control form-select">
                        <option value="">All</option>
                        <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Stock In</option>
                        <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Stock Out</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-control form-select">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->company }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Batch No.</label>
                    <input type="text" name="batch_no" class="form-control" value="{{ request('batch_no') }}" placeholder="Search...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill"><i data-feather="search" style="width:14px;"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Batch No</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Remaining</th>
                            <th>Unit Cost</th>
                            <th>Supplier</th>
                            <th>Ref</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            <tr>
                                <td>{{ $batches->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $batch->batch_no ?: '—' }}</strong></td>
                                <td>{{ Str::limit($batch->product->name ?? '—', 30) }}</td>
                                <td>
                                    <span class="badge bg-{{ $batch->type === 'in' ? 'success' : 'danger' }}">
                                        {{ strtoupper($batch->type) }}
                                    </span>
                                </td>
                                <td>{{ $batch->quantity }}</td>
                                <td>
                                    @if($batch->remaining_qty > 0)
                                        <span class="text-success fw-bold">{{ $batch->remaining_qty }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>৳{{ number_format($batch->unit_cost, 2) }}</td>
                                <td><small>{{ $batch->supplier->name ?? '—' }}</small></td>
                                <td><small class="text-muted">{{ Str::limit($batch->reference_type ? class_basename($batch->reference_type) : '—', 15) }}</small></td>
                                <td><small>{{ $batch->created_at->format('d/m/Y') }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">No batches found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">
                {{ $batches->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
