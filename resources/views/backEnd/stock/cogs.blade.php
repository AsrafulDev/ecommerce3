@extends('backEnd.layouts.master')
@section('title', 'COGS Report')

@section('css')
<style>
    .card { border: none; border-radius: 10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.1); }
    .card-header { background: #fff; border-bottom: 1px solid #e3e6f0; padding: 1rem 1.35rem; font-weight: 700; border-radius: 10px 10px 0 0 !important; }
    .table thead th { background: #f8f9fc; color: #4e73df; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e3e6f0; padding: 0.75rem; }
    .table tbody td { font-size: 0.85rem; vertical-align: middle; padding: 0.6rem; color: #5a5c69; }
    .summary-card { padding: 1.25rem; border-radius: 10px; text-align: center; }
    .summary-card h3 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0; }
    .summary-card small { text-transform: uppercase; font-weight: 600; font-size: 0.7rem; letter-spacing: 0.5px; }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <h4 class="fw-bold text-gray-800 mb-0">
            <i data-feather="trending-down" class="text-danger me-1"></i> Cost of Goods Sold (COGS)
        </h4>
        <a href="{{ route('admin.stock.dashboard') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i data-feather="arrow-left" class="me-1" style="width:14px;"></i> Dashboard
        </a>
    </div>

    {{-- SUMMARY --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="summary-card bg-light-danger">
                <small class="text-danger">Total COGS</small>
                <h3 class="text-danger">৳{{ number_format($totalCogs, 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card bg-light-success">
                <small class="text-success">Total Revenue</small>
                <h3 class="text-success">৳{{ number_format($totalRevenue, 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card bg-light-primary">
                <small class="text-primary">Gross Profit</small>
                <h3 class="text-primary">৳{{ number_format($totalProfit, 2) }}</h3>
            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <input type="text" name="product_id" class="form-control" placeholder="Product ID" value="{{ request('product_id') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary rounded-pill w-100"><i data-feather="search" style="width:14px;"></i> Filter</button>
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
                            <th>Invoice</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>COGS</th>
                            <th>Profit</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $d)
                            @php $profit = $d->price - $d->cogs; @endphp
                            <tr>
                                <td>{{ $details->firstItem() + $loop->index }}</td>
                                <td><small>{{ $d->order->invoice_no ?? '—' }}</small></td>
                                <td>{{ Str::limit($d->product->name ?? '—', 25) }}</td>
                                <td>{{ $d->qty }}</td>
                                <td>৳{{ number_format($d->price, 2) }}</td>
                                <td>৳{{ number_format($d->cogs, 2) }}</td>
                                <td class="fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳{{ number_format($profit, 2) }}
                                </td>
                                <td><small>{{ $d->created_at->format('d/m/Y') }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No COGS data found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">{{ $details->withQueryString()->links() }}</div>
        </div>
    </div>
</div>
@endsection
