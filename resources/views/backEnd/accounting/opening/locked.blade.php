@extends('backEnd.layouts.master')
@section('title', 'Opening Balances Posted')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Opening Balances — posted</h4>
            <small class="text-muted">Booked once, on purpose. These figures are history now.</small>
        </div>
        <a href="{{ route('admin.accounting.journals.show', $journal->id) }}" class="btn btn-sm btn-outline-primary">
            <i data-feather="external-link" class="me-1"></i> Open {{ $journal->journal_no }}
        </a>
    </div>

    <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong>{{ $journal->journal_no }}</strong> — {{ number_format((float) $journal->total_debit, 2) }} brought forward as at
            {{ \Carbon\Carbon::parse($journal->transaction_date)->format('d M Y') }}.
        </div>
        <span class="badge bg-success">POSTED</span>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-light"><strong>What was booked</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Account</th>
                            <th>Description</th>
                            <th>Party</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($journal->lines as $line)
                        <tr>
                            <td>{{ $line->account->code }} — {{ $line->account->name }}</td>
                            <td>{{ $line->description }}</td>
                            <td><small class="text-muted">{{ $line->party_type ? $line->party_type->value.' #'.$line->party_id : '—' }}</small></td>
                            <td class="text-end">{{ number_format((float) $line->debit, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $line->credit, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Totals</td>
                            <td class="text-end fw-bold">{{ number_format((float) $journal->total_debit, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format((float) $journal->total_credit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light"><strong>If a figure turns out to be wrong</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-2">
                Opening balances are not editable, and they are not deleted. The way to fix one is the way to fix any posted entry:
                reverse it, which leaves both the mistake and the correction on the record, then post a new opening journal.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.accounting.journals.show', $journal->id) }}" class="btn btn-sm btn-outline-danger">
                    Reverse {{ $journal->journal_no }}
                </a>
                <a href="{{ route('admin.accounting.journals.index', ['source_type' => 'opening']) }}" class="btn btn-sm btn-outline-secondary">
                    All opening journals
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
