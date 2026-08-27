@extends('backEnd.layouts.master')
@section('title', 'Duplicate Orders')

@section('content')

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .pro-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        background: #fff;
        overflow: hidden;
    }
    .table-pro thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 1px solid #e9ecef;
        padding: 15px;
    }
    .table-pro tbody td {
        padding: 15px;
        vertical-align: middle;
        color: #495057;
        font-size: 14px;
        border-bottom: 1px solid #f1f1f1;
    }
    .dup-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }
    .dup-high { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .dup-low  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .custom-paginate { margin-top: 20px; text-align: right; }
</style>

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <h4 class="fw-bold mb-1 text-dark"> {{ __('Duplicate Orders') }} </h4>
            <small class="text-muted"> {{ __('Orders flagged as duplicate by the fraud detection system') }} </small>
        </div>
        <a href="{{ route('manualDuplicateOrder.page') }}" class="btn btn-outline-primary btn-sm">
            <i class="fe-search me-1"></i> {{ __('Manual Check') }}
        </a>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('admin.all_duplicate_orders') }}" class="mb-3">
        <div class="input-group" style="max-width:420px;">
            <input type="text" name="keyword" class="form-control" placeholder="{{ __('Search invoice / phone / name...') }}" value="{{ request('keyword') }}">
            <button class="btn btn-primary" type="submit"><i class="fe-search"></i> {{ __('Search') }}</button>
            @if(request('keyword'))
                <a href="{{ route('admin.all_duplicate_orders') }}" class="btn btn-light border">{{ __('Reset') }}</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="row">
        <div class="col-12">
            <div class="pro-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-pro mb-0">
                            <thead>
                                <tr>
                                    <th width="5%">{{ __('SL') }}</th>
                                    <th width="12%">{{ __('Invoice') }}</th>
                                    <th width="18%">{{ __('Customer') }}</th>
                                    <th width="13%">{{ __('Phone') }}</th>
                                    <th width="10%">{{ __('Dup. Count') }}</th>
                                    <th width="8%">{{ __('Rate') }}</th>
                                    <th width="13%">{{ __('Last Duplicate') }}</th>
                                    <th width="11%">{{ __('Order Date') }}</th>
                                    <th width="10%" class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($show_data as $key => $order)
                                <tr>
                                    <td>{{ $show_data->firstItem() + $key }}</td>
                                    <td>
                                        <span class="fw-bold text-dark">{{ $order->invoice_id }}</span>
                                    </td>
                                    <td>{{ $order->customer->name ?? ($order->shipping->name ?? '—') }}</td>
                                    <td>{{ $order->shipping->phone ?? $order->customer->phone ?? '—' }}</td>
                                    <td>
                                        <span class="dup-badge {{ ($order->duplicate_order_count ?? 0) >= 3 ? 'dup-high' : 'dup-low' }}">
                                            {{ $order->duplicate_order_count ?? 0 }}
                                        </span>
                                    </td>
                                    <td>{{ !is_null($order->duplicate_order_rate) ? $order->duplicate_order_rate . '%' : '—' }}</td>
                                    <td>{{ $order->last_duplicate_order_date ? \Carbon\Carbon::parse($order->last_duplicate_order_date)->format('d M Y') : '—' }}</td>
                                    <td>{{ $order->created_at ? $order->created_at->format('d M Y') : '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.order.invoice', $order->invoice_id) }}" class="btn btn-outline-primary btn-sm px-2" title="{{ __('View Invoice') }}">
                                            <i class="fe-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="fe-shield font-size-24 mb-2 d-block"></i>
                                        <p class="mb-0"> {{ __('No duplicate orders found.') }} </p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="custom-paginate">
        {{ $show_data->links('pagination::bootstrap-4') }}
    </div>
</div>

@endsection
