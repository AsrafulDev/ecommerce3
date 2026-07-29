@extends('frontEnd.layouts.customer.panel')

@php
    $pageTitle = 'Challan #' . $challan->challan_no;
    $headerTitle = 'Challan #' . $challan->challan_no;
    $headerSubtitle = $challan->challan_type_label . ' | ' . $challan->created_at->format('d M, Y h:i A');
    $d = $challan->challan_data;
    $claim = $challan->warrantyClaim;
@endphp

@push('styles')
<style>
    @media print {
        body * { visibility: hidden; }
        .printable-area, .printable-area * { visibility: visible; }
        .printable-area { position: absolute; left: 0; top: 0; width: 100%; }
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
        border-radius: 12px;
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

    .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
    .info-table td { padding: 8px 12px; vertical-align: top; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
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
</style>
@endpush

@section('content')
<div class="printable-area">
    {{-- Print Controls --}}
    <div class="no-print max-w-[800px] mx-auto mb-4 flex justify-between items-center">
        <a href="{{ route('customer.warranty.track', $claim->id) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition">
            <i class="fas fa-arrow-left"></i> Back to Claim
        </a>
        <button onclick="window.print()" class="flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition shadow-sm">
            <i class="fas fa-print"></i> Print / Download
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- RECEIVE CHALLAN                               --}}
    {{-- ═══════════════════════════════════════════════ --}}
    @if($challan->challan_type === 'receive')
    <div class="challan-box" style="border-left: 4px solid #1565c0;">
        <div class="challan-header">
            <h3>{{ config('app.name', 'Store') }}</h3>
            <div class="challan-no">Challan #: <strong>{{ $d['challan_no'] }}</strong></div>
            <span class="challan-type-badge badge-receive">Product Receive</span>
            <div style="float:right;font-size:13px;color:#888;">{{ $d['date'] ?? '' }}</div>
        </div>

        <p class="text-sm text-gray-500 mb-4">This challan confirms that your product has been received at our service center.</p>

        <table class="info-table">
            <tr><td class="label">Customer Name</td><td>: {{ $d['customer_name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Customer Phone</td><td>: {{ $d['customer_phone'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Claim Number</td><td>: {{ $d['claim_number'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Product</td><td>: {{ $d['product_name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Serial Number</td><td>: {{ $d['serial_number'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Product Condition</td><td>: {{ $d['received_condition'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Accessories</td><td>: {{ $d['accessories'] ?? 'None' }}</td></tr>
        </table>

        @if(!empty($d['issue_description']))
        <div class="section-title">Issue</div>
        <p class="text-sm text-gray-600">{{ $d['issue_description'] }}</p>
        @endif

        @if(!empty($d['notes']))
        <div class="section-title">Notes</div>
        <p class="text-sm text-gray-600">{{ $d['notes'] }}</p>
        @endif

        <div class="footer-text">{{ $d['footer_text'] ?? 'This is a computer-generated challan.' }}</div>
        <div style="display:flex;justify-content:space-between;margin-top:40px;">
            <div class="text-sm">Customer Signature: _______________</div>
            <div class="text-sm">Store Signature: _______________</div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- SEND TO SUPPLIER (informational only)         --}}
    {{-- ═══════════════════════════════════════════════ --}}
    @if($challan->challan_type === 'send_to_supplier')
    <div class="challan-box" style="border-left: 4px solid #e65100;">
        <div class="challan-header">
            <h3>{{ config('app.name', 'Store') }}</h3>
            <div class="challan-no">Challan #: <strong>{{ $d['challan_no'] }}</strong></div>
            <span class="challan-type-badge badge-send">Sent to Supplier</span>
            <div style="float:right;font-size:13px;color:#888;">{{ $d['date'] ?? '' }}</div>
        </div>

        <p class="text-sm text-gray-500 mb-4">Your product has been sent to the supplier/manufacturer for further inspection and service.</p>

        <table class="info-table">
            <tr><td class="label">Customer Name</td><td>: {{ $d['customer_name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Claim Number</td><td>: {{ $d['claim_number'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Product</td><td>: {{ $d['product_name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Serial Number</td><td>: {{ $d['serial_number'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Supplier</td><td>: {{ $d['supplier_name'] ?? 'N/A' }}</td></tr>
        </table>

        @if(!empty($d['notes']))
        <div class="section-title">Notes</div>
        <p class="text-sm text-gray-600">{{ $d['notes'] }}</p>
        @endif

        <div class="footer-text">{{ $d['footer_text'] ?? 'This is a computer-generated challan.' }}</div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- DELIVERY CHALLAN (return to customer)         --}}
    {{-- ═══════════════════════════════════════════════ --}}
    @if($challan->challan_type === 'delivery')
    <div class="challan-box" style="border-left: 4px solid #2e7d32;">
        <div class="challan-header">
            <h3>{{ config('app.name', 'Store') }}</h3>
            <div class="challan-no">Challan #: <strong>{{ $d['challan_no'] }}</strong></div>
            <span class="challan-type-badge badge-delivery">Customer Delivery</span>
            <div style="float:right;font-size:13px;color:#888;">{{ $d['date'] ?? '' }}</div>
        </div>

        <p class="text-sm text-gray-500 mb-4">This challan confirms the delivery of your serviced product.</p>

        <table class="info-table">
            <tr><td class="label">Customer Name</td><td>: {{ $d['customer_name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Customer Phone</td><td>: {{ $d['customer_phone'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Claim Number</td><td>: {{ $d['claim_number'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Product</td><td>: {{ $d['product_name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Serial Number</td><td>: {{ $d['serial_number'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Service Performed</td><td>: {{ $d['service_performed'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Return Condition</td><td>: {{ $d['delivery_condition'] ?? 'Working' }}</td></tr>
        </table>

        @if(!empty($d['notes']))
        <div class="section-title">Notes</div>
        <p class="text-sm text-gray-600">{{ $d['notes'] }}</p>
        @endif

        <div class="footer-text">{{ $d['footer_text'] ?? 'This is a computer-generated challan.' }}</div>

        @if(!empty($d['return_policy']))
        <div class="section-title">Post-Service Policy</div>
        <p class="text-sm text-gray-600">{{ $d['return_policy'] }}</p>
        @endif

        <div style="display:flex;justify-content:space-between;margin-top:40px;">
            <div class="text-sm">Customer Signature: _______________</div>
            <div class="text-sm">Store Signature: _______________</div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- SUPPLIER RETURN (informational only)          --}}
    {{-- ═══════════════════════════════════════════════ --}}
    @if($challan->challan_type === 'receive_return')
    <div class="challan-box" style="border-left: 4px solid #e91e63;">
        <div class="challan-header">
            <h3>{{ config('app.name', 'Store') }}</h3>
            <div class="challan-no">Challan #: <strong>{{ $d['challan_no'] }}</strong></div>
            <span class="challan-type-badge badge-return">Supplier Return</span>
            <div style="float:right;font-size:13px;color:#888;">{{ $d['date'] ?? '' }}</div>
        </div>

        <p class="text-sm text-gray-500 mb-4">Your product has been returned from the supplier and is back at our service center.</p>

        <table class="info-table">
            <tr><td class="label">Customer Name</td><td>: {{ $d['customer_name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Claim Number</td><td>: {{ $d['claim_number'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Product</td><td>: {{ $d['product_name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Serial Number</td><td>: {{ $d['serial_number'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Supplier</td><td>: {{ $d['supplier_name'] ?? 'N/A' }}</td></tr>
            <tr><td class="label">Return Status</td><td>: {{ $d['return_type'] ?? 'N/A' }}</td></tr>
        </table>

        @if(!empty($d['notes']))
        <div class="section-title">Notes</div>
        <p class="text-sm text-gray-600">{{ $d['notes'] }}</p>
        @endif

        <div class="footer-text">{{ $d['footer_text'] ?? 'This is a computer-generated challan.' }}</div>
    </div>
    @endif

    {{-- Bottom Print Button --}}
    <div class="no-print max-w-[800px] mx-auto text-center mt-6 mb-10">
        <button onclick="window.print()" class="flex items-center gap-2 mx-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition shadow-sm">
            <i class="fas fa-download"></i> Download / Print Challan
        </button>
    </div>
</div>
@endsection
