@extends('backEnd.layouts.master')

@section('title', 'Accounts Dashboard')

@section('css')
<style>
    /* Theme forces .card background to white with !important — inline !important beats it */
    .acct-card h2, .acct-card h3, .acct-card h4, .acct-card h5, .acct-card small { color: rgba(255,255,255,.92) !important; }
    .acct-card h2, .acct-card h3 { font-weight: 700; }
</style>
@endsection

@section('content')
@php
    $fmt = fn ($v) => number_format((float) $v, 2);
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">📒 {{ __('Accounts Dashboard') }}</h3>
        <div>
            <a href="{{ route('admin.fund.index') }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-briefcase me-1"></i>{{ __('Fund') }}</a>
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-credit-card me-1"></i>{{ __('Expenses') }}</a>
            <a href="{{ route('admin.fund.export') }}" class="btn btn-sm btn-outline-success"><i class="mdi mdi-file-delimited me-1"></i>{{ __('Export CSV') }}</a>
        </div>
    </div>

    {{-- Balance cards --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white acct-card" style="background:#198754 !important;">
                <div class="card-body">
                    <h5 class="mb-1">{{ __('Available Balance') }}</h5>
                    <h2 class="mb-0">{{ $fmt($fund_balance) }} ৳</h2>
                    <small>{{ __('Total in') }}: {{ $fmt($total_in) }} ৳ · {{ __('Total out') }}: {{ $fmt($total_out) }} ৳</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white acct-card" style="background:#0d6efd !important;">
                <div class="card-body">
                    <h5 class="mb-1">{{ __('Income This Month') }}</h5>
                    <h3 class="mb-0">{{ $fmt($in_month) }} ৳</h3>
                    <small>{{ __('Today') }}: +{{ $fmt($in_today) }} ৳ · {{ __('This Year') }}: {{ $fmt($in_year) }} ৳</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white acct-card" style="background:#dc3545 !important;">
                <div class="card-body">
                    <h5 class="mb-1">{{ __('Expense This Month') }}</h5>
                    <h3 class="mb-0">{{ $fmt($out_month) }} ৳</h3>
                    <small>{{ __('Today') }}: −{{ $fmt($out_today) }} ৳ · {{ __('This Year') }}: {{ $fmt($out_year) }} ৳</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white acct-card" style="background:#222275 !important;">
                <div class="card-body">
                    <h5 class="mb-1">{{ __('Gross Profit This Month') }}</h5>
                    <h3 class="mb-0">{{ $fmt($month_profit) }} ৳</h3>
                    <small>{{ __('Sales') }}: {{ $fmt($month_sales) }} ৳ ({{ __('COGS included') }})</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Position cards --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted">{{ __('Stock Value (batches)') }}</h6>
                    <h4 class="mb-0">{{ $fmt($stock_value) }} ৳</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted">{{ __('Supplier Due (payable)') }}</h6>
                    <h4 class="mb-0 text-danger">{{ $fmt($supplier_due) }} ৳</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted">{{ __('Customer Due (receivable)') }}</h6>
                    <h4 class="mb-0 text-success">{{ $fmt($customer_due) }} ৳</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        {{-- 12-month trend --}}
        <div class="col-lg-8 mb-3">
            <div class="card h-100">
                <div class="card-header bg-light"><strong>📈 {{ __('Income vs Expense (last 12 months)') }}</strong></div>
                <div class="card-body">
                    <div id="accountsTrendChart" style="min-height:300px;"></div>
                </div>
            </div>
        </div>

        {{-- Source breakdown --}}
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-light"><strong>🧾 {{ __('This Month by Source') }}</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ __('Income') }}</th><th class="text-end">৳</th><th class="text-end">{{ __('Expense') }}</th><th class="text-end">৳</th></tr></thead>
                        <tbody>
                        @forelse($sourceRows as $row)
                            <tr>
                                <td>{{ $row['in_label'] }}</td>
                                <td class="text-end text-success">{{ $row['in_total'] !== null ? $fmt($row['in_total']) : '' }}</td>
                                <td>{{ $row['out_label'] }}</td>
                                <td class="text-end text-danger">{{ $row['out_total'] !== null ? $fmt($row['out_total']) : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">{{ __('No transactions this month') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent fund transactions --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong>🕘 {{ __('Recent Fund Transactions') }}</strong>
                    <a href="{{ route('admin.fund.index') }}" class="btn btn-sm btn-link">{{ __('View all') }}</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Source') }}</th>
                                    <th>{{ __('Note') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent as $tx)
                                    <tr>
                                        <td>{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $tx->direction === 'in' ? 'success' : 'danger' }}-subtle text-{{ $tx->direction === 'in' ? 'success' : 'danger' }}-emphasis">
                                                {{ $tx->direction === 'in' ? '▲' : '▼' }} {{ ucwords(str_replace('_', ' ', $tx->source)) }}
                                            </span>
                                        </td>
                                        <td class="text-truncate" style="max-width:380px;">{{ $tx->note }}</td>
                                        <td class="text-end fw-bold text-{{ $tx->direction === 'in' ? 'success' : 'danger' }}">
                                            {{ $tx->direction === 'in' ? '+' : '−' }}{{ $fmt($tx->amount) }} ৳
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No fund transactions yet') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.5/dist/apexcharts.min.js"></script>
<script>
(function () {
    var trend = @json($trend);
    new ApexCharts(document.querySelector('#accountsTrendChart'), {
        chart: { type: 'bar', height: 320, stacked: false, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: @json(__('Income')),   data: trend.map(t => t.in) },
            { name: @json(__('Expense')), data: trend.map(t => t.out) },
        ],
        colors: ['#198754', '#dc3545'],
        xaxis: { categories: trend.map(t => t.label) },
        yaxis: { labels: { formatter: (v) => Math.round(v).toLocaleString() } },
        dataLabels: { enabled: false },
        plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
        legend: { position: 'top' },
    }).render();
})();
</script>
@endsection
