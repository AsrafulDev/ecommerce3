@extends('backEnd.layouts.master')

@section('title', $journal->journal_no)

@section('content')
@php use Softmit\DoubleEntry\Support\Money; @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            {{ $journal->journal_no }}
            <span class="badge bg-{{ $journal->status->badgeClass() }} align-middle">{{ $journal->status->label() }}</span>
        </h3>
        <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-sm btn-light">{{ __('Back to journals') }}</a>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-7 mb-3">
            <div class="card h-100">
                <div class="card-header bg-light">{{ __('What happened') }}</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th class="text-muted small" style="width:35%">{{ __('Transaction date') }}</th>
                            <td>{{ $journal->transaction_date?->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted small">{{ __('Description') }}</th>
                            <td>{{ $journal->description ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted small">{{ __('Reference') }}</th>
                            <td>{{ $journal->reference ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted small">{{ __('Posted total') }}</th>
                            <td>
                                {{ Money::format($journal->total_debit) }}
                                {{ __('debit') }} / {{ Money::format($journal->total_credit) }} {{ __('credit') }}
                                @if (!Money::equals($journal->total_debit, $journal->total_credit))
                                    <span class="badge bg-danger ms-2">{{ __('OUT OF BALANCE') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted small">{{ __('Lines') }}</th>
                            <td>{{ $journal->lines->count() }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-3">
            <div class="card h-100">
                <div class="card-header bg-light">{{ __('Three traces') }}</div>
                <div class="card-body">
                    <p class="mb-3">
                        <span class="small text-muted d-block">{{ __('SOURCE — what produced this') }}</span>
                        @php $sourceRoute = config("double-entry.source_routes.{$journal->source_type?->value}"); @endphp
                        @if ($journal->source_type)
                            @if ($sourceRoute && $journal->source_id)
                                <a href="{{ route($sourceRoute, $journal->source_id) }}">{{ $journal->sourceLabel() }}</a>
                            @else
                                {{ $journal->sourceLabel() }}
                            @endif
                            <span class="text-muted small">({{ $journal->source_type->value }})</span>
                        @else
                            {{ __('Manual entry — no originating record') }}
                        @endif
                    </p>
                    <p class="mb-3">
                        <span class="small text-muted d-block">{{ __('PARTY — whose money relationship') }}</span>
                        @if ($journal->party_type && $journal->party_id)
                            <a href="{{ route('admin.accounting.ledger.party', [$journal->party_type->value, $journal->party_id]) }}">
                                {{ $journal->party_type->label() }} #{{ $journal->party_id }}
                            </a>
                        @else
                            {{ __('None') }}
                        @endif
                    </p>
                    <p class="mb-0">
                        <span class="small text-muted d-block">{{ __('ACTOR — who did it') }}</span>
                        <span class="d-block">{{ __('Created by') }}: {{ $actors->name($journal->created_by) }}</span>
                        <span class="d-block">{{ __('Approved by') }}: {{ $actors->name($journal->approved_by) }}</span>
                        <span class="d-block">{{ __('Posted by') }}: {{ $actors->name($journal->posted_by) }}
                            @if ($journal->posted_at) <span class="text-muted small">({{ $journal->posted_at->format('d M Y H:i') }})</span> @endif
                        </span>
                        @if ($journal->reversed_by)
                            <span class="d-block">{{ __('Reversed by') }}: {{ $actors->name($journal->reversed_by) }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span>{{ __('Journal lines') }}</span>
            <small class="text-muted">{{ __('Amounts are exact decimals; the account column links to its ledger.') }}</small>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Account') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Party') }}</th>
                        <th class="text-end">{{ __('Debit') }}</th>
                        <th class="text-end">{{ __('Credit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($journal->lines as $line)
                        <tr>
                            <td>
                                <a href="{{ route('admin.accounting.ledger.account', $line->account_id) }}">
                                    {{ $line->account?->code }} — {{ $line->account?->name }}
                                </a>
                                <span class="badge bg-light text-dark border">{{ $line->account?->account_type?->label() }}</span>
                            </td>
                            <td class="small">{{ $line->description ?: '—' }}</td>
                            <td class="small">
                                @if ($line->party_type && $line->party_id)
                                    {{ $line->party_type->label() }} #{{ $line->party_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ Money::isPositive((string) $line->debit) ? Money::format($line->debit) : '' }}</td>
                            <td class="text-end">{{ Money::isPositive((string) $line->credit) ? Money::format($line->credit) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3">{{ __('Total') }}</th>
                        <th class="text-end">{{ Money::format($journal->total_debit) }}</th>
                        <th class="text-end">{{ Money::format($journal->total_credit) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if ($journal->reversal_of_id)
        <div class="alert alert-info">
            {{ __('This journal corrects') }}
            <a href="{{ route('admin.accounting.journals.show', $journal->reversal_of_id) }}">
                {{ $journal->reversedSource?->journal_no ?? '#' . $journal->reversal_of_id }}
            </a>
        </div>
    @endif

    @if ($journal->reversals->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header bg-light">{{ __('Reversals of this journal') }}</div>
            <ul class="list-group list-group-flush">
                @foreach ($journal->reversals as $reversal)
                    <li class="list-group-item d-flex justify-content-between">
                        <a href="{{ route('admin.accounting.journals.show', $reversal->id) }}">{{ $reversal->journal_no }}</a>
                        <span class="small text-muted">{{ $reversal->transaction_date?->format('d M Y') }} — {{ $reversal->reversal_reason ?: $reversal->description }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($journal->isPosted())
        @can('accounting-reverse')
            <div class="card">
                <div class="card-header bg-danger text-white">{{ __('Correct this entry') }}</div>
                <div class="card-body">
                    <p class="text-muted small">
                        {{ __('A posted journal cannot be edited or deleted. Reversing it posts the exact mirror image and keeps both entries visible and auditable.') }}
                    </p>
                    <form method="POST" action="{{ route('admin.accounting.journals.reverse', $journal->id) }}" class="row g-2">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('Why it is wrong (required)') }}</label>
                            <input type="text" name="reason" required maxlength="500" class="form-control form-control-sm"
                                   value="{{ old('reason') }}" placeholder="{{ __('e.g. Wrong customer, double-posted') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('Effective date') }}</label>
                            <input type="date" name="as_at_date" class="form-control form-control-sm" value="{{ old('as_at_date') }}">
                        </div>
                        <div class="col-md-3 align-self-end">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('{{ __('Post a reversal journal? This cannot be undone.') }}')">
                                {{ __('Reverse & post correction') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @elseif ($journal->isDraft())
        <div class="alert alert-warning">
            {{ __('This is a draft: it is not included in any report. Complete its lines and post it.') }}
        </div>
    @endif

</div>
@endsection
