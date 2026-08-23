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
                @if(in_array($warrantyClaim->status, ['sent_to_supplier', 'awaiting_supplier_return', 'supplier_returned', 'serviced', 'resolved', 'product_received']))
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

            {{-- 🆕 Instant Replacement button (approved / awaiting_product / product_received) --}}
            @if(in_array($warrantyClaim->status, ['approved', 'awaiting_product', 'product_received', 'under_review']))
                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#replacementModal">
                    💥 Instant Replacement
                </button>
            @endif
        </div>
    </div>

    {{-- 🆕 Reminders strip --}}
    @php $activeReminders = $warrantyClaim->reminders->where('status', 'pending'); @endphp
    @if($activeReminders->isNotEmpty())
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header py-2"><strong>⏰ Reminders</strong></div>
                <div class="card-body py-2">
                    @foreach($activeReminders as $rem)
                    <div class="d-flex justify-content-between align-items-center py-1 {{ $rem->is_overdue ? 'text-danger' : '' }}">
                        <span>
                            <i class="fa {{ $rem->is_overdue ? 'fa-exclamation-circle' : 'fa-clock' }} me-1"></i>
                            <strong>{{ $rem->label }}</strong>
                            <small class="text-muted">— {{ $rem->remind_at->format('d M, Y h:i A') }}</small>
                            @if($rem->is_overdue)<span class="badge bg-danger ms-1">Overdue</span>@endif
                        </span>
                        <span class="d-flex gap-2 align-items-center">
                            <a href="{{ route('admin.warranty.claims.show', $warrantyClaim) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            <form action="{{ route('admin.warranty.reminders.complete', $rem) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success">✅ Done</button>
                            </form>
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

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
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
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

                            {{-- Per-step attachments --}}
                            <div class="ms-4 mt-2">
                                @if($stage->attachmentsSafe()->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @foreach($stage->attachmentsSafe() as $att)
                                        @php
                                            $attPath = $att->file_path;
                                            $attUrl = str_starts_with($attPath, 'http') ? $attPath : asset($attPath);
                                            $isImg = in_array(strtolower($att->file_type ?: pathinfo($attPath, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','webp','bmp','svg','avif']);
                                        @endphp
                                        <div class="position-relative">
                                            <a href="{{ $attUrl }}" target="_blank" title="{{ $att->file_name ?? basename($attPath) }}">
                                                @if($isImg)
                                                    <img src="{{ $attUrl }}" alt="attachment" style="width:54px;height:54px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">
                                                @else
                                                    <span class="d-flex flex-column align-items-center justify-content-center text-danger" style="width:54px;height:54px;border:1px dashed #f0ad4e;border-radius:6px;background:#fffdf5;">
                                                        <i class="mdi mdi-file-pdf-box" style="font-size:20px;"></i>
                                                        <small style="font-size:8px;">PDF</small>
                                                    </span>
                                                @endif
                                            </a>
                                            <form method="POST" action="{{ route('admin.warranty.claims.stage.attachment.delete', $att) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-danger position-absolute top-0 end-0 rounded-circle"
                                                        style="padding:0 4px;top:-5px;right:-5px;font-size:10px;line-height:1;" title="Remove"
                                                        onclick="return confirm('Remove this attachment?')">&times;</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                                @endif

                                <form method="POST" action="{{ route('admin.warranty.claims.stage.attachment', $stage) }}" enctype="multipart/form-data" class="d-flex align-items-center flex-wrap gap-2">
                                    @csrf
                                    <input type="hidden" name="attachment_path" id="stage_att_path_{{ $stage->id }}">
                                    <button type="button" class="btn btn-xs btn-outline-primary"
                                            onclick="openMediaPicker('#stage_att_path_{{ $stage->id }}', null, 'path')">
                                        <i class="fe-image"></i> {{ __('Media') }}
                                    </button>
                                    <input type="file" name="attachment_files[]" multiple
                                           accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.avif,.pdf,image/*,application/pdf"
                                           class="form-control form-control-sm" style="max-width:180px;">
                                    <button type="submit" class="btn btn-xs btn-primary">{{ __('Attach') }}</button>
                                </form>
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

            {{-- 📎 Attachments (customer + admin) — right side --}}
            @php
                $customerAttachments = [];
                foreach (($warrantyClaim->attachments ?? []) as $att) {
                    if (is_array($att)) {
                        $att = $att['url'] ?? $att['path'] ?? $att['file'] ?? $att['name'] ?? $att['src']
                            ?? (isset($att[0]) && is_string($att[0]) ? $att[0] : null);
                    } elseif (is_object($att)) {
                        $att = $att->url ?? $att->path ?? $att->file ?? $att->name ?? $att->src ?? null;
                    }
                    if (is_string($att) && $att !== '') $customerAttachments[] = $att;
                }
                // Admin attachments are stored as notes when the product is received
                $adminAttachments = [];
                foreach ($warrantyClaim->notes as $note) {
                    $n = $note->note ?? '';
                    if (str_starts_with($n, 'Product image uploaded: ')) {
                        $adminAttachments[] = trim(substr($n, strlen('Product image uploaded: ')));
                    } elseif (str_starts_with($n, 'Product image (Media Gallery): ')) {
                        $adminAttachments[] = trim(substr($n, strlen('Product image (Media Gallery): ')));
                    }
                }
                $allAttachments = [];
                foreach ($customerAttachments as $a) { $allAttachments[] = ['src' => $a, 'who' => 'Customer']; }
                foreach ($adminAttachments as $a)  { $allAttachments[] = ['src' => $a, 'who' => 'Admin']; }
            @endphp
            @if(!empty($allAttachments))
            <div class="card mb-3">
                <div class="card-header"><strong>📎 Attachments ({{ count($allAttachments) }})</strong></div>
                <div class="card-body p-0">
                    @foreach($allAttachments as $att)
                        @php
                            $src = $att['src'];
                            $attUrl = str_starts_with($src, 'http') ? $src : asset($src);
                            $attExt = strtolower(pathinfo($src, PATHINFO_EXTENSION));
                            $isImg = in_array($attExt, ['jpg','jpeg','png','gif','webp','bmp','svg','avif']);
                        @endphp
                        <div class="d-flex align-items-center px-3 py-2 border-bottom">
                            <a href="{{ $attUrl }}" target="_blank" class="text-decoration-none me-2" title="{{ basename($src) }}">
                                @if($isImg)
                                    <img src="{{ $attUrl }}" alt="attachment" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">
                                @else
                                    <span class="d-flex flex-column align-items-center justify-content-center text-danger" style="width:60px;height:60px;border:1px dashed #f0ad4e;border-radius:6px;background:#fffdf5;">
                                        <i class="mdi mdi-file-pdf-box" style="font-size:22px;"></i>
                                        <small style="font-size:8px;">PDF</small>
                                    </span>
                                @endif
                            </a>
                            <div class="small">
                                <span class="badge bg-{{ $att['who'] === 'Admin' ? 'warning text-dark' : 'info' }} mb-1">{{ $att['who'] }}</span>
                                <div class="text-truncate text-muted" style="max-width:150px;" title="{{ basename($src) }}">{{ basename($src) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- 🆕 Damage Products card --}}
            @php $damageProducts = $warrantyClaim->damageProducts; @endphp
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <strong>💥 Damage Products ({{ $damageProducts->count() }})</strong>
                    <a href="{{ route('admin.warranty.damage.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="card-body p-0">
                    @forelse($damageProducts as $dp)
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <div>
                            <span class="badge bg-{{ $dp->damage_type_enum->badgeClass() }} me-1">{{ $dp->damage_type_enum->label() }}</span>
                            <span class="badge bg-{{ $dp->status_enum->badgeClass() }}">{{ $dp->status_enum->label() }}</span>
                            <br><small class="text-muted">
                                @if($dp->original_serial_number) SN: {{ $dp->original_serial_number }} @else Damage #{{ $dp->id }} @endif
                                @if($dp->service_cost > 0) · Service: ৳{{ $dp->service_cost }} @endif
                                @if($dp->damage_cost > 0) · Loss: ৳{{ $dp->damage_cost }} @endif
                            </small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#damageStatusModal{{ $dp->id }}">
                            Update Status
                        </button>
                    </div>

                    {{-- Damage Status Update Modal --}}
                    <div class="modal fade" id="damageStatusModal{{ $dp->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('admin.warranty.damage.status', $dp) }}" method="POST" class="modal-content">
                                @csrf
                                <div class="modal-header"><h5>Update Damage Product #{{ $dp->id }}</h5></div>
                                <div class="modal-body">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select mb-2">
                                        @foreach(\App\Enums\DamageStatus::cases() as $st)
                                            <option value="{{ $st->value }}" {{ $dp->status === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                                        @endforeach
                                    </select>
                                    <div class="mb-2">
                                        <label class="form-label">Service Cost (→ Resellable)</label>
                                        <input type="number" name="service_cost" class="form-control" value="{{ $dp->service_cost }}" step="0.01" min="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Damage Cost (→ Unsellable)</label>
                                        <input type="number" name="damage_cost" class="form-control" value="{{ $dp->damage_cost }}" step="0.01" min="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Resell Price (→ Resellable)</label>
                                        <input type="number" name="resell_price" class="form-control" value="{{ $dp->resell_price }}" step="0.01" min="0">
                                    </div>
                                    <small class="text-muted">Resellable: stock +1 back to sellable. Unsellable: write-off loss.</small>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary">Update Status</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-3 mb-0">No damage products for this claim.</p>
                    @endforelse
                </div>
            </div>

            {{-- 🆕 Challans (inline, no separate page needed) --}}
            @php $claimChallans = $warrantyClaim->challans()->latest()->get(); @endphp
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <strong>📄 Challans ({{ $claimChallans->count() }})</strong>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#newChallanModal" title="Add new challan">
                            <i class="fa fa-plus"></i> Add
                        </button>
                        @if($claimChallans->isNotEmpty())
                        <a href="{{ route('admin.warranty.claims.challans', $warrantyClaim) }}" class="btn btn-sm btn-outline-secondary">View All</a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse($claimChallans as $ch)
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
                    @empty
                    <p class="text-muted text-center py-3 mb-0">No challans yet. Click <strong>+ Add</strong> to create one.</p>
                    @endforelse
                </div>
            </div>

            {{-- 🆕 Claiming Process checklist --}}
            @php
                $status = $warrantyClaim->status;
                $processes = [
                    [
                        'key'    => 'receive',
                        'label'  => '📦 Product Received',
                        'done'   => !empty($warrantyClaim->receive_challan_no) || in_array($status, ['product_received','sent_to_supplier','awaiting_supplier_return','supplier_returned','serviced','ready_for_delivery','delivered','resolved']),
                        'modal'  => 'receiveModal',
                        'hint'   => 'Receive product from customer & generate receive challan',
                    ],
                    [
                        'key'    => 'send_to_supplier',
                        'label'  => '🚚 Sent to Supplier',
                        'done'   => !empty($warrantyClaim->supplier_challan_no) || !empty($warrantyClaim->sent_supplier_id) || in_array($status, ['sent_to_supplier','awaiting_supplier_return','supplier_returned','serviced','ready_for_delivery','delivered','resolved']),
                        'modal'  => 'sendSupplierModal',
                        'hint'   => 'Send product to supplier for warranty claim & generate supplier challan',
                    ],
                    [
                        'key'    => 'receive_return',
                        'label'  => '📥 Supplier Return Received',
                        'done'   => !empty($warrantyClaim->supplier_return_challan_no) || in_array($status, ['supplier_returned','serviced','ready_for_delivery','delivered','resolved']),
                        'modal'  => 'supplierReturnModal',
                        'hint'   => 'Record product returned from supplier & generate return challan',
                    ],
                    [
                        'key'    => 'ready_for_delivery',
                        'label'  => '✅ Ready for Delivery',
                        'done'   => !empty($warrantyClaim->ready_for_delivery_at) || in_array($status, ['ready_for_delivery','delivered','resolved']),
                        'modal'  => 'readyForDeliveryForm',
                        'hint'   => 'Mark product ready to return to customer',
                    ],
                    [
                        'key'    => 'delivery',
                        'label'  => '🎉 Delivered to Customer',
                        'done'   => !empty($warrantyClaim->delivery_challan_no) || in_array($status, ['delivered','resolved']),
                        'modal'  => 'deliverModal',
                        'hint'   => 'Deliver product to customer & generate delivery challan',
                    ],
                ];
            @endphp
            <div class="card mb-3">
                <div class="card-header py-2"><strong>🔄 Claiming Process</strong></div>
                <div class="card-body p-0">
                    @foreach($processes as $proc)
                    <div class="d-flex align-items-center px-3 py-2 border-bottom {{ $proc['done'] ? 'bg-soft-success' : '' }}">
                        <div class="form-check me-2 mb-0">
                            <input class="form-check-input" type="checkbox" id="proc_{{ $proc['key'] }}" {{ $proc['done'] ? 'checked' : '' }} disabled>
                        </div>
                        <div class="flex-grow-1">
                            <label for="proc_{{ $proc['key'] }}" class="mb-0 {{ $proc['done'] ? 'text-success' : '' }}">
                                <strong>{{ $proc['label'] }}</strong>
                            </label>
                            <br><small class="text-muted">{{ $proc['hint'] }}</small>
                        </div>
                        @if(!$proc['done'])
                            @if($proc['modal'] === 'readyForDeliveryForm')
                            <form action="{{ route('admin.warranty.claims.ready-for-delivery', $warrantyClaim) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-success">Do Now</button>
                            </form>
                            @else
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#{{ $proc['modal'] }}">Do Now</button>
                            @endif
                        @else
                            <span class="badge bg-success">Done</span>
                        @endif
                    </div>
                    @endforeach
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
                    @include('backEnd.media._picker_button', [
                        'field' => 'product_image',
                        'label' => 'Choose from Media Library',
                        'current' => '',
                    ])
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
                        @forelse($productSuppliers ?? [] as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @empty
                            <option value="" disabled>No supplier found for this product</option>
                        @endforelse
                    </select>
                    <label class="form-label">Warehouse</label>
                    <input type="text" name="warehouse" class="form-control mb-2" placeholder="Warehouse name / location">
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
    @if(in_array($warrantyClaim->status, ['sent_to_supplier', 'awaiting_supplier_return', 'supplier_returned', 'serviced', 'resolved', 'product_received']))
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
                    <input type="number" name="supplier_charge" id="supplier_charge_input" class="form-control mb-2" placeholder="0.00" step="0.01" min="0">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="add_to_expenses" id="add_to_expenses" value="1" checked>
                        <label class="form-check-label" for="add_to_expenses">💰 Add to Expenses</label>
                    </div>
                    <small class="text-muted d-block mb-2">Supplier charge will be recorded as an expense in the fund ledger.</small>
                    <hr>
                    <label class="form-label">⏰ Remind me (supplier return due)</label>
                    <input type="datetime-local" name="remind_at" class="form-control mb-2">
                    <input type="hidden" name="reminder_step" value="supplier_delivery">
                    <input type="hidden" name="reminder_label" value="Supplier Return Due">
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
                    <label class="form-label">Customer Charge (if any)</label>
                    <input type="number" name="customer_charge" class="form-control mb-2" placeholder="0.00" step="0.01" min="0" value="{{ $warrantyClaim->customer_charge ?? 0 }}">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="apply_to_earnings" id="apply_to_earnings" value="1" checked>
                        <label class="form-check-label" for="apply_to_earnings">💵 Apply to Earnings</label>
                    </div>
                    <small class="text-muted d-block mb-2">Customer charge will be recorded as earning in the fund ledger.</small>
                    <hr>
                    <label class="form-label">⏰ Remind me (customer delivery due)</label>
                    <input type="datetime-local" name="remind_at" class="form-control mb-2">
                    <input type="hidden" name="reminder_step" value="customer_delivery">
                    <input type="hidden" name="reminder_label" value="Customer Delivery Due">
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

    {{-- 💥 Instant Replacement Modal --}}
    @if(in_array($warrantyClaim->status, ['approved', 'awaiting_product', 'product_received', 'under_review']))
    <div class="modal fade" id="replacementModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.warranty.claims.replacement', $warrantyClaim) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5>💥 Instant Replacement</h5></div>
                <div class="modal-body">
                    <p class="text-muted">Give the customer a new unit immediately. The damaged unit will be recorded in damage stock and your sellable stock will decrease by 1.</p>
                    <label class="form-label">Replacement Product <span class="text-danger">*</span></label>
                    <select name="replacement_product_id" class="form-select mb-2" required>
                        <option value="">-- Select product (from stock) --</option>
                        @foreach(\App\Models\Product::select('id','name','stock','new_price')->where('status',1)->limit(200)->get() as $rp)
                            <option value="{{ $rp->id }}" @if($rp->id === $warrantyClaim->product_id) selected @endif>
                                {{ $rp->name }} (Stock: {{ $rp->stock }})
                            </option>
                        @endforeach
                    </select>
                    <label class="form-label">Replacement Serial Number</label>
                    <input type="text" name="replacement_sn" class="form-control mb-2" placeholder="New unit SN">
                    <label class="form-label">Damage Type <span class="text-danger">*</span></label>
                    <select name="damage_type" class="form-select mb-2" required>
                        <option value="partial">Partial Damage</option>
                        <option value="full">Full Damage</option>
                    </select>
                    <label class="form-label">Condition Note</label>
                    <input type="text" name="condition_note" class="form-control mb-2" placeholder="e.g. screen cracked, no accessories">
                    <label class="form-label">Accessories Received</label>
                    <input type="text" name="accessories" class="form-control mb-2" placeholder="Charger, box, manual...">
                    <hr>
                    <div class="alert alert-info py-2 mb-2">
                        <small>🚚 <strong>Send damaged unit to supplier for warranty claim</strong> — optional. Select a supplier to generate a supplier challan for the damaged unit.</small>
                    </div>
                    <label class="form-label">Send to Supplier (for warranty claim)</label>
                    <select name="supplier_id" class="form-select mb-2">
                        <option value="">-- No supplier (keep in damage stock) --</option>
                        @forelse($productSuppliers ?? [] as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @empty
                            <option value="" disabled>No supplier found for this product</option>
                        @endforelse
                    </select>
                    <label class="form-label">Warehouse</label>
                    <input type="text" name="warehouse" class="form-control mb-2" placeholder="Warehouse name / location">
                    <label class="form-label">Courier</label>
                    <input type="text" name="courier" class="form-control mb-2" placeholder="Courier name">
                    <label class="form-label">Tracking ID</label>
                    <input type="text" name="tracking_id" class="form-control mb-2" placeholder="Tracking number">
                    <div class="alert alert-warning py-2 mb-0">
                        <small>⚠️ Replaces the warranty serial number with the new unit's SN.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success">Issue Replacement & Adjust Stock</button>
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

{{-- ➕ New Challan Modal (add any challan type) --}}
<div class="modal fade" id="newChallanModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="newChallanForm" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5>➕ Add New Challan</h5></div>
            <div class="modal-body">
                <label class="form-label">Challan Type <span class="text-danger">*</span></label>
                <select name="challan_type" id="new_challan_type" class="form-select mb-3" required>
                    <option value="">-- Select challan type --</option>
                    <option value="receive">📦 Product Receive</option>
                    <option value="send_to_supplier">🚚 Send to Supplier</option>
                    <option value="receive_return">📥 Supplier Return</option>
                    <option value="delivery">🎉 Customer Delivery</option>
                </select>

                {{-- 📦 Receive fields --}}
                <div class="challan-fields" data-type="receive" style="display:none;">
                    <label class="form-label">Product Condition</label>
                    <select name="condition" class="form-select mb-2">
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

                {{-- 🚚 Send to Supplier fields --}}
                <div class="challan-fields" data-type="send_to_supplier" style="display:none;">
                    <label class="form-label">Select Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select mb-2" required>
                        <option value="">-- Select --</option>
                        @forelse($productSuppliers ?? [] as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @empty
                            <option value="" disabled>No supplier found for this product</option>
                        @endforelse
                    </select>
                    <label class="form-label">Warehouse</label>
                    <input type="text" name="warehouse" class="form-control mb-2" placeholder="Warehouse name / location">
                    <label class="form-label">Courier</label>
                    <input type="text" name="courier" class="form-control mb-2" placeholder="Courier name">
                    <label class="form-label">Tracking ID</label>
                    <input type="text" name="tracking_id" class="form-control mb-2" placeholder="Tracking number">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Additional info..."></textarea>
                </div>

                {{-- 📥 Supplier Return fields --}}
                <div class="challan-fields" data-type="receive_return" style="display:none;">
                    <label class="form-label">Return Type</label>
                    <select name="return_type" class="form-select mb-2">
                        <option value="repaired">Repaired</option>
                        <option value="replaced">Replaced (new unit)</option>
                        <option value="refunded">Refunded</option>
                    </select>
                    <label class="form-label">Replacement Serial Number</label>
                    <input type="text" name="replacement_sn" class="form-control mb-2" placeholder="New SN from supplier">
                    <label class="form-label">Supplier's Return Challan No</label>
                    <input type="text" name="supplier_return_challan" class="form-control mb-2" placeholder="Supplier's challan reference">
                    <label class="form-label">Supplier Charge (if any)</label>
                    <input type="number" name="supplier_charge" class="form-control mb-2" placeholder="0.00" step="0.01" min="0">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>

                {{-- 🎉 Delivery fields --}}
                <div class="challan-fields" data-type="delivery" style="display:none;">
                    <label class="form-label">Delivery Method</label>
                    <select name="delivery_method" class="form-select mb-2">
                        <option value="Counter Pickup">Counter Pickup</option>
                        <option value="Courier">Courier</option>
                        <option value="Hand Delivery">Hand Delivery</option>
                    </select>
                    <label class="form-label">Customer Charge (if any)</label>
                    <input type="number" name="customer_charge" class="form-control mb-2" placeholder="0.00" step="0.01" min="0" value="{{ $warrantyClaim->customer_charge ?? 0 }}">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Delivery notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Generate Challan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ➕ New Challan modal — toggle fields + set form action by challan type
    (function () {
        var typeSelect = document.getElementById('new_challan_type');
        var form = document.getElementById('newChallanForm');
        if (!typeSelect || !form) return;

        var actions = {
            'receive':           '{{ route('admin.warranty.claims.receive-product', $warrantyClaim) }}',
            'send_to_supplier':  '{{ route('admin.warranty.claims.send-to-supplier', $warrantyClaim) }}',
            'receive_return':    '{{ route('admin.warranty.claims.supplier-return', $warrantyClaim) }}',
            'delivery':          '{{ route('admin.warranty.claims.deliver', $warrantyClaim) }}',
        };

        function sync() {
            var type = typeSelect.value;
            document.querySelectorAll('.challan-fields').forEach(function (el) {
                el.style.display = (el.getAttribute('data-type') === type) ? '' : 'none';
            });
            // Only require supplier_id when the "Send to Supplier" type is selected,
            // otherwise the hidden required field blocks form submission.
            var supplierSelect = form.querySelector('select[name="supplier_id"]');
            if (supplierSelect) {
                if (type === 'send_to_supplier') {
                    supplierSelect.setAttribute('required', 'required');
                } else {
                    supplierSelect.removeAttribute('required');
                }
            }
            if (actions[type]) {
                form.setAttribute('action', actions[type]);
            }
        }

        typeSelect.addEventListener('change', sync);
        // Reset when modal opens
        document.getElementById('newChallanModal').addEventListener('shown.bs.modal', function () {
            typeSelect.value = '';
            sync();
        });
    })();
</script>

{{-- Reusable Media Gallery picker — "choose image from media library" --}}
@include('backEnd.media._picker')
@endsection
