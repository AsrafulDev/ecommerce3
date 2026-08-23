{{-- Supplier Warranty Claim Challan — print template (A4) --}}
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
@endphp

<style>
    .sup-challan * { box-sizing: border-box; }
    .sup-challan { font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 12px; }
    .sup-challan .page {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        background: #fff;
        padding: 12mm;
    }

    /* HEADER */
    .sup-challan .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 2px solid #111;
        padding-bottom: 12px;
    }
    .sup-challan .company-name { font-size: 25px; font-weight: 700; letter-spacing: .3px; }
    .sup-challan .company-subtitle { color: #666; margin-top: 4px; font-size: 11px; }
    .sup-challan .copy-label {
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
    .sup-challan .document-title { text-align: right; }
    .sup-challan .document-title h1 { margin: 0; font-size: 19px; letter-spacing: .8px; }
    .sup-challan .document-title .status {
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
    .sup-challan .meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 10px;
    }
    .sup-challan .meta-box { border: 1px solid #ccc; padding: 6px 10px; }
    .sup-challan .meta-label { color: #666; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
    .sup-challan .meta-value { margin-top: 2px; font-weight: bold; font-size: 12px; }

    /* SECTION */
    .sup-challan .section { margin-top: 10px; }
    .sup-challan .section-title {
        background: #111;
        color: #fff;
        padding: 6px 9px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    /* PARTIES */
    .sup-challan .parties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 1px solid #ccc;
        border-top: 0;
    }
    .sup-challan .party { padding: 8px 10px; min-height: 88px; }
    .sup-challan .party + .party { border-left: 1px solid #ccc; }
    .sup-challan .party-title { font-size: 10px; font-weight: bold; color: #555; text-transform: uppercase; margin-bottom: 5px; }
    .sup-challan .party-name { font-size: 15px; font-weight: bold; margin-bottom: 5px; }
    .sup-challan .party-row { display: flex; margin: 2px 0; }
    .sup-challan .party-row .label { width: 65px; color: #666; }
    .sup-challan .party-row .value { flex: 1; }

    /* TABLE */
    .sup-challan table { width: 100%; border-collapse: collapse; }
    .sup-challan .details-table { border: 1px solid #ccc; border-top: 0; }
    .sup-challan .details-table td { border-bottom: 1px solid #ddd; padding: 7px 9px; vertical-align: top; }
    .sup-challan .details-table tr:last-child td { border-bottom: 0; }
    .sup-challan .details-table .label { width: 28%; background: #f6f6f6; color: #555; font-weight: bold; }
    .sup-challan .details-table .value { font-weight: 500; }

    /* TWO COLUMN DETAILS */
    .sup-challan .two-column {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 1px solid #ccc;
        border-top: 0;
    }
    .sup-challan .two-column .item { padding: 8px 10px; border-bottom: 1px solid #ddd; }
    .sup-challan .two-column .item:nth-child(odd) { border-right: 1px solid #ddd; }
    .sup-challan .two-column .item:nth-last-child(-n+2) { border-bottom: 0; }
    .sup-challan .item-label { display: block; color: #666; font-size: 9px; text-transform: uppercase; margin-bottom: 3px; }
    .sup-challan .item-value { font-weight: bold; }

    /* ISSUE */
    .sup-challan .issue-box { border: 1px solid #ccc; border-top: 0; }
    .sup-challan .issue-row { display: grid; grid-template-columns: 28% 72%; border-bottom: 1px solid #ddd; }
    .sup-challan .issue-row:last-child { border-bottom: 0; }
    .sup-challan .issue-label { padding: 8px 9px; background: #f6f6f6; color: #555; font-weight: bold; }
    .sup-challan .issue-value { padding: 8px 9px; }

    /* DECLARATION */
    .sup-challan .declaration {
        margin-top: 10px;
        border: 1px solid #ccc;
        padding: 8px 10px;
        background: #fafafa;
        line-height: 1.4;
    }
    .sup-challan .declaration-title { font-weight: bold; margin-bottom: 3px; text-transform: uppercase; font-size: 10px; }

    /* SIGNATURES */
    .sup-challan .signatures {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-top: 24px;
    }
    .sup-challan .signature { text-align: center; }
    .sup-challan .signature-line { border-top: 1px solid #222; padding-top: 6px; font-weight: bold; font-size: 10px; }
    .sup-challan .signature small { display: block; color: #777; margin-top: 4px; font-size: 9px; }

    /* FOOTER */
    .sup-challan .footer {
        margin-top: 18px;
        padding-top: 8px;
        border-top: 1px solid #ccc;
        display: flex;
        justify-content: space-between;
        color: #666;
        font-size: 9px;
    }
    .sup-challan .footer strong { color: #222; }

    @media print {
        .sup-challan .page { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
    }
</style>

<div class="sup-challan">
    <div class="page">
        <!-- HEADER -->
        <div class="header">
            <div>
                <div class="company-name">{{ $d['store_name'] }}</div>
                <div class="company-subtitle">Supplier Warranty Claim Documentation</div>
            </div>
            <div class="copy-label">{{ $copyLabel ?? '' }}</div>
            <div class="document-title">
                <h1>WARRANTY CLAIM CHALLAN</h1>
                <div class="status">SENT TO SUPPLIER</div>
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

        <!-- SENDER / RECEIVER -->
        <div class="section">
            <div class="section-title">Sender & Receiver Information</div>
            <div class="parties">
                <div class="party">
                    <div class="party-title">Sender / Store</div>
                    <div class="party-name">{{ $d['store_name'] }}</div>
                    <div class="party-row">
                        <div class="label">Address</div>
                        <div class="value">{{ $d['store_address'] ?: '____________________________' }}</div>
                    </div>
                    <div class="party-row">
                        <div class="label">Phone</div>
                        <div class="value">{{ $d['store_phone'] ?: '____________________________' }}</div>
                    </div>
                    <div class="party-row">
                        <div class="label">Contact</div>
                        <div class="value">{{ $d['store_contact'] ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="party">
                    <div class="party-title">Receiver / Supplier</div>
                    <div class="party-name">{{ $d['supplier_name'] }}</div>
                    <div class="party-row">
                        <div class="label">Address</div>
                        <div class="value">{{ $d['supplier_address'] ?: '____________________________' }}</div>
                    </div>
                    <div class="party-row">
                        <div class="label">Phone</div>
                        <div class="value">{{ $d['supplier_phone'] ?: '____________________________' }}</div>
                    </div>
                    <div class="party-row">
                        <div class="label">Contact</div>
                        <div class="value">{{ $d['supplier_contact'] ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRODUCT -->
        <div class="section">
            <div class="section-title">Product & Warranty Information</div>
            <table class="details-table">
                <tr>
                    <td class="label">Product Name</td>
                    <td class="value">{{ $d['product_name'] }}</td>
                </tr>
                <tr>
                    <td class="label">Serial Number</td>
                    <td class="value">{{ $d['serial_number'] }}</td>
                </tr>
                <tr>
                    <td class="label">Claim Number</td>
                    <td class="value">{{ $d['claim_number'] }}</td>
                </tr>
                <tr>
                    <td class="label">Warranty Type</td>
                    <td class="value">{{ $warrantyType }}</td>
                </tr>
                <tr>
                    <td class="label">Warranty Period</td>
                    <td class="value">{{ $d['warranty_days'] }} Days</td>
                </tr>
            </table>
        </div>

        <!-- ISSUE -->
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

        <!-- LOGISTICS -->
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
                    <span class="item-value">{{ $d['serial_number'] }}</span>
                </div>
            </div>
        </div>

        <!-- DECLARATION -->
        <div class="declaration">
            <div class="declaration-title">Declaration</div>
            This product is being sent to the supplier for warranty inspection,
            service, repair or replacement. The supplier is requested to process
            this claim according to the applicable warranty terms and conditions.
        </div>

        <!-- SIGNATURES -->
        <div class="signatures">
            <div class="signature">
                <div style="height:38px;"></div>
                <div class="signature-line">Prepared By</div>
                <small>Name & Signature</small>
            </div>
            <div class="signature">
                <div style="height:38px;"></div>
                <div class="signature-line">Supplier Representative</div>
                <small>Name, Signature & Date</small>
            </div>
            <div class="signature">
                <div style="height:38px;"></div>
                <div class="signature-line">Store Representative</div>
                <small>Name, Signature & Date</small>
            </div>
        </div>
    </div>
</div>