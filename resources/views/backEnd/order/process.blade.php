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
                <h4 class="page-title">Order Process [{{ __('{{ __('Inv') }}oice') }} : #{{$data->invoice_id}}]</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 

  <table class="table table-bordered align-middle">
    <thead class="bg-light">
        <tr>
            <th>{{ __('SL') }}</th>
            <th>{{ __('Image') }}</th>
            <th>{{ __('Product') }}</th>
            <th>{{ __('Color') }}</th>
            <th>{{ __('Size') }}</th>
            <th>{{ __('Price') }}</th>
            <th>{{ __('Qty') }}</th>
            <th>{{ __('{{ __('Total') }}') }}</th>
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

            {{-- ✅ {{ __('Product') }} Image --}}
            <td>
                <img src="{{ asset($product->image->image ?? 'public/no-image.png') }}"
                     height="50" width="50" alt="{{ __('Product') }} Image">
            </td>

            {{-- ✅ {{ __('{{ __('Product') }} {{ __('Name') }}') }} --}}
            <td>{{ $product->product_name }}</td>

<td>{{ ($product->color && $product->color->name) ? $product->color->name : ($product->product_color ?: '{{ __('N/A') }}') }}</td>
@php
    $sizeDisplay = '{{ __('N/A') }}';
    if ($product->size) {
        $sizeDisplay = $product->size->size{{ __('Name') }} ?? $product->size->size_name ?? $product->size->name ?? '{{ __('N/A') }}';
    } elseif ($product->product_size) {
        // If product_size is an ID, fetch the Size model
        $s = \App\Models\Size::find($product->product_size);
        if ($s) {
            $sizeDisplay = $s->size{{ __('Name') }} ?? $s->size_name ?? '{{ __('N/A') }}';
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
               <form action="{{route('admin.order_change')}}" method={{ __('"{{ __('POST') }}"') }} class="row" data-parsley-validate="" name="editForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" value="{{$data->id}}">
                    
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">{{ __('{{ __('Customer') }} name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{$data->shipping?$data->shipping->name:''}}" placeholder="{{ __('{{ __('Customer') }} {{ __('Name') }}') }}">
                        </div>
                    </div>
                            
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="{{ __('phone') }}" class="form-label">{{ __('{{ __('Customer') }} {{ __('Phone') }}') }}</label>
                            <input type="text" class="form-control" name="{{ __('phone') }}" value="{{$data->shipping?$data->shipping->{{ __('phone') }}:''}}" placeholder="{{ __('{{ __('Phone') }} Number') }}">
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="address" class="form-label">{{ __('{{ __('Customer') }} Address') }}</label>
                            <textarea name="address" class="form-control">{{$data->shipping?$data->shipping->address:''}}</textarea>
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="area" class="form-label">{{ __('{{ __('Delivery {{ __('Area') }}') }} *') }}</label>
                            <select id="area" class="form-control" name="area" required>
                                @foreach($shippingcharge as $key=>$value)
                                    <option @if($data->shipping?$data->shipping->area:'' == $value->name) selected @endif value="{{$value->id}}">
                                        {{$value->name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- ✅ {{ __('Payment Gateway') }} + {{ __('Status') }} Section -->
                    @php
                        $paymentInfo = DB::table('orders')
                            ->select('payment_gateway', 'payment_status')
                            ->w{{ __('here') }}('id', $data->{{ __('id)') }}
                            ->first();
                    @endphp

                    <div class="col-sm-12">
                        <div class="payment-box">
                            <h5 class="mb-3"><i class="fa fa-credit-card"></i> {{ __('{{ __('Payment Info') }}rmation') }}</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="payment-label">{{ __('Payment Gateway') }}:</label><br>
                                    <span class="payment-value">
                                        @if(!empty($paymentInfo->payment_gateway))
                                            {{ strtoupper($paymentInfo->payment_gateway) }}
                                        @else
                                            <span class="text-danger">{{ __('Not Found') }}</span>
                                        @endif
                                    </span>
                                </div>

                                <div class="col-md-6">
                                    <label class="payment-label">{{ __('Payment {{ __('Status') }}') }}:</label>
                                    <div class="d-flex align-items-center">
                                        <select id="payment_status_{{ $data->id }}" class="form-select form-select-sm w-auto">
                                            <option value="pending" {{ ($paymentInfo->payment_status ?? '') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                            <option value="paid" {{ ($paymentInfo->payment_status ?? '') == 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                                            <option value="unpaid" {{ ($paymentInfo->payment_status ?? '') == 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
                                            <option value="failed" {{ ($paymentInfo->payment_status ?? '') == 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                                        </select>
                                        <button type="button" class="btn btn-success btn-sm ms-2" onclick="updatePayment{{ __('Status') }}({{ $data->id }})">
                                            <i class="fa fa-check"></i>{{ __('Update') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ✅ END -->

                    <!-- ✅ Order {{ __('Amount') }} Section -->
                    @php
                        // Normal customer order: calculate from sale_price
                        $sub{{ __('total') }} = $data->orderdetails->sum(function($item) {
                            return $item->sale_price * $item->qty;
                        });
                        
                        $shipping = $data->shipping_charge ?? 0;
                        $discount = $data->discount ?? 0;
                        $final{{ __('Total') }} = $data->amount;
                    @endphp

                    <div class="col-sm-12 mt-3">
                        <div class="payment-box">
                            <h5 class="mb-3"><i class="fa fa-money-bill-wave"></i> {{ __('Order {{ __('Amount') }} Information') }}</h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="payment-label">{{ __('Sub{{ __('total') }}') }}:</label>
                                    <span class="payment-value">৳{{ number_format($sub{{ __('total') }}, 2) }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="payment-label">{{ __('Shipping') }}:</label>
                                    <span class="payment-value">৳{{ number_format($shipping, 2) }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="payment-label">{{ __('Discount') }}:</label>
                                    <span class="payment-value">৳{{ number_format($discount, 2) }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="payment-label"><strong>{{ __('Final {{ __('Total') }}') }}:</strong></label>
                                    <span class="payment-value" style="font-size: 18px; color: #28a745;"><strong>৳{{ number_format($final{{ __('Total') }}, 2) }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ✅ END Order {{ __('Amount') }} Section -->

                    <div class="col-sm-12 mt-3">
                        <div class="form-group mb-3">
                            <label for="category_id" class="form-label">{{ __('Order {{ __('Status') }}') }}</label>
                            <select class="form-control select2-multiple" name="status" data-toggle="select2" required>
                                <option value="">{{ __('Select..') }}</option>
                                @foreach($orderstatus as $value)
                                    <option value="{{$value->id}}"  @if($data->order_status==$value->{{ __('id)') }} selected @endif>{{$value->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-sm-12 text-end">
                        <button type="{{ __('submit') }}" class="btn btn-success px-4">
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
