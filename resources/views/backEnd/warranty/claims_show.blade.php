@extends('backEnd.layouts.master')
@section('title', 'Claim Details')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>🔧 Claim #{{ $warrantyClaim->claim_number }}</h4>
        <div>
            @if($warrantyClaim->status_enum->isActive())
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
                @if($warrantyClaim->status === 'in_service')
                    <form action="{{ route('admin.warranty.claims.action', [$warrantyClaim, 'resolve']) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-success btn-sm">Mark Resolved</button>
                    </form>
                @endif
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
</div>
@endsection
