@extends('backEnd.layouts.master')
@section('title', 'New Stock Adjustment')

@section('content')
<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <h4 class="fw-bold text-gray-800 mb-0">
            <i data-feather="edit" class="text-warning me-1"></i> New Stock Adjustment
        </h4>
        <a href="{{ route('admin.stock.adjustments') }}" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i data-feather="arrow-left" class="me-1" style="width:14px;"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.stock.adjustments.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">Product *</label>
                            <select name="product_id" class="form-control select2 @error('product_id') is-invalid @enderror" required>
                                <option value="">Select Product</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-stock="{{ $p->stock }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} (Stock: {{ $p->stock }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Stock</label>
                            <input type="text" id="current_stock_display" class="form-control" readonly value="—">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Adjustment Type *</label>
                            <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                <option value="addition" {{ old('type') === 'addition' ? 'selected' : '' }}>Addition (Add Stock)</option>
                                <option value="reduction" {{ old('type') === 'reduction' ? 'selected' : '' }}>Reduction (Remove Stock)</option>
                                <option value="correction" {{ old('type') === 'correction' ? 'selected' : '' }}>Correction (Set Exact Stock)</option>
                            </select>
                            @error('type') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Quantity <small class="text-muted">(For correction: enter the NEW stock value)</small>
                            </label>
                            <input type="number" step="0.01" min="0.01" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                                   value="{{ old('quantity') }}" placeholder="0" required>
                            @error('quantity') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Reason *</label>
                            <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="2"
                                      placeholder="Why is this adjustment needed?" required>{{ old('reason') }}</textarea>
                            @error('reason') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="btn btn-warning rounded-pill px-4">
                            <i data-feather="save" class="me-1" style="width:14px;"></i> Save Adjustment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function () {
        $('select[name="product_id"]').on('change', function () {
            var stock = $(this).find(':selected').data('stock');
            $('#current_stock_display').val(stock !== undefined ? stock : '—');
        });
        // Trigger on load if old value exists
        if ($('select[name="product_id"]').val()) {
            $('select[name="product_id"]').trigger('change');
        }
    });
</script>
@endsection
