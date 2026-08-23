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

        /* ── Supplier challan: each copy = exactly one A4 page ── */
        .sup-copy { page-break-after: always; }
        .sup-copy:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    @php $d = $challan->challan_data; @endphp

    @if($challan->challan_type === 'receive')
    {{-- Copy 1: Customer Copy --}}
    <div class="sup-copy">
        @include('backEnd.warranty._challan_a4_print', ['d' => $d, 'copyLabel' => 'Customer Copy', 'challanType' => 'receive'])
    </div>
    {{-- Copy 2: Store Copy --}}
    <div class="sup-copy">
        @include('backEnd.warranty._challan_a4_print', ['d' => $d, 'copyLabel' => 'Store Copy', 'challanType' => 'receive'])
    </div>
    @endif

    @if($challan->challan_type === 'send_to_supplier')
    {{-- Copy 1: Supplier Copy --}}
    <div class="sup-copy">
        @include('backEnd.warranty._challan_a4_print', ['d' => $d, 'copyLabel' => 'Supplier Copy', 'challanType' => 'send_to_supplier'])
    </div>
    {{-- Copy 2: Store Copy --}}
    <div class="sup-copy">
        @include('backEnd.warranty._challan_a4_print', ['d' => $d, 'copyLabel' => 'Store Copy', 'challanType' => 'send_to_supplier'])
    </div>
    @endif

    @if($challan->challan_type === 'receive_return')
    {{-- Copy 1: Supplier Copy --}}
    <div class="sup-copy">
        @include('backEnd.warranty._challan_a4_print', ['d' => $d, 'copyLabel' => 'Supplier Copy', 'challanType' => 'receive_return'])
    </div>
    {{-- Copy 2: Store Copy --}}
    <div class="sup-copy">
        @include('backEnd.warranty._challan_a4_print', ['d' => $d, 'copyLabel' => 'Store Copy', 'challanType' => 'receive_return'])
    </div>
    @endif

    @if($challan->challan_type === 'delivery')
    {{-- Copy 1: Customer Copy --}}
    <div class="sup-copy">
        @include('backEnd.warranty._challan_a4_print', ['d' => $d, 'copyLabel' => 'Customer Copy', 'challanType' => 'delivery'])
    </div>
    {{-- Copy 2: Store Copy --}}
    <div class="sup-copy">
        @include('backEnd.warranty._challan_a4_print', ['d' => $d, 'copyLabel' => 'Store Copy', 'challanType' => 'delivery'])
    </div>
    @endif
</body>
</html>
