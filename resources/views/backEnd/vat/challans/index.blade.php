@extends('backEnd.layouts.master')
@section('title', 'Mushak 6.3 challans')

@section('content')
@php
    use App\Enums\OrderStatus;
    $money = fn ($value) => number_format((float) $value, 2);
    $eligibleCount = collect($candidates)->filter(fn ($row) => $row['inspection']['eligible'] && ! isset($issuedByOrder[$row['order']->id]))->count();
    $periodQuery = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
@endphp

<style>
    .challan-table th { font-size: 12px; text-transform: uppercase; letter-spacing: .03em; color: #6b7280; font-weight: 600; white-space: nowrap; }
    .challan-table td { vertical-align: middle; font-variant-numeric: tabular-nums; }
    .challan-blockers { font-size: 12px; color: #b00020; margin: 0; padding-left: 16px; }
    .challan-warnings { font-size: 12px; color: #6b7280; margin: 4px 0 0; }
    .challan-inline { display: inline-flex; gap: 4px; align-items: center; }
</style>

<section class="sms-breadcrumb mb-20">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-banner d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h1>Mushak 6.3 challans</h1>
                        <div class="small text-muted">{{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.reports') }}">VAT reports</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.challans.export', $periodQuery) }}">CSV register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container-fluid">
    <div class="alert alert-warning">
        <strong>Not yet approved for filing.</strong> The printed layout follows the NBR form, but a Bengali-literate reviewer
        has not yet checked it against the official PDF, and a VAT practitioner has not signed off the rules. Do not hand over
        printed challans until the review is recorded. Open questions are listed in <code>docs/vat-mushak-63.md</code>.
    </div>

    <div class="white-box mb-3">
        <form method="GET" action="{{ route('admin.vat.challans.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-1" for="challan-from">From</label>
                <input id="challan-from" type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}">
            </div>
            <div>
                <label class="form-label small mb-1" for="challan-to">To</label>
                <input id="challan-to" type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}">
            </div>
            <button class="primary-btn fix-gr-bg" type="submit">Show</button>
        </form>
    </div>

    {{-- Bulk issue: a standalone form. The checkboxes below point at it with the form attribute, so no table cell nests a form. --}}
    <form id="bulk-issue" method="POST" action="{{ route('admin.vat.challans.issue_selected') }}" class="d-none">@csrf</form>

    <div class="white-box mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div>
                <h4 class="mb-0">Sales in this period</h4>
                <div class="small text-muted">
                    A challan is issued only for a delivered or completed sale to a buyer with a 12-digit BIN, and only after you
                    choose to issue it. Nothing is issued automatically. {{ $eligibleCount }} sale(s) are ready to issue.
                </div>
            </div>
            <button type="submit" form="bulk-issue" class="btn btn-sm btn-primary" @disabled($eligibleCount === 0)>Issue selected challans</button>
        </div>

        <div class="table-responsive">
            <table class="table table-sm challan-table mb-0">
                <thead>
                <tr>
                    <th style="width: 32px"><span class="visually-hidden">Select</span></th>
                    <th>Invoice</th><th>Date</th><th>Status</th><th>Buyer</th><th>Buyer BIN</th>
                    <th class="text-end">VAT</th><th>Challan</th><th>Result</th>
                </tr>
                </thead>
                <tbody>
                @forelse($candidates as $row)
                    @php
                        $order = $row['order'];
                        $inspection = $row['inspection'];
                        $issuedChallan = $issuedByOrder[$order->id] ?? null;
                        $statusLabel = OrderStatus::tryFrom((string) $order->order_status)?->label() ?? $order->order_status;
                    @endphp
                    <tr>
                        <td>
                            @if(! $issuedChallan && $inspection['eligible'])
                                <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" form="bulk-issue" aria-label="Select {{ $order->invoice_id }}">
                            @endif
                        </td>
                        <td>{{ $order->invoice_id }}</td>
                        <td>{{ $order->created_at->format('d M Y') }}</td>
                        <td>{{ $statusLabel }}</td>
                        <td>{{ $order->shipping?->name ?? $order->customer?->name ?? '—' }}</td>
                        <td>
                            @if($issuedChallan)
                                {{ $order->buyer_bin ?? '—' }}
                            @else
                                <form method="POST" action="{{ route('admin.vat.challans.buyer_bin', $order) }}" class="challan-inline">
                                    @csrf
                                    <input type="text" name="buyer_bin" value="{{ $order->buyer_bin }}" maxlength="20" class="form-control form-control-sm" style="width: 140px" placeholder="12 digits" aria-label="Buyer BIN for {{ $order->invoice_id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                </form>
                            @endif
                        </td>
                        <td class="text-end">{{ $money($inspection['total_vat']) }}</td>
                        <td>
                            @if($issuedChallan)
                                <a href="{{ route('admin.vat.challans.show', $issuedChallan) }}">{{ $issuedChallan->challan_no }}</a>
                                @if($issuedChallan->isIssued()) <span class="badge bg-success">issued</span> @else <span class="badge bg-secondary">voided</span> @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($issuedChallan && $issuedChallan->isIssued())
                                <span class="text-muted small">Already challaned</span>
                            @elseif($inspection['eligible'])
                                <form method="POST" action="{{ route('admin.vat.challans.issue', $order) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Issue challan</button>
                                </form>
                            @else
                                <ul class="challan-blockers">
                                    @foreach($inspection['blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach
                                </ul>
                            @endif
                            @if(! empty($inspection['warnings']) && ! ($issuedChallan && $issuedChallan->isIssued()))
                                <ul class="challan-warnings">
                                    @foreach($inspection['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-muted">No delivered or completed sales in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="white-box mb-3">
        <h4>Challans issued in this period</h4>
        <div class="table-responsive">
            <table class="table table-sm challan-table mb-0">
                <thead>
                <tr>
                    <th>Challan no.</th><th>Issue date</th><th>Invoice</th><th>Buyer</th><th>Buyer BIN</th>
                    <th class="text-end">Taxable</th><th class="text-end">VAT</th><th class="text-end">Total</th><th>Status</th><th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($issued as $challan)
                    <tr>
                        <td><a href="{{ route('admin.vat.challans.show', $challan) }}">{{ $challan->challan_no }}</a></td>
                        <td>{{ $challan->issue_date->format('d M Y') }}</td>
                        <td>{{ $challan->invoice_no }}</td>
                        <td>{{ $challan->buyer_name }}</td>
                        <td>{{ $challan->buyer_bin }}</td>
                        <td class="text-end">{{ $money($challan->total_value) }}</td>
                        <td class="text-end">{{ $money($challan->total_vat) }}</td>
                        <td class="text-end">{{ $money($challan->total_with_tax) }}</td>
                        <td>
                            @if($challan->isIssued()) <span class="badge bg-success">issued</span>
                            @else <span class="badge bg-secondary" title="{{ $challan->void_reason }}">voided</span> @endif
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.challans.pdf', $challan) }}" target="_blank" rel="noopener">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-muted">No challans issued in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
