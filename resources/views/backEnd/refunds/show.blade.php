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
                    @php
                        $effectiveTotal = $refund->totalRefundAmount();
                        $isPartial = $refund->refund_amount !== null;
                        // order->amount already includes shipping
                        $orderTotal = (float) $refund->order->amount;
                        $productOnly = $orderTotal - (float) $refund->order->shipping_charge;
                    @endphp
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="label-text"> {{ __('Current Status') }} </span>
                            @if($refund->status == 'pending')
                                <span class="badge-soft bg-soft-warning"><i class="fe-clock"></i> {{ __('Pending Approval') }} </span>
                            @elseif($refund->status == 'approved')
                                <span class="badge-soft bg-soft-info"><i class="fe-check-circle"></i> {{ __('Approved & Waiting Payment') }} </span>
                            @elseif($refund->status == 'rejected')
                                <span class="badge-soft bg-soft-danger"><i class="fe-x-circle"></i> {{ __('Request Rejected') }} </span>
                            @elseif($refund->status == 'processed')
                                <span class="badge-soft bg-soft-success"><i class="fe-check"></i> {{ __('Successfully Processed') }} </span>
                            @endif
                        </div>
                        <div class="text-end">
                            <span class="label-text">
                                {{ __('Refund Amount') }}
                                @if($isPartial)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:10px;">PARTIAL</span>
                                @endif
                            </span>
                            <div class="amount-highlight text-primary">৳{{ number_format($effectiveTotal, 2) }}</div>
                            <small class="text-muted">
                                Order Total: ৳{{ number_format($orderTotal, 2) }}
                                (Product: ৳{{ number_format($productOnly, 2) }}
                                @if($refund->include_shipping && $refund->shipping_charge > 0)
                                    + Shipping: ৳{{ number_format($refund->shipping_charge, 2) }}
                                @else
                                    , Shipping excluded
                                @endif
                                )
                            </small>
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
                    <h5 class="section-title"><i class="fe-message-square me-2 text-muted"></i> {{ __('Reason for Return') }} </h5>
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
                    <h5 class="section-title"><i class="fe-shopping-bag me-2 text-muted"></i>{{ __('Order Details') }}</h5>
                    <a href="{{ route('admin.order.invoice', ['invoice_id' => $refund->order->invoice_id]) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                        View Invoice <i class="fe-external-link ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="row p-4 border-bottom">
                        <div class="col-md-4">
                            <span class="label-text"> {{ __('Invoice No') }} </span>
                            <span class="value-text fw-bold">#{{ $refund->order->invoice_id }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="label-text">{{ __('Order Date') }}</span>
                            <span class="value-text">{{ $refund->order->created_at->format('d M, Y') }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="label-text"> {{ __('Grand Total') }} </span>
                            <span class="value-text">৳{{ number_format((float) $refund->order->amount, 2) }}</span>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-details mb-0">
                            <thead>
                                <tr>
                                    <th> {{ __('Product Name') }} </th>
                                    <th class="text-center">{{ __('Qty') }}</th>
                                    <th class="text-end"> {{ __('Unit Price') }} </th>
                                    <th class="text-end">{{ __('Discount') }}</th>
                                    <th class="text-end">{{ __('Warranty') }}</th>
                                    <th class="text-end">{{ __('Subtotal') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($refund->order->orderdetails as $item)
                                    @php
                                        $pd = (float) ($item->product_discount ?? 0);
                                        $wp = (float) ($item->warranty_price ?? 0);
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="d-block text-dark fw-bold">{{ $item->product_name }}</span>
                                            @if($item->warranty_tier_id)
                                                @php $wt = \App\Models\ProductWarrantyTier::find($item->warranty_tier_id); @endphp
                                                @if($wt && $wt->warranty_days > 0)
                                                    <small class="text-success">🛡️ {{ $wt->tier_name }} ({{ $wt->warranty_days }}d)</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $item->qty }}</td>
                                        <td class="text-end">৳{{ number_format($item->sale_price, 2) }}</td>
                                        <td class="text-end">
                                            @if($pd > 0)<span class="text-danger">-৳{{ number_format($pd, 2) }}</span>@else — @endif
                                        </td>
                                        <td class="text-end">
                                            @if($wp > 0)<span class="text-success">+৳{{ number_format($wp, 2) }}</span>@else — @endif
                                        </td>
                                        <td class="text-end fw-bold">৳{{ number_format($item->sale_price * $item->qty, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Admin & Customer Notes --}}
            @if($refund->admin_note || $refund->customer_note)
            <div class="card-modern border-start border-4 border-secondary">
                <div class="card-body">
                    @if($refund->admin_note)
                    <h6 class="fw-bold text-dark mb-2"><i class="fe-clipboard me-2"></i> {{ __('Admin Note') }} </h6>
                    <p class="text-secondary mb-1">{!! nl2br(e($refund->admin_note)) !!}</p>
                    @endif

                    @if($refund->customer_note)
                    <h6 class="fw-bold text-primary mt-3 mb-2"><i class="fe-message-circle me-2"></i> {{ __('Customer Note') }} </h6>
                    <p class="text-dark mb-1 bg-light p-3 rounded">{!! nl2br(e($refund->customer_note)) !!}</p>
                    @endif

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
                    <h6 class="label-text mb-3"> {{ __('Available Actions') }} </h6>
                    
                    @if($refund->status == 'pending')
                        <button type="button" class="btn btn-success btn-action-lg" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="fe-check"></i> Approve Request
                        </button>
                        <button type="button" class="btn btn-white text-danger border btn-action-lg" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fe-x"></i> {{ __('Reject Request') }} </button>
                    @elseif($refund->status == 'approved')
                        <div class="alert alert-info bg-soft-info border-0 mb-3">
                            <small> {{ __('Request approved. Ready for payment.') }} </small>
                        </div>
                        <button type="button" class="btn btn-primary btn-action-lg" data-bs-toggle="modal" data-bs-target="#processModal">
                            <i class="fe-credit-card"></i> {{ __('Process Payment') }} </button>
                    @else
                        <button class="btn btn-light btn-action-lg text-muted" disabled>
                            <i class="fe-lock"></i> No Actions Available
                        </button>
                    @endif
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header-modern">
                    <h5 class="section-title"> {{ __('Payment Preferences') }} </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="label-text"> {{ __('Method') }} </span>
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
                        <span class="label-text"> {{ __('Account Number') }} </span>
                        <span class="value-text font-monospace bg-light px-2 py-1 rounded">{{ $refund->refund_account }}</span>
                    </div>

                    @if($refund->refund_account_name)
                    <div class="mb-3">
                        <span class="label-text"> {{ __('Account Holder') }} </span>
                        <span class="value-text">{{ $refund->refund_account_name }}</span>
                    </div>
                    @endif

                    @if($refund->transaction_id)
                    <div class="p-3 bg-soft-success rounded mt-3">
                        <span class="label-text text-success"> {{ __('Transaction ID') }} </span>
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

{{-- ════════════════ APPROVE MODAL (Partial Refund + Shipping Toggle) ════════════════ --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"> {{ __('Approve Refund Request') }} </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.refunds.approve', $refund->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    {{-- Refund Amount Breakdown --}}
                    <div class="p-3 bg-light rounded mb-3">
                        @php
                            $productSubtotal = (float) $refund->order->amount - (float) $refund->order->shipping_charge;
                        @endphp
                        <div class="d-flex justify-content-between mb-2">
                            <span>Product Amount:</span>
                            <strong>৳{{ number_format($productSubtotal, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping Charge:</span>
                            <strong>৳{{ number_format($refund->order->shipping_charge, 2) }}</strong>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span>Order Total:</span>
                            <strong>৳{{ number_format((float) $refund->order->amount, 2) }}</strong>
                        </div>
                    </div>

                    {{-- Shipping Toggle --}}
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="include_shipping" name="include_shipping" value="1"
                               {{ $refund->include_shipping ? 'checked' : '' }}>
                        <label class="form-check-label" for="include_shipping">
                            <strong>{{ __('Include Shipping Charge') }}</strong>
                            <small class="text-muted d-block">Toggle off to exclude shipping (৳{{ number_format($refund->shipping_charge, 2) }}) from refund</small>
                        </label>
                    </div>

                    {{-- Partial Refund Amount --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            {{ __('Custom Refund Amount (Optional)') }}
                            <small class="text-muted fw-normal">— Leave empty for full refund</small>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" name="refund_amount" class="form-control form-control-modern"
                                   step="0.01" min="0"
                                   max="{{ (float) $refund->order->amount }}"
                                   placeholder="Enter partial amount..."
                                   value="{{ $refund->refund_amount }}">
                        </div>
                        <small class="text-muted">Set a specific amount for partial refund. Max: ৳{{ number_format((float) $refund->order->amount, 2) }}</small>
                    </div>

                    {{-- Admin Note --}}
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold"> {{ __('Admin Note') }} </label>
                        <textarea name="admin_note" class="form-control form-control-modern" rows="2"
                                  placeholder="Add an internal note (visible only to admins)...">{{ $refund->admin_note }}</textarea>
                    </div>

                    {{-- Customer Note --}}
                    <div class="form-group">
                        <label class="form-label fw-bold">
                            {{ __('Customer Note') }}
                            <small class="text-muted fw-normal">— Visible to customer</small>
                        </label>
                        <textarea name="customer_note" class="form-control form-control-modern" rows="2"
                                  placeholder="Add a note for the customer...">{{ $refund->customer_note }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success px-4" id="approveSubmitBtn">
                        <i class="fe-check me-1"></i> {{ __('Approve Refund') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"> {{ __('Reject Request') }} </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.refunds.reject', $refund->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="label-text"> {{ __('Rejection Reason') }} <span class="text-danger">*</span></label>
                        <textarea name="admin_note" class="form-control form-control-modern" rows="2" placeholder="Why are you rejecting this?" required>{{ $refund->admin_note }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="label-text">
                            {{ __('Customer Note (Optional)') }}
                            <small class="text-muted">— Visible to customer</small>
                        </label>
                        <textarea name="customer_note" class="form-control form-control-modern" rows="2"
                                  placeholder="Add a note for the customer (e.g., reason for rejection)...">{{ $refund->customer_note }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger px-4"> {{ __('Reject') }} </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"> {{ __('Process Payment') }} </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.refunds.process', $refund->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="label-text"> {{ __('Transaction ID') }} <span class="text-danger">*</span></label>
                        <input type="text" name="transaction_id" class="form-control form-control-modern" required placeholder="TRX-12345678" value="{{ $refund->transaction_id }}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="label-text"> {{ __('Method') }} <span class="text-danger">*</span></label>
                            <select name="refund_method" class="form-select form-control-modern" required>
                                <option value="bkash" {{ $refund->refund_method == 'bkash' ? 'selected' : '' }}> {{ __('bKash') }} </option>
                                <option value="nagad" {{ $refund->refund_method == 'nagad' ? 'selected' : '' }}> {{ __('Nagad') }} </option>
                                <option value="bank" {{ $refund->refund_method == 'bank' ? 'selected' : '' }}> {{ __('Bank Transfer') }} </option>
                                <option value="manual" {{ $refund->refund_method == 'manual' ? 'selected' : '' }}> {{ __('Cash') }} </option>
                                <option value="original_payment" {{ $refund->refund_method == 'original_payment' ? 'selected' : '' }}> {{ __('Original') }} </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label-text">{{ __('Amount') }}</label>
                            <input type="text" class="form-control form-control-modern bg-light" value="৳{{ number_format($refund->totalRefundAmount(), 2) }}" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="label-text"> {{ __('Sent To (Account)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="refund_account" class="form-control form-control-modern" required value="{{ $refund->refund_account }}">
                    </div>

                    <div class="mb-3">
                        <label class="label-text"> {{ __('Account Holder Name') }} </label>
                        <input type="text" name="refund_account_name" class="form-control form-control-modern" value="{{ $refund->refund_account_name }}">
                    </div>

                    {{-- Admin Note --}}
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold"> {{ __('Processing Note (Optional)') }} </label>
                        <textarea name="admin_note" class="form-control form-control-modern" rows="2"
                                  placeholder="Add a note about this payment..."></textarea>
                    </div>

                    {{-- Customer Note --}}
                    <div class="form-group">
                        <label class="form-label fw-bold">
                            {{ __('Customer Note (Optional)') }}
                            <small class="text-muted fw-normal">— Visible to customer</small>
                        </label>
                        <textarea name="customer_note" class="form-control form-control-modern" rows="2"
                                  placeholder="Add a note for the customer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4"> {{ __('Complete Payment') }} </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection