@extends('backEnd.layouts.master')
@section('title', 'Chart of Accounts')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Chart of Accounts</h3>
            <p class="text-muted mb-0">{{ $total }} accounts, nested under their parent. Read-only: edit accounts on the Accounts screen.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.accounts.index') }}">Accounts</a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card mb-3">
        <div class="card-header bg-white"><strong>Add an account</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.accounting.chart.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label small mb-1">Code *</label>
                    <input type="text" name="code" value="{{ old('code') }}" maxlength="20" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="150" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Type *</label>
                    <select name="account_type" class="form-select form-select-sm" required>
                        @foreach (\Softmit\DoubleEntry\Enums\AccountType::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('account_type') === $case->value)>{{ ucwords(str_replace('_', ' ', $case->value)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Normal balance *</label>
                    <select name="normal_balance" class="form-select form-select-sm" required>
                        @foreach (\Softmit\DoubleEntry\Enums\NormalBalance::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('normal_balance') === $case->value)>{{ ucfirst($case->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary">Add account</button>
                </div>
            </form>
            <p class="small text-muted mb-0 mt-2">Opening balances are not set here. Enter them on the Opening Balances screen so they are booked once, with its checks.</p>
        </div>
    </div>

    @forelse ($groups as $type => $roots)
    <div class="card mb-3">
        <div class="card-header bg-white"><strong>{{ ucwords(str_replace('_', ' ', $type)) }}</strong></div>
        <ul class="list-unstyled mb-0 p-3">
            @foreach ($roots as $node)
                @include('backEnd.accounting.accounts.chart-node', ['node' => $node, 'depth' => 0])
            @endforeach
        </ul>
    </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-4">No accounts yet. Set up the chart from the Accounts screen.</div></div>
    @endforelse
</div>
@endsection
