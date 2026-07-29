@extends('backEnd.layouts.master')
@section('title','Warranty Challan #' . $challan->challan_no)
@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
        .challan-box { box-shadow: none !important; border: none; }
    }
    .challan-box {
        max-width: 800px;
        margin: 20px auto;
        background: #fff;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        font-family: 'Segoe UI', sans-serif;
    }
    .challan-header { border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
    .challan-header h3 { margin: 0; font-size: 20px; }
    .challan-header .challan-no { font-size: 14px; color: #666; }
    .challan-type-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
        margin: 5px 0;
    }
    .badge-receive { background: #e3f2fd; color: #1565c0; }
    .badge-send { background: #fff3e0; color: #e65100; }
    .badge-return { background: #e8f5e9; color: #2e7d32; }
    .badge-delivery { background: #fce4ec; color: #c62828; }

    .info-table { width: 100%; margin-bottom: 20px; }
    .info-table td { padding: 6px 10px; vertical-align: top; font-size: 14px; }
    .info-table .label { font-weight: 600; color: #555; width: 35%; }

    .section-title {
        font-size: 15px;
        font-weight: 700;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
        margin: 20px 0 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .footer-text {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
        text-align: center;
        font-size: 13px;
        color: #888;
    }

    /* ── Per-page layout for printing ── */
    .copy-page { max-width: 800px; margin: 20px auto; padding: 25px; border: 2px dashed #999; }
    .page-label { text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; padding: 8px 16px; margin-bottom: 15px; border-radius: 4px; }
    .page-customer { background: #e3f2fd; color: #1565c0; border: 2px solid #1565c0; }
    .page-supplier { background: #fff3e0; color: #e65100; border: 2px solid #e65100; }
    .page-store { background: #fce4ec; color: #c62828; border: 2px solid #c62828; }
    .sig-area { margin-top: 40px; width: 100%; border: none; }
    .sig-area td { width: 50%; padding: 0 15px; vertical-align: top; border: none; }
    .sig-line { border-top: 1px solid #333; padding-top: 6px; font-size: 12px; color: #666; text-align: center; }

    @media print {
        .copy-page { border: 0px solid #000 !important; box-shadow: none !important; page-break-after: always; }
        .copy-page:last-child { page-break-after: auto; }
    }
</style>

<div class="no-print text-end mb-3" style="max-width:800px;margin:10px auto;">
    <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="fa fa-print"></i> Print Challan
    </button>
    <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> Back
    </a>
</div>

@php $d = $challan->challan_data; @endphp

{{-- ═══════════════════════════════════════════════ --}}
{{-- RECEIVE CHALLAN (2 pages: Customer + Store)   --}}
{{-- ═══════════════════════════════════════════════ --}}
@if($challan->challan_type === 'receive')
{{-- Page 1: Customer Copy --}}
<div class="copy-page">
    <div class="page-label page-customer">Customer Copy</div>
    <div class="challan-box" style="box-shadow:none;padding:10px;margin:0;">
        @include('backEnd.warranty._challan_receive_content', ['d' => $d])
    </div>
    <table class="sig-area"><tr>
        <td><div class="sig-line">Customer Signature</div></td>
        <td><div class="sig-line">Store Representative</div></td>
    </tr></table>
</div>
{{-- Page 2: Store Copy --}}
<div class="copy-page">
    <div class="page-label page-store">Store Copy</div>
    <div class="challan-box" style="box-shadow:none;padding:10px;margin:0;">
        @include('backEnd.warranty._challan_receive_content', ['d' => $d])
    </div>
    <table class="sig-area"><tr>
        <td><div class="sig-line">Customer Signature</div></td>
        <td><div class="sig-line">Store Representative</div></td>
    </tr></table>
</div>
@endif

{{-- ═══════════════════════════════════════════════ --}}
{{-- SEND TO SUPPLIER (2 pages: Supplier + Store)  --}}
{{-- ═══════════════════════════════════════════════ --}}
@if($challan->challan_type === 'send_to_supplier')
{{-- Page 1: Supplier Copy --}}
<div class="copy-page">
    <div class="page-label page-supplier">Supplier Copy</div>
    <div class="challan-box" style="box-shadow:none;padding:10px;margin:0;">
        @include('backEnd.warranty._challan_supplier_content', ['d' => $d])
    </div>
    <table class="sig-area"><tr>
        <td><div class="sig-line">Supplier Signature</div></td>
        <td><div class="sig-line">Store Representative</div></td>
    </tr></table>
</div>
{{-- Page 2: Store Copy --}}
<div class="copy-page">
    <div class="page-label page-store">Store Copy</div>
    <div class="challan-box" style="box-shadow:none;padding:10px;margin:0;">
        @include('backEnd.warranty._challan_supplier_content', ['d' => $d])
    </div>
    <table class="sig-area"><tr>
        <td><div class="sig-line">Supplier Signature</div></td>
        <td><div class="sig-line">Store Representative</div></td>
    </tr></table>
</div>
@endif

{{-- ═══════════════════════════════════════════════ --}}
{{-- SUPPLIER RETURN / DELIVERY (single page)       --}}
{{-- ═══════════════════════════════════════════════ --}}
@if(in_array($challan->challan_type, ['receive_return', 'delivery']))
<div class="copy-page">
    @if($challan->challan_type === 'receive_return')
        @include('backEnd.warranty._challan_return_content', ['d' => $d])
    @else
        @include('backEnd.warranty._challan_delivery_content', ['d' => $d])
    @endif
    <table class="sig-area"><tr>
        <td><div class="sig-line">Customer Signature</div></td>
        <td><div class="sig-line">Store Representative</div></td>
    </tr></table>
</div>
@endif

<div class="no-print text-center mt-4">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fa fa-print"></i> Print Challan
    </button>
</div>

{{-- Auto-open print dialog when opened from "Print" button --}}
<script>
    @if(request()->has('autoprint'))
        window.onload = function() { setTimeout(function() { window.print(); }, 500); };
    @endif
</script>
@endsection
