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
            <th>SL</th>
            <th>Image</th>
            <th>Product</th>
            <th>Color</th>
            <th>Size</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @php
            // Check if this is a reseller order (once, outside loop)
            // Reseller orders ALWAYS have customer_payable_amount field set
            $isResellerOrderItem = !empty($data->customer_payable_amount);
            
            // For reseller orders: calculate custom_price from customer_payable_amount
            $customPrice = null;
            $totalProductValue = 0;
            if ($isResellerOrderItem && $data->customer_payable_amount) {
                $customPrice = $data->customer_payable_amount - ($data->shipping_charge ?? 0);
                // Calculate total of all products (sum of sale_price * qty)
                foreach ($data->orderdetails as $od) {
                    $totalProductValue += ($od->sale_price * $od->qty);
                }
            }
        @endphp
        @foreach($data->orderdetails as $key => $product)
        @php
            // For reseller orders: Calculate price from customer_payable_amount proportionally
            // customer_payable_amount = custom_price + shipping
            // custom_price = reseller যে দামে sell করেছে (total)
            // For normal orders: show sale_price (main price)
            
            if ($isResellerOrderItem && $customPrice && $totalProductValue > 0) {
                // Reseller order: Calculate per product price from customer_payable_amount
                // This product's share = (this product's value / total value) * custom_price
                $thisProductValue = $product->sale_price * $product->qty;
                $thisProductShare = ($thisProductValue / $totalProductValue) * $customPrice;
                $displayPrice = $thisProductShare / $product->qty; // Per unit price
            } else {
                // Normal order: show sale_price (main price)
                $displayPrice = $product->sale_price;
            }
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
                            <label for="name" class="form-label">Customer name</label>
                            <input type="text" class="form-control" name="name" value="{{$data->shipping?$data->shipping->name:''}}" placeholder="Customer Name">
                        </div>
                    </div>
                            
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="phone" class="form-label">Customer Phone</label>
                            <input type="text" class="form-control" name="phone" value="{{$data->shipping?$data->shipping->phone:''}}" placeholder="Phone Number">
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="address" class="form-label">Customer Address</label>
                            <textarea name="address" class="form-control">{{$data->shipping?$data->shipping->address:''}}</textarea>
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="area" class="form-label">Delivery Area *</label>
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
                        $paymentInfo = DB::table('orders')
                            ->select('payment_gateway', 'payment_status')
                            ->where('id', $data->id)
                            ->first();
                    @endphp

                    <div class="col-sm-12">
                        <div class="payment-box">
                            <h5 class="mb-3"><i class="fa fa-credit-card"></i> Payment Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="payment-label">Payment Gateway:</label><br>
                                    <span class="payment-value">
                                        @if(!empty($paymentInfo->payment_gateway))
                                            {{ strtoupper($paymentInfo->payment_gateway) }}
                                        @else
                                            <span class="text-danger">Not Found</span>
                                        @endif
                                    </span>
                                </div>

                                <div class="col-md-6">
                                    <label class="payment-label">Payment Status:</label>
                                    <div class="d-flex align-items-center">
                                        <select id="payment_status_{{ $data->id }}" class="form-select form-select-sm w-auto">
                                            <option value="pending" {{ ($paymentInfo->payment_status ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="paid" {{ ($paymentInfo->payment_status ?? '') == 'paid' ? 'selected' : '' }}>Paid</option>
                                            <option value="unpaid" {{ ($paymentInfo->payment_status ?? '') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                            <option value="failed" {{ ($paymentInfo->payment_status ?? '') == 'failed' ? 'selected' : '' }}>Failed</option>
                                        </select>
                                        <button type="button" class="btn btn-success btn-sm ms-2" onclick="updatePaymentStatus({{ $data->id }})">
                                            <i class="fa fa-check"></i> Update
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ✅ END -->

                    <!-- ✅ Order Amount Section -->
                    @php
                        // Check if this is a reseller order
                        $isResellerOrder = !empty($data->customer_payable_amount);
                        
                        // Calculate subtotal
                        if ($isResellerOrder && $data->customer_payable_amount) {
                            // Reseller order: subtotal = customer_payable_amount - shipping
                            $subtotal = $data->customer_payable_amount - ($data->shipping_charge ?? 0);
                        } else {
                            // Normal customer order: calculate from sale_price
                            $subtotal = $data->orderdetails->sum(function($item) {
                                return $item->sale_price * $item->qty;
                            });
                        }
                        
                        $shipping = $data->shipping_charge ?? 0;
                        $discount = $data->discount ?? 0;
                        $finalTotal = $isResellerOrder ? ($data->customer_payable_amount ?? $data->amount) : $data->amount;
                    @endphp

                    <div class="col-sm-12 mt-3">
                        <div class="payment-box">
                            <h5 class="mb-3"><i class="fa fa-money-bill-wave"></i> Order Amount Information</h5>
                            @if($isResellerOrder)
                                <div class="alert alert-warning mb-3">
                                    <i class="fa fa-user-tag"></i> <strong>Reseller Order</strong> - Showing customer payable amount
                                </div>
                            @endif
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
                                    <label class="payment-label"><strong>{{ $isResellerOrder ? 'Customer Payable Amount' : 'Final Total' }}:</strong></label>
                                    <span class="payment-value" style="font-size: 18px; color: #28a745;"><strong>৳{{ number_format($finalTotal, 2) }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ✅ END Order Amount Section -->

                    <div class="col-sm-12 mt-3">
                        <div class="form-group mb-3">
                            <label for="category_id" class="form-label">Order Status</label>
                            <select class="form-control select2-multiple" name="status" data-toggle="select2" required>
                                <option value="">Select..</option>
                                @foreach($orderstatus as $value)
                                    <option value="{{$value->id}}"  @if($data->order_status==$value->id) selected @endif>{{$value->name}}</option>
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
