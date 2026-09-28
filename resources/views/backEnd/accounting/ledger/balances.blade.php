@extends('backEnd.layouts.master')

@section('title', __('Party Balances'))

@section('content')
@php
    use Softmit\DoubleEntry\Support\Money;
    use Softmit\DoubleEntry\Support\AccountRole;
    use Illuminate\Support\Carbon;
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ __('Party Balances') }}</h3>
        <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-sm btn-light">{{ __('Journals') }}</a>
    </div>

    <div class="row mb-3">
        <div class="col-sm-6">
            <div class="card border-start-primary">
                <div class="card-body py-3">
                    <span class="small text-muted">{{ __('Owed to us (receivables)') }}</span>
                    <div class="fs-4 fw-bold">{{ Money::format($receivables) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="card border-start-danger">
                <div class="card-body py-3">
                    <span class="small text-muted">{{ __('We owe (payables)') }}</span>
                    <div class="fs-4 fw-bold">{{ Money::format($payables) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span>{{ __('Every subledger with an outstanding balance') }}</span>
            <span class="small text-muted">{{ count($rows) }} {{ __('parties') }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Party') }}</th>
                        <th>{{ __('Ledger account') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('As at') }}</th>
                        <th class="text-end">{{ __('Balance') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                @if ($row['party_type'])
                                    <a href="{{ route('admin.accounting.ledger.party', [$row['party_type']->value, $row['party_id']]) }}">
                                        {{ $row['party_name'] }}
                                    </a>
                                @else
                                    {{ $row['party_name'] }}
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.accounting.ledger.account', $row['account']->id) }}">
                                    {{ $row['account']->code }} — {{ $row['account']->name }}
                                </a>
                            </td>
                            <td class="small">{{ $row['role'] }}</td>
                            <td class="small text-muted">{{ Carbon::today()->format('d M Y') }}</td>
                            <td class="text-end fw-bold {{ Money::isNegative($row['balance']) ? 'text-danger' : '' }}">
                                {{ Money::format($row['balance']) }}
                            </td>
                            <td class="small text-muted text-nowrap">
                                @if (in_array($row['role'], [AccountRole::ACCOUNTS_PAYABLE, AccountRole::EMPLOYEE_PAYABLE], true))
                                    {{ __('We owe') }}
                                @else
                                    {{ __('Owes us') }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                {{ __('Nothing outstanding. Once sale, payment, purchase and expense journals are posted, debtors and creditors appear here automatically.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-2">
        {{ __('Balances are summed from posted journal lines at request time — there is no stored balance column to fall out of step with the ledger, which is the failure mode the legacy supplier and product stock columns already have.') }}
    </p>
</div>
@endsection
