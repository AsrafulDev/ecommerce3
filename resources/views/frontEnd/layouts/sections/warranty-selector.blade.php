{{--
  Warranty Selector — Minimal UI for Product Detail Page
  Usage: @include('frontEnd.layouts.sections.warranty-selector', ['product' => $product])
--}}
@php
    use App\Services\WarrantyDisplayService;
    $displayService = app(WarrantyDisplayService::class);
    $tiers = $displayService->getDisplayableTiers($product);
@endphp

@if(count($tiers) > 1)
<div class="warranty-selector-section mt-3 mb-3">
    <div class="d-flex align-items-center flex-wrap gap-2">
        <small class="text-muted fw-semibold me-1">🛡️ Warranty:</small>
        @foreach($tiers as $tier)
        <label class="warranty-chip {{ $tier['is_default'] ? 'active' : '' }}" style="cursor:pointer;">
            <input type="radio" name="warranty_tier_id" value="{{ $tier['id'] }}"
                data-adjustment="{{ $tier['additional_cost'] }}"
                data-label="{{ $tier['label'] }}"
                {{ $tier['is_default'] ? 'checked' : '' }}>
            <span>{{ $tier['label'] }}</span>
            @if($tier['additional_cost'] != 0)
            <small class="warranty-chip-adj {{ $tier['additional_cost'] > 0 ? 'text-danger' : 'text-success' }}">
                {{ $tier['additional_cost'] > 0 ? '+' : '' }}{{ number_format($tier['additional_cost'], 0) }} TK
            </small>
            @endif
        </label>
        @endforeach
    </div>
</div>

<style>
.warranty-chip {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 4px 10px; border: 1px solid #ddd; border-radius: 20px;
    font-size: 13px; background: #fff; transition: all 0.15s;
    user-select: none;
}
.warranty-chip input { display: none; }
.warranty-chip:hover { border-color: #198754; }
.warranty-chip.active { border-color: #198754; background: #d4edda; }
.warranty-chip-adj { font-size: 11px; margin-left: 2px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    window._currentWarrantyAdjustment = 0;
    const chips = document.querySelectorAll('.warranty-chip input');
    function update() {
        const sel = document.querySelector('.warranty-chip input:checked');
        chips.forEach(c => c.parentElement.classList.remove('active'));
        if (sel) {
            sel.parentElement.classList.add('active');
            window._currentWarrantyAdjustment = parseFloat(sel.dataset.adjustment) || 0;
            const hidden = document.getElementById('warranty-tier-input');
            if (hidden) hidden.value = sel.value;
            if (typeof updateDisplayPrice === 'function') updateDisplayPrice();
        }
    }
    chips.forEach(c => c.addEventListener('change', update));
    update();
});
</script>
@endif
