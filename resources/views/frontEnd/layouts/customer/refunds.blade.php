@php
use Illuminate\Support\Facades\Auth;
$customerId = Auth::guard('customer')->id();
$refunds = $refunds ?? \App\Models\Refund::where('customer_id', $customerId)->latest()->paginate(10);
@endphp

@extends('frontEnd.layouts.customer.panel')

@php
    $pageTitle = __('Refund Request');
    $headerTitle = __('Refund Request');
    $headerSubtitle = __('Your refund requests');
@endphp

@section('content')
<div class="p-4 lg:p-8 max-w-7xl mx-auto">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">🔄 {{ __('Refund Request') }}</h3>
                    @if($refunds->count() > 0)
                        <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-sm font-semibold">{{ $refunds->total() }} {{ __('requests') }}</span>
                    @endif
                </div>
                
                @if($refunds->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left custom-table">
                            <thead>
                                <tr>
                                    <th class="pl-6 py-4">{{ __('Refund ID') }}</th>
                                    <th class="py-4">{{ __('Order Info') }}</th>
                                    <th class="py-4">{{ __('Total Refund') }}</th>
                                    <th class="py-4">{{ __('Status') }}</th>
                                    <th class="py-4">{{ __('Date') }}</th>
                                    <th class="pr-6 py-4 text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($refunds as $refund)
                                    @php
                                        // Status Badge Classes
                                        $statusClass = '';
                                        $statusText = '';
                                        $statusIcon = '';
                                        
                                        if($refund->status == 'pending') {
                                            $statusClass = 'bg-orange-50 text-orange-600';
                                            $statusText = __('Pending');
                                            $statusIcon = 'fas fa-clock';
                                        } elseif($refund->status == 'approved') {
                                            $statusClass = 'bg-blue-50 text-blue-600';
                                            $statusText = __('Approved');
                                            $statusIcon = 'fas fa-check';
                                        } elseif($refund->status == 'rejected') {
                                            $statusClass = 'bg-red-50 text-red-600';
                                            $statusText = __('Rejected');
                                            $statusIcon = 'fas fa-times';
                                        } elseif($refund->status == 'processed') {
                                            $statusClass = 'bg-green-50 text-green-600';
                                            $statusText = __('Processed');
                                            $statusIcon = 'fas fa-check-double';
                                        }
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="pl-6 font-bold text-indigo-600">#{{ $refund->refund_id }}</td>
                                        
                                        <td>
                                            <a href="{{ route('customer.invoice', ['id' => $refund->order->id]) }}" class="text-indigo-600 hover:text-indigo-700 font-bold hover:underline">
                                                #{{ $refund->order->invoice_id ?? $refund->order->id }}
                                            </a>
                                            <div class="text-xs text-gray-400 mt-0.5">{{ __('Invoice ID') }}</div>
                                        </td>

                                        <td class="font-bold text-gray-800">৳{{ number_format($refund->totalRefundAmount(), 2) }}</td>

                                        <td>
                                            <span class="{{ $statusClass }} px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                                <i class="{{ $statusIcon }}"></i>
                                                {{ $statusText }}
                                            </span>
                                        </td>

                                        <td class="text-gray-500">
                                            <div>{{ $refund->created_at->format('d M, Y') }}</div>
                                            <div class="text-xs text-gray-400">{{ $refund->created_at->format('h:i A') }}</div>
                                        </td>

                                        <td class="pr-6 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('customer.refunds.show', $refund->id) }}" class="text-indigo-600 hover:text-indigo-700 p-2 hover:bg-indigo-50 rounded-lg transition" title="{{ __('View Details') }}">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if($refund->status == 'pending')
                                                    <form action="{{ route('customer.refunds.cancel', $refund->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __("Are you sure you want to cancel this refund request?") }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-700 p-2 hover:bg-red-50 rounded-lg transition" title="{{ __('Cancel') }}">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($refunds->hasPages())
                        <div class="p-6 border-t border-gray-100 flex justify-center">
                            {{ $refunds->links() }}
                        </div>
                    @endif
                @else
                    {{-- Empty State --}}
                    <div class="text-center py-16 px-4">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                            <i class="fas fa-file-invoice-dollar text-4xl text-gray-300"></i>
                        </div>
                        <h5 class="text-lg font-bold text-gray-800 mb-2">{{ __('No refund requests') }}</h5>
                        <p class="text-gray-500 mb-6">{{ __("You haven't made any refund requests.") }}</p>
                        <a href="{{ route('customer.orders') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                            <i class="fas fa-box-open"></i>
                            {{ __('View Orders') }}
                        </a>
                    </div>
                @endif
            </div>

        </div>
    
</div>
@endsection
