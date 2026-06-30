@extends('backEnd.layouts.master')
@section('title','{{ __('Refund {{ __('Manage') }}ment') }}')

@section('css')
<style>
    /* --- General Card & Layout --- */
    .card-modern {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
        background: #fff;
        overflow: hidden;
    }

    /* --- Modern Table Styles --- */
    .table-responsive {
        padding: 0 5px;
    }
    .table-modern {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    .table-modern thead th {
        background-color: transparent;
        color: #8898aa;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        padding: 15px;
    }
    .table-modern tbody tr {
        background-color: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        transition: all 0.2s ease;
    }
    .table-modern tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(0,0,0,0.08);
        z-index: 1;
    }
    .table-modern td {
        border: none;
        padding: 18px 15px;
        vertical-align: middle;
        border-top: 1px solid #f8f9fa;
        font-size: {{ __('14px') }};
        color: #525f7f;
    }
    .table-modern td:first-child {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
    }
    .table-modern td:last-child {
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
    }

    /* --- Typography & Badges --- */
    .fw-bold-custom { font-weight: 600; color: #32325d; }
    .invoice-link {
        color: #5e72e4;
        font-weight: 600;
        text-decoration: none;
        background: rgba(94, 114, 228, 0.1);
        padding: 4px 8px;
        border-radius: 5px;
        font-size: 12px;
    }
    .badge-modern {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .badge-pending { background: rgba(251, 99, 64, 0.1); color: #fb6340; }
    .badge-approved { background: rgba(45, 206, 137, 0.1); color: #2dce89; }
    .badge-rejected { background: rgba(245, 54, 92, 0.1); color: #f5365c; }
    .badge-processed { background: rgba(17, 205, 239, 0.1); color: #11cdef; }

    /* --- Search & Pagination --- */
    .search-modern {
        border-radius: 25px;
        padding-left: 20px;
        border: 1px solid #e9ecef;
        background-color: #f8f9fa;
    }
    .search-modern:focus {
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(50,50,93,0.11);
    }
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 40px;
        margin-bottom: 20px;
    }
    .modern-pagination {
        display: flex;
        list-style: none;
        padding: 0;
        gap: 8px;
    }
    .modern-page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #fff;
        color: #525f7f;
        font-size: {{ __('14px') }};
        font-weight: 600;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        transition: all 0.2s;
    }
    .modern-page-link:hover {
        background-color: #f6f9fc;
        transform: translateY(-2px);
    }
    .modern-page-item.active .modern-page-link {
        background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
        color: white;
        border: none;
        box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    .modern-page-item.disabled .modern-page-link {
        background-color: #f6f9fc;
        color: #adb5bd;
        cursor: not-allowed;
        box-shadow: none;
    }
    .modern-page-link-text { width: auto; padding: 0 15px; border-radius: 20px; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    
    <div class="row align-items-center mb-4">
        <div class="col">
            <h2 class="h3 mb-0 text-gray-800">{{ __('Refund {{ __('Manage') }}ment') }}</h2>
            <p class="text-muted text-small mb-0">{{ __('{{ __('Manage') }} and track all refund requests') }}</p>
        </div>
        <div class="col-auto">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 m-0">
                    <li class="breadcrumb-item"><a href="#">{{ __('Home') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Refunds') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-modern">
                <div class="card-body">
                    
                    <form method="{{ __('GET') }}" action="{{ route('admin.refunds.index') }}" class="mb-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 border rounded-pill-start ps-3"><i class="fe-search text-muted"></i></span>
                                    <input type="text" name="order_invoice" class="form-control border-start-0 search-modern ps-2" placeholder="{{ __('Search by {{ __('{{ __('{{ __('Inv') }}oice') }} ID') }}...') }}" value="{{ request('order_invoice') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select search-modern" onchange="this.form.{{ __('submit') }}()">
                                    <option value="">{{ __('{{ __('All {{ __('Status') }}') }}es') }}</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5 text-end">
                                <a href="{{ route('admin.refunds.index') }}" class="btn btn-light rounded-pill px-4">
                                    <i class="fe-refresh-cw me-1"></i>{{ __('Reset') }}</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Refund Info') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('{{ __('Status') }}') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $key => $refund)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        
                                        <td>
                                            <div class="fw-bold-custom">#{{ $refund->refund_id }}</div>
                                            <a href="{{ route('admin.order.invoice', ['invoice_id' => $refund->order->invoice_id]) }}" target="_blank" class="invoice-link mt-1 d-inline-block">
                                                INV-{{ $refund->order->invoice_id }}
                                            </a>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm rounded-circle bg-light text-primary d-flex align-items-center justify-content-center me-2" style="width:35px; height:35px;">
                                                    <i class="fe-user"></i>
                                                </div>
                                                <div>
                                                    <span class="d-block fw-bold-custom">{{ $refund->customer->name ?? 'Guest' }}</span>
                                                    <small class="text-muted">{{ $refund->customer->{{ __('phone') }} ?? '{{ __('N/A') }}' }}</small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="fw-bold-custom" style="font-size: 15px;">৳{{ number_format($refund->amount + $refund->shipping_charge, 2) }}</div>
                                            @if($refund->shipping_charge > 0)
                                                <small class="text-muted d-block" style="font-size: 10px;">{{ __('(Incl. {{ __('Shipping') }})') }}</small>
                                            @endif
                                        </td>

                                        <td>
                                            @php
                                                $icons = [
                                                    'bkash' => 'fe-smart{{ __('phone') }}',
                                                    'nagad' => 'fe-smart{{ __('phone') }}',
                                                    'bank' => 'fe-briefcase',
                                                    'manual' => 'fe-dollar-sign',
                                                    'original_payment' => 'fe-credit-card'
                                                ];
                                                $icon = $icons[$refund->refund_method] ?? 'fe-help-circle';
                                            @endphp
                                            <div class="d-flex align-items-center text-muted">
                                                <i class="{{ $icon }} me-2"></i>
                                                <span class="text-capitalize">{{ str_replace('_', ' ', $refund->refund_method) }}</span>
                                            </div>
                                        </td>

                                        <td>
                                            @if($refund->status == 'pending')
                                                <span class="badge-modern badge-pending">{{ __('Pending') }}</span>
                                            @elseif($refund->status == 'approved')
                                                <span class="badge-modern badge-approved">{{ __('{{ __('Approve') }}d') }}</span>
                                            @elseif($refund->status == 'rejected')
                                                <span class="badge-modern badge-rejected">{{ __('{{ __('Reject') }}ed') }}</span>
                                            @elseif($refund->status == 'processed')
                                                <span class="badge-modern badge-processed">{{ __('Processed') }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="text-muted" style="font-size: 13px;">{{ $refund->created_at->format('d M, Y') }}</span>
                                        </td>

                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-icon-only text-light" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fe-more-vertical text-muted" style="font-size: 16px;"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                                    <a class="dropdown-item" href="{{ route('admin.refunds.show', $refund->{{ __('id)') }} }}">
                                                        <i class="fe-eye me-2"></i> View Details
                                                    </a>
                                                    
                                                    @if($refund->status == 'pending')
                                                        <div class="dropdown-divider"></div>
                                                        <button type="button" class="dropdown-item text-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $refund->id }}">
                                                            <i class="fe-check me-2"></i> {{ __('Approve') }}
                                                        </button>
                                                        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $refund->id }}">
                                                            <i class="fe-x me-2"></i> {{ __('Reject') }}
                                                        </button>
                                                    @elseif($refund->status == 'approved')
                                                        <div class="dropdown-divider"></div>
                                                        <button type="button" class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#processModal{{ $refund->id }}">
                                                            <i class="fe-check-circle me-2"></i> {{ __('Process Payment') }}
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <h6 class="text-muted">{{ __('No refund requests found!') }}</h6>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($data->hasPages())
                    <div class="pagination-wrapper">
                        <ul class="modern-pagination">
                            {{-- {{ __('Prev') }}ious --}}
                            @if ($data->onFirstPage())
                                <li class="modern-page-item disabled"><span class="modern-page-link modern-page-link-text"><i class="fe-arrow-left me-1"></i> {{ __('Prev') }}</span></li>
                            @else
                                <li class="modern-page-item"><a class="modern-page-link modern-page-link-text" href="{{ $data->previousPageUrl() }}" rel="prev"><i class="fe-arrow-left me-1"></i> {{ __('Prev') }}</a></li>
                            @endif

                            {{-- Numbers --}}
                            @foreach(range(1, $data->lastPage()) as $i)
                                @if($i >= $data->currentPage() - 2 && $i <= $data->currentPage() + 2)
                                    @if ($i == $data->currentPage())
                                        <li class="modern-page-item active"><span class="modern-page-link">{{ $i }}</span></li>
                                    @else
                                        <li class="modern-page-item"><a class="modern-page-link" href="{{ $data->url($i) }}">{{ $i }}</a></li>
                                    @endif
                                @endif
                            @endforeach

                            {{-- Next --}}
                            @if ($data->hasMorePages())
                                <li class="modern-page-item"><a class="modern-page-link modern-page-link-text" href="{{ $data->nextPageUrl() }}" rel="next">{{ __('Next') }}<i class="fe-arrow-right ms-1"></i></a></li>
                            @else
                                <li class="modern-page-item disabled"><span class="modern-page-link modern-page-link-text">{{ __('Next') }}<i class="fe-arrow-right ms-1"></i></span></li>
                            @endif
                        </ul>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL SECTION (MOVED OUTSIDE TABLE) --}}
@foreach($data as $refund)

    <div class="modal fade" id="approveModal{{ $refund->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">{{ __('{{ __('Approve') }} Refund') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.refunds.approve', $refund->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }}>
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3">{{ __('{{ __('Approve') }} refund of') }} <strong>৳{{ number_format($refund->amount + $refund->shipping_charge, 2) }}</strong>?</p>
                        <div class="form-group">
                            <label class="form-label text-small fw-bold">{{ __('Admin {{ __('{{ __('Note') }} {{ __('({{ __('Optional') }})') }}') }}') }}</label>
                            <textarea name="admin_note" class="form-control bg-light border-0" rows="2">{{ $refund->admin_note }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="{{ __('submit') }}" class="btn btn-success px-4">{{ __('Approve') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal{{ $refund->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-danger">{{ __('{{ __('Reject') }} Refund') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.refunds.reject', $refund->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }}>
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label text-small fw-bold">{{ __('{{ __('Reject') }}ion {{ __('Reason') }}') }} <span class="text-danger">*</span></label>
                            <textarea name="admin_note" class="form-control bg-light border-0" rows="3" required>{{ $refund->admin_note }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="{{ __('submit') }}" class="btn btn-danger px-4">{{ __('Reject') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="processModal{{ $refund->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">{{ __('Process Payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.refunds.process', $refund->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }}>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-small fw-bold">{{ __('Transaction ID') }} <span class="text-danger">*</span></label>
                            <input type="text" name="transaction_id" class="form-control bg-light border-0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-small fw-bold">{{ __('Method') }}</label>
                            <select name="refund_method" class="form-select bg-light border-0">
                                <option value="bkash">{{ __('bKash') }}</option>
                                <option value="nagad">{{ __('Nagad') }}</option>
                                <option value="bank">{{ __('Bank Transfer') }}</option>
                                <option value="manual">{{ __('{{ __('Cash') }}/Manual') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-small fw-bold">{{ __('Account Info') }}</label>
                            <input type="text" name="refund_account" class="form-control bg-light border-0" value="{{ $refund->refund_account }}">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="{{ __('submit') }}" class="btn btn-primary px-4">{{ __('Complete') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endforeach
{{-- END MODAL SECTION --}}

@endsection