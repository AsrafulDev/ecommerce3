@extends('backEnd.layouts.master')
@section('title', 'New Stock Adjustment')

@php
    $totalStock = \App\Models\Product::sum('stock');
    $lowStock   = \App\Models\Product::where('stock','>',0)->where('stock','<=',10)->count();
    $outOfStock = \App\Models\Product::where('stock','<=',0)->count();
    $totalProds = \App\Models\Product::count();
@endphp

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <div>
            <h4 class="fw-bold text-gray-800 mb-0">
                <i data-feather="edit" class="text-warning me-1"></i> New Stock Adjustment
            </h4>
            <div class="d-flex gap-3 mt-2">
                <span class="badge bg-primary px-3 py-2">Total Stock: {{ number_format($totalStock) }}</span>
                <span class="badge bg-warning text-dark px-3 py-2">Low Stock (≤10): {{ $lowStock }}</span>
                <span class="badge bg-danger px-3 py-2">Out of Stock: {{ $outOfStock }}</span>
                <span class="badge bg-secondary px-3 py-2">Products: {{ $totalProds }}</span>
            </div>
        </div>
        <a href="{{ route('admin.stock.adjustments') }}" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i data-feather="arrow-left" class="me-1" style="width:14px;"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.stock.adjustments.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fe-package me-1"></i> Adjustment Items</span>
                        <div class="d-flex gap-2">
                            <input type="text" id="barcode-scan" class="form-control form-control-sm" style="width:200px;" placeholder="Scan barcode to add...">
                            <button type="button" id="add-row-btn" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus"></i> Add Row</button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div id="adj-rows">
                            <div class="adj-row border rounded p-3 mb-2 bg-light">
                                <div class="row align-items-end g-2">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small">Product *</label>
                                        <select name="items[0][product_id]" class="form-control form-select form-select-sm product-select" required>
                                            <option value="">Select</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" data-stock="{{ $p->stock }}" data-barcode="{{ $p->barcode ?? '' }}" data-has-variants="{{ $p->variantPrices->count() > 0 ? 1 : 0 }}">
                                                    {{ $p->name }} ({{ $p->stock }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2 variant-col" style="display:none;">
                                        <label class="form-label small">Variant</label>
                                        <select name="items[0][variant_id]" class="form-control form-select form-select-sm variant-select">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Curr.Stock</label>
                                        <input type="text" class="form-control form-control-sm current-stock-display" readonly value="—">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Type *</label>
                                        <select name="items[0][type]" class="form-control form-select form-select-sm" required>
                                            <option value="addition">Addition (+)</option>
                                            <option value="reduction">Reduction (−)</option>
                                            <option value="correction">Correction (=)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small">Qty *</label>
                                        <input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control form-control-sm" value="1" required>
                                    </div>
                                    <div class="col-md-3 mb-2 batch-col" style="display:none;">
                                        <label class="form-label small">Batch</label>
                                        <select name="items[0][batch_id]" class="form-control form-select form-select-sm batch-select">
                                            <option value="">Auto / Global</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 mb-2">
                                        <label class="form-label small">Reason</label>
                                        <input type="text" name="items[0][reason]" class="form-control form-control-sm" placeholder="Why?">
                                    </div>
                                    <div class="col-md-2 mb-2 self-align-right">
                                        <button type="button" class="btn btn-danger btn-sm btn-remove-row w-100"><i class="fa fa-times"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-warning rounded-pill px-4">
                            <i data-feather="save" class="me-1" style="width:14px;"></i> Save All Adjustments
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('script')
<script>
$(function(){
    let rowIdx = 1;
    const allProducts = {!! $productsJson !!};

    function loadVariants(row, pid) {
        const vCol = row.find('.variant-col'), vSel = row.find('.variant-select');
        vSel.html('<option value="">All</option>');
        $.get('/admin/products/' + pid + '/variants', function(data) {
            if (data && data.length) {
                vCol.show();
                data.forEach(v => {
                    vSel.append('<option value="'+v.id+'" data-stock="'+(v.stock||0)+'">'+(v.color_name||'')+' / '+(v.size_name||'')+' ('+(v.stock||0)+')</option>');
                });
            } else { vCol.hide(); }
        });
    }

    function loadBatches(row, pid, vid) {
        const bCol = row.find('.batch-col'), bSel = row.find('.batch-select');
        bSel.html('<option value="">Auto/Global</option>');
        $.get('/admin/stock/products/' + pid + '/batches' + (vid ? '?variant_id='+vid : ''), function(data) {
            if (data && data.length) {
                bCol.show();
                data.forEach(b => {
                    bSel.append('<option value="'+b.id+'" data-qty="'+b.remaining_qty+'">'+ (b.batch_no||'Batch#'+b.id) +' ('+b.remaining_qty+' @ '+b.unit_cost+'TK)</option>');
                });
            } else { bCol.hide(); }
        });
    }

    function updateStock(row) {
        const pid = row.find('.product-select').val();
        const vid = row.find('.variant-select').val();
        const stockDisplay = row.find('.current-stock-display');
        if (vid) {
            const opt = row.find('.variant-select option:selected');
            stockDisplay.val(opt.data('stock') || '—');
        } else if (pid) {
            const opt = row.find('.product-select option:selected');
            stockDisplay.val(opt.data('stock') || '—');
        } else { stockDisplay.val('—'); }
    }

    $('#adj-rows').on('change', '.product-select', function() {
        const row = $(this).closest('.adj-row'), pid = $(this).val();
        loadVariants(row, pid); loadBatches(row, pid, ''); updateStock(row);
    });

    $('#adj-rows').on('change', '.variant-select', function() {
        const row = $(this).closest('.adj-row');
        const pid = row.find('.product-select').val();
        const vid = $(this).val();
        loadBatches(row, pid, vid); updateStock(row);
    });

    $('#adj-rows').on('click', '.btn-remove-row', function() {
        if ($('#adj-rows .adj-row').length > 1) {
            $(this).closest('.adj-row').remove(); reindexRows();
        }
    });

    function reindexRows() {
        $('#adj-rows .adj-row').each(function(i) {
            $(this).find('[name]').each(function() {
                const n = $(this).attr('name');
                if (n) $(this).attr('name', n.replace(/items\[\d+\]/, 'items['+i+']'));
            });
        });
        rowIdx = $('#adj-rows .adj-row').length;
    }

    $('#add-row-btn').click(function() {
        const tmpl = $('#adj-rows .adj-row').first().clone();
        tmpl.find('input').val(''); tmpl.find('input[name*="quantity"]').val('1');
        tmpl.find('.current-stock-display').val('—');
        tmpl.find('.variant-col, .batch-col').hide();
        tmpl.find('[name]').each(function() {
            $(this).attr('name', $(this).attr('name').replace(/items\[\d+\]/, 'items['+rowIdx+']'));
        });
        $('#adj-rows').append(tmpl); rowIdx++;
    });

    // Barcode
    $('#barcode-scan').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const code = $(this).val().trim();
            if (!code) return;
            const p = allProducts.find(x => x.barcode === code);
            if (p) {
                const last = $('#adj-rows .adj-row').last();
                last.find('.product-select').val(p.id).trigger('change');
                last.find('input[name*="quantity"]').focus().select();
            } else { alert('No product with barcode: '+code); }
            $(this).val('');
        }
    });
});
</script>
@endsection
