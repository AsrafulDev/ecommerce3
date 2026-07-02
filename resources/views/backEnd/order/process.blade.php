@extends('backEnd.layouts.master')
@section('title','Order Process')
@section('css')
<style>
    .increment_btn, .remove_btn {
        margin-top: -17px;
        margin-bottom: 10px;
    }
    .payment-box {
        background: #f8f9fa;
        border: 1px solid #e1e1e1;
        border-radius: 8px;
        padding: 20px;
        margin-top: 10px;
    }
    .payment-label {
        font-weight: 600;
        color: #333;
    }
    .payment-value {
        font-weight: 500;
        color: #007bff;
    }
</style>
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Order Process [Invoice : #{{$data->invoice_id}}]</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 

  <table class="table table-bordered align-middle">
    <thead class="bg-light">
        <tr>
            <th>{{ __('SL') }}</th>
            <th>{{ __('Image') }}</th>
            <th> {{ __('Product') }} </th>
            <th>{{ __('Color') }}</th>
            <th>{{ __('Size') }}</th>
            <th>{{ __('Price') }}</th>
            <th>{{ __('Qty') }}</th>
            <th>{{ __('Total') }}</th>
        </tr>
    </thead>
    <tbody>
        @php
            // Normal order: show sale_price (main price)
            $displayPrice = 0;
        @endphp
        @foreach($data->orderdetails as $key => $product)
        @php
            $displayPrice = $product->sale_price;
        @endphp
        <tr>
            <td>{{ $key + 1 }}</td>

            {{-- ✅ Product Image --}}
            <td>
                <img src="{{ asset($product->image->image ?? 'public/no-image.png') }}"
                     height="50" width="50" alt="Product Image">
            </td>

            {{-- ✅ Product Name --}}
            <td>{{ $product->product_name }}</td>

<td>{{ ($product->color && $product->color->name) ? $product->color->name : ($product->product_color ?: 'N/A') }}</td>
@php
    $sizeDisplay = 'N/A';
    if ($product->size) {
        $sizeDisplay = $product->size->sizeName ?? $product->size->size_name ?? $product->size->name ?? 'N/A';
    } elseif ($product->product_size) {
        // If product_size is an ID, fetch the Size model
        $s = \App\Models\Size::find($product->product_size);
        if ($s) {
            $sizeDisplay = $s->sizeName ?? $s->size_name ?? 'N/A';
        } elseif (!is_numeric($product->product_size)) {
            // If it's not numeric, it might be a direct size name string
            $sizeDisplay = $product->product_size;
        }
    }
@endphp
<td>{{ $sizeDisplay }}</td>
<td>৳{{ number_format($displayPrice, 2) }}</td>
<td>{{ $product->qty }}</td>
<td>৳{{ number_format($displayPrice * $product->qty, 2) }}</td>

        </tr>
        @endforeach
    </tbody>
</table>


        <div class="card">
            <div class="card-body">
               <form action="{{route('admin.order_change')}}" method="POST" class="row" data-parsley-validate="" name="editForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" value="{{$data->id}}">
                    
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label"> {{ __('Customer name') }} </label>
                            <input type="text" class="form-control" name="name" value="{{$data->shipping?$data->shipping->name:''}}" placeholder="Customer Name">
                        </div>
                    </div>
                            
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="phone" class="form-label"> {{ __('Customer Phone') }} </label>
                            <input type="text" class="form-control" name="phone" value="{{$data->shipping?$data->shipping->phone:''}}" placeholder="Phone Number">
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="address" class="form-label"> {{ __('Customer Address') }} </label>
                            <textarea name="address" class="form-control">{{$data->shipping?$data->shipping->address:''}}</textarea>
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="area" class="form-label"> {{ __('Delivery Area *') }} </label>
                            <select id="area" class="form-control" name="area" required>
                                @foreach($shippingcharge as $key=>$value)
                                    <option @if($data->shipping?$data->shipping->area:'' == $value->name) selected @endif value="{{$value->id}}">
                                        {{$value->name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- ✅ Payment Gateway + Status Section -->
                    @php
                        $paymentInfo = $data->payment;
                        $gatewayLabel = $paymentInfo->payment_method ?? 'N/A';
                        $payStatus = $paymentInfo->payment_status ?? $data->payment_status ?? 'pending';
                    @endphp

                    <div class="col-sm-12">
                        <div class="payment-box">
                            <h5 class="mb-3"><i class="fa fa-credit-card"></i> {{ __('Payment Information') }} </h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="payment-label">Payment Gateway:</label><br>
                                    <span class="payment-value">
                                        {{ strtoupper($gatewayLabel) }}
                                    </span>
                                </div>

                                <div class="col-md-6">
                                    <label class="payment-label">Payment Status:</label>
                                    <div class="d-flex align-items-center">
                                        <select id="payment_status_{{ $data->id }}" class="form-select form-select-sm w-auto">
                                            <option value="pending" {{ strtolower($payStatus) == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                            <option value="paid" {{ strtolower($payStatus) == 'paid' ? 'selected' : '' }}> {{ __('Paid') }} </option>
                                            <option value="unpaid" {{ strtolower($payStatus) == 'unpaid' ? 'selected' : '' }}> {{ __('Unpaid') }} </option>
                                            <option value="failed" {{ strtolower($payStatus) == 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ✅ END -->

                    <!-- ✅ Order Amount Section -->
                    @php
                        // Normal customer order: calculate from sale_price
                        $subtotal = $data->orderdetails->sum(function($item) {
                            return $item->sale_price * $item->qty;
                        });
                        
                        $shipping = $data->shipping_charge ?? 0;
                        $discount = $data->discount ?? 0;
                        $finalTotal = $data->amount;
                    @endphp

                    <div class="col-sm-12 mt-3">
                        <div class="payment-box">
                            <h5 class="mb-3"><i class="fa fa-money-bill-wave"></i> {{ __('Order Amount Information') }} </h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="payment-label">Subtotal:</label>
                                    <span class="payment-value">৳{{ number_format($subtotal, 2) }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="payment-label">Shipping:</label>
                                    <span class="payment-value">৳{{ number_format($shipping, 2) }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="payment-label">Discount:</label>
                                    <span class="payment-value">৳{{ number_format($discount, 2) }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="payment-label"><strong>Final Total:</strong></label>
                                    <span class="payment-value" style="font-size: 18px; color: #28a745;"><strong>৳{{ number_format($finalTotal, 2) }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ✅ END Order Amount Section -->

                    <div class="col-sm-12 mt-3">
                        <div class="form-group mb-3">
                            <label for="category_id" class="form-label">{{ __('Order Status') }}</label>
                            <select class="form-control select2-multiple" name="status" data-toggle="select2" required>
                                <option value=""> {{ __('Select..') }} </option>
                                @foreach($orderstatus as $value)
                                    <option value="{{$value->slug}}" @if($data->order_status==$value->slug || $data->order_status==$value->id) selected @endif>{{$value->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-sm-12 text-end">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="fa fa-save"></i> Update Order
                        </button>
                    </div>
                </form>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
   </div>
</div>

<!-- ✅ Toastr Notification -->
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
