{{-- Warranty Tier Row Partial --}}
@php
    $idx = $idx ?? 0;
    $defaultType = $defaultType ?? ($tier->warranty_type ?? 'none');
    $days = $tier->warranty_days ?? ($defaultType === 'none' ? 0 : ($defaultType === 'supplier_warranty' ? $supplierDays : 90));
    $isGlobal = !$tier || $tier->variant_id === null;
    $adjustment = $tier->additional_cost ?? $tier->price ?? '';
    $isActive = !$tier || $tier->is_active;
@endphp
<div class="variant-card warranty-tier-row">
    <div class="row align-items-end">
        @if($hasVariants)
        <div class="col-md-2 mb-2">
            <label class="form-label small">{{ __('Variant') }}</label>
            <select name="warranty_tiers[{{ $idx }}][variant_id]" class="form-control select2 form-control-sm">
                <option value="">{{ __('All Variants') }}</option>
                @foreach($allVariants as $vp)
                    @php $vl = trim(($vp->color->colorName ?? $vp->color->name ?? '') . ' ' . ($vp->size->sizeName ?? $vp->size->name ?? '')); @endphp
                    <option value="{{ $vp->id }}" {{ (!$isGlobal && $tier && $tier->variant_id == $vp->id) ? 'selected' : '' }}>{{ $vl ?: '#' . $vp->id }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="{{ $hasVariants ? 'col-md-2' : 'col-md-3' }} mb-2">
            <label class="form-label small">{{ __('Warranty Type') }}</label>
            <select name="warranty_tiers[{{ $idx }}][warranty_type]" class="form-control form-control-sm warranty-type-select">
                <option value="none" {{ $defaultType === 'none' ? 'selected' : '' }}>No Warranty</option>
                @if($supplierWarranty)
                <option value="supplier_warranty" {{ $defaultType === 'supplier_warranty' ? 'selected' : '' }}>Supplier Warranty</option>
                @endif
                <option value="extended_warranty" {{ $defaultType === 'extended_warranty' ? 'selected' : '' }}>Extended Warranty</option>
            </select>
        </div>
        <div class="{{ $hasVariants ? 'col-md-2' : 'col-md-2' }} mb-2">
            <label class="form-label small">{{ __('Days') }}</label>
            <input type="number" name="warranty_tiers[{{ $idx }}][warranty_days]"
                class="form-control form-control-sm warranty-days-input"
                value="{{ $days }}"
                data-type="{{ $defaultType }}"
                data-supplier-days="{{ $supplierDays }}"
                {{ $defaultType === 'none' || $defaultType === 'supplier_warranty' ? 'readonly' : '' }}>
        </div>
        <div class="{{ $hasVariants ? 'col-md-2' : 'col-md-2' }} mb-2">
            <label class="form-label small">{{ __('Adjustment (± TK)') }} <i class="fe-info text-muted" data-bs-toggle="tooltip" title="+ amount = extra cost, − amount = discount"></i></label>
            <input type="number" step="0.01" name="warranty_tiers[{{ $idx }}][additional_cost]"
                class="form-control form-control-sm" value="{{ $adjustment }}" placeholder="+100 or -50">
        </div>
        <div class="{{ $hasVariants ? 'col-md-2' : 'col-md-2' }} mb-2">
            <label class="form-label small">{{ __('Active') }}</label>
            <select name="warranty_tiers[{{ $idx }}][is_active]" class="form-control form-control-sm">
                <option value="1" {{ $isActive ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$isActive ? 'selected' : '' }}>No</option>
            </select>
        </div>
        <div class="{{ $hasVariants ? 'col-md-2' : 'col-md-3' }} mb-2">
            <button type="button" class="btn btn-danger btn-sm btn-remove-warranty w-100"><i class="fa fa-trash"></i></button>
        </div>
    </div>
</div>
