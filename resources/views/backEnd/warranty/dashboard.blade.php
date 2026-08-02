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
            <a href="{{ route('admin.warranty.claims.index') }}" class="btn btn-outline-warning btn-sm me-2">🔧 Claims</a>
            <a href="{{ route('admin.warranty.damage.index') }}" class="btn btn-outline-danger btn-sm">💥 Damage</a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card bg-primary">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['total_warranties'] }}</h3>
                    <small>Total Sold</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['active_warranties'] }}</h3>
                    <small>Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['supplier_warranties'] }}</h3>
                    <small>Supplier Warranties</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['expired_warranties'] }}</h3>
                    <small>Expired</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['pending_claims'] }}</h3>
                    <small>Pending Claims</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['active_claims'] }}</h3>
                    <small>Active Claims</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ⚠️ NEW CLAIMS (unreviewed, from customers) --}}
    @if($newClaims->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-danger bg-danger bg-opacity-10">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-2">
                    <strong>⚠️ New Claims Awaiting Review ({{ $newClaims->count() }})</strong>
                    <a href="{{ route('admin.warranty.claims.index') }}" class="btn btn-sm btn-light">View All Claims</a>
                </div>
                <div class="card-body py-2">
                    @foreach($newClaims as $nc)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <a href="{{ route('admin.warranty.claims.show', $nc) }}" class="fw-bold">{{ $nc->claim_number }}</a>
                            <span class="text-muted">— {{ $nc->product->name ?? 'N/A' }}</span>
                            <br><small class="text-muted">
                                Customer: {{ $nc->customer->name ?? 'N/A' }} ({{ $nc->customer->phone ?? '' }}) ·
                                Filed: {{ $nc->created_at->diffForHumans() }}
                            </small>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.warranty.claims.show', $nc) }}" class="btn btn-sm btn-primary">Review</a>
                            <form action="{{ route('admin.warranty.claims.action', [$nc, 'review']) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success">Start Review</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 🗓️ Today's & Tomorrow's Tasks --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100 border-warning">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <strong>⏰ Today's Tasks ({{ $todayTasks->count() }})</strong>
                    @if($overdueTasks->isNotEmpty())
                        <span class="badge bg-danger">{{ $overdueTasks->count() }} Overdue</span>
                    @endif
                </div>
                <div class="card-body py-2">
                    @if($overdueTasks->isNotEmpty())
                        @foreach($overdueTasks as $task)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom text-danger">
                            <div>
                                @if($task->warranty_claim)
                                <a href="{{ route('admin.warranty.claims.show', $task->warranty_claim) }}" class="text-danger"><strong>{{ $task->warranty_claim->claim_number }}</strong></a>
                                <span class="text-muted">— {{ $task->warranty_claim->product->name ?? 'N/A' }}</span>
                                @else
                                <strong class="text-muted">Claim #N/A</strong>
                                @endif
                                <br><small>{{ $task->label }} · due {{ $task->remind_at->format('d M, h:i A') }}</small>
                            </div>
                            <form action="{{ route('admin.warranty.reminders.complete', $task) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm btn-success">✅ Done</button>
                            </form>
                        </div>
                        @endforeach
                    @endif
                    @forelse($todayTasks as $task)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                @if($task->warranty_claim)
                                <a href="{{ route('admin.warranty.claims.show', $task->warranty_claim) }}"><strong>{{ $task->warranty_claim->claim_number }}</strong></a>
                                <span class="text-muted">— {{ $task->warranty_claim->product->name ?? 'N/A' }}</span>
                                @else
                                <strong class="text-muted">Claim #N/A</strong>
                                @endif
                                <br><small>{{ $task->label }} · due {{ $task->remind_at->format('h:i A') }}</small>
                            </div>
                            <form action="{{ route('admin.warranty.reminders.complete', $task) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm btn-success">✅ Done</button>
                            </form>
                        </div>
                    @empty
                        @if($overdueTasks->isEmpty())
                        <p class="text-muted mb-0 text-center py-2">No tasks due today. 🎉</p>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2"><strong>📅 Tomorrow's Tasks ({{ $tomorrowTasks->count() }})</strong></div>
                <div class="card-body py-2">
                    @forelse($tomorrowTasks as $task)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                @if($task->warranty_claim)
                                <a href="{{ route('admin.warranty.claims.show', $task->warranty_claim) }}"><strong>{{ $task->warranty_claim->claim_number }}</strong></a>
                                <span class="text-muted">— {{ $task->warranty_claim->product->name ?? 'N/A' }}</span>
                                @else
                                <strong class="text-muted">Claim #N/A</strong>
                                @endif
                                <br><small>{{ $task->label }} · due {{ $task->remind_at->format('d M, h:i A') }}</small>
                            </div>
                            <span class="text-muted small">{{ $task->remind_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0 text-center py-2">No tasks due tomorrow.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Warranty Utilization --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><strong>📊 Warranty Utilization</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td>Warranties Sold</td>
                            <td class="text-end"><strong>{{ $stats['total_warranties'] }}</strong></td>
                        </tr>
                        <tr>
                            <td>Active Warranties</td>
                            <td class="text-end"><strong class="text-success">{{ $stats['active_warranties'] }}</strong></td>
                        </tr>
                        <tr>
                            <td>Supplier Warranties Available</td>
                            <td class="text-end"><strong class="text-info">{{ $stats['supplier_warranties'] }}</strong></td>
                        </tr>
                        <tr>
                            <td>Utilization Rate</td>
                            <td class="text-end">
                                @php $utilRate = $stats['supplier_warranties'] > 0 ? round(($stats['total_warranties'] / $stats['supplier_warranties']) * 100) : 0; @endphp
                                <div class="progress" style="height:18px">
                                    <div class="progress-bar bg-{{ $utilRate > 80 ? 'danger' : ($utilRate > 50 ? 'warning' : 'success') }}"
                                         style="width:{{ min($utilRate, 100) }}%">{{ $utilRate }}%</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Claim Rate</td>
                            <td class="text-end">
                                @php $claimRate = $stats['total_warranties'] > 0 ? round(($stats['total_claims'] / $stats['total_warranties']) * 100) : 0; @endphp
                                <span class="badge bg-{{ $claimRate > 10 ? 'danger' : 'success' }}">{{ $claimRate }}%</span>
                                <small class="text-muted">({{ $stats['total_claims'] }} claims)</small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Supplier-wise Warranty Summary --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><strong>🏭 Supplier Warranty Summary</strong></div>
                <div class="card-body">
                    @forelse($supplierWarrantyStats as $sw)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <div>
                                <strong>{{ $sw['supplier_name'] }}</strong>
                                <br><small class="text-muted">Available: {{ $sw['available'] }} | Sold: {{ $sw['sold'] }}</small>
                            </div>
                            <div class="text-end">
                                @php $swUtil = $sw['available'] > 0 ? round(($sw['sold'] / $sw['available']) * 100) : 0; @endphp
                                <span class="badge bg-{{ $swUtil > 75 ? 'success' : ($swUtil > 40 ? 'warning' : 'secondary') }}">{{ $swUtil }}% used</span>
                                @if($sw['claims'] > 0)
                                    <br><small class="text-danger">{{ $sw['claims'] }} claim(s)</small>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No supplier warranties found.</p>
                    @endforelse
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
