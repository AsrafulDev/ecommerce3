@extends('backEnd.layouts.master')
@section('title', isset($supplier) ? 'Edit Supplier' : 'Suppliers Management')

@section('css')
<style>
    /* --- Form & Card Styles --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.02);
        background: #fff;
        transition: all 0.3s ease;
    }
    .card-header-modern {
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.25rem 1.5rem;
        border-radius: 12px 12px 0 0 !important;
        font-weight: 700;
        color: #1e293b;
    }
    
    .form-control-modern {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.65rem 1rem;
        font-size: 0.9rem;
        color: #334155;
    }
    .form-control-modern:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .form-label-modern {
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.4rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* --- Table Styles --- */
    .table-modern th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .table-modern td {
        padding: 1rem;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:last-child td { border-bottom: none; }
    .table-modern tr:hover td { background-color: #f8fafc; }

    /* --- Action Buttons --- */
    .btn-icon {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; transition: all 0.2s;
    }
    .btn-icon:hover { transform: translateY(-2px); }
    .btn-edit { background: #e0e7ff; color: #4338ca; }
    .btn-delete { background: #fee2e2; color: #991b1b; }
    
    .due-amount { font-weight: 600; color: #ef4444; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">

    {{-- PAGE HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="users" class="text-primary me-2"></i> Supplier Management
            </h4>
            <p class="text-muted small mb-0"> {{ __('Manage your supplier list and track dues.') }} </p>
        </div>
        @if(isset($supplier))
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-white border shadow-sm rounded-pill px-3">
                <i data-feather="plus" class="me-1"></i> Add New Supplier
            </a>
        @endif
    </div>

    <div class="row g-4">

        {{-- LEFT COLUMN: FORM --}}
        <div class="col-lg-4">
            <div class="card card-modern h-100">
                <div class="card-header-modern">
                    <i data-feather="{{ isset($supplier) ? 'edit-2' : 'plus-circle' }}" class="me-2" style="width:18px;"></i>
                    {{ isset($supplier) ? 'Edit Supplier' : 'Add New Supplier' }}
                </div>
                <div class="card-body p-4">
                    <form action="{{ isset($supplier) ? route('admin.suppliers.update', $supplier->id) : route('admin.suppliers.store') }}" method="POST">
                        @csrf
                        {{-- Route uses POST, not PUT --}}

                        <div class="mb-3">
                            <label class="form-label-modern"> {{ __('Full Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-modern @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $supplier->name ?? '') }}" placeholder="e.g. John Doe" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern"> {{ __('Phone Number') }} </label>
                            <input type="text" name="phone" class="form-control form-control-modern" 
                                   value="{{ old('phone', $supplier->phone ?? '') }}" placeholder="e.g. 017xxxxxxxx">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern"> {{ __('Email Address') }} </label>
                            <input type="email" name="email" class="form-control form-control-modern" 
                                   value="{{ old('email', $supplier->email ?? '') }}" placeholder="supplier@example.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern">{{ __('Company') }}</label>
                            <input type="text" name="company" class="form-control form-control-modern" 
                                   value="{{ old('company', $supplier->company ?? '') }}" placeholder="Company name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern">{{ __('Contact Person') }}</label>
                            <input type="text" name="contact_person" class="form-control form-control-modern" 
                                   value="{{ old('contact_person', $supplier->contact_person ?? '') }}" placeholder="Alternative contact person">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label-modern">{{ __('Tax ID') }}</label>
                                <input type="text" name="tax_id" class="form-control form-control-modern" 
                                       value="{{ old('tax_id', $supplier->tax_id ?? '') }}" placeholder="Tax/VAT ID">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">{{ __('Payment Terms') }}</label>
                                <select name="payment_terms" class="form-control form-control-modern">
                                    <option value="">Select</option>
                                    <option value="cod" {{ (old('payment_terms', $supplier->payment_terms ?? '') === 'cod') ? 'selected' : '' }}>Cash on Delivery (COD)</option>
                                    <option value="15days" {{ (old('payment_terms', $supplier->payment_terms ?? '') === '15days') ? 'selected' : '' }}>15 Days</option>
                                    <option value="30days" {{ (old('payment_terms', $supplier->payment_terms ?? '') === '30days') ? 'selected' : '' }}>30 Days</option>
                                    <option value="60days" {{ (old('payment_terms', $supplier->payment_terms ?? '') === '60days') ? 'selected' : '' }}>60 Days</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label-modern">{{ __('Lead Time (days)') }}</label>
                                <input type="number" min="0" name="lead_time" class="form-control form-control-modern" 
                                       value="{{ old('lead_time', $supplier->lead_time ?? '') }}" placeholder="Delivery lead time in days">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="is_active" class="form-check-input" value="1" id="is_active"
                                           {{ (old('is_active', $supplier->is_active ?? 1)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern">{{ __('Notes') }}</label>
                            <textarea name="notes" class="form-control form-control-modern" rows="2" 
                                      placeholder="Internal notes about this supplier...">{{ old('notes', $supplier->notes ?? '') }}</textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold">
                                {{ isset($supplier) ? 'Update Supplier' : 'Save Supplier' }}
                            </button>
                            @if(isset($supplier))
                                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-light py-2"> {{ __('Cancel Edit') }} </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: TABLE --}}
        <div class="col-lg-8">
            <div class="card card-modern h-100">
                <div class="card-header-modern d-flex justify-content-between align-items-center">
                    <span> {{ __('Registered Suppliers') }} </span>
                    <span class="badge bg-light text-dark border">{{ $suppliers->total() }} Found</span>
                </div>
                
                <div class="card-body p-0">
                    <div id="supplier-table-wrapper" class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%"> {{ __('Supplier Info') }} </th>
                                    <th width="20%"> {{ __('Contact') }} </th>
                                    <th width="20%">{{ __('Address') }}</th>
                                    <th width="15%"> {{ __('Due Amount') }} </th>
                                    <th width="15%" class="text-end"> {{ __('Actions') }} </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suppliers as $s)
                                    <tr>
                                        <td>{{ $loop->iteration + ($suppliers->currentPage()-1)*$suppliers->perPage() }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $s->name }}</div>
                                            <small class="text-muted">{{ $s->phone }}</small>
                                        </td>
                                        <td>
                                            @if($s->email) <div class="d-flex align-items-center text-muted small">
                                                    <i data-feather="mail" class="me-1" style="width:12px;"></i> {{ $s->email }}
                                                </div>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ Str::limit($s->address, 30) ?? '-' }}</span>
                                        </td>
                                        <td>
                                            @if($s->current_due > 0)
                                                <span class="due-amount">{{ number_format($s->current_due, 2) }} ৳</span>
                                            @else
                                                <span class="badge bg-light text-success border border-success"> {{ __('Paid') }} </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.suppliers.edit', $s->id) }}" class="btn-icon btn-edit me-1" title="{{ __('Edit') }}">
                                                <i data-feather="edit-2" style="width:16px;"></i>
                                            </a>
                                            
                                            <form action="{{ route('admin.suppliers.destroy', $s->id) }}" method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure? This will delete all history related to this supplier.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-delete" title="{{ __('Delete') }}">
                                                    <i data-feather="trash-2" style="width:16px;"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="60" class="mb-3 opacity-25">
                                            <p class="text-muted fw-bold mb-0"> {{ __('No Suppliers Found') }} </p>
                                            <small class="text-muted"> {{ __('Add a new supplier from the left form.') }} </small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div id="supplier-pagination" class="p-3 border-top d-flex justify-content-end">
                            {{ $suppliers->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // AJAX Pagination Script
    $(document).on('click', '#supplier-pagination a', function(e){
        e.preventDefault();
        let url = $(this).attr('href');
        $('#supplier-table-wrapper').css('opacity', '0.5'); // Loading effect
        
        $.get(url, function(response){
            let html = $(response).find('#supplier-table-wrapper').html();
            $('#supplier-table-wrapper').html(html);
            $('#supplier-table-wrapper').css('opacity', '1');
            feather.replace(); // Re-init icons
        });
    });
</script>
@endpush