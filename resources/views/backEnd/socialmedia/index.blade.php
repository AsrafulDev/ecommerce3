@extends('backEnd.layouts.master')
@section('title','Social Media Management')

@section('css')
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<style>
    /* Professional Card Styling */
    .card-modern {
        border: none;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.05);
        background: #fff;
        overflow: hidden;
    }
    
    .table-pro thead th {
        background-color: #f8f9fa;
        color: #475569;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.8px;
        border-bottom: 2px solid #e2e8f0;
        padding: 18px 15px;
    }
    
    .table-pro tbody td {
        vertical-align: middle;
        color: #334155;
        font-size: 14px;
        padding: 15px;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Social Icon Preview */
    .social-icon-wrapper {
        width: 40px;
        height: 40px;
        background: #f1f5f9;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #0f172a;
        transition: all 0.3s ease;
    }
    tr:hover .social-icon-wrapper {
        background: #2563eb;
        color: #fff;
    }

    /* Status Pill Badges */
    .status-badge {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
    }
    .status-active {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .status-inactive {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    /* Action Buttons */
    .btn-action {
        width: 35px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        margin: 0 3px;
        transition: all 0.2s;
        border: none;
        text-decoration: none;
    }
    .btn-edit-modern {
        background-color: #eff6ff;
        color: #2563eb !important;
    }
    .btn-edit-modern:hover {
        background-color: #2563eb;
        color: #fff !important;
        transform: translateY(-2px);
    }
    .btn-delete-modern {
        background-color: #fef2f2;
        color: #dc2626 !important;
    }
    .btn-delete-modern:hover {
        background-color: #dc2626;
        color: #fff !important;
        transform: translateY(-2px);
    }
    .btn-status-toggle {
        background-color: #fef2f2;
        color: #dc2626 !important;
    }
    .btn-status-toggle:hover {
        background-color: #dc2626;
        color: #fff !important;
        transform: translateY(-2px);
    }
    .btn-status-active {
        background-color: #dcfce7;
        color: #15803d !important;
    }
    .btn-status-active:hover {
        background-color: #15803d;
        color: #fff !important;
        transform: translateY(-2px);
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h4 class="fw-bold text-dark m-0">Social Media Links</h4>
            <span class="text-muted small">Manage your official social media presence and links</span>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="{{route('socialmedias.create')}}" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                <i class="fa fa-plus me-1"></i> Add Connection
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-modern">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="datatable-buttons" class="table table-pro mb-0 w-100">
                            <thead>
                                <tr>
                                    <th width="5%">SL</th>
                                    <th width="15%">Platform</th>
                                    <th width="40%">Name / Title</th>
                                    <th width="15%">Status</th>
                                    <th width="15%" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($show_data as $key=>$value)
                                <tr>
                                    <td><span class="text-muted fw-bold">#{{$loop->iteration}}</span></td>
                                    <td>
                                        <div class="social-icon-wrapper">
                                            <i class="{{$value->icon}}"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark d-block" style="font-size: 15px;">{{$value->title}}</span>
                                    </td>
                                    <td>
                                        @if($value->status == 1)
                                            <span class="status-badge status-active">
                                                <i class="mdi mdi-check-circle me-1"></i> Active
                                            </span>
                                        @else
                                            <span class="status-badge status-inactive">
                                                <i class="mdi mdi-alert-circle me-1"></i> Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center">
                                            
                                            {{-- Status Toggle --}}
                                            @if($value->status == 1)
                                                <form method="post" action="{{route('socialmedias.inactive')}}" class="d-inline"> 
                                                    @csrf
                                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                                    <button type="submit" class="btn-action btn-status-toggle shadow-sm" title="Deactivate">
                                                        <i class="fa fa-thumbs-down"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="post" action="{{route('socialmedias.active')}}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                                    <button type="submit" class="btn-action btn-status-active shadow-sm" title="Activate">
                                                        <i class="fa fa-thumbs-up"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <a href="{{route('socialmedias.edit',$value->id)}}" 
                                               class="btn-action btn-edit-modern shadow-sm ms-1" 
                                               title="Edit Platform">
                                                <i class="fa fa-pencil-alt"></i>
                                            </a>

                                            <form method="post" action="{{route('socialmedias.destroy')}}" class="d-inline ms-1" id="delete-form-{{$value->id}}">
                                                @csrf
                                                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                                <button type="button" class="btn-action btn-delete-modern shadow-sm delete-confirm" 
                                                        title="Remove Platform"
                                                        data-form-id="delete-form-{{$value->id}}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                            
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
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
<script src="{{asset('/public/backEnd/')}}/assets/js/pages/datatables.init.js"></script>

<script>
    $(document).ready(function() {
        // Delete confirmation handler
        $(document).on('click', '.delete-confirm', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            var formId = $(this).data('form-id');
            var form = $('#' + formId);
            
            if (form.length === 0) {
                form = $(this).closest('form');
            }
            
            // Use master layout's swal if available, otherwise use confirm
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Are you sure?",
                    text: "This social media link will be permanently removed!",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        form.submit();
                    }
                });
            } else {
                // Fallback to native confirm
                if (confirm('Are you sure you want to delete this social media link?')) {
                    form.submit();
                }
            }
            
            return false;
        });
    });
</script>
@endsection