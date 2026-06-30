@extends('backEnd.layouts.master')
@section('title','Purchase {{ __('{{ __('Inv') }}oice') }}')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h4 class="mb-0">Purchase {{ __('{{ __('Inv') }}oice') }} #{{ $purchase->invoice_no }}</h4>
            <a href="javascript:window.print()" class="btn btn-sm btn-primary">{{ __('Print') }}</a>
        </div>
        <div class="card-body">

            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>{{ __('Supplier') }}:</h5>
                    <p class="mb-0"><strong>{{ optional($purchase->supplier)->name }}</strong></p>
                    <p class="mb-0">{{ optional($purchase->supplier)->{{ __('phone') }} }}</p>
                    <p class="mb-0">{{ optional($purchase->supplier)->address }}</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0"><strong>{{ __('Date') }}:</strong> {{ $purchase->purchase_date }}</p>
                    <p class="mb-0"><strong>{{ __('{{ __('Inv') }}oice') }}:</strong> {{ $purchase->invoice_no }}</p>
                </div>
            </div>

            <table class="table table-bordered">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Product') }}</th>
                    <th class="text-end">{{ __('Qty') }}</th>
                    <th class="text-end">{{ __('Unit Cost') }}</th>
                    <th class="text-end">{{ __('Line {{ __('Total') }}') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($purchase->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ optional($item->product)->name }}</td>
                        <td class="text-end">{{ $item->qty }}</td>
                        <td class="text-end">{{ number_format($item->unit_cost,2) }}</td>
                        <td class="text-end">{{ number_format($item->line_{{ __('total') }},2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="row justify-content-end">
                <div class="col-md-4">
                    <table class="table table-sm">
                        <tr>
                            <th>{{ __('Sub{{ __('total') }}') }}</th>
                            <td class="text-end">{{ number_format($purchase->sub{{ __('total') }},2) }} ৳</td>
                        </tr>
                        <tr>
                            <th>{{ __('Discount') }}</th>
                            <td class="text-end">{{ number_format($purchase->discount,2) }} ৳</td>
                        </tr>
                        <tr>
                            <th>{{ __('Shipping') }}</th>
                            <td class="text-end">{{ number_format($purchase->shipping_cost,2) }} ৳</td>
                        </tr>
                        <tr>
                            <th>{{ __('Grand {{ __('Total') }}') }}</th>
                            <td class="text-end"><strong>{{ number_format($purchase->grand_{{ __('total') }},2) }} ৳</strong></td>
                        </tr>
                        <tr>
                            <th>{{ __('Paid') }}</th>
                            <td class="text-end text-success">{{ number_format($purchase->paid_amount,2) }} ৳</td>
                        </tr>
                        <tr>
                            <th>{{ __('Due') }}</th>
                            <td class="text-end text-danger">{{ number_format($purchase->due_amount,2) }} ৳</td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
