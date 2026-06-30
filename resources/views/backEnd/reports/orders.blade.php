@extends('backEnd.layouts.master')
@section('title', '{{ __('Order Report') }}')

{{-- CSS Section --}}
@section('css')
<style>
    /* --- Modern Card --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #fff;
        margin-bottom: 24px;
        transition: transform 0.2s;
    }
    
    /* --- Filter Section --- */
    .{{ __('filter') }}-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .form-label-custom {
        font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 5px;
    }
    .form-control-custom, .form-select-custom {
        border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; font-size: 0.9rem;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* --- Stat Cards --- */
    .stat-card {
        display: flex; align-items: center; padding: 20px;
        background: #fff; border-radius: 12px; border: 1px solid #f1f5f9;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .stat-icon {
        width: 50px; height: 50px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; margin-right: 15px;
    }
    .bg-light-primary { background: #e0e7ff; color: #4338ca; }
    .bg-light-success { background: #dcfce7; color: #166534; }
    .bg-light-warning { background: #fef3c7; color: #b45309; }
    .bg-light-info { background: #e0f2fe; color: #0369a1; }
    
    .stat-label { font-size: 0.85rem; color: #64748b; font-weight: 500; margin-bottom: 2px; }
    .stat-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }

    /* --- Table Styles --- */
    .table-modern th {
        background-color: #f8fafc; color: #475569; font-size: 0.75rem;
        font-weight: 700; text-transform: uppercase; padding: 1rem; border-bottom: 1px solid #e2e8f0;
    }
    .table-modern td {
        padding: 1rem; vertical-align: middle; font-size: 0.875rem; color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:hover td { background-color: #f8fafc; }

    /* --- Utilities --- */
    .btn-custom-primary { background: #4f46e5; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; }
    .btn-custom-primary:hover { background: #4338ca; color: #fff; }
    .btn-custom-outline { background: transparent; border: 1px solid #10b981; color: #10b981; padding: 10px 20px; border-radius: 8px; font-weight: 600; }
    .btn-custom-outline:hover { background: #10b981; color: #fff; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="bar-chart-2" class="text-primary me-2"></i> {{ __('Order Report') }}s
            </h4>
            <p class="text-muted small mb-0">{{ __('Analysis for') }}: <strong>{{ $label }}</strong></p>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="{{ __('filter') }}-card">
        <form method="{{ __('GET') }}" action="{{ route('admin.reports.orders') }}" id="order-{{ __('filter') }}-form">
            <div class="row g-3 align-items-end">
                
                {{-- Report Type --}}
                <div class="col-md-3">
                    <label class="form-label-custom">{{ __('Filter By') }}</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i data-feather="{{ __('filter') }}" style="width:16px;"></i></span>
                        <select name="type" class="form-select form-select-custom border-start-0" id="report-type">
                            <option value="{{ __('today') }}" {{ $type=='{{ __('today') }}' ? 'selected' : '' }}>{{ __('Today') }}</option>
                            <option value="month" {{ $type=='month' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                            <option value="year"  {{ $type=='year'  ? 'selected' : '' }}>{{ __('Yearly') }}</option>
                            <option value="range" {{ $type=='range' ? 'selected' : '' }}>{{ __('{{ __('Custom {{ __('Date') }}') }} Range') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Dynamic Inputs --}}
                <div class="col-md-2 type-month type-year" style="display:none;">
                    <label class="form-label-custom">{{ __('Year') }}</label>
                    <input type="{{ __('number') }}" name="year" class="form-control form-control-custom" value="{{ request('year', now()->year) }}" placeholder="{{ __('YYYY') }}">
                </div>

                <div class="col-md-2 type-month" style="display:none;">
                    <label class="form-label-custom">{{ __('Month') }}</label>
                    <select name="month" class="form-select form-select-custom">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2 type-range" style="display:none;">
                    <label class="form-label-custom">{{ __('Start {{ __('Date') }}') }}</label>
                    <input type="date" name="from_date" class="form-control form-control-custom" value="{{ request('from_date') }}">
                </div>

                <div class="col-md-2 type-range" style="display:none;">
                    <label class="form-label-custom">{{ __('End {{ __('Date') }}') }}</label>
                    <input type="date" name="to_date" class="form-control form-control-custom" value="{{ request('to_date') }}">
                </div>

                {{-- {{ __('Actions') }} --}}
                <div class="col-md-auto ms-auto d-flex gap-2">
                    <button class="btn btn-custom-primary" type="{{ __('submit') }}">
                        <i data-feather="search" class="me-1" style="width:16px;"></i> Generate
                    </button>
                    <button class="btn btn-custom-outline" type="{{ __('submit') }}" name="export" value="csv" id="export-csv-btn">
                        <i data-feather="download" class="me-1" style="width:16px;"></i> CSV
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- SUMMARY STATS --}}
    <div class="row g-4 mb-4">
        {{-- {{ __('{{ __('Total') }} Orders') }} --}}
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-light-primary">
                    <i data-feather="shopping-bag"></i>
                </div>
                <div>
                    <div class="stat-label">{{ __('{{ __('Total') }} Orders') }}</div>
                    <h3 class="stat-value">{{ $totalOrders }}</h3>
                </div>
            </div>
        </div>

        {{-- {{ __('{{ __('Total') }} {{ __('Amount') }}') }} --}}
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-light-success">
                    <span class="fw-bold">৳</span>
                </div>
                <div>
                    <div class="stat-label">{{ __('Revenue') }}</div>
                    <h3 class="stat-value">{{ number_format($total{{ __('Amount') }}, 2) }}</h3>
                </div>
            </div>
        </div>

        {{-- {{ __('Total') }} {{ __('Discount') }} --}}
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-light-warning">
                    <i data-feather="tag"></i>
                </div>
                <div>
                    <div class="stat-label">{{ __('{{ __('Discount') }} Given') }}</div>
                    <h3 class="stat-value">{{ number_format($total{{ __('Discount') }}, 2) }}</h3>
                </div>
            </div>
        </div>

        {{-- {{ __('Shipping') }} --}}
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-light-info">
                    <i data-feather="truck"></i>
                </div>
                <div>
                    <div class="stat-label">{{ __('{{ __('Shipping') }} Cost') }}</div>
                    <h3 class="stat-value">{{ number_format($total{{ __('Shipping') }}, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- ORDER TABLE --}}
    <div class="card card-modern">
        <div class="card-header border-bottom bg-white py-3">
            <h5 class="mb-0 fw-bold text-dark">{{ __('Detailed Order List') }}</h5>
        </div>

        <div class="table-responsive" id="order-table">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">{{ __('{{ __('Inv') }}oice') }}</th>
                        <th width="20%">{{ __('Customer') }}</th>
                        <th width="15%" class="text-end">{{ __('{{ __('Total') }} {{ __('Amount') }}') }}</th>
                        <th width="10%" class="text-end">{{ __('{{ __('Discount') }}') }}</th>
                        <th width="10%" class="text-end">{{ __('{{ __('Shipping') }}') }}</th>
                        <th width="10%">{{ __('{{ __('Status') }}') }}</th>
                        <th width="15%">{{ __('{{ __('Date') }}') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    @php
                        $row{{ __('Total') }} = $order->amount ?? $order->{{ __('total') }} ?? $order->{{ __('total') }}_amount ?? $order->grand_{{ __('total') }} ?? $order->sub{{ __('total') }} ?? 0;
                        $row{{ __('Discount') }} = $order->discount ?? $order->discount_amount ?? $order->coupon_discount ?? 0;
                        $row{{ __('Shipping') }} = $order->shipping_amount ?? $order->shipping_charge ?? $order->shipping_cost ?? $order->shipping ?? 0;
                        
                        // {{ __('Status') }} Badge Logic
                        $status{{ __('Name') }} = is_object($order->status) ? $order->status->name : ($order->order_status ?? $order->status ?? '-');
                        $badgeClass = 'bg-secondary';
                        if(stripos($status{{ __('Name') }}, 'complete') !== false || stripos($status{{ __('Name') }}, 'delivered') !== false) $badgeClass = 'bg-success';
                        elseif(stripos($status{{ __('Name') }}, 'pending') !== false) $badgeClass = 'bg-warning text-dark';
                        elseif(stripos($status{{ __('Name') }}, 'cancel') !== false) $badgeClass = 'bg-danger';
                        elseif(stripos($status{{ __('Name') }}, 'process') !== false) $badgeClass = 'bg-info';
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $loop->iteration + ($orders->currentPage() - 1) * $orders->perPage() }}</td>
                        <td>
                            <span class="fw-bold text-primary">#{{ $order->invoice_id ?? $order->id }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width:30px; height:30px; font-size:12px; font-weight:bold; color:#64748b;">
                                    {{ substr($order->customer_name ?? ($order->customer->name ?? 'G'), 0, 1) }}
                                </div>
                                <span class="text-dark fw-medium">{{ $order->customer_name ?? ($order->customer->name ?? 'Guest') }}</span>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-dark">৳{{ number_format($row{{ __('Total') }}, 2) }}</td>
                        <td class="text-end text-muted">{{ $row{{ __('Discount') }} > 0 ? '৳'.number_format($row{{ __('Discount') }}, 2) : '-' }}</td>
                        <td class="text-end text-muted">{{ $row{{ __('Shipping') }} > 0 ? '৳'.number_format($row{{ __('Shipping') }}, 2) : 'Free' }}</td>
                        <td>
                            <span class="badge {{ $badgeClass }} rounded-pill px-2">{{ $status{{ __('Name') }} }}</span>
                        </td>
                        <td class="text-muted small">
                            {{ optional($order->created_at)->format('d M, Y') }}<br>
                            {{ optional($order->created_at)->format('h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center">
                                <img src="{{ __('https://') }}cdn-icons-png.flaticon.com/512/7486/7486744.png" width="50" class="opacity-25 mb-2">
                                <p class="text-muted fw-bold mb-0">{{ __('No orders found') }}</p>
                                <small class="text-muted">{{ __('Try changing the {{ __('filter') }} parameters.') }}</small>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            
            {{-- Pagination --}}
            <div class="p-4 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ $orders->first{{ __('Item') }}() }} to {{ $orders->last{{ __('Item') }}() }} of {{ $orders->{{ __('total') }}() }} results</small>
                <div>{{ $orders->links('pagination::bootstrap-4') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts') {{-- {{ __('Change') }}d from section('script') to push('scripts') to fix error --}}
<script>
    function toggleReportFields() {
        let type = document.getElementById('report-type').value;
        
        // Hide all specific {{ __('filter') }}s first
        document.querySelectorAll('.type-month, .type-year, .type-range').forEach(el => el.style.display = 'none');

        // Show based on selection
        if (type === 'month') {
            document.querySelectorAll('.type-month, .type-year').forEach(el => el.style.display = 'block');
        } else if (type === 'year') {
            document.querySelectorAll('.type-year').forEach(el => el.style.display = 'block');
        } else if (type === 'range') {
            document.querySelectorAll('.type-range').forEach(el => el.style.display = 'block');
        }
    }

    // Initialize toggle
    document.getElementById('report-type').addEventListener('change', toggleReportFields);
    toggleReportFields();

    // AJAX Handling
    document.getElementById('order-{{ __('filter') }}-form').addEventListener('{{ __('submit') }}', function(e){
        let {{ __('submit') }}ter = e.{{ __('submit') }}ter;
        
        // Let normal {{ __('submit') }} happen for CSV export
        if ({{ __('submit') }}ter && {{ __('submit') }}ter.id === 'export-csv-btn') {
            return true;
        }

        e.preventDefault();
        
        // Add loading state
        let btn = this.querySelector('button[type="{{ __('submit') }}"]:not(#export-csv-btn)');
        let original{{ __('Text') }} = btn.innerHTML;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i> Loading...';
        btn.disabled = true;

        loadOrders(new URLSearchParams(new FormData(this)).to{{ __('String') }}(), () => {
            btn.innerHTML = original{{ __('Text') }};
            btn.disabled = false;
        });
    });

    // Pagination Click Handling
    document.addEventListener('click', function(e){
        let link = e.target.closest('.pagination a');
        if (link) {
            e.preventDefault();
            loadOrders(link.getAttribute('href').split('?')[1] ?? '');
        }
    });

    function loadOrders(query, callback) {
        fetch("{{ route('admin.reports.orders') }}?" + query, {
            headers: { 'X-{{ __('Requested') }}-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            let temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Update Table
            let newTable = temp.querySelector('#order-table');
            if(newTable) {
                document.getElementById('order-table').innerHTML = newTable.innerHTML;
            }
            
            // {{ __('Note') }}: If you want to update {{ __('Summary') }} Cards dynamically, 
            // you'd need the backend to return JSON data or partial HTML for those cards as well.
            
            if(callback) callback();
        })
        .catch(err => {
            console.error(err);
            if(callback) callback();
        });
    }
</script>
@endpush