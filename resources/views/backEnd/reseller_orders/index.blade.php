@extends('backEnd.layouts.master')
@section('title', 'Reseller Orders')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Reseller Orders ({{ $orders->total() }})</h4>
                <p class="text-muted mb-0"><small><i class="fe-info"></i> Only incomplete/pending orders are shown here. Completed orders appear in the main order list.</small></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reseller-orders.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Invoice ID, Customer, Reseller..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                @foreach($orderStatuses as $status)
                                    <option value="{{ $status->id }}" {{ request('status') == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reseller</label>
                            <select name="reseller_id" class="form-select">
                                <option value="">All Resellers</option>
                                @foreach($resellers as $reseller)
                                    <option value="{{ $reseller->id }}" {{ request('reseller_id') == $reseller->id ? 'selected' : '' }}>
                                        {{ $reseller->name }} ({{ $reseller->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                                <a href="{{ route('admin.reseller-orders.index') }}" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="bulkStatusForm" method="POST" action="{{ route('admin.reseller-orders.bulk-update-status') }}">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Bulk Status Change</label>
                                <select name="order_status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    @foreach($orderStatuses as $status)
                                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary" id="bulkUpdateBtn" disabled>
                                    <i class="fe-settings"></i> Update Selected Orders
                                </button>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="text-muted" id="selectedCount">0 orders selected</span>
                            </div>
                        </div>
                        <input type="hidden" name="order_ids" id="selectedOrderIds">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th style="width:2%;">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>SL</th>
                                    <th>Invoice ID</th>
                                    <th>Date</th>
                                    <th>Reseller</th>
                                    <th>Customer</th>
                                    <th>Products</th>
                                    <th>Amount</th>
                                    <th>Reseller Profit</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input order-checkbox" value="{{ $order->id }}">
                                    </td>
                                    <td>{{ $loop->iteration + ($orders->currentPage() - 1) * $orders->perPage() }}</td>
                                    <td><strong>#{{ $order->invoice_id }}</strong></td>
                                    <td>
                                        {{ $order->created_at->format('d M, Y') }}<br>
                                        <small class="text-muted">{{ $order->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        @if($order->user)
                                            <strong>{{ $order->user->name }}</strong><br>
                                            <small class="text-muted">{{ $order->user->email }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $order->customer->name ?? 'N/A' }}</strong><br>
                                        <small class="text-muted">{{ $order->customer->phone ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @if($order->orderdetails && $order->orderdetails->count() > 0)
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($order->orderdetails->take(3) as $detail)
                                                    @php
                                                        $productImage = null;
                                                        if ($detail->product && $detail->product->image) {
                                                            $productImage = $detail->product->image->image;
                                                        } elseif ($detail->image) {
                                                            $productImage = $detail->image->image;
                                                        }
                                                    @endphp
                                                    @if($productImage)
                                                        <img src="{{ asset($productImage) }}" 
                                                             alt="{{ $detail->product_name }}" 
                                                             style="width: 35px; height: 35px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;"
                                                             title="{{ $detail->product_name }} (x{{ $detail->qty }})">
                                                    @else
                                                        <div style="width: 35px; height: 35px; background: #f1f5f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                                                            <i class="fe-package" style="font-size: 0.7rem; color: #94a3b8;"></i>
                                                        </div>
                                                    @endif
                                                @endforeach
                                                @if($order->orderdetails->count() > 3)
                                                    <div style="width: 35px; height: 35px; background: #e2e8f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold; color: #64748b;">
                                                        +{{ $order->orderdetails->count() - 3 }}
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">No products</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>৳{{ number_format($order->customer_payable_amount ?? $order->amount, 0) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">৳{{ number_format($order->reseller_profit ?? 0, 0) }}</span>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.reseller-orders.update-status') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                                            <select name="order_status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 150px;">
                                                @foreach($orderStatuses as $status)
                                                    <option value="{{ $status->id }}" {{ $order->order_status == $status->id ? 'selected' : '' }}>
                                                        {{ $status->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="button-list">
                                            <a href="{{ route('admin.order.invoice', ['invoice_id' => $order->invoice_id]) }}" 
                                               class="btn btn-sm btn-info" title="Invoice">
                                                <i class="fe-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.order.process', ['invoice_id' => $order->invoice_id]) }}" 
                                               class="btn btn-sm btn-primary" title="Process">
                                                <i class="fe-settings"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fe-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="mt-2">No reseller orders found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

           <style>
    /* কন্টেইনার স্টাইল */
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 30px;
        margin-bottom: 30px;
    }

    /* মূল লিস্ট স্টাইল */
    .modern-pagination {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0;
        gap: 10px; /* বাটনগুলোর মাঝের গ্যাপ */
        align-items: center;
    }

    /* সাধারণ লিংকের ডিজাইন */
    .modern-page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 15px;
        border-radius: 50px; /* সম্পূর্ণ গোল বাটন */
        background-color: #fff;
        color: #555;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); /* হালকা শ্যাডো */
        border: 1px solid #eee;
        transition: all 0.3s ease;
    }

    /* হোভার ইফেক্ট (মাউস নিলে যা হবে) */
    .modern-page-link:hover {
        background-color: #f8f9fa;
        color: #333;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }

    /* একটিভ পেজ (বর্তমানে যে পেজে আছেন) */
    .modern-page-item.active .modern-page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* সুন্দর ব্লু-পার্পল গ্রাডিয়েন্ট */
        color: white;
        border: none;
        box-shadow: 0 4px 10px rgba(118, 75, 162, 0.4);
    }

    /* ডিজেবল বাটন (Next/Prev যখন কাজ করবে না) */
    .modern-page-item.disabled .modern-page-link {
        background-color: #f1f1f1;
        color: #ccc;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
    
    /* আইকনের সাইজ ঠিক রাখা */
    .modern-page-link svg, .modern-page-link i {
        font-size: 12px;
    }
</style>

@if ($orders->hasPages())
<div class="pagination-wrapper">
    <ul class="modern-pagination">
        
        {{-- Previous Button --}}
        @if ($orders->onFirstPage())
            <li class="modern-page-item disabled">
                <span class="modern-page-link">
                    <span>&#8592; Prev</span> </span>
            </li>
        @else
            <li class="modern-page-item">
                <a class="modern-page-link" href="{{ $orders->previousPageUrl() }}" rel="prev">
                    <span>&#8592; Prev</span>
                </a>
            </li>
        @endif

        {{-- Page Numbers (Logic to show surrounding pages) --}}
        @foreach(range(1, $orders->lastPage()) as $i)
            @if($i >= $orders->currentPage() - 2 && $i <= $orders->currentPage() + 2)
                @if ($i == $orders->currentPage())
                    <li class="modern-page-item active">
                        <span class="modern-page-link">{{ $i }}</span>
                    </li>
                @else
                    <li class="modern-page-item">
                        <a class="modern-page-link" href="{{ $orders->url($i) }}">{{ $i }}</a>
                    </li>
                @endif
            @endif
        @endforeach

        {{-- Next Button --}}
        @if ($orders->hasMorePages())
            <li class="modern-page-item">
                <a class="modern-page-link" href="{{ $orders->nextPageUrl() }}" rel="next">
                    <span>Next &#8594;</span> </a>
            </li>
        @else
            <li class="modern-page-item disabled">
                <span class="modern-page-link">
                    <span>Next &#8594;</span>
                </span>
            </li>
        @endif

    </ul>
</div>
@endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedOrders();
    });

    // Individual Checkbox
    document.querySelectorAll('.order-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedOrders();
            // Update select all checkbox
            const allChecked = document.querySelectorAll('.order-checkbox:checked').length === document.querySelectorAll('.order-checkbox').length;
            document.getElementById('selectAll').checked = allChecked;
        });
    });

    // Update selected orders
    function updateSelectedOrders() {
        const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
        const count = selected.length;
        
        document.getElementById('selectedCount').textContent = count + ' orders selected';
        document.getElementById('selectedOrderIds').value = JSON.stringify(selected);
        document.getElementById('bulkUpdateBtn').disabled = count === 0;
    }

    // Bulk form submission
    document.getElementById('bulkStatusForm').addEventListener('submit', function(e) {
        const selected = JSON.parse(document.getElementById('selectedOrderIds').value || '[]');
        if (selected.length === 0) {
            e.preventDefault();
            alert('Please select at least one order');
            return false;
        }
        
        // Add order_ids to form
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order_ids[]';
            input.value = id;
            this.appendChild(input);
        });
    });
</script>
@endpush
@endsection
