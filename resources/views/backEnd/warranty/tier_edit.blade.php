@extends('backEnd.layouts.master')
@section('title', 'Edit Warranty Tiers — ' . $product->name)
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h4>🛡️ Warranty Tiers: {{ $product->name }}</h4>
        <a href="{{ route('admin.warranty.tiers.index') }}" class="btn btn-sm btn-secondary">← Back</a>
    </div>

    @if($supplierWarranty)
    <div class="alert alert-info">
        Supplier Warranty: {{ $supplierWarranty->warranty_days }} days |
        Remaining: {{ $supplierWarranty->remaining_days }} days |
        Expires: {{ $supplierWarranty->warranty_end_date->format('d M, Y') }}
    </div>
    @else
    <div class="alert alert-warning">No active supplier warranty for this product.</div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><strong>Tier Configuration</strong></div>
        <div class="card-body">
            <form action="{{ route('admin.warranty.tiers.update', $product) }}" method="POST">
                @csrf

                @foreach($tiers as $tier)
                <div class="card mb-3 border-{{ $tier->is_active ? 'success' : 'secondary' }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Tier Name</label>
                                <input type="text" class="form-control" name="tiers[{{ $loop->index }}][tier_name]" value="{{ $tier->tier_name }}" required>
                                <input type="hidden" name="tiers[{{ $loop->index }}][warranty_type]" value="{{ $tier->warranty_type }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Days</label>
                                <input type="number" class="form-control" name="tiers[{{ $loop->index }}][warranty_days]" value="{{ $tier->warranty_days }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Price (TK)</label>
                                <input type="number" step="0.01" class="form-control" name="tiers[{{ $loop->index }}][price]" value="{{ $tier->price }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Sort</label>
                                <input type="number" class="form-control" name="tiers[{{ $loop->index }}][sort_order]" value="{{ $tier->sort_order }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Actions</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" name="tiers[{{ $loop->index }}][is_active]" value="1" {{ $tier->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label">Active (show on product page)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

                <button type="submit" class="btn btn-primary">💾 Save Tiers</button>
            </form>
        </div>
    </div>
</div>
@endsection
