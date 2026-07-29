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
                    <form action="{{ route('admin.warranty.claims.action', [$warrantyClaim, 'await-product']) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-info btn-sm">📦 Awaiting Product</button>
                    </form>
                @endif
                @if($warrantyClaim->status === 'awaiting_product')
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

            {{-- 📄 Direct print/download for latest challan — 1 click from claim page --}}
            @php $latestChallan = $warrantyClaim->challans()->latest()->first(); @endphp
            @if($latestChallan)
                <a href="{{ route('admin.warranty.challans.print', $latestChallan) }}?autoprint=1" target="_blank" class="btn btn-primary btn-sm">
                    🖨 Print {{ $latestChallan->challan_type_label }}
                </a>
                <a href="{{ route('admin.warranty.challans.pdf', $latestChallan) }}" class="btn btn-danger btn-sm">
                    📥 PDF
                </a>
                @if($warrantyClaim->challans()->count() > 1)
                <a href="{{ route('admin.warranty.claims.challans', $warrantyClaim) }}" class="btn btn-outline-secondary btn-sm">
                    🧾 All ({{ $warrantyClaim->challans()->count() }})
                </a>
                @endif
            @endif

            {{-- 🆕 Update SN button (always available for active claims) --}}
            @if($warrantyClaim->status_enum->isActive() && !in_array($warrantyClaim->status, ['submitted', 'under_review', 'rejected', 'cancelled', 'resolved', 'delivered']))
                <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateSnModal">
                    🔄 Update SN
                </button>
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
                    @php
                        $sns = $ws->serial_numbers ?? [];
                        $replacementSn = $warrantyClaim->replacement_sn;
                    @endphp
                    <p class="mb-0"><small class="text-muted">SN: {{ is_array($sns) ? implode(', ', $sns) : ($sns ?: 'N/A') }}</small></p>
                    @if($replacementSn)
                        <p class="mb-0"><small class="text-success">↳ Replaced SN: {{ $replacementSn }}</small></p>
                    @endif
                </div>
            </div>

            {{-- 🆕 Challans (inline, no separate page needed) --}}
            @php $claimChallans = $warrantyClaim->challans()->latest()->get(); @endphp
            @if($claimChallans->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <strong>📄 Challans</strong>
                    <a href="{{ route('admin.warranty.claims.challans', $warrantyClaim) }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="card-body p-0">
                    @foreach($claimChallans as $ch)
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <div>
                            <span class="badge bg-{{ match($ch->challan_type) {'receive'=>'primary','send_to_supplier'=>'warning','receive_return'=>'info','delivery'=>'success',default=>'secondary'} }} me-1">{{ $ch->challan_type_label }}</span>
                            <small class="text-muted">{{ $ch->challan_no }}</small>
                            <br><small class="text-muted">{{ $ch->created_at->format('d M, h:i A') }}</small>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.warranty.challans.print', $ch) }}?autoprint=1" target="_blank" class="btn btn-sm btn-outline-primary" title="Print">🖨</a>
                            <a href="{{ route('admin.warranty.challans.pdf', $ch) }}" class="btn btn-sm btn-outline-danger" title="Download PDF">📥</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
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
    @if($warrantyClaim->status === 'awaiting_product')
    <div class="modal fade" id="receiveModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.warranty.claims.receive-product', $warrantyClaim) }}" method="POST" enctype="multipart/form-data" class="modal-content">
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
                    <label class="form-label">Product Image (Optional)</label>
                    <input type="file" name="product_image" class="form-control mb-2" accept="image/*">
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
                    <label class="form-label">Serial Number</label>
                    <input type="text" class="form-control mb-2 bg-light" value="{{ is_array($ws->serial_numbers ?? []) ? implode(', ', $ws->serial_numbers ?? []) : ($ws->serial_numbers ?? 'N/A') }}" readonly>
                    <small class="text-muted">Current SN on record — use 🔄 Update SN button to change</small>
                    <hr>
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

    {{-- 🆕 Update Serial Number Modal --}}
    <div class="modal fade" id="updateSnModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.warranty.claims.update-serial', $warrantyClaim) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5>🔄 Update Serial Number</h5></div>
                <div class="modal-body">
                    @php $currentSn = is_array($ws->serial_numbers ?? []) ? implode(', ', $ws->serial_numbers ?? []) : ($ws->serial_numbers ?? 'N/A'); @endphp
                    <p class="text-muted mb-3">Current SN: <code>{{ $currentSn }}</code></p>
                    <label class="form-label">New Serial Number <span class="text-danger">*</span></label>
                    <input type="text" name="new_serial_number" class="form-control mb-2" placeholder="Enter new serial number" required>
                    <small class="text-muted">⚠️ This updates the warranty sale, claim, and original order detail record.</small>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-warning">Update Serial Number</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
