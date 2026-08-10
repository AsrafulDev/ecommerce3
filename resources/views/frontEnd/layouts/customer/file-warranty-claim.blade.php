@extends('frontEnd.layouts.customer.panel')

@php
    $warrantySale = $warrantySale ?? \App\Models\WarrantySale::with(['product', 'order', 'claims', 'activeClaim'])->find(request('warranty_sale_id'));
    if(!$warrantySale) { echo '<div class="text-center py-20 text-red-500">Warranty not found.</div>'; return; }
    $canClaim = $warrantySale->can_claim;
    $orderCompleted = !$warrantySale->order || in_array($warrantySale->order->order_status, ['completed', 'delivered', 5, 6]);
    $pageTitle = __('File Warranty Claim');
    $headerTitle = __('File Warranty Claim');
    $headerSubtitle = __('Submit a claim for your warranty-covered product');
@endphp

@section('content')

            @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
            @endif

            {{-- Product & Warranty Info Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-indigo-50 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fas fa-box-open text-indigo-600 text-2xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-lg font-semibold text-gray-800">{{ $warrantySale->product->name ?? 'Product' }}</h4>
                        <div class="flex flex-wrap gap-x-6 gap-y-1 mt-2 text-sm text-gray-500">
                            <span><i class="far fa-clock mr-1"></i> {{ $warrantySale->warranty_days }} Days Warranty</span>
                            <span class="{{ $warrantySale->remaining_days <= 7 ? 'text-red-500 font-semibold' : 'text-green-600' }}">
                                <i class="fas fa-hourglass-half mr-1"></i> {{ $warrantySale->remaining_days }} days remaining
                            </span>
                            <span><i class="far fa-calendar-alt mr-1"></i> Expires: {{ $warrantySale->warranty_end_date?->format('d M, Y') ?? 'N/A' }}</span>
                        </div>
                        @if($warrantySale->serial_numbers)
                        <p class="text-xs text-gray-400 mt-1">SN: {{ is_array($warrantySale->serial_numbers) ? implode(', ', $warrantySale->serial_numbers) : ($warrantySale->serial_numbers ?: 'N/A') }}</p>
                        @endif
                        @if($warrantySale->claims->isNotEmpty())
                        <p class="text-sm text-orange-500 mt-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            {{ $warrantySale->claims->whereNotIn('status', ['resolved','rejected','cancelled'])->count() }} active claim(s) on this warranty
                        </p>
                        @endif
                    </div>
                </div>

                @if(!$canClaim)
                <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                    <p class="text-yellow-700 font-semibold"><i class="fas fa-ban mr-2"></i>This warranty cannot be claimed right now.</p>
                    @if($warrantySale->status === 'expired')
                        <p class="text-sm text-yellow-600 mt-1">The warranty has expired.</p>
                    @elseif($warrantySale->status === 'claimed' && $warrantySale->activeClaim)
                        <p class="text-sm text-yellow-600 mt-1">
                            An active claim (#{{ $warrantySale->activeClaim->claim_number }}) is already in progress.
                            <a href="{{ route('customer.warranty.track', $warrantySale->activeClaim->id) }}" class="text-indigo-600 underline">Track it here</a>
                        </p>
                    @elseif(!$orderCompleted)
                        <p class="text-sm text-yellow-600 mt-1">Your order must be completed/delivered before filing a claim.</p>
                    @endif
                </div>
                @endif
            </div>

            @if($canClaim)
            {{-- Claim Form --}}
            <form action="{{ route('customer.warranty.submit-claim') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="warranty_sale_id" value="{{ $warrantySale->id }}">

                {{-- Issue Type --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-tag mr-1 text-indigo-500"></i> {{ __('Issue Type') }}
                    </label>
                    <select name="issue_type" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-700 bg-gray-50">
                        <option value="">— {{ __('Select Issue Type') }} —</option>
                        <option value="defective">🔧 {{ __('Defective Product') }}</option>
                        <option value="not_working">⚠️ {{ __('Not Working as Expected') }}</option>
                        <option value="damaged">💥 {{ __('Physical Damage (Covered)') }}</option>
                        <option value="missing_parts">📦 {{ __('Missing Parts / Accessories') }}</option>
                        <option value="other">📋 {{ __('Other') }}</option>
                    </select>
                </div>

                {{-- Description --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-pen mr-1 text-indigo-500"></i> {{ __('Describe the Issue') }}
                    </label>
                    <textarea name="issue_description" rows="5" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-700 bg-gray-50"
                        placeholder="{{ __('Please describe what is wrong with the product in detail...') }}"></textarea>
                    <p class="text-xs text-gray-400 mt-1">{{ __('Minimum 10 characters') }}</p>
                </div>

                {{-- Attachments --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-paperclip mr-1 text-indigo-500"></i> {{ __('Attachments') }} <span class="text-gray-400 font-normal">({{ __('Optional') }})</span>
                    </label>
                    <input type="file" name="attachments[]" multiple accept="image/*,application/pdf,.jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.avif,.pdf"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-gray-700 bg-gray-50 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm">
                    <p class="text-xs text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i> {{ __('Max 5 files, 10MB each. Images & PDF only.') }}</p>
                </div>

                {{-- Terms --}}
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-4">
                    <h6 class="font-semibold text-amber-800 mb-2"><i class="fas fa-shield-alt mr-1"></i> {{ __('⚠️ Warranty Terms Reminder') }}</h6>
                    <ul class="text-sm text-amber-700 space-y-1 list-disc list-inside">
                        <li>{{ __('Physical damage from misuse is NOT covered') }}</li>
                        <li>{{ __('Water damage is NOT covered') }}</li>
                        <li>{{ __('Normal wear & tear is NOT covered') }}</li>
                        <li>{{ __('Unauthorized repair voids the warranty') }}</li>
                    </ul>
                </div>

                {{-- Confirmation --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" required class="mt-1 w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <span class="text-sm text-gray-600">{{ __('I confirm that the issue is covered under the warranty terms and all information provided is accurate.') }}</span>
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3 mb-8">
                    <a href="{{ route('customer.warranties') }}" class="flex-1 text-center px-6 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">
                        <i class="fas fa-arrow-left mr-2"></i> {{ __('Back') }}
                    </a>
                    <button type="submit" class="flex-1 px-6 py-3.5 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold transition shadow-md">
                        <i class="fas fa-paper-plane mr-2"></i> {{ __('Submit Claim') }}
                    </button>
                </div>
            </form>
            @else
            {{-- Cannot claim — back button --}}
            <div class="text-center mb-8">
                <a href="{{ route('customer.warranties') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold transition shadow-md">
                    <i class="fas fa-arrow-left mr-2"></i> {{ __('Back to My Warranties') }}
                </a>
            </div>
            @endif
        </div>
@endsection
