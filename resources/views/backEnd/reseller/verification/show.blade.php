@extends('backEnd.layouts.master')
@section('title','{{ __('Reseller {{ __('Verification') }} Details') }}')
@section('content')
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.reseller.verification.index') }}" class="btn btn-primary rounded-pill">
                        <i class="fe-arrow-left"></i> Back to List
                    </a>
                </div>
                <h4 class="page-title">{{ __('Reseller {{ __('Verification') }} Details') }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <!-- {{ __('Reseller Information') }} Card -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fe-user"></i> {{ __('Reseller Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>{{ __('Name') }}:</strong> {{ $reseller->name }}</p>
                            <p><strong>{{ __('{{ __('Shop') }} {{ __('Name') }}') }}:</strong> {{ $reseller->shop_name ?? '{{ __('N/A') }}' }}</p>
                            <p><strong>{{ __('Email') }}:</strong> {{ $reseller->email }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __('Status') }}:</strong> 
                                @if($reseller->status == 1)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                @endif
                            </p>
                            <p><strong>{{ __('{{ __('Wallet') }} {{ __('Balance') }}') }}:</strong> ৳{{ number_format($reseller->wallet_balance ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- {{ __('{{ __('Verification') }} {{ __('Status') }}') }} Card -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fe-check-circle"></i> {{ __('{{ __('Verification') }} {{ __('Status') }}') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>{{ __('{{ __('Current') }} {{ __('Status') }}') }}:</strong>
                                @if($reseller->verification_status == 'approved')
                                    <span class="badge bg-success">{{ __('{{ __('Approve') }}d') }}</span>
                                @elseif($reseller->verification_status == 'rejected')
                                    <span class="badge bg-danger">{{ __('{{ __('Reject') }}ed') }}</span>
                                @else
                                    <span class="badge bg-warning">{{ __('Pending') }}</span>
                                @endif
                            </p>
                            @if($reseller->verified_at)
                                <p><strong>{{ __('{{ __('Verified') }} At') }}:</strong> 
                                    @if(is_object($reseller->verified_at))
                                        {{ $reseller->verified_at->format('d M, Y h:i A') }}
                                    @else
                                        {{ \Carbon\Carbon::parse($reseller->verified_at)->format('d M, Y h:i A') }}
                                    @endif
                                </p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            @if($reseller->verification_note)
                                <p><strong>{{ __('Admin {{ __('Note') }}') }}:</strong></p>
                                <div class="alert alert-info">
                                    {{ $reseller->verification_note }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- {{ __('{{ __('Verification') }} {{ __('Documents') }}') }} Card -->
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fe-file-text"></i> {{ __('{{ __('Verification') }} {{ __('Documents') }}') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Voter ID Front -->
                        <div class="col-md-4 mb-4">
                            <label class="form-label"><strong>{{ __('Voter ID Card - Front Side') }}</strong></label>
                            @if($reseller->voter_id_front)
                                <div class="text-center">
                                    <img src="{{ asset($reseller->voter_id_front) }}" alt="Voter ID Front" 
                                         class="img-thumbnail" style="max-width: 100%; cursor: pointer; max-height: 400px;" 
                                         onclick="window.open('{{ asset($reseller->voter_id_front) }}', '_blank')">
                                    <p class="mt-2">
                                        <a href="{{ asset($reseller->voter_id_front) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fe-maximize-2"></i> View Full Size
                                        </a>
                                    </p>
                                </div>
                            @else
                                <div class="alert alert-warning">{{ __('Not uploaded') }}</div>
                            @endif
                        </div>

                        <!-- Voter ID Back -->
                        <div class="col-md-4 mb-4">
                            <label class="form-label"><strong>{{ __('Voter ID Card - Back Side') }}</strong></label>
                            @if($reseller->voter_id_back)
                                <div class="text-center">
                                    <img src="{{ asset($reseller->voter_id_back) }}" alt="Voter ID Back" 
                                         class="img-thumbnail" style="max-width: 100%; cursor: pointer; max-height: 400px;" 
                                         onclick="window.open('{{ asset($reseller->voter_id_back) }}', '_blank')">
                                    <p class="mt-2">
                                        <a href="{{ asset($reseller->voter_id_back) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fe-maximize-2"></i> View Full Size
                                        </a>
                                    </p>
                                </div>
                            @else
                                <div class="alert alert-warning">{{ __('Not uploaded') }}</div>
                            @endif
                        </div>

                        <!-- Self Image -->
                        <div class="col-md-4 mb-4">
                            <label class="form-label"><strong>{{ __('Self Image (Your Photo)') }}</strong></label>
                            @if($reseller->self_image)
                                <div class="text-center">
                                    <img src="{{ asset($reseller->self_image) }}" alt="Self Image" 
                                         class="img-thumbnail" style="max-width: 100%; cursor: pointer; max-height: 400px;" 
                                         onclick="window.open('{{ asset($reseller->self_image) }}', '_blank')">
                                    <p class="mt-2">
                                        <a href="{{ asset($reseller->self_image) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fe-maximize-2"></i> View Full Size
                                        </a>
                                    </p>
                                </div>
                            @else
                                <div class="alert alert-warning">{{ __('Not uploaded') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            @if($reseller->verification_status == 'pending')
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fe-check-circle"></i> {{ __('{{ __('Verification') }} {{ __('Actions') }}') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('admin.reseller.verification.approve', $reseller->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }}>
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="admin_note_approve">{{ __('Admin {{ __('{{ __('Note') }} {{ __('({{ __('Optional') }})') }}') }}') }}</label>
                                    <textarea name="admin_note" id="admin_note_approve" class="form-control" rows="3" placeholder="{{ __('Add a note...') }}"></textarea>
                                </div>
                                <button type="{{ __('submit') }}" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to approve this reseller verification?')">
                                    <i class="fe-check"></i> {{ __('Approve') }} {{ __('Verification') }}
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('admin.reseller.verification.reject', $reseller->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }}>
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="rejection_reason">{{ __('{{ __('{{ __('Reject') }}ion {{ __('Reason') }}') }} *') }}</label>
                                    <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" placeholder="{{ __('Enter reason for rejection...') }}" required></textarea>
                                </div>
                                <button type="{{ __('submit') }}" class="btn btn-danger btn-lg" onclick="return confirm('{{ __('Are you sure you want to reject') }} this reseller verification?')">
                                    <i class="fe-x"></i> {{ __('Reject') }} {{ __('Verification') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fe-info"></i> This reseller verification has already been {{ $reseller->verification_status }}.
                        @if($reseller->verification_status == 'rejected' && $reseller->verification_note)
                            <br><strong>{{ __('Reason') }}:</strong> {{ $reseller->verification_note }}
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
