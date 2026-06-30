@extends('backEnd.layouts.master') 
@section('title','{{ __('Order Create') }}') 
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
    <!-- {{ __('Page {{ __('Title') }}') }} -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <form method="get" action="{{route('admin.order.cart_clear')}}" class="d-inline">
                        @csrf
                        <button type="{{ __('submit') }}" class="btn btn-danger rounded-pill delete-confirm" title="{{ __('Delete') }}">
                            <i class="fas fa-trash-alt"></i> {{ __('Cart') }} Clear
                        </button>
                    </form>
                </div>
                <h4 class="page-title">{{ __('Order Create') }}</h4>
            </div>
        </div>
    </div>

    <!-- {{ __('Order Create') }} Form -->
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.order.update')}}" method={{ __('"{{ __('POST') }}"') }} class="row pos_form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" value="{{$order->id}}" name="order_id">

                        <!-- {{ __('Product') }} Select -->
                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('{{ __('Product') }}s *') }}</label>
                                <select id="cart_add" class="form-control select2">
                                    <option value="">{{ __('Select..') }}</option>
                                    @foreach($products as $value)
                                        <option value="{{$value->id}}">{{$value->name}}</option> 
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- {{ __('Product') }} {{ __('Cart') }} Table -->
                        <div class="col-sm-12">
                            <table class="table table-bordered table-responsive-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('Image') }}</th>
                                        <th>{{ __('{{ __('Name') }}') }}</th>
										  <th>{{ __('{{ __('Color') }}') }}</th>
										  <th>{{ __('Size') }}</th>
                                        <th>{{ __('Qty') }}</th>
                                        <th>{{ __('Sell Price') }}</th>
                                        <th>{{ __('Discount') }}</th>
                                        <th>{{ __('Sub {{ __('Total') }}') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="cartTable">
                                    @php $product_discount = 0; @endphp
                                    @foreach($cartinfo as $key=>$value)
                                    <tr>
                                        <td><img height="30" src="{{asset($value->options->image)}}"></td>
                                        <td>{{$value->name}}</td>
										<td>{{ $value->options->product_color_name ?? '{{ __('N/A') }}' }}</td>
<td>{{ $value->options->product_size_name ?? '{{ __('N/A') }}' }}</td>
                                        <td>
                                            <div class="quantity">
                 
                                                <input type="text" value="{{$value->qty}}" readonly />
                                            </div>
                                        </td>
                                        <td>{{$value->price}}</td>
                                        <td><input type="{{ __('number') }}" class="product_discount" value="{{$value->options->product_discount}}" data-id="{{$value->rowId}}"></td>
                                        <td>{{($value->price - $value->options->product_discount)*$value->qty}}</td>
                                        <td><button type="button" class="btn btn-danger btn-xs cart_remove" data-id="{{$value->rowId}}"><i class="fa fa-times"></i></button></td>
                                    </tr>
                                    @php
                                        $product_discount += $value->options->product_discount*$value->qty;
                                        {{ __('Session') }}::put('product_discount',$product_discount);
                                    @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- {{ __('{{ __('Customer') }} Info') }} -->
                        <div class="col-sm-6">
                            <div class="row">
                                <div class="col-sm-12 mb-2">
                                    <input type="text" id="name" class="form-control" placeholder="{{ __('{{ __('Customer') }} {{ __('Name') }}') }}" name="name" value="{{$shippinginfo->name}}" required>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <input type="{{ __('number') }}" id="{{ __('phone') }}" class="form-control" placeholder="{{ __('{{ __('Customer') }} Number') }}" name="{{ __('phone') }}" value="{{$shippinginfo->{{ __('phone') }}}}" required>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <input type="text" id="address" class="form-control" placeholder="{{ __('Address') }}" name="address" value="{{$shippinginfo->address}}" required>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <select id="area" class="form-control" name="area" required>
                                        <option value="">{{ __('Delivery {{ __('Area') }}') }}</option>
                                        @foreach($shippingcharge as $key=>$value)
                                            <option value="{{$value->id}}" @if($shippinginfo->area == $value->name) selected @endif>{{$value->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- {{ __('{{ __('Cart') }} {{ __('Summary') }}') }} -->
<div class="col-sm-6">
    <table class="table table-bordered">
        <tbody id="cart_details">
            @php
                // আগের মতই cart {{ __('total') }} হিসাব
                $sub{{ __('total') }} = {{ __('Cart') }}::instance('pos_shopping')->sub{{ __('total') }}();
                $sub{{ __('total') }} = str_replace([',','.00'], '', $sub{{ __('total') }});
                $shipping = {{ __('Session') }}::get('pos_shipping');
                $total_discount = {{ __('Session') }}::get('pos_discount') + {{ __('Session') }}::get('product_discount');

                $total = ($sub{{ __('total') }} + $shipping) - $total_discount;

                // 💳 {{ __('Orders') }}র {{ __('bn_f0a1817c') }} থেকে কত টাকা নেয়া হয়েছে (advance / full)
                $paid{{ __('Amount') }} = \App\Models\Payment::w{{ __('here') }}('order_id', $order->{{ __('id)') }}->sum('amount');

                // ডিফল্ট: মনে করি advance নাই
                $advance{{ __('Paid') }} = 0;
                $due{{ __('Amount') }}    = $total;

                // যদি কিছু payment থাকে এবং সেটা {{ __('total') }} থেকে কম হয় = advance payment
                if ($paid{{ __('Amount') }} > 0 && $paid{{ __('Amount') }} < $total) {
                    $advance{{ __('Paid') }} = $paid{{ __('Amount') }};
                    $due{{ __('Amount') }}   = $total - $advance{{ __('Paid') }};
                }

                // যদি paid{{ __('Amount') }} == {{ __('total') }} হয় → ফুল {{ __('bn_f0a1817c') }}, তখন advance দেখাব না, আগের মতই {{ __('total') }} থাকবে
            @endphp

            <tr>
                <td>{{ __('Sub {{ __('Total') }}') }}</td>
                <td>{{ $sub{{ __('total') }} }}</td>
            </tr>
            <tr>
                <td>{{ __('{{ __('Shipping') }} Fee') }}</td>
                <td>{{ $shipping }}</td>
            </tr>
            <tr>
                <td>{{ __('Discount') }}</td>
                <td>{{ $total_discount }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('{{ __('Total') }}') }}</strong></td>
                <td><strong>{{ $total }}</strong></td>
            </tr>

            {{-- 🔥 যদি advance payment থাকে তখনই extra দুইটা রো দেখাব --}}
            @if($advance{{ __('Paid') }} > 0)
                <tr>
                    <td><strong>{{ __('Advance {{ __('Paid') }}') }}</strong></td>
                    <td><strong>{{ number_format($advance{{ __('Paid') }}, 2) }}</strong></td>
                </tr>
                <tr>
                    <td><strong>{{ __('{{ __('Due') }} {{ __('Amount') }}') }}</strong></td>
                    <td><strong>{{ number_format($due{{ __('Amount') }}, 2) }}</strong></td>
                </tr>
            @endif
        </tbody>
    </table>
</div>


                        <!-- ✅ {{ __('Full {{ __('Width') }}') }} {{ __('Payment Info') }} Section -->
                        <div class="col-sm-12 mt-3">
                            <div class="payment-box w-100">
                                <h6><i class="fa fa-credit-card"></i> {{ __('Payment Info') }}</h6>
                                <div class="row">
                                    <!-- Gateway -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('Payment Gateway') }}</label>
                                        <input type="text" class="form-control" value="{{ ucfirst($order->payment_gateway ?? '{{ __('N/A') }}') }}" readonly>
                                    </div>

                                    <!-- {{ __('Status') }} -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('Payment {{ __('Status') }}') }}</label>
                                        <div class="input-group">
                                            <select id="payment_status_{{ $order->id }}" class="form-select">
                                                <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                                <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                                                <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
                                                <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                                            </select>
                                            <button type="button" class="btn btn-success" onclick="updatePayment{{ __('Status') }}({{ $order->id }})">
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
                            <input type="{{ __('submit') }}" class="btn btn-success px-4" value="Update Order" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Toastr + JS -->
<script src="{{ __('https://') }}cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link href="{{ __('https://') }}cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

<script>
function updatePayment{{ __('Status') }}(orderId) {
    let status = document.getElementById('payment_status_' + orderId).value;

    fetch('{{ route("admin.order.updatePayment{{ __('Status') }}") }}', {
        method: '{{ __('POST') }}',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order_id: orderId, payment_status: status })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success(data.{{ __('message') }}, 'Success!');
        } else {
            toastr.error(data.{{ __('message') }}, '{{ __('Error!') }}');
        }
    })
    .catch(err => {
        toastr.error('Something went wrong!', '{{ __('Error!') }}');
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
        placeholder: "Enter Your {{ __('Text') }} Here",
    });
</script>

<script type="text/javascript">
    $(document).ready(function () {
        $('.select2').select2();
    });
</script>
<script>
    function cart_content(){
           $.ajax({
             type:"{{ __('GET') }}",
             url:"{{route('admin.order.cart_content')}}",
             dataType: "html",
             success: function(cartinfo){
               $('#cartTable').html(cartinfo)
             }
          });
      }
      function cart_details(){
           $.ajax({
             type:"{{ __('GET') }}",
             url:"{{route('admin.order.cart_details')}}",
             dataType: "html",
             success: function(cartinfo){
               $('#cart_details').html(cartinfo)
             }
          });
      }

      $('#cart_add').on('change',function(e){
       var id =$(this).val();
        if({{ __('id)') }}{
            $.ajax({
            cache: 'false',
            type:"{{ __('GET') }}",
            data:{'id':id},
            url:"{{route('admin.order.cart_add')}}",
            dataType: "json",
            success: function(cartinfo){
                return cart_content()+cart_details();
            }
            });
        }
       });
    $(".cart_increment").click(function(e){
        e.preventDefault();
        var id = $(this).data("id");
        var qty = $(this).val();
        if({{ __('id)') }}{
              $.ajax({
               cache: false,
               data:{'id':id,'qty':qty},
               type:"{{ __('GET') }}",
               url:"{{route('admin.order.cart_increment')}}",
               dataType: "json",
            success: function(cartinfo){
                return cart_content()+cart_details();
            }
          });
        }
   });
    $(".cart_decrement").click(function(e){
        e.preventDefault();
        var id = $(this).data("id");
        var qty = $(this).val();
        if({{ __('id)') }}{
              $.ajax({
               cache: false, 
               type:"{{ __('GET') }}",
               data:{'id':id,'qty':qty},
               url:"{{route('admin.order.cart_decrement')}}",
               dataType: "json",
            success: function(cartinfo){
                return cart_content()+cart_details();
            }
          });
        }
   });
    $(".cart_remove").click(function(e){
        e.preventDefault();
        var id = $(this).data("id");
        if({{ __('id)') }}{
              $.ajax({
               cache: false,
               type:"{{ __('GET') }}",
               data:{'id':id},
               url:"{{route('admin.order.cart_remove')}}",
               dataType: "json",
              success: function(cartinfo){
                return cart_content()+cart_details();
            }
          });
        }
   });
   $(".product_discount").change(function(){
        var id = $(this).data("id");
        var discount = $(this).val();
          $.ajax({
           cache: false,
           type:"{{ __('GET') }}",
           data:{'id':id,'discount':discount},
           url:"{{route('admin.order.product_discount')}}",
           dataType: "json",
          success: function(cartinfo){
            return cart_content()+cart_details();
          }
        });
   });
    $(".cartclear").click(function(e){
      $.ajax({
           cache: false,
           type:"{{ __('GET') }}",
           url:"{{route('admin.order.cart_clear')}}",
           dataType: "json",
          success: function(cartinfo){
            return cart_content()+cart_details();
          }
       });
   });// pshippingfee from {{ __('total') }}
    $("#area").on("change", function () {
        var id = $(this).val();
        $.ajax({
            type: "{{ __('GET') }}",
            data: { id: id },
            url: "{{route('admin.order.cart_shipping')}}",
            dataType: "html",
            success: function(cartinfo){
               return cart_content()+cart_details();
            }
        });
    });
// Event listener for size selector change
$('.cart-size-selector').on('change', function() {
    var rowId = $(this).data('id'); // Get the row ID
    var selectedSize = $(this).val(); // Get the selected size
     $.ajax({
           cache: false,
           type:"{{ __('GET') }}",
           data:{'id':rowId,'product_size':selectedSize},
           url:"{{ route('admin.order.cart.update') }}",
           dataType: "json",
            success: function(cartinfo){
            return cart_content()+cart_details();
          }
        });

});


// Event listener for color selector change
$('.cart-color-selector').on('change', function() {
    var rowId = $(this).data('id'); // Get the row ID
    var selected{{ __('Color') }} = $(this).val(); // Get the selected color
    $.ajax({
           cache: false,
           type:"{{ __('GET') }}",
           data:{'id':rowId,'product_color':selected{{ __('Color') }}},
           url:"{{ route('admin.order.cart.update') }}",
           dataType: "json",
            success: function(cartinfo){
            return cart_content()+cart_details();
          }
        });

});
</script>
@endsection

