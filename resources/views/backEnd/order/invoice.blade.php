@extends('backEnd.layouts.master')
@section('title','Order Invoice')
@section('content')
<style>
    .customer-invoice {
        margin: 25px 0;
    }
    .invoice_btn{
        margin-bottom: 15px;
    }
    p{
        margin:0;
    }
    td{
        font-size: 16px;
    }
    /* POS receipt — hidden on screen, shown on print */
    .pos-receipt { display: none; }

    @page { size: 80mm auto; margin: 3mm 4mm; }

    @media print {
        /* Hide admin layout */
        .navbar-custom, .left-side-menu, .right-bar,
        .customer-invoice, .invoice_btn, .no-print,
        header, footer { display: none !important; }

        body { background: #fff !important; }
        #wrapper, .content-page, .content-page > .content {
            padding: 0 !important; margin: 0 !important;
        }

        /* Show receipt */
        .pos-receipt { display: block !important; }

        /* Receipt styles */
        .pos-receipt * { font-family: 'Courier New', Courier, monospace; }
        .pos-receipt .rh { text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 5px; }
        .pos-receipt .rh .shop { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .pos-receipt .rh p { font-size: 10px; margin-top: 2px; }
        .pos-receipt .rt { text-align: center; font-size: 12px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; margin: 4px 0; letter-spacing: 3px; }
        .pos-receipt .rm { font-size: 11px; margin-bottom: 3px; }
        .pos-receipt .fl { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .pos-receipt table { width: 100%; border-collapse: collapse; font-size: 10px; margin: 4px 0; }
        .pos-receipt table thead th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px; font-weight: 700; text-align: left; }
        .pos-receipt table thead th.r { text-align: right; }
        .pos-receipt table tbody td { padding: 3px 2px; vertical-align: top; }
        .pos-receipt table tbody tr:last-child td { border-bottom: 1px solid #000; }
        .pos-receipt .rs { display: flex; justify-content: space-between; font-size: 11px; padding: 2px 0; }
        .pos-receipt .rtotal { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0; margin: 3px 0; }
        .pos-receipt .rp { font-size: 11px; margin-top: 3px; }
        .pos-receipt .rp .fl { padding: 2px 0; margin-bottom: 0; }
        .pos-receipt .ptotal { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; padding-top: 4px; margin-top: 3px; }
        .pos-receipt .dash { border: none; border-top: 1px dashed #555; margin: 5px 0; }
        .pos-receipt .rf { text-align: center; border-top: 1px dashed #666; margin-top: 10px; padding-top: 7px; font-size: 12px; }
        .pos-receipt .rf .ty { font-size: 14px; font-weight: 700; }
        .pos-receipt .rf small { font-size: 9px; font-style: italic; margin-top: 3px; display: block; }
    }
</style>

<section class="customer-invoice ">
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <a href="/admin/order/all" class="no-print"><strong><i class="fe-arrow-left"></i> Back To Order</strong></a>
            </div>
            <div class="col-sm-6 text-end">
                <button onclick="printFunction()" class="no-print btn btn-xs btn-success waves-effect waves-light"><i class="fa fa-print"></i></button>
            </div>

            <div class="col-sm-12 mt-3">
                <div class="invoice-innter" style="width:760px;margin: 0 auto;background: #fff;overflow: hidden;padding: 30px;padding-top: 0;">
                    <table style="width:100%">
                        <tr>
                            <td style="width: 40%; float: left; padding-top: 15px;">
                                <img src="{{asset($generalsetting->white_logo)}}" width="190px" style="margin-top:25px !important" alt="">
                                <p style="font-size: 14px; color: #222; margin: 20px 0;">
                                    <strong>Payment Method:</strong> 
                                    <span style="text-transform: uppercase;">{{$order->payment?$order->payment->payment_method:''}}</span>
                                </p>

                                <!-- ✅ Payment Gateway + Status অংশ -->
                                <div style="margin-bottom:15px;">
                                    <p><strong>Payment Gateway:</strong> {{ ucfirst($order->payment_gateway ?? 'N/A') }}</p>
                                    <p><strong>Payment Status:</strong></p>
                                    <select id="payment_status_{{ $order->id }}" class="form-control no-print" style="width:auto; display:inline-block;">
                                        <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                        <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                    <button class="btn btn-sm btn-success no-print" onclick="updatePaymentStatus({{ $order->id }})">Update</button>
                                </div>
                                
                                <!-- ✅ Order Status Change (Manual) -->
                                <div style="margin-bottom:15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                    <p style="margin-bottom: 5px;"><strong>Order Status:</strong> 
                                        <span class="badge bg-{{ $order->order_status == 6 ? 'success' : ($order->order_status == 11 ? 'danger' : 'warning') }}">
                                            {{ $order->status ? $order->status->name : 'N/A' }}
                                        </span>
                                    </p>
                                    @if(isset($orderstatus))
                                    <div class="no-print">
                                        <select id="order_status_{{ $order->id }}" class="form-control" style="width:auto; display:inline-block; margin-right: 5px;">
                                            @foreach($orderstatus as $status)
                                                <option value="{{ $status->id }}" {{ $order->order_status == $status->id ? 'selected' : '' }}>
                                                    {{ $status->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-primary" onclick="updateOrderStatus({{ $order->id }})">
                                            <i class="fa fa-save"></i> Update Status
                                        </button>
                                        @if($order->courier_type)
                                        <br><small class="text-muted" style="margin-top: 5px; display: inline-block;">
                                            <i class="fa fa-truck"></i> Courier: {{ ucfirst($order->courier_type) }}
                                            @if($order->courier_tracking_id)
                                                | Tracking: {{ $order->courier_tracking_id }}
                                            @endif
                                            <br><span style="color: #6c757d; font-size: 11px;">(Auto-update from courier every 10 minutes)</span>
                                        </small>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                <!-- ✅ END -->

                                <div class="invoice_form">
                                    <p style="font-size:16px;line-height:1.8;color:#222"><strong>Invoice From:</strong></p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{$generalsetting->name}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{$contact->phone}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{$contact->email}}</p>
                            {{-- ⭐ SHOW ORDER NOTE --}}
@if(!empty($order->order_note) || !empty($order->note))
<p style="font-size:16px;line-height:1.8;color:#222">
    <strong>Order Note:</strong> {{ $order->order_note ?? $order->note }}
</p>
@endif
									
                                </div>
                            </td>

                            <td  style="width:60%;float: left;">
                                <div class="invoice-bar" style=" background: #4DBC60; transform: skew(38deg); width: 100%; margin-left: 65px; padding: 20px 60px; ">
                                    <p style="font-size: 30px; color: #fff; transform: skew(-38deg); text-transform: uppercase; text-align: right; font-weight: bold;">Invoice</p>
                                </div>
                                <div class="invoice-bar" style="background: #fff; transform: skew(36deg); width: 72%; margin-left: 182px; padding: 12px 32px; margin-top: 6px;">
                                    <p style="font-size: 15px; color: #222;font-weight:bold; transform: skew(-36deg); text-align: right; padding-right: 18px">Invoice ID : <strong>#{{$order->invoice_id}}</strong></p>
                                    <p style="font-size: 15px; color: #222;font-weight:bold; transform: skew(-36deg); text-align: right; padding-right: 32px">Invoice Date: <strong>{{$order->created_at->format('d-m-y')}}</strong></p>
                                </div>
                                <div class="invoice_to" style="padding-top: 20px;">
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;"><strong>Invoice To:</strong></p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->name:''}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->phone:''}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->address:''}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->area:''}}</p>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <table class="table" style="margin-top: 30px;margin-bottom: 0;">
                        <thead style="background: #4DBC60; color: #fff;">
                            <tr>
                                <th>SL</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Check if this is a reseller order (once, outside loop)
                                // Reseller orders ALWAYS have customer_payable_amount field set
                                $isResellerOrderItem = !empty($order->customer_payable_amount);
                                
                                // For reseller orders: calculate custom_price from customer_payable_amount
                                $customPrice = null;
                                $totalProductValue = 0;
                                if ($isResellerOrderItem && $order->customer_payable_amount) {
                                    $customPrice = $order->customer_payable_amount - $order->shipping_charge;
                                    // Calculate total of all products (sum of sale_price * qty)
                                    foreach ($order->orderdetails as $od) {
                                        $totalProductValue += ($od->sale_price * $od->qty);
                                    }
                                }
                            @endphp
                            @foreach($order->orderdetails as $key=>$value)
                            @php
                                // For reseller orders: Calculate price from customer_payable_amount proportionally
                                // customer_payable_amount = custom_price + shipping
                                // custom_price = reseller যে দামে sell করেছে (total)
                                // For normal orders: show sale_price (main price)
                                
                                if ($isResellerOrderItem && $customPrice && $totalProductValue > 0) {
                                    // Reseller order: Calculate per product price from customer_payable_amount
                                    // This product's share = (this product's value / total value) * custom_price
                                    $thisProductValue = $value->sale_price * $value->qty;
                                    $thisProductShare = ($thisProductValue / $totalProductValue) * $customPrice;
                                    $displayPrice = $thisProductShare / $value->qty; // Per unit price
                                } else {
                                    // Normal order: show sale_price (main price)
                                    $displayPrice = $value->sale_price;
                                }
                            @endphp
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$value->product_name}} 
                                    <br> 
                                @php
                                    $sizeDisplay = null;
                                    if ($value->size) {
                                        $sizeDisplay = $value->size->sizeName ?? $value->size->size_name ?? $value->size->name ?? null;
                                    } elseif ($value->product_size) {
                                        // If product_size is an ID, fetch the Size model
                                        $s = \App\Models\Size::find($value->product_size);
                                        $sizeDisplay = $s ? ($s->sizeName ?? $s->size_name ?? null) : null;
                                        // If still null, it might be a direct size name string
                                        if (!$sizeDisplay && !is_numeric($value->product_size)) {
                                            $sizeDisplay = $value->product_size;
                                        }
                                    }
                                @endphp
                                @if($sizeDisplay)
                                    <small>Size: {{ $sizeDisplay }}</small><br>
                                @endif   
                                @php
                                    $displayColor = ($value->color && $value->color->name) ? $value->color->name : ($value->product_color ?: null);
                                @endphp
                                @if($displayColor)
                                    <small>Color: {{ $displayColor }}</small>
                                @endif 
                                </td>
                                <td>৳{{ number_format($displayPrice, 2) }}</td>
                                <td>{{$value->qty}}</td>
                                <td>৳{{ number_format($displayPrice * $value->qty, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="invoice-bottom">
                       @php
    // Check if this is a reseller order
    // Reseller orders ALWAYS have customer_payable_amount field set
    $isResellerOrder = !empty($order->customer_payable_amount);

    // Calculate subtotal - for reseller orders, calculate from customer_payable_amount
    if ($isResellerOrder && $order->customer_payable_amount) {
        // customer_payable_amount = custom_price + shipping
        // custom_price = reseller যে দামে sell করেছে (total)
        // So subtotal = customer_payable_amount - shipping
        $subtotal = $order->customer_payable_amount - $order->shipping_charge;
    } else {
        // Normal order: calculate from sale_price
        $subtotal = 0;
        foreach ($order->orderdetails as $item) {
            $subtotal += ($item->sale_price * $item->qty);
        }
    }
    
    $shipping = $order->shipping_charge;
    $discount = $order->discount;
    
    // If reseller order, use customer_payable_amount, otherwise use amount
    $finalTotal = $isResellerOrder ? $order->customer_payable_amount : $order->amount;

    // Payment Table থেকে নেওয়া Paid/Advance Amount
    $advancePaid = \App\Models\Payment::where('order_id', $order->id)->sum('amount');

    // Due Amount
    $dueAmount = $finalTotal - $advancePaid;
@endphp

<table class="table" style="width: 300px; float: right; margin-bottom: 30px;">
    <tbody style="background:#f1f9f8">
        @if($isResellerOrder)
            <tr style="background:#ffc107;color:#000">
                <td><strong><i class="fa fa-user-tag"></i> Reseller Order</strong></td>
                <td></td>
            </tr>
        @endif
        <tr>
            <td><strong>SubTotal</strong></td>
            <td><strong>৳{{ number_format($subtotal, 2) }}</strong></td>
        </tr>
        <tr>
            <td><strong>Shipping(+)</strong></td>
            <td><strong>৳{{ number_format($shipping, 2) }}</strong></td>
        </tr>
        <tr>
            <td><strong>Discount(-)</strong></td>
            <td><strong>৳{{ number_format($discount, 2) }}</strong></td>
        </tr>

        <tr style="background:#4DBC60;color:#fff">
            <td><strong>{{ $isResellerOrder ? 'Customer Payable Amount' : 'Final Total' }}</strong></td>
            <td><strong>৳{{ number_format($finalTotal, 2) }}</strong></td>
        </tr>

        {{-- 🔥 যদি Advance Payment থাকে --}}
        @if($advancePaid > 0 && $advancePaid < $finalTotal)
            <tr>
                <td><strong>Advance Paid</strong></td>
                <td><strong>৳{{ number_format($advancePaid, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Due Amount</strong></td>
                <td><strong>৳{{ number_format($dueAmount, 2) }}</strong></td>
            </tr>
        @endif
    </tbody>
</table>


                        <div class="terms-condition" style="overflow: hidden; width: 100%; text-align: center; padding: 20px 0; border-top: 1px solid #ddd;">
                            <h5 style="font-style: italic;"><a href="{{route('page',['slug'=>'terms-condition'])}}">Terms & Conditions</a></h5>
                            <p style="text-align: center; font-style: italic; font-size: 15px; margin-top: 10px;">* This is a computer generated invoice, does not require any signature.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ POS RECEIPT (print only) ══ --}}
@php
    $isRes   = !empty($order->customer_payable_amount);
    $sub     = 0;
    foreach ($order->orderdetails as $od) { $sub += ($od->sale_price * $od->qty); }
    if ($isRes && $order->customer_payable_amount) { $sub = $order->customer_payable_amount - $order->shipping_charge; }
    $ftotal  = $isRes ? $order->customer_payable_amount : $order->amount;
    $tqty    = $order->orderdetails->sum('qty');
    $pmethod = strtoupper($order->payment_gateway ?? ($order->payment ? $order->payment->payment_method : 'N/A'));
    $pstatus = $order->payment_status ?? ($order->payment ? $order->payment->payment_status : 'pending');
    $adv     = \App\Models\Payment::where('order_id',$order->id)->sum('amount');
    $due     = $ftotal - $adv;
    $trkId   = $order->courier_tracking_id ?? $order->consignment_id ?? null;
    $courier = $order->courier_type ?? ($trkId ? 'steadfast' : null);
@endphp
<div class="pos-receipt">
    <div class="rh">
        <div class="shop">{{ $generalsetting->name }}</div>
        @if($contact->address)<p>{{ $contact->address }}</p>@endif
        @if($contact->phone)<p>Phone: {{ $contact->phone }}</p>@endif
        @if($contact->email)<p>{{ $contact->email }}</p>@endif
    </div>
    <div class="rt">POS Invoice</div>
    <div class="rm">
        <div class="fl">
            <span>Bill No. : <strong>{{ $order->invoice_id }}</strong></span>
            <span>{{ $order->created_at->format('H:i') }} hrs</span>
        </div>
        <div class="fl"><span>Date &nbsp;&nbsp;: <strong>{{ $order->created_at->format('d-m-Y') }}</strong></span></div>
        @if($order->shipping && $order->shipping->name)
        <div class="fl"><span>Buyer &nbsp;&nbsp;: <strong>{{ $order->shipping->name }}</strong></span></div>
        @endif
        @if($order->shipping && $order->shipping->phone)
        <div class="fl"><span>Phone &nbsp;&nbsp;: {{ $order->shipping->phone }}</span></div>
        @endif
        @if($order->shipping && ($order->shipping->address || $order->shipping->area))
        <div class="fl"><span>Address : {{ $order->shipping->address }}{{ $order->shipping->area ? ', '.$order->shipping->area : '' }}</span></div>
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:14px;">#</th>
                <th>Product</th>
                <th style="width:22px;text-align:center;">Qty</th>
                <th style="width:44px;" class="r">Rate</th>
                <th style="width:48px;" class="r">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderdetails as $key => $value)
            @php
                if ($isRes && $order->customer_payable_amount && $sub > 0) {
                    $tv = $value->sale_price * $value->qty;
                    $dp = (($tv / ($sub + $order->discount)) * $sub) / $value->qty;
                } else { $dp = $value->sale_price; }
                $szd = null;
                if ($value->size) { $szd = $value->size->sizeName ?? null; }
                elseif ($value->product_size) {
                    $sm  = \App\Models\Size::find($value->product_size);
                    $szd = $sm ? ($sm->sizeName ?? null) : (is_numeric($value->product_size) ? null : $value->product_size);
                }
                $cld = ($value->color && $value->color->colorName) ? $value->color->colorName : ((!is_numeric($value->product_color) && $value->product_color) ? $value->product_color : null);
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    <strong>{{ $value->product_name }}</strong>
                    @if($szd || $cld)<br><small>@if($szd)Sz:{{ $szd }}@endif @if($szd&&$cld)| @endif @if($cld){{ $cld }}@endif</small>@endif
                </td>
                <td style="text-align:center;">{{ $value->qty }}</td>
                <td style="text-align:right;">{{ number_format($dp,2) }}</td>
                <td style="text-align:right;">{{ number_format($dp*$value->qty,2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="rs"><span>Subtotal</span><span>{{ number_format($sub,2) }}</span></div>
    @if($order->discount > 0)<div class="rs"><span>Discount (–)</span><span>{{ number_format($order->discount,2) }}</span></div>@endif
    @if($order->shipping_charge > 0)<div class="rs"><span>Delivery (+)</span><span>{{ number_format($order->shipping_charge,2) }}</span></div>@endif
    <div class="rtotal">
        <span>Total &nbsp; {{ $tqty }} {{ $tqty>1?'Nos':'No' }}</span>
        <span>&#2547; {{ number_format($ftotal,2) }}</span>
    </div>
    <div class="rp">
        <div class="fl"><span>Method &nbsp;&nbsp;:</span><span><strong>{{ $pmethod }}</strong></span></div>
        <div class="fl"><span>Pay Status :</span><span><strong>{{ strtoupper($pstatus) }}</strong></span></div>
        @if($adv > 0 && $adv < $ftotal)
        <div class="fl"><span>Advance &nbsp;&nbsp;:</span><span>&#2547; {{ number_format($adv,2) }}</span></div>
        <div class="fl"><span>Due &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span><span><strong>&#2547; {{ number_format($due,2) }}</strong></span></div>
        @endif
        @if($courier)
        <hr class="dash">
        <div class="fl"><span>Courier &nbsp;&nbsp;:</span><span><strong>{{ ucfirst($courier) }}</strong></span></div>
        @if($trkId)<div class="fl"><span>Tracking &nbsp;:</span><span>{{ $trkId }}</span></div>@endif
        @if($order->courier_sent_at)<div class="fl"><span>Sent &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span><span>{{ \Carbon\Carbon::parse($order->courier_sent_at)->format('d M Y') }}</span></div>@endif
        @endif
        <div class="ptotal"><span>Total Paid</span><span>&#2547; {{ number_format($ftotal,2) }}</span></div>
    </div>
    <hr class="dash">
    <div class="rs"><span>Order Status :</span><span><strong>{{ $order->status ? $order->status->name : 'Processing' }}</strong></span></div>
    <div class="rf">
        <div class="ty">Thank You!</div>
        <div>Visit Again!</div>
        <small>* Computer generated invoice. No signature required.</small>
    </div>
</div>
{{-- ══ END POS RECEIPT ══ --}}

<!-- ✅ JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

<script>
function printFunction() {
    window.print();
}

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

function updateOrderStatus(orderId) {
    let status = document.getElementById('order_status_' + orderId).value;
    
    if (!status) {
        toastr.warning('Please select a status', 'Warning!');
        return;
    }

    // Confirm before changing status
    if (!confirm('Are you sure you want to change the order status? This will manually override any automatic courier status updates.')) {
        return;
    }

    fetch('{{ route("admin.order.updateSingleStatus") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order_id: orderId, order_status: status })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success(data.message, 'Success!');
            // Reload page after 1 second to show updated status
            setTimeout(function() {
                location.reload();
            }, 1000);
        } else {
            toastr.error(data.message, 'Error!');
        }
    })
    .catch(err => {
        toastr.error('Something went wrong!', 'Error!');
        console.error(err);
    });
}
</script>
@endsection
