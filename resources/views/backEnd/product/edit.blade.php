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
                                            <label class="form-label">{{ __('Price') }}</label>
                                            <input type="number" step="0.01" name="variant_price[{{ $variantIndex }}][price]"
                                                   value="{{ $vp->price }}" class="form-control" placeholder="{{ __('Enter Price') }}">
                                        </div>

                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">{{ __('Stock') }}</label>
                                            <input type="number" name="variant_price[{{ $variantIndex }}][stock]"
                                                   value="{{ $vp->stock }}" class="form-control" placeholder="0">
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label"> {{ __('Variant Image') }} </label>
                                            @php
                                                $matchImg = $edit_data->images->filter(function($img) use ($vp) {
                                                    return ($img->color_id == $vp->color_id || (empty($img->color_id) && empty($vp->color_id)))
                                                        && ($img->size_id == $vp->size_id || (empty($img->size_id) && empty($vp->size_id)));
                                                })->unique('image');
                                            @endphp
                                            @if($matchImg->isNotEmpty())
                                                <div class="variant-existing-imgs d-flex flex-wrap gap-1 mb-2">
                                                    @foreach($matchImg as $vImg)
                                                        <div class="position-relative">
                                                            <img src="{{ asset($vImg->image) }}" class="rounded border" style="width:50px;height:50px;object-fit:cover;" alt="">
                                                            <a href="{{ route('products.image.destroy', ['id' => $vImg->id]) }}" class="btn btn-xs btn-danger position-absolute top-0 end-0 rounded-circle" style="padding:0 4px;top:-4px;right:-4px;" onclick="return confirm('Delete this image?')"><i class="mdi mdi-close"></i></a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="variant-img-upload">
                                                <input type="file" name="variant_image[{{ $variantIndex }}][image]" class="form-control form-control-sm variant-img-input" accept="image/*">
                                                <div class="variant-img-preview mt-1" style="display:none;">
                                                    <img src="" alt="Preview" class="rounded border" style="max-width:60px;max-height:60px;object-fit:cover;">
                                                    <button type="button" class="btn btn-sm btn-danger variant-img-clear ms-1" title="{{ __('Remove') }}"><i class="fe-x"></i></button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-2 mb-2 d-flex justify-content-end">
                                            @if($loop->first)
                                                <button type="button" class="btn btn-success add-variant" style="margin-top:5px;">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-danger remove-variant" style="margin-top:5px;">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endif
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

                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">{{ __('Price') }}<small class="text-muted">(Optional)</small></label>
                                            <input type="number" step="0.01" name="variant_price[0][price]"
                                                   class="form-control" placeholder="{{ __('Enter Price') }}">
                                        </div>

                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">{{ __('Stock') }}</label>
                                            <input type="number" name="variant_price[0][stock]" class="form-control" placeholder="0">
                                        </div>

                                        <div class="col-md-2 mb-2">
                                            <label class="form-label"> {{ __('Variant Image') }} </label>
                                            <div class="variant-img-upload">
                                                <input type="file" name="variant_image[0][image]" class="form-control form-control-sm variant-img-input" accept="image/*">
                                                <div class="variant-img-preview mt-1" style="display:none;">
                                                    <img src="" alt="Preview" class="rounded border" style="max-width:60px;max-height:60px;object-fit:cover;">
                                                    <button type="button" class="btn btn-sm btn-danger variant-img-clear ms-1" title="{{ __('Remove') }}"><i class="fe-x"></i></button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-1 mb-2 d-flex justify-content-end">
                                            <button type="button" class="btn btn-success add-variant" style="margin-top:5px;">
                                                <i class="fa fa-plus"></i>
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

                {{-- WHOLESALE PRICING TIERS --}}
                <div id="wholesale_area" style="{{ old('is_wholesale', $edit_data->is_wholesale ?? 0) ? 'display:block;' : 'display:none;' }}" class="card mb-4">
                    <div class="card-body">
                        <div class="section-title d-flex justify-content-between align-items-center">
                            <span><i class="fe-dollar-sign me-1"></i> {{ __('Wholesale Pricing Tiers') }} </span>
                            <button type="button" class="btn btn-sm btn-success add-wholesale-tier rounded-pill px-3"><i class="fa fa-plus me-1"></i> {{ __('Add New Tier') }} </button>
                        </div>
                        
                        <div id="wholesale-wrapper">
                            @if($wholesalePrices && $wholesalePrices->count() > 0)
                                @foreach($wholesalePrices as $key => $tier)
                                    <div class="variant-card">
                                        <div class="row align-items-end">
                                            @if($hasVariants)
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label"> {{ __('Variant') }} </label>
                                                <select name="wholesale_discount[{{ $key }}][variant_id]" class="form-control select2 wholesale-variant-select">
                                                    <option value=""> {{ __('All Variants') }} </option>
                                                    @foreach($allVariants as $vp)
                                                        @php
                                                            $vpColorName = $vp->color ? ($vp->color->colorName ?? $vp->color->name) : '';
                                                            $vpSizeName  = $vp->size ? ($vp->size->sizeName ?? $vp->size->name) : '';
                                                            $variantLabel = trim($vpColorName . ' ' . $vpSizeName);
                                                        @endphp
                                                        <option value="{{ $vp->id }}" {{ $tier->variant_id == $vp->id ? 'selected' : '' }}>
                                                            {{ $variantLabel ?: __('No Variant') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @endif
                                            <div class="{{ $hasVariants ? 'col-md-2' : 'col-md-3' }} mb-2">
                                                <label class="form-label"> {{ __('Min Quantity') }} </label>
                                                <input type="number" name="wholesale_discount[{{ $key }}][min_quantity]" class="form-control" 
                                                       value="{{ old('wholesale_price.'.$key.'.min_quantity', $tier->min_quantity) }}">
                                            </div>
                                            <div class="{{ $hasVariants ? 'col-md-2' : 'col-md-3' }} mb-2">
                                                <label class="form-label"> {{ __('Max Quantity') }} </label>
                                                <input type="number" name="wholesale_discount[{{ $key }}][max_quantity]" class="form-control" 
                                                       value="{{ old('wholesale_price.'.$key.'.max_quantity', $tier->max_quantity) }}" placeholder="Optional">
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <label class="form-label"> {{ __('Discount (TK)') }} </label>
                                                <input type="number" step="0.01" name="wholesale_discount[{{ $key }}][wholesale_price]" class="form-control" 
                                                       value="{{ old('wholesale_price.'.$key.'.wholesale_price', $tier->wholesale_price) }}" placeholder="0.00">
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <label class="form-label"> {{ __('Stock Qty') }} </label>
                                                <input type="number" name="wholesale_discount[{{ $key }}][stock]" class="form-control" 
                                                       value="{{ old('wholesale_price.'.$key.'.stock', $tier->stock ?? 0) }}" placeholder="0">
                                            </div>
                                            <div class="{{ $hasVariants ? 'col-md-1' : 'col-md-2' }} mb-2">
                                                <button type="button" class="btn btn-danger btn-remove-wholesale w-100" title="{{ __('Remove Tier') }}"><i class="fa fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="variant-card">
                                    <div class="row align-items-end">
                                        @if($hasVariants)
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label"> {{ __('Variant') }} </label>
                                            <select name="wholesale_discount[0][variant_id]" class="form-control select2 wholesale-variant-select">
                                                {!! $wholesaleVariantOptions !!}
                                            </select>
                                        </div>
                                        @endif
                                        <div class="{{ $hasVariants ? 'col-md-2' : 'col-md-3' }} mb-2">
                                            <label class="form-label"> {{ __('Min Quantity') }} </label>
                                            <input type="number" name="wholesale_discount[0][min_quantity]" class="form-control" placeholder="e.g. 10">
                                        </div>
                                        <div class="{{ $hasVariants ? 'col-md-2' : 'col-md-3' }} mb-2">
                                            <label class="form-label"> {{ __('Max Quantity') }} </label>
                                            <input type="number" name="wholesale_discount[0][max_quantity]" class="form-control" placeholder="e.g. 50 (optional)">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label"> {{ __('Discount (TK)') }} </label>
                                            <input type="number" step="0.01" name="wholesale_discount[0][wholesale_price]" class="form-control" placeholder="0.00">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label"> {{ __('Stock Qty') }} </label>
                                            <input type="number" name="wholesale_discount[0][stock]" class="form-control" placeholder="0">
                                        </div>
                                        <div class="{{ $hasVariants ? 'col-md-1' : 'col-md-2' }} mb-2">
                                            <button type="button" class="btn btn-success add-wholesale-tier w-100" title="{{ __('Add New Tier') }}"><i class="fa fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 🛡️ WARRANTY TIERS --}}
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
                                <input type="file" name="meta_image" id="meta_image" class="form-control">

                                @if(!empty($edit_data->meta_image))
                                    <div class="mt-2">
                                        <img src="{{ asset($edit_data->meta_image) }}" alt="Meta Image"
                                             class="border rounded" width="120">
                                    </div>
                                @endif
                                <small class="text-muted d-block mt-1">Recommended size: 1200x630px</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                
                {{-- PRICING & INVENTORY CARD --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-dollar-sign me-1"></i> {{ __('Pricing & Inventory') }} </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="old_price" class="form-label"> {{ __('Old Price') }} </label>
                                <input type="text" class="form-control @error('old_price') is-invalid @enderror"
                                       name="old_price" value="{{ $edit_data->old_price }}" id="old_price" />
                                @error('old_price')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="new_price" class="form-label"> {{ __('New Price') }} <small class="text-muted">(Optional)</small></label>
                                <input type="text" class="form-control font-weight-bold @error('new_price') is-invalid @enderror"
                                       name="new_price" value="{{ $edit_data->new_price }}" id="new_price" placeholder="0" />
                                @error('new_price')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="reseller_price" class="form-label"> {{ __('Reseller Price') }} </label>
                            <input type="text" step="0.01" class="form-control @error('reseller_price') is-invalid @enderror"
                                   name="reseller_price" value="{{ old('reseller_price', $edit_data->reseller_price) }}" id="reseller_price" placeholder="Reseller price (optional)" />
                            <small class="text-muted"> {{ __('Special price for resellers. Leave empty if not applicable.') }} </small>
                            @error('reseller_price')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="supplier_price" class="form-label"> {{ __('Purchase Price') }} <small class="text-muted">(Updated automatically from purchases)</small></label>
                            <input type="text" step="0.01" class="form-control bg-light @error('supplier_price') is-invalid @enderror"
                                   name="supplier_price" value="{{ old('supplier_price', $edit_data->supplier_price ?? $edit_data->purchase_price) }}" id="supplier_price" placeholder="From purchase" readonly />
                            <small class="text-muted"><i class="fe-info me-1"></i> {{ __('This price comes from purchase records. Cannot be edited here.') }}</small>
                            @error('supplier_price')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label"> {{ __('Total Stock') }} <small class="text-muted">(Optional)</small></label>
                                <input type="text" class="form-control @error('stock') is-invalid @enderror"
                                       name="stock" value="{{ $edit_data->stock }}" id="stock" placeholder="0" />
                                @error('stock')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pro_unit" class="form-label">{{ __('Unit') }}</label>
                                <input type="text" class="form-control @error('pro_unit') is-invalid @enderror"
                                       name="pro_unit" value="{{ $edit_data->pro_unit }}" id="pro_unit" />
                            </div>
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
                                    <option value="">{{ __('Default (Global Setting)') }}</option>
                                    <option value="fifo" {{ (old('costing_method') ?: $edit_data->costing_method) === 'fifo' ? 'selected' : '' }}>FIFO (First In, First Out)</option>
                                    <option value="lifo" {{ (old('costing_method') ?: $edit_data->costing_method) === 'lifo' ? 'selected' : '' }}>LIFO (Last In, First Out)</option>
                                    <option value="average" {{ (old('costing_method') ?: $edit_data->costing_method) === 'average' ? 'selected' : '' }}>Weighted Average</option>
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
                            <div class="increment-wrapper">
                                <div class="control-group increment mb-2 image-row">
                                    <div class="row align-items-end g-2">
                                        <div class="col-md-10">
                                            <label class="form-label small">{{ __('Image') }}</label>
                                            <input type="file" name="image[]" class="form-control form-control-sm @error('image') is-invalid @enderror" accept="image/*" />
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-success btn-increment btn-sm w-100" type="button"><i class="fa fa-plus"></i></button>
                                        </div>
                                    </div>
                                    @error('image')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Hidden Clone for JS --}}
                            <div class="clone hide" style="display: none;">
                                <div class="control-group mt-2 image-row">
                                    <div class="row align-items-end g-2">
                                        <div class="col-md-10">
                                            <label class="form-label small">{{ __('Image') }}</label>
                                            <input type="file" name="image[]" class="form-control form-control-sm" accept="image/*" />
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-danger btn-remove-image btn-sm w-100" type="button"><i class="fa fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="product_img mt-3 d-flex flex-wrap">
                                @foreach($edit_data->images->filter(fn($img) => !$img->color_id && !$img->size_id) as $image)
                                    <div class="position-relative me-2 mb-2">
                                        <img src="{{asset($image->image)}}" class="edit-image border" alt="">
                                        <a href="{{route('products.image.destroy',['id'=>$image->id])}}"
                                           class="btn btn-xs btn-danger waves-effect waves-light position-absolute top-0 end-0 rounded-circle"
                                           style="padding: 0px 4px; top: -5px; right: -5px;"
                                           onclick="return confirm('Delete this image?')">
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
                                            <a href="{{route('products.image.destroy',['id'=>$img->id])}}" class="btn btn-xs btn-danger position-absolute top-0 end-0 rounded-circle" style="padding:0 4px;top:-5px;right:-5px;" onclick="return confirm('Delete?')"><i class="mdi mdi-close"></i></a>
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
                         <div class="form-group mb-3">
                            <label class="d-block form-label"> {{ __('Wholesale Product') }} </label>
                            <label class="switch">
                                <input type="checkbox" value="1" name="is_wholesale" id="is_wholesale" {{ old('is_wholesale', $edit_data->is_wholesale ?? 0) ? 'checked' : '' }}>
                                <span class="slider round"></span>
                            </label>
                        </div>

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

                        {{-- FLAGS & SWITCHES --}}
                        <div class="row text-center mb-3">
                            <div class="col-3 mb-2">
                                <label for="status" class="d-block form-label">{{ __('Status') }}</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="status" @if($edit_data->status==1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="col-3 mb-2">
                                <label for="topsale" class="d-block form-label">{{ __('Hot Deals') }}</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="topsale" @if($edit_data->topsale==1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="col-3 mb-2">
                                <label for="flashsale" class="d-block form-label">{{ __('Flash Sale') }}</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="flashsale" @if($edit_data->flashsale==1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                          
                            <div class="col-12 mb-2 text-start">
                                <label for="sold" class="form-label"> {{ __('Sold Count') }} </label>
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
    });
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
    
    // Initialize Select2 on existing variant selects
    $('.variant-size-select').select2({ width: '100%' });
    $('.variant-color-select').select2({ width: '100%' });

    // Use jQuery event delegation for add/remove buttons
    $(document).on('click', '.add-variant', function() {
        var wrapper = $('#variant-wrapper');
        var firstRow = wrapper.find('.variant-item').first();
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
                $(this).attr('name', 'variant_image[' + variantIndex + '][image]');
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

    // Variant image preview
    $(document).on('change', '.variant-img-input', function() {
        var $input = $(this);
        var $preview = $input.siblings('.variant-img-preview');
        var $img = $preview.find('img');
        var file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function(e) { $img.attr('src', e.target.result); $preview.show(); };
            reader.readAsDataURL(file);
        } else { $preview.hide(); $img.attr('src', ''); }
    });
    
    $(document).on('click', '.variant-img-clear', function() {
        var $preview = $(this).closest('.variant-img-preview');
        $preview.siblings('.variant-img-input').val('');
        $preview.find('img').attr('src', '');
        $preview.hide();
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
    var stockField = document.getElementById('stock');
    if (newPriceField) {
        newPriceField.closest('.col-md-6').style.opacity = checked ? '0.5' : '1';
        newPriceField.readOnly = checked;
    }
    if (stockField) {
        stockField.closest('.col-md-6').style.opacity = checked ? '0.5' : '1';
        stockField.readOnly = checked;
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
    var stockField = document.getElementById('stock');
    
    if (digitalHidden) digitalHidden.value = checked ? 1 : 0;
    if (digitalArea) digitalArea.style.display = checked ? 'block' : 'none';
    if (advanceArea) advanceArea.style.display = checked ? 'none' : 'block';
    
    if (checked) {
        // Digital product - hide variant options
        if (variantToggleArea) variantToggleArea.style.display = 'none';
        if (variantSection) variantSection.style.display = 'none';
        if (productType) productType.value = 'digital';
        // Enable price/stock for digital products
        if (newPriceField) {
            newPriceField.closest('.col-md-6').style.opacity = '1';
            newPriceField.readOnly = false;
        }
        if (stockField) {
            stockField.closest('.col-md-6').style.opacity = '1';
            stockField.readOnly = false;
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
    var stockField = document.getElementById('stock');
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
        // Enable price/stock
        if (newPriceField) { newPriceField.closest('.col-md-6').style.opacity = '1'; newPriceField.readOnly = false; }
        if (stockField) { stockField.closest('.col-md-6').style.opacity = '1'; stockField.readOnly = false; }
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
        
        // Toggle price/stock based on variable
        if (newPriceField) {
            newPriceField.closest('.col-md-6').style.opacity = isVariable ? '0.5' : '1';
            newPriceField.readOnly = isVariable;
        }
        if (stockField) {
            stockField.closest('.col-md-6').style.opacity = isVariable ? '0.5' : '1';
            stockField.readOnly = isVariable;
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
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Discount (TK)") }}</label><input type="number" step="0.01" name="wholesale_discount[' + wholesaleIndex + '][wholesale_price]" class="form-control" placeholder="0.00"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Stock Qty") }}</label><input type="number" name="wholesale_discount[' + wholesaleIndex + '][stock]" class="form-control" placeholder="0"></div>' +
                    '<div class="col-md-1 mb-2"><button type="button" class="btn btn-danger btn-remove-wholesale w-100" title="{{ __("Remove Tier") }}"><i class="fa fa-trash"></i></button></div>' +
                    '</div></div>';
            } else {
                var html = '<div class="variant-card"><div class="row align-items-end">' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Min Quantity") }}</label><input type="number" name="wholesale_discount[' + wholesaleIndex + '][min_quantity]" class="form-control" placeholder="e.g. 10"></div>' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Max Quantity") }}</label><input type="number" name="wholesale_discount[' + wholesaleIndex + '][max_quantity]" class="form-control" placeholder="e.g. 50 (optional)"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Discount (TK)") }}</label><input type="number" step="0.01" name="wholesale_discount[' + wholesaleIndex + '][wholesale_price]" class="form-control" placeholder="0.00"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Stock Qty") }}</label><input type="number" step="0.01" name="wholesale_discount[' + wholesaleIndex + '][stock]" class="form-control" placeholder="0"></div>' +
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
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Discount (TK)") }}</label><input type="number" step="0.01" name="wholesale_discount[0][wholesale_price]" class="form-control" placeholder="0.00"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Stock Qty") }}</label><input type="number" name="wholesale_discount[0][stock]" class="form-control" placeholder="0"></div>' +
                    '<div class="col-md-1 mb-2"><button type="button" class="btn btn-success add-wholesale-tier w-100" title="{{ __("Add New Tier") }}"><i class="fa fa-plus"></i></button></div>' +
                    '</div></div>';
            } else {
                var html = '<div class="variant-card"><div class="row align-items-end">' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Min Quantity") }}</label><input type="number" name="wholesale_discount[0][min_quantity]" class="form-control" placeholder="e.g. 10"></div>' +
                    '<div class="col-md-3 mb-2"><label class="form-label">{{ __("Max Quantity") }}</label><input type="number" name="wholesale_discount[0][max_quantity]" class="form-control" placeholder="e.g. 50 (optional)"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Discount (TK)") }}</label><input type="number" step="0.01" name="wholesale_discount[0][wholesale_price]" class="form-control" placeholder="0.00"></div>' +
                    '<div class="col-md-2 mb-2"><label class="form-label">{{ __("Stock Qty") }}</label><input type="number" name="wholesale_discount[0][stock]" class="form-control" placeholder="0"></div>' +
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

    // 🛡️ Warranty Tier Management
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

})();
</script>
@endsection