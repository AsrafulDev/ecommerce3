@extends('frontEnd.layouts.master')
@section('title','{{ __('Customer') }} {{ __('{{ __('Inv') }}oice') }}')
@section('content')

@php
    // ১. {{ __('bn_f0a1817c') }} ইনফো নেওয়া ({{ __('Late') }}st payment)
    $payment = \App\Models\Payment::w{{ __('here') }}('order_id', $order->{{ __('id)') }}->orderBy('id','desc')->first();

    // ২. {{ __('{{ __('Status') }}') }} লোয়ারকেস করা
    $gateway_status = $payment ? strtolower(trim($payment->payment_status)) : ''; 
    $payment_method = $payment ? strtolower(trim($payment->payment_method)) : strtolower(trim($order->payment_method ?? ''));
    
    $admin_status   = strtolower(trim($order->payment_status ?? ''));
    $order_status   = strtolower(trim($order->status ?? ''));

    // ৩. গ্র্যান্ড টোটাল
    $grand_{{ __('total') }} = $order->amount;
    $paid_amount = 0;

    // ======================================================
    // ⭐ ইনভয়েস ক্যালকুলেশন লজিক (Exact Logic from Account Page)
    // ======================================================

    // ১. {{ __('bn_f0a1817c') }} রেকর্ড থেকে আসল টাকাটা বের করি
    if ($payment && !in_array($gateway_status, ['failed', 'cancel', 'cancelled', 'rejected'])) {
        $paid_amount = $payment->amount;
    }

    // ২. {{ __('COD') }} ফিক্স: {{ __('COD') }} হলে এবং অর্ডার কমপ্লিট না হলে টাকা ০ দেখাবে (ভুল এড়াতে)
    $is_cod = in_array($payment_method, ['cod', 'cash', 'cash_on_delivery', 'hand cash']);
    $is_order_completed = in_array($order_status, ['completed', 'delivered']) || in_array($admin_status, ['completed', 'delivered']);

    if ($is_cod && !$is_order_completed) {
        if ($paid_amount >= $grand_{{ __('total') }}) {
            $paid_amount = 0; 
        }
    }

    // ৩. ফোর্স ফুল {{ __('bn_623a38a3') }} (Admin Priority):
    if ($is_order_completed) {
        $paid_amount = $grand_{{ __('total') }};
    } 
    elseif (($paid_amount == 0 || !$payment) && in_array($admin_status, ['paid', 'success', 'approved'])) {
        $paid_amount = $grand_{{ __('total') }};
    }

    // ৪. ডিউ ক্যালকুলেশন
    $due_amount = max(0, $grand_{{ __('total') }} - $paid_amount);

    // ৫. {{ __('{{ __('Status') }}') }} চেক (ডিসপ্লে এর জন্য)
    $is_failed = false;
    if ($paid_amount == 0 && in_array($gateway_status, ['failed', 'cancel', 'cancelled'])) {
        $is_failed = true;
    }
@endphp

<style>
    .customer-invoice { margin: 25px 0; }
    .invoice_btn{ margin-bottom: 15px; }
    td{ font-size: 16px; }

   @page { size: a4;  margin: 0mm; background:#fff }
   @media print {
        td{ font-size: 18px; }
        header,footer,nav,.no-print,.sidebar,#sidebar,.navbar,.main-nav,.top-header,.header-section,.footer-section,
        .page-header,.main-footer,.site-header,.site-footer,.mobile-nav,.top-bar { display: none !important; }
        body { background: #fff !important; }
        .container { width: 100% !important; max-width: 100% !important; }
   }
</style>

<section class="customer-invoice">
    <div class="container">
        <div class="row">

            <div class="col-sm-6">
                <a href="{{route('customer.orders')}}">
                    <strong><i class="fa-solid fa-arrow-left"></i> {{ __('Back To Order') }}</strong>
                </a>
            </div>

            <div class="col-sm-6 text-end">
                <button onclick="printFunction()" class="no-print invoice_btn btn btn-primary">
                    <i class="fa fa-print"></i>{{ __('Print') }}</button>
            </div>

            <div class="col-sm-12">

                <div class="invoice-innter" style="width: 900px;margin: 0 auto;background: #f9f9f9;overflow: hidden;padding: 30px;padding-top: 0;">

                    {{-- ===================== {{ __('INVOICE') }} HEADER ===================== --}}
                    <table style="width:100%">
                        <tr>
                            <td style="width: 40%; float: left; padding-top: 15px;">

                                <img src="{{asset($generalsetting->white_logo)}}" style="margin-top:25px !important;width:150px">

                                <div style="margin: 20px 0;">
                                    <p style="font-size: {{ __('14px') }}; color: #222; margin-bottom: 5px;">
                                        <strong>{{ __('Payment {{ __('Method') }}') }}:</strong> 
                                        <span style="text-transform: uppercase;">{{ $payment_method }}</span>
                                    </p>
                                    
                                    {{-- {{ __('bn_f0a1817c') }} {{ __('{{ __('Status') }}') }} ডিসপ্লে --}}
                                    <p style="font-size: {{ __('14px') }}; color: #222;">
                                        <strong>{{ __('Status') }}:</strong>
                                        @if($paid_amount >= $grand_{{ __('total') }})
                                            <span style="color: green; font-weight: bold; text-transform: uppercase;">{{ __('PAID') }}</span>
                                        @elseif($is_failed)
                                            <span style="color: red; font-weight: bold; text-transform: uppercase;">{{ __('FAILED') }}</span>
                                        @elseif($paid_amount > 0)
                                            <span style="color: #007bff; font-weight: bold; text-transform: uppercase;">{{ __('PARTIAL {{ __('PAID') }}') }}</span>
                                        @else
                                            <span style="color: red; font-weight: bold; text-transform: uppercase;">{{ __('UN{{ __('PAID') }}') }}</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="invoice_form">
                                    <p><strong>{{ __('{{ __('{{ __('Inv') }}oice') }} {{ __('From') }}') }}:</strong></p>
                                    <p>{{$generalsetting->name}}</p>
                                    <p>{{$contact->{{ __('phone') }}}}</p>
                                    <p>{{$contact->email}}</p>
                                    <p>{{$contact->address}}</p>
                                    
                                    @if(!empty($order->order_note) || !empty($order->note))
                                        <p style="font-size:16px; line-height:1.8; color:#222; margin-top: 10px;">
                                            <strong>{{ __('Order {{ __('Note') }}') }}:</strong> {{ $order->order_note ?? $order->note }}
                                        </p>
                                    @endif
                                </div>
                            </td>

                            <td style="width:60%;float: left;">
                                <div class="invoice-bar" style="background:#00aef0; transform: skew(38deg); padding: 20px 60px; margin-left: 65px;">
                                    <p style="font-size: 30px; color: #fff; transform: skew(-38deg); text-align: right; font-weight: bold;">{{ __('{{ __('{{ __('Inv') }}oice') }}') }}</p>
                                </div>

                                <div class="invoice-bar" style="background:#fff; transform: skew(36deg); width: 80%; margin-left: 182px; padding: 12px 32px; margin-top: 6px;text-align:right">
                                   <p style="transform: skew(-36deg);display:inline-block">{{ __('{{ __('{{ __('Inv') }}oice') }} {{ __('Date') }}') }}: <strong>{{$order->created_at->format('d-m-y')}}</strong></p>
                                   <br>
                                   <p style="transform: skew(-36deg);display:inline-block">{{ __('{{ __('{{ __('Inv') }}oice') }} No') }}: <strong>{{$order->invoice_id}}</strong></p>
                                </div>

                                <div class="invoice_to" style="padding-top: 20px;">
                                    <p><strong>{{ __('{{ __('{{ __('Inv') }}oice') }} To') }}:</strong></p>
                                    <p>{{$order->shipping?$order->shipping->name:''}}</p>
                                    <p>{{$order->shipping?$order->shipping->{{ __('phone') }}:''}}</p>
                                    <p>{{$order->shipping?$order->shipping->address:''}}</p>
                                    <p>{{$order->shipping?$order->shipping->area:''}}</p>
                                </div>
                            </td>
                        </tr>
                    </table>

                    {{-- ===================== PRODUCTS TABLE ===================== --}}
                    <table class="table" style="margin-top: 30px;">
                        <thead style="background: #00aef0; color: #fff;">
                            <tr>
                                <th>{{ __('SL') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Qty') }}</th>
                                <th>{{ __('{{ __('Total') }}') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($order->orderdetails as $value)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>
                                    {{$value->product_name}} <br>
                                    @php
                                        $sizeDisplay = null;
                                        $colorDisplay = null;
                                        if ($value->size) {
                                            $sizeDisplay = $value->size->size{{ __('Name') }} ?? $value->size->size_name ?? $value->size->name ?? null;
                                        } elseif ($value->product_size) {
                                            $s = \App\Models\Size::find($value->product_size);
                                            $sizeDisplay = $s ? ($s->size{{ __('Name') }} ?? $s->size_name ?? null) : null;
                                        }
                                        if ($value->color) {
                                            $colorDisplay = $value->color->getDisplay{{ __('Name') }}() ?? $value->color->color{{ __('Name') }} ?? $value->color->color_name ?? $value->color->name ?? null;
                                        } elseif ($value->product_color) {
                                            $c = \App\Models\{{ __('Color') }}::find($value->product_color);
                                            $colorDisplay = $c ? ($c->getDisplay{{ __('Name') }}() ?? $c->color{{ __('Name') }} ?? $c->color_name ?? null) : null;
                                        }
                                    @endphp
                                    @if($sizeDisplay) <small>Size: {{ $sizeDisplay }}</small> @endif
                                    @if($colorDisplay) <small>{{ __('Color') }}: {{ $colorDisplay }}</small> @endif
                                </td>
                                <td>৳{{$value->sale_price}}</td>
                                <td>{{$value->qty}}</td>
                                <td>৳{{$value->sale_price * $value->qty}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- ===================== TOTAL CALCULATION ===================== --}}
                    @php
                        $sub{{ __('total') }} = ($order->amount + $order->discount) - $order->shipping_charge;
                        $shipping = $order->shipping_charge;
                        $discount = $order->discount;
                    @endphp

                    <div class="invoice-bottom">
                        <table class="table" style="width: 300px; float: right; margin-bottom: 30px;">
                            <tbody style="background:#00aef0; color:#fff;">

                                <tr>
                                    <td><strong>{{ __('Sub{{ __('Total') }}') }}</strong></td>
                                    <td><strong>৳{{$sub{{ __('total') }}}}</strong></td>
                                </tr>

                                <tr>
                                    <td><strong>{{ __('{{ __('Shipping') }}(+)') }}</strong></td>
                                    <td><strong>৳{{$shipping}}</strong></td>
                                </tr>

                                <tr>
                                    <td><strong>{{ __('{{ __('Discount') }}(-)') }}</strong></td>
                                    <td><strong>৳{{$discount}}</strong></td>
                                </tr>

                                <tr>
                                    <td><strong>{{ __('{{ __('Total') }} {{ __('Amount') }}') }}</strong></td>
                                    <td><strong>৳{{$grand_{{ __('total') }}}}</strong></td>
                                </tr>

                                {{-- ========== {{ __('Paid') }} & {{ __('Due') }} Display ========== --}}
                                @if($paid_amount > 0 && $due_amount > 0)
                                    {{-- পার্শিয়াল {{ __('bn_f0a1817c') }} (Advance) --}}
                                    <tr style="background:#27ae60;">
                                        <td><strong>{{ __('{{ __('Paid') }} / Advance') }}</strong></td>
                                        <td><strong>৳{{ number_format($paid_amount, 2) }}</strong></td>
                                    </tr>
                                    <tr style="background:#c0392b;">
                                        <td><strong>{{ __('{{ __('Due') }} {{ __('Amount') }}') }}</strong></td>
                                        <td><strong>৳{{ number_format($due_amount, 2) }}</strong></td>
                                    </tr>
                                @elseif($paid_amount >= $grand_{{ __('total') }})
                                    {{-- ফুল {{ __('bn_623a38a3') }} --}}
                                    <tr style="background:#27ae60;">
                                        <td><strong>{{ __('{{ __('Paid') }} {{ __('Amount') }}') }}</strong></td>
                                        <td><strong>৳{{ number_format($paid_amount, 2) }}</strong></td>
                                    </tr>
                                    <tr style="background:#2ecc71;">
                                        <td><strong>{{ __('{{ __('Due') }} {{ __('Amount') }}') }}</strong></td>
                                        <td><strong>৳{{ __('0.00') }}</strong></td>
                                    </tr>
                                @else
                                    {{-- আন{{ __('bn_623a38a3') }} --}}
                                    <tr style="background:#e74c3c;">
                                        <td><strong>{{ __('{{ __('Paid') }} {{ __('Amount') }}') }}</strong></td>
                                        <td><strong>৳{{ __('0.00') }}</strong></td>
                                    </tr>
                                    <tr style="background:#c0392b;">
                                        <td><strong>{{ __('{{ __('Due') }} {{ __('Amount') }}') }}</strong></td>
                                        <td><strong>৳{{ number_format($grand_{{ __('total') }}, 2) }}</strong></td>
                                    </tr>
                                @endif

                            </tbody>
                        </table>

                        <div class="terms-condition" style="overflow: hidden; width: 100%; text-align: center; padding: 20px 0;">
                            <h5 style="font-style: italic;">
                                <a href="{{route('page',['slug'=>'terms-condition'])}}">{{ __('{{ __('Terms') }} & Conditions') }}</a>
                            </h5>
                            <p style="text-align: center; font-style: italic; font-size: 15px;">{{ __('* This is a computer generated invoice.') }}</p>
                        </div>
                    </div>

                </div> 
            </div>
        </div>
    </div>
</section>

<script>
    function printFunction() {
        window.print();
    }
</script>

@endsection