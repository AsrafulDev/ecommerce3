@extends('backEnd.layouts.master')
@section('title', 'Supplier Accounts')
@section('content')
<div class="container-fluid"><h3>Supplier Balance & Ledger</h3><div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Supplier</th><th>Phone</th><th>Total Purchases</th><th>Total Paid</th><th>Current Due</th><th></th></tr></thead><tbody>@forelse($suppliers as $supplier)<tr><td>{{ $supplier->name }}</td><td>{{ $supplier->phone }}</td><td>{{ number_format($supplier->purchases_sum_grand_total ?? 0, 2) }}</td><td>{{ number_format($supplier->purchases_sum_paid_amount ?? 0, 2) }}</td><td>{{ number_format($supplier->purchases_sum_due_amount ?? 0, 2) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounts.supplier', $supplier->id) }}">View ledger</a></td></tr>@empty<tr><td colspan="6">No suppliers found.</td></tr>@endforelse</tbody></table></div>{{ $suppliers->links('pagination::bootstrap-4') }}</div></div>
@endsection
