@extends('backEnd.layouts.master')

@section('title', __('Chart of Accounts'))

@section('content')
@php use Softmit\DoubleEntry\Support\Money; @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ __('Chart of Accounts') }}</h3>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">{{ count($rows) }} {{ __('accounts') }}</span>
            @can('accounting-create')
            <form method="POST" action="{{ route('admin.accounting.accounts.sync-defaults') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary"
                        title="{{ __('Create any missing default accounts and map the default fund. Existing accounts are left untouched.') }}">
                    <i data-feather="refresh-cw"></i> {{ __('Sync Defaults') }}
                </button>
            </form>
            @endcan
        </div>
    </div>

    @if ($missingRoles)
        <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>
                <strong>{{ __('Posting will fail for these roles — no active account is assigned:') }}</strong>
                {{ implode(', ', $missingRoles) }}
            </span>
            @can('accounting-create')
            <form method="POST" action="{{ route('admin.accounting.accounts.sync-defaults') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning">{{ __('Sync Defaults now') }}</button>
            </form>
            @endcan
        </div>
    @endif

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label mb-1 small text-muted">{{ __('Balances as at') }}</label>
            <input type="date" name="as_at" value="{{ $asAt }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-primary">{{ __('Apply') }}</button>
            <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-sm btn-light">{{ __('Up to today') }}</a>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Account') }}</th>
                        <th>{{ __('Class') }}</th>
                        <th>{{ __('Role used by posting code') }}</th>
                        <th class="text-end">{{ __('Total Debit') }}</th>
                        <th class="text-end">{{ __('Total Credit') }}</th>
                        <th class="text-end">{{ __('Balance') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="{{ $row['account']->is_active ? '' : 'text-muted' }}">
                            <td>{{ $row['account']->code }}</td>
                            <td>
                                <a href="{{ route('admin.accounting.ledger.account', $row['account']->id) }}">
                                    {{ $row['account']->name }}
                                </a>
                                @if ($row['account']->is_system)
                                    <span class="badge bg-light text-dark border">system</span>
                                @endif
                            </td>
                            <td class="small">{{ $row['account']->account_type->label() }}</td>
                            <td><code class="small">{{ $row['account']->role ?: '—' }}</code></td>
                            <td class="text-end">{{ Money::format($row['debit']) }}</td>
                            <td class="text-end">{{ Money::format($row['credit']) }}</td>
                            <td class="text-end fw-bold">{{ Money::format($row['balance']) }}</td>
                            <td>
                                <span class="badge bg-{{ $row['account']->is_active ? 'success' : 'secondary' }}">
                                    {{ $row['account']->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td>
                                @can('accounting-edit')
                                    <a href="{{ route('admin.accounting.accounts.edit', $row['account']->id) }}" class="btn btn-sm btn-link p-0">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="6">{{ __('Balances by normal side') }}</th>
                        <th class="text-end">{{ Money::format($totals['debit']) }} {{ __('Dr') }}</th>
                        <th class="text-end">{{ Money::format($totals['credit']) }} {{ __('Cr') }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-2">
        {{ __('Balances are calculated from posted journal lines every time this page loads — nothing is stored, so nothing can drift out of step with the ledger.') }}
    </p>
</div>
@endsection
