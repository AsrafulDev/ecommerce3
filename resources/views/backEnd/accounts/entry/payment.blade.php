@extends('backEnd.layouts.master')
@section('title', 'Payment Entry')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Payment Entry</h3>
            <p class="text-muted mb-0">Money paid to a supplier, set against their open purchases.</p>
        </div>
        <span class="badge bg-light text-dark border">Fund balance: {{ number_format((float) $fundBalance, 2) }}</span>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small mb-1">Supplier *</label>
                <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Select supplier…</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}" @selected($supplier && $supplier->id === $s->id)>{{ $s->name }}{{ $s->phone ? ' — '.$s->phone : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-sm btn-outline-primary">Show purchases</button>
            </div>
        </div>
    </form>

    @if ($supplier)
    <form method="POST" action="{{ route('admin.accounts.entry.payment.store') }}" class="card">
        @csrf
        <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Open purchases from {{ $supplier->name }}</strong>
            <span class="small text-muted">{{ $purchases->count() }} open</span>
        </div>

        @if ($purchases->isEmpty())
            <div class="card-body text-center text-muted py-4">No open purchases for this supplier.</div>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th class="text-end">Purchase Amount</th>
                        <th class="text-end">Due</th>
                        <th style="width:180px;" class="text-end">Pay now</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $purchase)
                    <tr>
                        <td>{{ $purchase->invoice_no ?: '#'.$purchase->id }}</td>
                        <td>{{ optional($purchase->purchase_date)->format('d M Y') }}</td>
                        <td class="text-end">{{ number_format((float) $purchase->grand_total, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $purchase->due_amount, 2) }}</td>
                        <td>
                            <input type="number" step="0.01" min="0" max="{{ $purchase->due_amount }}"
                                   name="amounts[{{ $purchase->id }}]" value="{{ old('amounts.'.$purchase->id) }}"
                                   class="form-control form-control-sm text-end">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Payment date *</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-1">Note</label>
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="255" class="form-control form-control-sm">
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary">Save payment</button>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2">Each amount is checked against its purchase's due, and the total against the fund balance, before anything is booked.</p>
        </div>
        @endif
    </form>
    @endif
</div>
@endsection
