@extends('backEnd.layouts.master')
@section('title', 'VAT Reports')

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 2);
    $exceptionCount = count($exceptions['sales']) + count($exceptions['purchases']) + count($exceptions['uncategorised']);
    $reconciliationOk = collect($reconciliation)->every(fn ($check) => $check['agrees']);
    $netLabel = $summary['net'] >= 0 ? 'Payable' : 'Carry-forward';
    $kinds = [
        'sales'            => ['label' => 'Sales register (6.2)', 'pdf' => true],
        'purchase'         => ['label' => 'Purchase register (6.1)', 'pdf' => true],
        'credit-sales'     => ['label' => 'Credit notes on sales', 'pdf' => false],
        'credit-purchases' => ['label' => 'Credit notes on purchases', 'pdf' => false],
    ];
    $periodQuery = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
@endphp

<style>
    .vat-kpi .vat-kpi-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
    .vat-kpi .vat-kpi-value { font-size: 22px; font-weight: 600; line-height: 1.2; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .vat-table th { font-size: 12px; text-transform: uppercase; letter-spacing: .03em; color: #6b7280; font-weight: 600; white-space: nowrap; }
    .vat-table td { font-variant-numeric: tabular-nums; vertical-align: middle; }
    .vat-table tfoot th, .vat-table tfoot td { font-weight: 600; border-top: 2px solid #e5e7eb; }
    .vat-period .btn.active { font-weight: 600; }
    .vat-empty { color: #6b7280; padding: 16px 0; }
    @media print {
        .no-print, .nav-tabs, form, .btn, .sms-breadcrumb .btn { display: none !important; }
        .tab-content > .tab-pane { display: block !important; opacity: 1 !important; }
        .white-box { box-shadow: none !important; border: 0 !important; break-inside: avoid; }
    }
</style>

<section class="sms-breadcrumb mb-20">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-banner d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h1>VAT Reports</h1>
                        <div class="small text-muted">{{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</div>
                    </div>
                    <div class="d-flex gap-2 no-print">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.challans.index') }}">Mushak 6.3 challans</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.edit') }}">VAT settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container-fluid">
    @unless($enabled)
        <div class="alert alert-warning">VAT is switched off. These figures will be empty until it is turned on in <a href="{{ route('admin.vat.edit') }}">VAT settings</a>.</div>
    @endunless

    <div class="white-box mb-3 no-print">
        <div class="d-flex flex-wrap gap-2 align-items-end justify-content-between vat-period">
            <div class="btn-group btn-group-sm" role="group" aria-label="Quick periods">
                @foreach($presets as $name => [$start, $end])
                    <a class="btn btn-outline-secondary {{ $from->isSameDay($start) && $to->isSameDay($end) ? 'active' : '' }}"
                       href="{{ route('admin.vat.reports', ['from' => $start->toDateString(), 'to' => $end->toDateString()]) }}">{{ $name }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.vat.reports') }}" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label small mb-1" for="vat-from">From</label>
                    <input id="vat-from" type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}">
                </div>
                <div>
                    <label class="form-label small mb-1" for="vat-to">To</label>
                    <input id="vat-to" type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}">
                </div>
                <button class="primary-btn fix-gr-bg" type="submit">Show</button>
            </form>
        </div>
        <div class="small text-muted mt-2">
            Sales are counted in the period they were made, for delivered or completed orders. A return reduces the period it is issued in.
            Purchases are counted by purchase date.
        </div>
    </div>

    @if($draft91['status'] === 'final')
        <div class="alert alert-success mb-3">Final: no open items for this period. Line numbers follow the official Mushak 9.1 form.</div>
    @else
        <div class="alert alert-warning mb-3">
            <strong>Not final.</strong> These items must be closed before this return can be filed:
            <ul class="mb-0">
                @foreach($draft91['blockers'] as $blocker)
                    <li>{{ $blocker }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="white-box h-100 vat-kpi">
                <div class="vat-kpi-label">Output VAT (net)</div>
                <div class="vat-kpi-value">{{ $money($summary['output_vat']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="white-box h-100 vat-kpi">
                <div class="vat-kpi-label">Input VAT (net)</div>
                <div class="vat-kpi-value">{{ $money($summary['input_vat']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="white-box h-100 vat-kpi">
                <div class="vat-kpi-label">Net: {{ $netLabel }}</div>
                <div class="vat-kpi-value">{{ $money(abs($summary['net'])) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="white-box h-100 vat-kpi">
                <div class="vat-kpi-label">Lines to fix</div>
                <div class="vat-kpi-value {{ $exceptionCount > 0 ? 'text-warning' : '' }}">{{ $exceptionCount }}</div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs nav-bordered mb-3 no-print" role="tablist">
        <li class="nav-item"><a href="#vat-return" data-bs-toggle="tab" class="nav-link active">Return (9.1)</a></li>
        <li class="nav-item"><a href="#vat-sales" data-bs-toggle="tab" class="nav-link">Sales register (6.2)</a></li>
        <li class="nav-item"><a href="#vat-purchases" data-bs-toggle="tab" class="nav-link">Purchase register (6.1)</a></li>
        <li class="nav-item"><a href="#vat-credit" data-bs-toggle="tab" class="nav-link">Credit notes</a></li>
        <li class="nav-item">
            <a href="#vat-exceptions" data-bs-toggle="tab" class="nav-link">
                Exceptions @if($exceptionCount > 0)<span class="badge bg-warning text-dark ms-1">{{ $exceptionCount }}</span>@endif
            </a>
        </li>
        <li class="nav-item"><a href="#vat-downloads" data-bs-toggle="tab" class="nav-link">Downloads</a></li>
    </ul>

    <div class="tab-content">

        {{-- Return (9.1) --}}
        <div class="tab-pane fade show active" id="vat-return">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="white-box h-100">
                        <h4>Return summary (Mushak 9.1)</h4>
                        <table class="table table-sm vat-table mb-2">
                            <tbody>
                            <tr><th>Output VAT (sales)</th><td class="text-end">{{ $money($summary['sales_vat']) }}</td></tr>
                            <tr><th>Less credit notes on sales</th><td class="text-end">{{ $money($summary['sales_credit_vat']) }}</td></tr>
                            <tr><th>Output VAT (net)</th><td class="text-end"><strong>{{ $money($summary['output_vat']) }}</strong></td></tr>
                            <tr><th>Input VAT (purchases)</th><td class="text-end">{{ $money($summary['purchase_vat']) }}</td></tr>
                            <tr><th>Less credit notes on purchases</th><td class="text-end">{{ $money($summary['purchase_credit_vat']) }}</td></tr>
                            <tr><th>Input VAT (net)</th><td class="text-end"><strong>{{ $money($summary['input_vat']) }}</strong></td></tr>
                            <tr><th>Net (positive = payable, negative = carry-forward)</th><td class="text-end"><strong>{{ $money($summary['net']) }}</strong></td></tr>
                            </tbody>
                        </table>
                        @if($summary['sales_lines_without_vat'] || $summary['purchase_lines_without_vat'])
                            <div class="alert alert-warning mb-0">
                                {{ $summary['sales_lines_without_vat'] }} sales lines and {{ $summary['purchase_lines_without_vat'] }} purchase lines have no VAT recorded. See the Exceptions tab, then recheck before filing.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="white-box h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="mb-0">Reconciliation</h4>
                            @if($reconciliationOk)
                                <span class="badge bg-success">All checks agree</span>
                            @else
                                <span class="badge bg-danger">A check differs</span>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm vat-table mb-0">
                                <thead><tr><th>Check</th><th class="text-end">Figure A</th><th class="text-end">Figure B</th><th class="text-end">Status</th></tr></thead>
                                <tbody>
                                @foreach($reconciliation as $check)
                                    <tr>
                                        <td class="small">{{ $check['label'] }}</td>
                                        <td class="text-end">{{ $money($check['left']) }}</td>
                                        <td class="text-end">{{ $money($check['right']) }}</td>
                                        <td class="text-end">
                                            @if($check['agrees'])
                                                <span class="badge bg-success">Agrees</span>
                                            @else
                                                <span class="badge bg-danger">Differs by {{ $money($check['difference']) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="white-box">
                        <h4>Mushak 9.1 by line</h4>
                        @if(!empty($draft91['unmapped']))
                            <div class="alert alert-warning">
                                Some products have no VAT category, so their figures are left out: {{ collect($draft91['unmapped'])->flatten(1)->count() }} group(s). Set a category on those products and recheck.
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-sm vat-table">
                                <thead><tr><th>Line</th><th class="text-end">Value</th><th class="text-end">VAT</th></tr></thead>
                                <tbody>
                                @foreach($draft91['lines'] as $line => $figures)
                                    <tr><td>{{ $line }}</td><td class="text-end">{{ $money($figures['value']) }}</td><td class="text-end">{{ $money($figures['vat']) }}</td></tr>
                                @endforeach
                                </tbody>
                                <tfoot><tr><th>35 net</th><th></th><th class="text-end">{{ $money($draft91['net']) }}</th></tr></tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="white-box">
                        <h4>VAT by product category</h4>
                        <p class="small text-muted mb-2">Each category feeds one 9.1 line. A category with no line is shown as unmapped and is left out of the return until it is mapped.</p>
                        <div class="table-responsive">
                            <table class="table table-sm vat-table mb-0">
                                <thead>
                                <tr>
                                    <th rowspan="2" class="align-bottom">Category</th>
                                    <th colspan="3" class="text-center">Sales</th>
                                    <th colspan="3" class="text-center">Purchases</th>
                                </tr>
                                <tr>
                                    <th>9.1 line</th><th class="text-end">Taxable</th><th class="text-end">VAT</th>
                                    <th>9.1 line</th><th class="text-end">Taxable</th><th class="text-end">VAT</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($categories as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td>
                                            @if($row['sales_line'] !== null) {{ $row['sales_line'] }} @else <span class="badge bg-warning text-dark">unmapped</span> @endif
                                        </td>
                                        <td class="text-end">{{ $money($row['sales']['taxable']) }}</td>
                                        <td class="text-end">{{ $money($row['sales']['vat']) }}</td>
                                        <td>
                                            @if($row['purchase_line'] !== null) {{ $row['purchase_line'] }} @else <span class="badge bg-warning text-dark">unmapped</span> @endif
                                        </td>
                                        <td class="text-end">{{ $money($row['purchases']['taxable']) }}</td>
                                        <td class="text-end">{{ $money($row['purchases']['vat']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="vat-empty">No VAT was recorded in this period.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="white-box">
                        <h4>Mushak forms in this app</h4>
                        <div class="table-responsive">
                            <table class="table table-sm vat-table mb-0">
                                <thead><tr><th>Form</th><th>Status here</th><th>Note</th></tr></thead>
                                <tbody>
                                <tr><td>6.1 purchase book</td><td><span class="badge bg-info text-dark">Draft book (PDF)</span></td><td class="small">Column layout follows the NBR form; not approved for filing.</td></tr>
                                <tr><td>6.2 sales book</td><td><span class="badge bg-info text-dark">Draft book (PDF)</span></td><td class="small">Column layout follows the NBR form; not approved for filing.</td></tr>
                                <tr><td>9.1 VAT return</td><td><span class="badge bg-info text-dark">Working figures</span></td><td class="small">Shown above. Final only when every blocker is closed.</td></tr>
                                <tr><td>Credit notes</td><td><span class="badge bg-secondary">Register</span></td><td class="small">Reverse VAT on returned sales and purchases. Not a numbered Mushak form.</td></tr>
                                <tr><td>6.3 tax challan</td><td><span class="badge bg-info text-dark">Per sale</span></td><td class="small">For sales to VAT-registered buyers. Issued from <a href="{{ route('admin.vat.challans.index') }}">the challans page</a>. Layout not yet reviewed.</td></tr>
                                <tr><td>6.10 challan list</td><td><span class="badge bg-light text-dark">Not produced</span></td><td class="small">Needed only if challans above Tk 2,00,000 are issued. Not built.</td></tr>
                                <tr><td>6.5, 6.2.1, 9.2</td><td><span class="badge bg-light text-dark">Not produced</span></td><td class="small">Specific cases only: branch transfers, non-business persons, turnover tax.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sales register (6.2 working) --}}
        <div class="tab-pane fade" id="vat-sales">
            <div class="white-box">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <h4 class="mb-0">Sales register (Mushak 6.2 working)</h4>
                        <div class="small text-muted">One row per invoice. Working register; the PDF is the book layout.</div>
                    </div>
                    <div class="no-print d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.export', ['type' => 'sales', 'mode' => 'range'] + $periodQuery) }}">CSV</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.export', ['type' => 'sales', 'mode' => 'range', 'format' => 'pdf'] + $periodQuery) }}">PDF book (6.2)</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm vat-table">
                        <thead><tr><th>Invoice</th><th>Date</th><th>Buyer BIN</th><th class="text-end">Taxable</th><th class="text-end">Output VAT</th></tr></thead>
                        <tbody>
                        @forelse($sales as $row)
                            <tr>
                                <td>{{ $row['invoice'] }}</td>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['buyer_bin'] ?? '—' }}</td>
                                <td class="text-end">{{ $money($row['taxable']) }}</td>
                                <td class="text-end">{{ $money($row['vat']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="vat-empty">No sales with VAT in this period.</td></tr>
                        @endforelse
                        </tbody>
                        @if(count($sales))
                            <tfoot>
                            <tr>
                                <th colspan="3">{{ count($sales) }} invoice(s)</th>
                                <th class="text-end">{{ $money(collect($sales)->sum('taxable')) }}</th>
                                <th class="text-end">{{ $money(collect($sales)->sum('vat')) }}</th>
                            </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Purchase register (6.1 working) --}}
        <div class="tab-pane fade" id="vat-purchases">
            <div class="white-box">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <h4 class="mb-0">Purchase register (Mushak 6.1 working)</h4>
                        <div class="small text-muted">One row per purchase invoice. Working register; the PDF is the book layout.</div>
                    </div>
                    <div class="no-print d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.export', ['type' => 'purchase', 'mode' => 'range'] + $periodQuery) }}">CSV</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.export', ['type' => 'purchase', 'mode' => 'range', 'format' => 'pdf'] + $periodQuery) }}">PDF book (6.1)</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm vat-table">
                        <thead><tr><th>Invoice</th><th>Date</th><th>Supplier</th><th>Supplier BIN</th><th class="text-end">Taxable</th><th class="text-end">Input VAT</th></tr></thead>
                        <tbody>
                        @forelse($purchases as $row)
                            <tr>
                                <td>{{ $row['invoice'] }}</td>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['supplier_name'] ?? '—' }}</td>
                                <td>{{ $row['supplier_bin'] ?? '—' }}</td>
                                <td class="text-end">{{ $money($row['taxable']) }}</td>
                                <td class="text-end">{{ $money($row['vat']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="vat-empty">No purchases with VAT in this period.</td></tr>
                        @endforelse
                        </tbody>
                        @if(count($purchases))
                            <tfoot>
                            <tr>
                                <th colspan="4">{{ count($purchases) }} invoice(s)</th>
                                <th class="text-end">{{ $money(collect($purchases)->sum('taxable')) }}</th>
                                <th class="text-end">{{ $money(collect($purchases)->sum('vat')) }}</th>
                            </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Credit notes --}}
        <div class="tab-pane fade" id="vat-credit">
            @foreach([
                'sales' => ['title' => 'Credit notes on sales', 'rows' => $creditSales, 'party' => 'Buyer', 'original' => 'Invoice', 'type' => 'credit-sales', 'vat' => 'Output VAT reversed'],
                'purchases' => ['title' => 'Credit notes on purchases', 'rows' => $creditPurchases, 'party' => 'Supplier', 'original' => 'Purchase invoice', 'type' => 'credit-purchases', 'vat' => 'Input VAT reversed'],
            ] as $block)
                <div class="white-box mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div>
                            <h4 class="mb-0">{{ $block['title'] }}</h4>
                            <div class="small text-muted">Dated by the day the note was issued. Reverses VAT declared on the original invoice.</div>
                        </div>
                        <div class="no-print">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.export', ['type' => $block['type'], 'mode' => 'range'] + $periodQuery) }}">CSV</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm vat-table">
                            <thead>
                            <tr>
                                <th>Credit note</th><th>Date</th><th>{{ $block['original'] }}</th><th>{{ $block['party'] }}</th>
                                <th class="text-end">Taxable</th><th class="text-end">{{ $block['vat'] }}</th><th>Reason</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($block['rows'] as $row)
                                <tr>
                                    <td>{{ $row['number'] }}</td>
                                    <td>{{ $row['date'] }}</td>
                                    <td>{{ $row['original'] }}</td>
                                    <td>{{ $row['party'] ?? '—' }}</td>
                                    <td class="text-end">{{ $money($row['taxable']) }}</td>
                                    <td class="text-end">{{ $money($row['vat']) }}</td>
                                    <td class="small">{{ $row['reason'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="vat-empty">No credit notes in this period.</td></tr>
                            @endforelse
                            </tbody>
                            @if(count($block['rows']))
                                <tfoot>
                                <tr>
                                    <th colspan="4">{{ count($block['rows']) }} note(s)</th>
                                    <th class="text-end">{{ $money(collect($block['rows'])->sum('taxable')) }}</th>
                                    <th class="text-end">{{ $money(collect($block['rows'])->sum('vat')) }}</th>
                                    <th></th>
                                </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Exceptions --}}
        <div class="tab-pane fade" id="vat-exceptions">
            @if($exceptionCount === 0)
                <div class="alert alert-success mb-0">Nothing to fix in this period. Every line carries VAT and every product has a category.</div>
            @else
                <div class="alert alert-warning">These lines are left out of the figures until they are fixed. Set the product's VAT rate and category, then recheck.</div>

                @if(count($exceptions['uncategorised']))
                    <div class="white-box mb-3">
                        <h4>Products with no VAT category</h4>
                        <div class="table-responsive">
                            <table class="table table-sm vat-table mb-0">
                                <thead><tr><th>Side</th><th>Product</th><th class="text-end">Lines</th><th class="text-end">VAT</th></tr></thead>
                                <tbody>
                                @foreach($exceptions['uncategorised'] as $row)
                                    <tr><td>{{ $row['side'] === 'sales' ? 'Sales' : 'Purchases' }}</td><td>{{ $row['product'] }}</td><td class="text-end">{{ $row['lines'] }}</td><td class="text-end">{{ $money($row['vat']) }}</td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(count($exceptions['sales']))
                    <div class="white-box mb-3">
                        <h4>Sales lines with no VAT recorded</h4>
                        <div class="table-responsive">
                            <table class="table table-sm vat-table mb-0">
                                <thead><tr><th>Invoice</th><th>Date</th><th>Product</th><th class="text-end">Qty</th></tr></thead>
                                <tbody>
                                @foreach($exceptions['sales'] as $row)
                                    <tr><td>{{ $row['invoice'] }}</td><td>{{ $row['date'] }}</td><td>{{ $row['product'] }}</td><td class="text-end">{{ rtrim(rtrim(number_format($row['qty'], 2), '0'), '.') }}</td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(count($exceptions['purchases']))
                    <div class="white-box mb-3">
                        <h4>Purchase lines with no VAT recorded</h4>
                        <div class="table-responsive">
                            <table class="table table-sm vat-table mb-0">
                                <thead><tr><th>Invoice</th><th>Date</th><th>Product</th><th class="text-end">Qty</th></tr></thead>
                                <tbody>
                                @foreach($exceptions['purchases'] as $row)
                                    <tr><td>{{ $row['invoice'] }}</td><td>{{ $row['date'] }}</td><td>{{ $row['product'] }}</td><td class="text-end">{{ rtrim(rtrim(number_format($row['qty'], 2), '0'), '.') }}</td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Downloads --}}
        <div class="tab-pane fade" id="vat-downloads">
            <div class="white-box">
                <h4>Download a register</h4>
                <p class="small text-muted mb-3">Choose one invoice, one day, or a date range. Sales are by invoice date; purchases by purchase date. These are working registers, not filing layouts.</p>
                <div class="row g-3">
                    @foreach($kinds as $kind => $meta)
                    <div class="col-md-6">
                        <form method="GET" action="{{ route('admin.vat.export', ['type' => $kind]) }}" class="border rounded p-3 h-100">
                            <strong>{{ $meta['label'] }}</strong>
                            <div class="mt-2 small">
                                <label class="me-2"><input type="radio" name="mode" value="invoice" checked onchange="this.form.querySelectorAll('[data-mode-field]').forEach(e=>e.style.display='none');this.form.querySelector('[data-mode=invoice]').style.display='block';"> One invoice</label>
                                <label class="me-2"><input type="radio" name="mode" value="date" onchange="this.form.querySelectorAll('[data-mode-field]').forEach(e=>e.style.display='none');this.form.querySelector('[data-mode=date]').style.display='block';"> One day</label>
                                <label><input type="radio" name="mode" value="range" onchange="this.form.querySelectorAll('[data-mode-field]').forEach(e=>e.style.display='none');this.form.querySelector('[data-mode=range]').style.display='block';"> Date range</label>
                            </div>
                            <div class="mt-2" data-mode-field data-mode="invoice">
                                <input type="text" name="invoice" class="form-control form-control-sm" placeholder="Invoice number, e.g. INV-000123">
                            </div>
                            <div class="mt-2" data-mode-field data-mode="date" style="display:none">
                                <input type="date" name="date" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="mt-2 row g-1" data-mode-field data-mode="range" style="display:none">
                                <div class="col-6"><input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}"></div>
                                <div class="col-6"><input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}"></div>
                            </div>
                            <div class="mt-2 small">
                                <label class="me-2"><input type="radio" name="format" value="csv" checked> CSV</label>
                                @if($meta['pdf'])
                                    <label><input type="radio" name="format" value="pdf"> PDF (book layout, draft)</label>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary mt-2">Download</button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
