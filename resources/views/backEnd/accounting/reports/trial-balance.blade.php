@extends('backEnd.layouts.master')

@section('title', __('Trial Balance'))

@section('content')
@php use Softmit\DoubleEntry\Support\Money; @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ __('Trial Balance') }}</h3>
        <span class="badge bg-{{ $report['balanced'] ? 'success' : 'danger' }} fs-6">
            {{ $report['balanced'] ? __('Balanced') : __('Out of balance') }}
        </span>
    </div>

    @include('backEnd.accounting.partials.period', ['reportKey' => 'trial-balance'])

    @if (!$report['balanced'])
        <div class="alert alert-danger">
            {{ __('The ledger is not balanced: period movement differs by') }}
            {{ Money::format($report['difference']) }} {{ __(', brought-forward balances by') }} {{ Money::format($report['opening_difference']) }} {{ __('and closing balances by') }} {{ Money::format($report['closing_difference']) }}.
            {{ __('No report figure should be trusted until this is found — check for a header whose stored total disagrees with its lines.') }}
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Account') }}</th>
                        <th>{{ __('Class') }}</th>
                        <th class="text-end">{{ __('Opening') }}</th>
                        <th class="text-end">{{ __('Debit') }}</th>
                        <th class="text-end">{{ __('Credit') }}</th>
                        <th class="text-end">{{ __('Closing') }}</th>
                        <th class="text-end">{{ __('Dr') }}</th>
                        <th class="text-end">{{ __('Cr') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td>
                                <a href="{{ route('admin.accounting.ledger.account', $row['id']) }}">{{ $row['code'] }}</a>
                            </td>
                            <td>{{ $row['name'] }}</td>
                            <td class="small text-muted">{{ $row['account_type'] }}</td>
                            <td class="text-end {{ Money::isNegative($row['opening']) ? 'text-danger' : '' }}">{{ Money::format($row['opening']) }}</td>
                            <td class="text-end">{{ Money::isPositive($row['debit']) ? Money::format($row['debit']) : '' }}</td>
                            <td class="text-end">{{ Money::isPositive($row['credit']) ? Money::format($row['credit']) : '' }}</td>
                            <td class="text-end {{ Money::isNegative($row['closing']) ? 'text-danger' : '' }}">{{ Money::format($row['closing']) }}</td>
                            <td class="text-end text-muted">{{ Money::isPositive($row['closing_debit']) ? Money::format($row['closing_debit']) : '' }}</td>
                            <td class="text-end text-muted">{{ Money::isPositive($row['closing_credit']) ? Money::format($row['closing_credit']) : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                {{ __('Nothing posted yet. Opening balances and business journals will appear here.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4">{{ __('Totals') }}</th>
                        <th class="text-end">{{ Money::format($report['totals']['movement_debit']) }}</th>
                        <th class="text-end">{{ Money::format($report['totals']['movement_credit']) }}</th>
                        <th></th>
                        <th class="text-end">{{ Money::format($report['totals']['closing_debit']) }}</th>
                        <th class="text-end">{{ Money::format($report['totals']['closing_credit']) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-2">
        {{ __('Opening is everything posted strictly before the start date; closing is everything up to and including the end date. Amounts in the Opening and Closing columns are signed against the account’s normal balance, so a negative figure is shown in red rather than moved to the other side.') }}
    </p>
</div>
@endsection
