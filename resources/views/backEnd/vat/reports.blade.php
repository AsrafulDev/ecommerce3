@extends('backEnd.layouts.master')
@section('title', 'VAT Reports')

@section('content')
<section class="sms-breadcrumb mb-20">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-banner d-flex flex-wrap justify-content-between align-items-center">
                    <h1>VAT Reports (working draft)</h1>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container-fluid">
    @unless($enabled)
        <div class="alert alert-warning">VAT is switched off. These figures will be empty until it is turned on in <a href="{{ route('admin.vat.edit') }}">VAT settings</a>.</div>
    @endunless

    <div class="white-box mb-3">
        <form method="GET" action="{{ route('admin.vat.reports') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control" value="{{ $from->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control" value="{{ $to->toDateString() }}">
            </div>
            <div class="col-md-2">
                <button class="primary-btn fix-gr-bg" type="submit">Show</button>
            </div>
            <div class="col-md-4 text-end">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.export', ['type' => 'sales', 'mode' => 'range', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Sales CSV</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vat.export', ['type' => 'purchase', 'mode' => 'range', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Purchase CSV</a>
            </div>
        </form>
    </div>

    <div class="white-box mb-3">
        <h4>Download a register (working draft, not the official Mushak layout)</h4>
        <p class="small text-muted mb-2">Choose one invoice, one day, or a date range. Sales are by invoice date; purchases by purchase date.</p>
        <div class="row g-2">
            @foreach(['sales' => 'Sales', 'purchase' => 'Purchase'] as $kind => $kindLabel)
            <div class="col-md-6">
                <form method="GET" action="{{ route('admin.vat.export', ['type' => $kind]) }}" class="border rounded p-2 mb-2">
                    <strong>{{ $kindLabel }}</strong>
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
                        <label><input type="radio" name="format" value="pdf"> PDF (working draft)</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-2">Download</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>

    <div class="white-box mb-3">
        <h4>Return summary (9.1 working)</h4>
        <table class="table table-sm mb-2">
            <tr><th>Output VAT (sales)</th><td class="text-end">{{ number_format($summary['sales_vat'], 2) }}</td></tr>
            <tr><th>Less credit notes on sales</th><td class="text-end">{{ number_format($summary['sales_credit_vat'], 2) }}</td></tr>
            <tr><th>Output VAT (net)</th><td class="text-end"><strong>{{ number_format($summary['output_vat'], 2) }}</strong></td></tr>
            <tr><th>Input VAT (purchases)</th><td class="text-end">{{ number_format($summary['purchase_vat'], 2) }}</td></tr>
            <tr><th>Less credit notes on purchases</th><td class="text-end">{{ number_format($summary['purchase_credit_vat'], 2) }}</td></tr>
            <tr><th>Input VAT (net)</th><td class="text-end"><strong>{{ number_format($summary['input_vat'], 2) }}</strong></td></tr>
            <tr><th>Net (positive = payable, negative = carry-forward)</th><td class="text-end"><strong>{{ number_format($summary['net'], 2) }}</strong></td></tr>
        </table>
        @if($summary['sales_lines_without_vat'] || $summary['purchase_lines_without_vat'])
            <div class="alert alert-warning mb-0">
                {{ $summary['sales_lines_without_vat'] }} sales lines and {{ $summary['purchase_lines_without_vat'] }} purchase lines have no VAT recorded. Set the product VAT rate and recheck before filing.
            </div>
        @endif
    </div>

    <div class="white-box mb-3">
        <h4>Mushak 9.1 draft by line</h4>
        <div class="alert alert-danger">DRAFT, NOT FOR FILING. Line numbers come from the official form. The mapping from product category to line is an assumption that a practitioner must confirm.</div>
        @if(!empty($draft91['unmapped']))
            <div class="alert alert-warning">
                Some products have no VAT category, so their figures are left out: {{ collect($draft91['unmapped'])->flatten(1)->count() }} group(s). Set a category on those products and recheck.
            </div>
        @endif
        <table class="table table-sm">
            <thead><tr><th>Line</th><th class="text-end">Value</th><th class="text-end">VAT</th></tr></thead>
            <tbody>
            @foreach($draft91['lines'] as $line => $figures)
                <tr><td>{{ $line }}</td><td class="text-end">{{ number_format($figures['value'], 2) }}</td><td class="text-end">{{ number_format($figures['vat'], 2) }}</td></tr>
            @endforeach
            </tbody>
            <tfoot><tr><th>35 net</th><th></th><th class="text-end">{{ number_format($draft91['net'], 2) }}</th></tr></tfoot>
        </table>
    </div>

    <div class="white-box mb-3">
        <h4>Sales register (Mushak 6.2 working)</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Invoice</th><th>Date</th><th>Buyer BIN</th><th class="text-end">Taxable</th><th class="text-end">Output VAT</th></tr></thead>
                <tbody>
                @forelse($sales as $row)
                    <tr><td>{{ $row['invoice'] }}</td><td>{{ $row['date'] }}</td><td>{{ $row['buyer_bin'] ?? '—' }}</td>
                        <td class="text-end">{{ number_format($row['taxable'], 2) }}</td><td class="text-end">{{ number_format($row['vat'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No sales with VAT in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="white-box mb-3">
        <h4>Purchase register (Mushak 6.1 working)</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Invoice</th><th>Date</th><th>Supplier</th><th>Supplier BIN</th><th class="text-end">Taxable</th><th class="text-end">Input VAT</th></tr></thead>
                <tbody>
                @forelse($purchases as $row)
                    <tr><td>{{ $row['invoice'] }}</td><td>{{ $row['date'] }}</td><td>{{ $row['supplier_name'] ?? '—' }}</td><td>{{ $row['supplier_bin'] ?? '—' }}</td>
                        <td class="text-end">{{ number_format($row['taxable'], 2) }}</td><td class="text-end">{{ number_format($row['vat'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No purchases with VAT in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
