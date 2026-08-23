{{-- Receive Challan Content (Customer + Store) --}}
<div class="challan-header">
    <h3>{{ config('app.name', 'Store') }}</h3>
    <div class="challan-no">Challan #: <strong>{{ $d['challan_no'] }}</strong></div>
    <span class="challan-type-badge badge-receive">Product Receive</span>
    <div style="float:right;font-size:13px;">Date: {{ $d['date'] }}</div>
</div>

<table class="info-table">
    <tr><td class="label">Customer Name</td><td>: {{ $d['customer_name'] }}</td></tr>
    <tr><td class="label">Customer Phone</td><td>: {{ $d['customer_phone'] }}</td></tr>
    <tr><td class="label">Claim Number</td><td>: {{ $d['claim_number'] }}</td></tr>
    <tr><td class="label">Product</td><td>: {{ $d['product_name'] }}</td></tr>
    <tr><td class="label">Serial Number</td><td>: {{ $d['serial_number'] }}</td></tr>
    <tr><td class="label">Product Condition</td><td>: {{ $d['received_condition'] }}</td></tr>
    <tr><td class="label">Accessories</td><td>: {{ $d['accessories'] }}</td></tr>
</table>

<div class="section-title">Issue Description</div>
<p><strong>Issue Type:</strong> {{ $d['issue_type'] ?? 'N/A' }}</p>
<p>{{ $d['issue_description'] }}</p>

@if(!empty($d['notes']))
<div class="section-title">Notes</div>
<p>{{ $d['notes'] }}</p>
@endif

<div class="footer-text">{{ $d['footer_text'] }}</div>

