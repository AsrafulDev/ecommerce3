@extends('backEnd.layouts.master')
@section('title', 'Warranty Sale #' . $warrantySale->id)
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>🧾 Warranty Sale #{{ $warrantySale->id }}</h4>
        <a href="{{ route('admin.warranty.sales.index') }}" class="btn btn-sm btn-secondary">← Back</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header"><strong>Details</strong></div>
                <div class="card-body">
                    <table class="table table-bordered mb-0">
                        <tr><th style="width:180px">Customer</th><td>{{ $warrantySale->customer->name ?? 'N/A' }} ({{ $warrantySale->customer->phone ?? '' }})</td></tr>
                        <tr><th>Product</th><td>{{ $warrantySale->product->name ?? 'N/A' }}</td></tr>
                        <tr><th>Order</th><td>#{{ $warrantySale->order_id }} ({{ $warrantySale->order->invoice_id ?? 'N/A' }})</td></tr>
                        <tr><th>Warranty Type</th><td>{{ ucfirst($warrantySale->warranty_type) }}</td></tr>
                        <tr><th>Warranty Days</th><td>{{ $warrantySale->warranty_days }} days</td></tr>
                        @if($warrantySale->supplier_warranty_id)
                        <tr><th>Supplier Warranty</th><td>
                            @php $sw = \App\Models\SupplierWarranty::find($warrantySale->supplier_warranty_id); @endphp
                            @if($sw)
                                {{ $sw->supplier->name ?? 'N/A' }} — 
                                {{ $sw->warranty_start_date?->format('d M Y') }} to {{ $sw->warranty_end_date?->format('d M Y') }}
                                ({{ $sw->remaining_days }}d left)
                            @endif
                        </td></tr>
                        @endif
                        <tr><th>Start Date</th><td>{{ $warrantySale->warranty_start_date?->format('d M, Y') ?? 'Not activated' }}</td></tr>
                        <tr><th>End Date</th><td>{{ $warrantySale->warranty_end_date?->format('d M, Y') ?? 'N/A' }}</td></tr>
                        <tr><th>Price</th><td>{{ number_format($warrantySale->warranty_price, 2) }} TK</td></tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-{{ \App\Enums\WarrantySaleStatus::from($warrantySale->status)->badgeClass() }}">
                                    {{ ucfirst($warrantySale->status) }}
                                </span>
                                @if($warrantySale->status === 'active')
                                    <form action="{{ route('admin.warranty.sales.void', $warrantySale) }}" method="POST" class="d-inline ms-2">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Void this warranty?')">Void</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @if($warrantySale->warranty_days > 0 && $warrantySale->warranty_start_date)
                        <tr>
                            <th>Progress</th>
                            <td>
                                <div class="progress" style="height:20px">
                                    <div class="progress-bar bg-{{ $warrantySale->warranty_progress_percent > 80 ? 'danger' : 'success' }}"
                                        style="width:{{ $warrantySale->warranty_progress_percent }}%">
                                        {{ $warrantySale->warranty_progress_percent }}%
                                    </div>
                                </div>
                                <small>{{ $warrantySale->remaining_days }} days remaining</small>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($warrantySale->claims->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><strong>🔧 Claims</strong></div>
                <div class="card-body">
                    @foreach($warrantySale->claims as $claim)
                    <div class="border-bottom pb-2 mb-2">
                        <a href="{{ route('admin.warranty.claims.show', $claim) }}">
                            <strong>{{ $claim->claim_number }}</strong>
                        </a>
                        — <span class="badge bg-{{ $claim->status_enum->badgeClass() }}">{{ $claim->status_enum->label() }}</span>
                        <br><small>{{ Str::limit($claim->issue_description, 80) }}</small>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
