@extends('backEnd.layouts.master')
@section('title', 'Manage Resellers')

@section('css')
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<style>
    /* --- Modern Card --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #fff;
    }

    /* --- Filter Section --- */
    .filter-box {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem;
        border-radius: 12px 12px 0 0;
    }
    .form-control-modern {
        border: 1px solid #e2e8f0;
        padding: 0.6rem 1rem;
        border-radius: 8px;
    }
    .form-control-modern:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* --- Table Styling --- */
    .table-modern th {
        background-color: #fff;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 1rem;
        border-bottom: 2px solid #f1f5f9;
        white-space: nowrap;
    }
    .table-modern td {
        vertical-align: middle;
        padding: 1rem;
        font-size: 0.875rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:hover td { background-color: #f8fafc; }

    /* --- Reseller Avatar --- */
    .reseller-avatar-placeholder {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: #e0e7ff; color: #4338ca;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 16px;
        border: 1px solid #c7d2fe;
    }

    /* --- Badges --- */
    .badge-soft {
        padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .badge-verified { background: #dcfce7; color: #166534; }
    .badge-rejected { background: #fee2e2; color: #991b1b; }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    /* --- Action Buttons --- */
    .btn-icon {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; transition: all 0.2s; border: none; background: #f1f5f9; color: #64748b;
    }
    .btn-icon:hover { transform: translateY(-2px); }
    .btn-edit:hover { background: #e0e7ff; color: #4338ca; }
    .btn-delete:hover { background: #fee2e2; color: #ef4444; }
    .btn-toggle-on:hover { background: #fee2e2; color: #ef4444; }
    .btn-toggle-off:hover { background: #dcfce7; color: #16a34a; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">

    {{-- PAGE HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="users" class="text-primary me-2"></i> Manage Resellers
            </h4>
            <p class="text-muted small mb-0">Overview of all registered resellers and partners.</p>
        </div>
        <div>
            {{-- Add create button if needed --}}
        </div>
    </div>

    <div class="card card-modern">
        
        {{-- SEARCH / FILTER --}}
        <div class="filter-box">
            <form method="GET" action="{{ route('admin.resellers.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i data-feather="search" style="width: 16px;"></i></span>
                            <input type="text" name="keyword" class="form-control form-control-modern border-start-0" 
                                   placeholder="Search by name, shop, email..." value="{{ request('keyword') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary fw-bold px-4">Search</button>
                        @if(request('keyword'))
                            <a href="{{ route('admin.resellers.index') }}" class="btn btn-light border ms-2">Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table id="datatable-buttons" class="table table-modern w-100">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Reseller Profile</th>
                        <th width="15%">Shop Info</th>
                        <th width="15%">Contact</th>
                        <th width="10%">Wallet</th>
                        <th width="10%">Verification</th>
                        <th width="10%">Status</th>
                        <th width="10%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resellers as $key => $reseller)
                    <tr>
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        
                        {{-- Reseller Profile --}}
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="reseller-avatar-placeholder me-3">
                                    {{ substr($reseller->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $reseller->name }}</div>
                                    <div class="small text-muted" style="font-size: 11px;">ID: #{{ $reseller->id }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Shop Info --}}
                        <td>
                            <div class="text-dark fw-medium">{{ $reseller->shop_name ?? 'N/A' }}</div>
                        </td>

                        {{-- Contact --}}
                        <td>
                            <div class="d-flex flex-column small">
                                <span class="text-muted"><i data-feather="mail" style="width: 12px;"></i> {{Str::limit($reseller->email, 20)}}</span>
                            </div>
                        </td>

                        {{-- Wallet --}}
                        <td>
                            <span class="fw-bold text-dark bg-light px-2 py-1 rounded border">
                                ৳{{ number_format($reseller->wallet_balance ?? 0, 2) }}
                            </span>
                        </td>

                        {{-- Verification --}}
                        <td>
                            @if($reseller->verification_status == 'approved')
                                <span class="badge-soft badge-verified"><span class="status-dot"></span> Verified</span>
                            @elseif($reseller->verification_status == 'rejected')
                                <span class="badge-soft badge-rejected"><span class="status-dot"></span> Rejected</span>
                            @else
                                <span class="badge-soft badge-pending"><span class="status-dot"></span> Pending</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td>
                            @if($reseller->status == 1)
                                <span class="badge bg-success small">Active</span>
                            @else
                                <span class="badge bg-danger small">Inactive</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                
                                {{-- Status Toggle --}}
                                <form method="post" action="{{ route('admin.resellers.toggle-status', $reseller->id) }}" class="d-inline">
                                    @csrf
                                    @if($reseller->status == 1)
                                        <button type="submit" class="btn-icon btn-toggle-on" title="Deactivate">
                                            <i data-feather="thumbs-down" style="width:14px;"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn-icon btn-toggle-off" title="Activate">
                                            <i data-feather="thumbs-up" style="width:14px;"></i>
                                        </button>
                                    @endif
                                </form>

                                {{-- Edit --}}
                                <a href="{{ route('admin.resellers.edit', $reseller->id) }}" class="btn-icon btn-edit" title="Edit Profile">
                                    <i data-feather="edit-2" style="width:14px;"></i>
                                </a>

                                {{-- Delete --}}
                                <form method="post" action="{{ route('admin.resellers.destroy', $reseller->id) }}" class="d-inline" onsubmit="return confirm('Delete this reseller? Associated accounts will also be deleted.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-delete" title="Delete Reseller">
                                        <i data-feather="trash-2" style="width:14px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="p-4 border-top d-flex justify-content-between align-items-center bg-white rounded-bottom">
            <small class="text-muted">
                Showing <strong>{{ $resellers->firstItem() }}</strong> to <strong>{{ $resellers->lastItem() }}</strong> of <strong>{{ $resellers->total() }}</strong> resellers
            </small>
            <div>
                {{ $resellers->links('pagination::bootstrap-4') }}
            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/js/pages/datatables.init.js"></script>
@endsection