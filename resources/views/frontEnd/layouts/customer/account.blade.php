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
                <div class="category-product main_product_inner pt-2">
                    @foreach($recommendedProducts as $product)
                        @include('frontEnd.layouts.sections.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    
            </div>
@endsection