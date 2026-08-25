@extends('backEnd.layouts.master')
@section('title','Edit Purchase')

@section('css')
<style>
    /* --- Modern Card & Form Styles --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    .card-header-modern {
        background-color: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.25rem 1.5rem;
        border-radius: 12px 12px 0 0 !important;
    }
    .form-label {
        font-weight: 600;
        color: #344767;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }
    .form-control {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
    }
    .form-control[readonly] {
        background-color: #f8f9fc;
        border-color: #eaecf4;
        color: #6e707e;
    }
    
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

    {{-- PAGE HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
        <div>
            <h4 class="mb-1 fw-bold text-gray-800">
                <i data-feather="edit-3" class="text-primary me-1"></i> Edit Purchase
            </h4>
            <p class="text-muted small mb-0"> {{ __('Update invoice details and payment information.') }} </p>
        </div>
        <a href="{{ route('purchases.index') }}" class="btn btn-white border shadow-sm rounded-pill px-3">
            <i data-feather="arrow-left" class="me-1"></i> Back to List
        </a>
    </div>

    <form action="{{ route('purchases.update', $purchase->id) }}" method="POST">
        @csrf
        <div class="row">
            
            {{-- LEFT COLUMN: EDITABLE FORM --}}
            <div class="col-lg-8">
                <div class="card card-modern mb-4">
                    <div class="card-header-modern">
                        <h6 class="m-0 font-weight-bold text-primary"> {{ __('Editable Information') }} </h6>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label"> {{ __('Invoice No') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i data-feather="hash" style="width:14px;"></i></span>
                                    <input type="text" name="invoice_no" class="form-control border-start-0 @error('invoice_no') is-invalid @enderror" 
                                           value="{{ old('invoice_no', $purchase->invoice_no) }}" required>
                                </div>
                                @error('invoice_no') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label"> {{ __('Purchase Date') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i data-feather="calendar" style="width:14px;"></i></span>
                                    <input type="date" name="purchase_date" class="form-control border-start-0 @error('purchase_date') is-invalid @enderror" 
                                           value="{{ old('purchase_date', $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                                </div>
                                @error('purchase_date') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Paid Amount (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" 
                                   value="{{ old('paid_amount', $purchase->paid_amount) }}" min="0" max="{{ $purchase->grand_total }}" required>
                            
                            @error('paid_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                            
                            <div class="alert alert-soft-warning d-flex align-items-center mt-2 p-2 rounded" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404;">
                                <i data-feather="alert-circle" class="me-2"></i>
                                <small><strong>Warning:</strong> {{ __('Changing the "Paid Amount" will automatically adjust your company fund balance.') }} </small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"> {{ __('Note (Optional)') }} </label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Add any additional details here...">{{ old('note', $purchase->note) }}</textarea>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i data-feather="save" class="me-1"></i> Update Changes
                            </button>
                            <a href="{{ route('purchases.index') }}" class="btn btn-light px-4">{{ __('Cancel') }}</a>
                        </div>

                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: READ-ONLY INFO --}}
            <div class="col-lg-4">
                
                {{-- Grand Total Card --}}
                <div class="card card-modern mb-4">
                    <div class="card-body p-0">
                        <div class="grand-total-box">
                            <div class="opacity-75 text-uppercase small fw-bold mb-1"> {{ __('Grand Total Amount') }} </div>
                            <h2 class="mb-0 fw-bold">{{ number_format($purchase->grand_total, 2) }} ৳</h2>
                            <small class="opacity-75">(Cannot be edited here)</small>
                        </div>
                    </div>
                </div>

                {{-- Supplier Info --}}
                <div class="card card-modern mb-4">
                    <div class="card-header-modern bg-light">
                        <h6 class="m-0 font-weight-bold text-dark"> {{ __('Supplier Details') }} </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="info-label">{{ __('Name') }}</div>
                            <div class="info-value text-dark">{{ $purchase->supplier->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-0">
                            <div class="info-label">{{ __('Phone') }}</div>
                            <div class="text-secondary">{{ $purchase->supplier->phone ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Meta Info --}}
                <div class="card card-modern">
                    <div class="card-body">
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

            </div>
        </div>
    </form>

    {{-- ⭐ Batch-wise pricing — this purchase's batches --}}
    @if((bool) config('pricing.batch_wise', false) && $batches->isNotEmpty())
    <div class="card card-modern mb-4">
        <div class="card-header-modern">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fe-tag me-1"></i> {{ __('Batch Pricing (this purchase)') }}
                <small class="text-muted d-block">{{ __('Sell price / MRP per batch — website uses the active batch, POS uses the chosen batch') }}</small>
            </h6>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-2" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Batch') }}</th>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Remaining') }}</th>
                            <th class="text-end">{{ __('Unit Cost') }}</th>
                            <th style="width:110px;">{{ __('Sell Price') }}</th>
                            <th style="width:110px;">{{ __('MRP') }}</th>
                            <th class="text-center" style="width:150px;">{{ __('Website') }}</th>
                            <th class="text-center">{{ __('Save') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batches as $b)
                        <tr class="{{ $b->is_active_for_website ? 'table-success' : '' }}">
                            <td>
                                <strong>{{ $b->batch_no ?: 'Batch #'.$b->id }}</strong>
                                @if($b->is_active_for_website) <span class="badge bg-success">{{ __('Active') }}</span> @endif
                            </td>
                            <td>{{ $b->product?->name }}</td>
                            <td class="text-end">{{ $b->remaining_qty }}</td>
                            <td class="text-end">৳{{ number_format($b->unit_cost, 2) }}</td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm pe-sell" data-id="{{ $b->id }}" value="{{ $b->selling_price }}"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm pe-mrp" data-id="{{ $b->id }}" value="{{ $b->mrp }}"></td>
                            <td class="text-center">
                                @if(!$b->is_active_for_website)
                                <button type="button" class="btn btn-xs btn-success pe-activate" data-id="{{ $b->id }}">
                                    <i class="fe-check-circle"></i> {{ __('Set Active') }}
                                </button>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-primary pe-save" data-id="{{ $b->id }}" title="{{ __('Save price') }}">
                                    <i class="fe-save"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <small class="text-muted"><i class="fe-info me-1"></i>{{ __('Only one batch can be the website active batch. When it sells out, auto-advance moves to the next FIFO batch with stock.') }}</small>
        </div>
    </div>
    @endif

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

    // Save sell price + MRP for a batch
    $(document).on('click', '.pe-save', function () {
        var id = $(this).data('id');
        pePost('{{ route("purchases.price.batch.save") }}', {
            batch_id: id,
            selling_price: $('.pe-sell[data-id="' + id + '"]').val(),
            mrp: $('.pe-mrp[data-id="' + id + '"]').val()
        });
    });

    // Set a batch as the website active batch
    $(document).on('click', '.pe-activate', function () {
        pePost('{{ route("purchases.price.activate") }}', { batch_id: $(this).data('id') });
    });
});
</script>
@endpush