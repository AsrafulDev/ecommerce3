@extends('backEnd.layouts.master')

@section('title', __('Transaction Control Center'))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i data-feather="shield" class="text-danger me-2"></i>{{ __('Transaction Control Center') }}</h4>
            <p class="text-muted mb-0">{{ __('Protected financial correction and purge tools. Preview is fail-closed; complex transactions require reversal.') }}</p>
        </div>
        <span class="badge bg-danger-subtle text-danger border px-3 py-2">{{ __('Window: :days days', ['days' => config('accounting.transaction_hard_delete_window_days', 30)]) }}</span>
    </div>

    <div class="alert alert-warning"><strong>{{ __('Protected operation') }}</strong> — {{ __('Hard deletion is not enabled for unresolved dependencies. Use reversal for posted or linked financial history.') }}</div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6"><label class="form-label">{{ __('Search reference, title, source, or ID') }}</label><input name="q" value="{{ $query }}" class="form-control" placeholder="Expense, manual_add, 123"></div>
                <div class="col-auto"><button class="btn btn-primary"><i data-feather="search" class="me-1"></i>{{ __('Search') }}</button></div>
            </form>
        </div>
    </div>

    <div class="card mb-4"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>{{ __('Type') }}</th><th>{{ __('Reference') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Business date') }}</th><th>{{ __('Age / window') }}</th><th>{{ __('Status') }}</th><th>{{ __('Action') }}</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr><td>{{ str_replace('_', ' ', ucfirst($row['purge_type'] ?? $row['type'])) }}</td><td><strong>#{{ $row['id'] }}</strong> {{ $row['reference'] }}</td><td>৳{{ number_format((float) $row['amount'], 2) }}</td><td>{{ $row['effective_date']?->format('d M Y') ?? '—' }}</td><td>{{ $row['age_days'] ?? '—' }} {{ __('days') }} @if($row['days_remaining'])<small class="text-success">({{ $row['days_remaining'] }} {{ __('left') }})</small>@endif</td><td>@if($row['eligible'])<span class="badge bg-success">{{ __('PURGE ELIGIBLE') }}</span>@else<span class="badge bg-secondary">{{ __('PURGE BLOCKED') }}</span><div class="small text-danger mt-1">{{ implode(', ', $row['blockers']) }}</div>@endif</td><td>@if($row['eligible'] && $row['purge_type'])<details><summary class="btn btn-sm btn-outline-danger">{{ __('Permanently Purge') }}</summary><form method="POST" action="{{ route('admin.transaction-control.purge', [$row['purge_type'], $row['id']]) }}" class="border rounded p-3 mt-2 bg-light" style="min-width:290px">@csrf<p class="small text-danger mb-2">{{ __('This permanently removes the transaction and linked eligible accounting traces. Normal corrections should use reversal.') }}</p><input type="hidden" name="expected_confirmation" value="{{ $row['expected_confirmation'] }}"><label class="form-label small">{{ __('Reason') }}</label><textarea name="reason" required minlength="10" class="form-control form-control-sm mb-2"></textarea><label class="form-label small">{{ __('Current password') }}</label><input type="password" name="password" required class="form-control form-control-sm mb-2"><label class="form-label small">{{ __('Type :phrase', ['phrase' => $row['expected_confirmation']]) }}</label><input name="confirmation" required class="form-control form-control-sm mb-2"><button class="btn btn-sm btn-danger" onclick="return confirm('Permanently purge this transaction?')">{{ __('Permanently Purge') }}</button></form></details>@else<span class="text-muted small">{{ __('Use reversal') }}</span>@endif</td></tr>
        @empty <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No financial transactions found.') }}</td></tr>@endforelse
        </tbody>
    </table></div></div>

    <div class="card"><div class="card-header"><strong>{{ __('Purge History') }}</strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Transaction') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Performed by') }}</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->performed_at?->format('d M Y H:i') }}</td><td>{{ $log->transaction_type }} #{{ $log->source_id }}</td><td>{{ $log->reason }}</td><td>{{ $log->performed_by ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="text-muted">{{ __('No purge records.') }}</td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection
