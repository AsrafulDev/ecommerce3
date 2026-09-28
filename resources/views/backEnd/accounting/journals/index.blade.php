@extends('backEnd.layouts.master')

@section('title', __('Accounting Journals'))

@section('content')
@php use Softmit\DoubleEntry\Support\Money; @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ __('Accounting Journals') }}</h3>
        <span class="text-muted small">{{ number_format($journals->total(), 0) }} {{ __('journals') }}</span>
    </div>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-2">
            <label class="form-label mb-1 small text-muted">{{ __('From') }}</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1 small text-muted">{{ __('To') }}</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1 small text-muted">{{ __('Status') }}</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">{{ __('All') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1 small text-muted">{{ __('Source') }}</label>
            <select name="source_type" class="form-select form-select-sm">
                <option value="">{{ __('All') }}</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->value }}" @selected(($filters['source_type'] ?? '') === $source->value)>{{ $source->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1 small text-muted">{{ __('Account') }}</label>
            <select name="account_id" class="form-select form-select-sm">
                <option value="">{{ __('All') }}</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((int) ($filters['account_id'] ?? 0) === $account->id)>
                        {{ $account->code }} — {{ $account->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1 small text-muted">{{ __('Search') }}</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                   placeholder="{{ __('JV number / reference') }}">
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
            <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-sm btn-light">{{ __('Reset') }}</a>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Journal') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Source (what)') }}</th>
                        <th>{{ __('Party (whose)') }}</th>
                        <th class="text-end">{{ __('Debit') }}</th>
                        <th class="text-end">{{ __('Credit') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($journals as $journal)
                        <tr>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.accounting.journals.show', $journal->id) }}">{{ $journal->journal_no }}</a>
                                @if ($journal->reference)
                                    <span class="small text-muted d-block">{{ $journal->reference }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $journal->transaction_date?->format('d M Y') }}</td>
                            <td class="small">{{ Str::limit($journal->description, 60) ?: '—' }}</td>
                            <td class="small">{{ $journal->sourceLabel() }}</td>
                            <td class="small">
                                @if ($journal->party_type && $journal->party_id)
                                    <a href="{{ route('admin.accounting.ledger.party', [$journal->party_type->value, $journal->party_id]) }}">
                                        {{ $journal->party_type->label() }} #{{ $journal->party_id }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ Money::format($journal->total_debit) }}</td>
                            <td class="text-end">{{ Money::format($journal->total_credit) }}</td>
                            <td><span class="badge bg-{{ $journal->status->badgeClass() }}">{{ $journal->status->label() }}</span></td>
                            <td>
                                <a href="{{ route('admin.accounting.journals.show', $journal->id) }}" class="btn btn-sm btn-link p-0">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                {{ __('No journals yet. Books open on') }} {{ config('double-entry.cutover_date') }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $journals->links() }}
</div>
@endsection
