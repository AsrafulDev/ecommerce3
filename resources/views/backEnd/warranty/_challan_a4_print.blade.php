{{-- Generic A4 Warranty Challan — print template (all types) --}}
@php
    $issueTypeLabels = [
        'defective'     => 'Defective Product',
        'not_working'   => 'Not Working',
        'damaged'       => 'Physical Damage',
        'missing_parts' => 'Missing Parts / Accessories',
        'other'         => 'Other',
    ];
    $issueType = $issueTypeLabels[$d['issue_type'] ?? ''] ?? ($d['issue_type'] ?? 'N/A');
    $warrantyType = \App\Enums\WarrantyType::tryFrom($d['warranty_type'] ?? '')?->label()
                    ?? ($d['warranty_type'] ?? 'N/A');
    $type = $challanType ?? ($d['challan_type'] ?? '');
    $title = match ($type) {
        'receive'          => 'PRODUCT RECEIVE CHALLAN',
        'send_to_supplier' => 'WARRANTY CLAIM CHALLAN',
        'receive_return'   => 'SUPPLIER RETURN CHALLAN',
        'delivery'         => 'CUSTOMER DELIVERY CHALLAN',
        default            => 'WARRANTY CHALLAN',
    };
    $status = match ($type) {
        'receive'          => 'PRODUCT RECEIVED',
        'send_to_supplier' => 'SENT TO SUPPLIER',
        'receive_return'   => 'SUPPLIER RETURNED',
        'delivery'         => 'DELIVERED',
        default            => 'WARRANTY',
    };
    $subtitle = match ($type) {
        'receive'          => 'Product Receive Documentation',
        'send_to_supplier' => 'Supplier Warranty Claim Documentation',
        'receive_return'   => 'Supplier Return Documentation',
        'delivery'         => 'Customer Delivery Documentation',
        default            => 'Warranty Claim Documentation',
    };
@endphp

<style>
    .a4-challan * { box-sizing: border-box; }
    .a4-challan { font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 12px; }
    .a4-challan .page {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        background: #fff;
        padding: 12mm;
    }

    /* HEADER */
    .a4-challan .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 2px solid #111;
        padding-bottom: 12px;
    }
    .a4-challan .company-name { font-size: 25px; font-weight: 700; letter-spacing: .3px; }
    .a4-challan .company-subtitle { color: #666; margin-top: 4px; font-size: 11px; }
    .a4-challan .copy-label {
        align-self: center;
        text-align: center;
        font-weight: bold;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #fff;
        background: #111;
        padding: 6px 16px;
        border-radius: 3px;
    }
    .a4-challan .document-title { text-align: right; }
    .a4-challan .document-title h1 { margin: 0; font-size: 19px; letter-spacing: .8px; }
    .a4-challan .document-title .status {
        display: inline-block;
        margin-top: 7px;
        padding: 4px 9px;
        background: #111;
        color: #fff;
        font-size: 10px;
        font-weight: bold;
        border-radius: 3px;
    }

    /* CHALLAN META */
    .a4-challan .meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 6px;
    }
    .a4-challan .meta-box { border: 1px solid #ccc; padding: 5px 10px; }
    .a4-challan .meta-label { color: #666; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
    .a4-challan .meta-value { margin-top: 2px; font-weight: bold; font-size: 12px; }

    /* SECTION */
    .a4-challan .section { margin-top: 6px; }
    .a4-challan .section-title {
        background: #111;
        color: #fff;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    /* PARTIES */
    .a4-challan .parties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 1px solid #ccc;
        border-top: 0;
    }
    .a4-challan .party { padding: 6px 10px; min-height: 60px; }
    .a4-challan .party + .party { border-left: 1px solid #ccc; }
    .a4-challan .party-title { font-size: 10px; font-weight: bold; color: #555; text-transform: uppercase; margin-bottom: 4px; }
    .a4-challan .party-name { font-size: 15px; font-weight: bold; margin-bottom: 4px; }
    .a4-challan .party-row { display: flex; margin: 2px 0; }
    .a4-challan .party-row .label { width: 65px; color: #666; }
    .a4-challan .party-row .value { flex: 1; }

    /* TABLE */
    .a4-challan table { width: 100%; border-collapse: collapse; }
    .a4-challan .details-table { border: 1px solid #ccc; border-top: 0; }
    .a4-challan .details-table td { border-bottom: 1px solid #ddd; padding: 4px 9px; vertical-align: top; }
    .a4-challan .details-table tr:last-child td { border-bottom: 0; }
    .a4-challan .details-table .label { width: 28%; background: #f6f6f6; color: #555; font-weight: bold; }
    .a4-challan .details-table .value { font-weight: 500; }

    /* TWO COLUMN DETAILS */
    .a4-challan .two-column {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 1px solid #ccc;
        border-top: 0;
    }
    .a4-challan .two-column .item { padding: 8px 10px; border-bottom: 1px solid #ddd; }
    .a4-challan .two-column .item:nth-child(odd) { border-right: 1px solid #ddd; }
    .a4-challan .two-column .item:nth-last-child(-n+2) { border-bottom: 0; }
    .a4-challan .item-label { display: block; color: #666; font-size: 9px; text-transform: uppercase; margin-bottom: 3px; }
    .a4-challan .item-value { font-weight: bold; }

    /* ISSUE */
    .a4-challan .issue-box { border: 1px solid #ccc; border-top: 0; }
    .a4-challan .issue-row { display: grid; grid-template-columns: 28% 72%; border-bottom: 1px solid #ddd; }
    .a4-challan .issue-row:last-child { border-bottom: 0; }
    .a4-challan .issue-label { padding: 8px 9px; background: #f6f6f6; color: #555; font-weight: bold; }
    .a4-challan .issue-value { padding: 8px 9px; }

    /* DECLARATION */
    .a4-challan .declaration {
        margin-top: 6px;
        border: 1px solid #ccc;
        padding: 6px 10px;
        background: #fafafa;
        line-height: 1.4;
    }
    .a4-challan .declaration-title { font-weight: bold; margin-bottom: 3px; text-transform: uppercase; font-size: 10px; }

    /* SIGNATURES */
    .a4-challan .signatures {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-top: 16px;
    }
    .a4-challan .signature { text-align: center; }
    .a4-challan .signature-line { border-top: 1px solid #222; padding-top: 6px; font-weight: bold; font-size: 10px; }
    .a4-challan .signature small { display: block; color: #777; margin-top: 4px; font-size: 9px; }

    /* FOOTER */
    .a4-challan .footer {
        margin-top: 12px;
        padding-top: 8px;
        border-top: 1px solid #ccc;
        display: flex;
        justify-content: space-between;
        color: #666;
        font-size: 9px;
    }
    .a4-challan .footer strong { color: #222; }

    @media print {
        .a4-challan .page { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
    }
</style>

<div class="a4-challan">
    <div class="page">
        <!-- HEADER -->
        <div class="header">
            <div>
                <div class="company-name">{{ $d['store_name'] }}</div>
                <div class="company-subtitle">{{ $subtitle }}</div>
            </div>
            <div class="copy-label">{{ $copyLabel ?? '' }}</div>
            <div class="document-title">
                <h1>{{ $title }}</h1>
                <div class="status">{{ $status }}</div>
            </div>
        </div>

        <!-- CHALLAN META -->
        <div class="meta">
            <div class="meta-box">
                <div class="meta-label">Challan Number</div>
                <div class="meta-value">{{ $d['challan_no'] }}</div>
            </div>
            <div class="meta-box">
                <div class="meta-label">Date & Time</div>
                <div class="meta-value">{{ $d['date'] }}</div>
            </div>
        </div>

        {{-- ═══════════ PARTIES ═══════════ --}}
        @if(in_array($type, ['receive', 'delivery']))
        {{-- Store + Customer --}}
        <div class="section">
            <div class="section-title">Sender & Receiver Information</div>
            <div class="parties">
                <div class="party">
                    <div class="party-title">Sender / Store</div>
                    <div class="party-name">{{ $d['store_name'] ?? 'N/A' }}</div>
                    <div class="party-row">
                        <div class="label">Address</div>
                        <div class="value">{{ ($d['store_address'] ?? '') ?: '____________________________' }}</div>
                    </div>
                    <div class="party-row">
                        <div class="label">Phone</div>
                        <div class="value">{{ ($d['store_phone'] ?? '') ?: '____________________________' }}</div>
                    </div>
                </div>
                <div class="party">
                    <div class="party-title">Receiver / Customer</div>
                    <div class="party-name">{{ $d['customer_name'] ?? 'N/A' }}</div>
                    <div class="party-row">
                        <div class="label">Phone</div>
                        <div class="value">{{ ($d['customer_phone'] ?? '') ?: '____________________________' }}</div>
                    </div>
                    @if(!empty($d['customer_address'] ?? ''))
                    <div class="party-row">
                        <div class="label">Address</div>
                        <div class="value">{{ $d['customer_address'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @elseif(in_array($type, ['send_to_supplier', 'receive_return']))
        {{-- Store + Supplier --}}
        <div class="section">
            <div class="section-title">Sender & Receiver Information</div>
            <div class="parties">
                <div class="party">
                    <div class="party-title">Sender / Store</div>
                    <div class="party-name">{{ $d['store_name'] ?? 'N/A' }}</div>
                    <div class="party-row">
                        <div class="label">Address</div>
                        <div class="value">{{ ($d['store_address'] ?? '') ?: '____________________________' }}</div>
                    </div>
                    <div class="party-row">
                        <div class="label">Phone</div>
                        <div class="value">{{ ($d['store_phone'] ?? '') ?: '____________________________' }}</div>
                    </div>
                    <div class="party-row">
                        <div class="label">Contact</div>
                        <div class="value">{{ $d['store_contact'] ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="party">
                    <div class="party-title">Receiver / Supplier</div>
                    <div class="party-name">{{ $d['supplier_name'] ?? 'N/A' }}</div>
                    <div class="party-row">
                        <div class="label">Address</div>
                        <div class="value">{{ ($d['supplier_address'] ?? '') ?: '____________________________' }}</div>
                    </div>
                    <div class="party-row">
                        <div class="label">Phone</div>
                        <div class="value">{{ ($d['supplier_phone'] ?? '') ?: '____________________________' }}</div>
                    </div>
                    <div class="party-row">
                        <div class="label">Contact</div>
                        <div class="value">{{ $d['supplier_contact'] ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ═══════════ PRODUCT ═══════════ --}}
        <div class="section">
            <div class="section-title">Product & Warranty Information</div>
            <table class="details-table">
                <tr>
                    <td class="label">Product Name</td>
                    <td class="value">{{ $d['product_name'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Serial Number</td>
                    <td class="value">{{ $d['serial_number'] ?? $d['original_sn'] ?? 'N/A' }}</td>
                </tr>
                @if(!empty($d['replacement_sn']))
                <tr>
                    <td class="label">Replacement SN</td>
                    <td class="value">{{ $d['replacement_sn'] }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Claim Number</td>
                    <td class="value">{{ $d['claim_number'] ?? 'N/A' }}</td>
                </tr>
                @if(!empty($d['return_type']))
                <tr>
                    <td class="label">Return Type</td>
                    <td class="value">{{ ucfirst($d['return_type']) }}</td>
                </tr>
                @endif
                @if(!empty($d['warranty_type']))
                <tr>
                    <td class="label">Warranty Type</td>
                    <td class="value">{{ $warrantyType }}</td>
                </tr>
                @endif
                @if(!empty($d['warranty_days']))
                <tr>
                    <td class="label">Warranty Period</td>
                    <td class="value">{{ $d['warranty_days'] }} Days</td>
                </tr>
                @endif
                @if(!empty($d['warranty_start']) || !empty($d['warranty_end']))
                <tr>
                    <td class="label">Warranty Period</td>
                    <td class="value">{{ $d['warranty_start'] ?? '—' }} to {{ $d['warranty_end'] ?? '—' }}</td>
                </tr>
                @endif
            </table>
        </div>

        {{-- ═══════════ ISSUE ═══════════ --}}
        <div class="section">
            <div class="section-title">Claim Cause / Issue Details</div>
            <div class="issue-box">
                <div class="issue-row">
                    <div class="issue-label">Issue Type</div>
                    <div class="issue-value">{{ $issueType }}</div>
                </div>
                <div class="issue-row">
                    <div class="issue-label">Issue Description</div>
                    <div class="issue-value">{{ $d['issue_description'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- ═══════════ TYPE-SPECIFIC ═══════════ --}}
        @if($type === 'receive')
        <div class="section">
            <div class="section-title">Received Condition</div>
            <div class="two-column">
                <div class="item">
                    <span class="item-label">Product Condition</span>
                    <span class="item-value">{{ $d['received_condition'] ?? 'N/A' }}</span>
                </div>
                <div class="item">
                    <span class="item-label">Accessories</span>
                    <span class="item-value">{{ $d['accessories'] ?? 'None' }}</span>
                </div>
            </div>
        </div>
        @elseif($type === 'send_to_supplier')
        <div class="section">
            <div class="section-title">Delivery & Logistics</div>
            <div class="two-column">
                <div class="item">
                    <span class="item-label">Warehouse</span>
                    <span class="item-value">{{ $d['warehouse'] ?? 'N/A' }}</span>
                </div>
                <div class="item">
                    <span class="item-label">Courier</span>
                    <span class="item-value">{{ $d['courier'] ?? 'N/A' }}</span>
                </div>
                <div class="item">
                    <span class="item-label">Tracking ID</span>
                    <span class="item-value">{{ $d['tracking_id'] ?? 'N/A' }}</span>
                </div>
                <div class="item">
                    <span class="item-label">Product Serial</span>
                    <span class="item-value">{{ $d['serial_number'] ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        @elseif($type === 'receive_return')
        <div class="section">
            <div class="section-title">Supplier Return Details</div>
            <div class="two-column">
                <div class="item">
                    <span class="item-label">Supplier Return Challan</span>
                    <span class="item-value">{{ $d['supplier_return_challan'] ?? 'N/A' }}</span>
                </div>
                <div class="item">
                    <span class="item-label">Return Type</span>
                    <span class="item-value">{{ ucfirst($d['return_type'] ?? 'repaired') }}</span>
                </div>
                @if(($d['supplier_charge'] ?? 0) > 0)
                <div class="item">
                    <span class="item-label">Supplier Charge</span>
                    <span class="item-value">৳{{ number_format($d['supplier_charge'], 2) }}</span>
                </div>
                @endif
            </div>
        </div>
        @elseif($type === 'delivery')
        <div class="section">
            <div class="section-title">Delivery Details</div>
            <div class="two-column">
                <div class="item">
                    <span class="item-label">Delivery Method</span>
                    <span class="item-value">{{ $d['delivery_method'] ?? 'Counter Pickup' }}</span>
                </div>
                <div class="item">
                    <span class="item-label">Total Claims</span>
                    <span class="item-value">{{ $d['claim_count'] ?? 1 }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- ═══════════ DECLARATION ═══════════ --}}
        <div class="declaration">
            <div class="declaration-title">Declaration</div>
            @if($type === 'receive')
            This product has been received from the customer for warranty inspection
            and service. The details above are accurate to the best of our knowledge.
            @elseif($type === 'send_to_supplier')
            This product is being sent to the supplier for warranty inspection,
            service, repair or replacement. The supplier is requested to process
            this claim according to the applicable warranty terms and conditions.
            @elseif($type === 'receive_return')
            This product has been returned from the supplier after warranty
            processing. The return details above are accurate and recorded.
            @elseif($type === 'delivery')
            This product has been serviced under warranty and is being delivered
            back to the customer. The details above are accurate and recorded.
            @endif
        </div>

        {{-- ═══════════ SIGNATURES ═══════════ --}}
        <div class="signatures">
            <div class="signature">
                <div style="height:28px;"></div>
                <div class="signature-line">Prepared By</div>
                <small>Name & Signature</small>
            </div>
            <div class="signature">
                <div style="height:28px;"></div>
                <div class="signature-line">
                    @if(in_array($type, ['send_to_supplier', 'receive_return']))
                        Supplier Representative
                    @else
                        Customer Signature
                    @endif
                </div>
                <small>Name, Signature & Date</small>
            </div>
            <div class="signature">
                <div style="height:28px;"></div>
                <div class="signature-line">Store Representative</div>
                <small>Name, Signature & Date</small>
            </div>
        </div>
    </div>
</div>