{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- ⭐ PRODUCT / STOCK PANEL — purchases/manage right side            --}}
{{-- Lists EVERY product added in the purchase form as an accordion row  --}}
{{-- (title = product). Each product shows its Stock-In batch history.   --}}
{{-- Each batch row has a "View" button → full-detail popup (modal).     --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
@php
    $products = $products ?? collect();
    $batchWise = (bool) config('pricing.batch_wise', false);
@endphp

<div class="card shadow-sm mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fe-shopping-bag me-1"></i> {{ __('Products / Stock In') }}
        </h6>
        <span class="badge bg-primary">{{ $products->count() }} {{ __('product(s)') }}</span>
    </div>
    <div class="card-body p-2">

        @if($products->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="fe-shopping-bag" style="font-size:32px;"></i>
                <p class="small mb-0 mt-2">{{ __('Select products in the purchase above — each product is listed here with its stock-in batches.') }}</p>
            </div>
        @else
            <div class="accordion" id="ppProductsAccordion">

                @foreach($products as $i => $product)
                    @php
                        $inBatches = $product->stockBatches->where('type', 'in')->sortByDesc('created_at')->values();
                        $outBatches = $product->stockBatches->where('type', 'out');
                        $active     = $product->stockBatches->firstWhere('is_active_for_website', true);
                        $stockLeft  = $inBatches->sum('remaining_qty');
                        $isOpen     = ($i === 0);
                    @endphp
                    <div class="accordion-item border rounded mb-1">
                        <h2 class="accordion-header" id="ppProdHead{{ $product->id }}">
                            <button class="accordion-button {{ $isOpen ? '' : 'collapsed' }} py-2" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#ppProdBody{{ $product->id }}"
                                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="ppProdBody{{ $product->id }}">
                                <span class="me-2">{{ $product->name }}</span>
                                <span class="badge bg-light text-dark border ms-1">{{ __('Left') }}: {{ $stockLeft }}</span>
                                @if($active)
                                    <span class="badge bg-success ms-1">🟢 {{ __('Active') }}</span>
                                @elseif($stockLeft > 0)
                                    <span class="badge bg-danger ms-1">🔴 {{ __('No active') }}</span>
                                @endif
                            </button>
                        </h2>
                        <div id="ppProdBody{{ $product->id }}" class="accordion-collapse collapse {{ $isOpen ? 'show' : '' }}"
                             aria-labelledby="ppProdHead{{ $product->id }}" data-bs-parent="#ppProductsAccordion">
                            <div class="accordion-body p-1">

                                <div class="small fw-bold text-muted mb-1">📥 {{ __('Stock In') }}</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" style="font-size:12px;background:#fff;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('Batch') }}</th>
                                                <th>{{ __('Ref') }}</th>
                                                <th>{{ __('Supplier') }}</th>
                                                <th>{{ __('In') }}</th>
                                                <th>{{ __('Left') }}</th>
                                                <th>{{ __('Cost') }}</th>
                                                <th>{{ __('Sell') }}</th>
                                                <th>{{ __('MRP') }}</th>
                                                <th>{{ __('Expiry') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th style="width:160px;">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($inBatches as $b)
                                            @php
                                                $isActive = $active && $active->id === $b->id;
                                                $ref = $b->purchase?->invoice_no
                                                    ?: (ucfirst(str_replace('_', ' ', $b->reference_type ?? 'adjustment')));
                                                $snIn  = is_array($b->sn_stock) ? array_values($b->sn_stock) : [];
                                                $snOut = is_array($b->sn_sold) ? array_values($b->sn_sold) : [];
                                                $bd = [
                                                    'id'          => $b->id,
                                                    'batch_no'    => $b->batch_no ?: ('#' . $b->id),
                                                    'type'        => $b->type,
                                                    'product'     => $product->name,
                                                    'supplier'    => $b->supplier?->name ?: '—',
                                                    'purchase'    => $ref,
                                                    'quantity'    => (int) $b->quantity,
                                                    'remaining'   => (int) $b->remaining_qty,
                                                    'unit_cost'   => $b->unit_cost !== null ? (float) $b->unit_cost : null,
                                                    'sell_price'  => $b->selling_price !== null ? (float) $b->selling_price : null,
                                                    'mrp'         => $b->mrp !== null ? (float) $b->mrp : null,
                                                    'mfg'         => $b->mfg_date?->format('d M, Y'),
                                                    'exp'         => $b->exp_date?->format('d M, Y'),
                                                    'pos_enabled' => (bool) $b->pos_enabled,
                                                    'is_active'   => $isActive,
                                                    'sn_in'       => $snIn,
                                                    'sn_sold'     => $snOut,
                                                    'created'     => $b->created_at?->format('d M, Y h:i A'),
                                                ];
                                            @endphp
                                            <tr class="{{ $isActive ? 'table-success' : '' }}">
                                                <td><strong style="font-size:12px;">{{ $b->batch_no ?: '#' . $b->id }}</strong></td>
                                                <td class="small">{{ $ref }}</td>
                                                <td class="small">{{ $b->supplier?->name ?: '—' }}</td>
                                                <td>{{ $b->quantity }}</td>
                                                <td><strong>{{ $b->remaining_qty }}</strong></td>
                                                <td>{{ $b->unit_cost !== null ? '৳' . number_format($b->unit_cost, 2) : '—' }}</td>
                                                <td>{{ $b->selling_price ? '৳' . number_format($b->selling_price, 2) : '—' }}</td>
                                                <td>{{ $b->mrp ? '৳' . number_format($b->mrp, 2) : '—' }}</td>
                                                <td class="small">{{ $b->exp_date?->format('d M Y') ?? '—' }}</td>
                                                <td>
                                                    @if($isActive) <span class="badge bg-success">{{ __('Active') }}</span>@endif
                                                    @if(!$b->pos_enabled) <span class="badge bg-secondary">{{ __('POS off') }}</span>@endif
                                                    @if(!$isActive)
                                                        <button type="button" class="btn btn-xs btn-success pp-activate"
                                                                data-id="{{ $b->id }}"
                                                                title="{{ __('Set as active website batch') }}">
                                                            <i class="fe-check"></i> {{ __('Set Active') }}
                                                        </button>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-outline-primary pp-view-batch"
                                                            title="{{ __('View batch details') }}"
                                                            data-batch='@json($bd)'>
                                                        <i class="fe-eye"></i> {{ __('View') }}
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center text-muted py-3">
                                                    {{ __('No stock-in batches yet — stock comes from purchases.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        @endif

    </div>
</div>

{{-- 📦 Batch Details Modal (shared by every "View" button) --}}
<div class="modal fade" id="ppBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fe-layers me-1"></i> {{ __('Batch Details') }} <small id="pp_title_batch" class="text-muted"></small></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 small">
                    <div class="col-md-6">
                        <table class="table table-sm table-bordered mb-0">
                            <tr><th class="text-muted" style="width:45%;">{{ __('Product') }}</th><td id="pp_product">—</td></tr>
                            <tr><th class="text-muted">{{ __('Supplier') }}</th><td id="pp_supplier">—</td></tr>
                            <tr><th class="text-muted">{{ __('Reference') }}</th><td id="pp_purchase">—</td></tr>
                            <tr><th class="text-muted">{{ __('Type') }}</th><td id="pp_type">—</td></tr>
                            <tr><th class="text-muted">{{ __('Quantity In') }}</th><td id="pp_qty">—</td></tr>
                            <tr><th class="text-muted">{{ __('Remaining') }}</th><td id="pp_remaining">—</td></tr>
                            <tr><th class="text-muted">{{ __('Sold') }}</th><td id="pp_sold">—</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-bordered mb-0">
                            <tr><th class="text-muted" style="width:45%;">{{ __('Unit Cost') }}</th><td id="pp_cost">—</td></tr>
                            <tr><th class="text-muted">{{ __('Selling Price') }}</th><td id="pp_sell">—</td></tr>
                            <tr><th class="text-muted">{{ __('MRP') }}</th><td id="pp_mrp">—</td></tr>
                            <tr><th class="text-muted">{{ __('Mfg Date') }}</th><td id="pp_mfg">—</td></tr>
                            <tr><th class="text-muted">{{ __('Expiry') }}</th><td id="pp_exp">—</td></tr>
                            <tr><th class="text-muted">{{ __('Status') }}</th><td id="pp_status">—</td></tr>
                            <tr><th class="text-muted">{{ __('Created') }}</th><td id="pp_created">—</td></tr>
                        </table>
                    </div>
                </div>

                <div class="mt-2 small">
                    <div class="fw-bold text-muted mb-1">{{ __('Serial Numbers (SN)') }}</div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="border rounded p-2" style="background:#f8f9fc;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ __('In Stock') }} (<span id="pp_sn_in_count">0</span>)</span>
                                    <button type="button" class="btn btn-xs btn-outline-primary pp-sn-edit" id="pp_sn_edit"><i class="fe-edit-2"></i> {{ __('Edit') }}</button>
                                </div>
                                <div id="pp_sn_view" class="mt-1 text-secondary">—</div>
                                <div id="pp_sn_editor" style="display:none;">
                                    {{-- One small box per unit — same style as the purchase-entry SN list --}}
                                    <div id="pp_sn_inputs" class="sn-inputs mt-1"></div>
                                    <div class="text-muted small mt-1">{{ __('One box per unit — count must match remaining stock.') }}</div>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-success pp-sn-save" id="pp_sn_save"><i class="fe-check me-1"></i> {{ __('Save SNs') }}</button>
                                        <button type="button" class="btn btn-sm btn-light border pp-sn-cancel" id="pp_sn_cancel">{{ __('Cancel') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2" style="background:#f8f9fc;">
                                <span class="text-muted">{{ __('Sold') }} (<span id="pp_sn_sold_count">0</span>)</span>
                                <div id="pp_sn_sold" class="mt-1 text-secondary">—</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3 small" id="pp_actions">
                    <label class="d-inline-flex align-items-center gap-1 mb-0">
                        <input type="checkbox" id="ppPosToggle" class="form-check-input m-0"> {{ __('POS enabled') }}
                    </label>
                    <button type="button" id="ppActivateBtn" class="btn btn-sm btn-success d-none"><i class="fe-check"></i> {{ __('Set Active') }}</button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Hidden trigger (Bootstrap 5 data-API) so the modal works without JS bootstrap global --}}
<button type="button" id="ppBatchModalTrigger" class="d-none" data-bs-toggle="modal" data-bs-target="#ppBatchModal"></button>

<script>
(function () {
    'use strict';

    // Set by showBatchModal() whenever the popup opens — how many SN boxes the
    // editor should build (= remaining_qty) and their current values.
    var currentBatchRemaining = 0;
    var currentBatchSnIn = [];

    // POST + reload the panel (mirrors previous panel helpers)
    function ppPost(url, data) {
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.status !== 'success') {
                // Failure — keep the popup open and surface the server message
                if (window.toastr) toastr.error(res.message || 'Error');
                return;
            }
            if (window.toastr) toastr.success(res.message || 'Saved');

            // Close via Bootstrap's standard dismiss control (removes the backdrop),
            // then refresh the product list once the modal is fully hidden.
            var doReload = function () {
                // The re-render replaces the whole panel (incl. the modal element);
                // clear any backdrop Bootstrap couldn't remove first.
                document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                window.ppReload && window.ppReload();
            };
            var modalEl = document.getElementById('ppBatchModal');
            var closeBtn = modalEl ? modalEl.querySelector('[data-bs-dismiss="modal"]') : null;
            if (modalEl && closeBtn) {
                var fired = false;
                var reloadOnce = function () { if (!fired) { fired = true; doReload(); } };
                modalEl.addEventListener('hidden.bs.modal', reloadOnce, { once: true });
                closeBtn.click();
                // Fallback: if the modal was already closed, no hidden event fires.
                setTimeout(reloadOnce, 600);
            } else {
                doReload();
            }
        })
        .catch(function () { if (window.toastr) toastr.error('Request failed'); });
    }

    // 🔢 SN — one small box per unit (same style as the purchase-entry SN list),
    //    built to match remaining_qty and pre-filled from the batch's current SNs.
    function buildPpSnInputs(count, existingValues) {
        var wrap = document.getElementById('pp_sn_inputs');
        if (!wrap) return;
        var html = '';
        for (var i = 0; i < count; i++) {
            html += '<div class="input-group input-group-sm sn-input-row" style="flex-wrap:nowrap;">' +
                        '<span class="input-group-text bg-light text-muted" style="font-size:10px;">' + (i + 1) + '</span>' +
                        '<input type="text" class="form-control form-control-sm sn-input" placeholder="SN ' + (i + 1) + '">' +
                    '</div>';
        }
        wrap.innerHTML = html;
        var inputs = wrap.querySelectorAll('.sn-input');
        for (var j = 0; j < inputs.length; j++) {
            if (existingValues[j] !== undefined) inputs[j].value = existingValues[j];
        }
    }

    // Same value typed twice for this batch is invalid — flag every box sharing
    // a duplicated value red, live as the admin types (mirrors the purchase form).
    function markPpDuplicateSn() {
        var inputs = Array.prototype.slice.call(document.querySelectorAll('#pp_sn_inputs .sn-input'));
        var counts = {};
        inputs.forEach(function (el) {
            var val = (el.value || '').trim();
            if (val) counts[val] = (counts[val] || 0) + 1;
        });
        inputs.forEach(function (el) {
            var val = (el.value || '').trim();
            el.classList.toggle('is-invalid', !!val && counts[val] > 1);
        });
    }
    var ppSnInputsWrap = document.getElementById('pp_sn_inputs');
    if (ppSnInputsWrap) {
        ppSnInputsWrap.addEventListener('input', function (e) {
            if (e.target && e.target.classList.contains('sn-input')) markPpDuplicateSn();
        });
    }

    function ppOpenSnEditor() {
        var view = document.getElementById('pp_sn_view');
        var editor = document.getElementById('pp_sn_editor');
        if (!editor) return;
        buildPpSnInputs(currentBatchRemaining, currentBatchSnIn);
        if (view) view.style.display = 'none';
        editor.style.display = '';
        var first = document.querySelector('#pp_sn_inputs .sn-input');
        if (first) first.focus();
    }

    function ppCloseSnEditor() {
        var view = document.getElementById('pp_sn_view');
        var editor = document.getElementById('pp_sn_editor');
        if (!editor) return;
        editor.style.display = 'none';
        if (view) view.style.display = '';
    }

    // Wire SN Edit / Cancel / Save (these elements exist in this freshly-rendered panel)
    var ppSnEditBtn = document.getElementById('pp_sn_edit');
    var ppSnCancelBtn = document.getElementById('pp_sn_cancel');
    var ppSnSaveBtn = document.getElementById('pp_sn_save');
    if (ppSnEditBtn) ppSnEditBtn.addEventListener('click', ppOpenSnEditor);
    if (ppSnCancelBtn) ppSnCancelBtn.addEventListener('click', ppCloseSnEditor);
    if (ppSnSaveBtn) {
        ppSnSaveBtn.addEventListener('click', function () {
            var batchId = ppSnSaveBtn.dataset.batchId;
            if (!batchId) return;
            var inputs = Array.prototype.slice.call(document.querySelectorAll('#pp_sn_inputs .sn-input'));
            markPpDuplicateSn();
            var hasDup = inputs.some(function (el) { return el.classList.contains('is-invalid'); });
            if (hasDup) {
                if (window.toastr) toastr.error('Duplicate serial number(s) — each SN must be unique.');
                return;
            }
            var vals = inputs.map(function (el) { return el.value.trim(); });
            ppPost('{{ route("purchases.price.batch.sn-save") }}', { batch_id: batchId, serials: vals });
        });
    }

    // Fill the detail modal from a batch's JSON payload + open it
    function showBatchModal(b) {
        function set(id, val, prefix) {
            var el = document.getElementById(id);
            if (!el) return;
            var text = (val === null || val === undefined || val === '') ? '—' : val;
            el.textContent = (prefix ? prefix + text : text);
        }
        set('pp_product', b.product);
        set('pp_supplier', b.supplier);
        set('pp_purchase', b.purchase);
        set('pp_type', b.type ? String(b.type).toUpperCase() : '—');
        set('pp_qty', b.quantity);
        set('pp_remaining', b.remaining);
        set('pp_sold', (b.quantity !== null && b.remaining !== null) ? Math.max(0, b.quantity - b.remaining) : '—');
        set('pp_cost', b.unit_cost !== null ? '৳' + parseFloat(b.unit_cost).toFixed(2) : '—');
        set('pp_sell', b.sell_price !== null ? '৳' + parseFloat(b.sell_price).toFixed(2) : '—');
        set('pp_mrp', b.mrp !== null ? '৳' + parseFloat(b.mrp).toFixed(2) : '—');
        set('pp_mfg', b.mfg);
        set('pp_exp', b.exp);
        set('pp_created', b.created);

        var statusBits = [];
        if (b.is_active) statusBits.push('Active website batch');
        if (!b.pos_enabled) statusBits.push('POS off');
        set('pp_status', statusBits.length ? statusBits.join(', ') : 'Normal');
        set('pp_title_batch', b.batch_no ? String(b.batch_no) : ('#' + b.id));

        // SN — in-stock SNs shown as a comma-separated list; edit as one box per unit
        var snIn = Array.isArray(b.sn_in) ? b.sn_in : [];
        var snSold = Array.isArray(b.sn_sold) ? b.sn_sold : [];
        var soldCountEl = document.getElementById('pp_sn_sold_count');
        if (soldCountEl) soldCountEl.textContent = snSold.length;
        var soldEl = document.getElementById('pp_sn_sold');
        if (soldEl) soldEl.textContent = snSold.length ? snSold.join(', ') : '—';
        var inCountEl = document.getElementById('pp_sn_in_count');
        if (inCountEl) inCountEl.textContent = snIn.length;
        var inViewEl = document.getElementById('pp_sn_view');
        if (inViewEl) inViewEl.textContent = snIn.length ? snIn.join(', ') : '—';
        var snSaveBtn = document.getElementById('pp_sn_save');
        if (snSaveBtn) snSaveBtn.dataset.batchId = b.id;
        currentBatchRemaining = parseInt(b.remaining, 10) || 0;
        currentBatchSnIn = snIn;
        ppCloseSnEditor();

        // Actions
        var activateBtn = document.getElementById('ppActivateBtn');
        var posToggle = document.getElementById('ppPosToggle');
        if (activateBtn) {
            if (b.is_active) { activateBtn.classList.add('d-none'); }
            else { activateBtn.classList.remove('d-none'); }
            activateBtn.dataset.batchId = b.id;
            activateBtn.dataset.pos = b.pos_enabled ? '1' : '0';
            activateBtn.onclick = function () {
                ppPost('{{ route("purchases.price.activate") }}', { batch_id: b.id });
            };
        }
        if (posToggle) {
            posToggle.checked = !!b.pos_enabled;
            posToggle.dataset.batchId = b.id;
            posToggle.onchange = function () {
                ppPost('{{ route("purchases.price.batch.save") }}', {
                    batch_id: b.id,
                    pos_enabled: posToggle.checked ? 1 : 0
                });
            };
        }

        // Open via the Bootstrap 5 Modal API (fall back to the hidden data-API trigger)
        var modalEl = document.getElementById('ppBatchModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            var trigger = document.getElementById('ppBatchModalTrigger');
            if (trigger) trigger.click();
        }
    }

    // Row-level "Set Active" (quick action, no popup needed)
    document.querySelectorAll('.pp-activate').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ppPost('{{ route("purchases.price.activate") }}', { batch_id: btn.getAttribute('data-id') });
        });
    });

    // Bind every "View" button present in this freshly-rendered panel.
    // (Panel is re-rendered on each load, so direct binding is safe — no duplication.)
    document.querySelectorAll('.pp-view-batch').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var raw = btn.getAttribute('data-batch');
            if (!raw) return;
            var b;
            try { b = JSON.parse(raw); } catch (err) { if (window.toastr) toastr.error('Could not load batch data'); return; }
            showBatchModal(b);
        });
    });
})();
</script>
