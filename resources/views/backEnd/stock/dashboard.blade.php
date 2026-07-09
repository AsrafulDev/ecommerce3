@extends('backEnd.layouts.master')
@section('title', 'Stock Dashboard')

@section('css')
<style>
    .stat-card {
        padding: 1.5rem;
        background: #fff; border-radius: 12px; border: 1px solid #f1f5f9;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        display: flex; align-items: center; justify-content: space-between;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    .stat-icon-box {
        width: 50px; height: 50px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 24px;
    }
    .bg-light-primary { background: #e0e7ff; color: #4338ca; }
    .bg-light-info { background: #e0f2fe; color: #0369a1; }
    .bg-light-success { background: #dcfce7; color: #166534; }
    .bg-light-warning { background: #fef9c3; color: #a16207; }
    .stat-title { font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-size: 1.6rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.2; }
    .table-modern th { background: #f8fafc; color: #475569; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.75rem; border-bottom: 1px solid #e2e8f0; }
    .table-modern td { padding: 0.75rem; vertical-align: middle; font-size: 0.85rem; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .table-modern tr:hover td { background: #f8fafc; }
    .badge-costing { font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 4px; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="box" class="text-primary me-2"></i> {{ __('Stock Dashboard') }}
            </h4>
            <p class="text-muted small mb-0">{{ __('Real-time inventory overview and management.') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.stock.adjustments.create') }}" class="btn btn-warning rounded-pill shadow-sm btn-sm">
                <i data-feather="edit" class="me-1" style="width:14px;"></i> Adjust Stock
            </a>
            <a href="{{ route('admin.stock.batches') }}" class="btn btn-primary rounded-pill shadow-sm btn-sm">
                <i data-feather="layers" class="me-1" style="width:14px;"></i> View Batches
            </a>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">{{ __('Total Products') }}</div>
                    <h3 class="stat-value">{{ number_format($totalProducts) }}</h3>
                </div>
                <div class="stat-icon-box bg-light-primary"><i data-feather="package"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">{{ __('Total Stock Qty') }}</div>
                    <h3 class="stat-value">{{ number_format($totalStockQty) }}</h3>
                </div>
                <div class="stat-icon-box bg-light-info"><i data-feather="layers"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">{{ __('Stock Value') }}</div>
                    <h3 class="stat-value">৳{{ number_format($totalStockValue, 2) }}</h3>
                </div>
                <div class="stat-icon-box bg-light-success"><i data-feather="dollar-sign"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">{{ __('Low Stock Items') }}</div>
                    <h3 class="stat-value text-warning">{{ $lowStockProducts }}</h3>
                </div>
                <div class="stat-icon-box bg-light-warning"><i data-feather="alert-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- COSTING METHOD DISTRIBUTION --}}
        <div class="col-md-4">
            <div class="card card-modern h-100">
                <div class="card-header bg-white fw-bold py-3">
                    <i data-feather="bar-chart-2" class="me-1 text-primary" style="width:16px;"></i>
                    {{ __('Costing Methods') }}
                </div>
                <div class="card-body">
                    @if($costingMethods->count())
                        @foreach($costingMethods as $method => $count)
                            @php
                                $badge = match($method) { 'fifo' => 'bg-info', 'lifo' => 'bg-warning', 'average' => 'bg-success', default => 'bg-secondary' };
                                $pct = $totalProducts > 0 ? round($count / $totalProducts * 100) : 0;
                            @endphp
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="badge {{ $badge }} badge-costing">{{ strtoupper($method) }}</span>
                                    <small class="text-muted ms-2">{{ $count }} products</small>
                                </div>
                                <strong>{{ $pct }}%</strong>
                            </div>
                            <div class="progress mb-3" style="height:6px;">
                                <div class="progress-bar {{ $badge }}" style="width:{{ $pct }}%"></div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No costing methods configured yet.</p>
                    @endif
                    <a href="{{ route('admin.stock.valuation') }}" class="btn btn-sm btn-outline-primary mt-2 rounded-pill">
                        <i data-feather="dollar-sign" style="width:14px;"></i> View Valuation
                    </a>
                </div>
            </div>
        </div>

        {{-- RECENT BATCHES --}}
        <div class="col-md-4">
            <div class="card card-modern h-100">
                <div class="card-header bg-white fw-bold py-3">
                    <i data-feather="layers" class="me-1 text-info" style="width:16px;"></i>
                    {{ __('Recent Batches') }}
                </div>
                <div class="card-body p-0">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr><th>Batch</th><th>Product</th><th>Qty</th><th>Cost</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentBatches as $batch)
                                <tr>
                                    <td><small>{{ $batch->batch_no ?: '—' }}</small></td>
                                    <td><small>{{ Str::limit($batch->product->name ?? '—', 25) }}</small></td>
                                    <td><span class="badge bg-{{ $batch->type === 'in' ? 'success' : 'danger' }}">{{ $batch->remaining_qty }}/{{ $batch->quantity }}</span></td>
                                    <td>৳{{ number_format($batch->unit_cost, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No batches yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RECENT ADJUSTMENTS --}}
        <div class="col-md-4">
            <div class="card card-modern h-100">
                <div class="card-header bg-white fw-bold py-3">
                    <i data-feather="edit" class="me-1 text-warning" style="width:16px;"></i>
                    {{ __('Recent Adjustments') }}
                </div>
                <div class="card-body p-0">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr><th>Product</th><th>Type</th><th>Qty</th><th>Reason</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentAdjustments as $adj)
                                <tr>
                                    <td><small>{{ Str::limit($adj->product->name ?? '—', 22) }}</small></td>
                                    <td>
                                        @php $b = match($adj->type) { 'addition' => 'success', 'reduction' => 'danger', 'correction' => 'info', default => 'secondary' }; @endphp
                                        <span class="badge bg-{{ $b }}">{{ ucfirst($adj->type) }}</span>
                                    </td>
                                    <td>{{ $adj->quantity }}</td>
                                    <td><small class="text-muted">{{ Str::limit($adj->reason, 20) }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No adjustments.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
