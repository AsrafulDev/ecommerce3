{{-- Supplier Return Challan Content --}}
<div class="challan-header">
    <h3>{{ optional(\App\Models\GeneralSetting::first())->name ?: config('app.name', 'Store') }}</h3>
    <div class="challan-no">Challan #: <strong>{{ $d['challan_no'] }}</strong></div>
    <span class="challan-type-badge badge-return">Supplier Return</span>
    <div style="float:right;font-size:13px;">Date: {{ $d['date'] }}</div>
</div>

<div class="section-title">Supplier Information</div>
<table class="info-table">
    <tr><td class="label">Supplier</td><td>: {{ $d['supplier_name'] }}</td></tr>
    <tr><td class="label">Supplier Return Challan</td><td>: {{ $d['supplier_return_challan'] }}</td></tr>
</table>

<div class="section-title">Product Information</div>
<table class="info-table">
    <tr><td class="label">Product</td><td>: {{ $d['product_name'] }}</td></tr>
    <tr><td class="label">Original SN</td><td>: {{ $d['original_sn'] }}</td></tr>
    @if(!empty($d['replacement_sn']))
    <tr><td class="label">New SN</td><td>: <strong style="color:#2e7d32;">{{ $d['replacement_sn'] }}</strong></td></tr>
    @endif
    <tr><td class="label">Return Type</td><td>: <strong>{{ ucfirst($d['return_type']) }}</strong></td></tr>
    <tr><td class="label">Claim Number</td><td>: {{ $d['claim_number'] }}</td></tr>
    @if(($d['supplier_charge'] ?? 0) > 0)
    <tr><td class="label">Supplier Charge</td><td>: ৳{{ number_format($d['supplier_charge'], 2) }}</td></tr>
    @endif
</table>

<div class="section-title">Claim Cause / Issue Details</div>
<table class="info-table">
    <tr><td class="label">Issue Type</td><td>: {{ $d['issue_type'] ?? 'N/A' }}</td></tr>
    <tr><td class="label">Issue Description</td><td>: {{ $d['issue_description'] ?? 'N/A' }}</td></tr>
</table>

@if(!empty($d['notes']))
<div class="section-title">Notes</div>
<p>{{ $d['notes'] }}</p>
@endif

<div class="footer-text">{{ $d['footer_text'] }}</div>

