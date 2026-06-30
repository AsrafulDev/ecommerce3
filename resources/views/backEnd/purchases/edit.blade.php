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
    .grand-{{ __('total') }}-box {
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
            <p class="text-muted small mb-0">{{ __('Update invoice details and payment information.') }}</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="btn btn-white border shadow-sm rounded-pill px-3">
            <i data-feather="arrow-left" class="me-1"></i> Back to List
        </a>
    </div>

    <form action="{{ route('purchases.update', $purchase->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }}>
        @csrf
        <div class="row">
            
            {{-- LEFT COLUMN: EDITABLE FORM --}}
            <div class="col-lg-8">
                <div class="card card-modern mb-4">
                    <div class="card-header-modern">
                        <h6 class="m-0 font-weight-bold text-primary">{{ __('Editable Information') }}</h6>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">{{ __('{{ __('{{ __('Inv') }}oice') }} No') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i data-feather="hash" style="width:{{ __('14px') }};"></i></span>
                                    <input type="text" name="invoice_no" class="form-control border-start-0 @error('invoice_no') is-invalid @enderror" 
                                           value="{{ old('invoice_no', $purchase->invoice_no) }}" required>
                                </div>
                                @error('invoice_no') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">{{ __('Purchase {{ __('Date') }}') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i data-feather="calendar" style="width:{{ __('14px') }};"></i></span>
                                    <input type="date" name="purchase_date" class="form-control border-start-0 @error('purchase_date') is-invalid @enderror" 
                                           value="{{ old('purchase_date', $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                                </div>
                                @error('purchase_date') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">{{ __('bn_124b816e') }} <span class="text-danger">*</span></label>
                            <input type="{{ __('number') }}" step="0.01" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" 
                                   value="{{ old('paid_amount', $purchase->paid_amount) }}" min="0" max="{{ $purchase->grand_{{ __('total') }} }}" required>
                            
                            @error('paid_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                            
                            <div class="alert alert-soft-warning d-flex align-items-center mt-2 p-2 rounded" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404;">
                                <i data-feather="alert-circle" class="me-2"></i>
                                <small><strong>{{ __('Warning') }}:</strong> {{ __('Changing the "{{ __('{{ __('Paid') }} {{ __('Amount') }}') }}" will automatically adjust your company fund balance.') }}</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">{{ __('{{ __('Note') }} {{ __('({{ __('Optional') }})') }}') }}</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="{{ __('Add any additional details {{ __('here') }}...') }}">{{ old('note', $purchase->note) }}</textarea>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="{{ __('submit') }}" class="btn btn-primary px-4">
                                <i data-feather="save" class="me-1"></i> Update {{ __('Change') }}s
                            </button>
                            <a href="{{ route('purchases.index') }}" class="btn btn-light px-4">{{ __('Cancel') }}</a>
                        </div>

                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: READ-ONLY INFO --}}
            <div class="col-lg-4">
                
                {{-- {{ __('Grand {{ __('Total') }}') }} Card --}}
                <div class="card card-modern mb-4">
                    <div class="card-body p-0">
                        <div class="grand-{{ __('total') }}-box">
                            <div class="opacity-75 text-uppercase small fw-bold mb-1">{{ __('Grand {{ __('{{ __('Total') }} {{ __('Amount') }}') }}') }}</div>
                            <h2 class="mb-0 fw-bold">{{ number_format($purchase->grand_{{ __('total') }}, 2) }} ৳</h2>
                            <small class="opacity-75">{{ __('(Cannot be edited {{ __('here') }})') }}</small>
                        </div>
                    </div>
                </div>

                {{-- {{ __('{{ __('Supplier') }} Info') }} --}}
                <div class="card card-modern mb-4">
                    <div class="card-header-modern bg-light">
                        <h6 class="m-0 font-weight-bold text-dark">{{ __('{{ __('Supplier') }} Details') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="info-label">{{ __('Name') }}</div>
                            <div class="info-value text-dark">{{ $purchase->supplier->name ?? '{{ __('N/A') }}' }}</div>
                        </div>
                        <div class="mb-0">
                            <div class="info-label">{{ __('Phone') }}</div>
                            <div class="text-secondary">{{ $purchase->supplier->{{ __('phone') }} ?? '{{ __('N/A') }}' }}</div>
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

</div>
@endsection