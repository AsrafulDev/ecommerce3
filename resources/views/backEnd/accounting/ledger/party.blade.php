@extends('backEnd.layouts.master')

@section('title', __('Party statement'))

@section('content')
@php
    use Softmit\DoubleEntry\Support\Money;
    use Illuminate\Support\Carbon;
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            {{ $party?->name ?? ($partyType->label() . ' #' . $partyId) }}
            <span class="badge bg-light text-dark border align-middle">{{ $partyType->label() }}</span>
        </h3>
        <a href="{{ route('admin.accounting.ledger.balances') }}" class="btn btn-sm btn-light">{{ __('All balances') }}</a>
    </div>

    @include('backEnd.accounting.partials.period', ['reportKey' => ''])

    @if (!$statement['account'])
        <div class="alert alert-warning">
            {{ __('No receivable/payable account is assigned yet, so this statement cannot be built. Open the chart of accounts to see which roles are missing.') }}
        </div>
    @else
        <div class="row mb-3">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body py-3">
                        <span class="small text-muted">{{ __('Opening on') }} {{ $statement['account']->name }}</span>
                        <div class="fs-5 fw-bold">{{ Money::format($statement['opening']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body py-3">
                        <span class="small text-muted">{{ __('Movement') }}</span>
                        <div class="fs-5 fw-bold">{{ Money::format(Money::subtract($statement['closing'], $statement['opening'])) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body py-3">
                        <span class="small text-muted">{{ __('Outstanding') }}</span>
                        <div class="fs-5 fw-bold">{{ Money::format($statement['closing']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($statement['truncated'])
            <div class="alert alert-warning">
                {{ __('This statement is longer than the rows shown. Narrow the period for a complete one.') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header bg-light">
                {{ $statement['account']->code }} — {{ $statement['account']->name }}
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Journal') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Source') }}</th>
                            <th class="text-end">{{ __('Debit') }}</th>
                            <th class="text-end">{{ __('Credit') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td colspan="6" class="text-muted small">{{ __('Brought forward') }}</td>
                            <td class="text-end fw-bold">{{ Money::format($statement['opening']) }}</td>
                        </tr>
                        @foreach ($statement['rows']->items() as $row)
                            <tr>
                                <td class="small text-nowrap">{{ Carbon::parse($row['transaction_date'])->format('d M Y') }}</td>
                                <td><a href="{{ route('admin.accounting.journals.show', $row['journal_id']) }}">{{ $row['journal_no'] }}</a></td>
                                <td class="small">{{ $row['description'] ?: '—' }}</td>
                                <td class="small text-muted">
                                    @if ($row['source_type'])
                                        {{ $row['source_type'] }}@if($row['source_id']) #{{ $row['source_id'] }}@endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">{{ Money::isPositive($row['debit']) ? Money::format($row['debit']) : '' }}</td>
                                <td class="text-end">{{ Money::isPositive($row['credit']) ? Money::format($row['credit']) : '' }}</td>
                                <td class="text-end fw-bold">{{ Money::format($row['balance']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6">{{ __('Carried forward') }}</th>
                            <th class="text-end">{{ Money::format($statement['closing']) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-2">{{ $statement['rows']->links() }}</div>

        <p class="text-muted small mt-2">
            {{ __('A statement is built from the subledger account tagged with this party. The cash leg of their payments carries the same party trace but is deliberately not part of their balance.') }}
        </p>
    @endif
</div>
@endsection
