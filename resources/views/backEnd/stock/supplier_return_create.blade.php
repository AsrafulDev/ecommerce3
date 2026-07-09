@extends('backEnd.layouts.master')
@section('title', 'New Supplier Return')

@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<style>
    .card { border: none; border-radius: 10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,0.1); }
    .card-header { background: #fff; border-bottom: 1px solid #e3e6f0; padding: 1rem 1.35rem; font-weight: 700; border-radius: 10px 10px 0 0 !important; }
    .form-label { font-weight: 600; font-size: 0.85rem; color: #5a5c69; }
    .form-control { border-radius: 6px; border: 1px solid #d1d3e2; padding: 0.5rem 0.75rem; }
    .item-row { background: #fafbfd; border: 1px solid #e2e7f1; padding: 15px; border-radius: 10px; margin-bottom: 12px; }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <h4 class="fw-bold text-gray-800 mb-0">
            <i data-feather="rotate-ccw" class="text-danger me-1"></i> New Supplier Return
        </h4>
        <a href="{{ route('admin.stock.supplier-returns') }}" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i data-feather="arrow-left" class="me-1" style="width:14px;"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.stock.supplier-returns.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">Return Details</div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Supplier *</label>
                                <select name="supplier_id" class="form-control select2" required>
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->company }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Purchase Invoice (Optional)</label>
                                <select name="purchase_id" class="form-control select2">
                                    <option value="">— None —</option>
                                    @foreach($purchases as $p)
                                        <option value="{{ $p->id }}" {{ old('purchase_id') == $p->id ? 'selected' : '' }}>{{ $p->invoice_no }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Return Date *</label>
                                <input type="date" name="return_date" class="form-control" value="{{ old('return_date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reason *</label>
                                <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="Why returning?" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RETURN ITEMS --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i data-feather="shopping-bag" class="me-1" style="width:16px;"></i> Return Items</span>
                        <button type="button" class="btn btn-sm btn-success rounded-pill" id="add-return-item">
                            <i class="fa fa-plus me-1"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body" id="return-items-wrapper">
                        <div class="item-row return-item">
                            <div class="row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Product *</label>
                                    <select name="items[0][product_id]" class="form-control select2 product-select" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Batch</label>
                                    <select name="items[0][batch_id]" class="form-control batch-select">
                                        <option value="">— Auto —</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Qty *</label>
                                    <input type="number" step="0.01" min="0.01" name="items[0][qty]" class="form-control" placeholder="0" required>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Unit Cost *</label>
                                    <input type="number" step="0.01" min="0" name="items[0][unit_cost]" class="form-control" placeholder="0.00" required>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Reason</label>
                                    <input type="text" name="items[0][reason]" class="form-control" placeholder="Optional">
                                </div>
                                <div class="col-md-1 mb-2">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-item w-100" style="margin-top:24px;"><i class="fe-trash-2"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger rounded-pill px-4">
                    <i data-feather="check" class="me-1" style="width:16px;"></i> Create Return
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('script')
<script>
    let itemIndex = 1;
    const productBatchesUrl = "{{ url('admin/stock/products') }}/";

    $('#add-return-item').on('click', function () {
        const clone = $('.return-item:first').clone();
        clone.find('select, input').each(function () {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/\[\d+\]/, '[' + itemIndex + ']'));
            }
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            $(this).val('').removeAttr('data-select2-id');
        });
        clone.find('.select2-container').remove();
        clone.find('.batch-select').empty().append('<option value="">— Auto —</option>');
        $('#return-items-wrapper').append(clone);
        clone.find('.product-select').select2({ width: '100%' });
        itemIndex++;
    });

    $(document).on('change', '.product-select', function () {
        const productId = $(this).val();
        const $batchSelect = $(this).closest('.return-item').find('.batch-select');
        $batchSelect.empty().append('<option value="">Loading...</option>');
        if (productId) {
            $.getJSON(productBatchesUrl + productId + '/batches', function (data) {
                $batchSelect.empty().append('<option value="">— Auto —</option>');
                $.each(data, function (i, b) {
                    $batchSelect.append('<option value="' + b.id + '">' + (b.batch_no || '#' + b.id) + ' (৳' + b.unit_cost + ', qty:' + b.remaining_qty + ')</option>');
                });
            });
        } else {
            $batchSelect.empty().append('<option value="">— Auto —</option>');
        }
    });

    $(document).on('click', '.btn-remove-item', function () {
        if ($('.return-item').length > 1) {
            $(this).closest('.return-item').remove();
        }
    });
</script>
@endsection
