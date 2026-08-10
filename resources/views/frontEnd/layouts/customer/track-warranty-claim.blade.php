@extends('frontEnd.layouts.customer.panel')

@php
    $claim = $claim ?? \App\Models\WarrantyClaim::with(['product', 'warrantySale', 'stages', 'notes.user'])->find(request('claim_id'));
    if(!$claim) { echo '<div class="text-center py-20 text-red-500">Claim not found.</div>'; return; }
    $pageTitle = __('Track Claim') . ' #' . $claim->claim_number;
    $headerTitle = __('Track Claim') . ' #' . $claim->claim_number;
    $headerSubtitle = __('Filed') . ': ' . $claim->created_at->format('d M, Y h:i A');
@endphp

@section('content')
<div class="max-w-3xl mx-auto">

            {{-- Product Info Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 bg-indigo-50 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fas fa-box text-indigo-600 text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-800">{{ $claim->product->name ?? 'N/A' }}</h4>
                        @if($claim->warrantySale && $claim->warrantySale->serial_numbers)
                            <p class="text-sm text-gray-400 mt-1">SN: <code class="bg-gray-100 px-2 py-0.5 rounded text-gray-600">{{ is_array($claim->warrantySale->serial_numbers ?? []) ? implode(', ', $claim->warrantySale->serial_numbers) : ($claim->warrantySale->serial_numbers ?? 'N/A') }}</code></p>
                        @endif
                        <p class="text-sm text-gray-500 mt-1">
                            {{ __('Issue Type') }}: <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $claim->issue_type ?? 'N/A')) }}</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Status Message --}}
            @php
                $statusMsg = match($claim->status) {
                    'submitted'           => ['📋 Claim submitted. Our team will review within 24 hours.', 'blue'],
                    'under_review'        => ['🔍 Under review. Expected time: 1-2 business days.', 'blue'],
                    'approved'            => ['✅ Claim approved. Please bring/send the product to our service center.', 'green'],
                    'awaiting_product'    => ['📦 Waiting for you to send the product. Please bring it to our store.', 'yellow'],
                    'product_received'    => ['📦 Product received at our service center. Challan #' . ($claim->receive_challan_no ?? 'N/A'), 'yellow'],
                    'in_service'          => ['🔧 Product is being serviced at our center.', 'blue'],
                    'sent_to_supplier'    => ['🚚 Product sent to supplier for inspection. Estimated return: 7-14 days.', 'blue'],
                    'awaiting_supplier_return' => ['⏳ Awaiting return from supplier.', 'yellow'],
                    'supplier_returned'   => ['📥 Product returned from supplier. ' . ($claim->return_type ? 'Status: ' . ucfirst($claim->return_type) : ''), 'green'],
                    'serviced'            => ['✅ Servicing complete. Preparing for delivery.', 'green'],
                    'ready_for_delivery'  => ['🎉 Product ready for delivery! We will contact you shortly.', 'green'],
                    'delivered'           => ['🚀 Product delivered back to you. Thank you for your patience!', 'green'],
                    'resolved'            => ['✅ Claim resolved. Thank you!', 'green'],
                    'rejected'            => ['❌ Claim rejected: ' . ($claim->rejection_reason ?? 'No reason provided'), 'red'],
                    'cancelled'           => ['✕ Claim cancelled.', 'gray'],
                    default               => ['Processing...', 'gray'],
                };
            @endphp
            <div class="
                {{ $statusMsg[1] === 'green' ? 'bg-green-50 border-green-200 text-green-700' : '' }}
                {{ $statusMsg[1] === 'yellow' ? 'bg-yellow-50 border-yellow-200 text-yellow-700' : '' }}
                {{ $statusMsg[1] === 'blue' ? 'bg-blue-50 border-blue-200 text-blue-700' : '' }}
                {{ $statusMsg[1] === 'red' ? 'bg-red-50 border-red-200 text-red-700' : '' }}
                {{ $statusMsg[1] === 'gray' ? 'bg-gray-50 border-gray-200 text-gray-700' : '' }}
                border rounded-xl p-4 mb-4 font-medium
            ">
                {{ $statusMsg[0] }}
            </div>

            {{-- Progress Timeline --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
                <h5 class="font-semibold text-gray-800 mb-4"><i class="fas fa-tasks mr-2 text-indigo-500"></i> {{ __('Claim Progress') }}</h5>
                <div class="space-y-3">
                    @foreach($claim->stages as $stage)
                    <div class="flex items-start gap-3 {{ $loop->last ? '' : 'pb-3 border-b border-gray-50' }}">
                        <span class="text-lg shrink-0">
                            @if($stage->is_complete) ✅
                            @elseif($stage->status === 'pending') 🔄
                            @else ⬜
                            @endif
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-700">{{ \App\Enums\WarrantyStageType::from($stage->stage)->label() }}</p>
                            <p class="text-xs text-gray-400">
                                {{ __('Started') }}: {{ $stage->started_at?->format('d M, h:i A') ?? __('Pending') }}
                            </p>
                            @if($stage->completed_at)
                                <p class="text-xs text-green-500">{{ __('Completed') }}: {{ $stage->completed_at->format('d M, h:i A') }}</p>
                            @endif
                            @if($stage->notes)
                                <p class="text-xs text-gray-500 mt-1">{{ $stage->notes }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Updates / Notes --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
                <h5 class="font-semibold text-gray-800 mb-4"><i class="fas fa-comment-dots mr-2 text-indigo-500"></i> {{ __('Updates') }}</h5>
                @forelse($claim->notes as $note)
                @php
                    // If a note references a product image (uploaded / media gallery),
                    // extract the path so we can show an image preview.
                    $noteImgUrl = null;
                    $noteText = $note->note ?? '';
                    if (str_starts_with($noteText, 'Product image uploaded: ')) {
                        $noteImgUrl = trim(substr($noteText, strlen('Product image uploaded: ')));
                    } elseif (str_starts_with($noteText, 'Product image (Media Gallery): ')) {
                        $noteImgUrl = trim(substr($noteText, strlen('Product image (Media Gallery): ')));
                    }
                    if ($noteImgUrl) {
                        $noteImgUrl = str_starts_with($noteImgUrl, 'http') ? $noteImgUrl : asset($noteImgUrl);
                    }
                @endphp
                <div class="{{ $loop->last ? '' : 'pb-3 mb-3 border-b border-gray-50' }}">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-semibold text-gray-700 text-sm">{{ $note->user->name ?? 'System' }}</span>
                        <span class="text-xs text-gray-400">{{ $note->created_at->format('d M, h:i A') }}</span>
                    </div>
                    @if($noteImgUrl)
                        <div class="flex items-start gap-3">
                            <a href="{{ $noteImgUrl }}" target="_blank" class="shrink-0">
                                <img src="{{ $noteImgUrl }}" alt="Product image" class="w-24 h-24 object-cover rounded-lg border border-gray-200">
                            </a>
                            <div>
                                <p class="text-sm text-gray-600">{{ __('Product image attached.') }}</p>
                                <a href="{{ $noteImgUrl }}" target="_blank" class="text-xs text-indigo-600 hover:underline">
                                    <i class="fas fa-external-link-alt mr-1"></i>{{ __('View full image') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-600">{{ $noteText }}</p>
                    @endif
                </div>
                @empty
                <p class="text-gray-400 text-sm">{{ __('No updates yet.') }}</p>
                @endforelse
            </div>

            {{-- 📎 Attachments (customer-submitted documents only) --}}
            @php
                // Normalize claim-level attachments (customer-submitted)
                $claimAttachments = [];
                foreach (($claim->attachments ?? []) as $att) {
                    if (is_array($att)) {
                        $att = $att['url'] ?? $att['path'] ?? $att['file'] ?? $att['name'] ?? $att['src']
                            ?? (isset($att[0]) && is_string($att[0]) ? $att[0] : null);
                    } elseif (is_object($att)) {
                        $att = $att->url ?? $att->path ?? $att->file ?? $att->name ?? $att->src ?? null;
                    }
                    if (is_string($att) && $att !== '') $claimAttachments[] = $att;
                }
                // NOTE: admin/service-center per-step attachments are intentionally NOT shown to the customer.
            @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
                <h5 class="font-semibold text-gray-800 mb-4"><i class="fas fa-paperclip mr-2 text-indigo-500"></i> {{ __('Your Submitted Documents') }}</h5>
                @if(!empty($claimAttachments))
                    <p class="text-xs text-gray-400 mb-3">{{ __('Files you attached when filing this claim.') }}</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($claimAttachments as $att)
                            @php
                                $attUrl = str_starts_with($att, 'http') ? $att : asset($att);
                                $attExt = strtolower(pathinfo($att, PATHINFO_EXTENSION));
                                $isImg = in_array($attExt, ['jpg','jpeg','png','gif','webp','bmp','svg','avif']);
                            @endphp
                            <a href="{{ $attUrl }}" target="_blank" title="{{ basename($att) }}" class="block">
                                @if($isImg)
                                    <img src="{{ $attUrl }}" alt="attachment" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                                @else
                                    <span class="w-20 h-20 flex flex-col items-center justify-center text-red-500 rounded-lg border border-dashed border-red-300 bg-red-50">
                                        <i class="fas fa-file-pdf text-2xl"></i>
                                        <span class="text-[9px] font-bold">PDF</span>
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400"><i class="fas fa-info-circle mr-1"></i> {{ __('No documents were attached to this claim.') }}</p>
                @endif
            </div>

            {{-- 🆕 Challans / Documents (customer-visible only: Product Receive + Customer Delivery) --}}
            @php
                $claimChallans = $claim->challans()
                    ->whereIn('challan_type', ['receive', 'delivery'])
                    ->latest()
                    ->get();
            @endphp
            @if($claimChallans->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
                <h5 class="font-semibold text-gray-800 mb-4"><i class="fas fa-file-alt mr-2 text-indigo-500"></i> {{ __('Challans / Documents') }}</h5>
                <div class="space-y-2">
                    @foreach($claimChallans as $clChallan)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                                <i class="fas fa-file-alt text-indigo-600 text-sm"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-700 text-sm">{{ $clChallan->challan_no }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $clChallan->challan_type_label }} · {{ $clChallan->created_at->format('d M, Y') }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('customer.warranty.challan', $clChallan) }}" target="_blank"
                           class="shrink-0 flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Actions --}}
            @if($claim->status_enum->isActive())
            <div class="flex flex-wrap gap-3 mb-8">
                <a href="{{ route('customer.warranties') }}" class="flex-1 text-center px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i> {{ __('Back to Warranties') }}
                </a>
                @if(in_array($claim->status, ['submitted', 'under_review']))
                <form action="{{ route('customer.warranty.cancel-claim') }}" method="POST" class="flex-1" onsubmit="return confirm('{{ __('Cancel this claim?') }}')">
                    @csrf
                    <input type="hidden" name="claim_id" value="{{ $claim->id }}">
                    <button type="submit" class="w-full px-4 py-3 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl font-semibold transition">
                        <i class="fas fa-times mr-2"></i> {{ __('Cancel Claim') }}
                    </button>
                </form>
                @endif
            </div>
            @else
            <div class="text-center mb-8">
                <a href="{{ route('customer.warranties') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold transition shadow-md">
                    <i class="fas fa-arrow-left mr-2"></i> {{ __('Back to My Warranties') }}
                </a>
            </div>
            @endif
        </div>
</div>
@endsection
