@extends('backEnd.layouts.master')
@section('title','Edit Purchase')

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

    /* --- Info Cards on Right --- */
    .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #858796; font-weight: 700; }
    .info-value { font-size: 1.1rem; font-weight: 600; color: #4e73df; }
    .grand-total-box {
        background: linear-gradient(45deg, #4e73df, #224abe);
        color: white;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
    }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">🛒 Edit Purchase</h1>
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
        {{-- LEFT: PRICING PANEL — this purchase's batches (manage-style) - BIGGER WIDTH --}}
        <div class="col-lg-8 mb-4">
            {{-- Supplier / Meta info card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-2 bg-white">
                    <h6 class="m-0 fw-bold text-primary"><i class="fe-truck me-1"></i> {{ __('Supplier Details') }}</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="info-label">{{ __('Name') }}</div>
                        <div class="info-value text-dark">{{ $purchase->supplier->name ?? 'N/A' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="info-label">{{ __('Phone') }}</div>
                        <div class="text-secondary">{{ $purchase->supplier->phone ?? 'N/A' }}</div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <i data-feather="clock" class="text-muted me-2 mt-1" style="width:16px;"></i>
                        <div>
                            <div class="info-label">{{ __('Created At') }}</div>
                            <div class="small text-dark">{{ $purchase->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>
                    @if($purchase->updated_by)
                    <div class="d-flex align-items-start">
                        <i data-feather="refresh-cw" class="text-muted me-2 mt-1" style="width:16px;"></i>
                        <div>
                            <div class="info-label">{{ __('Last Updated') }}</div>
                            <div class="small text-dark">{{ $purchase->updated_at->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                    <h6 class="m-0 fw-bold text-primary"><i class="fe-tag me-1"></i> {{ __('Pricing Panel') }}</h6>
                    <span class="badge bg-success">Batch: {{ $batches->count() }}</span>
                </div>
                <div class="card-body p-2">
                    {{-- Purchase summary --}}
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="flex-grow-1">
                            <strong class="text-dark" style="font-size:13px;">{{ $purchase->invoice_no }}</strong>
                            <div class="small text-muted">
                                @if($purchase->supplier)
                                    {{ $purchase->supplier->name }} ({{ $purchase->supplier->phone }})
                                @else
                                    <span class="text-danger">{{ __('Deleted') }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="badge bg-info">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M, Y') }}</span>
                    </div>

                    @if($batches->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="fe-tag" style="font-size: 32px;"></i>
                            <p class="small mb-0 mt-2">No batches found for this purchase.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-2" style="font-size:12px;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:28px;"></th>
                                        <th>{{ __('Batch') }}</th>
                                        <th style="max-width:70px;">{{ __('Product') }}</th>
                                        <th class="text-end">{{ __('Left') }}</th>
                                        <th style="width:95px;">{{ __('Sell') }}</th>
                                        <th style="width:95px;">{{ __('MRP') }}</th>
                                        <th class="text-center">{{ __('POS') }}</th>
                                        <th class="text-center">{{ __('Website') }}</th>
                                        <th class="text-center">{{ __('Save') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($batches as $b)
                                    <tr class="{{ $b->is_active_for_website ? 'table-success' : '' }}">
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-outline-secondary pe-toggle-details" data-target="#pp-details-row-{{ $b->id }}" title="{{ __('Manage variant / wholesale / warranty') }}">
                                                <i class="fe-chevron-down"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <strong>{{ $b->batch_no ?: 'Batch #'.$b->id }}</strong>
                                            @if($b->is_active_for_website) <span class="badge bg-success">{{ __('Active') }}</span> @endif
                                        </td>
                                        <td class="text-truncate" style="max-width:70px;" title="{{ $b->product?->name }}">{{ $b->product?->name }}</td>
                                        <td class="text-end"><strong>{{ $b->remaining_qty }}</strong></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm pe-sell" data-id="{{ $b->id }}" value="{{ $b->selling_price }}" style="min-width:85px;"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm pe-mrp" data-id="{{ $b->id }}" value="{{ $b->mrp }}" style="min-width:85px;"></td>
                                        <td class="text-center">
                                            <label class="d-inline-flex align-items-center gap-1 mb-0 small">
                                                <input type="checkbox" class="pe-pos-toggle" data-id="{{ $b->id }}" {{ $b->pos_enabled ? 'checked' : '' }}>
                                            </label>
                                        </td>
                                        <td class="text-center">
                                            @if($b->is_active_for_website)
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            @else
                                                <button type="button" class="btn btn-xs btn-outline-success pe-activate" data-id="{{ $b->id }}" title="{{ __('Set as website active batch') }}">{{ __('Set Active') }}</button>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-primary pe-save" data-id="{{ $b->id }}" title="{{ __('Save price') }}">
                                                <i class="fe-save"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="pp-details-row" id="pp-details-row-{{ $b->id }}" style="display:none;">
                                        <td colspan="9" class="p-0">
                                            <div class="p-2 bg-light border-top">
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-4"><small class="text-muted">{{ __('Purchase Price') }}</small><div class="fw-bold">৳{{ number_format($b->unit_cost, 2) }}</div></div>
                                                    <div class="col-md-4"><small class="text-muted">{{ __('Quantity') }}</small><div class="fw-bold">{{ $b->quantity }}</div></div>
                                                    <div class="col-md-4"><small class="text-muted">{{ __('Expiry') }}</small><div class="fw-bold">{{ $b->exp_date?->format('d M Y') ?? '—' }}</div></div>
                                                </div>

                                                <div class="accordion" id="ppBatchAccordion{{ $b->id }}">
                                                    @if($b->product?->variantPrices->count())
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header">
                                                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#ppVariant{{ $b->id }}">
                                                                <i class="fe-grid me-1"></i> {{ __('Variant') }}
                                                            </button>
                                                        </h2>
                                                        <div id="ppVariant{{ $b->id }}" class="accordion-collapse collapse" data-bs-parent="#ppBatchAccordion{{ $b->id }}">
                                                            <div class="accordion-body p-2">
                                                                <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                                                                    <thead class="table-light"><tr><th>{{ __('Variant') }}</th><th>{{ __('Price') }}</th><th>{{ __('MRP') }}</th><th>{{ __('Stock') }}</th></tr></thead>
                                                                    <tbody>
                                                                    @foreach($b->product->variantPrices as $vp)
                                                                        @php $bvp = $b->variantPrices->firstWhere('variant_price_id', $vp->id); @endphp
                                                                        <tr>
                                                                            <td>{{ trim(($vp->color?->colorName ?? $vp->color?->name ?? '') . ' ' . ($vp->size?->sizeName ?? $vp->size?->name ?? '')) ?: __('No Variant') }}</td>
                                                                            <td>৳{{ number_format($bvp->price ?? $vp->price, 2) }}</td>
                                                                            <td>{{ ($bvp->old_price ?? 0) ? '৳' . number_format($bvp->old_price, 2) : '—' }}</td>
                                                                            <td>{{ $bvp->stock ?? 0 }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                    </tbody>
                                                                </table>
                                                                <small class="text-muted"><i class="fe-info"></i> {{ __('Edit variant prices from the product page.') }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif

                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header">
                                                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#ppWholesale{{ $b->id }}">
                                                                <i class="fe-layers me-1"></i> {{ __('Wholesale') }}
                                                            </button>
                                                        </h2>
                                                        <div id="ppWholesale{{ $b->id }}" class="accordion-collapse collapse" data-bs-parent="#ppBatchAccordion{{ $b->id }}">
                                                            <div class="accordion-body p-2">
                                                                <table class="table table-sm table-bordered mb-2" style="font-size:12px;">
                                                                    <thead class="table-light"><tr><th>{{ __('Min') }}</th><th>{{ __('Max') }}</th><th>{{ __('Wholesale Price') }}</th><th style="width:36px;"></th></tr></thead>
                                                                    <tbody class="pe-ws-rows" data-batch-id="{{ $b->id }}">
                                                                    @foreach($b->wholesalePrices as $w)
                                                                        <tr data-id="{{ $w->id }}">
                                                                            <td><input type="number" min="1" class="form-control form-control-sm pe-ws-min" value="{{ $w->min_quantity }}"></td>
                                                                            <td><input type="number" min="1" class="form-control form-control-sm pe-ws-max" value="{{ $w->max_quantity }}"></td>
                                                                            <td><input type="number" step="0.01" class="form-control form-control-sm pe-ws-price" value="{{ $w->wholesale_price }}"></td>
                                                                            <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger pe-ws-remove"><i class="fe-x"></i></button></td>
                                                                        </tr>
                                                                    @endforeach
                                                                    </tbody>
                                                                </table>
                                                                <div class="d-flex gap-2">
                                                                    <button type="button" class="btn btn-xs btn-outline-secondary pe-ws-add" data-batch-id="{{ $b->id }}"><i class="fa fa-plus"></i> {{ __('Add Tier') }}</button>
                                                                    <button type="button" class="btn btn-xs btn-primary pe-ws-save" data-batch-id="{{ $b->id }}"><i class="fe-save"></i> {{ __('Save Wholesale') }}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header">
                                                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#ppWarranty{{ $b->id }}">
                                                                <i class="fe-shield me-1"></i> {{ __('Warranty') }}
                                                            </button>
                                                        </h2>
                                                        <div id="ppWarranty{{ $b->id }}" class="accordion-collapse collapse" data-bs-parent="#ppBatchAccordion{{ $b->id }}">
                                                            <div class="accordion-body p-2">
                                                                <div class="pe-wt-rows" data-batch-id="{{ $b->id }}">
                                                                @forelse($b->product->warrantyTiers as $t)
                                                                    @php $ov = $b->warrantyTiers->firstWhere('warranty_tier_id', $t->id); @endphp
                                                                    <div class="border rounded p-2 mb-1 d-flex align-items-center gap-2 pe-wt-row" data-tier-id="{{ $t->id }}">
                                                                        <div class="flex-grow-1">
                                                                            <strong>{{ $t->tier_name }}</strong> <span class="text-muted small">({{ $t->warranty_days }}d)</span>
                                                                        </div>
                                                                        <input type="number" step="0.01" class="form-control form-control-sm pe-wt-cost" style="width:100px;" value="{{ $ov->additional_cost ?? $t->additional_cost }}" placeholder="{{ __('Cost') }}">
                                                                        <label class="small d-inline-flex align-items-center gap-1 mb-0">
                                                                            <input type="checkbox" class="pe-wt-active" {{ ($ov->is_active ?? $t->is_active) ? 'checked' : '' }}> {{ __('Active') }}
                                                                        </label>
                                                                    </div>
                                                                @empty
                                                                    <p class="text-muted small mb-0">{{ __('No warranty tiers defined for this product.') }}</p>
                                                                @endforelse
                                                                </div>
                                                                @if($b->product->warrantyTiers->count())
                                                                    <button type="button" class="btn btn-xs btn-primary pe-wt-save mt-1" data-batch-id="{{ $b->id }}"><i class="fe-save"></i> {{ __('Save Warranty') }}</button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary btn-sm pe-save-all px-3">
                                <i class="fe-save me-1"></i> {{ __('Save All Prices') }}
                            </button>
                            <small class="text-muted"><i class="fe-info me-1"></i>{{ __('Only one batch can be the website active batch. When it sells out, auto-advance moves to the next FIFO batch with stock.') }}</small>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- RIGHT: EDIT FORM (Editable Information) - SMALLER WIDTH --}}
        <div class="col-lg-4 mb-4">
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fe-edit-3 me-1"></i> {{ __('Editable Information') }} </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('purchases.update', $purchase->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label"> {{ __('Invoice No') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i data-feather="hash" style="width:14px;"></i></span>
                                <input type="text" name="invoice_no" class="form-control border-start-0 @error('invoice_no') is-invalid @enderror"
                                       value="{{ old('invoice_no', $purchase->invoice_no) }}" required>
                            </div>
                            @error('invoice_no') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"> {{ __('Purchase Date') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i data-feather="calendar" style="width:14px;"></i></span>
                                <input type="date" name="purchase_date" class="form-control border-start-0 @error('purchase_date') is-invalid @enderror"
                                       value="{{ old('purchase_date', $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                            </div>
                            @error('purchase_date') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Paid Amount (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror"
                                   value="{{ old('paid_amount', $purchase->paid_amount) }}" min="0" max="{{ $purchase->grand_total }}" required>
                            @error('paid_amount') <span class="text-danger small">{{ $message }}</span> @enderror

                            <div class="alert alert-soft-warning d-flex align-items-center mt-2 p-2 rounded" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; font-size: 0.85rem;">
                                <i data-feather="alert-circle" class="me-2" style="width: 16px; flex-shrink: 0;"></i>
                                <small><strong>Warning:</strong> {{ __('Changing "Paid Amount" adjusts fund balance.') }} </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"> {{ __('Note (Optional)') }} </label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Add details...">{{ old('note', $purchase->note) }}</textarea>
                        </div>

                        <div class="bg-light p-2 rounded mb-3">
                            <strong class="small">Grand Total:</strong>
                            <div class="text-primary fw-bold">{{ number_format($purchase->grand_total, 2) }} ৳</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe-save me-1"></i> Update
                            </button>
                            <a href="{{ route('purchases.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Export (same as manage) --}}
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

    {{-- Recent Purchase History (same as manage) --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fe-list me-1"></i> {{ __('Recent Purchase History') }} </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
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
                            <td>{{ \Carbon\Carbon::parse($p->purchase_date)->format('d M, Y') }}</td>
                            <td>
                                <span class="fw-bold text-dark">{{ $p->invoice_no }}</span>
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
                                        <a href="{{ route('purchases.edit', $p->id) }}" class="btn btn-action btn-outline-primary ms-1" title="{{ __('Edit') }}">
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
                <div class="p-3 d-flex justify-content-end">
                    {{ $purchases->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {
    function pePost(url, data, cb) {
        data._token = '{{ csrf_token() }}';
        $.post(url, data, function (res) {
            if (res.status === 'success') {
                if (window.toastr) toastr.success(res.message || 'Saved');
                if (cb) cb();
                setTimeout(function () { location.reload(); }, 700);
            } else {
                if (window.toastr) toastr.error(res.message || 'Error');
            }
        }).fail(function () { if (window.toastr) toastr.error('Request failed'); });
    }

    // Save sell price + MRP + POS status for a batch
    $(document).on('click', '.pe-save', function () {
        var id = $(this).data('id');
        pePost('{{ route("purchases.price.batch.save") }}', {
            batch_id: id,
            selling_price: $('.pe-sell[data-id="' + id + '"]').val(),
            mrp: $('.pe-mrp[data-id="' + id + '"]').val(),
            pos_enabled: $('.pe-pos-toggle[data-id="' + id + '"]').is(':checked') ? 1 : 0
        });
    });

    $(document).on('change', '.pe-pos-toggle', function () {
        var id = $(this).data('id');
        pePost('{{ route("purchases.price.batch.save") }}', {
            batch_id: id,
            selling_price: $('.pe-sell[data-id="' + id + '"]').val(),
            mrp: $('.pe-mrp[data-id="' + id + '"]').val(),
            pos_enabled: $(this).is(':checked') ? 1 : 0
        });
    });

    // Set a batch as the website active batch
    $(document).on('click', '.pe-activate', function () {
        pePost('{{ route("purchases.price.activate") }}', { batch_id: $(this).data('id') });
    });

    // Toggle a batch's Variant/Wholesale/Warranty details row
    $(document).on('click', '.pe-toggle-details', function () {
        var $row = $($(this).data('target'));
        $row.toggle();
        $(this).find('i').toggleClass('fe-chevron-down fe-chevron-up');
    });

    // Add a blank wholesale tier row
    $(document).on('click', '.pe-ws-add', function () {
        var batchId = $(this).data('batch-id');
        $('.pe-ws-rows[data-batch-id="' + batchId + '"]').append(
            '<tr><td><input type="number" min="1" class="form-control form-control-sm pe-ws-min"></td>' +
            '<td><input type="number" min="1" class="form-control form-control-sm pe-ws-max"></td>' +
            '<td><input type="number" step="0.01" class="form-control form-control-sm pe-ws-price"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger pe-ws-remove"><i class="fe-x"></i></button></td></tr>'
        );
    });

    // Remove a wholesale tier row (tracks deleted ids for the save request)
    var peWsDeletedIds = {};
    $(document).on('click', '.pe-ws-remove', function () {
        var $tr = $(this).closest('tr');
        var id = $tr.data('id');
        var batchId = $tr.closest('.pe-ws-rows').data('batch-id');
        if (id) {
            peWsDeletedIds[batchId] = peWsDeletedIds[batchId] || [];
            peWsDeletedIds[batchId].push(id);
        }
        $tr.remove();
    });

    // Save wholesale tiers for a batch
    $(document).on('click', '.pe-ws-save', function () {
        var batchId = $(this).data('batch-id');
        var tiers = [];
        $('.pe-ws-rows[data-batch-id="' + batchId + '"] tr').each(function () {
            var min = $(this).find('.pe-ws-min').val();
            var price = $(this).find('.pe-ws-price').val();
            if (min === '' || price === '') return;
            tiers.push({
                id: $(this).data('id') || null,
                min_quantity: min,
                max_quantity: $(this).find('.pe-ws-max').val() || null,
                wholesale_price: price
            });
        });
        pePost('{{ route("purchases.price.wholesale.save") }}', {
            batch_id: batchId,
            tiers: tiers,
            delete_ids: peWsDeletedIds[batchId] || []
        }, function () { peWsDeletedIds[batchId] = []; });
    });

    // Save warranty tier overrides for a batch
    $(document).on('click', '.pe-wt-save', function () {
        var batchId = $(this).data('batch-id');
        var tiers = [];
        $('.pe-wt-rows[data-batch-id="' + batchId + '"] .pe-wt-row').each(function () {
            tiers.push({
                tier_id: $(this).data('tier-id'),
                additional_cost: $(this).find('.pe-wt-cost').val() || 0,
                is_active: $(this).find('.pe-wt-active').is(':checked') ? 1 : 0
            });
        });
        pePost('{{ route("purchases.price.warranty.save") }}', { batch_id: batchId, tiers: tiers });
    });

    // Save ALL sell prices + MRPs in one request
    $(document).on('click', '.pe-save-all', function () {
        var map = {};
        $('.pe-sell').each(function () {
            var id = $(this).data('id');
            if (!map[id]) map[id] = { batch_id: id };
            map[id].selling_price = $(this).val();
        });
        $('.pe-mrp').each(function () {
            var id = $(this).data('id');
            if (!map[id]) map[id] = { batch_id: id };
            map[id].mrp = $(this).val();
        });
        var batches = Object.values(map);
        if (!batches.length) return;

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fe-loader me-1"></i> Saving…');
        $.post('{{ route("purchases.price.batches.save") }}', {
            _token: '{{ csrf_token() }}',
            batches: batches
        }, function (res) {
            if (res.status === 'success') {
                if (window.toastr) toastr.success(res.message || 'Saved');
                setTimeout(function () { location.reload(); }, 700);
            } else {
                if (window.toastr) toastr.error(res.message || 'Error');
                btn.prop('disabled', false).html('<i class="fe-save me-1"></i> Save All Prices');
            }
        }).fail(function () {
            if (window.toastr) toastr.error('Request failed');
            btn.prop('disabled', false).html('<i class="fe-save me-1"></i> Save All Prices');
        });
    });
});
</script>
@endpush