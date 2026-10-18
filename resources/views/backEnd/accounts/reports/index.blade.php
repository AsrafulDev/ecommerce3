@extends('backEnd.layouts.master')
@section('title', 'Finance Reports')
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Finance Reports</h3>
            <p class="text-muted mb-0">Balance and ledger reports. Read-only: nothing on these pages writes money.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounts.dashboard') }}">Transactions overview</a>
        <a class="btn btn-outline-primary" href="{{ route('admin.finance.masters.index', 'account-category') }}">Finance setup</a>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white"><strong>Enter money</strong></div>
        <div class="list-group list-group-flush">
            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                <div>
                    <div class="fw-semibold">Receipt Entry</div>
                    <div class="small text-muted">Money received from a customer, against one or more open invoices.</div>
                </div>
                <a class="btn btn-sm btn-primary" href="{{ route('admin.accounts.entry.receipt') }}">Open</a>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                <div>
                    <div class="fw-semibold">Payment Entry</div>
                    <div class="small text-muted">Money paid to a supplier, against one or more open purchases.</div>
                </div>
                <a class="btn btn-sm btn-primary" href="{{ route('admin.accounts.entry.payment') }}">Open</a>
            </div>
        </div>
    </div>

    @foreach ($reports as $group => $items)
    <div class="card mb-3">
        <div class="card-header bg-white"><strong>{{ ucfirst($group) }} reports</strong></div>
        <div class="list-group list-group-flush">
            @foreach ($items as $slug => $item)
                @php [$label, $status, $desc] = [$item[0], $item[1], $item[2]]; @endphp
                <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-semibold">{{ $label }}</div>
                        <div class="small text-muted">{{ $desc }}</div>
                    </div>
                    @if ($status === 'available')
                        <a class="btn btn-sm btn-primary" href="{{ route('admin.accounts.reports.show', $slug) }}">Open</a>
                    @elseif ($status === 'link')
                        <a class="btn btn-sm btn-outline-primary" href="{{ route($item[3]) }}">Open</a>
                    @else
                        <span class="badge bg-light text-secondary border">Not built yet</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection
