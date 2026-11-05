@extends('backEnd.layouts.master')
@section('title', 'Challan '.$challan->challan_no)

@section('content')
@php
    use App\Enums\OrderStatus;
    use App\Services\CogsCalculator;
    $money = fn ($value) => number_format((float) $value, 2);
    $orderRecognised = $order && in_array($order->order_status, CogsCalculator::RECOGNIZED_STATUSES, true);
    $orderStatusLabel = $order ? (OrderStatus::tryFrom((string) $order->order_status)?->label() ?? $order->order_status) : 'deleted';
@endphp

<section class="sms-breadcrumb mb-20">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-banner d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h1>Challan {{ $challan->challan_no }}</h1>
                        <div class="small text-muted">
                            Mushak 6.3 · issued {{ $challan->issued_at->format('d M Y, h:i A') }}
                            @if($challan->isIssued()) <span class="badge bg-success ms-1">issued</span>
                            @else <span class="badge bg-secondary ms-1">voided</span> @endif
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.challans.index') }}">All challans</a>
                        <a class="btn btn-sm btn-primary" href="{{ route('admin.vat.challans.pdf', $challan) }}" target="_blank" rel="noopener">Printed copy (PDF)</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container-fluid">
    @if($challan->isIssued() && $order && ! $orderRecognised)
        <div class="alert alert-warning">
            The sale is now <strong>{{ $orderStatusLabel }}</strong>. This challan stays as it was issued. Any reversal is recorded
            on the sale's VAT credit note, not by changing this challan.
        </div>
    @endif
    @if(! $challan->isIssued())
        <div class="alert alert-secondary">
            Voided on {{ $challan->voided_at?->format('d M Y, h:i A') }}: {{ $challan->void_reason }}. The number is kept on record.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="white-box h-100">
                <h4>Seller and buyer</h4>
                <table class="table table-sm mb-0">
                    <tr><th style="width: 40%">Seller (registered person)</th><td>{{ $challan->seller_name }}</td></tr>
                    <tr><th>Seller BIN</th><td>{{ $challan->seller_bin }}</td></tr>
                    <tr><th>Issue address</th><td>{{ $challan->seller_address }}</td></tr>
                    <tr><th>Buyer</th><td>{{ $challan->buyer_name }}</td></tr>
                    <tr><th>Buyer BIN</th><td>{{ $challan->buyer_bin }}</td></tr>
                    <tr><th>Destination of supply</th><td>{{ $challan->destination }}</td></tr>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="white-box h-100">
                <h4>Document</h4>
                <table class="table table-sm mb-0">
                    <tr><th style="width: 40%">Challan number</th><td>{{ $challan->challan_no }}</td></tr>
                    <tr><th>Issue date and time</th><td>{{ $challan->issue_date->format('d M Y') }} {{ $challan->issue_time }}</td></tr>
                    <tr><th>Invoice</th><td>{{ $challan->invoice_no }}</td></tr>
                    <tr><th>Sale</th><td>{{ $order ? $order->invoice_id : 'deleted' }} · {{ $orderStatusLabel }}</td></tr>
                    <tr><th>Printed copy SHA-256</th><td class="small text-break">{{ $challan->pdf_sha256 }}</td></tr>
                </table>
            </div>
        </div>

        <div class="col-12">
            <div class="white-box">
                <h4>Lines</h4>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>No.</th><th>Description</th><th>Unit</th><th class="text-end">Qty</th>
                            <th class="text-end">Unit value</th><th class="text-end">Value</th><th class="text-end">Rate</th>
                            <th class="text-end">VAT</th><th class="text-end">Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($challan->lines as $line)
                            <tr>
                                <td>{{ $line->serial }}</td>
                                <td>{{ $line->description }}</td>
                                <td>{{ $line->unit }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float) $line->quantity, 3), '0'), '.') }}</td>
                                <td class="text-end">{{ $money($line->unit_value) }}</td>
                                <td class="text-end">{{ $money($line->line_value) }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float) $line->vat_rate, 2), '0'), '.') }}%</td>
                                <td class="text-end">{{ $money($line->vat_amount) }}</td>
                                <td class="text-end">{{ $money($line->line_total) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total</th>
                            <th class="text-end">{{ $money($challan->total_value) }}</th>
                            <th></th>
                            <th class="text-end">{{ $money($challan->total_vat) }}</th>
                            <th class="text-end">{{ $money($challan->total_with_tax) }}</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @if($challan->isIssued())
            <div class="col-lg-6">
                <div class="white-box h-100">
                    <h4>Void this challan</h4>
                    <p class="small text-muted">The number stays on record. The sale can then be challaned again under a new number. Give the reason.</p>
                    <form method="POST" action="{{ route('admin.vat.challans.void', $challan) }}" class="d-flex flex-column gap-2">
                        @csrf
                        <textarea name="reason" rows="2" class="form-control" maxlength="500" required placeholder="Reason for voiding"></textarea>
                        <div><button type="submit" class="btn btn-sm btn-outline-danger">Void challan</button></div>
                    </form>
                </div>
            </div>
        @endif

        <div class="col-lg-{{ $challan->isIssued() ? '6' : '12' }}">
            <div class="white-box h-100">
                <h4>History</h4>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>When</th><th>Event</th><th>By</th><th>IP</th><th>Details</th></tr></thead>
                        <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td class="small text-nowrap">{{ $event->created_at->format('d M Y, h:i:s A') }}</td>
                                <td>{{ str_replace('_', ' ', $event->event) }}</td>
                                <td>{{ $event->user_id ?? 'system' }}</td>
                                <td class="small">{{ $event->ip }}</td>
                                <td class="small text-break">
                                    @foreach(($event->details ?? []) as $key => $value)
                                        <div><strong>{{ $key }}</strong>: {{ is_array($value) ? json_encode($value) : $value }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No history recorded.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
