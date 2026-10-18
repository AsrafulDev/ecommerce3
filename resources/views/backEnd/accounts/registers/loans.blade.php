@extends('backEnd.layouts.master')
@section('title', 'Loans')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Loans</h3>
            <p class="text-muted mb-0">Money borrowed from or lent to a party or employee. Each amount moves cash on the funds ledger.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.accounts.reports.show', 'general-loan-balance') }}">Loan balance</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.accounts.reports.show', 'general-loan-ledger') }}">Loan ledger</a>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card mb-3">
        <div class="card-header bg-white"><strong>New loan</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.accounts.loans.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label small mb-1">Kind *</label>
                    <select name="kind" class="form-select form-select-sm" required>
                        <option value="general">General</option>
                        <option value="employee">Employee loan</option>
                        <option value="expense_advance">Employee expense advance</option>
                        <option value="salary_advance">Employee salary advance</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Direction *</label>
                    <select name="direction" class="form-select form-select-sm" required>
                        <option value="taken">We borrowed (cash in)</option>
                        <option value="given">We lent (cash out)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Party name (general)</label>
                    <input type="text" name="party_name" value="{{ old('party_name') }}" maxlength="150" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Employee (employee loans)</label>
                    <select name="employee_id" class="form-select form-select-sm">
                        <option value="">—</option>
                        @foreach ($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Amount *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Date *</label>
                    <input type="date" name="loan_date" value="{{ old('loan_date', now()->toDateString()) }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Note</label>
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="255" class="form-control form-control-sm">
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary">Record loan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Loan register</strong>
            <form method="GET" class="d-flex gap-1">
                <select name="kind" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All kinds</option>
                    <option value="general" @selected($kind === 'general')>General</option>
                    <option value="employee" @selected($kind === 'employee')>Employee loan</option>
                    <option value="expense_advance" @selected($kind === 'expense_advance')>Expense advance</option>
                    <option value="salary_advance" @selected($kind === 'salary_advance')>Salary advance</option>
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Date</th>
                        <th>Party</th>
                        <th>Kind</th>
                        <th>Direction</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-end">Repay / collect</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                    <tr>
                        <td>{{ $loan->code }}</td>
                        <td>{{ $loan->loan_date->format('d M Y') }}</td>
                        <td>{{ $loan->party_name }}</td>
                        <td>{{ ucfirst($loan->kind) }}</td>
                        <td>{{ $loan->direction === 'taken' ? 'Borrowed' : 'Lent' }}</td>
                        <td class="text-end">{{ number_format((float) $loan->amount, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($loan->outstanding(), 2) }}</td>
                        <td class="text-end">
                            @if ($loan->outstanding() > 0)
                            <form method="POST" action="{{ route('admin.accounts.loans.repay', $loan) }}" class="d-flex gap-1 justify-content-end">
                                @csrf
                                <input type="number" step="0.01" min="0.01" max="{{ $loan->outstanding() }}" name="amount" class="form-control form-control-sm text-end" style="width:110px;" required>
                                <input type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control form-control-sm" style="width:140px;" required>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                            @else
                            <span class="badge bg-success">Settled</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No loans recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
