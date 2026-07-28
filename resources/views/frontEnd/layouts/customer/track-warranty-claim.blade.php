{{--
  Track Warranty Claim Page — Customer
--}}
@php
    $claim = \App\Models\WarrantyClaim::with(['product', 'warrantySale', 'stages', 'notes.user'])
        ->find(request('claim_id'));
    if(!$claim) { echo 'Claim not found.'; return; }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Claim — {{ $claim->claim_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 700px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>🔍 Claim #{{ $claim->claim_number }}</h4>
        <span class="badge bg-{{ $claim->status_enum->badgeClass() }} fs-6">
            {{ $claim->status_enum->label() }}
        </span>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-1"><strong>Product:</strong> {{ $claim->product->name ?? 'N/A' }}
                @if($claim->warrantySale && $claim->warrantySale->serial_numbers)
                    <small class="text-muted ms-2">SN: <code>{{ implode(', ', $claim->warrantySale->serial_numbers) }}</code></small>
                @endif
            </p>
            <p class="mb-1"><strong>Filed:</strong> {{ $claim->created_at->format('d M, Y h:i A') }}</p>
            <p class="mb-0"><strong>Issue Type:</strong> {{ ucfirst(str_replace('_', ' ', $claim->issue_type ?? 'N/A')) }}</p>
        </div>
    </div>

    {{-- 🆕 Pipeline Status Message --}}
    @php
        $statusMsg = match($claim->status) {
            'submitted'           => ['📋 Claim submitted. Our team will review within 24 hours.', 'info'],
            'under_review'        => ['🔍 Under review. Expected time: 1-2 business days.', 'info'],
            'approved'            => ['✅ Claim approved. Please bring/send the product to our service center.', 'success'],
            'awaiting_product'    => ['📦 Waiting for you to send the product. Please bring it to our store.', 'warning'],
            'product_received'    => ['📦 Product received at our service center. Challan #' . ($claim->receive_challan_no ?? 'N/A'), 'warning'],
            'in_service'          => ['🔧 Product is being serviced at our center.', 'info'],
            'sent_to_supplier'    => ['🚚 Product sent to supplier for inspection. Estimated return: 7-14 days.', 'info'],
            'awaiting_supplier_return' => ['⏳ Awaiting return from supplier.', 'info'],
            'supplier_returned'   => ['📥 Product returned from supplier. ' . ($claim->return_type ? 'Status: ' . ucfirst($claim->return_type) : ''), 'success'],
            'serviced'            => ['✅ Servicing complete. Preparing for delivery.', 'success'],
            'ready_for_delivery'  => ['🎉 Product ready for delivery! We will contact you shortly.', 'success'],
            'delivered'           => ['🚀 Product delivered back to you. Thank you for your patience!', 'success'],
            'resolved'            => ['✅ Claim resolved. Thank you!', 'success'],
            'rejected'            => ['❌ Claim rejected: ' . ($claim->rejection_reason ?? 'No reason provided'), 'danger'],
            'cancelled'           => ['✕ Claim cancelled.', 'dark'],
            default               => ['Processing...', 'secondary'],
        };
    @endphp
    <div class="alert alert-{{ $statusMsg[1] }} mb-4">
        {{ $statusMsg[0] }}
    </div>

    {{-- Progress Timeline --}}
    <div class="card mb-4">
        <div class="card-header"><strong>📋 Claim Progress</strong></div>
        <div class="card-body">
            @foreach($claim->stages as $stage)
            <div class="d-flex align-items-start mb-3 {{ $loop->last ? '' : 'border-bottom pb-3' }}">
                <span class="me-3 fs-5">
                    @if($stage->is_complete) ✅
                    @elseif($stage->status === 'pending') 🔄
                    @else ⬜
                    @endif
                </span>
                <div>
                    <strong>{{ \App\Enums\WarrantyStageType::from($stage->stage)->label() }}</strong>
                    <br><small class="text-muted">
                        Started: {{ $stage->started_at?->format('d M, h:i A') ?? 'Pending' }}
                    </small>
                    @if($stage->completed_at)
                        <br><small class="text-success">Completed: {{ $stage->completed_at->format('d M, h:i A') }}</small>
                    @endif
                    @if($stage->notes)
                        <br><small class="text-secondary">{{ $stage->notes }}</small>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Notes --}}
    <div class="card mb-4">
        <div class="card-header"><strong>💬 Updates</strong></div>
        <div class="card-body">
            @forelse($claim->notes as $note)
            <div class="border-bottom pb-2 mb-2">
                <strong>{{ $note->user->name ?? 'System' }}</strong>
                <small class="text-muted float-end">{{ $note->created_at->format('d M, h:i A') }}</small>
                <p class="mb-0">{{ $note->note }}</p>
            </div>
            @empty
            <p class="text-muted mb-0">No updates yet.</p>
            @endforelse
        </div>
    </div>

    @if($claim->status_enum->isActive())
    <div class="text-center">
        <a href="#" class="btn btn-outline-secondary me-2">📞 Contact Support</a>
        @if(in_array($claim->status, ['submitted', 'under_review']))
        <form action="{{ route('customer.warranty.cancel-claim') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="claim_id" value="{{ $claim->id }}">
            <button class="btn btn-outline-danger" onclick="return confirm('Cancel this claim?')">✕ Cancel Claim</button>
        </form>
        @endif
    </div>
    @endif
</div>
</body>
</html>
