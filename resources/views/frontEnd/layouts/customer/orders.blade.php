@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

$customer = Auth::guard('customer')->user();
$customerId = $customer->id;

$resolveOrderStatus = function ($orderStatus, $relationStatus = null) {
    $statusValue = is_string($orderStatus) ? strtolower(trim($orderStatus)) : (string) $orderStatus;
    $enum = \App\Enums\OrderStatus::tryFrom($statusValue);
    if ($enum) {
        return ['label' => $enum->label(), 'class' => match ($enum) {
            \App\Enums\OrderStatus::COMPLETED => 'bg-green-50 text-green-600',
            \App\Enums\OrderStatus::CANCELLED, \App\Enums\OrderStatus::CLOSED => 'bg-red-50 text-red-600',
            \App\Enums\OrderStatus::SHIPPED, \App\Enums\OrderStatus::OUT_FOR_DELIVERY => 'bg-orange-50 text-orange-600',
            default => 'bg-gray-50 text-gray-600',
        }];
    }
    $legacyMap = ['1'=>'Pending','2'=>'Confirmed','3'=>'Picking','4'=>'Packing','5'=>'Packed','6'=>'Completed','7'=>'Shipped','8'=>'Out for Delivery','9'=>'Delivered','10'=>'Return Requested','11'=>'Cancelled','12'=>'Return Approved','13'=>'Returned','14'=>'Closed'];
    $label = $legacyMap[$statusValue] ?? 'Pending';
    return ['label' => $label, 'class' => 'bg-gray-50 text-gray-600'];
};

$totalOrderAmount = \App\Models\Order::where('customer_id', $customerId)->sum('amount');
$pendingOrdersCount = \App\Models\Order::where('customer_id', $customerId)
    ->whereNotIn('order_status', ['6', '11'])
    ->count();
@endphp

@extends('frontEnd.layouts.customer.panel')

@php
    $pageTitle = __('My Orders');
    $headerTitle = __('My Orders');
    $headerSubtitle = __('Your order history');
@endphp

@section('content')
<div class="p-4 lg:p-8 max-w-7xl mx-auto">
            
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">{{ __('Total Orders') }}</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $orders->count() }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">{{ __('Active') }}</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $pendingOrdersCount }}</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">{{ __('Total Amount') }}</p>
                            <p class="text-2xl font-bold text-gray-800">৳{{ number_format($totalOrderAmount, 0) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Orders List --}}
            <div class="space-y-4">
                @forelse($orders as $value)
                                @php
                                    // Payment Logic
                                    $payment_record = \App\Models\Payment::where('order_id', $value->id)->orderBy('id', 'desc')->first();
                                    
                                    $gateway_status = $payment_record ? strtolower(trim($payment_record->payment_status)) : '';
                                    $payment_method = $payment_record ? strtolower(trim($payment_record->payment_method)) : '';
                                    
                                    $admin_status   = strtolower(trim($value->payment_status ?? ''));
                                    $order_status   = strtolower(trim($value->status->slug ?? $value->status->name ?? $value->order_status ?? ''));

                                    $grand_total = $value->amount;
                                    $paid_amount = 0;

                                    // Payment calculation logic
                                    if ($payment_record && !in_array($gateway_status, ['failed', 'cancel', 'cancelled', 'rejected'])) {
                                        $paid_amount = $payment_record->amount;
                                    }

                                    $is_cod = in_array($payment_method, ['cod', 'cash', 'cash_on_delivery']);
                                    $is_order_completed = in_array($order_status, ['completed', 'delivered']) || in_array($admin_status, ['completed', 'delivered']);

                                    if ($is_cod && !$is_order_completed) {
                                        if ($paid_amount >= $grand_total) {
                                            $paid_amount = 0;
                                        }
                                    }

                                    if ($is_order_completed) {
                                        $paid_amount = $grand_total;
                                    } 
                                    elseif (($paid_amount == 0 || !$payment_record) && in_array($admin_status, ['paid', 'success', 'approved'])) {
                                        $paid_amount = $grand_total;
                                    }

                                    $due_amount = max(0, $grand_total - $paid_amount);

                                    $is_failed = false;
                                    if ($paid_amount == 0 && in_array($gateway_status, ['failed', 'cancel', 'cancelled'])) {
                                        $is_failed = true;
                                    }

                                    $show_download = ($paid_amount >= $grand_total) || ($paid_amount > 0 && !$is_failed);

                                    $digitalDownloads = \App\Models\DigitalDownload::where('order_id', $value->id)->get();
                                    $hasDigitalProduct = $digitalDownloads->count() > 0;

                                    // Order Status Badge
                                    $statusMeta = $resolveOrderStatus($value->order_status, $value->status);
                                    $statusClass = $statusMeta['class'];
                                    $statusText = __($statusMeta['label']);

                                    // Refund Logic
                                    $canRefund = false;
                                    $hasPendingRefund = method_exists($value, 'hasPendingRefund') ? $value->hasPendingRefund() : false;
                                    
                                    if ($value->order_status != 11 && $paid_amount > 0 && !$hasPendingRefund) {
                                        $canRefund = true;
                                    }
                                    
                                    $existingRefund = \App\Models\Refund::where('order_id', $value->id)
                                        ->whereIn('status', ['pending', 'approved'])
                                        ->first();
                                @endphp

                                {{-- Order Card - Simplified --}}
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200">
                                    <div class="p-4">
                                        {{-- Header Row --}}
                                        <div class="flex items-start justify-between gap-4 mb-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="text-base font-bold text-gray-800">#{{ $value->invoice_id ?? $value->id }}</h4>
                                                    <span class="{{ $statusClass }} px-2 py-0.5 rounded text-xs font-semibold">{{ $statusText }}</span>
                                                </div>
                                                <p class="text-xs text-gray-500">{{ $value->created_at->format('d M, Y - h:i A') }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-bold text-gray-800">৳{{ number_format($grand_total, 2) }}</p>
                                            </div>
                                        </div>

                                        {{-- Product Images --}}
                                        @if($value->orderdetails && $value->orderdetails->count() > 0)
                                            <div class="flex gap-2 mb-4 pb-4 border-b border-gray-100">
                                                @foreach($value->orderdetails->take(5) as $detail)
                                                    @php
                                                        $productImage = null;
                                                        if ($detail->product && $detail->product->image) {
                                                            $productImage = $detail->product->image->image;
                                                        } elseif ($detail->image) {
                                                            $productImage = $detail->image->image;
                                                        }
                                                    @endphp
                                                    <img src="{{ $productImage ? asset($productImage) : asset('public/assets/images/no-image.png') }}" 
                                                         onerror="this.src='{{ asset('public/assets/images/no-image.png') }}'"
                                                         class="w-12 h-12 rounded object-cover border border-gray-200"
                                                         alt="{{ $detail->product_name ?? 'Product' }}">
                                                @endforeach
                                                @if($value->orderdetails->count() > 5)
                                                    <div class="w-12 h-12 rounded bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold">
                                                        +{{ $value->orderdetails->count() - 5 }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Payment & Actions Row --}}
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            {{-- Payment Status --}}
                                            <div class="flex items-center gap-3 text-sm">
                                                <div>
                                                    <span class="text-gray-500">{{ __('Paid:') }} </span>
                                                    <span class="font-semibold {{ $paid_amount > 0 ? 'text-green-600' : 'text-gray-400' }}">৳{{ number_format($paid_amount, 2) }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">{{ __('Due:') }} </span>
                                                    <span class="font-semibold {{ $due_amount > 0 ? 'text-red-600' : 'text-gray-400' }}">৳{{ number_format($due_amount, 2) }}</span>
                                                </div>
                                                <div>
                                                    @if($paid_amount >= $grand_total)
                                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-semibold">{{ __('Paid') }}</span>
                                                    @elseif($is_failed)
                                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-semibold">{{ __('Failed') }}</span>
                                                    @elseif($paid_amount > 0)
                                                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-semibold">{{ __('Partial') }}</span>
                                                    @else
                                                        @if($is_cod)
                                                            <span class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded text-xs font-semibold">{{ __('COD') }}</span>
                                                        @else
                                                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-semibold">{{ __('Unpaid') }}</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Action Buttons --}}
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('customer.invoice',['id'=>$value->id]) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-4 py-2 rounded-lg transition font-semibold flex items-center gap-1">
                                                    <i class="fas fa-eye"></i>
                                                    <span class="hidden sm:inline">{{ __('View') }}</span>
                                                </a>
                                                @if($value->admin_note)
                                                    <a href="{{ route('customer.order_note',['id'=>$value->id]) }}" class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-2 rounded-lg transition" title="Admin Note">
                                                        <i class="fas fa-sticky-note"></i>
                                                    </a>
                                                @endif
                                                @if($hasDigitalProduct && $show_download)
                                                    @foreach($digitalDownloads as $dl)
                                                        <a href="{{ route('digital.download', $dl->token) }}" 
                                                           class="bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-2 rounded-lg transition" target="_blank" title="{{ __('Download') }}">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    @endforeach
                                                @endif
                                                @if($canRefund)
                                                    <a href="{{ route('customer.refunds.create', $value->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-3 py-2 rounded-lg transition" title="{{ __('Request Refund') }}">
                                                        <i class="fas fa-undo"></i>
                                                    </a>
                                                @elseif($existingRefund)
                                                    <a href="{{ route('customer.refunds.show', $existingRefund->id) }}" class="bg-purple-500 hover:bg-purple-600 text-white text-xs px-3 py-2 rounded-lg transition" title="View Refund">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                                        <i class="fas fa-box-open text-4xl text-gray-300"></i>
                                    </div>
                                    <h5 class="text-lg font-bold text-gray-800 mb-2">{{ __('No orders found') }}</h5>
                                    <p class="text-gray-500">{{ __("You haven't placed any orders yet.") }}</p>
                                </div>
                            @endforelse
            </div>

            {{-- Pagination --}}
            @if(method_exists($orders, 'hasPages') && $orders->hasPages())
                <div class="mt-6 flex justify-center">
                    <div class="flex items-center gap-2 flex-wrap justify-center">
                        {{-- Previous Page Link --}}
                        @if ($orders->onFirstPage())
                            <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        @else
                            <a href="{{ $orders->previousPageUrl() }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        @endif

                        {{-- Pagination Elements --}}
                        @php
                            $currentPage = $orders->currentPage();
                            $lastPage = $orders->lastPage();
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($lastPage, $currentPage + 2);
                        @endphp

                        @if($startPage > 1)
                            <a href="{{ $orders->url(1) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">1</a>
                            @if($startPage > 2)
                                <span class="px-2 text-gray-400">...</span>
                            @endif
                        @endif

                        @for($page = $startPage; $page <= $endPage; $page++)
                            @if ($page == $currentPage)
                                <span class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold">{{ $page }}</span>
                            @else
                                <a href="{{ $orders->url($page) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">{{ $page }}</a>
                            @endif
                        @endfor

                        @if($endPage < $lastPage)
                            @if($endPage < $lastPage - 1)
                                <span class="px-2 text-gray-400">...</span>
                            @endif
                            <a href="{{ $orders->url($lastPage) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">{{ $lastPage }}</a>
                        @endif

                        {{-- Next Page Link --}}
                        @if ($orders->hasMorePages())
                            <a href="{{ $orders->nextPageUrl() }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        @else
                            <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg cursor-not-allowed">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    
            </div>
@endsection