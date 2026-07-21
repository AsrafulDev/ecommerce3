@extends('backEnd.layouts.master')
@section('title', 'Warranty Dashboard')
@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">🛡️ Warranty Management</h4>
        <div>
            <a href="{{ route('admin.warranty.supplier.index') }}" class="btn btn-outline-primary btn-sm me-2">📦 Supplier Warranties</a>
            <a href="{{ route('admin.warranty.tiers.index') }}" class="btn btn-outline-info btn-sm me-2">🏷️ Warranty Tiers</a>
            <a href="{{ route('admin.warranty.sales.index') }}" class="btn btn-outline-success btn-sm me-2">🧾 Sales</a>
            <a href="{{ route('admin.warranty.claims.index') }}" class="btn btn-outline-warning btn-sm">🔧 Claims</a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h1 class="mb-0">{{ $stats['total_warranties'] }}</h1>
                    <small>Total Warranties</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h1 class="mb-0">{{ $stats['active_warranties'] }}</h1>
                    <small>Active Warranties</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h1 class="mb-0">{{ $stats['expired_warranties'] }}</h1>
                    <small>Expired</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h1 class="mb-0">{{ $stats['pending_claims'] }}</h1>
                    <small>Pending Claims</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Expiring Soon --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><strong>⏰ Expiring Soon (Next 7 Days)</strong></div>
                <div class="card-body">
                    @forelse($expiringSoon as $sale)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $sale->product->name ?? 'N/A' }}</span>
                            <span class="{{ $sale->remaining_days <= 3 ? 'text-danger' : 'text-warning' }}">
                                {{ $sale->remaining_days }} days left
                            </span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No warranties expiring soon.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Claims --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><strong>🔧 Recent Claims</strong></div>
                <div class="card-body">
                    @forelse($recentClaims as $claim)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <div>
                                <a href="{{ route('admin.warranty.claims.show', $claim) }}">
                                    <strong>#{{ $claim->claim_number }}</strong>
                                </a>
                                <br><small>{{ $claim->product->name ?? 'N/A' }} — {{ $claim->customer->name ?? 'N/A' }}</small>
                            </div>
                            <span class="badge bg-{{ $claim->status_enum->badgeClass() }}">
                                {{ $claim->status_enum->label() }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No claims yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
