@extends('backEnd.layouts.master')
@section('title','Product Edit')

@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />

<style>
    /* Custom Design similar to Create Page */
    .section-title { background: #f1f3f7; padding: 10px 15px; border-radius: 6px; font-weight: 700; color: #343a40; border-left: 4px solid #727cf5; margin-bottom: 20px; font-size: 15px; }
    .form-label { font-weight: 600; font-size: 13px; color: #555; }
    .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border-radius: 0.75rem; }
    
    /* Image Styling */
    .edit-image { width: 70px; height: 70px; object-fit: cover; margin-right: 5px; border-radius: 5px; border: 1px solid #ddd; }
    
    /* Variant Styling */
    .variant-card { background: #fafbfd; border: 1px solid #e2e7f1; padding: 15px; border-radius: 10px; margin-bottom: 12px; position: relative; }
    .color-group { margin-bottom: 20px; }
    .sizes-wrapper { margin-left: 20px; }
    .size-row { background: #fff; border: 1px solid #dee2e6; }

    /* Toggle Switch */
    .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 20px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #0acf97; }
    input:checked + .slider:before { transform: translateX(20px); }
</style>
@endsection

@section('content')
@php
    $hasVariants = $edit_data->variantPrices && $edit_data->variantPrices->count() > 0;
    // WooCommerce-like product types: simple, variable, digital
    $dbType   = $edit_data->product_type ?? ($edit_data->is_digital ? 'digital' : 'simple');
    $currentType = old('product_type', $dbType);
    $isDigital   = $currentType === 'digital' || $edit_data->is_digital;
    $isVariable  = $currentType === 'variable' || $hasVariants;
    // ⭐ Batch-wise pricing engine — when ON, sell prices are managed on
    //    /admin/purchases/manage, NOT here (this page keeps variant identity only).
    $batchWise = (bool) config('pricing.batch_wise', false);
    // Pre-build variant select options for wholesale JS
    $wholesaleVariantOptions = '';
    if ($hasVariants && isset($allVariants)) {
        $wholesaleVariantOptions .= '<option value="">'.__('All Variants').'</option>';
        foreach ($allVariants as $vp) {
            $vpColorName = $vp->color ? ($vp->color->colorName ?? $vp->color->name) : '';
            $vpSizeName  = $vp->size ? ($vp->size->sizeName ?? $vp->size->name) : '';
            $variantLabel = trim($vpColorName . ' ' . $vpSizeName);
            $wholesaleVariantOptions .= '<option value="'.$vp->id.'">'.($variantLabel ?: __('No Variant')).'</option>';
        }
    }
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between py-3">
                <h4 class="page-title mb-0">Edit Product: {{ $edit_data->name }}</h4>
                <div class="page-title-right">
                    <a href="{{route('inhouse.products.index')}}" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fe-list me-1"></i>{{ __('Manage Products') }}</a>
                </div>
            </div>
        </div>
    </div>
    <form action="{{route('products.update')}}" method="POST" data-parsley-validate="" enctype="multipart/form-data" name="editForm">
        @csrf
        <input type="hidden" value="{{$edit_data->id}}" name="id" />

        <div class="row">
            <div class="col-lg-8">
                
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-info me-1"></i> {{ __('Basic Information') }} </div>

                        <div class="form-group mb-3">
                            <label for="name" class="form-label"> {{ __('Product Name *') }} </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   name="name" value="{{$edit_data->name }}" id="name" required />
                            @error('name')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label"> {{ __('Categories *') }} </label>
                                <select class="form-control form-select select2 @error('category_id') is-invalid @enderror"
                                        name="category_id" id="category_id" required>
                                    <optgroup>
                                        <option value=""> {{ __('Select..') }} </option>
                                        @foreach($categories as $category)
                                            <option value="{{$category->id}}" @if($edit_data->category_id==$category->id) selected @endif>
                                                {{$category->name}}
                                            </option>
                                            @foreach ($category->childrenCategories as $childCategory)
                                                <option value="{{$childCategory->id}}" @if($edit_data->category_id==$childCategory->id) selected @endif>
                                                    - {{$childCategory->name}}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </optgroup>
                                </select>
                                @error('category_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="subcategory_id" class="form-label"> {{ __('SubCategories') }} </label>
                                <select class="form-control form-select select2 @error('subcategory_id') is-invalid @enderror"
                                        id="subcategory_id" name="subcategory_id">
                                    <optgroup>
                                        <option value=""> {{ __('Select..') }} </option>
                                        @foreach($subcategory as $value)
                                            <option value="{{$value->id}}" @if($edit_data->subcategory_id==$value->id) selected @endif>
                                                {{$value->subcategoryName}}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                @error('subcategory_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="childcategory_id" class="form-label"> {{ __('Child Categories') }} </label>
                                <select class="form-control form-select select2 @error('childcategory_id') is-invalid @enderror"
                                        id="childcategory_id" name="childcategory_id">
                                    <optgroup>
                                        <option value=""> {{ __('Select..') }} </option>
                                        @foreach($childcategory as $value)
                                            <option value="{{$value->id}}" @if($edit_data->childcategory_id==$value->id) selected @endif>
                                                {{$value->childcategoryName}}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                @error('childcategory_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" rows="6"
                                      class="summernote form-control @error('description') is-invalid @enderror">
                                {{$edit_data->description}}
                            </textarea>
                            @error('description')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="note" class="form-label">{{ __('Note') }}</label>
                            <textarea name="note" rows="2"
                                      class="form-control @error('note') is-invalid @enderror">{{$edit_data->note}}</textarea>
                            @error('note')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- VARIANT PRICE CARD --}}
                <div class="card mb-4" id="variant_section" style="{{ $hasVariants ? '' : 'display:none;' }}">
                    <div class="card-body">
                        <div class="section-title d-flex justify-content-between align-items-center">
                            <span><i class="fe-layers me-1"></i> {{ __('Product Variants (Color & Size)') }} </span>
                            <button type="button" class="btn btn-sm btn-success add-variant rounded-pill px-3"><i class="fa fa-plus me-1"></i> {{ __('Add Variant') }} </button>
                        </div>
                        <small class="text-muted d-block mb-2">
                            <i class="fe-info me-1"></i> {{ __('Variant sell price & stock are managed per batch (Purchase → Batch Management). The picked variant image is saved to the variant automatically.') }}
                        </small>

                        <div id="variant-wrapper">
                            @php
                                // Each variant = its own row (WooCommerce-style)
                                // No grouping by color — every color+size combo is a separate row
                                $allVariants = $edit_data->variantPrices;
                                $variantIndex = 0;
                            @endphp
                            
                            @forelse($allVariants as $vp)
                                @php
                                    $vpColorId = $vp->color_id ?? '';
                                    $vpSizeId  = $vp->size_id ?? '';
                                @endphp
                                <div class="variant-card variant-item">
                                    <div class="row align-items-end">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">{{ __('Color') }}</label>
                                            <select name="variant_price[{{ $variantIndex }}][color_id]" class="form-control select2 variant-color-select">
                                                <option value=""> {{ __('Select Color (Optional)') }} </option>
                                                @foreach($totalcolors as $color)
                                                    <option value="{{ $color->id }}" {{ $vpColorId == $color->id ? 'selected' : '' }}>
                                                        {{ $color->colorName ?? $color->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">{{ __('Size') }}</label>
                                            <select name="variant_price[{{ $variantIndex }}][size_id]" class="form-control select2 variant-size-select">
                                                <option value=""> {{ __('Select Size (Optional)') }} </option>
                                                @foreach($totalsizes as $size)
                                                    <option value="{{ $size->id }}" {{ $vpSizeId == $size->id ? 'selected' : '' }}>
                                                        {{ $size->sizeName ?? $size->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">{{ __('Barcode') }} <small class="text-muted">(Optional)</small></label>
                                            <input type="text" name="variant_price[{{ $variantIndex }}][barcode]"
                                                   value="{{ $vp->barcode }}" class="form-control" placeholder="Scan or enter barcode">
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label"> {{ __('Variant Image') }} </label>
                                            @php
                                                $firstImg = $edit_data->images->filter(function($img) use ($vp) {
                                                    return ($img->color_id == $vp->color_id || (empty($img->color_id) && empty($vp->color_id)))
                                                        && ($img->size_id == $vp->size_id || (empty($img->size_id) && empty($vp->size_id)));
                                                })->unique('image')->first();
                                            @endphp
                                            <div class="variant-img-upload">
                                                <input type="hidden" name="variant_image[{{ $variantIndex }}][image]" class="variant-media-path" id="variant_image_{{ $variantIndex }}_image" value="{{ $firstImg ? $firstImg->image : '' }}">
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary variant-media-pick rounded-pill px-3">
                                                        <i class="fe-image me-1"></i> {{ __('Media Library') }}
                                                    </button>
                                                    <img class="variant-media-preview rounded border" id="variant_image_{{ $variantIndex }}_preview" src="{{ $firstImg ? asset($firstImg->image) : '' }}" alt=""
                                                         style="width:52px;height:52px;object-fit:cover;{{ $firstImg ? '' : 'display:none;' }}">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-2 mb-2 d-flex justify-content-end">
                                            <button type="button" class="btn btn-danger remove-variant" style="margin-top:5px;" title="{{ __('Remove Variant') }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fa fa-info-circle"></i> 
                                                আপনি শুধু Color, শুধু Size, অথবা Color + Size উভয় add করতে পারবেন
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                @php $variantIndex++; @endphp
                            @empty
                                <div class="variant-card variant-item">
                                    <div class="row align-items-end">
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">{{ __('Color') }}<small class="text-muted">(Optional)</small></label>
                                            <select name="variant_price[0][color_id]" class="form-control select2 variant-color-select">
                                                <option value=""> {{ __('Select Color (Optional)') }} </option>
                                                @foreach($totalcolors as $color)
                                                    <option value="{{ $color->id }}">{{ $color->colorName ?? $color->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">{{ __('Size') }}<small class="text-muted">(Optional)</small></label>
                                            <select name="variant_price[0][size_id]" class="form-control select2 variant-size-select">
                                                <option value=""> {{ __('Select Size (Optional)') }} </option>
                                                @foreach($totalsizes as $size)
                                                    <option value="{{ $size->id }}">{{ $size->sizeName ?? $size->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">{{ __('Barcode') }} <small class="text-muted">(Optional)</small></label>
                                            <input type="text" name="variant_price[0][barcode]"
                                                   class="form-control" placeholder="Scan or enter barcode">
                                        </div>

                                        <div class="col-md-2 mb-2">
                                            <label class="form-label"> {{ __('Variant Image') }} </label>
                                            <div class="variant-img-upload">
                                                <input type="hidden" name="variant_image[0][image]" class="variant-media-path" id="variant_image_0_image" value="">
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary variant-media-pick rounded-pill px-3">
                                                        <i class="fe-image me-1"></i> {{ __('Media Library') }}
                                                    </button>
                                                    <img class="variant-media-preview rounded border" id="variant_image_0_preview" src="" alt=""
                                                         style="width:52px;height:52px;object-fit:cover;display:none;">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-1 mb-2 d-flex justify-content-end">
                                            <button type="button" class="btn btn-danger remove-variant" style="margin-top:5px;" title="{{ __('Remove Variant') }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fa fa-info-circle"></i> 
                                                আপনি শুধু Color, শুধু Size, অথবা Color + Size উভয় add করতে পারবেন
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>



                @if(!$batchWise)
                {{-- 🛡️ WARRANTY TIERS (legacy — in batch-wise mode tiers are managed on the Purchase page) --}}
                @php
                    $warrantyTiers = $warrantyTiers ?? collect();
                    $supplierWarranty = $supplierWarranty ?? null;
                    $supplierDays = $supplierWarranty ? $supplierWarranty->remaining_days : 0;
                @endphp
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title d-flex justify-content-between align-items-center">
                            <span><i class="fe-shield me-1"></i> {{ __('Warranty Tiers') }}
                                @if($supplierWarranty)
                                <small class="text-success ms-2">(Supplier warranty valid {{ $supplierDays }} days, till {{ $supplierWarranty->warranty_end_date->format('d M') }})</small>
                                @endif
                            </span>
                            <button type="button" id="add-warranty-tier" class="btn btn-sm btn-success rounded-pill px-3">
                                <i class="fa fa-plus me-1"></i> {{ __('Add Tier') }}
                            </button>
                        </div>

                        @if($supplierWarranty)
                        <div class="alert alert-info py-2 mb-3 small">
                            Supplier Warranty: {{ $supplierWarranty->warranty_days }} days — 
                            <strong>{{ $supplierDays }} days remaining</strong> 
                            (expires {{ $supplierWarranty->warranty_end_date->format('d M Y') }})
                        </div>
                        @endif

                        <div id="warranty-wrapper">
                            @php $wIndex = 0; @endphp
                            @forelse($warrantyTiers as $tier)
                                @include('backEnd.product._warranty_row', ['idx' => $wIndex, 'tier' => $tier, 'supplierDays' => $supplierDays, 'supplierWarranty' => $supplierWarranty, 'hasVariants' => $hasVariants, 'allVariants' => $allVariants])
                                @php $wIndex++; @endphp
                            @empty
                                @include('backEnd.product._warranty_row', ['idx' => $wIndex++, 'tier' => null, 'supplierDays' => $supplierDays, 'supplierWarranty' => $supplierWarranty, 'hasVariants' => $hasVariants, 'allVariants' => $allVariants, 'defaultType' => 'none'])
                                @if($supplierWarranty)
                                @include('backEnd.product._warranty_row', ['idx' => $wIndex++, 'tier' => null, 'supplierDays' => $supplierDays, 'supplierWarranty' => $supplierWarranty, 'hasVariants' => $hasVariants, 'allVariants' => $allVariants, 'defaultType' => 'supplier_warranty'])
                                @endif
                                @include('backEnd.product._warranty_row', ['idx' => $wIndex++, 'tier' => null, 'supplierDays' => $supplierDays, 'supplierWarranty' => $supplierWarranty, 'hasVariants' => $hasVariants, 'allVariants' => $allVariants, 'defaultType' => 'extended_warranty'])
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif

                {{-- SEO CONFIG CARD --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-search me-1"></i> {{ __('SEO Configuration') }} </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="meta_title" class="form-label">{{ __('Meta Title') }}</label>
                                <input type="text" name="meta_title" id="meta_title" class="form-control"
                                       value="{{ $edit_data->meta_title ?? $edit_data->name }}"
                                       placeholder="Enter meta title">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="meta_keywords" class="form-label">{{ __('Meta Keywords') }}</label>
                                <input type="text" name="meta_keywords" id="meta_keywords" class="form-control"
                                       value="{{ $edit_data->meta_keywords ?? '' }}"
                                       placeholder="meta1, meta2, meta3">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="meta_description" class="form-label">{{ __('Meta Description') }}</label>
                                <textarea name="meta_description" id="meta_description" class="form-control" rows="3"
                                          placeholder="Enter short SEO description...">{{ $edit_data->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($edit_data->description), 160) }}</textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="meta_image" class="form-label"> {{ __('Meta Image (og:image)') }} </label>

                                {{-- 🎨 Media Library — primary option (single image) --}}
                                <div class="border rounded p-2 mb-2" style="background:#f4f7ff;border-color:#d3e0ff!important;">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <button type="button" class="btn btn-primary btn-sm"
                                                onclick="openMediaPicker('#meta_image_url', '#metaImagePreview', 'path')">
                                            <i class="fe-image me-1"></i> {{ __('Choose Meta Image from Media Library') }}
                                        </button>
                                        <small class="text-muted">Pick one image, then press “Insert”.</small>
                                    </div>
                                    <input type="hidden" name="meta_image_url" id="meta_image_url"
                                           value="{{ (strpos($edit_data->meta_image ?? '', 'uploads/media/') !== false) ? $edit_data->meta_image : '' }}">
                                    <img id="metaImagePreview" src="{{ asset($edit_data->meta_image) }}" alt="Meta Image"
                                         class="border rounded mt-2" width="120"
                                         style="{{ !empty($edit_data->meta_image) ? '' : 'display:none;' }}">
                                </div>

                                <small class="text-muted d-block mt-2">Recommended size: 1200x630px</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">



                {{-- PRICING & INVENTORY CARD --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-dollar-sign me-1"></i> {{ __('Inventory') }} </div>



                        <div class="form-group mb-3">
                            <label for="pro_unit" class="form-label">{{ __('Unit') }}</label>
                            <input type="text" class="form-control @error('pro_unit') is-invalid @enderror"
                                   name="pro_unit" value="{{ $edit_data->pro_unit }}" id="pro_unit" />
                            @error('pro_unit')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        {{-- 📦 Batch-wise stock — read only, click row for details --}}
                        <div class="form-group mb-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>{{ __('Total Stock') }}</span>
                                <span class="badge bg-primary">{{ $edit_data->stock }} {{ __('in stock') }}</span>
                            </label>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover mb-0 batch-stock-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Batch') }}</th>
                                            <th class="text-end">{{ __('Qty') }}</th>
                                            <th class="text-end">{{ __('Unit Cost') }}</th>
                                            <th class="text-end">{{ __('Sell') }}</th>
                                            <th>{{ __('Supplier') }}</th>
                                            <th class="text-end">{{ __('Expiry') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($edit_data->stockBatches as $batch)
                                        @php
                                            $batchData = [
                                                'batch_no'   => $batch->batch_no ?: 'Batch #' . $batch->id,
                                                'type'       => $batch->type,
                                                'quantity'   => $batch->quantity,
                                                'remaining'  => $batch->remaining_qty,
                                                'unit_cost'  => $batch->unit_cost,
                                                'sell_price' => $batch->selling_price,
                                                'supplier'   => $batch->supplier->name ?? '—',
                                                'purchase'   => $batch->purchase?->invoice_no ?: ('#' . $batch->purchase_id),
                                                'mfg'        => $batch->mfg_date?->format('d M, Y') ?? '—',
                                                'exp'        => $batch->exp_date?->format('d M, Y') ?? '—',
                                                'sn_in'      => is_array($batch->sn_stock) ? count($batch->sn_stock) : 0,
                                                'sn_sold'    => is_array($batch->sn_sold) ? count($batch->sn_sold) : 0,
                                                'created'    => $batch->created_at?->format('d M, Y h:i A'),
                                            ];
                                        @endphp
                                        <tr class="batch-row" style="cursor:pointer"
                                            title="{{ __('Click to view batch details') }}"
                                            data-batch="{{ e(json_encode($batchData, JSON_UNESCAPED_SLASHES)) }}">
                                            <td><strong>{{ $batch->batch_no ?: 'Batch #'.$batch->id }}</strong></td>
                                            <td class="text-end">{{ $batch->remaining_qty }}</td>
                                            <td class="text-end">৳{{ number_format($batch->unit_cost, 2) }}</td>
                                            <td class="text-end">@if($batch->selling_price)৳{{ number_format($batch->selling_price, 2) }}@else<span class="text-muted">—</span>@endif</td>
                                            <td><small>{{ $batch->supplier->name ?? '—' }}</small></td>
                                            <td class="text-end"><small>{{ $batch->exp_date?->format('d M, Y') ?? '—' }}</small></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">
                                                {{ __('No batches yet — stock comes from purchases.') }}
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    @if($edit_data->stockBatches->isNotEmpty())
                                    <tfoot class="table-light">
                                        <tr>
                                            <th>{{ __('Total') }}</th>
                                            <th class="text-end">{{ $edit_data->stockBatches->sum('remaining_qty') }}</th>
                                            <th colspan="4"></th>
                                        </tr>
                                    </tfoot>
                                    @endif
                                </table>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="fe-info me-1"></i>{{ __('Click a batch row to view batch details. Stock is managed through purchases.') }}
                            </small>
                            @if((int) $edit_data->stock > 0 && $edit_data->stockBatches->isEmpty())
                            <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small">
                                <i class="fe-alert-triangle me-1"></i>
                                {{ __('This product has :qty units in stock but no batch records yet. Use a Stock Adjustment (correction) to create a batch so the ledger matches.', ['qty' => $edit_data->stock]) }}
                            </div>
                            @endif
                        </div>

                        <div class="form-group mb-3">
                            <label for="brand_id" class="form-label">{{ __('Brand') }}</label>
                            <select class="form-control select2 @error('brand_id') is-invalid @enderror"
                                    name="brand_id">
                                <option value=""> {{ __('Select..') }} </option>
                                @foreach($brands as $value)
                                    <option value="{{$value->id}}" @if($edit_data->brand_id==$value->id) selected @endif>
                                        {{$value->name}}
                                    </option>
                                @endforeach
                            </select>
                            @error('brand_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        {{-- 🆕 Barcode & Stock Management Fields --}}
                        <div class="section-title mt-4"><i class="fe-tag me-1"></i> {{ __('Barcode & Stock Settings') }} </div>
                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <label class="form-label"> {{ __('Barcode') }} </label>
                                <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $edit_data->barcode) }}" placeholder="Scan or enter barcode">
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label"> {{ __('Barcode Type') }} </label>
                                <select name="barcode_type" class="form-control form-select">
                                    <option value="C128" {{ (old('barcode_type') ?: $edit_data->barcode_type) === 'C128' ? 'selected' : '' }}>Code 128</option>
                                    <option value="C39" {{ (old('barcode_type') ?: $edit_data->barcode_type) === 'C39' ? 'selected' : '' }}>Code 39</option>
                                    <option value="EAN13" {{ (old('barcode_type') ?: $edit_data->barcode_type) === 'EAN13' ? 'selected' : '' }}>EAN-13</option>
                                    <option value="UPCA" {{ (old('barcode_type') ?: $edit_data->barcode_type) === 'UPCA' ? 'selected' : '' }}>UPC-A</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"> {{ __('Costing Method') }} </label>
                                <select name="costing_method" class="form-control form-select">
                                    <option value="fifo" {{ (old('costing_method') ?: ($edit_data->costing_method ?: 'fifo')) === 'fifo' ? 'selected' : '' }}>FIFO (First In, First Out)</option>
                                    <option value="lifo" {{ (old('costing_method') ?: ($edit_data->costing_method ?: 'fifo')) === 'lifo' ? 'selected' : '' }}>LIFO (Last In, First Out)</option>
                                    <option value="average" {{ (old('costing_method') ?: ($edit_data->costing_method ?: 'fifo')) === 'average' ? 'selected' : '' }}>Weighted Average</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"> {{ __('Low Stock Threshold') }} </label>
                                <input type="number" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', $edit_data->low_stock_threshold ?? 0) }}" placeholder="0 = disabled" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"> {{ __('Weight') }} </label>
                                <input type="text" name="weight" class="form-control" value="{{ old('weight', $edit_data->weight) }}" placeholder="e.g. 0.5 kg">
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" name="allow_negative_stock" class="form-check-input" value="1" id="allow_negative_stock" {{ (old('allow_negative_stock') ?: $edit_data->allow_negative_stock) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_negative_stock">{{ __('Allow Negative Stock') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MEDIA CARD --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-image me-1"></i> {{ __('Media & Video') }} </div>

                        <div class="form-group mb-3">
                            <label class="form-label"> {{ __('Product Gallery Images') }} </label>

                            {{-- 🎨 MEDIA LIBRARY — primary option --}}
                            <div class="border rounded p-3 mb-2" style="background:#f4f7ff;border-color:#d3e0ff!important;">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary btn-sm"
                                            onclick="openMediaPicker('#media_image_urls_json', null, 'path', true)">
                                        <i class="fe-image me-1"></i> {{ __('Add Images from Media Library') }}
                                    </button>
                                    <small class="text-muted">Select one or more images, then press “Add Selected”.</small>
                                    <small class="text-muted text-truncate ms-auto" id="media_image_urls_json_file" style="max-width:220px;"></small>
                                </div>
                                <input type="hidden" id="media_image_urls_json">
                                <div id="mediaPickedPreviews" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>

                            <div class="product_img d-flex flex-wrap">
                                @foreach($edit_data->images->filter(fn($img) => !$img->color_id && !$img->size_id) as $image)
                                    <div class="position-relative me-2 mb-2">
                                        <img src="{{asset($image->image)}}" class="edit-image border" alt="">
                                        <a href="{{route('products.image.remove',['id'=>$image->id])}}"
                                           class="btn btn-xs btn-warning waves-effect waves-light position-absolute top-0 end-0 rounded-circle"
                                           style="padding: 0px 4px; top: -5px; right: -5px;"
                                           title="{{ __('Remove from product (file stays in Media Library)') }}"
                                           onclick="return confirm('{{ __("Remove this image from the product? The file will remain in the Media Library.") }}')">
                                            <i class="mdi mdi-close"></i>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                            @php $colorSizeImages = $edit_data->images->filter(fn($img) => $img->color_id || $img->size_id); @endphp
                            @if($colorSizeImages->isNotEmpty())
                            <div class="mt-3">
                                <label class="form-label small text-muted"> {{ __('Color/Size Images') }} </label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($colorSizeImages as $img)
                                        <div class="position-relative">
                                            <img src="{{asset($img->image)}}" class="edit-image border" alt="">
                                            <span class="badge bg-info position-absolute bottom-0 start-0" style="font-size:9px;">
                                                {{ $img->color ? ($img->color->colorName ?? $img->color->name) : '-' }} / {{ $img->size ? ($img->size->sizeName ?? $img->size->name) : '-' }}
                                            </span>
                                            <a href="{{route('products.image.remove',['id'=>$img->id])}}" class="btn btn-xs btn-warning position-absolute top-0 end-0 rounded-circle" style="padding:0 4px;top:-5px;right:-5px;" title="{{ __('Remove from product (file stays in Media Library)') }}" onclick="return confirm('{{ __("Remove this image from the product? The file will remain in the Media Library.") }}')"><i class="mdi mdi-close"></i></a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                        </div>

                        {{-- ===== VIDEO SECTION (EDIT) ===== --}}
                        @php
                            $existingVideoType = $edit_data->pro_video_type ?? ($edit_data->pro_video ? 'youtube' : null);
                        @endphp
                        <div class="form-group mb-0">
                            <label class="form-label fw-semibold">প্রোডাক্ট ভিডিও</label>
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pro_video_source"
                                           id="vs_yt_e" value="youtube"
                                           {{ $existingVideoType !== 'upload' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="vs_yt_e">
                                        <i class="fa fa-youtube-play text-danger me-1"></i> YouTube লিংক
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pro_video_source"
                                           id="vs_up_e" value="upload"
                                           {{ $existingVideoType === 'upload' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="vs_up_e">
                                        <i class="fa fa-upload text-primary me-1"></i> ভিডিও আপলোড
                                    </label>
                                </div>
                            </div>

                            {{-- YouTube input --}}
                            <div id="yt_section_e" style="{{ $existingVideoType === 'upload' ? 'display:none;' : '' }}">
                                <input type="text" name="pro_video" id="pro_video_e"
                                       class="form-control @error('pro_video') is-invalid @enderror"
                                       value="{{ $edit_data->pro_video }}"
                                       placeholder="YouTube URL বা Video ID">
                                @error('pro_video')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                                @if($edit_data->pro_video)
                                <div id="yt_preview_e" class="mt-2">
                                    <iframe id="yt_iframe_e" width="100%" height="200"
                                            src="https://www.youtube.com/embed/{{ $edit_data->pro_video }}"
                                            frameborder="0" allowfullscreen
                                            style="border-radius:8px;"></iframe>
                                </div>
                                @else
                                <div id="yt_preview_e" class="mt-2" style="display:none;">
                                    <iframe id="yt_iframe_e" width="100%" height="200"
                                            src="" frameborder="0" allowfullscreen
                                            style="border-radius:8px;"></iframe>
                                </div>
                                @endif
                                <small class="text-muted">YouTube full URL অথবা শুধু Video ID উভয়ই চলবে।</small>
                            </div>

                            {{-- Upload input --}}
                            <div id="up_section_e" style="{{ $existingVideoType === 'upload' ? '' : 'display:none;' }}">
                                @if($existingVideoType === 'upload' && $edit_data->pro_video_path)
                                <div class="mb-2 p-2 bg-light rounded d-flex align-items-center gap-2">
                                    <i class="fa fa-film text-primary"></i>
                                    <span style="font-size:12px;">বর্তমান ভিডিও: <strong>{{ basename($edit_data->pro_video_path) }}</strong></span>
                                    <a href="{{ asset($edit_data->pro_video_path) }}" target="_blank"
                                       class="btn btn-xs btn-outline-primary ms-auto" style="font-size:11px;padding:2px 8px;">
                                        <i class="fa fa-play"></i> দেখুন
                                    </a>
                                </div>
                                @endif
                                <input type="file" name="pro_video_file" id="pro_video_file_e"
                                       class="form-control" accept="video/mp4,video/webm,video/ogg">
                                <div id="up_preview_e" class="mt-2" style="display:none;">
                                    <video id="up_video_e" width="100%" height="220" controls
                                           style="border-radius:8px;background:#000;"></video>
                                </div>
                                <small class="text-muted">নতুন ভিডিও বেছে না নিলে পুরনোটাই থাকবে। MP4, WebM, OGG | সর্বোচ্চ 40MB।</small>
                            </div>
                        </div>
                        {{-- ===== /VIDEO SECTION (EDIT) ===== --}}
                    </div>
                </div>

                {{-- PRODUCT SETTINGS CARD --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-settings me-1"></i> {{ __('Product Settings') }} </div>

                        <div class="form-group mb-3">
                            <label for="product_type" class="form-label"> {{ __('Product Type') }} </label>
                            <select class="form-control bg-light" id="product_type" name="product_type">
                                <option value="simple" {{ $currentType === 'simple' ? 'selected' : '' }}>📦 Simple Product</option>
                                <option value="variable" {{ $currentType === 'variable' ? 'selected' : '' }}>🎨 Variable Product</option>
                                <option value="digital" {{ $currentType === 'digital' ? 'selected' : '' }}>💾 Digital Product</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <strong>Simple:</strong> Single price & stock | <strong>Variable:</strong> Multiple variants with different prices (Color, Size, etc.)
                            </small>
                        </div>

                        {{-- VARIANT SECTION TOGGLE --}}
                        <div class="form-group mb-3" id="variant_toggle_area">
                            <label class="form-label d-flex align-items-center gap-2">
                                <input type="checkbox" id="enable_variants" {{ $hasVariants ? 'checked' : '' }} 
                                       onchange="toggleVariantSection()" style="width:18px;height:18px;">
                                <span> {{ __('Enable Product Variants (Color & Size)') }} </span>
                            </label>
                            <small class="text-muted d-block"> {{ __('Check this to add multiple variations with different prices, stock & images per Color/Size combination.') }} </small>
                        </div>

                        {{-- DIGITAL PRODUCT CHECKBOX --}}
                        <div class="form-group mb-3">
                            <input type="hidden" name="is_digital" id="is_digital_hidden" value="{{ $isDigital ? 1 : 0 }}">
                            <label class="form-label d-flex align-items-center gap-2">
                                <input type="checkbox" id="is_digital_check" name="is_digital_check" {{ $isDigital ? 'checked' : '' }} 
                                       onchange="toggleDigitalSection()" style="width:18px;height:18px;">
                                <span> {{ __('Digital / Downloadable Product') }} </span>
                            </label>
                            <small class="text-muted d-block"> {{ __('No shipping required. Customers can download the product after purchase.') }} </small>
                        </div>

                        {{-- ADVANCE PAYMENT (PHYSICAL) --}}
                        <div id="advance_area" style="{{ $isDigital ? 'display:none;' : 'display:block;' }}">
                            <div class="form-group mb-3">
                                <label for="advance_amount" class="form-label"> {{ __('Advance Payment') }} </label>
                                <input type="text" class="form-control @error('advance_amount') is-invalid @enderror"
                                       name="advance_amount" id="advance_amount"
                                       value="{{ old('advance_amount', $edit_data->advance_amount) }}" />
                                @error('advance_amount')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        {{-- FREE DELIVERY --}}
                        <div class="form-group mb-3">
                            <label class="form-label"> {{ __('Free Delivery') }} </label>
                            <div class="d-flex align-items-center">
                                <label class="switch me-3">
                                    <input type="checkbox" value="1" name="free_delivery" {{ old('free_delivery', $edit_data->free_delivery) ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                                <small class="text-muted"> {{ __('Enable free delivery for this product (No shipping charge will be applied)') }} </small>
                            </div>
                        </div>

                        {{-- DIGITAL FIELDS --}}
                        <div id="digital_area" style="{{ $isDigital ? 'display:block;' : 'display:none;' }}" class="p-2 border rounded mb-3 bg-light">
                            <div class="mb-3">
                                @if($edit_data->digital_file)
                                    <label class="form-label d-block text-truncate">Current: <code>{{ $edit_data->digital_file }}</code></label>
                                @endif
                                <label for="digital_file" class="form-label"> {{ __('Change Digital File') }} </label>
                                <input type="file" class="form-control" name="digital_file" id="digital_file">
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label"><small> {{ __('Limit') }} </small></label>
                                    <input type="number" class="form-control form-control-sm"
                                           name="download_limit" id="download_limit"
                                           value="{{ old('download_limit', $edit_data->download_limit ?? 5) }}" min="1">
                                </div>
                                <div class="col-6">
                                    <label class="form-label"><small> {{ __('Days Exp.') }} </small></label>
                                    <input type="number" class="form-control form-control-sm"
                                           name="download_expire_days" id="download_expire_days"
                                           value="{{ old('download_expire_days', $edit_data->download_expire_days ?? 7) }}" min="1">
                                </div>
                            </div>
                        </div>

                        <div class="row-auto mb-3">
                        {{-- WARRANTY METHOD --}}
                        <div class="form-group mb-3">
                            <label for="warranty_method" class="form-label">🛡️ {{ __('Warranty Method') }} </label>
                            <select class="form-control" id="warranty_method" name="warranty_method">
                                <option value="active" {{ old('warranty_method', $edit_data->warranty_method ?? 'active') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ old('warranty_method', $edit_data->warranty_method ?? 'active') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                <option value="hidden" {{ old('warranty_method', $edit_data->warranty_method ?? 'active') === 'hidden' ? 'selected' : '' }}>{{ __('Hidden') }}</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <strong>Active:</strong> {{ __('Show warranty options on product page') }} |
                                <strong>Inactive:</strong> {{ __('Hide warranty section completely') }} |
                                <strong>Hidden:</strong> {{ __('Hide from frontend but keep warranty data') }}
                            </small>
                        </div>

                        {{-- 🏷️ Product Status: Active / Draft / Private --}}
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Product Status') }}</label>
                            <select class="form-control form-select" name="publish_status">
                                @php
                                    $currentPublish = old('publish_status', $edit_data->resolved_publish_status);
                                @endphp
                                <option value="active" {{ $currentPublish === 'active' ? 'selected' : '' }}>🟢 {{ __('Active (Present)') }}</option>
                                <option value="draft" {{ $currentPublish === 'draft' ? 'selected' : '' }}>📝 {{ __('Draft') }}</option>
                                <option value="private" {{ $currentPublish === 'private' ? 'selected' : '' }}>🔒 {{ __('Private') }}</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <strong>Active:</strong> {{ __('Visible in storefront') }} |
                                <strong>Draft:</strong> {{ __('Hidden (work in progress)') }} |
                                <strong>Private:</strong> {{ __('Hidden from customers') }}
                            </small>
                        </div>

                        {{-- FLAGS & SWITCHES --}}
                        <div class="row text-center mb-3">
                            <div class="col-4 mb-2">
                                <label for="topsale" class="d-block form-label">{{ __('Hot Deals') }}</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="topsale" @if($edit_data->topsale==1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="col-4 mb-2">
                                <label for="flashsale" class="d-block form-label">{{ __('Flash Sale') }}</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="flashsale" @if($edit_data->flashsale==1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="col-4 mb-2">
                                <label for="sold" class="form-label">{{ __('Sold Count') }}</label>
                                <input type="text" class="form-control @error('sold') is-invalid @enderror"
                                       name="sold" value="{{ $edit_data->sold }}" id="sold" />
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 shadow rounded-pill"><i class="fe-check-circle me-1"></i> {{ __('Update Product') }} </button>

                    </div>
                </div>
            </div>
            </div>
    </form>

    {{-- Reusable Media Gallery picker — "choose image from media library" --}}
    @include('backEnd.media._picker')

    {{-- 📦 Batch Details Modal --}}
    <div class="modal fade" id="batchDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fe-layers me-1"></i> {{ __('Batch Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-bordered mb-0">
                        <tr><th style="width:45%">{{ __('Batch No') }}</th><td id="bd_batch">—</td></tr>
                        <tr><th>{{ __('Type') }}</th><td id="bd_type">—</td></tr>
                        <tr><th>{{ __('Quantity') }}</th><td id="bd_qty">—</td></tr>
                        <tr><th>{{ __('Remaining') }}</th><td id="bd_remaining">—</td></tr>
                        <tr><th>{{ __('Unit Cost') }}</th><td id="bd_cost">—</td></tr>
                        <tr><th>{{ __('Selling Price') }}</th><td id="bd_price">—</td></tr>
                        <tr><th>{{ __('Supplier') }}</th><td id="bd_supplier">—</td></tr>
                        <tr><th>{{ __('Purchase Invoice') }}</th><td id="bd_purchase">—</td></tr>
                        <tr><th>{{ __('Mfg Date') }}</th><td id="bd_mfg">—</td></tr>
                        <tr><th>{{ __('Expiry Date') }}</th><td id="bd_exp">—</td></tr>
                        <tr><th>{{ __('SN In Stock') }}</th><td id="bd_snin">—</td></tr>
                        <tr><th>{{ __('SN Sold') }}</th><td id="bd_snsold">—</td></tr>
                        <tr><th>{{ __('Created') }}</th><td id="bd_created">—</td></tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden trigger (Bootstrap 5 data-API) so the modal works without JS bootstrap global --}}
    <button type="button" id="batchModalTrigger" class="d-none" data-bs-toggle="modal" data-bs-target="#batchDetailModal"></button>
</div>
@endsection

@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-validation.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-advanced.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs//summernote/summernote-lite.min.js"></script>

<script>
    $(".summernote").summernote({
        placeholder: "Enter Your Text Here",
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['table', ['table']],
            ['insert', ['link', 'video', 'mediaLibrary']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        buttons: {
            // Insert an image from the Media Gallery by URL (image link)
            mediaLibrary: function (context) {
                var ui = $.summernote.ui;
                var button = ui.button({
                    contents: '<i class="fa fa-image"></i> Media',
                    tooltip: 'Insert image from Media Library',
                    container: '.note-editor',
                    click: function () {
                        openMediaPickerFor(function (item) {
                            context.invoke('editor.insertImage', item.url);
                        }, 'url');
                    }
                });
                return button.render();
            }
        }
    });
</script>

<script>
    // ── Multiple images from Media Library ──
    // The picker (multi mode) writes a JSON array of paths into #media_image_urls_json;
    // we turn it into hidden inputs (media_image_urls[]) so the controller can save them all.
    (function () {
        var jsonInput = document.getElementById('media_image_urls_json');
        if (!jsonInput) return; // element not present → do nothing
        jsonInput.addEventListener('change', function () {
            var paths = [];
            try { paths = JSON.parse(this.value || '[]'); } catch (e) { return; }
            var wrap = document.getElementById('mediaPickedPreviews');
            if (!wrap) return;
            // Already-added media paths (so we can accumulate instead of replace)
            var existing = [];
            wrap.querySelectorAll('input[name="media_image_urls[]"]').forEach(function (i) { existing.push(i.value); });
            paths.forEach(function (p) {
                if (existing.indexOf(p) >= 0) return; // already added

                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'media_image_urls[]';
                inp.value = p;
                wrap.appendChild(inp);

                var div = document.createElement('div');
                div.className = 'position-relative me-2 mb-2';
                var img = document.createElement('img');
                img.src = (p.indexOf('public/') === 0) ? window.location.origin + '/' + p : p;
                img.style.cssText = 'width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;';
                div.appendChild(img);

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-xs btn-danger position-absolute top-0 end-0 rounded-circle';
                btn.style.cssText = 'padding:0 4px;top:-5px;right:-5px;font-size:11px;line-height:1;';
                btn.innerHTML = '&times;';
                btn.title = 'Remove';
                btn.onclick = function () { inp.remove(); div.remove(); };
                div.appendChild(btn);

                wrap.appendChild(div);
            });
        });
    })();
</script>

<script>
    $(document).ready(function () {
        // Gallery image add
        $(".increment-wrapper .btn-increment").click(function () {
            var html = $(".clone").html();
            $(".increment-wrapper").append(html);
        });
        $("body").on("click", ".btn-remove-image", function () {
            $(this).parents(".control-group").remove();
        });

        $(".select2").select2();
    });
</script>

<script>
    // Category to subcategory & childcategory
    $("#category_id").on("change", function () {
        var ajaxId = $(this).val();
        if (ajaxId) {
            $.ajax({
                type: "GET",
                url: "{{url('ajax-product-subcategory')}}?category_id=" + ajaxId,
                success: function (res) {
                    if (res) {
                        $("#subcategory_id").empty();
                        $("#subcategory_id").append('<option value="0"> {{ __('Choose...') }} </option>');
                        $.each(res, function (key, value) {
                            $("#subcategory_id").append('<option value="' + key + '">' + value + "</option>");
                        });
                    } else {
                        $("#subcategory_id").empty();
                    }
                },
            });
        } else {
            $("#subcategory_id").empty();
        }
    });

    $("#subcategory_id").on("change", function () {
        var ajaxId = $(this).val();
        if (ajaxId) {
            $.ajax({
                type: "GET",
                url: "{{url('ajax-product-childcategory')}}?subcategory_id=" + ajaxId,
                success: function (res) {
                    if (res) {
                        $("#childcategory_id").empty();
                        $("#childcategory_id").append('<option value="0"> {{ __('Choose...') }} </option>');
                        $.each(res, function (key, value) {
                            $("#childcategory_id").append('<option value="' + key + '">' + value + "</option>");
                        });
                    } else {
                        $("#childcategory_id").empty();
                    }
                },
            });
        } else {
            $("#childcategory_id").empty();
        }
    });

    // Set selected values on load
    document.forms["editForm"].elements["category_id"].value = "{{$edit_data->category_id}}";
    document.forms["editForm"].elements["subcategory_id"].value = "{{$edit_data->subcategory_id}}";
    document.forms["editForm"].elements["childcategory_id"].value = "{{$edit_data->childcategory_id}}";
</script>

{{-- Variant add/remove with Multiple Size Select --}}
<script>
$(function() {
    // Count existing variant rows
    let variantIndex = {{ $allVariants ? $allVariants->count() : 0 }};

    // Keep a template of the first row so we can always add a new variant,
    // even after every row (including the first) has been removed.
    var variantTemplate = $('#variant-wrapper .variant-item').first().prop('outerHTML');

    // Initialize Select2 on existing variant selects
    $('.variant-size-select').select2({ width: '100%' });
    $('.variant-color-select').select2({ width: '100%' });

    // Use jQuery event delegation for add/remove buttons
    $(document).on('click', '.add-variant', function() {
        var wrapper = $('#variant-wrapper');
        var firstRow = wrapper.find('.variant-item').first();
        if (!firstRow.length && variantTemplate) {
            firstRow = $(variantTemplate); // rebuild a fresh row from the template (not yet in DOM)
        }
        if (!firstRow.length) return;
        
        // Clone the first row
        var newRow = firstRow.clone();
        
        // Remove Select2 instances from clone
        newRow.find('.select2-container').remove();
        
        // Remove existing variant images from clone
        newRow.find('.variant-existing-imgs').remove();
        
        // Update all input/select names with new index
        newRow.find('input, select').each(function() {
            var oldName = $(this).attr('name');
            if (!oldName) return;
            
            if (oldName.includes('variant_image')) {
                $(this).attr('name', oldName.replace(/\[(\d+)\]/, '[' + variantIndex + ']'));
            } else {
                $(this).attr('name', oldName.replace(/\[\d+\]/, '[' + variantIndex + ']'));
            }
            
            // Clear values
            if ($(this).attr('type') === 'file') {
                $(this).val('');
                $(this).siblings('.variant-img-preview').hide().find('img').attr('src', '');
            } else if ($(this).is('input')) {
                $(this).val('');
            } else if ($(this).is('select')) {
                $(this).val(null);
            }
        });
        
        // Re-index the media picker ids + reset preview for the cloned row
        newRow.find('.variant-media-path').attr('id', 'variant_image_' + variantIndex + '_image').val('');
        newRow.find('.variant-media-preview').attr('id', 'variant_image_' + variantIndex + '_preview').attr('src', '').hide();

        // Change add button to remove button
        newRow.find('.add-variant')
            .removeClass('btn-success add-variant')
            .addClass('btn-danger remove-variant')
            .html('<i class="fa fa-trash"></i>');
        
        wrapper.append(newRow);
        
        // Reinitialize Select2 on new row
        newRow.find('.variant-size-select').select2({ width: '100%' });
        newRow.find('.variant-color-select').select2({ width: '100%' });
        
        variantIndex++;
    });

    // Remove variant row
    $(document).on('click', '.remove-variant', function() {
        $(this).closest('.variant-item').remove();
    });

    // Open the Media Library picker for a variant image row
    $(document).on('click', '.variant-media-pick', function () {
        var $cell = $(this).closest('.variant-img-upload');
        var $path = $cell.find('.variant-media-path');
        var $preview = $cell.find('.variant-media-preview');
        if (window.openMediaPicker && $path.length) {
            openMediaPicker('#' + $path.attr('id'), $preview.length ? '#' + $preview.attr('id') : null, 'path');
        }
    });
});
</script>

{{-- Product type toggle --}}
<script>
// Global toggle functions (called from onchange attributes in HTML)
function toggleVariantSection() {
    var checked = document.getElementById('enable_variants').checked;
    var variantSection = document.getElementById('variant_section');
    if (variantSection) {
        variantSection.style.display = checked ? '' : 'none';
    }
    // Sync product_type select (only if not digital)
    var productType = document.getElementById('product_type');
    var digitalCheck = document.getElementById('is_digital_check');
    if (productType && !digitalCheck.checked && productType.value !== 'digital') {
        productType.value = checked ? 'variable' : 'simple';
    }
    // Toggle pricing fields visibility
    var newPriceField = document.getElementById('new_price');
    if (newPriceField) {
        newPriceField.closest('.col-md-6').style.opacity = checked ? '0.5' : '1';
        newPriceField.readOnly = checked;
    }
}

function toggleDigitalSection() {
    var checked = document.getElementById('is_digital_check').checked;
    var digitalArea = document.getElementById('digital_area');
    var advanceArea = document.getElementById('advance_area');
    var productType = document.getElementById('product_type');
    var variantToggleArea = document.getElementById('variant_toggle_area');
    var variantSection = document.getElementById('variant_section');
    var digitalHidden = document.getElementById('is_digital_hidden');
    var newPriceField = document.getElementById('new_price');
    
    if (digitalHidden) digitalHidden.value = checked ? 1 : 0;
    if (digitalArea) digitalArea.style.display = checked ? 'block' : 'none';
    if (advanceArea) advanceArea.style.display = checked ? 'none' : 'block';
    
    if (checked) {
        // Digital product - hide variant options
        if (variantToggleArea) variantToggleArea.style.display = 'none';
        if (variantSection) variantSection.style.display = 'none';
        if (productType) productType.value = 'digital';
        // Enable price for digital products
        if (newPriceField) {
            newPriceField.closest('.col-md-6').style.opacity = '1';
            newPriceField.readOnly = false;
        }
    } else {
        // Physical product - show variant options
        if (variantToggleArea) variantToggleArea.style.display = '';
        // Restore variant section based on checkbox
        var variantCheck = document.getElementById('enable_variants');
        if (variantSection && variantCheck) {
            variantSection.style.display = variantCheck.checked ? '' : 'none';
        }
        if (productType) {
            var variantCheck2 = document.getElementById('enable_variants');
            productType.value = variantCheck2 && variantCheck2.checked ? 'variable' : 'simple';
        }
    }
}

/** Sync all UI sections based on the selected product_type value */
function syncProductTypeUI(productTypeValue) {
    var isDigitalChecked = document.getElementById('is_digital_check');
    var digitalHidden = document.getElementById('is_digital_hidden');
    var digitalArea = document.getElementById('digital_area');
    var advanceArea = document.getElementById('advance_area');
    var variantToggleArea = document.getElementById('variant_toggle_area');
    var variantSection = document.getElementById('variant_section');
    var variantCheck = document.getElementById('enable_variants');
    var newPriceField = document.getElementById('new_price');
    var wholesaleToggleCard = document.getElementById('wholesale_toggle_card');
    var wholesaleArea = document.getElementById('wholesale_area');
    var wholesaleCheck = document.getElementById('is_wholesale');

    if (productTypeValue === 'digital') {
        // Digital: hide variants, show digital fields
        if (isDigitalChecked) isDigitalChecked.checked = true;
        if (digitalHidden) digitalHidden.value = 1;
        if (digitalArea) digitalArea.style.display = 'block';
        if (advanceArea) advanceArea.style.display = 'none';
        if (variantToggleArea) variantToggleArea.style.display = 'none';
        if (variantSection) variantSection.style.display = 'none';
        if (variantCheck) variantCheck.checked = false;
        // Hide wholesale for digital products
        if (wholesaleToggleCard) wholesaleToggleCard.style.display = 'none';
        if (wholesaleArea) wholesaleArea.style.display = 'none';
        if (wholesaleCheck) wholesaleCheck.checked = false;
        // Enable price
        if (newPriceField) { newPriceField.closest('.col-md-6').style.opacity = '1'; newPriceField.readOnly = false; }
    } else {
        // Physical (simple or variable)
        if (isDigitalChecked) isDigitalChecked.checked = false;
        if (digitalHidden) digitalHidden.value = 0;
        if (digitalArea) digitalArea.style.display = 'none';
        if (advanceArea) advanceArea.style.display = 'block';
        if (variantToggleArea) variantToggleArea.style.display = '';
        
        var isVariable = productTypeValue === 'variable';
        if (variantCheck) variantCheck.checked = isVariable;
        if (variantSection) variantSection.style.display = isVariable ? '' : 'none';

        // Wholesale works for both simple and variable products
        if (wholesaleCheck && wholesaleArea) {
            wholesaleArea.style.display = wholesaleCheck.checked ? 'block' : 'none';
        }
        
        // Toggle price based on variable
        if (newPriceField) {
            newPriceField.closest('.col-md-6').style.opacity = isVariable ? '0.5' : '1';
            newPriceField.readOnly = isVariable;
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Digital checkbox
    var digitalCheck = document.getElementById('is_digital_check');
    if (digitalCheck) {
        digitalCheck.addEventListener('change', toggleDigitalSection);
    }
    
    // Variant checkbox
    var variantCheck = document.getElementById('enable_variants');
    if (variantCheck) {
        variantCheck.addEventListener('change', toggleVariantSection);
    }

    // Product type select change (WooCommerce-like: simple, variable, digital)
    var productTypeSelect = document.getElementById('product_type');
    if (productTypeSelect) {
        productTypeSelect.addEventListener('change', function() {
            syncProductTypeUI(this.value);
        });
    }

    // Initial state sync based on the currently selected product_type
    if (productTypeSelect) {
        syncProductTypeUI(productTypeSelect.value);
    } else {
        // Fallback to individual toggles
        toggleDigitalSection();
        toggleVariantSection();
    }

    // Wholesale toggle
    var wholesaleCheck = document.getElementById('is_wholesale');
    if (wholesaleCheck) {
        wholesaleCheck.addEventListener('change', function() {
            var wholesaleArea = document.getElementById('wholesale_area');
            if (this.checked) {
                wholesaleArea.style.display = 'block';
                wholesaleArea.querySelectorAll('input').forEach(function(input) {
                    input.setAttribute('required', 'required');
                });
            } else {
                wholesaleArea.style.display = 'none';
                wholesaleArea.querySelectorAll('input').forEach(function(input) {
                    input.removeAttribute('required');
                });
            }
        });
    }
    // Wholesale pricing tiers — with variant support
    var hasVariants = {{ $hasVariants ? 'true' : 'false' }};
    var wholesaleVariantOptions = {!! json_encode($wholesaleVariantOptions) !!};
    let wholesaleIndex = {{ ($wholesalePrices && $wholesalePrices->count() > 0) ? $wholesalePrices->count() : 1 }};
    
    $(document).on('click', '.add-wholesale-tier', function() {
        let wrapper = $('#wholesale-wrapper');
        let firstRow = wrapper.find('.variant-card').first();
        
        if (firstRow.length === 0) {
            // No rows exist — create a fresh empty row from scratch
            if (hasVariants) {
                var html = '<div class="variant-card"><div class="row align-items-end">' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Variant") }}</label><select name="wholesale_discount[' + wholesaleIndex + '][variant_id]" class="form-control select2 wholesale-variant-select">' + wholesaleVariantOptions + '</select></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Min Quantity") }}</label><input type="number" name="wholesale_discount[' + wholesaleIndex + '][min_quantity]" class="form-control" placeholder="e.g. 10"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Max Quantity") }}</label><input type="number" name="wholesale_discount[' + wholesaleIndex + '][max_quantity]" class="form-control" placeholder="e.g. 50 (optional)"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Wholesale Discount") }}</label><input type="number" step="0.01" name="wholesale_discount[' + wholesaleIndex + '][wholesale_price]" class="form-control" placeholder="0.00"></div>' +
                    '<div class="col-md-1 mb-2"><button type="button" class="btn btn-danger btn-remove-wholesale w-100" title="{{ __("Remove Tier") }}"><i class="fa fa-trash"></i></button></div>' +
                    '</div></div>';
            } else {
                var html = '<div class="variant-card"><div class="row align-items-end">' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Min Quantity") }}</label><input type="number" name="wholesale_discount[' + wholesaleIndex + '][min_quantity]" class="form-control" placeholder="e.g. 10"></div>' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Max Quantity") }}</label><input type="number" name="wholesale_discount[' + wholesaleIndex + '][max_quantity]" class="form-control" placeholder="e.g. 50 (optional)"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Wholesale Discount") }}</label><input type="number" step="0.01" name="wholesale_discount[' + wholesaleIndex + '][wholesale_price]" class="form-control" placeholder="0.00"></div>' +
                    '<div class="col-md-2 mb-2"><button type="button" class="btn btn-danger btn-remove-wholesale w-100" title="{{ __("Remove Tier") }}"><i class="fa fa-trash"></i></button></div>' +
                    '</div></div>';
            }
            wrapper.append(html);
            if (hasVariants) {
                wrapper.find('.wholesale-variant-select').last().select2({ width: '100%' });
            }
            wholesaleIndex++;
            return;
        }
        
        var newRow = firstRow.clone();
        
        // Remove Select2 containers from clone
        newRow.find('.select2-container').remove();
        
        // Rename input and select elements, clear values
        newRow.find('input, select').each(function(){
            let oldName = $(this).attr('name');
            if (oldName) {
                $(this).attr('name', oldName.replace(/\[\d+\]/, '[' + wholesaleIndex + ']'));
            }
            if ($(this).is('input')) {
                $(this).val('');
            } else if ($(this).is('select')) {
                $(this).val(null);
            }
        });

        // Ensure the cloned row has a remove button
        if (newRow.find('.btn-remove-wholesale').length === 0) {
            var btnCol = newRow.find('[class*="col-md-"]').last();
            btnCol.html('<button type="button" class="btn btn-danger btn-remove-wholesale w-100" title="{{ __("Remove Tier") }}"><i class="fa fa-trash"></i></button>');
        }
        
        wrapper.append(newRow);
        
        // Reinitialize Select2 on cloned variant selects
        newRow.find('.wholesale-variant-select').select2({ width: '100%' });
        
        wholesaleIndex++;
    });
    
    $(document).on('click', '.btn-remove-wholesale', function () {
        var wrapper = $('#wholesale-wrapper');
        $(this).parents('.variant-card').remove();
        
        // If all rows removed, add a fresh blank row so user can start over
        if (wrapper.find('.variant-card').length === 0) {
            if (hasVariants) {
                var html = '<div class="variant-card"><div class="row align-items-end">' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Variant") }}</label><select name="wholesale_discount[0][variant_id]" class="form-control select2 wholesale-variant-select">' + wholesaleVariantOptions + '</select></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Min Quantity") }}</label><input type="number" name="wholesale_discount[0][min_quantity]" class="form-control" placeholder="e.g. 10"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Max Quantity") }}</label><input type="number" name="wholesale_discount[0][max_quantity]" class="form-control" placeholder="e.g. 50 (optional)"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Wholesale Discount") }}</label><input type="number" step="0.01" name="wholesale_discount[0][wholesale_price]" class="form-control" placeholder="0.00"></div>' +
                    '<div class="col-md-1 mb-2"><button type="button" class="btn btn-success add-wholesale-tier w-100" title="{{ __("Add New Tier") }}"><i class="fa fa-plus"></i></button></div>' +
                    '</div></div>';
            } else {
                var html = '<div class="variant-card"><div class="row align-items-end">' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Min Quantity") }}</label><input type="number" name="wholesale_discount[0][min_quantity]" class="form-control" placeholder="e.g. 10"></div>' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Max Quantity") }}</label><input type="number" name="wholesale_discount[0][max_quantity]" class="form-control" placeholder="e.g. 50 (optional)"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Wholesale Discount") }}</label><input type="number" step="0.01" name="wholesale_discount[0][wholesale_price]" class="form-control" placeholder="0.00"></div>' +
                    '<div class="col-md-2 mb-2"><button type="button" class="btn btn-success add-wholesale-tier w-100" title="{{ __("Add New Tier") }}"><i class="fa fa-plus"></i></button></div>' +
                    '</div></div>';
            }
            wrapper.append(html);
            if (hasVariants) {
                wrapper.find('.wholesale-variant-select').last().select2({ width: '100%' });
            }
            wholesaleIndex = 1;
        }
    });

    // Variant Image Add/Remove
    let variantImgIndex = 1;
    $(".add-variant-image").click(function () {
        let wrapper = $("#variant-image-wrapper");
        let firstRow = wrapper.find(".variant-image-row").first().clone();
        firstRow.find('.select2-container').remove();
        firstRow.find('input[type="file"]').val('');
        firstRow.find('select').each(function(){
            let name = $(this).attr('name');
            if (name) $(this).attr('name', name.replace(/\[\d+\]/, '[' + variantImgIndex + ']'));
            $(this).val(null);
        });
        firstRow.find('input[type="file"]').each(function(){
            let name = $(this).attr('name');
            if (name) $(this).attr('name', name.replace(/\[\d+\]/, '[' + variantImgIndex + ']'));
        });
        firstRow.find('.btn-remove-variant-img').show();
        wrapper.append(firstRow);
        firstRow.find('.variant-img-color, .variant-img-size').select2({ width: '100%' });
        variantImgIndex++;
    });
    $("body").on("click", ".btn-remove-variant-img", function () {
        $(this).closest(".variant-image-row").remove();
    });
});

// ===== VIDEO SOURCE SWITCHER (Edit) =====
(function () {
    var radios = document.querySelectorAll('input[name="pro_video_source"]');
    var ytSec  = document.getElementById('yt_section_e');
    var upSec  = document.getElementById('up_section_e');

    function switchVideo(val) {
        if (val === 'upload') {
            if (ytSec) ytSec.style.display = 'none';
            if (upSec) upSec.style.display = '';
        } else {
            if (ytSec) ytSec.style.display = '';
            if (upSec) upSec.style.display = 'none';
        }
    }

    radios.forEach(function (r) {
        r.addEventListener('change', function () { switchVideo(this.value); });
    });

    // YouTube live preview
    var ytInput = document.getElementById('pro_video_e');
    if (ytInput) {
        ytInput.addEventListener('input', function () {
            var val = this.value.trim();
            var id  = extractYtId(val);
            var box = document.getElementById('yt_preview_e');
            var fr  = document.getElementById('yt_iframe_e');
            if (id && box && fr) {
                fr.src = 'https://www.youtube.com/embed/' + id;
                box.style.display = '';
            } else if (box && fr) {
                fr.src = '';
                box.style.display = 'none';
            }
        });
    }

    // Upload local preview
    var upInput = document.getElementById('pro_video_file_e');
    if (upInput) {
        upInput.addEventListener('change', function () {
            var file = this.files[0];
            var box  = document.getElementById('up_preview_e');
            var vid  = document.getElementById('up_video_e');
            if (file && box && vid) {
                vid.src = URL.createObjectURL(file);
                box.style.display = '';
            }
        });
    }

    function extractYtId(input) {
        if (!input) return null;
        if (/^[a-zA-Z0-9_-]{11}$/.test(input)) return input;
        var m = input.match(/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
        return m ? m[1] : null;
    }

    // 🛡️ Warranty Tier Management (legacy — only when the product page owns warranty tiers)
    @if(!$batchWise)
    const warrantyWrapper = document.getElementById('warranty-wrapper');
    const supplierDays = {{ $supplierDays ?? 0 }};

    function updateWarrantyDays(select) {
        const row = select.closest('.warranty-tier-row');
        const daysInput = row.querySelector('.warranty-days-input');
        const type = select.value;
        if (type === 'none') { daysInput.value = 0; daysInput.readOnly = true; }
        else if (type === 'supplier_warranty') { daysInput.value = supplierDays; daysInput.readOnly = true; }
        else { daysInput.readOnly = false; if (daysInput.value == 0) daysInput.value = 90; }
    }

    function reindexWarrantyRows() {
        warrantyWrapper.querySelectorAll('.warranty-tier-row').forEach((row, i) => {
            row.querySelectorAll('select, input').forEach(el => {
                if (el.name) el.name = el.name.replace(/warranty_tiers\\[\\d+\\]/, 'warranty_tiers[' + i + ']');
            });
        });
    }

    warrantyWrapper.addEventListener('change', function(e) {
        if (e.target.classList.contains('warranty-type-select')) updateWarrantyDays(e.target);
    });

    warrantyWrapper.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-warranty')) {
            const rows = warrantyWrapper.querySelectorAll('.warranty-tier-row');
            if (rows.length > 1) { e.target.closest('.warranty-tier-row').remove(); reindexWarrantyRows(); }
        }
    });

    document.getElementById('add-warranty-tier').addEventListener('click', function() {
        const rows = warrantyWrapper.querySelectorAll('.warranty-tier-row');
        const lastRow = rows[rows.length - 1];
        const clone = lastRow.cloneNode(true);
        const newIdx = rows.length;
        clone.querySelectorAll('input').forEach(inp => {
            inp.value = '';
            if (inp.classList.contains('warranty-days-input')) { inp.value = 90; inp.readOnly = false; }
        });
        clone.querySelectorAll('select').forEach(sel => {
            if (sel.classList.contains('warranty-type-select')) sel.value = 'extended_warranty';
            if (sel.name && sel.name.includes('is_active')) sel.value = '1';
        });
        reindexWarrantyRows();
        warrantyWrapper.appendChild(clone);
    });
    @endif

})();

// 📦 Batch row click → populate & open batch details modal
$(document).ready(function () {
    $('body').on('click', '.batch-row', function () {
        var raw = this.getAttribute('data-batch');
        if (!raw) return;
        var b;
        try { b = JSON.parse(raw); } catch (err) { return; }

        $('#bd_batch').text(b.batch_no || '—');
        $('#bd_type').text(b.type ? String(b.type).toUpperCase() : '—');
        $('#bd_qty').text(b.quantity != null ? b.quantity : '—');
        $('#bd_remaining').text(b.remaining != null ? b.remaining : '—');
        $('#bd_cost').text(b.unit_cost != null ? '৳' + parseFloat(b.unit_cost).toFixed(2) : '—');
        $('#bd_price').text(b.sell_price != null ? '৳' + parseFloat(b.sell_price).toFixed(2) : '—');
        $('#bd_supplier').text(b.supplier || '—');
        $('#bd_purchase').text(b.purchase && b.purchase !== '#' ? b.purchase : '—');
        $('#bd_mfg').text(b.mfg || '—');
        $('#bd_exp').text(b.exp || '—');
        $('#bd_snin').text(b.sn_in != null ? b.sn_in : 0);
        $('#bd_snsold').text(b.sn_sold != null ? b.sn_sold : 0);
        $('#bd_created').text(b.created || '—');

        // Bootstrap 5 data-API trigger
        var trigger = document.getElementById('batchModalTrigger');
        if (trigger) trigger.click();
    });
});
</script>
@endsection