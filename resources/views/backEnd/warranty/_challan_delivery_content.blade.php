{{-- Delivery Challan Content (Customer + Store) --}}
<div class="challan-header">
    <h3>{{ $d['store_name'] }}</h3>
    <div class="challan-no">Challan #: <strong>{{ $d['challan_no'] }}</strong></div>
    <span class="challan-type-badge badge-delivery">Product Delivery</span>
    <div style="float:right;font-size:13px;">Date: {{ $d['date'] }}</div>
</div>

<div class="section-title">Customer Information</div>
<table class="info-table">
    <tr><td class="label">Name</td><td>: {{ $d['customer_name'] }}</td></tr>
    <tr><td class="label">Phone</td><td>: {{ $d['customer_phone'] }}</td></tr>
    <tr><td class="label">Address</td><td>: {{ $d['customer_address'] }}</td></tr>
</table>

<div class="section-title">Product Information</div>
<table class="info-table">
    <tr><td class="label">Product</td><td>: {{ $d['product_name'] }}</td></tr>
    <tr><td class="label">Serial Number</td><td>: {{ $d['serial_number'] }}</td></tr>
    <tr><td class="label">Return Type</td><td>: {{ ucfirst($d['return_type']) }}</td></tr>
    <tr><td class="label">Claim Number</td><td>: {{ $d['claim_number'] }}</td></tr>
</table>

<div class="section-title">Warranty Summary</div>
<table class="info-table">
    <tr><td class="label">Warranty Type</td><td>: {{ $d['warranty_type'] }}</td></tr>
    <tr><td class="label">Warranty Days</td><td>: {{ $d['warranty_days'] }} days</td></tr>
    <tr><td class="label">Warranty Period</td><td>: {{ $d['warranty_start'] }} to {{ $d['warranty_end'] }}</td></tr>
    <tr><td class="label">Total Claims</td><td>: {{ $d['claim_count'] }}</td></tr>
</table>

<div class="section-title">Delivery</div>
<table class="info-table">
    <tr><td class="label">Method</td><td>: {{ $d['delivery_method'] }}</td></tr>
</table>

@if(!empty($d['notes']))
<div class="section-title">Notes</div>
<p>{{ $d['notes'] }}</p>
@endif

<div class="footer-text">{{ $d['footer_text'] }}</div>

