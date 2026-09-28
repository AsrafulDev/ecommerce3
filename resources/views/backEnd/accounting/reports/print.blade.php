@php
    use Softmit\DoubleEntry\Support\Money;

    $titles = [
        'trial-balance' => __('Trial Balance'),
        'profit-loss'   => __('Profit & Loss'),
        'cash'          => __('Cash & Bank Positions'),
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $titles[$which] ?? $which }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; padding: 16px; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .muted { color: #666; font-size: 10px; }
        .head { border-bottom: 2px solid #222; padding-bottom: 8px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #ddd; text-align: left; }
        thead th { background: #f2f2f2; border-bottom: 1px solid #999; font-size: 10px; text-transform: uppercase; }
        tfoot th { background: #f7f7f7; border-top: 1px solid #999; }
        .text-end { text-align: right; }
        .neg { color: #b00020; }
        .totals td, .totals th { font-weight: bold; border-top: 1px solid #999; }
        h2 { font-size: 12px; margin: 12px 0 4px; text-transform: uppercase; color: #555; }
        .footer { margin-top: 14px; border-top: 1px solid #ccc; padding-top: 6px; font-size: 9px; color: #777; }
    </style>
</head>
<body>

<div class="head">
    <h1>{{ config('app.name') }}</h1>
    <div class="muted">{{ $titles[$which] ?? $which }} — {{ $from }} to {{ $to }}</div>
</div>

@if ($which === 'profit-loss')
    @foreach ($report['groups'] as $group)
        <h2>{{ $group['label'] }}</h2>
        <table>
            <thead>
                <tr><th style="width:12%">Code</th><th>Account</th><th class="text-end" style="width:20%">Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $row)
                    <tr>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-end {{ Money::isNegative($row['amount']) ? 'neg' : '' }}">{{ Money::format($row['amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals"><th></th><th>Subtotal</th><th class="text-end">{{ Money::format($group['total']) }}</th></tr>
            </tfoot>
        </table>
    @endforeach

    <table>
        <tbody>
            <tr><th>Revenue</th><td class="text-end">{{ Money::format($report['revenue']) }}</td></tr>
            <tr><th>Cost of goods sold</th><td class="text-end">{{ Money::format($report['cost_of_sales']) }}</td></tr>
            <tr><th>Gross profit</th><td class="text-end">{{ Money::format($report['gross_profit']) }}</td></tr>
            <tr><th>Operating expenses</th><td class="text-end">{{ Money::format($report['expenses']) }}</td></tr>
            <tr class="totals"><th>Net profit</th><td class="text-end">{{ Money::format($report['net_profit']) }}</td></tr>
        </tbody>
    </table>

@elseif ($which === 'cash')
    <table>
        <thead>
            <tr>
                <th>Fund</th>
                <th>Account</th>
                <th class="text-end">Opening</th>
                <th class="text-end">In</th>
                <th class="text-end">Out</th>
                <th class="text-end">Closing</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($positions as $fundKey => $position)
                <tr>
                    <td>{{ $position['label'] }} ({{ $fundKey }})</td>
                    <td>{{ $position['account_code'] }} {{ $position['account_name'] }}</td>
                    <td class="text-end">{{ Money::format($position['opening']) }}</td>
                    <td class="text-end">{{ Money::format($position['in']) }}</td>
                    <td class="text-end">{{ Money::format($position['out']) }}</td>
                    <td class="text-end">{{ Money::format($position['closing']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <th colspan="2">Total cash on hand up to {{ $to }}</th>
                <th></th><th></th><th></th>
                <th class="text-end">{{ Money::format($total) }}</th>
            </tr>
        </tfoot>
    </table>

@else
    <table>
        <thead>
            <tr>
                <th style="width:10%">Code</th>
                <th>Account</th>
                <th style="width:14%">Class</th>
                <th class="text-end">Opening</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Credit</th>
                <th class="text-end">Closing</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['rows'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['account_type'] }}</td>
                    <td class="text-end {{ Money::isNegative($row['opening']) ? 'neg' : '' }}">{{ Money::format($row['opening']) }}</td>
                    <td class="text-end">{{ Money::format($row['debit']) }}</td>
                    <td class="text-end">{{ Money::format($row['credit']) }}</td>
                    <td class="text-end {{ Money::isNegative($row['closing']) ? 'neg' : '' }}">{{ Money::format($row['closing']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <th colspan="4">Totals</th>
                <th class="text-end">{{ Money::format($report['totals']['movement_debit']) }}</th>
                <th class="text-end">{{ Money::format($report['totals']['movement_credit']) }}</th>
                <th class="text-end"></th>
            </tr>
        </tfoot>
    </table>

    <p class="muted">
        {{ $report['balanced'] ? 'Balanced: total debits equal total credits.' : 'NOT BALANCED — difference ' . Money::format($report['difference']) }}
    </p>
@endif

<div class="footer">
    Generated {{ now()->format('d M Y H:i') }} · {{ __('All figures are derived from posted journals only. Drafts are excluded; reversed journals are shown at their reversed value.') }}
</div>

</body>
</html>
