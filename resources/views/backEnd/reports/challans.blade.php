@extends('backEnd.layouts.master')
@section('title', 'Challan Register')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h4 class="mb-1 fw-bold text-dark">
            <i data-feather="truck" class="text-primary me-2"></i> Challan Register
        </h4>
        <p class="text-muted mb-0">Sales delivery challans and stock-in challans. Prices are not shown; open a challan to print it.</p>
    </div>

    <form method="GET" action="{{ route('admin.reports.challans') }}" class="card mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="all" @selected($filters['type'] === 'all')>All</option>
                    <option value="sales" @selected($filters['type'] === 'sales')>Sales delivery</option>
                    <option value="stock_in" @selected($filters['type'] === 'stock_in')>Stock-in</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Customer / supplier</label>
                <input type="text" name="party" value="{{ $filters['party'] }}" class="form-control" placeholder="Name">
            </div>
            <div class="col-md-2">
                <label class="form-label">Product</label>
                <input type="text" name="product" value="{{ $filters['product'] }}" class="form-control" placeholder="Name">
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card mb-0"><div class="card-body">
                <div class="text-muted small">Sales delivery challans</div>
                <div class="h4 mb-0">{{ $salesCount }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card mb-0"><div class="card-body">
                <div class="text-muted small">Stock-in challans</div>
                <div class="h4 mb-0">{{ $stockInCount }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Customer / supplier</th>
                        <th>Products</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Challan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($challans as $row)
                        <tr>
                            <td>{{ $row['date']->format('d M Y') }}</td>
                            <td>
                                @if($row['kind'] === 'sales')
                                    <span class="badge bg-info-subtle text-info">Sales delivery</span>
                                @else
                                    <span class="badge bg-success-subtle text-success">Stock-in</span>
                                @endif
                            </td>
                            <td>{{ $row['ref'] }}</td>
                            <td>{{ $row['party'] }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($row['items'], 80) }}</td>
                            <td class="text-end">{{ $row['qty'] }}</td>
                            <td class="text-end">
                                <a href="{{ $row['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No challans in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $challans->links() }}
    </div>

</div>
@endsection
