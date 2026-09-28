@extends('backEnd.layouts.master')

@section('title', __('Cash Ledger'))

@section('content')
@php use Softmit\DoubleEntry\Support\Money; @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ __('Cash & Bank by Fund') }}</h3>
        <div class="text-end">
            <span class="small text-muted d-block">{{ __('Total cash on hand up to') }} {{ $to }}</span>
            <span class="fs-5 fw-bold">{{ Money::format($total) }}</span>
        </div>
    </div>

    @include('backEnd.accounting.partials.period', ['reportKey' => 'cash'])

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Fund') }}</th>
                        <th>{{ __('Ledger account') }}</th>
                        <th class="text-end">{{ __('Opening') }}</th>
                        <th class="text-end">{{ __('In') }}</th>
                        <th class="text-end">{{ __('Out') }}</th>
                        <th class="text-end">{{ __('Closing') }}</th>
                        <th>{{ __('Reconciling') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($positions as $fundKey => $position)
                        <tr>
                            <td>
                                <a href="{{ route('admin.accounting.ledger.account', $position['account_id']) }}">{{ $position['label'] }}</a>
                                <span class="small text-muted">({{ $fundKey }})</span>
                            </td>
                            <td class="small">{{ $position['account_code'] }} — {{ $position['account_name'] }}</td>
                            <td class="text-end">{{ Money::format($position['opening']) }}</td>
                            <td class="text-end text-success">{{ Money::format($position['in']) }}</td>
                            <td class="text-end text-danger">{{ Money::format($position['out']) }}</td>
                            <td class="text-end fw-bold {{ Money::isNegative($position['closing']) ? 'text-danger' : '' }}">
                                {{ Money::format($position['closing']) }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $position['is_reconciling'] ? 'success' : 'secondary' }}">
                                    {{ $position['is_reconciling'] ? __('Yes') : __('No') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                {{ __('No fund is mapped to an accounting account yet. Map one in the chart of accounts before trusting a cash figure.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2">{{ __('Totals (mapped funds)') }}</th>
                        <th class="text-end">{{ Money::format(Money::sum(array_column($positions, 'opening'))) }}</th>
                        <th class="text-end">{{ Money::format(Money::sum(array_column($positions, 'in'))) }}</th>
                        <th class="text-end">{{ Money::format(Money::sum(array_column($positions, 'out'))) }}</th>
                        <th class="text-end">{{ Money::format(Money::sum(array_column($positions, 'closing'))) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="alert alert-light border mt-3 small">
        <strong>{{ __('Why this differs from the fund page:') }}</strong>
        {{ __('These figures come only from posted journals on cash, bank and other-fund accounts. Money a customer still owes is not cash, so it cannot be spent — the legacy fund ledger mixed the two.') }}
    </div>
</div>
@endsection
