@extends('backEnd.layouts.master')
@section('title', 'Damage Products')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h4 class="mb-0">💥 Damage Products</h4>
        <form method="get" action="{{ route('admin.warranty.damage.index') }}" class="d-flex align-items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" style="width:200px;" placeholder="Product, SN, claim #...">
            <select name="type" class="form-select form-select-sm" style="width:auto;">
                <option value="">All Types</option>
                @foreach(\App\Enums\DamageType::cases() as $dt)
                    <option value="{{ $dt->value }}" {{ request('type') === $dt->value ? 'selected' : '' }}>{{ $dt->label() }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                <option value="">All Statuses</option>
                @foreach(\App\Enums\DamageStatus::cases() as $st)
                    <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary"><i class="fa fa-search me-1"></i>Filter</button>
            @if(request('search') || request('type') || request('status'))
                <a href="{{ route('admin.warranty.damage.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-refresh me-1"></i>Clear</a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Damage #</th>
                            <th>Claim #</th>
                            <th>Product</th>
                            <th>Serial Number</th>
                            <th>Damage Type</th>
                            <th>Status</th>
                            <th>Supplier</th>
                            <th>Service Cost</th>
                            <th>Damage Cost</th>
                            <th>Received</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($damageProducts as $dp)
                        <tr>
                            <td><code>#{{ $dp->id }}</code></td>
                            <td>
                                @if($dp->warrantyClaim)
                                    <a href="{{ route('admin.warranty.claims.show', $dp->warrantyClaim) }}">{{ $dp->warrantyClaim->claim_number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $dp->product->name ?? 'N/A' }}</td>
                            <td><small>{{ $dp->original_serial_number ?? '—' }}</small></td>
                            <td><span class="badge bg-{{ $dp->damage_type_enum->badgeClass() }}">{{ $dp->damage_type_enum->label() }}</span></td>
                            <td><span class="badge bg-{{ $dp->status_enum->badgeClass() }}">{{ $dp->status_enum->label() }}</span></td>
                            <td><small>{{ $dp->supplier->name ?? '—' }}</small></td>
                            <td>৳{{ $dp->service_cost }}</td>
                            <td>৳{{ $dp->damage_cost }}</td>
                            <td><small>{{ $dp->received_at?->format('d M, Y') ?? '—' }}</small></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#damageLogModal{{ $dp->id }}" title="View update log">
                                        <i class="fa fa-list me-1"></i>Log
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#damageStatusModal{{ $dp->id }}">
                                        Update Status
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No damage products found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $damageProducts->links() }}
        </div>
    </div>

    {{-- ⬇️ ALL MODALS RENDERED OUTSIDE THE TABLE (Bootstrap 5 requires modals at root level) --}}
    @foreach($damageProducts as $dp)
    <div class="modal fade" id="damageStatusModal{{ $dp->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.warranty.damage.status', $dp) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5>Update Damage Product #{{ $dp->id }}</h5></div>
                <div class="modal-body">
                    <p class="text-muted mb-2">{{ $dp->product->name ?? 'N/A' }} — SN: {{ $dp->original_serial_number ?? 'N/A' }}</p>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select mb-2">
                        @foreach(\App\Enums\DamageStatus::cases() as $st)
                            <option value="{{ $st->value }}" {{ $dp->status === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                    <div class="mb-2">
                        <label class="form-label">Service Cost (→ Resellable)</label>
                        <input type="number" name="service_cost" class="form-control" value="{{ $dp->service_cost }}" step="0.01" min="0">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Damage Cost (→ Unsellable)</label>
                        <input type="number" name="damage_cost" class="form-control" value="{{ $dp->damage_cost }}" step="0.01" min="0">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Resell Price (→ Resellable)</label>
                        <input type="number" name="resell_price" class="form-control" value="{{ $dp->resell_price }}" step="0.01" min="0">
                    </div>
                    <small class="text-muted">Resellable: stock +1 back to sellable. Unsellable: write-off loss.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 📋 Damage Update Log Modal --}}
    <div class="modal fade" id="damageLogModal{{ $dp->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">📋 Damage #{{ $dp->id }} — Update Log</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2">{{ $dp->product->name ?? 'N/A' }} — SN: {{ $dp->original_serial_number ?? 'N/A' }}</p>
                    @forelse($dp->logs as $log)
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><i class="fa fa-user me-1"></i>{{ $log->user_name ?? 'System' }}</strong>
                            <small class="text-muted">{{ $log->created_at?->format('d M, Y h:i A') }}</small>
                        </div>
                        <div class="small mt-1">{{ $log->description }}</div>
                        @if($log->data)
                            <pre class="small bg-light p-2 rounded mb-0 mt-1 d-none" style="max-height:140px;overflow:auto;">{{ json_encode($log->data, JSON_PRETTY_PRINT) }}</pre>
                        @endif
                    </div>
                    @empty
                    <p class="text-muted text-center py-3 mb-0">No update log yet.</p>
                    @endforelse
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
