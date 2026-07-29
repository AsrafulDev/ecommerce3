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
                        @if($warrantySale->serial_numbers)
                        <tr><th>Serial Numbers</th><td><strong style="font-family:monospace;font-size:14px;">{{ is_array($warrantySale->serial_numbers) ? implode(', ', $warrantySale->serial_numbers) : $warrantySale->serial_numbers }}</strong></td></tr>
                        @endif
                        @if($warrantySale->stockBatch)
                        <tr><th>Batch</th><td>{{ $warrantySale->stockBatch->batch_no ?: 'Batch #'.$warrantySale->stockBatch->id }} (Unit Cost: ৳{{ number_format($warrantySale->stockBatch->unit_cost, 2) }})</td></tr>
                        @endif
                        @if($warrantySale->purchase)
                        <tr><th>Purchase Invoice</th><td>#{{ $warrantySale->purchase->invoice_no ?? $warrantySale->purchase->id }}</td></tr>
                        @endif
                        @if($warrantySale->soldBy)
                        <tr><th>Sold By</th><td>{{ $warrantySale->soldBy->name ?? 'N/A' }}</td></tr>
                        @endif
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
                                    @if($warrantySale->can_claim)
                                    <button class="btn btn-sm btn-warning ms-2" data-bs-toggle="modal" data-bs-target="#adminClaimModal">
                                        🛡️ Warranty Claim
                                    </button>
                                    @endif
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

            {{-- 🔍 Product SN History --}}
            @if($warrantySale->serial_numbers)
            <div class="card mb-3">
                <div class="card-header"><strong>🔍 Serial Number History</strong></div>
                <div class="card-body">
                    @php
                        $snHistory = \App\Models\WarrantySale::where('product_id', $warrantySale->product_id)
                            ->where('id', '!=', $warrantySale->id)
                            ->where(function($q) use ($warrantySale) {
                                foreach ($warrantySale->serial_numbers as $sn) {
                                    $q->orWhereJsonContains('serial_numbers', $sn);
                                }
                            })
                            ->with(['order', 'claims'])
                            ->latest()
                            ->limit(10)
                            ->get();
                    @endphp
                    @forelse($snHistory as $pastSale)
                        <div class="border-bottom pb-2 mb-2">
                            <strong>Sale #{{ $pastSale->id }}</strong>
                            <span class="badge bg-{{ $pastSale->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($pastSale->status) }}</span>
                            @if($pastSale->serial_numbers)
                                <br><small class="text-muted">SN: {{ is_array($pastSale->serial_numbers) ? implode(', ', $pastSale->serial_numbers) : $pastSale->serial_numbers }}</small>
                            @endif
                            @if($pastSale->claims->isNotEmpty())
                                <br><small class="text-warning">⚠️ Claims: {{ $pastSale->claims->pluck('claim_number')->implode(', ') }}</small>
                            @endif
                            @if($pastSale->order)
                                <br><small>Order: #{{ $pastSale->order->invoice_id ?? $pastSale->order_id }}</small>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No previous records found for these serial numbers.</p>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Admin File Claim Modal --}}
<div class="modal fade" id="adminClaimModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.warranty.claims.file-for-customer') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="warranty_sale_id" value="{{ $warrantySale->id }}">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">🛡️ Warranty Claim for Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Issue Type</label>
                    <select name="issue_type" class="form-select" required>
                        <option value="">— Select —</option>
                        <option value="defective">Defective Product</option>
                        <option value="not_working">Not Working as Expected</option>
                        <option value="damaged">Physical Damage (Covered)</option>
                        <option value="missing_parts">Missing Parts/Accessories</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Issue Description <span class="text-danger">*</span></label>
                    <textarea name="issue_description" class="form-control" rows="4" required
                        placeholder="Describe the issue..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin Note</label>
                    <textarea name="admin_note" class="form-control" rows="2"
                        placeholder="Internal note (not visible to customer)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">📤 Submit Claim</button>
            </div>
        </form>
    </div>
</div>

@endsection
