@extends('backEnd.layouts.master')
@section('title', 'Expense Report')

@section('css')
<style>
    /* --- Modern Card --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #fff;
        margin-bottom: 24px;
        transition: transform 0.2s;
    }
    
    /* --- Filter Section --- */
    .filter-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .form-label-custom {
        font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 5px;
    }
    .form-control-custom, .form-select-custom {
        border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; font-size: 0.9rem;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    /* --- Summary Card --- */
    .expense-card {
        padding: 20px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #dc3545;
        border-radius: 10px;
        color: #1e293b;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        display: flex; align-items: center; justify-content: space-between;
    }
    .expense-icon {
        width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 10px; background: #fff1f2; color: #dc3545; font-size: 21px;
    }
    .expense-label { font-size: 0.8rem; color: #64748b; font-weight: 600; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px; }
    .expense-amount { font-size: 1.7rem; font-weight: 700; margin: 0; line-height: 1.2; color: #1e293b; }

    /* --- Table Styles --- */
    .table-modern th {
        background-color: #f8fafc; color: #475569; font-size: 0.75rem;
        font-weight: 700; text-transform: uppercase; padding: 1rem; border-bottom: 1px solid #e2e8f0;
    }
    .table-modern td {
        padding: 1rem; vertical-align: middle; font-size: 0.875rem; color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:hover td { background-color: #fff1f2; }

    /* --- Utilities --- */
    .btn-custom-danger { background: #dc3545; color: #fff; border: 1px solid #dc3545; padding: 9px 17px; border-radius: 7px; font-weight: 600; }
    .btn-custom-danger:hover { background: #bb2d3b; border-color: #bb2d3b; color: #fff; }
    .filter-card .form-control-custom, .filter-card .form-select-custom { min-height: 42px; }
    .table-modern thead th { white-space: nowrap; }
    .report-meta { color: #64748b; font-size: .85rem; }
    .table-modern tbody tr { transition: background-color .15s ease; }
    .empty-state { color: #94a3b8; }
    .empty-state-icon { width: 52px; height: 52px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #fff1f2; color: #ef4444; }
    #expense-table-wrapper.is-loading { opacity: .55; pointer-events: none; }
    @media (max-width: 767.98px) {
        .expense-card { padding: 16px; }
        .expense-amount { font-size: 1.65rem; }
        .filter-card .ms-auto { margin-left: 0 !important; width: 100%; }
        .filter-card .ms-auto .btn { flex: 1; }
        .pagination-wrap { flex-direction: column; align-items: flex-start !important; gap: 12px; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="trending-down" class="text-danger me-2"></i> Expense Report
            </h4>
            <p class="text-muted small mb-0">Review business spending by date, category, and transaction details.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2"><span id="report-period-label" class="badge bg-light text-dark border px-3 py-2">{{ $label ?? 'Today' }}</span><a href="{{ route('admin.accounts.dashboard') }}" class="btn btn-sm btn-outline-secondary">Transactions overview</a><a href="{{ route('admin.reports.expenses') }}" class="btn btn-sm btn-outline-secondary"><i data-feather="rotate-ccw" class="me-1" style="width:15px;"></i>{{ __('Reset filters') }}</a></div>
    </div>

    {{-- FILTER FORM --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.reports.expenses') }}" id="expense-filter-form">
            <div class="row g-3 align-items-end">
                
                {{-- Report Type --}}
                <div class="col-md-3">
                    <label class="form-label-custom" for="report-type">{{ __('Reporting period') }}</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i data-feather="filter" style="width:16px;"></i></span>
                        <select name="type" class="form-select form-select-custom border-start-0" id="report-type">
                            <option value="today" {{ ($type ?? '')=='today' ? 'selected' : '' }}> {{ __('Today') }} </option>
                            <option value="month" {{ ($type ?? '')=='month' ? 'selected' : '' }}> {{ __('Monthly') }} </option>
                            <option value="year"  {{ ($type ?? '')=='year'  ? 'selected' : '' }}> {{ __('Yearly') }} </option>
                            <option value="range" {{ ($type ?? '')=='range' ? 'selected' : '' }}> {{ __('Custom Date Range') }} </option>
                        </select>
                    </div>
                </div>

                {{-- Dynamic Inputs --}}
                <div class="col-md-2 type-month type-year" style="display:none;">
                    <label class="form-label-custom" for="expense-year">{{ __('Year') }}</label>
                    <input id="expense-year" type="number" name="year" class="form-control form-control-custom" value="{{ request('year', now()->year) }}" placeholder="YYYY" min="2000" max="2100">
                </div>

                <div class="col-md-2 type-month" style="display:none;">
                    <label class="form-label-custom">{{ __('Month') }}</label>
                    <select name="month" class="form-select form-select-custom">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2 type-range" style="display:none;">
                    <label class="form-label-custom" for="expense-from-date">{{ __('Start date') }}</label>
                    <input id="expense-from-date" type="date" name="from_date" class="form-control form-control-custom" value="{{ request('from_date') }}">
                </div>

                <div class="col-md-2 type-range" style="display:none;">
                    <label class="form-label-custom" for="expense-to-date">{{ __('End date') }}</label>
                    <input id="expense-to-date" type="date" name="to_date" class="form-control form-control-custom" value="{{ request('to_date') }}">
                </div>

                {{-- Actions --}}
                <div class="col-md-auto ms-auto d-flex gap-2">
                    <button class="btn btn-custom-danger" type="submit">
                        <i data-feather="search" class="me-1" style="width:16px;"></i> Apply filters
                    </button>
                    <button class="btn btn-outline-secondary" type="submit" name="export" value="csv" id="export-csv-btn">
                        <i data-feather="download" class="me-1" style="width:16px;"></i> CSV
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- SUMMARY CARD --}}
    <div class="row g-3 mb-4" id="expense-summary-wrapper">
        <div class="col-md-4">
            <div class="expense-card">
                <div>
                    <div class="expense-label">{{ __('Total expenses') }} · <span class="report-period-value">{{ $label ?? 'Today' }}</span></div>
                    <h2 class="expense-amount">৳{{ number_format($totalExpense ?? 0, 2) }}</h2>
                </div>
                <div class="expense-icon">
                    <i data-feather="trending-down"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4"><div class="expense-card h-100"><div><div class="expense-label">{{ __('Expense records') }}</div><h2 class="expense-amount">{{ number_format($expenses->total()) }}</h2><small class="text-muted">{{ __('Transactions in this period') }}</small></div><div class="expense-icon"><i data-feather="list"></i></div></div></div>
        <div class="col-md-4"><div class="expense-card h-100"><div><div class="expense-label">{{ __('Average per record') }}</div><h2 class="expense-amount">৳{{ number_format($expenses->total() ? (($totalExpense ?? 0) / $expenses->total()) : 0, 2) }}</h2><small class="text-muted">{{ __('Based on the selected period') }}</small></div><div class="expense-icon"><i data-feather="bar-chart-2"></i></div></div></div>
    </div>

    {{-- EXPENSE TABLE --}}
    <div id="expense-table-wrapper">
        <div class="card card-modern">
            <div class="card-header border-bottom bg-white py-3">
                <div><h5 class="mb-0 fw-bold text-dark">{{ __('Expense transactions') }}</h5><small class="text-muted">{{ __('Showing entries for') }} <span id="expense-list-period">{{ $label ?? 'Today' }}</span></small></div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">{{ __('Date') }}</th>
                            <th width="20%">{{ __('Title') }}</th>
                            <th width="15%">{{ __('Category') }}</th>
                            <th width="15%" class="text-end">{{ __('Amount') }}</th>
                            <th width="30%">{{ __('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($expenses as $e)
                        @php
                            $rawDate = $e->expense_date ?? $e->created_at;
                            $formattedDate = $rawDate ? \Carbon\Carbon::parse($rawDate)->format('d M, Y') : '-';
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $expenses->firstItem() + $loop->index }}</td>
                            <td>{{ $formattedDate }}</td>
                            <td class="fw-bold text-dark">{{ $e->title }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $e->category ?? 'General' }}</span></td>
                            <td class="text-end fw-bold text-danger">৳{{ number_format($e->amount ?? 0, 2) }}</td>
                            <td class="text-muted small" title="{{ $e->note }}">{{ $e->note ? Str::limit($e->note, 70) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="empty-state-icon mb-2"><i data-feather="inbox"></i></span>
                                    <p class="text-muted fw-bold mb-0"> {{ __('No expenses found') }} </p>
                                    <small class="empty-state mt-1">{{ __('Try another date range or reset the filters.') }}</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-4 border-top d-flex justify-content-between align-items-center pagination-wrap">
                <small class="text-muted">@if($expenses->total()) Showing {{ $expenses->firstItem() }}–{{ $expenses->lastItem() }} of {{ $expenses->total() }} entries @else No entries to show @endif</small>
                <div>{{ $expenses->links('pagination::bootstrap-4') }}</div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
    /* -------- Toggle fields -------- */
    function toggleReportFields() {
        let type = document.getElementById('report-type').value;

        document.querySelectorAll('.type-month,.type-year,.type-range')
            .forEach(el => el.style.display = 'none');

        if (type === 'month') {
            document.querySelectorAll('.type-month,.type-year')
                .forEach(el => el.style.display = 'block');
        } else if (type === 'year') {
            document.querySelectorAll('.type-year')
                .forEach(el => el.style.display = 'block');
        } else if (type === 'range') {
            document.querySelectorAll('.type-range')
                .forEach(el => el.style.display = 'block');
        }
    }

    document.getElementById('report-type')
        .addEventListener('change', toggleReportFields);
    toggleReportFields();

    /* -------- AJAX Filter + Pagination -------- */
    const form = document.getElementById('expense-filter-form');

    form.addEventListener('submit', function(e){
        // CSV হলে normal submit
        if (e.submitter && e.submitter.id === 'export-csv-btn') {
            return true;
        }

        e.preventDefault();
        loadExpenses(new URLSearchParams(new FormData(form)).toString());
    });

    document.addEventListener('click', function(e){
        let link = e.target.closest('.pagination a');
        if (!link) return;

        e.preventDefault();
        let query = link.getAttribute('href').split('?')[1] || '';
        loadExpenses(query);
    });

    function loadExpenses(query) {
        const wrapper = document.getElementById('expense-table-wrapper');
        wrapper.classList.add('is-loading');
        fetch("{{ route('admin.reports.expenses') }}?" + query, {
            headers: {'X-Requested-With':'XMLHttpRequest'}
        })
        .then(res => {
            if (!res.ok) throw new Error('Unable to load the report.');
            return res.text();
        })
        .then(html => {
            let temp = document.createElement('div');
            temp.innerHTML = html;

            const updatedTable = temp.querySelector('#expense-table-wrapper');
            if (updatedTable) wrapper.innerHTML = updatedTable.innerHTML;
            const updatedSummary = temp.querySelector('#expense-summary-wrapper');
            const summary = document.getElementById('expense-summary-wrapper');
            if (updatedSummary && summary) summary.innerHTML = updatedSummary.innerHTML;
            const updatedPeriod = temp.querySelector('#report-period-label');
            const period = document.getElementById('report-period-label');
            if (updatedPeriod && period) period.textContent = updatedPeriod.textContent;
            const updatedListPeriod = temp.querySelector('#expense-list-period');
            const listPeriod = document.getElementById('expense-list-period');
            if (updatedListPeriod && listPeriod) listPeriod.textContent = updatedListPeriod.textContent;
        })
        .catch(() => {
            wrapper.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger mb-3">Unable to load the report. Please try again.</div>');
        })
        .finally(() => wrapper.classList.remove('is-loading'));
    }
</script>
@endsection
