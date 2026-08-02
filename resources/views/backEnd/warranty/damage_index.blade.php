@extends('backEnd.layouts.master')
@section('title', 'Damage Products')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">💥 Damage Products</h4>
        <div class="d-flex align-items-center gap-2">
            <form method="get" action="{{ route('admin.warranty.damage.index') }}" class="d-flex align-items-center gap-2">
                <select name="status" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\DamageStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
                @if(request('status'))
                    <a href="{{ route('admin.warranty.damage.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>
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
                            <td>৳{{ $dp->service_cost }}</td>
                            <td>৳{{ $dp->damage_cost }}</td>
                            <td><small>{{ $dp->received_at?->format('d M, Y') ?? '—' }}</small></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#damageStatusModal{{ $dp->id }}">
                                    Update Status
                                </button>
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
    @endforeach
</div>
@endsection
