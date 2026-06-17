@extends('backEnd.layouts.master')
@section('title','Inhouse Products')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    /* আপনার দেওয়া কাস্টম আধুনিক স্টাইল */
    .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border-radius: 0.75rem; }
    .table thead { background-color: #f8f9fa; }
    .table thead th { border-top: none; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; color: #6c757d; font-weight: 700; padding: 12px 15px; }
    .table tbody td { vertical-align: middle; padding: 12px 15px; border-color: #f1f3f5; }
    
    /* ইমেজ স্টাইল */
    .product-img { border-radius: 8px; object-fit: cover; border: 1px solid #ebedf2; transition: transform 0.2s ease; }
    .product-img:hover { transform: scale(1.1); }

    /* বাটন ও ব্যাজ স্টাইল */
    .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; transition: 0.3s; border: none; }
    .btn-edit { background: #e3f2fd; color: #2196f3; }
    .btn-edit:hover { background: #2196f3; color: #fff; }
    .btn-delete { background: #ffebee; color: #f44336; }
    .btn-delete:hover { background: #f44336; color: #fff; }
    .btn-status-toggle { background: #f1f3f5; color: #495057; }
    .btn-status-toggle:hover { background: #dee2e6; }
    .btn-status-active { background: #e8f5e9; color: #2e7d32; }
    .btn-status-active:hover { background: #2e7d32; color: #fff; }

    /* সফট ব্যাজ কালার */
    .badge-soft-primary { background-color: #e1f5fe; color: #039be5; }
    .badge-soft-success { background-color: #e8f5e9; color: #2e7d32; }
    .badge-soft-warning { background-color: #fff3e0; color: #ef6c00; }
    .badge-soft-danger { background-color: #ffebee; color: #c62828; }
    .badge-soft-info { background-color: #e0f7fa; color: #00838f; }
    .badge-soft-secondary { background-color: #f1f3f5; color: #495057; }

    .action2-btn { list-style: none; padding: 0; margin: 0; display: flex; gap: 8px; flex-wrap: wrap; }
    
    /* নাম এবং টেক্সট স্টাইল */
    .product-title { font-size: 14px; font-weight: 600; color: #343a40; margin: 0; }
    .text-small { font-size: 11px; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between py-3">
                <h4 class="page-title mb-0">Inhouse Products Library</h4>
                <div class="page-title-right">
                    <a href="{{route('products.create')}}" class="btn btn-danger rounded-pill shadow-sm">
                        <i class="fe-plus me-1"></i> Add New Product
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    
                    <div class="row mb-3 align-items-center">
                        <div class="col-lg-8 col-md-7">
                            <ul class="action2-btn">
                                <li>
                                    <button data-url="{{ route('products.update_deals') }}" data-status="1" class="btn btn-sm btn-outline-success rounded-pill hotdeal_update">
                                        <i class="fe-thumbs-up me-1"></i> Set Deal
                                    </button>
                                </li>
                                <li>
                                    <button data-url="{{ route('products.update_deals') }}" data-status="0" class="btn btn-sm btn-outline-danger rounded-pill hotdeal_update">
                                        <i class="fe-thumbs-down me-1"></i> Remove Deal
                                    </button>
                                </li>
                                <div class="vr mx-1 d-none d-lg-block"></div>
                                <li>
                                    <button data-url="{{ route('products.update_status') }}" data-status="1" class="btn btn-sm btn-primary rounded-pill update_status">
                                        <i class="fe-check me-1"></i> Active Selected
                                    </button>
                                </li>
                                <li>
                                    <button data-url="{{ route('products.update_status') }}" data-status="0" class="btn btn-sm btn-light border rounded-pill update_status">
                                        <i class="fe-x me-1"></i> Inactive Selected
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="col-lg-4 col-md-5 mt-2 mt-md-0">
                            <form method="GET" action="{{ route('inhouse.products.index') }}">
                                <div class="input-group">
                                    <input type="text" name="keyword" class="form-control form-control-sm border-end-0" placeholder="Search by name..." value="{{ request('keyword') }}">
                                    <button class="btn btn-sm btn-info border-start-0 px-3" type="submit">
                                        <i class="fe-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input checkall" id="parentCheck">
                                        </div>
                                    </th>
                                    <th>SL</th>
                                    <th>Image</th>
                                    <th style="width: 250px;">Product Info</th>
                                    <th>Category</th>
                                    <th>Price & Stock</th>
                                    <th>Features</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $key=>$value)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input checkbox" value="{{$value->id}}">
                                        </div>
                                    </td>
                                    <td>{{ $data->firstItem() + $key }}</td>
                                    
                                    <td>
                                        <img src="{{ asset($value->image ? $value->image->image : 'storage/uploads/placeholder.png') }}" 
                                             class="product-img shadow-sm" alt="product" width="55" height="55">
                                    </td>

                                    <td>
                                        <h5 class="product-title">{{ Str::limit($value->name, 40) }}</h5>
                                        @php
                                            $isDigital = (isset($value->is_digital) && $value->is_digital) || (isset($value->product_type) && $value->product_type === 'digital');
                                        @endphp
                                        <span class="badge {{ $isDigital ? 'badge-soft-primary' : 'badge-soft-info' }} mt-1 font-size-10">
                                            <i class="{{ $isDigital ? 'fe-file-text' : 'fe-box' }} me-1"></i>{{ $isDigital ? 'Digital' : 'Physical' }}
                                        </span>
                                    </td>

                                    <td>
                                        <p class="m-0 fw-bold text-muted font-size-12">{{$value->category ? $value->category->name : 'Uncategorized'}}</p>
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark">৳{{ number_format($value->new_price, 2) }}</div>
                                        <small class="text-muted text-small">Stock: <span class="{{ $value->stock <= 5 ? 'text-danger fw-bold' : 'text-success' }}">{{$value->stock}}</span></small>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            @if($value->topsale==1)
                                            <span class="badge badge-soft-warning font-size-10 text-start">
                                                <i class="fe-zap me-1"></i>Hot Deal
                                            </span>
                                            @endif
                                            @if($value->feature_product==1)
                                            <span class="badge badge-soft-success font-size-10 text-start">
                                                <i class="fe-star me-1"></i>Featured
                                            </span>
                                            @endif
                                            @if($value->topsale!=1 && $value->feature_product!=1)
                                                <span class="text-muted text-small">-</span>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        @if($value->status==1)
                                            <span class="badge badge-soft-success px-2 py-1">Active</span>
                                        @else
                                            <span class="badge badge-soft-danger px-2 py-1">Inactive</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            {{-- Status Toggle --}}
                                            @if($value->status == 1)
                                                <form method="post" action="{{route('products.inactive')}}" class="d-inline"> 
                                                    @csrf
                                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                                    <button type="submit" class="btn-action btn-status-toggle" title="Deactivate"><i class="fe-thumbs-down"></i></button>
                                                </form>
                                            @else
                                                <form method="post" action="{{route('products.active')}}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                                    <button type="submit" class="btn-action btn-status-active" title="Activate"><i class="fe-thumbs-up"></i></button>
                                                </form>
                                            @endif

                                            {{-- Edit --}}
                                            <a href="{{route('products.edit',$value->id)}}" class="btn-action btn-edit" title="Edit">
                                                <i class="fe-edit"></i>
                                            </a>

                                            {{-- Delete --}}
                                            <form method="post" action="{{route('products.destroy')}}" class="d-inline" onsubmit="return confirm('Are you sure?');">        
                                                @csrf
                                                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                                                <button type="submit" class="btn-action btn-delete" title="Delete">
                                                    <i class="fe-trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fe-search font-size-24 d-block mb-2"></i>
                                            No products found!
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap">
                        <div class="text-muted font-size-13 mb-2 mb-md-0">
                            Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} results
                        </div>
                        <div class="custom-paginate">
                            {{$data->links('pagination::bootstrap-4')}}
                        </div>
                    </div>
                </div> 
            </div> 
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function(){
    // Select all checkboxes
    $(".checkall").on('change', function(){
        $(".checkbox").prop('checked', $(this).is(":checked"));
    });

    function getCheckedIds() {
        return $('input.checkbox:checked').map(function(){ return $(this).val(); }).get();
    }

    function sendBulkRequest(url, status) {
        var ids = getCheckedIds();
        if(ids.length === 0){
            if (typeof toastr !== 'undefined') {
                toastr.error('Please select at least one product!');
            } else {
                alert('Please select at least one product!');
            }
            return;
        }

        var token = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: url,
            type: 'POST',
            data: JSON.stringify({ product_ids: ids, status: status }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': token },
            success: function(res){
                if(res.status === 'success'){
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.message);
                    }
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(res.message || 'Action failed');
                    }
                }
            },
            error: function(xhr){
                let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server Error';
                if (typeof toastr !== 'undefined') { toastr.error(msg); } else { alert(msg); }
            }
        });
    }

    // Handle Bulk Clicks
    $(document).on('click', '.hotdeal_update, .update_status', function(e){
        e.preventDefault();
        var url = $(this).data('url');
        var status = $(this).data('status');
        if(url) sendBulkRequest(url, status);
    });
});
</script>
@endsection