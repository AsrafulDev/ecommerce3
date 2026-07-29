{{-- Send to Supplier Challan Content (Supplier + Store — NO customer info) --}}
<div class="challan-header">
    <h3>{{ $d['store_name'] }}</h3>
    <div class="challan-no">Challan #: <strong>{{ $d['challan_no'] }}</strong></div>
    <span class="challan-type-badge badge-send">Sent to Supplier</span>
    <div style="float:right;font-size:13px;">Date: {{ $d['date'] }}</div>
</div>

<div class="section-title">Store Information (Sender)</div>
<table class="info-table">
    <tr><td class="label">Store</td><td>: {{ $d['store_name'] }}</td></tr>
    <tr><td class="label">Address</td><td>: {{ $d['store_address'] }}</td></tr>
    <tr><td class="label">Phone</td><td>: {{ $d['store_phone'] }}</td></tr>
    <tr><td class="label">Contact</td><td>: {{ $d['store_contact'] }}</td></tr>
</table>

<div class="section-title">Supplier Information (Receiver)</div>
<table class="info-table">
    <tr><td class="label">Supplier</td><td>: {{ $d['supplier_name'] }}</td></tr>
    <tr><td class="label">Address</td><td>: {{ $d['supplier_address'] }}</td></tr>
    <tr><td class="label">Phone</td><td>: {{ $d['supplier_phone'] }}</td></tr>
    <tr><td class="label">Contact Person</td><td>: {{ $d['supplier_contact'] }}</td></tr>
</table>

<div class="section-title">Product Information</div>
<table class="info-table">
    <tr><td class="label">Product</td><td>: {{ $d['product_name'] }}</td></tr>
    <tr><td class="label">Serial Number</td><td>: {{ $d['serial_number'] }}</td></tr>
    <tr><td class="label">Claim Number</td><td>: {{ $d['claim_number'] }}</td></tr>
    <tr><td class="label">Warranty Type</td><td>: {{ $d['warranty_type'] }}</td></tr>
    <tr><td class="label">Warranty Days</td><td>: {{ $d['warranty_days'] }} days</td></tr>
    <tr><td class="label">Courier</td><td>: {{ $d['courier'] }}</td></tr>
    <tr><td class="label">Tracking ID</td><td>: {{ $d['tracking_id'] }}</td></tr>
</table>

@if(!empty($d['notes']))
<div class="section-title">Notes</div>
<p>{{ $d['notes'] }}</p>
@endif

<div class="footer-text">{{ $d['footer_text'] }}</div>
