@extends('backEnd.layouts.master')
@section('title','{{ __('Order {{ __('{{ __('Inv') }}oice') }}') }}')
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
        .pos-receipt * { font-family: '{{ __('Courier') }} {{ __('New') }}', {{ __('Courier') }}, monospace; }
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
        .pos-receipt .r{{ __('total') }} { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0; margin: 3px 0; }
        .pos-receipt .rp { font-size: 11px; margin-top: 3px; }
        .pos-receipt .rp .fl { padding: 2px 0; margin-bottom: 0; }
        .pos-receipt .p{{ __('total') }} { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; border-top: 1px solid #000; padding-top: 4px; margin-top: 3px; }
        .pos-receipt .dash { border: none; border-top: 1px dashed #555; margin: 5px 0; }
        .pos-receipt .rf { text-align: center; border-top: 1px dashed #666; margin-top: 10px; padding-top: 7px; font-size: 12px; }
        .pos-receipt .rf .ty { font-size: {{ __('14px') }}; font-weight: 700; }
        .pos-receipt .rf small { font-size: 9px; font-style: italic; margin-top: 3px; display: block; }
    }
</style>

<section class="customer-invoice ">
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <a href="/admin/order/all" class="no-print"><strong><i class="fe-arrow-left"></i> {{ __('Back To Order') }}</strong></a>
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
                                <p style="font-size: {{ __('14px') }}; color: #222; margin: 20px 0;">
                                    <strong>{{ __('Payment {{ __('Method') }}') }}:</strong> 
                                    <span style="text-transform: uppercase;">{{$order->payment?$order->payment->payment_method:''}}</span>
                                </p>

                                <!-- ✅ {{ __('Payment Gateway') }} + {{ __('Status') }} অংশ -->
                                <div style="margin-bottom:15px;">
                                    <p><strong>{{ __('Payment Gateway') }}:</strong> {{ ucfirst($order->payment_gateway ?? '{{ __('N/A') }}') }}</p>
                                    <p><strong>{{ __('Payment {{ __('Status') }}') }}:</strong></p>
                                    <select id="payment_status_{{ $order->id }}" class="form-control no-print" style="width:auto; display:inline-block;">
                                        <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                        <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                                        <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
                                        <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                                    </select>
                                    <button class="btn btn-sm btn-success no-print" onclick="updatePayment{{ __('Status') }}({{ $order->id }})">{{ __('Update') }}</button>
                                </div>
                                
                                <!-- ✅ {{ __('Order {{ __('Status') }}') }} {{ __('Change') }} (Manual) -->
                                <div style="margin-bottom:15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                    <p style="margin-bottom: 5px;"><strong>{{ __('Order {{ __('Status') }}') }}:</strong> 
                                        <span class="badge bg-{{ $order->order_status == 6 ? 'success' : ($order->order_status == 11 ? 'danger' : 'warning') }}">
                                            {{ $order->status ? $order->status->name : '{{ __('N/A') }}' }}
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
                                        <button class="btn btn-sm btn-primary" onclick="updateOrder{{ __('Status') }}({{ $order->id }})">
                                            <i class="fa fa-save"></i> {{ __('Update {{ __('Status') }}') }}
                                        </button>
                                        @if($order->courier_type)
                                        <br><small class="text-muted" style="margin-top: 5px; display: inline-block;">
                                            <i class="fa fa-truck"></i> {{ __('Courier') }}: {{ ucfirst($order->courier_type) }}
                                            @if($order->courier_tracking_{{ __('id)') }}
                                                | {{ __('{{ __('Track') }}ing') }}: {{ $order->courier_tracking_id }}
                                            @endif
                                            <br><span style="color: #6c757d; font-size: 11px;">{{ __('(Auto-update from courier every 10 minutes)') }}</span>
                                        </small>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                <!-- ✅ END -->

                                <div class="invoice_form">
                                    <p style="font-size:16px;line-height:1.8;color:#222"><strong>{{ __('{{ __('{{ __('Inv') }}oice') }} {{ __('From') }}') }}:</strong></p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{$generalsetting->name}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{$contact->{{ __('phone') }}}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{$contact->email}}</p>
                            {{-- ⭐ SHOW ORDER NOTE --}}
@if(!empty($order->order_note) || !empty($order->note))
<p style="font-size:16px;line-height:1.8;color:#222">
    <strong>{{ __('Order {{ __('Note') }}') }}:</strong> {{ $order->order_note ?? $order->note }}
</p>
@endif
									
                                </div>
                            </td>

                            <td  style="width:60%;float: left;">
                                <div class="invoice-bar" style=" background: #4DBC60; transform: skew(38deg); width: 100%; margin-left: 65px; padding: 20px 60px; ">
                                    <p style="font-size: 30px; color: #fff; transform: skew(-38deg); text-transform: uppercase; text-align: right; font-weight: bold;">{{ __('{{ __('{{ __('Inv') }}oice') }}') }}</p>
                                </div>
                                <div class="invoice-bar" style="background: #fff; transform: skew(36deg); width: 72%; margin-left: 182px; padding: 12px 32px; margin-top: 6px;">
                                    <p style="font-size: 15px; color: #222;font-weight:bold; transform: skew(-36deg); text-align: right; padding-right: 18px">{{ __('{{ __('{{ __('Inv') }}oice') }} ID') }} : <strong>#{{$order->invoice_id}}</strong></p>
                                    <p style="font-size: 15px; color: #222;font-weight:bold; transform: skew(-36deg); text-align: right; padding-right: 32px">{{ __('{{ __('{{ __('Inv') }}oice') }} {{ __('Date') }}') }}: <strong>{{$order->created_at->format('d-m-y')}}</strong></p>
                                </div>
                                <div class="invoice_to" style="padding-top: 20px;">
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;"><strong>{{ __('{{ __('{{ __('Inv') }}oice') }} To') }}:</strong></p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->name:''}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->{{ __('phone') }}:''}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->address:''}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222;text-align: right;">{{$order->shipping?$order->shipping->area:''}}</p>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <table class="table" style="margin-top: 30px;margin-bottom: 0;">
                        <thead style="background: #4DBC60; color: #fff;">
                            <tr>
                                <th>{{ __('SL') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Qty') }}</th>
                                <th>{{ __('{{ __('Total') }}') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // {{ __('Check') }} if this is a reseller order (once, outside loop)
                                // Reseller orders ALWAYS have customer_payable_amount field set
                                $isResellerOrder{{ __('Item') }} = !empty($order->customer_payable_amount);
                                
                                // For reseller orders: calculate custom_price from customer_payable_amount
                                $customPrice = null;
                                $total{{ __('Product') }}{{ __('Value') }} = 0;
                                if ($isResellerOrder{{ __('Item') }} && $order->customer_payable_amount) {
                                    $customPrice = $order->customer_payable_amount - $order->shipping_charge;
                                    // {{ __('Calculate') }} {{ __('total') }} of all products (sum of sale_price * qty)
                                    foreach ($order->orderdetails as $od) {
                                        $total{{ __('Product') }}{{ __('Value') }} += ($od->sale_price * $od->qty);
                                    }
                                }
                            @endphp
                            @foreach($order->orderdetails as $key=>$value)
                            @php
                                // For reseller orders: {{ __('Calculate') }} price from customer_payable_amount proportionally
                                // customer_payable_amount = custom_price + shipping
                                // custom_price = reseller যে দামে sell করেছে ({{ __('total') }})
                                // For normal orders: show sale_price (main price)
                                
                                if ($isResellerOrder{{ __('Item') }} && $customPrice && $total{{ __('Product') }}{{ __('Value') }} > 0) {
                                    // Reseller order: {{ __('Calculate') }} per product price from customer_payable_amount
                                    // This product's share = (this product's value / {{ __('total') }} value) * custom_price
                                    $this{{ __('Product') }}{{ __('Value') }} = $value->sale_price * $value->qty;
                                    $this{{ __('Product') }}Share = ($this{{ __('Product') }}{{ __('Value') }} / $total{{ __('Product') }}{{ __('Value') }}) * $customPrice;
                                    $displayPrice = $this{{ __('Product') }}Share / $value->qty; // Per unit price
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
                                        $sizeDisplay = $value->size->size{{ __('Name') }} ?? $value->size->size_name ?? $value->size->name ?? null;
                                    } elseif ($value->product_size) {
                                        // If product_size is an ID, fetch the Size model
                                        $s = \App\Models\Size::find($value->product_size);
                                        $sizeDisplay = $s ? ($s->size{{ __('Name') }} ?? $s->size_name ?? null) : null;
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
                                    $display{{ __('Color') }} = ($value->color && $value->color->name) ? $value->color->name : ($value->product_color ?: null);
                                @endphp
                                @if($display{{ __('Color') }})
                                    <small>{{ __('Color') }}: {{ $display{{ __('Color') }} }}</small>
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
    // {{ __('Check') }} if this is a reseller order
    // Reseller orders ALWAYS have customer_payable_amount field set
    $isResellerOrder = !empty($order->customer_payable_amount);

    // {{ __('Calculate') }} sub{{ __('total') }} - for reseller orders, calculate from customer_payable_amount
    if ($isResellerOrder && $order->customer_payable_amount) {
        // customer_payable_amount = custom_price + shipping
        // custom_price = reseller যে দামে sell করেছে ({{ __('total') }})
        // So sub{{ __('total') }} = customer_payable_amount - shipping
        $sub{{ __('total') }} = $order->customer_payable_amount - $order->shipping_charge;
    } else {
        // Normal order: calculate from sale_price
        $sub{{ __('total') }} = 0;
        foreach ($order->orderdetails as $item) {
            $sub{{ __('total') }} += ($item->sale_price * $item->qty);
        }
    }
    
    $shipping = $order->shipping_charge;
    $discount = $order->discount;
    
    // If reseller order, use customer_payable_amount, otherwise use amount
    $final{{ __('Total') }} = $isResellerOrder ? $order->customer_payable_amount : $order->amount;

    // Payment Table থেকে নেওয়া {{ __('Paid') }}/Advance {{ __('Amount') }}
    $advance{{ __('Paid') }} = \App\Models\Payment::w{{ __('here') }}('order_id', $order->{{ __('id)') }}->sum('amount');

    // {{ __('{{ __('Due') }} {{ __('Amount') }}') }}
    $due{{ __('Amount') }} = $final{{ __('Total') }} - $advance{{ __('Paid') }};
@endphp

<table class="table" style="width: 300px; float: right; margin-bottom: 30px;">
    <tbody style="background:#f1f9f8">
        @if($isResellerOrder)
            <tr style="background:#ffc107;color:#000">
                <td><strong><i class="fa fa-user-tag"></i> {{ __('Reseller Order') }}</strong></td>
                <td></td>
            </tr>
        @endif
        <tr>
            <td><strong>{{ __('Sub{{ __('Total') }}') }}</strong></td>
            <td><strong>৳{{ number_format($sub{{ __('total') }}, 2) }}</strong></td>
        </tr>
        <tr>
            <td><strong>{{ __('{{ __('Shipping') }}(+)') }}</strong></td>
            <td><strong>৳{{ number_format($shipping, 2) }}</strong></td>
        </tr>
        <tr>
            <td><strong>{{ __('{{ __('Discount') }}(-)') }}</strong></td>
            <td><strong>৳{{ number_format($discount, 2) }}</strong></td>
        </tr>

        <tr style="background:#4DBC60;color:#fff">
            <td><strong>{{ $isResellerOrder ? '{{ __('Customer') }} Payable {{ __('Amount') }}' : '{{ __('Final {{ __('Total') }}') }}' }}</strong></td>
            <td><strong>৳{{ number_format($final{{ __('Total') }}, 2) }}</strong></td>
        </tr>

        {{-- 🔥 যদি {{ __('Advance Payment') }} থাকে --}}
        @if($advance{{ __('Paid') }} > 0 && $advance{{ __('Paid') }} < $final{{ __('Total') }})
            <tr>
                <td><strong>{{ __('Advance {{ __('Paid') }}') }}</strong></td>
                <td><strong>৳{{ number_format($advance{{ __('Paid') }}, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>{{ __('{{ __('Due') }} {{ __('Amount') }}') }}</strong></td>
                <td><strong>৳{{ number_format($due{{ __('Amount') }}, 2) }}</strong></td>
            </tr>
        @endif
    </tbody>
</table>


                        <div class="terms-condition" style="overflow: hidden; width: 100%; text-align: center; padding: 20px 0; border-top: 1px solid #ddd;">
                            <h5 style="font-style: italic;"><a href="{{route('page',['slug'=>'terms-condition'])}}">{{ __('{{ __('Terms') }} & Conditions') }}</a></h5>
                            <p style="text-align: center; font-style: italic; font-size: 15px; margin-top: 10px;">{{ __('* This is a computer generated invoice, does not require any signature.') }}</p>
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
    $f{{ __('total') }}  = $isRes ? $order->customer_payable_amount : $order->amount;
    $tqty    = $order->orderdetails->sum('qty');
    $pmethod = strtoupper($order->payment_gateway ?? ($order->payment ? $order->payment->payment_method : '{{ __('N/A') }}'));
    $pstatus = $order->payment_status ?? ($order->payment ? $order->payment->payment_status : 'pending');
    $adv     = \App\Models\Payment::w{{ __('here') }}('order_id',$order->{{ __('id)') }}->sum('amount');
    $due     = $f{{ __('total') }} - $adv;
    $trkId   = $order->courier_tracking_id ?? $order->consignment_id ?? null;
    $courier = $order->courier_type ?? ($trkId ? 'steadfast' : null);
@endphp
<div class="pos-receipt">
    <div class="rh">
        <div class="shop">{{ $generalsetting->name }}</div>
        @if($contact->{{ __('address)') }}<p>{{ $contact->address }}</p>@endif
        @if($contact->{{ __('{{ __('phone') }})') }}<p>{{ __('Phone') }}: {{ $contact->{{ __('phone') }} }}</p>@endif
        @if($contact->{{ __('email)') }}<p>{{ $contact->email }}</p>@endif
    </div>
    <div class="rt">{{ __('POS {{ __('{{ __('Inv') }}oice') }}') }}</div>
    <div class="rm">
        <div class="fl">
            <span>{{ __('Bill No.') }} : <strong>{{ $order->invoice_id }}</strong></span>
            <span>{{ $order->created_at->format('H:i') }} hrs</span>
        </div>
        <div class="fl"><span>{{ __('{{ __('Date') }} &nbsp;&nbsp') }};: <strong>{{ $order->created_at->format('d-m-Y') }}</strong></span></div>
        @if($order->shipping && $order->shipping->name)
        <div class="fl"><span>{{ __('Buyer &nbsp;&nbsp') }};: <strong>{{ $order->shipping->name }}</strong></span></div>
        @endif
        @if($order->shipping && $order->shipping->{{ __('{{ __('phone') }})') }}
        <div class="fl"><span>{{ __('Phone') }} &nbsp;&nbsp;: {{ $order->shipping->{{ __('phone') }} }}</span></div>
        @endif
        @if($order->shipping && ($order->shipping->address || $order->shipping->area))
        <div class="fl"><span>Address : {{ $order->shipping->address }}{{ $order->shipping->area ? ', '.$order->shipping->area : '' }}</span></div>
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:{{ __('14px') }};">#</th>
                <th>{{ __('Product') }}</th>
                <th style="width:22px;text-align:center;">{{ __('Qty') }}</th>
                <th style="width:44px;" class="r">{{ __('Rate') }}</th>
                <th style="width:48px;" class="r">{{ __('{{ __('Total') }}') }}</th>
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
                if ($value->size) { $szd = $value->size->size{{ __('Name') }} ?? null; }
                elseif ($value->product_size) {
                    $sm  = \App\Models\Size::find($value->product_size);
                    $szd = $sm ? ($sm->size{{ __('Name') }} ?? null) : (is_numeric($value->product_size) ? null : $value->product_size);
                }
                $cld = ($value->color && $value->color->color{{ __('Name') }}) ? $value->color->color{{ __('Name') }} : ((!is_numeric($value->product_color) && $value->product_color) ? $value->product_color : null);
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
    <div class="rs"><span>{{ __('Sub{{ __('total') }}') }}</span><span>{{ number_format($sub,2) }}</span></div>
    @if($order->discount > 0)<div class="rs"><span>{{ __('Discount') }} (–)</span><span>{{ number_format($order->discount,2) }}</span></div>@endif
    @if($order->shipping_charge > 0)<div class="rs"><span>{{ __('Delivery (+)') }}</span><span>{{ number_format($order->shipping_charge,2) }}</span></div>@endif
    <div class="r{{ __('total') }}">
        <span>{{ __('Total') }} &nbsp; {{ $tqty }} {{ $tqty>1?'Nos':'No' }}</span>
        <span>&#2547; {{ number_format($f{{ __('total') }},2) }}</span>
    </div>
    <div class="rp">
        <div class="fl"><span>{{ __('{{ __('Method') }} &nbsp;&nbsp') }};:</span><span><strong>{{ $pmethod }}</strong></span></div>
        <div class="fl"><span>{{ __('Pay {{ __('Status') }}') }} :</span><span><strong>{{ strtoupper($pstatus) }}</strong></span></div>
        @if($adv > 0 && $adv < $f{{ __('total') }})
        <div class="fl"><span>{{ __('Advance &nbsp;&nbsp') }};:</span><span>&#2547; {{ number_format($adv,2) }}</span></div>
        <div class="fl"><span>{{ __('{{ __('Due') }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp') }};:</span><span><strong>&#2547; {{ number_format($due,2) }}</strong></span></div>
        @endif
        @if($courier)
        <hr class="dash">
        <div class="fl"><span>{{ __('{{ __('Courier') }} &nbsp;&nbsp') }};:</span><span><strong>{{ ucfirst($courier) }}</strong></span></div>
        @if($trkId)<div class="fl"><span>{{ __('{{ __('{{ __('Track') }}ing') }} &nbsp') }};:</span><span>{{ $trkId }}</span></div>@endif
        @if($order->courier_sent_at)<div class="fl"><span>{{ __('Sent &nbsp;&nbsp;&nbsp;&nbsp;&nbsp') }};:</span><span>{{ \Carbon\Carbon::parse($order->courier_sent_at)->format('d M Y') }}</span></div>@endif
        @endif
        <div class="p{{ __('total') }}"><span>{{ __('{{ __('Total') }} {{ __('Paid') }}') }}</span><span>&#2547; {{ number_format($f{{ __('total') }},2) }}</span></div>
    </div>
    <hr class="dash">
    <div class="rs"><span>{{ __('Order {{ __('Status') }}') }} :</span><span><strong>{{ $order->status ? $order->status->name : 'Processing' }}</strong></span></div>
    <div class="rf">
        <div class="ty">{{ __('Thank You!') }}</div>
        <div>{{ __('Visit Again!') }}</div>
        <small>{{ __('* Computer generated invoice. No signature required.') }}</small>
    </div>
</div>
{{-- ══ END POS RECEIPT ══ --}}

<!-- ✅ JS -->
<script src="{{ __('https://') }}cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link href="{{ __('https://') }}cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

<script>
function printFunction() {
    window.print();
}

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

function updateOrder{{ __('Status') }}(orderId) {
    let status = document.getElementById('order_status_' + orderId).value;
    
    if (!status) {
        toastr.warning('{{ __('Please select a status') }}', '{{ __('Warning') }}!');
        return;
    }

    // Confirm before changing status
    if (!confirm('Are you sure you want to change the order status? This will manually override any automatic courier status updates.')) {
        return;
    }

    fetch('{{ route("admin.order.updateSingle{{ __('Status') }}") }}', {
        method: '{{ __('POST') }}',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order_id: orderId, order_status: status })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success(data.{{ __('message') }}, 'Success!');
            // Reload page after 1 second to show updated status
            setTimeout(function() {
                location.reload();
            }, 1000);
        } else {
            toastr.error(data.{{ __('message') }}, '{{ __('Error!') }}');
        }
    })
    .catch(err => {
        toastr.error('Something went wrong!', '{{ __('Error!') }}');
        console.error(err);
    });
}
</script>
@endsection
