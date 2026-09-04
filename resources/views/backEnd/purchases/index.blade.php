@extends('backEnd.layouts.master')
@section('title','Purchases')

@php
    use Illuminate\Support\Facades\Auth;
@endphp

@section('css')
<style>
    /* --- Modern Card Style --- */
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        transition: all 0.3s ease;
    }
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.35rem;
        font-weight: 700;
        color: #4e73df;
        border-radius: 10px 10px 0 0 !important;
    }
    
    /* --- Stats Widgets --- */
    .stats-card {
        display: flex;
        align-items: center;
        padding: 1.5rem;
    }
    .stats-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    .bg-soft-primary { background-color: rgba(78, 115, 223, 0.1); color: #4e73df; }
    .bg-soft-success { background-color: rgba(28, 200, 138, 0.1); color: #1cc88a; }
    .bg-soft-info    { background-color: rgba(54, 185, 204, 0.1); color: #36b9cc; }
    .bg-soft-danger  { background-color: rgba(231, 74, 59, 0.1); color: #e74a3b; }

    .stats-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem; }
    .stats-value { font-size: 1.5rem; font-weight: 700; color: #5a5c69; margin-bottom: 0; }
    .stats-sub   { font-size: 0.75rem; color: #858796; }

    /* --- Form Elements --- */
    .form-label { font-weight: 600; font-size: 0.85rem; color: #5a5c69; margin-bottom: 0.3rem; }
    .form-control, .form-select {
        border-radius: 6px;
        border: 1px solid #d1d3e2;
        padding: 0.5rem 0.75rem;
    }
    .form-control:focus { border-color: #bac8f3; box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25); }

    /* --- Table Styles --- */
    .table thead th {
        background-color: #f8f9fc;
        color: #4e73df;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        border-bottom: 2px solid #e3e6f0;
        padding: 1rem 0.75rem;
    }
    .table tbody td {
        font-size: 0.9rem;
        vertical-align: middle;
        padding: 0.75rem;
        color: #5a5c69;
    }
    .table-hover tbody tr:hover { background-color: #f8f9fc; }

    /* --- Action Buttons --- */
    .btn-action {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 6px; transition: all 0.2s;
    }
    .pay-input { width: 80px; font-size: 0.8rem; border-radius: 4px; border: 1px solid #d1d3e2; padding: 4px; }
    .pay-btn { padding: 4px 10px; font-size: 0.8rem; border-radius: 4px; font-weight: 600; }

    /* --- Serial Numbers (SN) — compact boxes, flowed side-by-side instead of
       one full-width row per unit, so a big Qty doesn't eat the whole panel --- */
    .sn-inputs { display: flex; flex-wrap: wrap; gap: 4px; }
    .sn-input-row { width: auto; margin-bottom: 0 !important; }
    .sn-input { flex: 0 0 auto; width: 12ch; min-width: 12ch; max-width: 15ch; }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">🛒 Purchase Management</h1>
        <a href="{{ route('purchases.logs') }}" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
            <i class="fe-file-text me-1"></i> View Reports / Logs
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-success">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-success"><i class="fe-calendar"></i></div>
                    <div>
                        <div class="stats-label text-success">This Year ({{ $currentYear }})</div>
                        <div class="stats-value">{{ number_format($yearlyTotal, 2) }} ৳</div>
                        <div class="stats-sub"> {{ __('Total Purchase') }} </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-info">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-info"><i class="fe-bar-chart-2"></i></div>
                    <div>
                        <div class="stats-label text-info">
                            {{ \Carbon\Carbon::createFromDate(now()->year, $currentMonth, 1)->format('F') }}
                        </div>
                        <div class="stats-value">{{ number_format($monthlyTotal, 2) }} ৳</div>
                        <div class="stats-sub"> {{ __('Monthly Purchase') }} </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-primary">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-primary"><i class="fe-shopping-bag"></i></div>
                    <div>
                        <div class="stats-label text-primary">Today ({{ now()->format('d M') }})</div>
                        <div class="stats-value">{{ number_format($todayTotal, 2) }} ৳</div>
                        <div class="stats-sub"> {{ __('Daily Purchase') }} </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2 border-start border-4 border-danger">
                <div class="card-body stats-card">
                    <div class="stats-icon bg-soft-danger"><i class="fe-alert-circle"></i></div>
                    <div>
                        <div class="stats-label text-danger"> {{ __('Supplier Due') }} </div>
                        <div class="stats-value">{{ number_format($totalDue, 2) }} ৳</div>
                        <div class="stats-sub"> {{ __('Total Liability') }} </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary" id="purchase-form-heading"><i class="fe-plus-circle me-1"></i> {{ __('New Purchase Entry') }} </h6>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <i class="fe-alert-triangle me-1"></i> <strong>{{ __('Could not save purchase:') }}</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form id="purchase-entry-form" action="{{ route('purchases.store') }}" method="POST" novalidate>
                        @csrf
                        <input type="hidden" name="draft_id" id="purchase-draft-id" value="{{ $draftToResume?->id }}">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"> {{ __('Supplier *') }} </label>
                                <select name="supplier_id" class="form-control form-select" required>
                                    <option value="">-- Select Supplier --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->phone }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"> {{ __('Invoice No *') }} </label>
                                <input type="text" name="invoice_no" class="form-control" value="{{ 'PUR-'.time() }}" required style="background-color: #f8f9fc;">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"> {{ __('Date *') }} </label>
                                <input type="date" name="purchase_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <hr class="sidebar-divider my-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-xs font-weight-bold text-uppercase text-gray-500 mb-0">{{ __('Product Details') }}</h6>
                            <div class="d-flex gap-2">
                                <div class="input-group input-group-sm" style="width:220px;">
                                    <span class="input-group-text bg-white"><i class="fas fa-barcode"></i></span>
                                    <input type="text" id="barcode-scan" class="form-control form-control-sm" placeholder="Scan barcode to add..." autofocus>
                                </div>
                                <button type="button" id="add-product-row" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus"></i> Add Row</button>
                            </div>
                        </div>

                        <div id="product-rows">
                            <div class="product-row border rounded p-3 mb-2 bg-light">
                                <div class="row align-items-end">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label small">{{ __('Product *') }}</label>
                                        <select name="items[0][product_id]" class="form-control form-select form-select-sm product-select" required>
                                            <option value="">-- Select --</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" data-barcode="{{ $p->barcode ?? '' }}" data-has-variants="{{ $p->variantPrices->count() > 0 ? '1' : '0' }}">{{ $p->name }} (Stock: {{ $p->stock }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2 variant-col" style="display:none;">
                                        <label class="form-label small">{{ __('Variant') }} <small>(optional)</small></label>
                                        <select name="items[0][variant_id]" class="form-control form-select form-select-sm variant-select">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">{{ __('Qty *') }}</label>
                                        <input type="number" name="items[0][qty]" class="form-control form-control-sm" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Unit Cost (৳) *</label>
                                        <input type="number" step="0.01" name="items[0][unit_cost]" class="form-control form-control-sm" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Total</label>
                                        <span class="form-control form-control-sm bg-light text-center row-total">0</span>
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <button type="button" class="btn btn-danger btn-sm btn-remove-row w-100" title="Remove"><i class="fa fa-times"></i></button>
                                    </div>
                                </div>
                                {{-- Batch info per product --}}
                                <div class="row mt-1">
                                    <div class="col-md-2">
                                        <label class="form-label small">{{ __('Batch No') }}</label>
                                        <input type="text" name="items[0][batch_no]" class="form-control form-control-sm" placeholder="e.g. BT-001">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">{{ __('MFG Date') }}</label>
                                        <input type="date" name="items[0][mfg_date]" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">{{ __('EXP Date') }}</label>
                                        <input type="date" name="items[0][exp_date]" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">{{ __('Custom Field') }}</label>
                                        <input type="text" name="items[0][custom_field]" class="form-control form-control-sm" placeholder="Extra info">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">{{ __('Selling Price') }} <small class="text-muted">(৳)</small></label>
                                        <input type="number" step="0.01" name="items[0][selling_price]" class="form-control form-control-sm" placeholder="0.00">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">{{ __('MRP') }} <small class="text-muted">(৳)</small></label>
                                        <input type="number" step="0.01" name="items[0][mrp]" class="form-control form-control-sm" placeholder="0.00">
                                    </div>
                                </div>
                                {{-- ⭐ Batch-wise extras: website activation --}}
                                <div class="row mt-1">
                                    <div class="col-auto">
                                        <label class="form-check-label small">
                                            <input type="checkbox" name="items[0][activate_website]" value="1" class="form-check-input pp-activate-cb me-1">
                                            {{ __('Set as website active batch') }}
                                        </label>
                                    </div>
                                </div>
                                {{-- Warranty per product --}}
                                <div class="row mt-1">
                                    <div class="col-md-3">
                                        <label class="form-label small">{{ __('Warranty Days') }}</label>
                                        <input type="number" name="items[0][warranty_days]" class="form-control form-control-sm" value="0" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">{{ __('Warranty Start') }}</label>
                                        <input type="date" name="items[0][warranty_start]" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">{{ __('Terms') }}</label>
                                        <input type="text" name="items[0][warranty_terms]" class="form-control form-control-sm" placeholder="Optional">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">{{ __('Transferable') }}</label>
                                        <select name="items[0][transferable]" class="form-control form-select form-select-sm">
                                            <option value="1">Yes</option><option value="0">No</option>
                                        </select>
                                    </div>
                                </div>
                                {{-- ⭐ Batch-wise pricing expanders (after supplier warranty info) --}}
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-warning toggle-wholesale-pricing"><i class="fe-layers"></i> {{ __('Wholesale Pricing') }}</button>
                                            <button type="button" class="btn btn-outline-primary toggle-warranty-pricing"><i class="fe-shield"></i> {{ __('Warranty Pricing') }}</button>
                                            <button type="button" class="btn btn-outline-dark toggle-sn-list"><i class="fe-hash"></i> {{ __('SN List') }}</button>
                                        </div>
                                    </div>
                                </div>
                                {{-- Wholesale pricing (dynamic tier rows, filled by JS) --}}
                                <div class="wholesale-pricing-block mt-1" style="display:none;">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-1" style="background:#fff;">
                                            <thead class="table-light"><tr><th>{{ __('Min') }}</th><th>{{ __('Max') }}</th><th style="width:120px;">{{ __('Wholesale Discount') }}</th><th style="width:40px;"></th></tr></thead>
                                            <tbody class="wholesale-pricing-rows"></tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-secondary add-wholesale-pricing-row"><i class="fa fa-plus"></i> {{ __('Add Tier') }}</button>
                                </div>
                                {{-- Warranty pricing (per product warranty tier, filled by JS) --}}
                                <div class="warranty-pricing-block mt-1" style="display:none;">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0" style="background:#fff;">
                                            <thead class="table-light"><tr><th>{{ __('Type') }}</th><th>{{ __('Name') }}</th><th>{{ __('Days') }}</th><th>{{ __('Cost') }}</th><th>{{ __('Active') }}</th><th style="width:40px;"></th></tr></thead>
                                            <tbody class="warranty-pricing-rows"></tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-secondary add-warranty-pricing-row"><i class="fa fa-plus"></i> {{ __('Add Warranty Option') }}</button>
                                    <small class="text-muted d-block mt-1"><i class="fe-info"></i> {{ __('Create warranty options directly here (No Warranty / Supplier / Extended) — no need to pre-create on the product page.') }}</small>
                                </div>
                                {{-- 🔢 Serial Numbers (SN) — one field per Qty at create; read-only list at edit --}}
                                <div class="sn-block mt-1" style="display:none;">
                                    <div class="small fw-bold text-muted mb-1">🔢 {{ __('Serial Numbers (SN)') }}
                                        <small class="text-secondary sn-block-note">({{ __('one per unit') }} — {{ __('auto from Qty') }})</small>
                                    </div>
                                    <div class="sn-inputs"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-3 mb-2">
                                <label class="form-label">{{ __('Discount') }}</label>
                                <input type="number" step="0.01" name="discount" class="form-control form-control-sm" value="0" id="global-discount">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">{{ __('Shipping Cost') }}</label>
                                <input type="number" step="0.01" name="shipping_cost" class="form-control form-control-sm" value="0" id="global-shipping">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label text-success fw-bold">{{ __('Paid Amount') }}</label>
                                <input type="number" step="0.01" name="paid_amount" class="form-control form-control-sm border-success" value="0" id="global-paid">
                                <small class="text-muted" style="font-size:10px;">Deducted from fund</small>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">{{ __('Note') }}</label>
                                <input type="text" name="note" class="form-control form-control-sm" placeholder="Optional">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded mt-2">
                            <div>
                                <strong>Grand Total: <span id="grand-total-display" class="text-primary">0.00</span> ৳</strong>
                                <small id="purchase-draft-status" class="d-block text-muted"></small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" id="save-purchase-draft" class="btn btn-outline-secondary px-3"><i class="fe-file-text me-1"></i> Save Draft</button>
                                <button type="submit" id="publish-submit-btn" class="btn btn-primary px-4"><i class="fe-save me-1"></i> Publish Purchase</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ⭐ BATCH-WISE PRICING PANEL (right) — ① Batch ② Variant ③ Wholesale ④ Warranty --}}
        <div class="col-lg-4 mb-4">
            <div id="price-panel-container">
                <div class="card shadow-sm">
                    <div class="card-header py-2 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="fe-shopping-bag me-1"></i> {{ __('Products / Stock In') }}</h6>
                    </div>
                    <div class="card-body text-center text-muted py-5">
                        <i class="fe-shopping-bag" style="font-size: 32px;"></i>
                        <p class="small mb-0 mt-2">Select products in the purchase above to see their stock-in batches.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Export moved below form, before history --}}
        <div class="col-lg-12 mb-4">
            <div class="card">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fe-download me-1"></i> {{ __('Export Report') }} </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('purchases.export') }}" method="GET" target="_blank" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('Month') }}</label>
                            <input type="number" name="month" class="form-control form-control-sm" placeholder="1-12" value="{{ request('month') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('Year') }}</label>
                            <input type="number" name="year" class="form-control form-control-sm" placeholder="2026" value="{{ request('year') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">{{ __('From') }}</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">{{ __('To') }}</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-dark btn-sm w-100"><i class="fe-download-cloud me-1"></i> CSV</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fe-list me-1"></i> {{ __('Recent Purchase History') }} </h6>
        </div>
        <div class="card-body py-2 border-bottom bg-white">
            <form method="GET" action="{{ route('purchases.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">{{ __('Search') }}</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="{{ __('Invoice # / supplier / phone') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">{{ __('From Date') }}</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">{{ __('To Date') }}</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-grow-1"><i class="fa fa-search"></i> {{ __('Filter') }}</button>
                    <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-light border"><i class="fa fa-refresh"></i> {{ __('Reset') }}</a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div id="purchase-table-wrapper" class="table-responsive">
                <table class="table table-bordered table-hover mb-0" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" width="5%">#</th>
                            <th width="12%">{{ __('Date') }}</th>
                            <th width="15%">{{ __('Invoice') }}</th>
                            <th width="15%">{{ __('Supplier') }}</th>
                            <th class="text-end" width="10%">{{ __('Total') }}</th>
                            <th class="text-end" width="10%"> {{ __('Paid') }} </th>
                            <th class="text-end" width="10%">Due</th>
                            <th class="text-center" width="23%"> {{ __('Actions') }} </th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($purchases as $p)
                        @php
                            $user = Auth::guard('admin')->user();
                            $isAdmin = false;
                            if ($user) {
                                if ($user->id == 1) {
                                    $isAdmin = true;
                                } else {
                                    $spatieRoles = $user->getRoleNames()->map(function($role) {
                                        return strtolower($role);
                                    })->toArray();
                                    $isAdmin = in_array('admin', $spatieRoles);
                                }
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration + ($purchases->currentPage()-1)*$purchases->perPage() }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($p->purchase_date)->format('d M, Y') }}
                            </td>
                            <td>
                                <span class="fw-bold text-dark">{{ $p->invoice_no }}</span>
                                @if((int) $p->status === 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ __('Draft') }}</span>
                                @endif
                                @if($p->updated_by)
                                    <i class="fe-edit-2 text-warning ms-1" title="Edited" style="font-size: 10px;"></i>
                                @endif
                            </td>
                            <td>
                                @if($p->supplier)
                                    <div class="fw-bold text-secondary">{{ $p->supplier->name }}</div>
                                    <small class="text-muted">{{ $p->supplier->phone }}</small>
                                @else
                                    <span class="text-danger"> {{ __('Deleted') }} </span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">{{ number_format($p->grand_total,2) }}</td>
                            <td class="text-end text-success">{{ number_format($p->paid_amount,2) }}</td>
                            <td class="text-end text-danger">{{ number_format($p->due_amount,2) }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    @if((int) $p->status === 0)
                                        <a href="{{ route('purchases.index', ['draft' => $p->id]) }}" class="btn btn-action btn-outline-primary" title="{{ __('Resume Draft') }}">
                                            <i class="fe-edit-3"></i>
                                        </a>
                                        <form method="POST" action="{{ route('purchases.drafts.destroy', $p->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-action btn-outline-danger" title="{{ __('Delete Draft') }}" onclick="return confirm('{{ __('Delete this draft?') }}');">
                                                <i class="fe-trash-2"></i>
                                            </button>
                                        </form>
                                    @else
                                    <a href="{{ route('purchases.invoice',$p->id) }}" class="btn btn-action btn-outline-secondary" target="_blank" title="{{ __('Invoice') }}">
                                        <i class="fe-file-text"></i>
                                    </a>
                                    <a href="{{ route('purchases.invoice',$p->id) }}" class="btn btn-action btn-outline-primary" title="{{ __('Print') }}">
                                        <i class="fe-printer"></i>
                                    </a>

                                    @if($p->due_amount > 0)
                                        <form action="{{ route('purchases.pay_due',$p->id) }}" method="POST" class="d-flex align-items-center bg-light rounded p-1 border">
                                            @csrf
                                            <input type="number" step="0.01" name="amount" class="pay-input me-1" placeholder="Pay Due" required>
                                            <input type="hidden" name="payment_date" value="{{ now()->format('Y-m-d') }}">
                                            <button class="btn btn-success pay-btn text-white" title="Pay Now">
                                                <i class="fe-check"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-success bg-soft-success text-success border border-success px-2"> {{ __('Paid') }} </span>
                                    @endif

                                    @if($isAdmin)
                                        <a href="#" class="btn btn-action btn-outline-primary purchase-edit-btn" data-url="{{ route('purchases.edit.data', $p->id) }}" title="{{ __('Edit') }}">
                                            <i class="fe-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('purchases.destroy', $p->id) }}" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-action btn-outline-danger delete-confirm ms-1" title="{{ __('Delete') }}" onclick="return confirm('Confirm delete? This affects stock & fund.');">
                                                <i class="fe-trash-2"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" width="60" class="mb-3 opacity-50">
                                <p class="text-muted mb-0"> {{ __('No purchase records found.') }} </p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                
                <div id="purchase-pagination" class="p-3 d-flex justify-content-end">
                    {{ $purchases->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // AJAX Pagination Script
    $(document).on('click', '#purchase-pagination a', function(e){
        e.preventDefault();
        let url = $(this).attr('href');
        // Add loading state opacity
        $('#purchase-table-wrapper').css('opacity', '0.5');
        
        $.get(url, function(response){
            let html = $(response).find('#purchase-table-wrapper').html();
            $('#purchase-table-wrapper').html(html);
            $('#purchase-table-wrapper').css('opacity', '1');
        });
    });

    // 🛒 MULTI-PRODUCT ROW MANAGEMENT
    let rowIndex = 1;
    const allProducts = {!! $productsJson !!};
    const allVariants = {!! $variantsJson !!};
    // ⭐ Batch-wise pricing engine — per-product warranty tiers (for the Warranty Pricing expander)
    const allWarrantyTiers = {!! $warrantyTiersJson !!};
    const serverDraft = @json($draftToResume?->draft_data);
    let draftSaveTimer;
    let restoringDraft = false;

    function savePurchaseDraft(showStatus) {
        const fields = {};
        $('#purchase-entry-form').serializeArray().forEach(function(field) {
            if (field.name === '_token' || field.name === 'draft_id') return;
            // serial_numbers are repeatable array inputs sharing ONE name
            // ("items[i][serial_numbers][]") — collect them into an array under a
            // single key instead of letting each value overwrite the last one.
            const snMatch = field.name.match(/^(items\[\d+\]\[serial_numbers\])\[\]$/);
            if (snMatch) {
                const key = snMatch[1];
                (fields[key] = fields[key] || []).push(field.value);
                return;
            }
            fields[field.name] = field.value;
        });
        $.post('{{ route("purchases.drafts.save") }}', {
            _token: '{{ csrf_token() }}',
            draft_id: $('#purchase-draft-id').val(),
            payload: JSON.stringify(fields)
        }, function(response) {
            if (response.status !== 'success') return;
            $('#purchase-draft-id').val(response.draft_id);
            if (showStatus) $('#purchase-draft-status').text('Draft saved at ' + new Date().toLocaleTimeString());
        }).fail(function() {
            if (showStatus && window.toastr) toastr.error('Draft could not be saved.');
        });
    }

    function restorePurchaseDraft() {
        const draft = serverDraft;
        if (!draft) return;
        restoringDraft = true;
        const itemIndexes = Object.keys(draft)
            .map(function(name) {
                const match = name.match(/^items\[(\d+)\]/);
                return match ? Number(match[1]) : null;
            })
            .filter(function(index) { return index !== null; });
        const rowCount = itemIndexes.length ? Math.max.apply(null, itemIndexes) + 1 : 1;
        while ($('#product-rows .product-row').length < rowCount) {
            addProductRow();
        }
        Object.keys(draft).forEach(function(name) {
            const $field = $('#purchase-entry-form').find('[name="' + name.replace(/"/g, '\\"') + '"]');
            if ($field.is(':checkbox')) {
                $field.prop('checked', String($field.val()) === String(draft[name]));
            } else {
                $field.val(draft[name]);
            }
        });
        $('#product-rows .product-select').each(function() {
            if ($(this).val()) $(this).trigger('change');
        });
        Object.keys(draft).forEach(function(name) {
            const $field = $('#purchase-entry-form').find('[name="' + name.replace(/"/g, '\\"') + '"]');
            if ($field.is(':checkbox')) {
                $field.prop('checked', String($field.val()) === String(draft[name]));
            } else {
                $field.val(draft[name]);
            }
        });
        // 🔢 Serial numbers were saved as an array under "items[i][serial_numbers]"
        //    (see savePurchaseDraft) — the actual SN inputs don't exist until the SN
        //    panel is built for the row's qty, so open it and fill them in here.
        Object.keys(draft).forEach(function(name) {
            const m = name.match(/^items\[(\d+)\]\[serial_numbers\]$/);
            if (!m) return;
            const serials = Array.isArray(draft[name]) ? draft[name] : [];
            if (!serials.length) return;
            const row = $('#product-rows .product-row').eq(Number(m[1]));
            if (!row.length) return;
            row.find('.sn-block').show();
            setSnToggleState(row.find('.toggle-sn-list')[0], true);
            buildSnFields(row);
            row.find('.sn-inputs .sn-input').each(function(i) {
                if (serials[i] !== undefined) this.value = serials[i];
            });
        });

        calcGrandTotal();
        $('#purchase-draft-status').text('Draft loaded. Continue editing or publish when ready.');
        restoringDraft = false;
    }

    function calcGrandTotal() {
        let total = 0;
        $('#product-rows .product-row').each(function() {
            const qty = parseFloat($(this).find('[name*="[qty]"]').val()) || 0;
            const cost = parseFloat($(this).find('[name*="[unit_cost]"]').val()) || 0;
            const rowTotal = qty * cost;
            $(this).find('.row-total').text(rowTotal.toFixed(2));
            total += rowTotal;
        });
        const discount = parseFloat($('#global-discount').val()) || 0;
        const shipping = parseFloat($('#global-shipping').val()) || 0;
        $('#grand-total-display').text((total - discount + shipping).toFixed(2));
    }

    function reindexRows() {
        $('#product-rows .product-row').each(function(i) {
            $(this).find('[name]').each(function() {
                const name = $(this).attr('name');
                if (name) $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + i + ']'));
            });
        });
        rowIndex = $('#product-rows .product-row').length;
    }

    function loadVariants(select, productId) {
        const row = select.closest('.product-row');
        const variantCol = row.find('.variant-col');
        const variantSelect = row.find('.variant-select');
        variantSelect.html('<option value="">All</option>');
        if (allVariants[productId]) {
            variantCol.show();
            allVariants[productId].forEach(v => {
                const label = [v.color?.colorName || v.color?.name, v.size?.sizeName || v.size?.name].filter(Boolean).join(' / ') || 'Variant #' + v.id;
                variantSelect.append('<option value="' + v.id + '">' + label + '</option>');
            });
        } else {
            variantCol.hide();
        }
    }

    function addProductRow() {
        const template = $('#product-rows .product-row').first().clone();
        template.find('input').val('');
        template.find('input[name*="qty"]').val('1');
        template.find('input[name*="[warranty_days]"]').val('0');
        template.find('.row-total').text('0');
        template.find('.variant-col').hide();
        template.find('.wholesale-pricing-block, .warranty-pricing-block').hide();
        template.find('.wholesale-pricing-rows, .warranty-pricing-rows').html('');
        template.find('.sn-block').hide();
        template.find('.sn-inputs').html('');
        template.removeAttr('data-sn-qty');
        template.removeAttr('data-batch-serials');
        template.find('.toggle-sn-list').removeClass('btn-dark active').addClass('btn-outline-dark');
        template.find('.pp-activate-cb').prop('checked', false);
        template.find('[name]').each(function() {
            $(this).attr('name', $(this).attr('name').replace(/items\[\d+\]/, 'items[' + rowIndex + ']'));
        });
        $('#product-rows').append(template);
        rowIndex++;
        calcGrandTotal();
    }

    // ⭐ Build a blank wholesale tier row for a product row
    function wholesaleRowHtml(row, rowIdx, i) {
        const selectedVariantId = row.find('.variant-select').val() || '';
        return '<tr>' +
            '<input type="hidden" name="items[' + rowIdx + '][wholesale_tiers][' + i + '][variant_id]" value="' + selectedVariantId + '">' +
            '<td><input type="number" min="1" name="items[' + rowIdx + '][wholesale_tiers][' + i + '][min_quantity]" class="form-control form-control-sm" placeholder="Min"></td>' +
            '<td><input type="number" min="1" name="items[' + rowIdx + '][wholesale_tiers][' + i + '][max_quantity]" class="form-control form-control-sm" placeholder="Max"></td>' +
            '<td><input type="number" step="0.01" name="items[' + rowIdx + '][wholesale_tiers][' + i + '][wholesale_price]" class="form-control form-control-sm" placeholder="0.00"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger ws-remove-row" title="Remove">×</button></td>' +
        '</tr>';
    }

    // ⭐ Ensure at least one wholesale tier row exists for the selected product
    function populateWholesalePricing(row, productId) {
        const tbody = row.find('.wholesale-pricing-rows');
        if (!productId) { tbody.html(''); return; }
        const m = row.find('.product-select').attr('name').match(/items\[(\d+)\]/);
        const rowIdx = m ? m[1] : 0;
        if (!tbody.children().length) {
            tbody.append(wholesaleRowHtml(row, rowIdx, 0));
        }
    }

    // ⭐ Populate the Warranty Pricing table from the product's existing warranty tiers
    function populateWarrantyPricing(row, productId) {
        const tbody = row.find('.warranty-pricing-rows');
        tbody.html('');
        const tiers = allWarrantyTiers[productId] || [];
        if (!tiers.length) { return; }
        const m = row.find('.product-select').attr('name').match(/items\[(\d+)\]/);
        const rowIdx = m ? m[1] : 0;
        tiers.forEach((t, i) => {
            tbody.append(
                '<tr>' +
                    '<td><span class="badge bg-soft-info text-info small">' + (t.warranty_type || 'tier') + '</span>' +
                        '<input type="hidden" name="items[' + rowIdx + '][warranty_tiers][' + i + '][tier_id]" value="' + t.id + '"></td>' +
                    '<td><span class="small">' + t.tier_name + '</span></td>' +
                    '<td><span class="small">' + t.warranty_days + 'd</span></td>' +
                    '<td><input type="number" min="0" step="0.01" name="items[' + rowIdx + '][warranty_tiers][' + i + '][additional_cost]" class="form-control form-control-sm" placeholder="Override"></td>' +
                    '<td class="text-center">' +
                        '<input type="hidden" name="items[' + rowIdx + '][warranty_tiers][' + i + '][is_active]" value="0">' +
                        '<input type="checkbox" name="items[' + rowIdx + '][warranty_tiers][' + i + '][is_active]" value="1" class="form-check-input" checked>' +
                    '</td>' +
                    '<td class="text-center"></td>' +
                '</tr>'
            );
        });
    }

    // ⭐ Build a NEW warranty option row (Add Warranty Option) — create by 3 types
    //    (No Warranty / Supplier Warranty / Extended Warranty) right from the purchase.
    function warrantyRowHtml(row, rowIdx, i) {
        return '<tr>' +
            '<td>' +
                '<select name="items[' + rowIdx + '][warranty_tiers][' + i + '][warranty_type]" class="form-select form-select-sm warranty-type-select" data-i="' + i + '">' +
                    '<option value="none">No Warranty</option>' +
                    '<option value="supplier_warranty" selected>Supplier Warranty</option>' +
                    '<option value="extended_warranty">Extended Warranty</option>' +
                '</select>' +
                '<input type="hidden" name="items[' + rowIdx + '][warranty_tiers][' + i + '][tier_id]" value="">' +
            '</td>' +
            '<td><input type="text" name="items[' + rowIdx + '][warranty_tiers][' + i + '][tier_name]" class="form-control form-control-sm warranty-tier-name" placeholder="Auto from type"></td>' +
            '<td><input type="number" min="0" name="items[' + rowIdx + '][warranty_tiers][' + i + '][warranty_days]" class="form-control form-control-sm warranty-days-input" value="90"></td>' +
            '<td><input type="number" min="0" step="0.01" name="items[' + rowIdx + '][warranty_tiers][' + i + '][additional_cost]" class="form-control form-control-sm" placeholder="0.00"></td>' +
            '<td class="text-center">' +
                '<input type="hidden" name="items[' + rowIdx + '][warranty_tiers][' + i + '][is_active]" value="0">' +
                '<input type="checkbox" name="items[' + rowIdx + '][warranty_tiers][' + i + '][is_active]" value="1" class="form-check-input" checked>' +
            '</td>' +
            '<td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger wr-remove-row" title="Remove">×</button></td>' +
        '</tr>';
    }

    $('#add-product-row').click(addProductRow);

    $('#product-rows').on('click', '.btn-remove-row', function() {
        if ($('#product-rows .product-row').length > 1) {
            $(this).closest('.product-row').remove();
            reindexRows();
            calcGrandTotal();
            ppLoadPanel();
        }
    });

    $('#product-rows').on('change', '.product-select', function() {
        const pid = $(this).val();
        const row = $(this).closest('.product-row');
        loadVariants($(this), pid);
        populateWholesalePricing(row, pid);
        populateWarrantyPricing(row, pid);
        buildSnFields(row);
        ppLoadPanel();
    });

    // ⭐ 2 pricing expanders (Wholesale / Warranty) — after supplier warranty info
    $('#product-rows').on('click', '.toggle-wholesale-pricing', function() {
        $(this).closest('.product-row').find('.wholesale-pricing-block').toggle();
    });
    $('#product-rows').on('click', '.toggle-warranty-pricing', function() {
        $(this).closest('.product-row').find('.warranty-pricing-block').toggle();
    });

    // Add / remove wholesale tier rows
    $('#product-rows').on('click', '.add-wholesale-pricing-row', function() {
        const row = $(this).closest('.product-row');
        const tbody = row.find('.wholesale-pricing-rows');
        const m = row.find('.product-select').attr('name').match(/items\[(\d+)\]/);
        const rowIdx = m ? m[1] : 0;
        tbody.append(wholesaleRowHtml(row, rowIdx, tbody.children().length));
    });
    $('#product-rows').on('click', '.ws-remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Add / remove warranty tier rows
    $('#product-rows').on('click', '.add-warranty-pricing-row', function() {
        const row = $(this).closest('.product-row');
        const tbody = row.find('.warranty-pricing-rows');
        const m = row.find('.product-select').attr('name').match(/items\[(\d+)\]/);
        const rowIdx = m ? m[1] : 0;
        tbody.append(warrantyRowHtml(row, rowIdx, tbody.children().length));
    });
    $('#product-rows').on('click', '.wr-remove-row', function() {
        $(this).closest('tr').remove();
    });

    // 🔢 SN LIST — auto-create one serial-number field per unit of Qty.
    // No manual add/remove: fields follow the Qty automatically.
    function buildSnFields(row) {
        if (editingPurchaseId) return; // SNs are entered at create time — edit a batch's SNs via its View popup
        const m = row.find('.product-select').attr('name')?.match(/items\[(\d+)\]/);
        const idx = m ? m[1] : 0;
        const qty = Math.max(0, parseInt(row.find('[name="items[' + idx + '][qty]"]').val(), 10) || 0);
        const wrap = row.find('.sn-inputs');
        const current = wrap.find('.sn-input').map(function () { return this.value; }).get();
        const lastQty = parseInt(row.attr('data-sn-qty') || '-1', 10);
        if (lastQty === qty) return; // unchanged — keep typed values & focus
        row.attr('data-sn-qty', qty);
        let html = '';
        for (let i = 0; i < qty; i++) {
            html += '<div class="input-group input-group-sm mb-1 sn-input-row" style="flex-wrap:nowrap;">' +
                        '<span class="input-group-text bg-light text-muted" style="font-size:10px;">' + (i + 1) + '</span>' +
                        '<input type="text" name="items[' + idx + '][serial_numbers][]" class="form-control form-control-sm sn-input" placeholder="SN ' + (i + 1) + '">' +
                    '</div>';
        }
        wrap.html(html);
        // Restore any already-typed serial numbers by index
        wrap.find('.sn-input').each(function (i) {
            if (current[i] !== undefined) this.value = current[i];
        });
    }

    // Visual state so the button reflects open/closed
    function setSnToggleState(btn, visible) {
        if (!btn) return;
        btn.classList.toggle('active', visible);
        btn.classList.toggle('btn-dark', visible);
        btn.classList.toggle('btn-outline-dark', !visible);
    }

    // Edit mode: show the batch serials as a read-only list (NO entry fields).
    // Serials for a published purchase are edited from the batch View popup on the right.
    function renderEditModeSnList(row) {
        let serials = [];
        try { serials = JSON.parse(row.attr('data-batch-serials') || '[]'); } catch (e) { serials = []; }
        if (!Array.isArray(serials)) serials = [];
        row.find('.sn-block-note').text('(batch serials — read only)');
        let html;
        if (serials.length) {
            html = '<div class="sn-list-view">' + serials.map(function (s) {
                return '<span class="badge bg-light text-dark border me-1 mb-1">' + s + '</span>';
            }).join('') + '</div>';
        } else {
            html = '<div class="text-muted fst-italic small">No serial numbers saved on this batch yet.</div>';
        }
        html += '<div class="small text-muted mt-1"><i class="fe-info"></i> Edit serial numbers from the batch View popup on the right side.</div>';
        row.find('.sn-inputs').html(html);
    }

    // Toggle the SN block; build entry fields (create) or a read-only list (edit) when opened
    $('#product-rows').on('click', '.toggle-sn-list', function () {
        const row = $(this).closest('.product-row');
        const block = row.find('.sn-block');
        block.toggle();
        if (block.is(':visible')) {
            if (editingPurchaseId) {
                renderEditModeSnList(row);
            } else {
                buildSnFields(row);
            }
        }
        setSnToggleState(this, block.is(':visible'));
    });

    // Keep SN fields in sync with Qty as it changes
    $('#product-rows').on('input change', 'input[name*="[qty]"]', function () {
        buildSnFields($(this).closest('.product-row'));
    });

    // 🔢 Warranty → SN required helpers
    function purchaseRowIdx(row) {
        const m = row.find('.product-select').attr('name')?.match(/items\[(\d+)\]/);
        return m ? m[1] : null;
    }
    function purchaseRowWarrantyDays(row) {
        const idx = purchaseRowIdx(row);
        if (idx === null) return 0;
        return parseInt(row.find('[name="items[' + idx + '][warranty_days]"]').val(), 10) || 0;
    }
    // Rows where warranty is set but an SN field is empty (auto-builds one field per unit)
    function warrantySnProblems() {
        const problems = [];
        $('#product-rows .product-row').each(function () {
            const row = $(this);
            const idx = purchaseRowIdx(row);
            if (idx === null) return;
            if (purchaseRowWarrantyDays(row) <= 0) return;
            const qty = parseInt(row.find('[name="items[' + idx + '][qty]"]').val(), 10) || 0;
            if (qty <= 0) return;
            buildSnFields(row); // ensure we have exactly one field per unit
            const empties = [];
            row.find('.sn-input').each(function (i) { if (String(this.value).trim() === '') empties.push(i); });
            if (empties.length) problems.push({ row: row, idx: idx, empties: empties });
        });
        return problems;
    }
    // Auto-open the SN list on the first problem row and highlight empty fields
    function focusSnProblems(problems) {
        const p = problems[0];
        if (!p) return;
        const block = p.row.find('.sn-block');
        block.show();
        p.row.find('.sn-input').removeClass('is-invalid');
        const first = p.row.find('.sn-input').eq(p.empties[0]);
        first.addClass('is-invalid');
        if (window.toastr) toastr.error('Warranty is set — every unit needs a serial number (SN). Please fill the highlighted field(s).');
        if (block.length) $('html, body').animate({ scrollTop: block.offset().top - 150 }, 400);
        setTimeout(function () { first.trigger('focus').trigger('select'); }, 500);
    }

    // 🔁 SN must be unique — same value typed twice for one product (e.g. "1212"
    // and "1212") is rejected; different values ("1212" and "1213") are fine.
    // Flags every input sharing a duplicated value red, live as the user types.
    function markDuplicateSnInputs(row) {
        const inputs = row.find('.sn-input').toArray();
        const counts = {};
        inputs.forEach(function (el) {
            const val = String(el.value || '').trim();
            if (val) counts[val] = (counts[val] || 0) + 1;
        });
        inputs.forEach(function (el) {
            const val = String(el.value || '').trim();
            $(el).toggleClass('is-invalid', !!val && counts[val] > 1);
        });
    }
    $('#product-rows').on('input blur', '.sn-input', function () {
        markDuplicateSnInputs($(this).closest('.product-row'));
    });

    // Same check across the whole form at publish time — also catches the same
    // SN reused across two different rows for the SAME product.
    function duplicateSnProblems() {
        const groups = {}; // product_id (or row index, if none selected) -> { value: [input, ...] }
        $('#product-rows .product-row').each(function () {
            const row = $(this);
            const idx = purchaseRowIdx(row);
            if (idx === null) return;
            const key = row.find('.product-select').val() || ('row-' + idx);
            row.find('.sn-input').each(function () {
                const val = String(this.value || '').trim();
                if (!val) return;
                groups[key] = groups[key] || {};
                (groups[key][val] = groups[key][val] || []).push({ row: row, input: this });
            });
        });
        const problems = [];
        Object.keys(groups).forEach(function (key) {
            Object.keys(groups[key]).forEach(function (val) {
                if (groups[key][val].length > 1) problems.push({ value: val, entries: groups[key][val] });
            });
        });
        return problems;
    }
    function focusDuplicateSnProblems(problems) {
        const p = problems[0];
        if (!p) return;
        p.entries.forEach(function (e) {
            e.row.find('.sn-block').show();
            $(e.input).addClass('is-invalid');
        });
        const first = $(p.entries[0].input);
        if (window.toastr) toastr.error('Serial number "' + p.value + '" is entered ' + p.entries.length + ' times — each SN must be unique.');
        const block = first.closest('.sn-block');
        if (block.length) $('html, body').animate({ scrollTop: block.offset().top - 150 }, 400);
        setTimeout(function () { first.trigger('focus').trigger('select'); }, 500);
    }
    // Auto-open the SN list when a supplier warranty is set on a row
    $('#product-rows').on('change input', 'input[name$="[warranty_days]"]', function () {
        const name = $(this).attr('name') || '';
        if (name.includes('[warranty_tiers]')) return; // skip per-tier rows
        const row = $(this).closest('.product-row');
        if ((parseInt($(this).val(), 10) || 0) > 0) {
            row.find('.sn-block').show();
            buildSnFields(row);
        }
    });

    // Auto-fill tier name + sensible default days when the warranty type changes
    $('#product-rows').on('change', '.warranty-type-select', function() {
        const $tr = $(this).closest('tr');
        const nameInput = $tr.find('.warranty-tier-name');
        const daysInput = $tr.find('.warranty-days-input');
        const type = $(this).val();
        if (!nameInput.val()) {
            nameInput.val($(this).find('option:selected').text());
        }
        if (type === 'none') {
            daysInput.val(0);
        } else if (!daysInput.val() || daysInput.val() === '0') {
            daysInput.val(90);
        }
    });

    $('#product-rows').on('input', 'input[name*="qty"], input[name*="unit_cost"]', calcGrandTotal);
    $('#global-discount, #global-shipping').on('input', calcGrandTotal);

    // 🔍 BARCODE SCANNER
    $('#barcode-scan').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const code = $(this).val().trim();
            if (!code) return;
            const product = allProducts.find(p => p.barcode === code);
            if (product) {
                const lastRow = $('#product-rows .product-row').last();
                lastRow.find('.product-select').val(product.id).trigger('change');
                lastRow.find('input[name*="qty"]').focus().select();
            } else {
                alert('No product found with barcode: ' + code);
            }
            $(this).val('');
        }
    });

    // ⭐ PRODUCT / STOCK PANEL (right side)
    // Lists every product added in the purchase as an accordion row; each shows
    // that product's Stock-In batch history with a "View" → batch detail popup.
    function ppCollectProductIds() {
        const ids = [];
        $('#product-rows .product-row').each(function () {
            const v = $(this).find('.product-select').val();
            if (v) ids.push(v);
        });
        return ids;
    }

    function ppLoadPanel() {
        $('#price-panel-container').css('opacity', '0.5');
        $.get('{{ route("purchases.price.panel") }}', { products: ppCollectProductIds() }, function (res) {
            if (res.status === 'success') {
                $('#price-panel-container').html(res.html);
            }
        }).always(function () {
            $('#price-panel-container').css('opacity', '1');
        });
    }

    // Reload the panel after an inline action (used by the panel's own script)
    window.ppReload = ppLoadPanel;

    $('#purchase-entry-form').on('input change', ':input', function() {
        if (editingPurchaseId) return;
        if (restoringDraft) return;
        clearTimeout(draftSaveTimer);
        draftSaveTimer = setTimeout(function() { savePurchaseDraft(false); }, 800);
    });

    $('#save-purchase-draft').on('click', function() {
        if (editingPurchaseId) return;
        savePurchaseDraft(true);
    });

    // Warranty additional cost is min:0 server-side — clamp negatives as they are
    // typed so native min="0" validation never silently blocks the edit-mode submit.
    $(document).on('input change', '[name*="[additional_cost]"]', function () {
        if (this.value !== '' && parseFloat(this.value) < 0) {
            this.value = '0';
        }
    });

    $('#purchase-entry-form').on('submit', function(event) {
        event.preventDefault();
        const form = this;

        // ⭐ EDIT MODE — update sell price, MRP, wholesale and warranty via AJAX
        if (editingPurchaseId) {
            const batches = [];
            const wholesaleCalls = [];
            const warrantyCalls = [];
            $('#product-rows .product-row').each(function() {
                const bid = $(this).attr('data-batch-id');
                if (!bid) return;
                const m = $(this).find('.product-select').attr('name').match(/items\[(\d+)\]/);
                const idx = m ? m[1] : 0;
                batches.push({
                    batch_id: bid,
                    selling_price: $(this).find('[name="items[' + idx + '][selling_price]"]').val(),
                    mrp: $(this).find('[name="items[' + idx + '][mrp]"]').val()
                });

                // Wholesale tiers
                const wsTiers = [];
                $(this).find('.wholesale-pricing-rows tr').each(function() {
                    const minQty = $(this).find('[name*="[min_quantity]"]').val();
                    const wsPrice = $(this).find('[name*="[wholesale_price]"]').val();
                    if (minQty === '' || minQty === undefined || wsPrice === '' || wsPrice === undefined) return;
                    wsTiers.push({
                        id: $(this).data('id') || null,
                        min_quantity: minQty,
                        max_quantity: $(this).find('[name*="[max_quantity]"]').val() || null,
                        wholesale_price: wsPrice
                    });
                });
                const removedWs = wsRemovedIds[bid] || [];
                if (wsTiers.length || removedWs.length) {
                    wholesaleCalls.push($.post('{{ route("purchases.price.wholesale.save") }}', {
                        _token: '{{ csrf_token() }}',
                        batch_id: bid,
                        tiers: wsTiers,
                        delete_ids: removedWs
                    }));
                }

                // Warranty tiers
                const tiers = [];
                $(this).find('.warranty-pricing-rows tr').each(function() {
                    const tierId = $(this).find('[name*="[tier_id]"]').val();
                    if (!tierId) return;
                    tiers.push({
                        tier_id: tierId,
                        warranty_type: $(this).find('[name*="[warranty_type]"]').val() || null,
                        tier_name: $(this).find('[name*="[tier_name]"]').val() || null,
                        warranty_days: $(this).find('[name*="[warranty_days]"]').val() || null,
                        // Server rule is min:0 — clamp negatives instead of failing the whole update
                        additional_cost: Math.max(0, parseFloat($(this).find('[name*="[additional_cost]"]').val()) || 0),
                        is_active: $(this).find('[name*="[is_active]"]:checkbox').is(':checked') ? 1 : 0
                    });
                });
                const removedWt = wtRemovedIds[bid] || [];
                if (tiers.length || removedWt.length) {
                    warrantyCalls.push($.post('{{ route("purchases.price.warranty.save") }}', {
                        _token: '{{ csrf_token() }}',
                        batch_id: bid,
                        tiers: tiers,
                        delete_ids: removedWt
                    }));
                }
            });
            const editBtn = $('#publish-submit-btn');
            editBtn.prop('disabled', true).html('<i class="fe-loader me-1"></i> Updating…');
            const promises = [$.post('{{ route("purchases.price.batches.save") }}', {
                _token: '{{ csrf_token() }}',
                batches: batches
            })].concat(wholesaleCalls, warrantyCalls);
            $.when.apply($, promises).then(function() {
                if (window.toastr) toastr.success('Purchase updated.');
                setTimeout(function() { location.reload(); }, 700);
            }).fail(function(xhr) {
                editBtn.prop('disabled', false).html('<i class="fe-save me-1"></i> Update Purchase');
                // Show the real server/validation message instead of a generic one
                let msg = 'Update failed.';
                if (xhr && xhr.responseJSON) {
                    const rj = xhr.responseJSON;
                    if (rj && rj.message) {
                        msg = rj.message;
                    } else if (rj && rj.errors) {
                        msg = Object.values(rj.errors).flat().join(' ');
                    }
                }
                if (window.toastr) toastr.error(msg);
            });
            return;
        }

        // 🔢 Warranty → SN required: block publish + auto-open the SN list until filled
        const snProblems = warrantySnProblems();
        if (snProblems.length) {
            focusSnProblems(snProblems);
            return;
        }

        // 🔁 SN must be unique — block publish if the same SN was typed twice
        const dupSnProblems = duplicateSnProblems();
        if (dupSnProblems.length) {
            focusDuplicateSnProblems(dupSnProblems);
            return;
        }

        const publish = function() {
            form.submit();
        };
        const message = 'Publishing this purchase creates stock, supplier due, and fund records. Purchase details, stock, costs, and payments will be locked. Only selling price and warranty cost can be updated afterward.';

        if (typeof Swal === 'undefined') {
            if (window.confirm(message + ' Continue?')) {
                publish();
            }
            return;
        }

        Swal.fire({
            title: 'Ready to publish?',
            html: '<div class="text-start small text-muted">' +
                '<p class="mb-3">Review the purchase carefully before publishing.</p>' +
                '<div class="border rounded p-2 bg-light mb-2"><strong class="text-dark d-block mb-1">Publishing will create</strong>Stock, supplier due, and fund records.</div>' +
                '<div class="border border-warning rounded p-2"><strong class="text-dark d-block mb-1">After publishing</strong>Purchase details, stock, costs, and payments are locked. Only selling price and warranty cost remain editable.</div>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Publish Purchase',
            cancelButtonText: 'Keep Editing',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: true
        }).then(function(result) {
            if (result.isConfirmed) {
                publish();
            }
        });
    });

    // ⭐ PURCHASE EDIT — reuse the New Purchase Entry form for editing
    let editingPurchaseId = null;

    // Track removed existing wholesale tier rows (edit mode) so they can be deleted on save
    const wsRemovedIds = {};
    $(document).on('click', '.ws-remove-row', function () {
        const $tr = $(this).closest('tr');
        const id = $tr.data('id');
        const batchId = $tr.closest('.product-row').attr('data-batch-id');
        if (id && batchId) {
            wsRemovedIds[batchId] = wsRemovedIds[batchId] || [];
            wsRemovedIds[batchId].push(id);
        }
        $tr.remove();
    });

    // Track removed existing warranty tier rows (edit mode) so they can be deleted on save
    const wtRemovedIds = {};
    $(document).on('click', '.wr-remove-row', function () {
        const $tr = $(this).closest('tr');
        const id = $tr.data('id');
        const batchId = $tr.closest('.product-row').attr('data-batch-id');
        if (id && batchId) {
            wtRemovedIds[batchId] = wtRemovedIds[batchId] || [];
            wtRemovedIds[batchId].push(id);
        }
        $tr.remove();
    });

    function loadPurchaseIntoForm(data) {
        editingPurchaseId = data.purchase.id;

        // Reset the form to a clean state
        $('#purchase-entry-form')[0].reset();
        $('#purchase-draft-id').val('');
        $('#product-rows .product-row:not(:first)').remove();
        rowIndex = 1;
        const firstRow = $('#product-rows .product-row').first();
        firstRow.find('input').val('');
        firstRow.find('input[name*="qty"]').val('1');
        firstRow.find('.variant-col').hide();
        firstRow.find('.wholesale-pricing-block, .warranty-pricing-block').hide();
        firstRow.find('.wholesale-pricing-rows, .warranty-pricing-rows').html('');
        firstRow.find('.sn-block').hide();
        firstRow.find('.sn-inputs').html('');
        firstRow.removeAttr('data-sn-qty');
        firstRow.removeAttr('data-batch-serials');
        firstRow.find('.pp-activate-cb').prop('checked', false);
        firstRow.attr('data-batch-id', '');
        firstRow.find('.product-select').val('');
        firstRow.find('.variant-select').html('<option value="">All</option>');

        // Purchase-level fields
        $('#purchase-entry-form select[name="supplier_id"]').val(data.purchase.supplier_id || '');
        $('#purchase-entry-form input[name="invoice_no"]').val(data.purchase.invoice_no || '');
        $('#purchase-entry-form input[name="purchase_date"]').val(data.purchase.purchase_date || '');
        $('#purchase-entry-form input[name="discount"]').val(data.purchase.discount || 0);
        $('#purchase-entry-form input[name="shipping_cost"]').val(data.purchase.shipping_cost || 0);
        $('#purchase-entry-form input[name="paid_amount"]').val(data.purchase.paid_amount || 0);
        $('#purchase-entry-form input[name="note"]').val(data.purchase.note || '');

        // Product rows
        data.items.forEach(function (item, i) {
            let $row;
            if (i === 0) {
                $row = firstRow;
            } else {
                addProductRow();
                $row = $('#product-rows .product-row').last();
            }
            const m = $row.find('.product-select').attr('name').match(/items\[(\d+)\]/);
            const idx = m ? m[1] : 0;
            $row.attr('data-batch-id', item.batch_id || '');
            $row.attr('data-batch-serials', JSON.stringify(item.serial_numbers || []));
            $row.find('.product-select').val(item.product_id).trigger('change');
            if (item.variant_id) {
                $row.find('.variant-select').val(item.variant_id);
            }
            $row.find('[name="items[' + idx + '][qty]"]').val(item.qty);
            $row.find('[name="items[' + idx + '][unit_cost]"]').val(item.unit_cost);
            $row.find('[name="items[' + idx + '][batch_no]"]').val(item.batch_no || '');
            $row.find('[name="items[' + idx + '][selling_price]"]').val(item.selling_price != null ? item.selling_price : '');
            $row.find('[name="items[' + idx + '][mrp]"]').val(item.mrp != null ? item.mrp : '');

            // Wholesale tiers (per batch)
            const wsBody = $row.find('.wholesale-pricing-rows');
            wsBody.html('');
            (item.wholesale_tiers || []).forEach(function (w, k) {
                wsBody.append(
                    '<tr data-id="' + (w.id || '') + '">' +
                        '<td><input type="number" min="1" name="items[' + idx + '][wholesale_tiers][' + k + '][min_quantity]" class="form-control form-control-sm" value="' + w.min_quantity + '"></td>' +
                        '<td><input type="number" min="1" name="items[' + idx + '][wholesale_tiers][' + k + '][max_quantity]" class="form-control form-control-sm" value="' + (w.max_quantity || '') + '"></td>' +
                        '<td><input type="number" step="0.01" name="items[' + idx + '][wholesale_tiers][' + k + '][wholesale_price]" class="form-control form-control-sm" value="' + w.wholesale_price + '"></td>' +
                        '<td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger ws-remove-row" title="Remove">×</button></td>' +
                    '</tr>'
                );
            });
            if (wsBody.children().length) {
                $row.find('.wholesale-pricing-block').show();
            }

            // Warranty pricing tiers (per batch)
            const wtBody = $row.find('.warranty-pricing-rows');
            wtBody.html('');
            (item.warranty_tiers || []).forEach(function (t, j) {
                wtBody.append(
                    '<tr data-id="' + (t.bwt_id || '') + '">' +
                        '<td><span class="badge bg-soft-info text-info small">' + (t.warranty_type || 'tier') + '</span>' +
                            '<input type="hidden" name="items[' + idx + '][warranty_tiers][' + j + '][tier_id]" value="' + t.tier_id + '"></td>' +
                        '<td><span class="small">' + (t.tier_name || '') + '</span></td>' +
                        '<td><span class="small">' + (t.warranty_days || 0) + 'd</span></td>' +
                        '<td><input type="number" min="0" step="0.01" name="items[' + idx + '][warranty_tiers][' + j + '][additional_cost]" class="form-control form-control-sm" value="' + t.additional_cost + '"></td>' +
                        '<td class="text-center">' +
                            '<input type="hidden" name="items[' + idx + '][warranty_tiers][' + j + '][is_active]" value="0">' +
                            '<input type="checkbox" name="items[' + idx + '][warranty_tiers][' + j + '][is_active]" value="1" class="form-check-input" ' + (t.is_active ? 'checked' : '') + '>' +
                        '</td>' +
                        '<td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger wr-remove-row" title="Remove">×</button></td>' +
                    '</tr>'
                );
            });
            if (wtBody.children().length) {
                $row.find('.warranty-pricing-block').show();
            }
        });

        calcGrandTotal();

        // Lock purchase details, stock, costs and item info — sell/wholesale/warranty stay editable
        $('#purchase-entry-form')
            .find('select[name="supplier_id"], input[name="invoice_no"], input[name="purchase_date"], input[name="discount"], input[name="shipping_cost"], input[name="paid_amount"]')
            .prop('disabled', true);
        $('#product-rows .product-row').each(function () {
            $(this).find(
                '.product-select, .variant-select, .btn-remove-row, ' +
                'input[name*="[qty]"], input[name*="[unit_cost]"], input[name*="[batch_no]"], ' +
                'input[name*="[mfg_date]"], input[name*="[exp_date]"], input[name*="[custom_field]"], ' +
                'input[name*="[activate_website]"], input[name*="[warranty_days]"], ' +
                'input[name*="[warranty_start]"], input[name*="[warranty_terms]"], input[name*="[transferable]"], ' +
                'input[name*="[serial_numbers]"]'
            ).prop('disabled', true);
        });
        // In edit mode the SN List button stays available but only shows a read-only list of the
        // batch serials (no entry fields) — serial edits happen from each batch's View popup on the right.
        $('#product-rows .sn-block').hide();
        $('#product-rows .toggle-sn-list').removeClass('btn-dark active').addClass('btn-outline-dark');
        $('#add-product-row, #barcode-scan, #save-purchase-draft').prop('disabled', true);

        $('#purchase-form-heading').html('<i class="fe-edit-3 me-1"></i> {{ __('Edit Purchase') }} — ' + data.purchase.invoice_no);
        $('#publish-submit-btn').html('<i class="fe-save me-1"></i> Update Purchase');
        $('#purchase-draft-status').text('{{ __('Editing published purchase — sell price, wholesale and warranty can be updated.') }}');

        // Refresh the right-side product/stock panel for the products being edited
        ppLoadPanel();
    }

    // Load the purchase into the same form when Edit is clicked
    $(document).on('click', '.purchase-edit-btn', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        $.get(url, function (res) {
            if (res.status !== 'success') {
                if (window.toastr) toastr.error('Could not load purchase data.');
                return;
            }
            loadPurchaseIntoForm(res);
            $('html, body').animate({ scrollTop: $('#purchase-entry-form').offset().top - 20 }, 400);
            if (window.toastr) toastr.info('Purchase loaded for editing.');
        }).fail(function () { if (window.toastr) toastr.error('Request failed'); });
    });

    restorePurchaseDraft();

    // Initial render of the product/stock panel (empty state if nothing selected yet)
    ppLoadPanel();

</script>
@endpush