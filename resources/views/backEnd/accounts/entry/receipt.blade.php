@extends('backEnd.layouts.master')
@section('title', 'Receipt Entry')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Receipt Entry</h3>
            <p class="text-muted mb-0">Money received from a customer, set against their open invoices.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounts.reports.show', 'receipt-record') }}">Receipt record</a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small mb-1">Customer *</label>
                <select name="customer_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Select customer…</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" @selected($customer && $customer->id === $c->id)>{{ $c->name }}{{ $c->phone ? ' — '.$c->phone : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-sm btn-outline-primary">Show invoices</button>
            </div>
        </div>
    </form>

    @if ($customer)
    <form method="POST" action="{{ route('admin.accounts.entry.receipt.store') }}" class="card">
        @csrf
        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Open invoices for {{ $customer->name }}</strong>
            <span class="small text-muted">{{ $orders->count() }} open</span>
        </div>

        @if ($orders->isEmpty())
            <div class="card-body text-center text-muted py-4">No open invoices for this customer.</div>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th class="text-end">Invoice Amount</th>
                        <th class="text-end">Due</th>
                        <th style="width:130px;" class="text-end">Receive now</th>
                        <th style="width:110px;" class="text-end">Discount</th>
                        <th style="width:110px;" class="text-end">VAT</th>
                        <th style="width:110px;" class="text-end">AIT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->invoice_id ?: '#'.$order->id }}</td>
                        <td>{{ optional($order->created_at)->format('d M Y') }}</td>
                        <td class="text-end">{{ number_format((float) $order->amount, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $order->due_amount, 2) }}</td>
                        <td>
                            <input type="number" step="0.01" min="0" max="{{ $order->due_amount }}"
                                   name="amounts[{{ $order->id }}]" value="{{ old('amounts.'.$order->id) }}"
                                   class="form-control form-control-sm text-end">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="discounts[{{ $order->id }}]" value="{{ old('discounts.'.$order->id) }}"
                                   class="form-control form-control-sm text-end">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="vats[{{ $order->id }}]" value="{{ old('vats.'.$order->id) }}"
                                   class="form-control form-control-sm text-end">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="aits[{{ $order->id }}]" value="{{ old('aits.'.$order->id) }}"
                                   class="form-control form-control-sm text-end">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Date *</label>
                    <input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Method *</label>
                    <select name="method" class="form-select form-select-sm" required>
                        @foreach ($methods as $method)
                            <option value="{{ $method }}" @selected(old('method') === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-1">Note</label>
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="255" class="form-control form-control-sm">
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary">Save receipt</button>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2">Discount, VAT and AIT reduce the invoice due but are not cash. For each invoice, cash plus adjustments must not exceed its due. Nothing is booked if any line is wrong.</p>
        </div>
        @endif
    </form>
    @endif
</div>
@endsection
