@extends('backEnd.layouts.master')
@section('title','Refund Details')

@section('css')
<style>
    /* --- General Layout --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        background: #fff;
        margin-bottom: 24px;
        overflow: hidden;
    }
    .card-header-modern {
        background: #fff;
        padding: 20px 25px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #334155;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* --- Typography & Labels --- */
    .label-text {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 5px;
        display: block;
    }
    .value-text {
        font-size: 15px;
        color: #1e293b;
        font-weight: 500;
    }
    .amount-highlight {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
    }

    /* --- Soft Badges --- */
    .badge-soft {
        padding: 8px 16px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .bg-soft-warning { background-color: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
    .bg-soft-success { background-color: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
    .bg-soft-danger { background-color: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
    .bg-soft-info { background-color: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
    .bg-soft-secondary { background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

    /* --- Tables --- */
    .table-details th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 12px 15px;
        border-bottom: 1px solid #e2e8f0;
    }
    .table-details td {
        padding: 15px;
        vertical-align: middle;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }

    /* --- Buttons & Inputs --- */
    .btn-action-lg {
        padding: 12px;
        font-weight: 600;
        border-radius: 8px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    .btn-action-lg:hover { transform: translateY(-2px); }
    
    .form-control-modern {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        font-size: 14px;
    }
    .form-control-modern:focus {
        background-color: #fff;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    
    <div class="row align-items-center mb-4">
        <div class="col">
            <h4 class="mb-1 text-dark fw-bold">Refund Request #{{ $refund->refund_id }}</h4>
            <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 13px;">
                <i class="fe-calendar"></i> Requested: {{ $refund->created_at->format('d M, Y h:i A') }}
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.refunds.index') }}" class="btn btn-white border shadow-sm rounded-pill px-4">
                <i class="fe-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            
            <div class="card-modern">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="label-text">Current Status</span>
                            @if($refund->status == 'pending')
                                <span class="badge-soft bg-soft-warning"><i class="fe-clock"></i> Pending Approval</span>
                            @elseif($refund->status == 'approved')
                                <span class="badge-soft bg-soft-info"><i class="fe-check-circle"></i> Approved & Waiting Payment</span>
                            @elseif($refund->status == 'rejected')
                                <span class="badge-soft bg-soft-danger"><i class="fe-x-circle"></i> Request Rejected</span>
                            @elseif($refund->status == 'processed')
                                <span class="badge-soft bg-soft-success"><i class="fe-check"></i> Successfully Processed</span>
                            @endif
                        </div>
                        <div class="text-end">
                            <span class="label-text">Total Refund Amount</span>
                            <div class="amount-highlight text-primary">৳{{ number_format($refund->amount + $refund->shipping_charge, 2) }}</div>
                            @if($refund->shipping_charge > 0)
                                <small class="text-muted">(Includes Shipping: ৳{{ number_format($refund->shipping_charge, 2) }})</small>
                            @endif
                        </div>
                    </div>
                    
                    @if($refund->processed_at)
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted"><i class="fe-check-square me-1"></i> Processed on: {{ $refund->processed_at->format('d M, Y h:i A') }}</small>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header-modern">
                    <h5 class="section-title"><i class="fe-message-square me-2 text-muted"></i> Reason for Return</h5>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded border border-light">
                        <i class="fe-quote-left text-muted mb-2 d-block"></i>
                        <p class="mb-0 text-dark fst-italic">{{ $refund->reason }}</p>
                    </div>
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header-modern">
                    <h5 class="section-title"><i class="fe-shopping-bag me-2 text-muted"></i> Order Details</h5>
                    <a href="{{ route('admin.order.invoice', ['invoice_id' => $refund->order->invoice_id]) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                        View Invoice <i class="fe-external-link ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="row p-4 border-bottom">
                        <div class="col-md-4">
                            <span class="label-text">Invoice No</span>
                            <span class="value-text fw-bold">#{{ $refund->order->invoice_id }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="label-text">Order Date</span>
                            <span class="value-text">{{ $refund->order->created_at->format('d M, Y') }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="label-text">Grand Total</span>
                            <span class="value-text">৳{{ number_format($refund->order->amount + $refund->order->shipping_charge, 2) }}</span>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-details mb-0">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($refund->order->orderdetails as $item)
                                    <tr>
                                        <td>
                                            <span class="d-block text-dark fw-bold">{{ $item->product_name }}</span>
                                        </td>
                                        <td class="text-center">{{ $item->qty }}</td>
                                        <td class="text-end">৳{{ number_format($item->sale_price, 2) }}</td>
                                        <td class="text-end fw-bold">৳{{ number_format($item->sale_price * $item->qty, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($refund->admin_note)
            <div class="card-modern border-start border-4 border-secondary">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-2"><i class="fe-clipboard me-2"></i> Admin Note</h6>
                    <p class="text-secondary mb-1">{{ $refund->admin_note }}</p>
                    @if($refund->processedBy)
                        <small class="text-muted mt-2 d-block">— Processed by: <strong>{{ $refund->processedBy->name }}</strong></small>
                    @endif
                </div>
            </div>
            @endif

        </div>

        <div class="col-lg-4">
            
            <div class="card-modern">
                <div class="card-body">
                    <h6 class="label-text mb-3">Available Actions</h6>
                    
                    @if($refund->status == 'pending')
                        <button type="button" class="btn btn-success btn-action-lg" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="fe-check"></i> Approve Request
                        </button>
                        <button type="button" class="btn btn-white text-danger border btn-action-lg" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fe-x"></i> Reject Request
                        </button>
                    @elseif($refund->status == 'approved')
                        <div class="alert alert-info bg-soft-info border-0 mb-3">
                            <small>Request approved. Ready for payment.</small>
                        </div>
                        <button type="button" class="btn btn-primary btn-action-lg" data-bs-toggle="modal" data-bs-target="#processModal">
                            <i class="fe-credit-card"></i> Process Payment
                        </button>
                    @else
                        <button class="btn btn-light btn-action-lg text-muted" disabled>
                            <i class="fe-lock"></i> No Actions Available
                        </button>
                    @endif
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header-modern">
                    <h5 class="section-title">Payment Preferences</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="label-text">Method</span>
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs me-2">
                                <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                    <i class="fe-dollar-sign"></i>
                                </span>
                            </div>
                            <span class="value-text text-capitalize">
                                @if($refund->refund_method == 'original_payment') Original Payment
                                @else {{ $refund->refund_method }} @endif
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <span class="label-text">Account Number</span>
                        <span class="value-text font-monospace bg-light px-2 py-1 rounded">{{ $refund->refund_account }}</span>
                    </div>

                    @if($refund->refund_account_name)
                    <div class="mb-3">
                        <span class="label-text">Account Holder</span>
                        <span class="value-text">{{ $refund->refund_account_name }}</span>
                    </div>
                    @endif

                    @if($refund->transaction_id)
                    <div class="p-3 bg-soft-success rounded mt-3">
                        <span class="label-text text-success">Transaction ID</span>
                        <span class="value-text fw-bold text-success">{{ $refund->transaction_id }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card-modern">
                <div class="card-body text-center pt-4">
                    <div class="avatar-md mx-auto mb-3">
                        <span class="avatar-title rounded-circle bg-light text-primary fs-3">
                            {{ substr($refund->customer->name ?? 'G', 0, 1) }}
                        </span>
                    </div>
                    <h5 class="text-dark fw-bold mb-1">{{ $refund->customer->name ?? 'Guest User' }}</h5>
                    <p class="text-muted mb-4">{{ $refund->customer->phone ?? 'No Phone' }}</p>
                    
                    <div class="text-start border-top pt-3">
                        <div class="mb-2">
                            <i class="fe-mail me-2 text-muted"></i> {{ $refund->customer->email ?? 'No Email' }}
                        </div>
                        <div class="mb-2">
                            <i class="fe-map-pin me-2 text-muted"></i> 
                            {{ $refund->customer->address ?? 'N/A' }}, {{ $refund->customer->district ?? '' }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- MODALS SECTION --}}

<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Approve Refund Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.refunds.approve', $refund->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert bg-soft-primary border-0 d-flex align-items-center mb-4">
                        <i class="fe-info me-2 fs-5"></i>
                        <div>This will approve <strong>৳{{ number_format($refund->amount + $refund->shipping_charge, 2) }}</strong> to be refunded.</div>
                    </div>
                    <div class="form-group">
                        <label class="label-text">Admin Note (Optional)</label>
                        <textarea name="admin_note" class="form-control form-control-modern" rows="3" placeholder="Add an internal note...">{{ $refund->admin_note }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">Reject Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.refunds.reject', $refund->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="label-text">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="admin_note" class="form-control form-control-modern" rows="3" placeholder="Why are you rejecting this?" required>{{ $refund->admin_note }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Process Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.refunds.process', $refund->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="label-text">Transaction ID <span class="text-danger">*</span></label>
                        <input type="text" name="transaction_id" class="form-control form-control-modern" required placeholder="TRX-12345678" value="{{ $refund->transaction_id }}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="label-text">Method <span class="text-danger">*</span></label>
                            <select name="refund_method" class="form-select form-control-modern" required>
                                <option value="bkash" {{ $refund->refund_method == 'bkash' ? 'selected' : '' }}>bKash</option>
                                <option value="nagad" {{ $refund->refund_method == 'nagad' ? 'selected' : '' }}>Nagad</option>
                                <option value="bank" {{ $refund->refund_method == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="manual" {{ $refund->refund_method == 'manual' ? 'selected' : '' }}>Cash</option>
                                <option value="original_payment" {{ $refund->refund_method == 'original_payment' ? 'selected' : '' }}>Original</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label-text">Amount</label>
                            <input type="text" class="form-control form-control-modern bg-light" value="৳{{ number_format($refund->amount + $refund->shipping_charge, 2) }}" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="label-text">Sent To (Account)</label>
                        <input type="text" name="refund_account" class="form-control form-control-modern" required value="{{ $refund->refund_account }}">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Complete Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection