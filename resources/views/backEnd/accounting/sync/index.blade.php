@extends('backEnd.layouts.master')
@section('title', 'Lite Data Sync')

@section('content')
@php use Softmit\DoubleEntry\Support\Money; @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Lite Data Sync / লাইট ডাটা সিঙ্ক</h4>
            <small class="text-muted">
                Send the Lite money records (expenses and the manual fund entries) that are still owed a journal into the double-entry books.
                Every row uses the same rules as its own money screen, so nothing here invents a mapping.
            </small>
        </div>
        <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-sm btn-outline-secondary">
            <i data-feather="list" class="me-1"></i> View journals
        </a>
    </div>

    @if ($pending['cutover'])
        <div class="alert alert-light border small mb-3">
            Records dated before the accounting start ({{ $pending['cutover'] }}) are not listed — they belong to the opening balances, not to a sync.
        </div>
    @endif

    {{-- Pending summary --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Expenses owed an entry</div>
                    <div class="fs-3 fw-bold">{{ $pending['expenses']->count() }}</div>
                    <div class="text-muted small">{{ $pending['totals']['expenses'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Owner withdrawals owed an entry</div>
                    <div class="fs-3 fw-bold">{{ $pending['withdrawals']->count() }}</div>
                    <div class="text-muted small">{{ $pending['totals']['withdrawals'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Money-in owed an entry</div>
                    <div class="fs-3 fw-bold">{{ $pending['money_ins']->count() }}</div>
                    <div class="text-muted small">{{ $pending['totals']['money_ins'] }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($truncated)
        <div class="alert alert-warning small">
            Only the first 1,000 of each kind are shown. The sync still reads afresh — but if you see a suspiciously round number here, sync more than once.
        </div>
    @endif

    {{-- Sync action --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-light">
            <strong><i data-feather="send" class="me-1" style="width:16px;height:16px;"></i> Send pending rows to the books</strong>
        </div>
        <div class="card-body">
            @php $totalPending = $pending['expenses']->count() + $pending['withdrawals']->count() + $pending['money_ins']->count(); @endphp

            @if ($totalPending === 0)
                <p class="text-muted mb-0">Nothing to send — every Lite money row already has a standing journal. This screen is idempotent, so sending again changes nothing.</p>
            @else
                <form method="POST" action="{{ route('admin.accounting.sync.run') }}">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">How to book money-in rows that have no recorded nature</label>
                            <select name="money_in_nature" class="form-select form-select-sm">
                                <option value="">Leave them unposted (default)</option>
                                <option value="owner_capital" @selected(old('money_in_nature') === 'owner_capital')>Owner Capital (equity — not profit)</option>
                                <option value="other_income" @selected(old('money_in_nature') === 'other_income')>Other Income (revenue)</option>
                            </select>
                            <div class="form-text">
                                Only rows never posted before are affected. A money-in that already has a nature keeps it. Leaving this blank is the safe choice — the screen will not guess between equity and profit for you.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i data-feather="upload" class="me-1"></i> Sync {{ $totalPending }} row(s)
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- Reset action --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light">
            <strong><i data-feather="rotate-ccw" class="me-1" style="width:16px;height:16px;"></i> Reset Lite sync (re-open for a fresh sync)</strong>
        </div>
        <div class="card-body">
            <p class="text-muted small">
                Reverses the journals that came from Lite money records — <strong>{{ $reopenCount }}</strong> standing journal(s) today.
                This is reversal-only: the original entries stay in the books with an audit trail, and the Lite rows become open again so you can re-sync.
                It does <em>not</em> touch the chart of accounts, opening balances, hand-built journals, or sales/purchase journals.
            </p>
            @if ($reopenCount === 0)
                <p class="mb-0 text-muted">There is nothing to re-open right now.</p>
            @else
                @can('accounting-reverse')
                <form method="POST" action="{{ route('admin.accounting.sync.reset') }}"
                      onsubmit="return confirm('This will post reversal journals for {{ $reopenCount }} Lite-synced entries. Continue?');">
                    @csrf
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label small mb-1">Type <code>RE-OPEN</code> to confirm</label>
                            <input type="text" name="confirm" class="form-control form-control-sm @error('confirm') is-invalid @enderror" placeholder="RE-OPEN" autocomplete="off">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-outline-danger">
                                <i data-feather="rotate-ccw" class="me-1"></i> Re-open Lite journals
                            </button>
                        </div>
                    </div>
                </form>
                @else
                    <p class="mb-0 small text-muted">You do not have permission to reverse journals.</p>
                @endcan
            @endif
        </div>
    </div>

    {{-- Preview of what is pending --}}
    @if ($totalPending ?? 0)
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light"><strong>Pending rows (preview)</strong></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>Kind</th><th>Description</th><th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pending['expenses']->take(25) as $expense)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') }}</td>
                            <td><span class="badge bg-secondary">Expense</span> {{ $expense->category }}</td>
                            <td>{{ $expense->title }}</td>
                            <td class="text-end">{{ Money::format((string) $expense->amount) }}</td>
                        </tr>
                    @endforeach
                    @foreach ($pending['withdrawals']->take(25) as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('Y-m-d') }}</td>
                            <td><span class="badge bg-danger">Withdrawal</span></td>
                            <td>{{ $tx->note ?: 'Owner withdrawal' }}</td>
                            <td class="text-end">{{ Money::format((string) $tx->amount) }}</td>
                        </tr>
                    @endforeach
                    @foreach ($moneyInRows as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('Y-m-d') }}</td>
                            <td><span class="badge bg-success">Money-in</span></td>
                            <td>{{ $tx->note ?: 'Money added' }}</td>
                            <td class="text-end">{{ Money::format((string) $tx->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">Preview limited to the first 25 of each kind. The sync posts every pending row, not just these.</div>
    </div>
    @endif

</div>
@endsection
