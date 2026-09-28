@extends('backEnd.layouts.master')

@section('title', __('Profit & Loss'))

@section('content')
@php use Softmit\DoubleEntry\Support\Money; @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ __('Profit & Loss') }}</h3>
        <span class="fs-5 fw-bold {{ Money::isNegative($report['net_profit']) ? 'text-danger' : 'text-success' }}">
            {{ Money::format($report['net_profit']) }}
        </span>
    </div>

    @include('backEnd.accounting.partials.period', ['reportKey' => 'profit-loss'])

    <div class="row mb-3">
        @foreach ([
            ['label' => __('Revenue'), 'amount' => $report['revenue'], 'tone' => 'primary'],
            ['label' => __('Cost of goods sold'), 'amount' => $report['cost_of_sales'], 'tone' => 'secondary'],
            ['label' => __('Gross profit'), 'amount' => $report['gross_profit'], 'tone' => 'info'],
            ['label' => __('Expenses'), 'amount' => $report['expenses'], 'tone' => 'warning'],
        ] as $card)
            <div class="col-sm-6 col-lg-3 mb-2">
                <div class="card border-top-{{ $card['tone'] }}">
                    <div class="card-body py-3">
                        <span class="small text-muted">{{ $card['label'] }}</span>
                        <div class="fs-5 fw-bold {{ Money::isNegative($card['amount']) ? 'text-danger' : '' }}">
                            {{ Money::format($card['amount']) }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            @foreach ($report['groups'] as $group)
                <h6 class="text-muted text-uppercase small mt-3">{{ $group['label'] }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-1">
                        <tbody>
                            @forelse ($group['rows'] as $row)
                                <tr>
                                    <td style="width:120px">
                                        <a href="{{ route('admin.accounting.ledger.account', $row['id']) }}">{{ $row['code'] }}</a>
                                    </td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-end {{ Money::isNegative($row['amount']) ? 'text-danger' : '' }}">
                                        {{ Money::format($row['amount']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-muted small py-2">{{ __('Nothing posted in this class for the period.') }}</td></tr>
                            @endforelse
                            <tr class="table-light">
                                <td></td>
                                <th>{{ __('Subtotal') }}</th>
                                <th class="text-end">{{ Money::format($group['total']) }}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach

            <hr>
            <table class="table table-sm mb-0">
                <tr>
                    <th>{{ __('Gross profit') }}</th>
                    <td class="text-end">{{ Money::format($report['gross_profit']) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Less operating expenses') }}</th>
                    <td class="text-end">{{ Money::format($report['expenses']) }}</td>
                </tr>
                <tr class="table-light">
                    <th class="fs-6">{{ __('Net profit for the period') }}</th>
                    <td class="text-end fw-bold fs-6 {{ Money::isNegative($report['net_profit']) ? 'text-danger' : '' }}">
                        {{ Money::format($report['net_profit']) }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-2">
        {{ __('Revenue is shown as credit minus debit, cost and expense as debit minus credit, so a contra account (such as sales returns) appears as a negative contribution instead of being quietly added.') }}
        {{ __('Equity accounts — owner capital and drawings — are never part of profit.') }}
    </p>
</div>
@endsection
