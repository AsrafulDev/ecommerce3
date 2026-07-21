@extends('backEnd.layouts.master')
@section('title', 'Supplier Warranties')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>📦 Supplier Warranties</h4>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Product</th>
                            <th>Warranty Days</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Remaining</th>
                            <th>Transferable</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warranties as $w)
                        <tr>
                            <td>{{ $w->id }}</td>
                            <td>{{ $w->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $w->product->name ?? 'N/A' }}</td>
                            <td>{{ $w->warranty_days }} days</td>
                            <td>{{ $w->warranty_start_date?->format('d M, Y') ?? 'N/A' }}</td>
                            <td>{{ $w->warranty_end_date?->format('d M, Y') ?? 'N/A' }}</td>
                            <td>
                                @if($w->is_valid)
                                    <span class="badge bg-success">{{ $w->remaining_days }} days</span>
                                @else
                                    <span class="badge bg-danger">Expired</span>
                                @endif
                            </td>
                            <td>{{ $w->is_transferable ? '✅' : '❌' }}</td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editModal{{ $w->id }}">Edit</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center">No supplier warranties found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $warranties->links() }}
        </div>
    </div>
</div>
@endsection
