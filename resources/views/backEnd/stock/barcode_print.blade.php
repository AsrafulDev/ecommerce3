@extends('backEnd.layouts.master')
@section('title', 'Print Barcode')

@section('css')
<style>
    /* ==================== GENERAL ==================== */
    body { font-family: Arial, Helvetica, sans-serif; }

    .barcode-control-card { border: 0; border-radius: 14px; box-shadow: 0 4px 20px rgba(0, 0, 0, .06); }
    .control-title { font-size: 15px; font-weight: 700; color: #1f2937; }
    .control-section { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; height: 100%; background: #fff; }
    .control-section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; margin-bottom: 12px; }
    .form-label { font-size: 12px; font-weight: 600; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 7px; }

    /* ==================== CONTROL SWITCH ==================== */
    .field-switch { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: #f8fafc; border-radius: 7px; margin-bottom: 6px; }
    .field-switch label { margin: 0; font-size: 12px; font-weight: 600; cursor: pointer; }

    /* ==================== PREVIEW AREA ==================== */
    .preview-wrapper { background: #eef1f5; border-radius: 14px; padding: 35px; overflow: auto; min-height: 500px; }
    .preview-title { font-size: 13px; font-weight: 700; color: #6b7280; margin-bottom: 20px; }

    /* ==================== BARCODE PAGE ==================== */
    .barcode-page { display: flex; flex-wrap: wrap; justify-content: center; gap: 18px; }

    /* ==================== BARCODE LABEL (Default 100mm × 70mm) ==================== */
    .barcode-label { position: relative; display: block; width: 100mm; height: 70mm; background: #fff; color: #000; box-sizing: border-box; padding: 7mm 7mm 5mm 14mm; text-align: center; overflow: hidden; page-break-inside: avoid; break-inside: avoid; border: 1px dashed #cfd4da; }

    /* ==================== SERIAL NUMBER ==================== */
    .label-serial { font-size: 18px; line-height: 1.1; font-weight: 700; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .label-serial .serial-label { font-weight: 700; }

    /* ==================== CUSTOM TEXT ==================== */
    .label-custom { font-size: 12px; font-weight: 600; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* ==================== PRODUCT NAME ==================== */
    .label-product { font-size: 11px; font-weight: 600; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* ==================== BARCODE ==================== */
    .label-barcode { width: 100%; height: 32mm; display: flex; justify-content: center; align-items: center; overflow: hidden; margin: 0 auto; }
    .label-barcode > div { max-width: 100%; }
    .label-barcode div { line-height: 0; }

    /* ==================== PRICE - LEFT VERTICAL ==================== */
    .label-price { position: absolute; left: 2mm; top: 50%; transform: translateY(-50%) rotate(-90deg); transform-origin: center center; font-size: 16px; font-weight: 700; white-space: nowrap; }

    /* ==================== CUSTOM CODE ==================== */
    .label-code { font-size: 17px; font-weight: 700; margin-top: 2px; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* ==================== PRODUCT ID + BATCH ==================== */
    .label-product-batch { margin-top: 4px; font-size: 10px; line-height: 1.2; white-space: nowrap; text-align: center; }
    .label-product-id { font-weight: 700; color: #000; }
    .label-batch-divider { margin: 0 5px; color: #777; }
    .label-batch { color: #555; }

    /* ==================== WEBSITE ==================== */
    .label-website { font-size: 12px; font-weight: 500; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* ==================== BATCH QUICK BUTTON ==================== */
    .batch-quick-btn { font-size: 11px; }

    /* ==================== RESPONSIVE PREVIEW ==================== */
    @media screen and (max-width: 768px) {
        .preview-wrapper { padding: 15px; }
        .barcode-label { transform-origin: top center; transform: scale(.85); margin-bottom: -10mm; }
    }

    /* ==================== PRINT ==================== */
    @media print {
        @page { size: 100mm 70mm; margin: 0; }

        html, body { width: 100mm; margin: 0; padding: 0; background: #fff \!important; }

        body * { visibility: hidden; }

        /* Make the label area (and its wrapper) visible for print */
        .preview-wrapper, .barcode-page, .barcode-page * { visibility: visible; }

        /* Hide the on-screen "LABEL PREVIEW" title — only labels should print */
        .preview-title { display: none \!important; }

        /* Remove clipping / fixed sizing on the wrapper so all labels print */
        .preview-wrapper { overflow: visible \!important; min-height: 0 \!important; height: auto \!important; padding: 0 \!important; margin: 0 \!important; }

        .barcode-page { display: block; width: 100%; margin: 0; padding: 0; }

        .barcode-label { width: 100mm; height: 70mm; margin: 0; padding: 7mm 7mm 5mm 14mm; border: none; page-break-after: always; break-after: page; }

        .barcode-label:last-child { page-break-after: auto; break-after: auto; }

        .no-print { display: none \!important; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid mb-5">

    {{-- ==================== PAGE HEADER ==================== --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mt-2 no-print">
        <div>
            <h4 class="fw-bold text-gray-800 mb-1"><i data-feather="tag" class="text-primary me-1"></i> Barcode Label Designer</h4>
            <small class="text-muted">Design, preview and print professional barcode labels</small>
        </div>
        <div>
            <button type="button" onclick="window.print()" class="btn btn-primary rounded-pill shadow-sm me-2">
                <i data-feather="printer" class="me-1" style="width:14px;"></i> Print Labels
            </button>
            <a href="{{ route('admin.stock.dashboard') }}" class="btn btn-outline-secondary rounded-pill">
                <i data-feather="arrow-left" class="me-1" style="width:14px;"></i> Back
            </a>
        </div>
    </div>

    {{-- ==================== CONTROL SYSTEM ==================== --}}
    <div class="card barcode-control-card mb-4 no-print">
        <div class="card-body">

            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;">
                    <i data-feather="settings" style="width:16px;color:#fff;"></i>
                </div>
                <div>
                    <div class="control-title">Label Control System</div>
                    <small class="text-muted">Control every element of your barcode label</small>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.stock.barcode.print') }}" id="barcodeForm">
                <div class="row g-3">

                    {{-- ==================== PRODUCT ==================== --}}
                    <div class="col-lg-4">
                        <div class="control-section">
                            <div class="control-section-title">Product</div>

                            <label class="form-label">Product</label>
                            <select name="product_id" class="form-control select2" required>
                                <option value="">Select Product</option>
                                @foreach(\App\Models\Product::orderBy('name')->get(['id', 'name', 'barcode']) as $p)
                                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                        @if($p->barcode)
                                            ({{ $p->barcode }})
                                        @else
                                            (PRD-{{ str_pad($p->id, 4, '0', STR_PAD_LEFT) }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            <div class="row mt-3">
                                <div class="col-6">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="quantity" class="form-control" value="{{ request('quantity', 10) }}" min="1" max="100">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Label Size</label>
                                    <select name="label_size" class="form-control">
                                        <option value="100x70" {{ request('label_size', '100x70') == '100x70' ? 'selected' : '' }}>100 × 70 mm</option>
                                        <option value="80x50" {{ request('label_size') == '80x50' ? 'selected' : '' }}>80 × 50 mm</option>
                                        <option value="50x30" {{ request('label_size') == '50x30' ? 'selected' : '' }}>50 × 30 mm</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ==================== LABEL ELEMENTS ==================== --}}
                    <div class="col-lg-4">
                        <div class="control-section">
                            <div class="control-section-title">Label Elements</div>

                            <div class="field-switch">
                                <label for="show_serial">Serial Number / S/N</label>
                                <input type="checkbox" name="show_serial" id="show_serial" value="1" {{ request('show_serial', '1') == '1' ? 'checked' : '' }}>
                            </div>
                            <div class="field-switch">
                                <label for="show_product">Product Name</label>
                                <input type="checkbox" name="show_product" id="show_product" value="1" {{ request('show_product', '0') == '1' ? 'checked' : '' }}>
                            </div>
                            <div class="field-switch">
                                <label for="show_price">Price</label>
                                <input type="checkbox" name="show_price" id="show_price" value="1" {{ request('show_price', '1') == '1' ? 'checked' : '' }}>
                            </div>
                            <div class="field-switch">
                                <label for="show_custom">Code / Custom Text</label>
                                <input type="checkbox" name="show_custom" id="show_custom" value="1" {{ request('show_custom', '1') == '1' ? 'checked' : '' }}>
                            </div>
                            <div class="field-switch">
                                <label for="show_website">Website</label>
                                <input type="checkbox" name="show_website" id="show_website" value="1" {{ request('show_website', '1') == '1' ? 'checked' : '' }}>
                            </div>
                            <div class="field-switch">
                                <label for="show_batch">Batch Number</label>
                                <input type="checkbox" name="show_batch" id="show_batch" value="1" {{ request('show_batch', '0') == '1' ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>

                    {{-- ==================== CUSTOMIZATION ==================== --}}
                    <div class="col-lg-4">
                        <div class="control-section">
                            <div class="control-section-title">Customization</div>

                            <label class="form-label">Serial Number</label>
                            @php
                                $previewProductId = request('product_id') ?: null;
                                $previewProduct = $previewProductId ? \App\Models\Product::find($previewProductId) : null;
                                $previewProductIdCode = $previewProduct ? 'PRD-' . str_pad($previewProduct->id, 4, '0', STR_PAD_LEFT) : '';
                                $previewBarcode = $previewProduct ? ($previewProduct->barcode ?: $previewProductIdCode) : '';
                            @endphp
                            <input type="text" name="serial_text" class="form-control" value="{{ request('serial_text', $previewBarcode) }}" placeholder="Barcode / Serial Number">
                            <small class="text-muted">Leave empty to automatically use product barcode or PRD-XXXX.</small>

                            <label class="form-label mt-3">Website / Footer</label>
                            <input type="text" name="website" class="form-control" value="{{ request('website', 'www.chowdhurytel.com') }}" placeholder="www.example.com">

                            <div class="row mt-3">
                                <div class="col-6">
                                    <label class="form-label">Barcode Height</label>
                                    <input type="number" name="barcode_height" class="form-control" value="{{ request('barcode_height', 40) }}" min="15" max="100">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Price</label>
                                    <input type="text" name="custom_price" class="form-control" value="{{ request('custom_price', $previewProduct->new_price ?? $previewProduct->purchase_price ?? 0) }}" placeholder="400">
                                </div>
                            </div>

                            <label class="form-label mt-3">Custom Text / Code</label>
                            <input type="text" name="custom_text" class="form-control" value="{{ request('custom_text') }}" placeholder="3.02.05.0111">
                            <small class="text-muted">This value appears after "Code:" on the label.</small>
                        </div>
                    </div>

                </div>

                {{-- ==================== BATCH QUICK SELECT ==================== --}}
                @if($batches->isNotEmpty())
                <div class="mt-3 p-3 rounded" style="background:#f8fafc;">
                    <div class="small fw-bold text-muted mb-2">📦 Available Batches</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($batches as $batch)
                            <button type="button" class="btn btn-sm btn-outline-secondary batch-quick-btn" data-batch-no="{{ $batch->batch_no ?: 'Batch #'.$batch->id }}">
                                {{ $batch->batch_no ?: 'Batch #'.$batch->id }}
                                @if($batch->custom_field)<small class="text-primary">({{ Str::limit($batch->custom_field, 20) }})</small>@endif
                                <small class="text-muted">[{{ $batch->remaining_qty }}]</small>
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="batch_no" id="batch_no_input" value="{{ request('batch_no') }}">
                </div>
                @endif

                {{-- ==================== PREVIEW BUTTON ==================== --}}
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i data-feather="eye" style="width:15px;"></i> Generate Preview
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== LABEL PREVIEW ==================== --}}
    @if(request('product_id'))

        @php
            $productIdCode = 'PRD-' . str_pad($product->id, 4, '0', STR_PAD_LEFT);
            $barcodeValue = $product->barcode ?: $productIdCode;
            $serialNumber = request('serial_text') ?: $barcodeValue;
            $price = request('custom_price') ?: ($product->new_price ?? $product->purchase_price ?? 0);
            $website = request('website') ?: 'www.chowdhurytel.com';
            $customText = request('custom_text', '');
            $generator = new \Picqer\Barcode\BarcodeGeneratorHTML();
        @endphp

        <div class="preview-wrapper">
            <div class="preview-title text-center">LABEL PREVIEW — 100 × 70 MM</div>

            <div class="barcode-page">
                @for($i = 0; $i < $quantity; $i++)
                    <div class="barcode-label">

                        @if(request('show_price', '1') == '1')
                            <div class="label-price">Price: ৳{{ number_format((float)$price, 0) }}/-</div>
                        @endif

                        @if(request('show_serial', '1') == '1')
                            <div class="label-serial"><span class="serial-label">S/N:</span> {{ $serialNumber }}</div>
                        @endif

                        @if(request('show_product') == '1')
                            <div class="label-product">{{ $product->name }}</div>
                        @endif

                        <div class="label-barcode">
                            {\!\! $generator->getBarcode($barcodeValue, $generator::TYPE_CODE_128, 2, (int)request('barcode_height', 40)) \!\!}
                        </div>

                        @if(request('show_custom', '1') == '1' && $customText)
                            <div class="label-code">Code: {{ $customText }}</div>
                        @endif

                        <div class="label-product-batch">
                            <span class="label-product-id">{{ $productIdCode }}</span>
                            @if(request('show_batch') == '1' && request('batch_no'))
                                <span class="label-batch-divider">|</span>
                                <span class="label-batch">Batch: {{ request('batch_no') }}</span>
                            @endif
                        </div>

                        @if(request('show_website', '1') == '1')
                            <div class="label-website">{{ $website }}</div>
                        @endif

                    </div>
                @endfor
            </div>
        </div>

    @else

        {{-- ==================== EMPTY STATE ==================== --}}
        <div class="card no-print">
            <div class="card-body text-center py-5 text-muted">
                <i data-feather="tag" style="width:48px;height:48px;"></i>
                <p class="mt-3 mb-0">Select a product and configure your label, then click <strong>Generate Preview</strong>.</p>
            </div>
        </div>

    @endif

</div>
@endsection

@section('script')
<script>
    /* ==================== CUSTOM TEXT ==================== */
    const customToggle = document.getElementById('show_custom');
    const customInput = document.querySelector('input[name="custom_text"]');

    function toggleCustomText() {
        if (\!customToggle || \!customInput) return;
        /* Keep the input visible because it is useful as a Code / Custom Text control.
           The checkbox controls whether it appears on the printed label. */
        customInput.closest('.control-section')?.classList.add('has-custom-text');
    }

    if (customToggle) customToggle.addEventListener('change', toggleCustomText);

    /* ==================== BATCH QUICK SELECT ==================== */
    document.querySelectorAll('.batch-quick-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const batchNo = this.getAttribute('data-batch-no');
            const batchInput = document.getElementById('batch_no_input');
            if (batchInput) batchInput.value = batchNo;
            /* Automatically enable Batch */
            const showBatch = document.getElementById('show_batch');
            if (showBatch) showBatch.checked = true;
        });
    });

    /* ==================== CTRL + P ==================== */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
            e.preventDefault();
            window.print();
        }
    });
</script>
@endsection
