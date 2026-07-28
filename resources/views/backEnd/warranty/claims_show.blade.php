@extends('backEnd.layouts.master')
@section('title', 'Claim Details')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>🔧 Claim #{{ $warrantyClaim->claim_number }}</h4>
        <div>
            @if($warrantyClaim->status_enum->isActive())
                {{-- Existing simple actions --}}
                @if($warrantyClaim->status === 'submitted')
                    <form action="{{ route('admin.warranty.claims.action', [$warrantyClaim, 'review']) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-info btn-sm">Start Review</button>
                    </form>
                @endif
                @if($warrantyClaim->status === 'under_review')
                    <form action="{{ route('admin.warranty.claims.action', [$warrantyClaim, 'approve']) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                @endif

                {{-- 🆕 Pipeline action buttons --}}
                @if($warrantyClaim->status === 'approved')
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#receiveModal">
                        📦 Product Received
                    </button>
                @endif
                @if($warrantyClaim->status === 'product_received')
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#sendSupplierModal">
                        🚚 Send to Supplier
                    </button>
                @endif
                @if($warrantyClaim->status === 'sent_to_supplier')
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#supplierReturnModal">
                        📥 Supplier Return Received
                    </button>
                @endif
                @if($warrantyClaim->status === 'supplier_returned')
                    <form action="{{ route('admin.warranty.claims.ready-for-delivery', $warrantyClaim) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-success btn-sm">✅ Ready for Delivery</button>
                    </form>
                @endif
                @if($warrantyClaim->status === 'ready_for_delivery')
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#deliverModal">
                        🎉 Deliver to Customer
                    </button>
                @endif

                {{-- In-service: still allow resolve --}}
                @if($warrantyClaim->status === 'in_service')
                    <form action="{{ route('admin.warranty.claims.action', [$warrantyClaim, 'resolve']) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-success btn-sm">Mark Resolved</button>
                    </form>
                @endif
            @endif

            {{-- Challan history button --}}
            @if($warrantyClaim->challans()->exists())
                <a href="{{ route('admin.warranty.claims.challans', $warrantyClaim) }}" class="btn btn-outline-secondary btn-sm">
                    🧾 Challans ({{ $warrantyClaim->challans()->count() }})
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h6>Issue Description</h6>
                    <p>{{ $warrantyClaim->issue_description }}</p>
                    <small class="text-muted">
                        Type: {{ $warrantyClaim->issue_type ?? 'N/A' }} |
                        Claimed: {{ $warrantyClaim->created_at->format('d M, Y h:i A') }}
                    </small>
                </div>
            </div>

            @if($warrantyClaim->stages->isNotEmpty())
            <div class="card mb-3">
                <div class="card-body">
                    <h6>📋 Progress Timeline</h6>
                    <div class="timeline">
                        @foreach($warrantyClaim->stages as $stage)
                        <div class="d-flex align-items-start mb-3">
                            <span class="me-2">
                                @if($stage->is_complete) ✅
                                @elseif($stage->status === 'pending') 🔄
                                @else ⬜
                                @endif
                            </span>
                            <div>
                                <strong>{{ \App\Enums\WarrantyStageType::from($stage->stage)->label() }}</strong>
                                <br><small class="text-muted">{{ $stage->started_at?->format('d M, h:i A') }}</small>
                                @if($stage->completed_at)
                                    <br><small class="text-success">Completed: {{ $stage->completed_at->format('d M, h:i A') }}</small>
                                @endif
                                @if($stage->notes)
                                    <br><small>{{ $stage->notes }}</small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Notes --}}
            <div class="card mb-3">
                <div class="card-header"><strong>💬 Notes</strong></div>
                <div class="card-body">
                    @foreach($warrantyClaim->notes as $note)
                    <div class="border-bottom pb-2 mb-2">
                        <strong>{{ $note->user->name ?? 'System' }}</strong>
                        <small class="text-muted float-end">{{ $note->created_at->format('d M, h:i A') }}</small>
                        <p class="mb-0">{{ $note->note }}</p>
                    </div>
                    @endforeach

                    <form action="{{ route('admin.warranty.claims.note', $warrantyClaim) }}" method="POST" class="mt-3">
                        @csrf
                        <textarea name="note" class="form-control mb-2" rows="2" placeholder="Add a note..." required></textarea>
                        <button class="btn btn-sm btn-primary">Add Note</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6>Customer</h6>
                    <p class="mb-1">{{ $warrantyClaim->customer->name ?? 'N/A' }}</p>
                    <small>{{ $warrantyClaim->customer->phone ?? '' }}</small>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h6>Product</h6>
                    <p class="mb-0">{{ $warrantyClaim->product->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h6>Warranty Info</h6>
                    @php $ws = $warrantyClaim->warrantySale; @endphp
                    <p class="mb-1">Type: {{ $ws->warranty_type ?? 'N/A' }}</p>
                    <p class="mb-1">Days: {{ $ws->warranty_days ?? 0 }}</p>
                    <p class="mb-1">Remaining: {{ $ws->remaining_days ?? 0 }} days</p>
                    <p class="mb-1">Status: <span class="badge bg-{{ \App\Enums\WarrantySaleStatus::from($ws->status ?? 'active')->badgeClass() }}">{{ ucfirst($ws->status ?? 'N/A') }}</span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    @if($warrantyClaim->status === 'under_review')
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.warranty.claims.reject', $warrantyClaim) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5>Reject Claim</h5></div>
                <div class="modal-body">
                    <textarea name="reason" class="form-control" rows="3" placeholder="Reason for rejection..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger">Reject Claim</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- 🆕 Pipeline Modals --}}

    {{-- Receive Product Modal --}}
    @if($warrantyClaim->status === 'approved')
    <div class="modal fade" id="receiveModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.warranty.claims.receive-product', $warrantyClaim) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5>📦 Receive Product from Customer</h5></div>
                <div class="modal-body">
                    <label class="form-label">Product Condition</label>
                    <select name="condition" class="form-select mb-2" required>
                        <option value="As described">As described</option>
                        <option value="Minor damage">Minor damage</option>
                        <option value="Major damage">Major damage</option>
                        <option value="Missing accessories">Missing accessories</option>
                    </select>
                    <label class="form-label">Accessories Received</label>
                    <input type="text" name="accessories" class="form-control mb-2" placeholder="Charger, box, manual...">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Any observations..."></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Generate Receive Challan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Send to Supplier Modal --}}
    @if($warrantyClaim->status === 'product_received')
    <div class="modal fade" id="sendSupplierModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.warranty.claims.send-to-supplier', $warrantyClaim) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5>🚚 Send to Supplier for Warranty Claim</h5></div>
                <div class="modal-body">
                    <label class="form-label">Select Supplier</label>
                    <select name="supplier_id" class="form-select mb-2" required>
                        <option value="">-- Select --</option>
                        @foreach(\App\Models\Supplier::orderBy('name')->get() as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">Courier</label>
                    <input type="text" name="courier" class="form-control mb-2" placeholder="Courier name">
                    <label class="form-label">Tracking ID</label>
                    <input type="text" name="tracking_id" class="form-control mb-2" placeholder="Tracking number">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Additional info..."></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-warning">Generate Supplier Challan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Supplier Return Modal --}}
    @if($warrantyClaim->status === 'sent_to_supplier')
    <div class="modal fade" id="supplierReturnModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.warranty.claims.supplier-return', $warrantyClaim) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5>📥 Supplier Return Received</h5></div>
                <div class="modal-body">
                    <label class="form-label">Return Type</label>
                    <select name="return_type" id="return_type_select" class="form-select mb-2" required onchange="document.getElementById('replacement_sn_wrap').style.display = this.value === 'replaced' ? '' : 'none'">
                        <option value="repaired">Repaired</option>
                        <option value="replaced">Replaced (new unit)</option>
                        <option value="refunded">Refunded</option>
                    </select>
                    <div id="replacement_sn_wrap" style="display:none;">
                        <label class="form-label">Replacement Serial Number</label>
                        <input type="text" name="replacement_sn" class="form-control mb-2" placeholder="New SN from supplier">
                    </div>
                    <label class="form-label">Supplier's Return Challan No</label>
                    <input type="text" name="supplier_return_challan" class="form-control mb-2" placeholder="Supplier's challan reference">
                    <label class="form-label">Supplier Charge (if any)</label>
                    <input type="number" name="supplier_charge" class="form-control mb-2" placeholder="0.00" step="0.01" min="0">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-info">Generate Return Challan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Deliver to Customer Modal --}}
    @if($warrantyClaim->status === 'ready_for_delivery')
    <div class="modal fade" id="deliverModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.warranty.claims.deliver', $warrantyClaim) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5>🎉 Deliver Product to Customer</h5></div>
                <div class="modal-body">
                    <label class="form-label">Delivery Method</label>
                    <select name="delivery_method" class="form-select mb-2">
                        <option value="Counter Pickup">Counter Pickup</option>
                        <option value="Courier">Courier</option>
                        <option value="Hand Delivery">Hand Delivery</option>
                    </select>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Delivery notes..."></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success">Generate Delivery Challan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
