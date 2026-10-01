@extends('backEnd.layouts.master')
@section('title', 'Customer Accounts')
@section('content')
<div class="container-fluid"><h3>Customer Balance & Ledger</h3><div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Customer</th><th>Phone</th><th>Total Sales</th><th>Total Paid</th><th>Current Due</th><th></th></tr></thead><tbody>@forelse($customers as $customer)<tr><td>{{ $customer->name }}</td><td>{{ $customer->phone }}</td><td>{{ number_format($customer->orders_sum_amount ?? 0, 2) }}</td><td>{{ number_format($customer->orders_sum_paid_amount ?? 0, 2) }}</td><td>{{ number_format($customer->orders_sum_due_amount ?? 0, 2) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounts.customer', $customer->id) }}">View ledger</a></td></tr>@empty<tr><td colspan="6">No customers found.</td></tr>@endforelse</tbody></table></div>{{ $customers->links('pagination::bootstrap-4') }}</div></div>
@endsection
