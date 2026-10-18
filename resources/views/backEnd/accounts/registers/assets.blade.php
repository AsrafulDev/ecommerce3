@extends('backEnd.layouts.master')
@section('title', 'Fixed Assets')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Fixed Assets</h3>
            <p class="text-muted mb-0">Buying and selling assets moves cash. Depreciation and revaluation are recorded on the asset and move no cash.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.accounts.reports.show', 'fixed-asset-balance') }}">Asset balance</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.accounts.reports.show', 'fixed-asset-ledger') }}">Asset ledger</a>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card mb-3">
        <div class="card-header bg-white"><strong>Buy an asset</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.accounts.assets.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small mb-1">Asset *</label>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="150" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Purchase date *</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Cost *</label>
                    <input type="number" step="0.01" min="0.01" name="cost" value="{{ old('cost') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary">Record purchase</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white"><strong>Asset register</strong></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Asset</th>
                        <th>Purchased</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Depreciation</th>
                        <th class="text-end">Book value</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                    <tr>
                        <td>{{ $asset->name }}</td>
                        <td>{{ $asset->purchase_date->format('d M Y') }}</td>
                        <td class="text-end">{{ number_format((float) $asset->cost, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $asset->accumulated_depreciation, 2) }}</td>
                        <td class="text-end">{{ number_format($asset->bookValue(), 2) }}</td>
                        <td>
                            @if ($asset->status === 'disposed')
                                <span class="badge bg-secondary">Disposed {{ $asset->disposal_date->format('d M Y') }}</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($asset->status === 'active')
                            <details class="d-inline-block text-start">
                                <summary class="btn btn-sm btn-outline-primary">Depreciate / revalue / dispose</summary>
                                <form method="POST" action="{{ route('admin.accounts.assets.depreciate', $asset) }}" class="d-flex flex-wrap gap-1 mt-2">
                                    @csrf
                                    <input type="number" step="0.01" min="0.01" name="amount" placeholder="Depreciation" class="form-control form-control-sm" style="width:130px;" required>
                                    <input type="date" name="event_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required>
                                    <button class="btn btn-sm btn-outline-secondary">Depreciate</button>
                                </form>
                                <form method="POST" action="{{ route('admin.accounts.assets.revalue', $asset) }}" class="d-flex flex-wrap gap-1 mt-2">
                                    @csrf
                                    <input type="number" step="0.01" min="0" name="value" placeholder="New value" class="form-control form-control-sm" style="width:130px;" required>
                                    <input type="date" name="event_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required>
                                    <button class="btn btn-sm btn-outline-secondary">Revalue</button>
                                </form>
                                <form method="POST" action="{{ route('admin.accounts.assets.dispose', $asset) }}" class="d-flex flex-wrap gap-1 mt-2">
                                    @csrf
                                    <input type="number" step="0.01" min="0" name="value" placeholder="Sale value" class="form-control form-control-sm" style="width:130px;" required>
                                    <input type="date" name="event_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required>
                                    <input type="text" name="reason" placeholder="Reason" maxlength="255" class="form-control form-control-sm" style="width:160px;" required>
                                    <button class="btn btn-sm btn-outline-danger">Dispose</button>
                                </form>
                            </details>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No fixed assets recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
