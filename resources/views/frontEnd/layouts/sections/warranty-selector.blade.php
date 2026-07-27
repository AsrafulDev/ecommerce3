{{--
  Warranty Selector — Minimal UI for Product Detail Page
  Usage: @include('frontEnd.layouts.sections.warranty-selector', ['product' => $product])
--}}
@php
    use App\Services\WarrantyDisplayService;
    $displayService = app(WarrantyDisplayService::class);
    $tiers = $displayService->getDisplayableTiers($product);
@endphp

@if(count($tiers) > 0)
<div class="warranty-selector-section mt-3 mb-3">
    <div class="d-flex align-items-center flex-wrap gap-2">
        <small class="text-muted fw-semibold me-1">🛡️ Warranty:</small>
        @foreach($tiers as $tier)
        <label class="warranty-chip {{ $tier['is_default'] ? 'active' : '' }}"
               data-tier-id="{{ $tier['id'] }}"
               data-adjustment="{{ $tier['additional_cost'] }}"
               data-label="{{ $tier['label'] }}"
               style="cursor:pointer;">
            <input type="radio" value="{{ $tier['id'] }}"
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
(function() {
    function initWarrantySelector() {
        const chips = document.querySelectorAll('.warranty-chip[data-tier-id]');
        if (!chips.length) return;

        function selectChip(chip) {
            // Update visual state
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');

            // Check the radio inside
            const radio = chip.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            // Update global state
            const adj = parseFloat(chip.dataset.adjustment) || 0;
            window._currentWarrantyAdjustment = adj;

            // Set hidden form input
            const hidden = document.getElementById('warranty-tier-input');
            if (hidden) hidden.value = chip.dataset.tierId;

            // Update price display
            if (typeof updateDisplayPrice === 'function') updateDisplayPrice();
        }

        // Click handler on labels
        chips.forEach(chip => {
            chip.addEventListener('click', function(e) {
                e.preventDefault();
                selectChip(this);
            });
        });

        // Initialize: select the active/default chip
        const activeChip = document.querySelector('.warranty-chip.active[data-tier-id]');
        if (activeChip) selectChip(activeChip);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWarrantySelector);
    } else {
        initWarrantySelector();
    }
})();
</script>
@endif
