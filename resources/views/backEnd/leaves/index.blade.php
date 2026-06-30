@extends('backEnd.layouts.master')
@section('title', 'Leave {{ __('Manage') }}ment')

@section('css')
<style>
    /* --- Card & Filter Styles --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        background: #fff;
    }
    .{{ __('filter') }}-container {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1.25rem;
        border-radius: 12px 12px 0 0;
    }
    
    /* --- Form Elements --- */
    .form-control-modern, .form-select-modern {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-size: 0.875rem;
        background-color: #fff;
    }
    .form-control-modern:focus, .form-select-modern:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* --- Table Styles --- */
    .table-modern th {
        background-color: #fff;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 1rem;
        border-bottom: 2px solid #f1f5f9;
    }
    .table-modern td {
        padding: 1rem;
        vertical-align: middle;
        font-size: 0.875rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:last-child td { border-bottom: none; }
    .table-modern tr:hover td { background-color: #f8fafc; }

    /* --- Badges --- */
    .badge-soft {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .badge-approved { background: #dcfce7; color: #166534; }
    .badge-rejected { background: #fee2e2; color: #991b1b; }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; background: current{{ __('Color') }}; }

    /* --- Action Buttons --- */
    .btn-icon {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; transition: all 0.2s; border: none;
    }
    .btn-icon:hover { transform: translateY(-2px); }
    .btn-approve { background: #e0f2fe; color: #0284c7; }
    .btn-reject { background: #fee2e2; color: #991b1b; }
    .btn-edit { background: #f1f5f9; color: #475569; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">

    {{-- PAGE HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="calendar" class="text-primary me-2"></i> Leave {{ __('Manage') }}ment
            </h4>
            <p class="text-muted small mb-0">{{ __('{{ __('Track') }} and manage employee leave requests.') }}</p>
        </div>
        <a href="{{ route('admin.leaves.create') }}" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
            <i data-feather="plus-circle" class="me-1" style="width: 16px;"></i> Add {{ __('New') }} Leave
        </a>
    </div>

    <div class="card card-modern">
        
        {{-- FILTERS --}}
        <div class="{{ __('filter') }}-container">
            <form method="{{ __('GET') }}" action="{{ route('admin.leaves.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">{{ __('Employee') }}</label>
                        <select name="employee_id" class="form-control select2 form-select-modern">
                            <option value="">{{ __('All {{ __('Employees') }}') }}</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }} ({{ $emp->employee_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">{{ __('{{ __('Status') }}') }}</label>
                        <select name="status" class="form-select form-select-modern">
                            <option value="">{{ __('All {{ __('Status') }}') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ __('{{ __('Approve') }}d') }}</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ __('{{ __('Reject') }}ed') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">{{ __('Leave Type') }}</label>
                        <select name="leave_type" class="form-select form-select-modern">
                            <option value="">{{ __('All Types') }}</option>
                            <option value="sick" {{ request('leave_type') == 'sick' ? 'selected' : '' }}>{{ __('Sick') }}</option>
                            <option value="casual" {{ request('leave_type') == 'casual' ? 'selected' : '' }}>{{ __('Casual') }}</option>
                            <option value="annual" {{ request('leave_type') == 'annual' ? 'selected' : '' }}>{{ __('Annual') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="{{ __('submit') }}" class="btn btn-dark w-100 fw-bold">{{ __('Filter') }}</button>
                        <a href="{{ route('admin.leaves.index') }}" class="btn btn-light border px-3" title="{{ __('Reset') }}">
                            <i data-feather="refresh-cw" style="width:16px;"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">{{ __('Employee Details') }}</th>
                        <th width="15%">{{ __('Leave Type') }}</th>
                        <th width="20%">{{ __('Duration') }}</th>
                        <th width="10%">{{ __('Days') }}</th>
                        <th width="15%">{{ __('{{ __('Status') }}') }}</th>
                        <th width="15%" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $leave)
                        <tr>
                            <td class="text-muted">{{ $loop->iteration + ($leaves->currentPage()-1)*$leaves->perPage() }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px; font-size: 12px; border: 1px solid #e0e7ff;">
                                        {{ substr($leave->employee->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $leave->employee->name }}</div>
                                        <div class="small text-muted">ID: {{ $leave->employee->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="fw-medium text-dark">{{ ucfirst($leave->leave_type) }} Leave</td>
                            <td class="text-muted">
                                {{ $leave->start_date->format('d M') }} - {{ $leave->end_date->format('d M, Y') }}
                            </td>
                            <td class="fw-bold">{{ $leave->{{ __('total') }}_days }}</td>
                            <td>
                                @if($leave->status == 'approved')
                                    <span class="badge-soft badge-approved"><span class="status-dot"></span>{{ __('{{ __('Approve') }}d') }}</span>
                                @elseif($leave->status == 'rejected')
                                    <span class="badge-soft badge-rejected"><span class="status-dot"></span>{{ __('{{ __('Reject') }}ed') }}</span>
                                @else
                                    <span class="badge-soft badge-pending"><span class="status-dot"></span>{{ __('Pending') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    @if($leave->status == 'pending')
                                        <form action="{{ route('admin.leaves.approve', $leave->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }} class="d-inline">
                                            @csrf
                                            <button type="{{ __('submit') }}" class="btn-icon btn-approve" title="{{ __('Approve') }}">
                                                <i data-feather="check" style="width:{{ __('14px') }};"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn-icon btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leave->id }}" title="{{ __('Reject') }}">
                                            <i data-feather="x" style="width:{{ __('14px') }};"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.leaves.edit', $leave->{{ __('id)') }} }}" class="btn-icon btn-edit" title="{{ __('Edit') }}">
                                        <i data-feather="edit-2" style="width:{{ __('14px') }};"></i>
                                    </a>
                                </div>

                                {{-- {{ __('Reject') }} Modal --}}
                                <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg">
                                            <div class="modal-header border-bottom-0 pb-0">
                                                <h5 class="modal-title text-danger fw-bold">{{ __('{{ __('Reject') }} Leave Request') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('admin.leaves.reject', $leave->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }}>
                                                @csrf
                                                <div class="modal-body text-start">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-muted">{{ __('{{ __('Reason') }} for {{ __('Reject') }}ion') }} <span class="text-danger">*</span></label>
                                                        <textarea name="admin_note" class="form-control" rows="3" required placeholder="{{ __('Enter rejection reason...') }}"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top-0 pt-0">
                                                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                                    <button type="{{ __('submit') }}" class="btn btn-danger btn-sm px-4">{{ __('Confirm {{ __('Reject') }}') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                {{-- End Modal --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <img src="{{ __('https://') }}cdn-icons-png.flaticon.com/512/7486/7486744.png" width="60" class="mb-3 opacity-25">
                                <p class="text-muted fw-bold mb-0">{{ __('No leave requests found') }}</p>
                                <small class="text-muted">{{ __('Adjust {{ __('filter') }}s or add a new request.') }}</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="p-4 border-top d-flex justify-content-between align-items-center bg-white rounded-bottom">
            <small class="text-muted">{{ __('Showing') }}<strong>{{ $leaves->first{{ __('Item') }}() }}</strong>{{ __('to') }}<strong>{{ $leaves->last{{ __('Item') }}() }}</strong>{{ __('of') }}<strong>{{ $leaves->{{ __('total') }}() }}</strong> records
            </small>
            <div>
                {{ $leaves->links('pagination::bootstrap-4') }}
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    });
</script>
@endpush