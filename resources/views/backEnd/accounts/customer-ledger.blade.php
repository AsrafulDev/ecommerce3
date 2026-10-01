@extends('backEnd.layouts.master')
@section('title', 'Customer Ledger')
@section('content')
<div class="container-fluid"><h3>{{ $customer->name }} — Customer Ledger</h3><p>Existing due: <strong>{{ number_format($due, 2) }}</strong></p><div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Transaction</th><th>Reference</th><th>Charge</th><th>Payment</th><th>Balance</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ optional($row['date'])->format('d M Y') }}</td><td>{{ $row['label'] }}</td><td>{{ $row['reference'] }}</td><td>{{ number_format($row['charge'], 2) }}</td><td>{{ number_format($row['payment'], 2) }}</td><td>{{ number_format($row['balance'], 2) }}</td></tr>@endforeach</tbody></table></div></div></div>
@endsection
