@extends('backEnd.layouts.master')
@section('title', 'Warranty Sales')
@section('content')
<div class="container-fluid">
    <h4 class="mb-3">🧾 Warranty Sales</h4>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-auto">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="claimed" {{ request('status') === 'claimed' ? 'selected' : '' }}>Claimed</option>
                        <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                    </select>
                </div>
                <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Order ID</th>
                            <th>Claim Count</th>
                            <th>Type</th>
                            <th>Days</th>
                            <th>Expiry</th>
                            <th>Remaining</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr>
                            <td>{{ $sale->id }}</td>
                            <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                            <td>{{ $sale->product->name ?? 'N/A' }}</td>
                            <td>{{ $sale->order_id ?? 'N/A' }}</td>
                            <td>
                                @if($sale->claims_count > 0)
                                    <a href="{{ route('admin.warranty.claims.index', ['warranty_sale_id' => $sale->id]) }}" class="btn btn-sm btn-outline-info">
                                        {{ $sale->claims_count }} claim(s)
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ ucfirst($sale->warranty_type) }}</td>
                            <td>{{ $sale->warranty_days }}d</td>
                            <td>{{ $sale->warranty_end_date?->format('d M, Y') ?? 'N/A' }}</td>
                            <td>
                                @if($sale->warranty_days > 0)
                                    <span class="badge bg-{{ $sale->remaining_days <= 7 ? 'danger' : 'success' }}">
                                        {{ $sale->remaining_days }} days
                                    </span>
                                @else — @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ \App\Enums\WarrantySaleStatus::from($sale->status)->badgeClass() }}">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.warranty.sales.show', $sale) }}" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center">No warranty sales found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $sales->links() }}
        </div>
    </div>
</div>
@endsection
