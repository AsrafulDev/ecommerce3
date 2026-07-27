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
                <a href="/admin/order/all" class="no-print"><strong><i class="fe-arrow-left"></i> {{ __('Back To Order') }} </strong></a>
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
                                    <p><strong>Payment Gateway:</strong> {{ $order->payment ? strtoupper($order->payment->payment_method) : 'N/A' }}</p>
                                    <p><strong>Payment Status:</strong></p>
                                    <select id="payment_status_{{ $order->id }}" class="form-control no-print" style="width:auto; display:inline-block;">
                                        <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                        <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}> {{ __('Paid') }} </option>
                                        <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}> {{ __('Unpaid') }} </option>
                                        <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                                    </select>
                                    <button class="btn btn-sm btn-success no-print" onclick="updatePaymentStatus({{ $order->id }})">{{ __('Update') }}</button>
                                </div>
                                
                                <!-- 🌟 NEW: System-Driven Order Status (Read-Only) + Action Buttons -->
                                <div style="margin-bottom:15px; padding: 12px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #4DBC60;">
                                    <p style="margin-bottom: 8px;">
                                        <strong>Order Status:</strong> 
                                        @php
                                            $statusEnum = \App\Enums\OrderStatus::tryFrom($order->order_status);
                                            $statusLabel = $statusEnum ? $statusEnum->label() : ($order->status->name ?? 'N/A');
                                            $badgeClass = $statusEnum ? $statusEnum->badgeClass() : 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badgeClass }}" style="font-size:14px; padding:6px 12px;">
                                            {{ $statusLabel }}
                                        </span>
                                        @if($order->order_type)
                                            <small class="text-muted">| Type: {{ ucfirst($order->order_type) }}</small>
                                        @endif
                                    </p>
                                    
                                    {{-- Courier info if shipped --}}
                                    @if($order->courier_type)
                                    <small class="text-muted d-block mb-2">
                                        <i class="fa fa-truck"></i> Courier: {{ ucfirst($order->courier_type) }}
                                        @if($order->courier_tracking_id)
                                            | Tracking: <strong>{{ $order->courier_tracking_id }}</strong>
                                        @endif
                                        @if($order->courier_sent_at)
                                            | Sent: {{ \Carbon\Carbon::parse($order->courier_sent_at)->format('d M Y h:i A') }}
                                        @endif
                                    </small>
                                    @endif

                                    {{-- Action Buttons + Quick Dropdown --}}
                                    @php 
                                        $hasActions = !empty($availableActions) && is_array($availableActions) && count($availableActions) > 0;
                                        $pipeline = $pipelineActions ?? $availableActions ?? [];
                                    @endphp
                                    @if($hasActions)
                                    <div class="no-print mt-2">
                                        <strong style="font-size:12px; color:#666;">Quick Actions (Next Step + Pipeline):</strong><br>
                                        {{-- Quick dropdown shows ALL downstream steps --}}
                                        <div class="input-group" style="max-width: 360px;">
                                            <select id="quick_action_select" class="form-select form-select-sm" style="border-radius: 4px 0 0 4px;">
                                                <option value="">— Select Pipeline Step —</option>
                                                @foreach($pipeline as $action)
                                                    <option value="{{ $action['action'] }}" data-next="{{ $action['next_status'] }}">
                                                        {{ $action['label'] }} @if(!in_array($action['action'], array_column($availableActions, 'action'))) (skip) @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-sm btn-success" onclick="performQuickAction({{ $order->id }})" style="border-radius: 0 4px 4px 0;">
                                                <i class="fa fa-bolt"></i> Go
                                            </button>
                                        </div>
                                        {{-- Individual next-step action buttons --}}
                                        <div class="mt-1">
                                        @foreach($availableActions as $action)
                                            <button class="btn btn-xs btn-{{ $action['class'] }} mt-1" 
                                                    onclick="performAction('{{ $action['action'] }}', {{ $order->id }})"
                                                    style="margin-right: 3px; margin-bottom: 3px;"
                                                    title="{{ $action['label'] }}">
                                                <i class="fa {{ $action['icon'] }}"></i> {{ $action['label'] }}
                                            </button>
                                        @endforeach
                                        </div>
                                    </div>
                                    @else
                                    <small class="text-muted no-print"><i class="fa fa-lock"></i> No further actions available (terminal status)</small>
                                    @endif
                                </div>

                                {{-- Ship Order Modal (hidden by default) --}}
                                <div id="shipModal" class="no-print" style="display:none; padding:10px; background:#fff3cd; border-radius:5px; margin-bottom:10px;">
                                    <strong><i class="fa fa-truck"></i> Ship Order</strong>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <input type="text" id="courier_type" class="form-control form-control-sm" placeholder="Courier type (e.g. Steadfast)">
                                        </div>
                                        <div class="col-6">
                                            <input type="text" id="courier_tracking_id" class="form-control form-control-sm" placeholder="Tracking ID">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <textarea id="ship_note" class="form-control form-control-sm" rows="1" placeholder="Optional note..."></textarea>
                                    </div>
                                    <button class="btn btn-sm btn-info mt-2" onclick="submitShip({{ $order->id }})">
                                        <i class="fa fa-paper-plane"></i> Confirm Shipment
                                    </button>
                                    <button class="btn btn-sm btn-secondary mt-2" onclick="document.getElementById('shipModal').style.display='none'">Cancel</button>
                                </div>

                                {{-- Action Note Modal (used by performAction) --}}
                                <div id="actionNoteModal" class="no-print" style="display:none; padding:10px; background:#e3f2fd; border-radius:5px; margin-bottom:10px; border:1px solid #90caf9;">
                                    <strong><i class="fa fa-pencil"></i> Add Note (Optional)</strong>
                                    <textarea id="action_note_input" class="form-control form-control-sm mt-1" rows="2" placeholder="Optional note..."></textarea>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-primary" onclick="confirmAction()"><i class="fa fa-check"></i> Confirm</button>
                                        <button class="btn btn-sm btn-secondary" onclick="cancelAction()">Cancel</button>
                                    </div>
                                </div>

                                {{-- Add Admin Note --}}
                                <div class="no-print mt-3" style="padding: 10px; background: #e8f4fd; border-radius: 5px;">
                                    <strong style="font-size:13px;"><i class="fa fa-sticky-note"></i> Add Admin Note</strong>
                                    <textarea id="admin_note_input" class="form-control form-control-sm mt-1" rows="2" placeholder="Type your note here..."></textarea>
                                    <div class="mt-1">
                                        <select id="note_type_select" class="form-control form-control-sm" style="width:auto; display:inline-block;">
                                            <option value="info">Info</option>
                                            <option value="warning">Warning</option>
                                            <option value="success">Success</option>
                                            <option value="danger">Danger</option>
                                        </select>
                                        <button class="btn btn-sm btn-primary" onclick="addNote({{ $order->id }})">
                                            <i class="fa fa-plus"></i> Add Note
                                        </button>
                                    </div>
                                </div>
                                <!-- ✅ END -->

                                <div class="invoice_form">
                                    <p style="font-size:16px;line-height:1.8;color:#222"><strong>Invoice From:</strong></p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{$generalsetting->name}}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{ $contact->phone ?? '' }}</p>
                                    <p style="font-size:16px;line-height:1.8;color:#222">{{ $contact->email ?? '' }}</p>
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
                                    <p style="font-size: 30px; color: #fff; transform: skew(-38deg); text-transform: uppercase; text-align: right; font-weight: bold;">{{ __('Invoice') }}</p>
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
                                <th>{{ __('SL') }}</th>
                                <th> {{ __('Product') }} </th>
                                <th>{{ __('Unit Price') }}</th>
                                <th>{{ __('Discount') }}</th>
                                <th>{{ __('Warranty') }}</th>
                                <th>{{ __('Qty') }}</th>
                                <th>{{ __('Subtotal') }}</th>
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
                                // 💰 Product discount (wholesale)
                                $productDiscount = (float) ($value->product_discount ?? 0);

                                // 🛡️ Warranty price — from tier's additional_cost (correct value)
                                $warrantyPrice = 0;
                                if ($value->warranty_tier_id) {
                                    $warrantyTier = \App\Models\ProductWarrantyTier::find($value->warranty_tier_id);
                                    $warrantyPrice = $warrantyTier ? (float)($warrantyTier->additional_cost ?? 0) : 0;
                                }

                                if ($isResellerOrderItem && $customPrice && $totalProductValue > 0) {
                                    $thisProductValue = $value->sale_price * $value->qty;
                                    $thisProductShare = ($thisProductValue / $totalProductValue) * $customPrice;
                                    $displayPrice = $thisProductShare / $value->qty;
                                } else {
                                    // Show actual sale_price — the price customer paid per unit
                                    $displayPrice = $value->sale_price;
                                }

                                // Per-unit subtotal (always = sale_price × qty)
                                $lineSubtotal = $value->sale_price * $value->qty;
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
                                {{-- 🛡️ Warranty Info --}}
                                @php
                                    $wt = null; $ws = null; $showWarranty = false;
                                    if ($value->warranty_tier_id) {
                                        $wt = \App\Models\ProductWarrantyTier::find($value->warranty_tier_id);
                                        $ws = \App\Models\WarrantySale::where('order_detail_id', $value->id)->first();
                                        if ($wt && $wt->warranty_days > 0) { $showWarranty = true; }
                                    }
                                @endphp
                                @if($showWarranty)
                                    <br><small class="text-success">
                                        🛡️ {{ $wt->tier_name }}
                                        @if($ws && $ws->warranty_end_date)
                                            <br>— Expires: {{ $ws->warranty_end_date->format('d M, Y') }}
                                        @endif
                                    </small>
                                    {{-- supplier warranty info if available --}}
                                @elseif($value->supplier_warranty_days > 0)
                                    <br><small class="text-success">🛡️ Supplier Warranty — Expires: {{ \Carbon\Carbon::parse($value->supplier_warranty_end_date)->format('d M, Y') }}</small>
                                @else
                                    <br><small class="text-muted">— No Warranty —</small>
                                @endif
                                </td>
                                <td>৳{{ number_format($displayPrice, 2) }}</td>
                                <td>
                                    @if($productDiscount > 0)
                                        <span class="text-danger">-৳{{ number_format($productDiscount, 2) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($warrantyPrice > 0)
                                        <span class="text-primary">+৳{{ number_format($warrantyPrice, 2) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{$value->qty}}</td>
                                <td>৳{{ number_format($lineSubtotal, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="invoice-bottom">
                       @php
    // Check if this is a reseller order
    // Reseller orders ALWAYS have customer_payable_amount field set
    $isResellerOrder = !empty($order->customer_payable_amount);

    $shipping = $order->shipping_charge;
    $discount = $order->discount;

    // 💰 Total product-level discount (wholesale)
    $totalProductDiscount = 0;
    foreach ($order->orderdetails as $item) {
        $totalProductDiscount += ((float) ($item->product_discount ?? 0)) * $item->qty;
    }

    // 🛡️ Total warranty charges
    $totalWarrantyCharge = 0;
    foreach ($order->orderdetails as $item) {
        $totalWarrantyCharge += ((float) ($item->warranty_price ?? 0)) * $item->qty;
    }

    // ⭐ Subtotal = sum of per-item line subtotals (includes warranty per-item, no separate row needed)
    if ($isResellerOrder && $order->customer_payable_amount) {
        $subtotal = $order->customer_payable_amount - $order->shipping_charge;
    } else {
        $subtotal = 0;
        foreach ($order->orderdetails as $item) {
            $subtotal += ($item->sale_price * $item->qty);
        }
    }
    
    // If reseller order, use customer_payable_amount, otherwise use amount
    $finalTotal = $isResellerOrder ? $order->customer_payable_amount : $order->amount;

    // Payment Table থেকে নেওয়া Paid/Advance Amount
    $advancePaid = \App\Models\Payment::where('order_id', $order->id)->sum('amount');

    // Due Amount
    $dueAmount = $finalTotal - $advancePaid;
@endphp

<table class="table" style="width: 350px; float: right; margin-bottom: 30px;">
    <tbody style="background:#f1f9f8">
        @if($isResellerOrder)
            <tr style="background:#ffc107;color:#000">
                <td><strong><i class="fa fa-user-tag"></i> {{ __('Reseller Order') }} </strong></td>
                <td></td>
            </tr>
        @endif
        <tr>
            <td><strong> {{ __('SubTotal') }} </strong></td>
            <td><strong>৳{{ number_format($subtotal, 2) }}</strong></td>
        </tr>
        @if($totalProductDiscount > 0)
        <tr>
            <td><strong> {{ __('Wholesale Discount(-)') }} </strong></td>
            <td><strong class="text-danger">-৳{{ number_format($totalProductDiscount, 2) }}</strong></td>
        </tr>
        @endif
        <tr>
            <td><strong> {{ __('Shipping(+)') }} </strong></td>
            <td><strong>৳{{ number_format($shipping, 2) }}</strong></td>
        </tr>
        <tr>
            <td><strong> {{ __('Coupon Discount(-)') }} </strong></td>
            <td><strong>৳{{ number_format($discount, 2) }}</strong></td>
        </tr>

        <tr style="background:#4DBC60;color:#fff">
            <td><strong>{{ $isResellerOrder ? 'Customer Payable Amount' : 'Final Total' }}</strong></td>
            <td><strong>৳{{ number_format($finalTotal, 2) }}</strong></td>
        </tr>

        {{-- 🔥 যদি Advance Payment থাকে --}}
        @if($advancePaid > 0 && $advancePaid < $finalTotal)
            <tr>
                <td><strong> {{ __('Advance Paid') }} </strong></td>
                <td><strong>৳{{ number_format($advancePaid, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong> {{ __('Due Amount') }} </strong></td>
                <td><strong>৳{{ number_format($dueAmount, 2) }}</strong></td>
            </tr>
        @endif
    </tbody>
</table>


                        <div class="terms-condition" style="overflow: hidden; width: 100%; text-align: center; padding: 20px 0; border-top: 1px solid #ddd;">
                            <h5 style="font-style: italic;"><a href="{{route('page',['slug'=>'terms-condition'])}}">{{ __('Terms & Conditions') }}</a></h5>
                            <p style="text-align: center; font-style: italic; font-size: 15px; margin-top: 10px;">* This is a computer generated invoice, does not require any signature.</p>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════ --}}
                    {{-- 🌟 NOTE HISTORY TIMELINE --}}
                    {{-- ═══════════════════════════════════════════ --}}
                    @php
                        $allNotes = $order->notes()->with('user:id,name')->get();
                    @endphp
                    @if($allNotes->count() > 0)
                    <div class="no-print" style="margin-top: 20px; width: 760px; margin-left: auto; margin-right: auto;">
                        <div style="border-top: 2px solid #4DBC60; padding-top: 15px;">
                            <h5 style="margin-bottom: 15px;"><i class="fa fa-history"></i> Order Activity Log ({{ $allNotes->count() }} entries)</h5>
                            <div style="max-height: 400px; overflow-y: auto;">
                                @foreach($allNotes as $nt)
                                <div style="display: flex; margin-bottom: 10px; padding: 8px; background: #fafafa; border-radius: 4px; border-left: 3px solid 
                                    @if($nt->type == 'success') #28a745
                                    @elseif($nt->type == 'warning') #ffc107
                                    @elseif($nt->type == 'danger') #dc3545
                                    @else #17a2b8 @endif
                                ;">
                                    <div style="min-width: 40px; text-align: center; padding-top: 2px;">
                                        <i class="fa 
                                            @if($nt->type == 'success') fa-check-circle text-success
                                            @elseif($nt->type == 'warning') fa-exclamation-triangle text-warning
                                            @elseif($nt->type == 'danger') fa-times-circle text-danger
                                            @else fa-info-circle text-info @endif
                                        " style="font-size:18px;"></i>
                                    </div>
                                    <div style="flex:1; padding-left: 10px;">
                                        <div style="font-size: 13px; color: #333;">{{ $nt->content }}</div>
                                        <div style="font-size: 11px; color: #888; margin-top: 2px;">
                                            <span><i class="fa fa-user"></i> {{ $nt->user->name ?? 'System' }}</span>
                                            <span style="margin-left: 10px;"><i class="fa fa-clock-o"></i> {{ $nt->created_at->format('d M Y, h:i A') }} ({{ $nt->created_at->diffForHumans() }})</span>
                                            <span style="margin-left: 10px;">
                                                <span class="badge bg-{{ $nt->source == 'system' ? 'secondary' : 'primary' }}" style="font-size:10px;">{{ $nt->source }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

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
    $pmethod = strtoupper($order->payment ? $order->payment->payment_method : 'N/A');
    $pstatus = $order->payment_status ?? ($order->payment ? $order->payment->payment_status : 'pending');
    $adv     = \App\Models\Payment::where('order_id',$order->id)->sum('amount');
    $due     = $ftotal - $adv;
    $trkId   = $order->courier_tracking_id ?? $order->consignment_id ?? null;
    $courier = $order->courier_type ?? ($trkId ? 'steadfast' : null);
@endphp
<div class="pos-receipt">
    <div class="rh">
        <div class="shop">{{ $generalsetting->name }}</div>
        @if($contact && $contact->address) <p>{{ $contact->address }}</p> @endif
        @if($contact && $contact->phone) <p>Phone: {{ $contact->phone }}</p> @endif
        @if($contact && $contact->email) <p>{{ $contact->email }}</p> @endif
    </div>
    <div class="rt"> {{ __('POS Invoice') }} </div>
    <div class="rm">
        <div class="fl">
            <span>Bill No. : <strong>{{ $order->invoice_id }}</strong></span>
            <span>{{ $order->created_at->format('H:i') }} hrs</span>
        </div>
        <div class="fl"><span>Date &nbsp;&nbsp;: <strong>{{ $order->created_at->format('d-m-Y') }}</strong></span></div>
        @if($order->shipping && $order->shipping->name)
        <div class="fl"><span>Buyer &nbsp;&nbsp;: <strong>{{ $order->shipping->name }}</strong></span></div>
        @endif
        @if($order->shipping && $order->shipping->phone) <div class="fl"><span>Phone &nbsp;&nbsp;: {{ $order->shipping->phone }}</span></div>
        @endif
        @if($order->shipping && ($order->shipping->address || $order->shipping->area))
        <div class="fl"><span>Address : {{ $order->shipping->address }}{{ $order->shipping->area ? ', '.$order->shipping->area : '' }}</span></div>
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:14px;">#</th>
                <th> {{ __('Product') }} </th>
                <th style="width:22px;text-align:center;">{{ __('Qty') }}</th>
                <th style="width:44px;" class="r"> {{ __('Rate') }} </th>
                <th style="width:48px;" class="r">{{ __('Total') }}</th>
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
    <div class="rs"><span>{{ __('Subtotal') }}</span><span>{{ number_format($sub,2) }}</span></div>
    @if($order->discount > 0)<div class="rs"><span>Discount (–)</span><span>{{ number_format($order->discount,2) }}</span></div>@endif
    @if($order->shipping_charge > 0)<div class="rs"><span> {{ __('Delivery (+)') }} </span><span>{{ number_format($order->shipping_charge,2) }}</span></div>@endif
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
        <div class="ptotal"><span> {{ __('Total Paid') }} </span><span>&#2547; {{ number_format($ftotal,2) }}</span></div>
    </div>
    <hr class="dash">
    <div class="rs"><span>Order Status :</span><span><strong>{{ $statusLabel ?? ($order->status ? $order->status->name : 'Processing') }}</strong></span></div>
    <div class="rf">
        <div class="ty"> {{ __('Thank You!') }} </div>
        <div> {{ __('Visit Again!') }} </div>
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
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(data.message, 'Error!');
        }
    })
    .catch(err => {
        toastr.error('Something went wrong!', 'Error!');
    });
}

// ═══════════════════════════════════════════════════
// 🌟 NEW: Action-Based Order Management
// ═══════════════════════════════════════════════════

let currentAction = null;
let currentOrderId = null;

function performQuickAction(orderId) {
    let select = document.getElementById('quick_action_select');
    let action = select.value;
    if (!action) {
        toastr.warning('Please select an action first', 'Warning!');
        return;
    }
    select.value = ''; // reset
    performAction(action, orderId);
}

function performAction(action, orderId) {
    // Ship action needs extra modal with courier fields
    if (action === 'ship') {
        document.getElementById('shipModal').style.display = 'block';
        return;
    }

    currentAction = action;
    currentOrderId = orderId;
    
    // Show the action note modal
    document.getElementById('actionNoteModal').style.display = 'block';
    document.getElementById('action_note_input').value = '';
    document.getElementById('action_note_input').focus();
}

function confirmAction() {
    let note = document.getElementById('action_note_input').value.trim();
    document.getElementById('actionNoteModal').style.display = 'none';
    
    if (!currentAction || !currentOrderId) return;
    
    let routes = {
        'confirm': '{{ route("admin.order.confirm") }}',
        'start_picking': '{{ route("admin.order.startPicking") }}',
        'start_packing': '{{ route("admin.order.startPacking") }}',
        'mark_packed': '{{ route("admin.order.markPacked") }}',
        'out_for_delivery': '{{ route("admin.order.outForDelivery") }}',
        'deliver': '{{ route("admin.order.deliver") }}',
        'complete': '{{ route("admin.order.complete") }}',
        'request_return': '{{ route("admin.order.requestReturn") }}',
        'approve_return': '{{ route("admin.order.approveReturn") }}',
        'mark_returned': '{{ route("admin.order.markReturned") }}',
        'close': '{{ route("admin.order.close") }}',
        'cancel': '{{ route("admin.order.cancel") }}',
    };

    let url = routes[currentAction];
    if (!url) {
        toastr.error('Unknown action: ' + currentAction);
        return;
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order_id: currentOrderId, note: note })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success(data.message, 'Success!');
            setTimeout(() => location.reload(), 800);
        } else {
            toastr.error(data.message || 'Action failed', 'Error!');
        }
    })
    .catch(err => {
        toastr.error('Something went wrong!', 'Error!');
        console.error(err);
    });
    
    currentAction = null;
    currentOrderId = null;
}

function cancelAction() {
    document.getElementById('actionNoteModal').style.display = 'none';
    currentAction = null;
    currentOrderId = null;
}

function submitShip(orderId) {
    let courierType = document.getElementById('courier_type').value;
    let trackingId = document.getElementById('courier_tracking_id').value;
    let note = document.getElementById('ship_note').value;

    fetch('{{ route("admin.order.ship") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            order_id: orderId,
            courier_type: courierType,
            courier_tracking_id: trackingId,
            note: note
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success(data.message, 'Success!');
            setTimeout(() => location.reload(), 800);
        } else {
            toastr.error(data.message || 'Ship failed', 'Error!');
        }
    })
    .catch(err => {
        toastr.error('Something went wrong!', 'Error!');
        console.error(err);
    });
}

function addNote(orderId) {
    let note = document.getElementById('admin_note_input').value.trim();
    let type = document.getElementById('note_type_select').value;

    if (!note) {
        toastr.warning('Please enter a note', 'Warning!');
        return;
    }

    fetch('{{ route("admin.order.addNote") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order_id: orderId, note: note, type: type })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success(data.message, 'Success!');
            document.getElementById('admin_note_input').value = '';
            setTimeout(() => location.reload(), 800);
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
