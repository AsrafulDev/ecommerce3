{{--
  My Warranties Section — Customer Account Page
  Shows active/expired warranties and claim statuses
--}}
@php
    $customer = Auth::guard('customer')->user();
    $warranties = \App\Models\WarrantySale::where('customer_id', $customer->id)
        ->with(['product:id,name,slug', 'activeClaim', 'claims', 'order'])
        ->latest()
        ->limit(10)
        ->get();
    $activeCount = $warranties->where('status', 'active')->count();
    $expiredCount = $warranties->where('status', 'expired')->count();
    $claimedCount = $warranties->where('status', 'claimed')->count();
@endphp

<div class="warranty-section mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold">🛡️ My Warranties</h5>
        <small class="text-muted">
            Active: {{ $activeCount }} | Expired: {{ $expiredCount }} | Claims: {{ $claimedCount }}
        </small>
    </div>

    @if($warranties->isEmpty())
        <div class="text-center py-4 text-muted">
            <i class="fas fa-shield-alt fa-3x mb-3 d-block"></i>
            <p>No warranty purchases yet. Warranties will appear here after your orders are delivered.</p>
        </div>
    @else
        <div class="warranty-list">
            @foreach($warranties as $sale)
            <div class="card mb-2 border-{{ $sale->status === 'active' ? 'success' : ($sale->status === 'claimed' ? 'warning' : 'secondary') }}">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="me-2">
                                @if($sale->status === 'active') 🟢
                                @elseif($sale->status === 'claimed') 🟡
                                @elseif($sale->status === 'expired') 🔴
                                @else ⚪
                                @endif
                            </span>
                            <strong>{{ $sale->product->name ?? 'Product' }}</strong>
                            @if($sale->serial_numbers)
                                <small class="text-muted ms-2">SN: <code>{{ implode(', ', $sale->serial_numbers) }}</code></small>
                            @endif
                            <br>
                            <small class="text-muted">
                                {{ $sale->warranty_type === 'none' ? 'No Warranty' : $sale->warranty_days . ' Days Warranty' }}
                                | Claims: {{ $sale->claims->count() }}
                                @if($sale->warranty_end_date && $sale->warranty_days > 0)
                                    | Expires: {{ $sale->warranty_end_date->format('d M, Y') }}
                                    | <span class="{{ $sale->remaining_days <= 7 ? 'text-danger' : 'text-success' }}">
                                        {{ $sale->remaining_days }} days left
                                    </span>
                                @endif
                            </small>
                            @if($sale->warranty_days > 0)
                            <div class="progress mt-1" style="height: 4px; width: 150px;">
                                <div class="progress-bar bg-{{ $sale->warranty_progress_percent > 80 ? 'danger' : 'success' }}"
                                    style="width: {{ $sale->warranty_progress_percent }}%"></div>
                            </div>
                            @endif
                        </div>
                        <div>
                            @if($sale->status === 'active')
                                @php $orderCompleted = $sale->order && in_array($sale->order->order_status, ['completed', 'delivered', 5, 6]); @endphp
                                @if($orderCompleted)
                                    <a href="{{ route('customer.warranty.claim', $sale->id) }}"
                                       class="btn btn-sm btn-outline-danger">File Claim</a>
                                @else
                                    <span class="text-muted small" title="Claim available after order is completed">
                                        ⏳ Pending Order
                                    </span>
                                @endif
                            @elseif($sale->status === 'claimed' && $sale->activeClaim)
                                <a href="{{ route('customer.warranty.track', $sale->activeClaim->id) }}"
                                   class="btn btn-sm btn-outline-warning">Track Claim →</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
