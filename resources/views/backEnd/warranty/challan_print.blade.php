@extends('backEnd.layouts.master')
@section('title','Warranty Challan #' . $challan->challan_no)
@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
        .challan-box { box-shadow: none !important; border: 1px solid #000 !important; }
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
    .dual-copy {
        display: flex;
        gap: 30px;
    }
    .copy-label {
        text-align: center;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 2px;
        padding: 4px;
        margin-bottom: 15px;
    }
    .copy-customer { border: 2px dashed #1565c0; padding: 15px; flex: 1; }
    .copy-store { border: 2px dashed #e65100; padding: 15px; flex: 1; }
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
{{-- RECEIVE CHALLAN (Customer + Store copies)      --}}
{{-- ═══════════════════════════════════════════════ --}}
@if($challan->challan_type === 'receive')
<div class="dual-copy" style="max-width:800px;margin:0 auto;">
    {{-- Customer Copy --}}
    <div class="copy-customer">
        <div class="copy-label" style="color:#1565c0;">Customer Copy</div>
        <div class="challan-box" style="box-shadow:none;padding:10px;margin:0;">
            @include('backEnd.warranty._challan_receive_content', ['d' => $d])
        </div>
    </div>
    {{-- Store Copy --}}
    <div class="copy-store">
        <div class="copy-label" style="color:#e65100;">Store Copy</div>
        <div class="challan-box" style="box-shadow:none;padding:10px;margin:0;">
            @include('backEnd.warranty._challan_receive_content', ['d' => $d])
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════ --}}
{{-- SEND TO SUPPLIER (Supplier + Store copies)     --}}
{{-- ═══════════════════════════════════════════════ --}}
@if($challan->challan_type === 'send_to_supplier')
<div class="dual-copy" style="max-width:800px;margin:0 auto;">
    <div class="copy-customer">
        <div class="copy-label" style="color:#1565c0;">Supplier Copy</div>
        <div class="challan-box" style="box-shadow:none;padding:10px;margin:0;">
            @include('backEnd.warranty._challan_supplier_content', ['d' => $d])
        </div>
    </div>
    <div class="copy-store">
        <div class="copy-label" style="color:#e65100;">Store Copy</div>
        <div class="challan-box" style="box-shadow:none;padding:10px;margin:0;">
            @include('backEnd.warranty._challan_supplier_content', ['d' => $d])
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════ --}}
{{-- SUPPLIER RETURN / DELIVERY (single copy)       --}}
{{-- ═══════════════════════════════════════════════ --}}
@if(in_array($challan->challan_type, ['receive_return', 'delivery']))
<div class="challan-box">
    @if($challan->challan_type === 'receive_return')
        @include('backEnd.warranty._challan_return_content', ['d' => $d])
    @else
        @include('backEnd.warranty._challan_delivery_content', ['d' => $d])
    @endif
</div>
@endif

<div class="no-print text-center mt-4">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fa fa-print"></i> Print Challan
    </button>
</div>
@endsection
