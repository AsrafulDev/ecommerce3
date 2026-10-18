@extends('backEnd.layouts.master')
@section('title', $title)
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">{{ $title }}</h3>
            <p class="text-muted mb-0">{{ $description }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounts.reports.index') }}">All finance reports</a>
    </div>

    @if ($todo)
        <div class="alert alert-secondary">This report is not built yet.</div>
    @else
        <form method="GET" class="card card-body mb-3">
            <div class="row g-2 align-items-end">
                @php $slug = request()->route('report'); @endphp
                @if (in_array($slug, ['customer-balance', 'supplier-balance']))
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search name</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control form-control-sm">
                </div>
                @else
                <div class="col-md-3">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
                </div>
                @endif
                @if ($slug === 'product-ledger')
                <div class="col-md-4">
                    <label class="form-label small mb-1">Product *</label>
                    <select name="product_id" class="form-select form-select-sm" required>
                        <option value="">Select product…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected($productId === $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if (in_array($slug, ['expense-record', 'receipt-record', 'payment-record']))
                <div class="col-md-3">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control form-control-sm">
                </div>
                @endif
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary">Report</button>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.accounts.reports.show', request()->route('report')) }}">Clear</a>
                </div>
            </div>
        </form>

        @if ($note)
            <p class="small text-muted mb-2">{{ $note }}</p>
        @endif

        @if (!empty($sections))
            @foreach ($sections as $section)
                <div class="card mb-3">
                    <div class="card-header bg-white"><strong>{{ $section['title'] }}</strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>SL</th>
                                    @foreach ($section['columns'] as [$key, $label, $align])
                                        <th class="{{ $align === 'end' ? 'text-end' : '' }}">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($section['rows'] as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        @foreach ($section['columns'] as [$key, $label, $align])
                                            <td class="{{ $align === 'end' ? 'text-end' : '' }}">
                                                @if ($align === 'end' && is_numeric($row[$key] ?? null))
                                                    {{ number_format((float) $row[$key], 2) }}
                                                @else
                                                    {{ $row[$key] ?? '—' }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="{{ count($section['columns']) }}" class="text-end">Total</td>
                                    <td class="text-end">{{ number_format((float) $section['total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
            @if (empty(array_filter($sections, fn ($s) => count($s['rows']) > 0)))
                <div class="card"><div class="card-body text-center text-muted py-4">No rows for this period.</div></div>
            @endif
        @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            @foreach ($columns as [$key, $label, $align])
                                <th class="{{ $align === 'end' ? 'text-end' : '' }}">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                @foreach ($columns as [$key, $label, $align])
                                    <td class="{{ $align === 'end' ? 'text-end' : '' }}">
                                        @php $v = $row[$key] ?? null; @endphp
                                        @if ($align === 'end' && is_numeric($v))
                                            {{ number_format((float) $v, 2) }}
                                        @elseif ($v === null || $v === '')
                                            <span class="text-muted">—</span>
                                        @else
                                            {{ $v }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($columns) + 1 }}" class="text-center text-muted py-4">No rows for this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if (!empty($totals))
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="text-end">
                                @foreach ($totals as $key => $value)
                                    <span class="me-3">{{ ucfirst($key) }}: {{ number_format((float) $value, 2) }}</span>
                                @endforeach
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @endif
    @endif
</div>
@endsection
