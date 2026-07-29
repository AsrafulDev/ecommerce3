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
        <div class="space-y-2">
            @foreach($warranties as $sale)
            <div class="rounded-xl p-4 border
                {{ $sale->status === 'active' ? 'border-green-200 bg-green-50/30' : '' }}
                {{ $sale->status === 'claimed' ? 'border-yellow-200 bg-yellow-50/30' : '' }}
                {{ $sale->status === 'expired' ? 'border-gray-200 bg-gray-50/50' : '' }}
            ">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm">
                                @if($sale->status === 'active') 🟢
                                @elseif($sale->status === 'claimed') 🟡
                                @elseif($sale->status === 'expired') 🔴
                                @else ⚪
                                @endif
                            </span>
                            <strong class="text-gray-800">{{ $sale->product->name ?? 'Product' }}</strong>
                            @if($sale->serial_numbers)
                                <small class="text-gray-400 ml-1">SN: <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs text-gray-600">{{ is_array($sale->serial_numbers) ? implode(', ', $sale->serial_numbers) : ($sale->serial_numbers ?: 'N/A') }}</code></small>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 space-x-3">
                            <span>{{ $sale->warranty_type === 'none' ? 'No Warranty' : $sale->warranty_days . ' Days Warranty' }}</span>
                            <span>| Claims: {{ $sale->claims->count() }}</span>
                            @if($sale->warranty_end_date && $sale->warranty_days > 0)
                                <span>| Expires: {{ $sale->warranty_end_date->format('d M, Y') }}</span>
                                <span class="{{ $sale->remaining_days <= 7 ? 'text-red-500 font-semibold' : 'text-green-600' }}">
                                    {{ $sale->remaining_days }} days left
                                </span>
                            @endif
                        </p>
                        @if($sale->warranty_days > 0)
                        <div class="mt-1 w-full max-w-[150px] bg-gray-200 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $sale->warranty_progress_percent > 80 ? 'bg-red-500' : 'bg-green-500' }}"
                                style="width: {{ $sale->warranty_progress_percent }}%"></div>
                        </div>
                        @endif
                    </div>
                    <div class="shrink-0">
                        @if($sale->status === 'active')
                            @php $orderCompleted = $sale->order && in_array($sale->order->order_status, ['completed', 'delivered', 5, 6]); @endphp
                            @php $nowarenty = $sale->warranty_type === 'none' || $sale->warranty_days <= 0; @endphp
                            @if($orderCompleted && (!$sale->activeClaim || $sale->activeClaim->status === 'rejected') && !$nowarenty)
                                <a href="{{ route('customer.warranty.claim', $sale->id) }}"
                                   class="inline-block px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                                    {{ __('Claim') }}
                                </a>
                            @else
                                <span class="text-xs text-gray-400" title="Claim available after order is completed">
                                    ⏳ {{ __('Pending Order') }}
                                </span>
                            @endif
                        @elseif($sale->status === 'claimed' && $sale->activeClaim)
                            <a href="{{ route('customer.warranty.track', $sale->activeClaim->id) }}"
                               class="inline-block px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                                {{ __('Track Claim') }} →
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
