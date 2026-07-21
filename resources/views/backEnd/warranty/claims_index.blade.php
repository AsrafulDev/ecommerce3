@extends('backEnd.layouts.master')
@section('title', 'Warranty Claims')
@section('content')
<div class="container-fluid">
    <h4 class="mb-3">🔧 Warranty Claims</h4>

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
                        @foreach($claims as $claim)
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $claims->links() }}
        </div>
    </div>
</div>
@endsection
