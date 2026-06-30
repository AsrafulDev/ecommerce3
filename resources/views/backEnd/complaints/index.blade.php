@extends('backEnd.layouts.master')
@section('title','{{ __('{{ __('Customer') }} {{ __('Complaints') }}') }}')

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
    }
    .card-body { padding: 25px; }

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
        font-size: {{ __('14px') }};
    }

    /* {{ __('{{ __('Customer') }} Info') }} Styling */
    .customer-name {
        font-weight: 600;
        color: #343a40;
        font-size: {{ __('14px') }};
        margin: 0;
    }
    .customer-{{ __('phone') }} {
        font-size: 12px;
        color: #98a6ad;
    }

    /* Complaint {{ __('Image *') }}/
    .complaint-img {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #dee2e6;
        padding: 2px;
        transition: transform 0.2s;
    }
    .complaint-img:hover { transform: scale(1.1); }

    /* Soft Badges */
    .badge-soft-warning { background-color: rgba(255, 188, 0, 0.18); color: #ffbc00; }
    .badge-soft-info { background-color: rgba(57, 175, 209, 0.18); color: #39afd1; }
    .badge-soft-success { background-color: rgba(10, 207, 151, 0.18); color: #0acf97; }
    .badge-pill { padding: 5px 10px; border-radius: 50rem; font-weight: 500; font-size: 11px; }

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
    .btn-delete:hover { background-color: rgba(250, 92, 124, 0.1); color: #fa5c7c; }
    .btn-update {
        background-color: #727cf5;
        color: #fff;
        border: none;
        padding: 5px 10px;
        font-size: 12px;
        border-radius: 4px;
    }
    .btn-update:hover { background-color: #5b63c9; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    
    <div class="row mb-3 mt-3">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title" style="font-weight: 700; color: #2d3436;">{{ __('{{ __('Customer') }} {{ __('Complaints') }}') }}</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-buttons" class="table table-hover w-100 dt-responsive nowrap">
                        <thead>
                            <tr>
                                <th style="width: 50px;">{{ __('SL') }}</th>
                                <th>{{ __('Order ID') }}</th>
                                <th>{{ __('{{ __('Customer') }} Info') }}</th>
                                <th style="width: 25%;">{{ __('Description') }}</th>
                                <th>{{ __('Image') }}</th>
                                <th>{{ __('{{ __('Current') }} {{ __('Status') }}') }}</th>
                                <th style="width: 200px;">{{ __('{{ __('Update {{ __('Status') }}') }} / Action') }}</th>
                            </tr>
                        </thead>                
                        <tbody>
                            @forelse($complaints as $complaint)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                
                                <td>
                                    <span class="fw-bold text-primary">#{{ $complaint->order_id ?? '{{ __('N/A') }}' }}</span>
                                </td>

                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="customer-name">{{ $complaint->name }}</span>
                                        <span class="customer-{{ __('phone') }}"><i class="fe-{{ __('phone') }} me-1"></i>{{ $complaint->{{ __('phone') }} }}</span>
                                    </div>
                                </td>

                                <td>
                                    <span class="text-muted" title="{{ $complaint->description }}">
                                        {{ \Illuminate\Support\Str::limit($complaint->description, 50) }}
                                    </span>
                                </td>

                                <td>
                                    @if($complaint->image)
                                        <a href="{{ asset('public/'.$complaint->image) }}" target="_blank">
                                            <img src="{{ asset('public/'.$complaint->image) }}" class="complaint-img" alt="Evidence">
                                        </a>
                                    @else
                                        <span class="badge bg-light text-dark">{{ __('No Image') }}</span>
                                    @endif
                                </td>

                                <td>
                                    @if($complaint->status === 'pending')
                                        <span class="badge badge-pill badge-soft-warning">{{ __('Pending') }}</span>
                                    @elseif($complaint->status === 'processing')
                                        <span class="badge badge-pill badge-soft-info">{{ __('Processing') }}</span>
                                    @else
                                        <span class="badge badge-pill badge-soft-success">{{ __('Resolved') }}</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        {{-- {{ __('Update {{ __('Status') }}') }} Form --}}
                                        <form action="{{ route('backEnd.complaints.status', $complaint->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }} class="d-flex align-items-center gap-1">
                                            @csrf
                                            <select name="status" class="form-select form-select-sm" style="width: 110px; font-size: 12px;">
                                                <option value="pending" {{ $complaint->status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                                <option value="processing" {{ $complaint->status == 'processing' ? 'selected' : '' }}>{{ __('Processing') }}</option>
                                                <option value="resolved" {{ $complaint->status == 'resolved' ? 'selected' : '' }}>{{ __('Resolved') }}</option>
                                            </select>
                                            <button type="{{ __('submit') }}" class="action-btn btn-update change-confirm" title="{{ __('Update') }}">
                                                <i class="fe-check"></i>
                                            </button>
                                        </form>

                                        {{-- Delete Button --}}
                                        <form action="{{ route('backEnd.complaints.destroy', $complaint->{{ __('id)') }} }}" method={{ __('"{{ __('POST') }}"') }} class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="{{ __('submit') }}" class="action-btn btn-delete delete-confirm" title="{{ __('Delete') }}">
                                                <i class="fe-trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            {{-- Empty state usually handled by datatable, but safe to keep --}}
                            @endforelse
                        </tbody>
                    </table>
                    
                    <div class="mt-4">
                        {{ $complaints->links('pagination::bootstrap-4') }}
                    </div>

                </div> </div> </div></div>
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
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.flash.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-select/js/dataTables.select.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/pdfmake.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/vfs_fonts.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/js/pages/datatables.init.js"></script>
@endsection