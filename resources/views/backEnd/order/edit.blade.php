@extends('backEnd.layouts.master') 
@section('title','Order Create') 
@section('css')
<style>
    .increment_btn, .remove_btn {
        margin-top: -17px;
        margin-bottom: 10px;
    }
    .payment-box {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-top: 10px;
    }
    .payment-box h6 {
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
    }
</style>
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
@endsection 

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <form method="get" action="{{route('admin.order.cart_clear')}}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger rounded-pill delete-confirm" title="{{ __('Delete') }}">
                            <i class="fas fa-trash-alt"></i> Cart Clear
                        </button>
                    </form>
                </div>
                <h4 class="page-title"> {{ __('Order Create') }} </h4>
            </div>
        </div>
    </div>

    <!-- Order Create Form -->
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.order.update')}}" method="POST" class="row pos_form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" value="{{$order->id}}" name="order_id">

                        <!-- Product Select -->
                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label class="form-label"> {{ __('Products *') }} </label>
                                <select id="cart_add" class="form-control select2">
                                    <option value=""> {{ __('Select..') }} </option>
                                    @foreach($products as $value)
                                        <option value="{{$value->id}}">{{$value->name}}</option> 
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Product Cart Table -->
                        <div class="col-sm-12">
                            <table class="table table-bordered table-responsive-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('Image') }}</th>
                                        <th>{{ __('Product') }}</th>
                                        <th>{{ __('Qty') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Sub Total') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="cartTable">
                                    {{-- ✅ Use the full cart_table_rows partial so batch & warranty dropdowns show on page load --}}
                                    @include('backEnd.order.cart_table_rows')
                                </tbody>
                            </table>
                        </div>

                        <!-- Customer Info -->
                        <div class="col-sm-6">
                            <div class="row">
                                <div class="col-sm-12 mb-2">
                                    <input type="text" id="name" class="form-control" placeholder="Customer Name" name="name" value="{{$shippinginfo->name}}" required>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <input type="number" id="phone" class="form-control" placeholder="Customer Number" name="phone" value="{{$shippinginfo->phone}}" required>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <input type="text" id="address" class="form-control" placeholder="{{ __('Address') }}" name="address" value="{{$shippinginfo->address}}" required>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <select id="area" class="form-control" name="area" required>
                                        <option value=""> {{ __('Delivery Area') }} </option>
                                        {{-- ✅ Default 0 TK shipping (admin only) --}}
                                        <option value="0" @if($shippinginfo->area == 'Store Pickup') selected @endif>Store Pickup (৳0)</option>
                                        @foreach($shippingcharge as $key=>$value)
                                            <option value="{{$value->id}}" @if($shippinginfo->area == $value->name) selected @endif>{{$value->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Cart Summary -->
<div class="col-sm-6">
    <table class="table table-bordered">
        <tbody id="cart_details">
            @php
                // ✅ Calculate product_discount from cart (replaces old inline foreach)
                $productDisc = 0;
                foreach($cartinfo as $item) {
                    $productDisc += (float)($item->options->product_discount ?? 0) * $item->qty;
                }
                Session::put('product_discount', $productDisc);

                // আগের মতই cart total হিসাব
                $subtotal = Cart::instance('pos_shopping')->subtotal();
                $subtotal = str_replace([',','.00'], '', $subtotal);
                $shipping = Session::get('pos_shipping');
                $total_discount = Session::get('pos_discount') + Session::get('product_discount');

                $total = ($subtotal + $shipping) - $total_discount;

                // 💳 এই অর্ডারের পেমেন্ট থেকে কত টাকা নেয়া হয়েছে (advance / full)
                $paidAmount = \App\Models\Payment::where('order_id', $order->id)->sum('amount');

                // ডিফল্ট: মনে করি advance নাই
                $advancePaid = 0;
                $dueAmount    = $total;

                // যদি কিছু payment থাকে এবং সেটা total থেকে কম হয় = advance payment
                if ($paidAmount > 0 && $paidAmount < $total) {
                    $advancePaid = $paidAmount;
                    $dueAmount   = $total - $advancePaid;
                }

                // যদি paidAmount == total হয় → ফুল পেমেন্ট, তখন advance দেখাব না, আগের মতই total থাকবে
            @endphp

            <tr>
                <td> {{ __('Sub Total') }} </td>
                <td>{{ $subtotal }}</td>
            </tr>
            <tr>
                <td> {{ __('Shipping Fee') }} </td>
                <td>{{ $shipping }}</td>
            </tr>
            <tr>
                <td>{{ __('Discount') }}</td>
                <td>{{ $total_discount }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('Total') }}</strong></td>
                <td><strong>{{ $total }}</strong></td>
            </tr>

            {{-- 🔥 যদি advance payment থাকে তখনই extra দুইটা রো দেখাব --}}
            @if($advancePaid > 0)
                <tr>
                    <td><strong> {{ __('Advance Paid') }} </strong></td>
                    <td><strong>{{ number_format($advancePaid, 2) }}</strong></td>
                </tr>
                <tr>
                    <td><strong> {{ __('Due Amount') }} </strong></td>
                    <td><strong>{{ number_format($dueAmount, 2) }}</strong></td>
                </tr>
            @endif
        </tbody>
    </table>
</div>


                        <!-- ✅ Full Width Payment Info Section -->
                        <div class="col-sm-12 mt-3">
                            <div class="payment-box w-100">
                                <h6><i class="fa fa-credit-card"></i> {{ __('Payment Info') }} </h6>
                                <div class="row">
                                    <!-- Gateway -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"> {{ __('Payment Gateway') }} </label>
                                        <input type="text" class="form-control" value="{{ $order->payment ? strtoupper($order->payment->payment_method) : 'N/A' }}" readonly>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"> {{ __('Payment Status') }} </label>
                                        <div class="input-group">
                                            <select id="payment_status_{{ $order->id }}" class="form-select">
                                                <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                                <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}> {{ __('Paid') }} </option>
                                                <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}> {{ __('Unpaid') }} </option>
                                                <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                                            </select>
                                            <button type="button" class="btn btn-success" onclick="updatePaymentStatus({{ $order->id }})">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ✅ END -->

                        <!-- Submit -->
                        <div class="col-12 text-end mt-3">
                            <input type="submit" class="btn btn-success px-4" value="Update Order" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Toastr + JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

<script>
function updatePaymentStatus(orderId) {
    let status = document.getElementById('payment_status_' + orderId).value;

    fetch('{{ route("admin.order.updatePaymentStatus") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order_id: orderId, payment_status: status })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success(data.message, 'Success!');
        } else {
            toastr.error(data.message, 'Error!');
        }
    })
    .catch(err => {
        toastr.error('Something went wrong!', 'Error!');
    });
}
</script>
@endsection
@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-validation.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-advanced.init.js"></script>
<!-- Plugins js -->
<script src="{{asset('public/backEnd/')}}/assets/libs//summernote/summernote-lite.min.js"></script>
<script>
    $(".summernote").summernote({
        placeholder: "Enter Your Text Here",
    });
</script>

<script type="text/javascript">
    $(document).ready(function () {
        $('.select2').select2();
    });
</script>
<script>
    // ✅ Single AJAX refresh — updates cart table + totals in one call
    function cart_refresh(){
           $.ajax({
             type:"GET",
             url:"{{route('admin.order.cart_refresh')}}",
             dataType: "json",
             success: function(res){
               $('#cartTable').html(res.cart_html);
               $('#cart_details').html(res.details_html);
             }
          });
      }

      $('#cart_add').on('change',function(e){
       var id =$(this).val();
        if(id){
            $.ajax({
            cache: 'false',
            type:"GET",
            data:{'id':id},
            url:"{{route('admin.order.cart_add')}}",
            dataType: "json",
            success: function(cartinfo){
                return cart_refresh();
            }
            });
        }
       });
    $(".cart_increment").click(function(e){
        e.preventDefault();
        var id = $(this).data("id");
        var qty = $(this).val();
        if(id){
              $.ajax({
               cache: false,
               data:{'id':id,'qty':qty},
               type:"GET",
               url:"{{route('admin.order.cart_increment')}}",
               dataType: "json",
            success: function(cartinfo){
                return cart_refresh();
            }
          });
        }
   });
    $(".cart_decrement").click(function(e){
        e.preventDefault();
        var id = $(this).data("id");
        var qty = $(this).val();
        if(id){
              $.ajax({
               cache: false, 
               type:"GET",
               data:{'id':id,'qty':qty},
               url:"{{route('admin.order.cart_decrement')}}",
               dataType: "json",
            success: function(cartinfo){
                return cart_refresh();
            }
          });
        }
   });
    $(".cart_remove").click(function(e){
        e.preventDefault();
        var id = $(this).data("id");
        if(id){
              $.ajax({
               cache: false,
               type:"GET",
               data:{'id':id},
               url:"{{route('admin.order.cart_remove')}}",
               dataType: "json",
              success: function(cartinfo){
                return cart_refresh();
            }
          });
        }
   });
   $(".product_discount").change(function(){
        var id = $(this).data("id");
        var discount = $(this).val();
          $.ajax({
           cache: false,
           type:"GET",
           data:{'id':id,'discount':discount},
           url:"{{route('admin.order.product_discount')}}",
           dataType: "json",
          success: function(cartinfo){
            return cart_refresh();
          }
        });
   });
    $(".cartclear").click(function(e){
      $.ajax({
           cache: false,
           type:"GET",
           url:"{{route('admin.order.cart_clear')}}",
           dataType: "json",
          success: function(cartinfo){
            return cart_refresh();
          }
       });
   });// pshippingfee from total
    $("#area").on("change", function () {
        var id = $(this).val();
        $.ajax({
            type: "GET",
            data: { id: id },
            url: "{{route('admin.order.cart_shipping')}}",
            dataType: "html",
            success: function(cartinfo){
               return cart_refresh();
            }
        });
    });
// Event listener for size selector change (delegated)
$(document).on("change", ".cart-size-selector", function(){
    var rowId = $(this).data('id');
    var productId = $(this).data('product-id');
    var sizeId = $(this).val();
     $.ajax({
           cache: false,
           type:"GET",
           data:{id:rowId, product_id:productId, size_id:sizeId},
           url:"{{ route('admin.order.cart.update') }}",
           dataType: "json",
            success: function(cartinfo){
            return cart_refresh();
          }
        });
});


// Event listener for color selector change (delegated)
$(document).on("change", ".cart-color-selector", function(){
    var rowId = $(this).data('id');
    var productId = $(this).data('product-id');
    var colorId = $(this).val();
    $.ajax({
           cache: false,
           type:"GET",
           data:{id:rowId, product_id:productId, color_id:colorId},
           url:"{{ route('admin.order.cart.update') }}",
           dataType: "json",
            success: function(cartinfo){
            return cart_refresh();
          }
        });
});

// ✅ Warranty selector change (delegated — works on initial load + AJAX refresh)
$(document).on("change", ".cart-warranty-selector", function(){
    var rowId = $(this).data('id');
    var productId = $(this).data('product-id');
    var warrantyId = $(this).val();
    $.ajax({
       cache: false, type:"GET",
       data:{id:rowId, product_id:productId, warranty_tier_id:warrantyId},
       url:"{{ route('admin.order.cart.update') }}",
       dataType: "json",
       success: function(){ cart_refresh(); }
    });
});

// ✅ Batch selector change (delegated — works on initial load + AJAX refresh)
$(document).on("change", ".cart-batch-selector", function(){
    var rowId = $(this).data('id');
    var productId = $(this).data('product-id');
    var batchId = $(this).val();
    $.ajax({
       cache: false, type:"GET",
       data:{id:rowId, product_id:productId, batch_id:batchId},
       url:"{{ route('admin.order.cart.update') }}",
       dataType: "json",
       success: function(){ cart_refresh(); }
    });
});

// ✅ Serial Numbers input (auto-save on blur/change)
$(document).on("change blur", ".cart-sn-input", function(){
    var rowId = $(this).data('id');
    var productId = $(this).data('product-id');
    var sn = $(this).val();
    $.ajax({
       cache: false, type:"GET",
       data:{id:rowId, product_id:productId, serial_numbers:sn},
       url:"{{ route('admin.order.cart.update') }}",
       dataType: "json",
       success: function(){ cart_refresh(); }
    });
});
</script>
@endsection

