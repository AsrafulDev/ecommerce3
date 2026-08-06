@extends('backEnd.layouts.master')
@section('title', 'Warranty Claims')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h4 class="mb-0">🔧 Warranty Claims</h4>
        <form method="GET" action="{{ route('admin.warranty.claims.index') }}" class="d-flex align-items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" style="width:220px;" placeholder="Claim #, customer, phone, product...">
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                <option value="">All Statuses</option>
                @foreach(\App\Enums\WarrantyClaimStatus::cases() as $st)
                    <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary"><i class="fa fa-search me-1"></i>Filter</button>
            @if(request('search') || request('status'))
                <a href="{{ route('admin.warranty.claims.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-refresh me-1"></i>Clear</a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Claim #</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Issue</th>
                            <th>Status</th>
                            <th>Claimed</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($claims as $claim)
                        <tr>
                            <td><code>{{ $claim->claim_number }}</code></td>
                            <td>{{ $claim->customer->name ?? 'N/A' }}</td>
                            <td>{{ $claim->product->name ?? 'N/A' }}</td>
                            <td>{{ Str::limit($claim->issue_description, 40) }}</td>
                            <td>
                                <span class="badge bg-{{ $claim->status_enum->badgeClass() }}">
                                    {{ $claim->status_enum->label() }}
                                </span>
                            </td>
                            <td>{{ $claim->created_at->format('d M, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.warranty.claims.show', $claim) }}" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No warranty claims found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $claims->links() }}
        </div>
    </div>
</div>
@endsection
