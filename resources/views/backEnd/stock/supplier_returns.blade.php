@extends('backEnd.layouts.master')
@section('title', 'Supplier Returns')

@section('css')
<style>
    .card { border: none; border-radius: 10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.1); }
    .card-header { background: #fff; border-bottom: 1px solid #e3e6f0; padding: 1rem 1.35rem; font-weight: 700; border-radius: 10px 10px 0 0 !important; }
    .table thead th { background: #f8f9fc; color: #4e73df; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e3e6f0; padding: 0.75rem; }
    .table tbody td { font-size: 0.85rem; vertical-align: middle; padding: 0.6rem; color: #5a5c69; }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <h4 class="fw-bold text-gray-800 mb-0">
            <i data-feather="rotate-ccw" class="text-danger me-1"></i> Supplier Returns
        </h4>
        <a href="{{ route('admin.stock.supplier-returns.create') }}" class="btn btn-danger rounded-pill shadow-sm">
            <i data-feather="plus" class="me-1" style="width:14px;"></i> New Return
        </a>
    </div>

    {{-- FILTERS --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="supplier_id" class="form-control form-select">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                        <tr>
                            <th>#</th>
                            <th>Return No</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total Qty</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $r)
                            <tr>
                                <td>{{ $returns->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $r->return_no }}</strong></td>
                                <td>{{ $r->supplier->name ?? '—' }}</td>
                                <td><small>{{ $r->return_date ? date('d/m/Y', strtotime($r->return_date)) : '—' }}</small></td>
                                <td>{{ $r->items->count() }}</td>
                                <td>{{ $r->total_qty }}</td>
                                <td>৳{{ number_format($r->total_amount, 2) }}</td>
                                <td>
                                    @php $b = match($r->status) { 'completed' => 'success', 'pending' => 'warning', 'cancelled' => 'danger', default => 'secondary' }; @endphp
                                    <span class="badge bg-{{ $b }}">{{ ucfirst($r->status) }}</span>
                                </td>
                                <td><small class="text-muted">{{ Str::limit($r->reason, 25) }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No returns found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">{{ $returns->withQueryString()->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection
