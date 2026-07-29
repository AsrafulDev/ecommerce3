@php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;

$customer = Auth::guard('customer')->user();
$customerId = $customer->id;
$totalOrders = Order::where('customer_id', $customerId)->count();
$pendingOrders = Order::where('customer_id', $customerId)->whereNotIn('order_status', ['6', '11'])->count();
$completedOrders = Order::where('customer_id', $customerId)->where('order_status', '6')->count();
$recentOrders = Order::where('customer_id', $customerId)->with(['status', 'payment', 'orderdetails.product'])->latest()->limit(5)->get();
$recommendedProducts = Product::where('status', 1)->where('approval_status', 'approved')->where('stock', '>', 0)->with('image')->inRandomOrder()->limit(4)->get();
$totalOrderAmount = \App\Models\Order::where('customer_id', $customerId)->sum('amount');
@endphp

@extends('frontEnd.layouts.customer.panel')

@php
    $pageTitle = __('Customer Panel');
    $headerTitle = __('Welcome, :name!', ['name' => $customer->name]);
    $headerSubtitle = __('Your shopping summary');
@endphp

@section('content')

<div class="p-3 sm:p-4 lg:p-8 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-center group hover:shadow-md transition">
                    <div>
                        <p class="text-gray-400 text-xs font-medium uppercase tracking-wider">{{ __('Total Orders') }}</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalOrders }} {{ __('items') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-center group hover:shadow-md transition">
                    <div>
                        <p class="text-gray-400 text-xs font-medium uppercase tracking-wider">{{ __('Active Orders') }}</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $pendingOrders }} {{ __('items') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition">
                        <i class="fas fa-truck-moving"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-center group hover:shadow-md transition">
                    <div>
                        <p class="text-gray-400 text-xs font-medium uppercase tracking-wider">{{ __('Completed Orders') }}</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $completedOrders }} {{ __('items') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-center group hover:shadow-md transition">
                    <div>
                        <p class="text-gray-400 text-xs font-medium uppercase tracking-wider">{{ __('Total Amount') }}</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">৳{{ number_format($totalOrderAmount, 0) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">📦 {{ __('Recent Orders') }}</h3>
                    <a href="{{ route('customer.orders') }}" class="text-sm text-indigo-600 font-semibold hover:underline">{{ __('View All') }}</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left custom-table">
                        <thead>
                            <tr>
                                <th class="pl-6 py-4">{{ __('Order ID') }}</th>
                                <th class="py-4">{{ __('Date') }}</th>
                                <th class="py-4">{{ __('Product Name') }}</th>
                                <th class="py-4">{{ __('Total Amount') }}</th>
                                <th class="py-4">{{ __('Payment') }}</th>
                                <th class="py-4">{{ __('Status') }}</th>
                                <th class="pr-6 py-4 text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                @php
                                    $firstProduct = $order->orderdetails->first();
                                    $productName = $firstProduct && $firstProduct->product ? Str::limit($firstProduct->product->name, 30) : 'N/A';
                                    
                                    $payment = $order->payment;
                                    $paymentStatus = $payment ? strtolower($payment->payment_status) : 'pending';
                                    $isPaid = $paymentStatus === 'paid' || $paymentStatus === 'success';
                                    
                                    $statusClass = '';
                                    $statusText = '';
                                    
                                    if($order->order_status == '6') {
                                        $statusClass = 'bg-green-50 text-green-600';
                                        $statusText = __('Delivered');
                                    } elseif($order->order_status == '11') {
                                        $statusClass = 'bg-red-50 text-red-600';
                                        $statusText = __('Cancelled');
                                    } elseif(in_array($order->order_status, ['3', '4', '5'])) {
                                        $statusClass = 'bg-orange-50 text-orange-600';
                                        $statusText = __('Shipped');
                                    } else {
                                        $statusClass = 'bg-blue-50 text-blue-600';
                                        $statusText = __('Processing');
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="pl-6 font-bold text-indigo-600">#{{ $order->invoice_id ?? $order->id }}</td>
                                    <td class="text-gray-500">{{ $order->created_at->format('d M, Y') }}</td>
                                    <td class="font-medium text-gray-700">{{ $productName }}</td>
                                    <td class="font-bold text-gray-800">৳{{ number_format($order->amount, 0) }}</td>
                                    <td>
                                        @if($isPaid)
                                            <span class="bg-green-50 text-green-600 px-2.5 py-1 rounded text-xs font-bold">{{ __('Paid') }}</span>
                                        @else
                                            <span class="bg-red-50 text-red-600 px-2.5 py-1 rounded text-xs font-bold">{{ __('Unpaid') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ $statusClass }} px-2.5 py-1 rounded text-xs font-bold">{{ $statusText }}</span>
                                    </td>
                                    <td class="pr-6 text-right">
                                        <a href="{{ route('customer.invoice', ['id' => $order->id]) }}" class="text-gray-400 hover:text-indigo-600"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-500">{{ __('No orders found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($recommendedProducts->count() > 0)
            <div>
                <div class="flex justify-between items-center mb-5 px-2 sm:px-0">
                    <h3 class="text-lg font-bold text-gray-800">🔥 {{ __('Best For You') }}</h3>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                    @foreach($recommendedProducts as $product)
                        @php
                            $discount = 0;
                            if($product->old_price && $product->new_price && $product->old_price > $product->new_price) {
                                $discount = round((($product->old_price - $product->new_price) / $product->old_price) * 100);
                            }
                        @endphp
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-lg transition-all duration-300 product-card-hover">
                            <div class="product-image-container relative">
                                <a href="{{ route('product', $product->slug ?? $product->id) }}" class="block w-full h-full">
                                    <img src="{{ asset($product->image->image ?? 'public/assets/images/no-image.png') }}" onerror="this.src='{{ asset('public/assets/images/no-image.png') }}'" class="group-hover:scale-105 transition duration-500" alt="{{ $product->name }}">
                                </a>
                                @if($discount > 0)
                                    <span class="absolute top-1.5 left-1.5 sm:top-2 sm:left-2 bg-indigo-600 text-white text-[9px] sm:text-xs px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full shadow-md font-bold z-10">{{ $discount }}% OFF</span>
                                @elseif($product->feature_product)
                                    <span class="absolute top-1.5 left-1.5 sm:top-2 sm:left-2 bg-green-500 text-white text-[9px] sm:text-xs px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full shadow-md font-bold z-10">New</span>
                                @endif
                                @if($product->stock <= 0)
                                    <span class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 bg-red-500 text-white text-[9px] sm:text-xs px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full shadow-md font-bold z-10">{{ __('Out of Stock') }}</span>
                                @endif
                            </div>
                            <div class="p-3 sm:p-4">
                                <h4 class="font-bold text-gray-800 text-xs sm:text-sm mb-2 line-clamp-2 min-h-[2rem] sm:min-h-[2.5rem]">
                                    <a href="{{ route('product', $product->slug ?? $product->id) }}" class="hover:text-indigo-600 transition">{{ $product->name }}</a>
                                </h4>
                                
                                <div class="flex items-center gap-1 sm:gap-2 mb-2 sm:mb-3 flex-wrap">
                                    @if($product->old_price && $product->old_price > $product->new_price)
                                        <span class="text-gray-400 line-through text-[10px] sm:text-xs">৳{{ number_format($product->old_price, 0) }}</span>
                                    @endif
                                    <span class="text-indigo-600 font-bold text-base sm:text-lg">৳{{ number_format($product->new_price ?? 0, 0) }}</span>
                                </div>
                                
                                @if($product->stock > 0)
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <a href="{{ route('product', $product->slug ?? $product->id) }}" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-semibold py-2 sm:py-2.5 px-3 sm:px-4 rounded-lg text-center transition duration-200 flex items-center justify-center gap-1 sm:gap-2 shadow-sm hover:shadow-md">
                                        <i class="fas fa-shopping-cart text-[10px] sm:text-xs"></i>
                                        <span class="whitespace-nowrap">{{ __('Order Now') }}</span>
                                    </a>
                                    <button onclick="addToCart({{ $product->id }})" class="w-full sm:w-auto bg-gray-100 hover:bg-indigo-600 hover:text-white text-gray-600 sm:w-11 h-9 sm:h-11 rounded-lg flex items-center justify-center transition duration-200 border border-gray-200 hover:border-indigo-600" title="{{ __('Add to Cart') }}">
                                        <i class="fas fa-cart-plus text-xs sm:text-sm"></i>
                                    </button>
                                </div>
                                @else
                                <div class="w-full bg-gray-100 text-gray-500 text-xs sm:text-sm font-semibold py-2 sm:py-2.5 px-3 sm:px-4 rounded-lg text-center">
                                    <i class="fas fa-ban mr-1 sm:mr-2"></i>{{ __('Out of Stock') }}
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    
            </div>
@endsection