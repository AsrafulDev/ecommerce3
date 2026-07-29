<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $challan->challan_no }}</title>
    <style>
        body { font-family: 'Segoe UI', 'DejaVu Sans', sans-serif; font-size: 13px; color: #333; margin: 0; padding: 20px; }
        .challan-header { border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .challan-header h3 { margin: 0; font-size: 18px; }
        .challan-header .challan-no { font-size: 12px; color: #666; }
        .challan-type-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 11px; font-weight: 600; margin: 5px 0; }
        .badge-receive { background: #e3f2fd; color: #1565c0; }
        .badge-send { background: #fff3e0; color: #e65100; }
        .badge-return { background: #e8f5e9; color: #2e7d32; }
        .badge-delivery { background: #fce4ec; color: #c62828; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 6px 10px; vertical-align: top; font-size: 13px; border-bottom: 1px solid #eee; }
        .info-table .label { font-weight: 600; color: #555; width: 35%; }
        .section-title { font-size: 14px; font-weight: 700; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin: 15px 0 10px; text-transform: uppercase; }
        .footer-text { margin-top: 25px; padding-top: 12px; border-top: 1px solid #ddd; text-align: center; font-size: 11px; color: #888; }

        /* ── Per-page copy layout ── */
        .copy-page { padding: 20px; border: 2px dashed #999; margin-bottom: 10px; page-break-after: always; }
        .copy-page:last-child { page-break-after: auto; }
        .page-label { text-align: center; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 2px; padding: 6px 12px; margin-bottom: 15px; border-radius: 4px; }
        .page-customer { background: #e3f2fd; color: #1565c0; border: 2px solid #1565c0; }
        .page-supplier { background: #fff3e0; color: #e65100; border: 2px solid #e65100; }
        .page-store { background: #fce4ec; color: #c62828; border: 2px solid #c62828; }
        .signature-area { margin-top: 40px; width: 100%; border: none; }
        .signature-area td { width: 50%; padding: 0 15px; vertical-align: top; border: none; }
        .sig-line { border-top: 1px solid #333; padding-top: 6px; font-size: 11px; color: #666; text-align: center; }
    </style>
</head>
<body>
    @php $d = $challan->challan_data; @endphp

    @if($challan->challan_type === 'receive')
    {{-- Page 1: Customer Copy --}}
    <div class="copy-page">
        <div class="page-label page-customer">Customer Copy</div>
        @include('backEnd.warranty._challan_receive_content', ['d' => $d])
        <table class="signature-area"><tr>
            <td><div class="sig-line">Customer Signature</div></td>
            <td><div class="sig-line">Store Representative</div></td>
        </tr></table>
    </div>
    {{-- Page 2: Store Copy --}}
    <div class="copy-page">
        <div class="page-label page-store">Store Copy</div>
        @include('backEnd.warranty._challan_receive_content', ['d' => $d])
        <table class="signature-area"><tr>
            <td><div class="sig-line">Customer Signature</div></td>
            <td><div class="sig-line">Store Representative</div></td>
        </tr></table>
    </div>
    @endif

    @if($challan->challan_type === 'send_to_supplier')
    {{-- Page 1: Supplier Copy --}}
    <div class="copy-page">
        <div class="page-label page-supplier">Supplier Copy</div>
        @include('backEnd.warranty._challan_supplier_content', ['d' => $d])
        <table class="signature-area"><tr>
            <td><div class="sig-line">Supplier Signature</div></td>
            <td><div class="sig-line">Store Representative</div></td>
        </tr></table>
    </div>
    {{-- Page 2: Store Copy --}}
    <div class="copy-page">
        <div class="page-label page-store">Store Copy</div>
        @include('backEnd.warranty._challan_supplier_content', ['d' => $d])
        <table class="signature-area"><tr>
            <td><div class="sig-line">Supplier Signature</div></td>
            <td><div class="sig-line">Store Representative</div></td>
        </tr></table>
    </div>
    @endif

    @if(in_array($challan->challan_type, ['receive_return', 'delivery']))
    <div class="copy-page">
        @if($challan->challan_type === 'receive_return')
            @include('backEnd.warranty._challan_return_content', ['d' => $d])
        @else
            @include('backEnd.warranty._challan_delivery_content', ['d' => $d])
        @endif
        <table class="signature-area"><tr>
            <td><div class="sig-line">Customer Signature</div></td>
            <td><div class="sig-line">Store Representative</div></td>
        </tr></table>
    </div>
    @endif
</body>
</html>
