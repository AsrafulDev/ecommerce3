@extends('frontEnd.layouts.customer.panel')

@php
    $pageTitle = __('Request Refund');
    $headerTitle = __('Request Refund');
    $headerSubtitle = __('Submit a refund request for your order');
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Order Info Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-indigo-600">
            <h6 class="text-white font-semibold mb-0"><i class="fas fa-info-circle mr-2"></i>{{ __('Order Information') }}</h6>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="space-y-2">
                    <p><span class="text-gray-500">{{ __('Order Invoice') }}:</span> <strong class="text-gray-800">#{{ $order->invoice_id }}</strong></p>
                    <p><span class="text-gray-500">{{ __('Order Date') }}:</span> <strong class="text-gray-800">{{ $order->created_at->format('d M, Y h:i A') }}</strong></p>
                    <p><span class="text-gray-500">{{ __('Order Status') }}:</span> 
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs font-semibold">{{ $order->status->name ?? ucfirst($order->order_status) }}</span>
                    </p>
                </div>
                <div class="space-y-2">
                    <p><span class="text-gray-500">{{ __('Total Amount') }}:</span> <strong class="text-gray-800">৳{{ number_format($order->amount, 2) }}</strong></p>
                    <p><span class="text-gray-500">{{ __('Shipping Charge') }}:</span> <strong class="text-gray-800">৳{{ number_format($order->shipping_charge, 2) }}</strong></p>
                    <p><span class="text-gray-500">{{ __('Grand Total') }}:</span> <strong class="text-indigo-600 text-lg">৳{{ number_format($order->amount, 2) }}</strong></p>
                </div>
            </div>

            <div>
                <h6 class="font-semibold text-gray-800 mb-2">{{ __('Order Items') }}:</h6>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2">{{ __('Product') }}</th>
                                <th class="px-4 py-2 text-center">{{ __('Qty') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('Price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderdetails as $item)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $item->product_name }}</td>
                                    <td class="px-4 py-2 text-center">{{ $item->qty }}</td>
                                    <td class="px-4 py-2 text-right">৳{{ number_format($item->sale_price * $item->qty, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Refund Form --}}
    <form action="{{ route('customer.refunds.store') }}" method="POST">
        @csrf
        <input type="hidden" name="order_id" value="{{ $order->id }}">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-amber-500">
                <h6 class="text-white font-semibold mb-0"><i class="fas fa-undo mr-2"></i>{{ __('Refund Details') }}</h6>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="amount" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Refund Amount') }} <span class="text-red-500">*</span></label>
                        <input type="number" id="amount" name="amount" value="{{ old('amount', $order->amount - $order->shipping_charge) }}" min="1" max="{{ $order->amount - $order->shipping_charge }}" step="0.01" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('amount') border-red-500 @enderror">
                        <p class="text-xs text-gray-400 mt-1">{{ __('Maximum') }}: ৳{{ number_format($order->amount - $order->shipping_charge, 2) }}</p>
                        @error('amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="shipping_charge" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Shipping Charge Refund') }}</label>
                        <input type="number" id="shipping_charge" name="shipping_charge" value="{{ old('shipping_charge', $order->shipping_charge) }}" min="0" max="{{ $order->shipping_charge }}" step="0.01"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('shipping_charge') border-red-500 @enderror">
                        <p class="text-xs text-gray-400 mt-1">{{ __('Maximum') }}: ৳{{ number_format($order->shipping_charge, 2) }}</p>
                        @error('shipping_charge')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Reason for Refund') }} <span class="text-red-500">*</span></label>
                    <textarea id="reason" name="reason" rows="4" required placeholder="{{ __('Please explain why you want a refund...') }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('reason') border-red-500 @enderror">{{ old('reason') }}</textarea>
                    @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="refund_method" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Refund Method') }} <span class="text-red-500">*</span></label>
                    <select id="refund_method" name="refund_method" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('refund_method') border-red-500 @enderror">
                        <option value="">{{ __('Select Method') }}</option>
                        <option value="original_payment" {{ old('refund_method') == 'original_payment' ? 'selected' : '' }}>{{ __('Original Payment Method') }}</option>
                        <option value="bkash" {{ old('refund_method') == 'bkash' ? 'selected' : '' }}>bKash</option>
                        <option value="nagad" {{ old('refund_method') == 'nagad' ? 'selected' : '' }}>Nagad</option>
                        <option value="bank" {{ old('refund_method') == 'bank' ? 'selected' : '' }}>{{ __('Bank Transfer') }}</option>
                        <option value="manual" {{ old('refund_method') == 'manual' ? 'selected' : '' }}>{{ __('Manual/Cash') }}</option>
                    </select>
                    @error('refund_method')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="refund_account" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Account Number/Phone') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="refund_account" name="refund_account" value="{{ old('refund_account') }}" required placeholder="{{ __('Enter bKash/Nagad number or Bank account') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('refund_account') border-red-500 @enderror">
                        @error('refund_account')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="refund_account_name" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Account Holder Name') }}</label>
                        <input type="text" id="refund_account_name" name="refund_account_name" value="{{ old('refund_account_name') }}" placeholder="{{ __('Enter account holder name (if applicable)') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('refund_account_name') border-red-500 @enderror">
                        @error('refund_account_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                    <div>
                        <strong class="text-blue-800">{{ __('Note:') }}</strong>
                        <p class="text-blue-700 text-sm">{{ __('Your refund request will be reviewed by our admin team. You will be notified once the refund is processed.') }}</p>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition shadow-sm flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i> {{ __('Submit Refund Request') }}
                    </button>
                    <a href="{{ route('customer.orders') }}" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-6 py-3 rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fas fa-arrow-left"></i> {{ __('Back to Orders') }}
                    </a>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection
