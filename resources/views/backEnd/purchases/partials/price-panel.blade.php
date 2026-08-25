{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- ⭐ BATCH-WISE PRICING PANEL — purchases/manage right side         --}}
{{-- 4-tab accordion: ① Batch (open by default) ② Variant ③ Wholesale ④ Warranty --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
@php
    $batchWise = (bool) config('pricing.batch_wise', false);
    $batches   = $product->stockBatches->sortByDesc('created_at')->values();
    $active    = $product->stockBatches->firstWhere('is_active_for_website', true);
    $sellable  = $product->stockBatches
        ->filter(fn ($b) => $b->pos_enabled && $b->remaining_qty > 0 && (!$b->exp_date || $b->exp_date->gte(now()->startOfDay())))
        ->sortBy('created_at')->values();
    $websiteStock = $sellable->sum('remaining_qty');
    $nextFifo  = $sellable->first(); // oldest sellable = next in FIFO line
    $variants  = $product->variantPrices;
    $hasVariants = $variants->count() > 0;
    $defaultBatchId = $active?->id ?? $batches->first()?->id;
@endphp

<div class="card shadow-sm mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fe-tag me-1"></i> {{ __('Pricing Panel') }}
        </h6>
        <span class="badge {{ $websiteStock > 0 ? 'bg-success' : 'bg-danger' }}" id="pp-website-stock">
            Website stock: {{ $websiteStock }}
        </span>
    </div>
    <div class="card-body p-2">
        {{-- Product summary --}}
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="flex-grow-1">
                <strong class="text-dark" style="font-size:13px;">{{ $product->name }}</strong>
                <div class="small text-muted">
                    @if($active)
                        <span class="badge bg-success">🟢 Active batch #{{ $active->batch_no ?: $active->id }}</span>
                    @else
                        <span class="badge bg-danger">🔴 No active batch → website Out of Stock</span>
                    @endif
                    @if($nextFifo && $nextFifo->id !== optional($active)->id)
                        <span class="badge bg-info ms-1">Next FIFO: #{{ $nextFifo->batch_no ?: $nextFifo->id }} ({{ $nextFifo->remaining_qty }})</span>
                    @endif
                </div>
            </div>
            <a href="{{ route('products.edit', $product->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="{{ __('Edit product') }}">
                <i class="fe-edit"></i>
            </a>
        </div>

        @if(!$batchWise)
            <div class="alert alert-warning small py-2 mb-0">
                <i class="fe-alert-triangle me-1"></i>
                {{ __('Batch-wise pricing is OFF. Set BATCH_WISE_PRICING=true in .env to manage per-batch prices here.') }}
            </div>
        @else
            {{-- ─────────────────────────── ACCORDION ─────────────────────────── --}}
            <div class="accordion" id="ppAccordion">

                {{-- ① BATCH TAB (open by default) --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="ppHeadBatch">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ppTabBatch" aria-expanded="true" aria-controls="ppTabBatch">
                            <i class="fe-package me-1"></i> ① {{ __('Batch') }}
                        </button>
                    </h2>
                    <div id="ppTabBatch" class="accordion-collapse collapse show" aria-labelledby="ppHeadBatch" data-bs-parent="#ppAccordion">
                        <div class="accordion-body p-2">

                            {{-- 🆕 New batches from the CURRENT purchase (filled live by JS) --}}
                            <div class="small fw-bold text-muted mb-1">🆕 {{ __('New (this purchase)') }}</div>
                            <table class="table table-sm table-bordered mb-2" style="font-size:12px;background:#fff;">
                                <thead class="table-light"><tr><th>{{ __('Batch') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Cost') }}</th><th>{{ __('Sell') }}</th><th>{{ __('MRP') }}</th></tr></thead>
                                <tbody id="pp-new-batch-rows">
                                    <tr><td colspan="5" class="text-center text-muted py-2">{{ __('Fill a product row to preview the new batch.') }}</td></tr>
                                </tbody>
                            </table>

                            {{-- Existing batches — read-only info --}}
                            <div class="small fw-bold text-muted mb-1">{{ __('Existing Batches') }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Batch') }}</th><th>{{ __('Ref') }}</th><th>{{ __('Supplier') }}</th>
                                            <th>{{ __('In') }}</th><th>{{ __('Left') }}</th><th>{{ __('Cost') }}</th>
                                            <th>{{ __('Sell') }}</th><th>{{ __('MRP') }}</th><th>{{ __('Expiry') }}</th><th>{{ __('Status') }}</th><th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($batches as $b)
                                            @php $isActive = optional($active)->id === $b->id; @endphp
                                            <tr class="{{ $isActive ? 'table-success' : '' }}">
                                                <td><strong style="font-size:12px;">{{ $b->batch_no ?: '#' . $b->id }}</strong></td>
                                                <td class="small">{{ $b->purchase?->invoice_no ?: (ucfirst(str_replace('_', ' ', $b->reference_type ?? 'adjustment'))) }}</td>
                                                <td class="small">{{ $b->supplier?->name ?: '—' }}</td>
                                                <td>{{ $b->quantity }}</td>
                                                <td><strong>{{ $b->remaining_qty }}</strong></td>
                                                <td>৳{{ number_format($b->unit_cost, 2) }}</td>
                                                <td>{{ $b->selling_price ? '৳' . number_format($b->selling_price, 2) : '—' }}</td>
                                                <td>{{ $b->mrp ? '৳' . number_format($b->mrp, 2) : '—' }}</td>
                                                <td class="small">{{ $b->exp_date?->format('d M Y') ?? '—' }}</td>
                                                <td>
                                                    @if($isActive)<span class="badge bg-success">{{ __('Active') }}</span>@endif
                                                    @if(!$b->pos_enabled)<span class="badge bg-secondary">{{ __('POS off') }}</span>@endif
                                                </td>
                                                <td>
                                                    @if(!$isActive)
                                                        <button type="button" class="btn btn-xs btn-success pp-activate" data-id="{{ $b->id }}">{{ __('Set Active') }}</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="11" class="text-center text-muted py-3">{{ __('No batches yet — stock comes from purchases.') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @if($hasVariants)
                {{-- ② VARIANT TAB --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="ppHeadVariant">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ppTabVariant" aria-expanded="false" aria-controls="ppTabVariant">
                            <i class="fe-grid me-1"></i> ② {{ __('Variant') }}
                        </button>
                    </h2>
                    <div id="ppTabVariant" class="accordion-collapse collapse" aria-labelledby="ppHeadVariant" data-bs-parent="#ppAccordion">
                        <div class="accordion-body p-2">
                            <div class="small text-muted mb-1">{{ __('Batch') }}: #{{ $batches->firstWhere('id', $defaultBatchId)?->batch_no ?: $defaultBatchId }}</div>
                            <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                                <thead class="table-light">
                                    <tr><th>{{ __('Variant') }}</th><th>{{ __('Price') }}</th><th>{{ __('MRP') }}</th><th>{{ __('Stock') }}</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($variants as $vp)
                                        @php
                                            $bvp = $product->stockBatches->firstWhere('id', $defaultBatchId)?->variantPrices->firstWhere('variant_price_id', $vp->id);
                                        @endphp
                                        <tr>
                                            <td>{{ trim(($vp->color?->getDisplayName() ?? $vp->color?->colorName ?? $vp->color?->name ?? '') . ' ' . ($vp->size?->sizeName ?? $vp->size?->name ?? '')) ?: __('No Variant') }}
                                                <div class="small text-muted">{{ $vp->sku }}</div></td>
                                            <td>৳{{ number_format($bvp->price ?? $vp->price, 2) }}</td>
                                            <td>{{ $bvp->old_price ? '৳' . number_format($bvp->old_price, 2) : '—' }}</td>
                                            <td>{{ $bvp->stock ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <small class="text-muted"><i class="fe-info"></i> {{ __('Edit variant prices in the product item row (Variant Pricing).') }}</small>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ③ WHOLESALE TAB --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="ppHeadWholesale">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ppTabWholesale" aria-expanded="false" aria-controls="ppTabWholesale">
                            <i class="fe-layers me-1"></i> ③ {{ __('Wholesale') }}
                        </button>
                    </h2>
                    <div id="ppTabWholesale" class="accordion-collapse collapse" aria-labelledby="ppHeadWholesale" data-bs-parent="#ppAccordion">
                        <div class="accordion-body p-2">
                            @php $batchWs = $product->stockBatches->firstWhere('id', $defaultBatchId)?->wholesalePrices; @endphp
                            <div class="small text-muted mb-1">{{ __('Batch') }}: #{{ $batches->firstWhere('id', $defaultBatchId)?->batch_no ?: $defaultBatchId }}</div>
                            @forelse($batchWs ?? collect() as $w)
                                <div class="border rounded p-1 mb-1 small">
                                    {{ $w->min_quantity }}{{ $w->max_quantity ? '–' . $w->max_quantity : '+' }} pcs
                                    @if($w->variant_price_id) · variant #{{ $w->variant_price_id }} @endif
                                    → <strong>৳{{ number_format($w->wholesale_price, 2) }}</strong>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">{{ __('No wholesale tiers for this batch yet.') }}</p>
                            @endforelse
                            <small class="text-muted"><i class="fe-info"></i> {{ __('Edit wholesale tiers in the product item row (Wholesale Pricing).') }}</small>
                        </div>
                    </div>
                </div>

                {{-- ④ WARRANTY TAB --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="ppHeadWarranty">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ppTabWarranty" aria-expanded="false" aria-controls="ppTabWarranty">
                            <i class="fe-shield me-1"></i> ④ {{ __('Warranty') }}
                        </button>
                    </h2>
                    <div id="ppTabWarranty" class="accordion-collapse collapse" aria-labelledby="ppHeadWarranty" data-bs-parent="#ppAccordion">
                        <div class="accordion-body p-2">
                            @php $batchWts = $product->stockBatches->firstWhere('id', $defaultBatchId)?->warrantyTiers; @endphp
                            <div class="small text-muted mb-1">{{ __('Batch') }}: #{{ $batches->firstWhere('id', $defaultBatchId)?->batch_no ?: $defaultBatchId }}</div>
                            @forelse($product->warrantyTiers as $t)
                                @php $ov = $batchWts?->firstWhere('warranty_tier_id', $t->id); @endphp
                                <div class="border rounded p-1 mb-1 small">
                                    <strong>{{ $t->tier_name }}</strong> <span class="text-muted">({{ $t->warranty_days }}d)</span>
                                    @if(($ov?->is_active ?? $t->is_active))
                                        <span class="badge bg-success ms-1">{{ __('Active') }}</span>
                                    @endif
                                    <div class="text-muted">{{ __('Override') }}: {{ $ov?->additional_cost !== null ? '৳' . number_format($ov->additional_cost, 2) : '—' }} (base ৳{{ number_format($t->additional_cost ?? 0, 2) }})</div>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">{{ __('No warranty tiers defined for this product.') }}</p>
                            @endforelse
                            <small class="text-muted"><i class="fe-info"></i> {{ __('Edit warranty pricing in the product item row (Warranty Pricing).') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
(function () {
    // ⭐ Pricing panel helpers — post JSON + reload panel
    function ppPost(url, data, cb) {
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                if (window.toastr) toastr.success(res.message || 'Saved');
                if (cb) cb(res);
                window.ppReload && ppReload();
            } else {
                if (window.toastr) toastr.error(res.message || 'Error');
            }
        })
        .catch(e => { if (window.toastr) toastr.error('Request failed'); console.error(e); });
    }

    // ⭐ The panel is INFO-only — editing happens in the purchase item rows.
    //    The only action here is Set Active (website batch).

    // Set active website batch
    document.querySelectorAll('.pp-activate').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ppPost('{{ route("purchases.price.activate") }}', { batch_id: btn.getAttribute('data-id') });
        });
    });
})();
</script>
