@extends('backEnd.layouts.master')

@section('title', $account->name . ' — ' . __('Ledger'))

@section('content')
@php
    use Softmit\DoubleEntry\Support\Money;
    use Illuminate\Support\Carbon;
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            {{ $account->code }} — {{ $account->name }}
            <span class="badge bg-light text-dark border align-middle">{{ $account->account_type->label() }}</span>
        </h3>
        <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-sm btn-light">{{ __('Chart of accounts') }}</a>
    </div>

    @include('backEnd.accounting.partials.period', ['reportKey' => ''])

    <div class="row mb-3">
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body py-3">
                    <span class="small text-muted">{{ __('Opening') }}</span>
                    <div class="fs-5 fw-bold">{{ Money::format($ledger['opening']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body py-3">
                    <span class="small text-muted">{{ __('Movement') }}</span>
                    <div class="fs-5 fw-bold">{{ Money::format(Money::subtract($ledger['closing'], $ledger['opening'])) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body py-3">
                    <span class="small text-muted">{{ __('Closing') }}</span>
                    <div class="fs-5 fw-bold">{{ Money::format($ledger['closing']) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($ledger['truncated'])
        <div class="alert alert-warning">
            {{ __('Only the newest') }} {{ number_format(\Softmit\DoubleEntry\Services\LedgerService::MAX_ROWS) }} {{ __('lines of this period are shown. Narrow the period for a complete statement.') }}
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Journal') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Source') }}</th>
                        <th>{{ __('Actor') }}</th>
                        <th class="text-end">{{ __('Debit') }}</th>
                        <th class="text-end">{{ __('Credit') }}</th>
                        <th class="text-end">{{ __('Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-light">
                        <td colspan="7" class="text-muted small">{{ __('Brought forward') }}</td>
                        <td class="text-end fw-bold">{{ Money::format($ledger['opening']) }}</td>
                    </tr>
                    @foreach ($ledger['rows']->items() as $row)
                        <tr>
                            <td class="small text-nowrap">{{ Carbon::parse($row['transaction_date'])->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('admin.accounting.journals.show', $row['journal_id']) }}">{{ $row['journal_no'] }}</a>
                                @if ($row['status'] === \Softmit\DoubleEntry\Enums\JournalStatus::REVERSED->value)
                                    <span class="badge bg-warning text-dark">{{ __('Reversed') }}</span>
                                @endif
                            </td>
                            <td class="small">{{ $row['description'] ?: '—' }}</td>
                            <td class="small text-muted">
                                @if ($row['source_type'])
                                    {{ $row['source_type'] }}@if($row['source_id']) #{{ $row['source_id'] }}@endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small text-muted">{{ $row['posted_by'] ? $actors->name((int) $row['posted_by']) : $actors->name($row['created_by'] ? (int) $row['created_by'] : null) }}</td>
                            <td class="text-end">{{ Money::isPositive($row['debit']) ? Money::format($row['debit']) : '' }}</td>
                            <td class="text-end">{{ Money::isPositive($row['credit']) ? Money::format($row['credit']) : '' }}</td>
                            <td class="text-end fw-bold">{{ Money::format($row['balance']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="7">{{ __('Carried forward') }}</th>
                        <th class="text-end">{{ Money::format($ledger['closing']) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-2">{{ $ledger['rows']->links() }}</div>

    <p class="text-muted small mt-2">
        {{ __('Posted and reversed journals only. Drafts are excluded, and the running balance restarts from the opening figure for the period you chose.') }}
    </p>
</div>
@endsection
