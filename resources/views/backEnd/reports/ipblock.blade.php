@extends('backEnd.layouts.master')
@section('title','Manage IP Block')

@section('css')
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<style>
    /* Premium Card & Table */
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .card-header {
        background: #fff;
        border-bottom: 1px solid #f1f5f7;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 700;
        color: #2d3436;
        margin: 0;
    }
    .header-icon {
        width: 35px;
        height: 35px;
        background: rgba(114, 124, 245, 0.1);
        color: #727cf5;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    /* Form Styles */
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #636e72;
        margin-bottom: 8px;
    }
    .form-control {
        background-color: #fbfcff;
        border: 1px solid #eef2f7;
        padding: 12px 15px;
        border-radius: 8px;
        font-size: 14px;
        color: #2d3436;
        transition: all 0.3s;
    }
    .form-control:focus {
        background-color: #fff;
        border-color: #727cf5;
        box-shadow: 0 0 0 4px rgba(114, 124, 245, 0.1);
    }

    /* Table Styling */
    .table thead th {
        background-color: #f9fbfd;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        color: #8391a2;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #eef2f7;
        padding: 12px 15px;
    }
    .table tbody td {
        vertical-align: middle;
        padding: 15px;
        border-bottom: 1px solid #f1f5f7;
        color: #313b5e;
        font-size: 14px;
    }

    /* Action Buttons */
    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #6c757d;
        transition: all 0.2s;
        border: 1px solid transparent;
        background: #f9fbfd;
        cursor: pointer;
    }
    .action-btn:hover { background-color: #eef2f7; color: #343a40; }
    .btn-edit:hover { background-color: rgba(114, 124, 245, 0.1); color: #727cf5; }
    .btn-delete:hover { background-color: rgba(250, 92, 124, 0.1); color: #fa5c7c; }

    /* Button Style */
    .btn-submit {
        background: linear-gradient(45deg, #fa5c7c, #ff3b30); /* Reddish for warning action */
        border: none;
        color: white;
        padding: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(250, 92, 124, 0.3);
        transition: 0.3s;
        width: 100%;
        border-radius: 8px;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(250, 92, 124, 0.4);
    }
    
    /* Modal Styling */
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .modal-header {
        border-bottom: 1px solid #f1f5f7;
        background: #f9fbfd;
        border-radius: 12px 12px 0 0;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between py-4">
                <div>
                    <h4 class="page-title mb-1 text-dark fw-bold">IP Block Manager</h4>
                    <p class="text-muted font-size-13 mb-0">Restrict access for specific IP addresses.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <div class="header-icon"><i class="fe-shield-off"></i></div>
                    <h5 class="card-title">Block New IP</h5>
                </div>
                <div class="card-body">
                    <form action="{{route('customers.ipblock.store')}}" method="POST" data-parsley-validate>
                        @csrf
                        <div class="form-group mb-3">
                            <label for="ip_no" class="form-label">IP Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('ip_no') is-invalid @enderror" 
                                   name="ip_no" value="{{ old('ip_no', $prefillIp ?? '') }}" id="ip_no" 
                                   placeholder="e.g. 192.168.0.1" required>
                            @error('ip_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" 
                                      name="reason" rows="4" id="reason" 
                                      placeholder="Why is this IP being blocked?" required>{{ old('reason', $prefillReason ?? '') }}</textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fe-lock me-1"></i> Block IP Address
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card bg-soft-warning border-0">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <i class="fe-alert-triangle font-size-18 me-2 text-warning"></i>
                        <p class="mb-0 font-size-13 text-warning">
                            Blocked IPs will not be able to access the website. Please be careful.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="header-icon"><i class="fe-list"></i></div>
                    <h5 class="card-title">Blocked IP List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable-buttons" class="table table-hover w-100 dt-responsive nowrap">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">SL</th>
                                    <th>IP Address</th>
                                    <th>Reason</th>
                                    <th class="text-end" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $key=>$value)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>
                                        <span class="font-weight-bold text-dark">{{$value->ip_no}}</span>
                                    </td>
                                    <td>{{$value->reason}}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="javascript:void(0);" class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#ipEdit{{$value->id}}" title="Edit">
                                                <i class="fe-edit"></i>
                                            </a>

                                            <form method="post" action="{{route('customers.ipblock.destroy')}}" class="d-inline">
                                                @csrf
                                                <input type="hidden" value="{{$value->id}}" name="id">
                                                <button type="submit" class="action-btn btn-delete delete-confirm" title="Delete">
                                                    <i class="fe-trash-2"></i>
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

@foreach($data as $key=>$value)
<div class="modal fade" id="ipEdit{{$value->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark fw-bold">Edit IP Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="{{route('customers.ipblock.update')}}" method="POST" data-parsley-validate>
                    @csrf
                    <input type="hidden" name="id" value="{{$value->id}}">
                    
                    <div class="form-group mb-3">
                        <label for="ip_no" class="form-label">IP Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ip_no" value="{{$value->ip_no}}" required>
                    </div>

                    <div class="form-group mb-4">
                        <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="4" required>{{$value->reason}}</textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill">
                            Update Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection

@section('script')
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.flash.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-select/js/dataTables.select.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/pdfmake.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/vfs_fonts.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/js/pages/datatables.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-validation.init.js"></script>
@endsection