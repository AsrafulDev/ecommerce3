@extends('backEnd.layouts.master')
@section('title','Create New Product')

@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
<style>
    /* কাস্টম ডিজাইন */
    .section-title { background: #f1f3f7; padding: 10px 15px; border-radius: 6px; font-weight: 700; color: #343a40; border-left: 4px solid #727cf5; margin-bottom: 20px; font-size: 15px; }
    .form-label { font-weight: 600; font-size: 13px; color: #555; }
    .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border-radius: 0.75rem; }
    
    /* ভ্যারিয়েন্ট আইটেম ডিজাইন */
    .variant-card { background: #fafbfd; border: 1px solid #e2e7f1; padding: 15px; border-radius: 10px; margin-bottom: 12px; position: relative; }
    
    /* টগল সুইচ */
    .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 20px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #0acf97; }
    input:checked + .slider:before { transform: translateX(20px); }

    .btn-remove-row { margin-top: 28px; }
</style>
@endsection 

@section('content')
@php
    // ⭐ Batch-wise pricing engine — when ON, sell prices are set after the first
    //    purchase on /admin/purchases/manage (this page keeps catalog + variant identity).
    $batchWise = (bool) config('pricing.batch_wise', false);
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between py-3">
                <h4 class="page-title mb-0"> {{ __('Add New Product') }} </h4>
                <div class="page-title-right">
                    <a href="{{route('inhouse.products.index')}}" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fe-list me-1"></i>{{ __('Manage Products') }}</a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{route('products.store')}}" method="POST" data-parsley-validate="" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-info me-1"></i> {{ __('Basic Information') }} </div>
                        
                        <div class="form-group mb-3">
                            <label for="name" class="form-label"> {{ __('Product Name *') }} </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="Enter product name" required />
                            @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"> {{ __('Main Category *') }} </label>
                                <select class="form-control select2" name="category_id" id="category_id" required>
                                    <option value="">{{ __('Select Category') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"> {{ __('Sub Category') }} </label>
                                <select class="form-control select2" name="subcategory_id" id="subcategory_id">
                                    <option value=""> {{ __('Choose Sub Category') }} </option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"> {{ __('Child Category') }} </label>
                                <select class="form-control select2" name="childcategory_id" id="childcategory_id">
                                    <option value=""> {{ __('Choose Child Category') }} </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label"> {{ __('Full Description *') }} </label>
                            <textarea name="description" class="summernote" required>{{ old('description') }}</textarea>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label"> {{ __('Short Note') }} </label>
                            <textarea name="note" rows="2" class="form-control" placeholder="Small note for internal use..."></textarea>
                        </div>
                    </div>
                </div>

                @if(!$batchWise)
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label class="d-block form-label"> {{ __('Wholesale Product') }} </label>
                            <label class="switch"><input type="checkbox" value="1" name="is_wholesale" id="is_wholesale"><span class="slider round"></span></label>
                        </div>
                    </div>
                </div>
                @endif

                @if(!$batchWise)
                <div id="wholesale_area" style="display:none;" class="card mb-4">
                    <div class="card-body">
                        <div class="section-title d-flex justify-content-between align-items-center">
                            <span><i class="fe-dollar-sign me-1"></i> {{ __('Wholesale Pricing Tiers') }} </span>
                            <button type="button" class="btn btn-sm btn-success add-wholesale-tier rounded-pill px-3"><i class="fa fa-plus me-1"></i> {{ __('Add New Tier') }} </button>
                        </div>
                        
                        <div id="wholesale-wrapper">
                            <div class="variant-card">
                                <div class="row align-items-end">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label"> {{ __('Min Quantity') }} </label>
                                        <input type="number" name="wholesale_price[0][min_quantity]" class="form-control" placeholder="e.g. 10">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label"> {{ __('Max Quantity') }} </label>
                                        <input type="number" name="wholesale_price[0][max_quantity]" class="form-control" placeholder="e.g. 50 (optional)">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label"> {{ __('Wholesale Price') }} </label>
                                        <input type="number" step="0.01" name="wholesale_price[0][wholesale_price]" class="form-control" placeholder="0.00">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <button type="button" class="btn btn-success add-wholesale-tier w-100"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="card mb-4" id="variant_section" style="display:none;">
                    <div class="card-body">
                        <div class="section-title d-flex justify-content-between align-items-center">
                            <span><i class="fe-layers me-1"></i> {{ __('Product Variants (Size & Color)') }} </span>
                            <button type="button" class="btn btn-sm btn-success add-variant rounded-pill px-3"><i class="fa fa-plus me-1"></i> {{ __('Add New Variant') }} </button>
                        </div>
                        
                        <div id="variant-wrapper">
                            <div class="variant-card variant-item">
                                <div class="row align-items-end">
                                    <div class="col-md-{{ $batchWise ? 3 : 2 }} mb-2">
                                        <label class="form-label">{{ __('Color') }}<small class="text-muted">(Optional)</small></label>
                                        <select name="variant_price[0][color_id]" class="form-control select2 variant-color-select">
                                            <option value="">{{ __('Select Color') }}</option>
                                            @foreach($colors as $color)
                                                <option value="{{ $color->id }}">{{ $color->colorName ?? $color->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-{{ $batchWise ? 3 : 2 }} mb-2">
                                        <label class="form-label">{{ __('Size') }}<small class="text-muted">(Optional)</small></label>
                                        <select name="variant_price[0][size_id]" class="form-control select2 variant-size-select">
                                            <option value="">{{ __('Select Size') }}</option>
                                            @foreach($sizes as $size)
                                                <option value="{{ $size->id }}">{{ $size->sizeName ?? $size->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if(!$batchWise)
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label">{{ __('Price') }}</label>
                                        <input type="number" step="0.01" name="variant_price[0][price]" class="form-control" placeholder="0.00">
                                    </div>
                                    @endif
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label"> {{ __('Variant Image') }} </label>
                                        <div class="variant-img-upload position-relative">
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
                                    <div class="col-md-1 mb-2">
                                        <button type="button" class="btn btn-danger btn-remove-row w-100" title="{{ __('Remove Variant') }}"><i class="fe-trash-2"></i></button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <small class="text-muted">
                                            <i class="fa fa-info-circle"></i> 
                                            Color ও Size অনুযায়ী ইমেজ এড করুন। Product details পেজে সিলেক্ট করলে সেই ইমেজ দেখাবে।
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-search me-1"></i> {{ __('SEO Configuration') }} </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Meta Title') }}</label>
                                <input type="text" name="meta_title" class="form-control" placeholder="SEO optimized title">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Meta Keywords') }}</label>
                                <input type="text" name="meta_keywords" class="form-control" placeholder="keyword1, keyword2">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Meta Description') }}</label>
                                <textarea name="meta_description" class="form-control" rows="2" placeholder="Brief description for search engines"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"> {{ __('Meta Image') }} </label>

                                {{-- 🎨 Media Library — primary option (single image) --}}
                                <div class="border rounded p-2 mb-2" style="background:#f4f7ff;border-color:#d3e0ff!important;">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <button type="button" class="btn btn-primary btn-sm"
                                                onclick="openMediaPicker('#meta_image_url', '#metaImagePreview', 'path')">
                                            <i class="fe-image me-1"></i> {{ __('Choose Meta Image from Media Library') }}
                                        </button>
                                        <small class="text-muted">Pick one image, then press “Insert”.</small>
                                    </div>
                                    <input type="hidden" name="meta_image_url" id="meta_image_url" value="{{ old('meta_image_url') ?? '' }}">
                                    <img id="metaImagePreview" src="" alt="Meta Image"
                                         class="border rounded mt-2" width="120" style="display:none;">
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-dollar-sign me-1"></i> {{ $batchWise ? __('Inventory') : __('Pricing & Inventory') }} </div>
                        
                        @if(!$batchWise)
                        <div class="form-group mb-3">
                            <label class="form-label"> {{ __('Purchase Price') }} <small class="text-muted">(Optional)</small></label>
                            <input type="number" name="purchase_price" class="form-control border-primary" placeholder="0">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"> {{ __('Old Price') }} </label>
                                <input type="number" name="old_price" class="form-control" placeholder="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"> {{ __('New Price') }} <small class="text-muted">(Optional)</small></label>
                                <input type="number" name="new_price" class="form-control font-weight-bold" placeholder="0">
                            </div>
                        </div>
                        @endif

                        <div class="form-group mb-3">
                            <label class="form-label"> {{ __('Unit (kg/pc)') }} </label>
                            <input type="text" name="pro_unit" class="form-control" placeholder="e.g. pcs">
                        </div>

                        {{-- Stock is managed via purchase batches — no manual total stock input --}}
                        <div class="alert alert-info py-2 px-3 mb-0" role="alert">
                            <i class="fe-info me-1"></i>
                            <strong>{{ __('Total Quantity') }}</strong> — {{ __('Stock is managed through purchase batches (batch-wise quantity & purchase price). You can add stock after creating the product.') }}
                        </div>

                        {{-- 🆕 Barcode & Stock Management Fields --}}
                        <div class="section-title mt-4"><i class="fe-tag me-1"></i> {{ __('Barcode & Stock Settings') }} </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"> {{ __('Barcode') }} </label>
                                <input type="text" name="barcode" class="form-control" value="{{ old('barcode') }}" placeholder="Scan or enter barcode">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"> {{ __('Barcode Type') }} </label>
                                <select name="barcode_type" class="form-control form-select">
                                    <option value="C128" {{ old('barcode_type') === 'C128' ? 'selected' : '' }}>Code 128</option>
                                    <option value="C39" {{ old('barcode_type') === 'C39' ? 'selected' : '' }}>Code 39</option>
                                    <option value="EAN13" {{ old('barcode_type') === 'EAN13' ? 'selected' : '' }}>EAN-13</option>
                                    <option value="UPCA" {{ old('barcode_type') === 'UPCA' ? 'selected' : '' }}>UPC-A</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"> {{ __('Costing Method') }} </label>
                                <select name="costing_method" class="form-control form-select">
                                    <option value="fifo" {{ (old('costing_method') ?: 'fifo') === 'fifo' ? 'selected' : '' }}>FIFO (First In, First Out)</option>
                                    <option value="lifo" {{ (old('costing_method') ?: 'fifo') === 'lifo' ? 'selected' : '' }}>LIFO (Last In, First Out)</option>
                                    <option value="average" {{ (old('costing_method') ?: 'fifo') === 'average' ? 'selected' : '' }}>Weighted Average</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"> {{ __('Low Stock Threshold') }} </label>
                                <input type="number" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', 0) }}" placeholder="0 = disabled" min="0">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label"> {{ __('Weight') }} </label>
                                <input type="text" name="weight" class="form-control" value="{{ old('weight') }}" placeholder="e.g. 0.5 kg">
                            </div>
                            <div class="col-md-2 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" name="allow_negative_stock" class="form-check-input" value="1" id="allow_negative_stock" {{ old('allow_negative_stock') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_negative_stock">{{ __('Allow Negative Stock') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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

                        </div>

                        {{-- ===== VIDEO SECTION ===== --}}
                        <div class="form-group mb-0">
                            <label class="form-label fw-semibold">প্রোডাক্ট ভিডিও</label>
                            {{-- Source selector --}}
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pro_video_source"
                                           id="vs_yt_c" value="youtube" checked>
                                    <label class="form-check-label" for="vs_yt_c">
                                        <i class="fa fa-youtube-play text-danger me-1"></i> YouTube লিংক
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pro_video_source"
                                           id="vs_up_c" value="upload">
                                    <label class="form-check-label" for="vs_up_c">
                                        <i class="fa fa-upload text-primary me-1"></i> ভিডিও আপলোড
                                    </label>
                                </div>
                            </div>

                            {{-- YouTube input --}}
                            <div id="yt_section_c">
                                <input type="text" name="pro_video" id="pro_video_c" class="form-control"
                                       placeholder="YouTube URL বা Video ID দিন (যেমন: https://youtu.be/xxxxx)">
                                <div id="yt_preview_c" class="mt-2" style="display:none;">
                                    <iframe id="yt_iframe_c" width="100%" height="200"
                                            src="" frameborder="0" allowfullscreen
                                            style="border-radius:8px;"></iframe>
                                </div>
                                <small class="text-muted">YouTube full URL অথবা শুধু Video ID উভয়ই চলবে।</small>
                            </div>

                            {{-- Upload input --}}
                            <div id="up_section_c" style="display:none;">
                                <input type="file" name="pro_video_file" id="pro_video_file_c"
                                       class="form-control" accept="video/mp4,video/webm,video/ogg">
                                <div id="up_preview_c" class="mt-2" style="display:none;">
                                    <video id="up_video_c" width="100%" height="220" controls
                                           style="border-radius:8px;background:#000;"></video>
                                </div>
                                <small class="text-muted">MP4, WebM, OGG সাপোর্টেড। সর্বোচ্চ 40MB (php.ini সেটিং)।</small>
                            </div>
                        </div>
                        {{-- ===== /VIDEO SECTION ===== --}}
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-settings me-1"></i> {{ __('Product Settings') }} </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label"> {{ __('Product Type') }} </label>
                            <select class="form-control bg-light" id="product_type" name="product_type">
                                <option value="simple" selected>📦 Simple Product</option>
                                <option value="variable">🎨 Variable Product</option>
                                <option value="digital">💾 Digital Product</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <strong>Simple:</strong> Single price & stock | <strong>Variable:</strong> Multiple variants with different prices (Color, Size, etc.)
                            </small>
                        </div>

                        <div class="form-group mb-3" id="advance_area">
                            <label class="form-label"> {{ __('Advance Payment Amount') }} </label>
                            <input type="number" name="advance_amount" class="form-control" placeholder="0.00">
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label"> {{ __('Free Delivery') }} </label>
                            <div class="d-flex align-items-center">
                                <label class="switch me-3">
                                    <input type="checkbox" value="1" name="free_delivery">
                                    <span class="slider round"></span>
                                </label>
                                <small class="text-muted"> {{ __('Enable free delivery for this product (No shipping charge will be applied)') }} </small>
                            </div>
                        </div>

                        <div id="digital_area" style="display:none;" class="p-2 border rounded mb-3 bg-light">
                            <label class="form-label"> {{ __('Digital File') }} </label>
                            <input type="file" class="form-control mb-2" name="digital_file">
                            <div class="row">
                                <div class="col-6">
                                    <small> {{ __('Limit') }} </small>
                                    <input type="number" class="form-control form-control-sm" name="download_limit" value="5">
                                </div>
                                <div class="col-6">
                                    <small> {{ __('Days Exp.') }} </small>
                                    <input type="number" class="form-control form-control-sm" name="download_expire_days" value="7">
                                </div>
                            </div>
                        </div>

                        {{-- WARRANTY METHOD --}}
                        <div class="form-group mb-3">
                            <label for="warranty_method" class="form-label">🛡️ {{ __('Warranty Method') }} </label>
                            <select class="form-control" id="warranty_method" name="warranty_method">
                                <option value="active" {{ old('warranty_method') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ old('warranty_method') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                <option value="hidden" {{ old('warranty_method') === 'hidden' ? 'selected' : '' }}>{{ __('Hidden') }}</option>
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
                                <option value="active" {{ old('publish_status', 'active') === 'active' ? 'selected' : '' }}>🟢 {{ __('Active (Present)') }}</option>
                                <option value="draft" {{ old('publish_status', 'active') === 'draft' ? 'selected' : '' }}>📝 {{ __('Draft') }}</option>
                                <option value="private" {{ old('publish_status', 'active') === 'private' ? 'selected' : '' }}>🔒 {{ __('Private') }}</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <strong>Active:</strong> {{ __('Visible in storefront') }} |
                                <strong>Draft:</strong> {{ __('Hidden (work in progress)') }} |
                                <strong>Private:</strong> {{ __('Hidden from customers') }}
                            </small>
                        </div>

                        <div class="row text-center mb-3">
                            <div class="col-4 mb-2">
                                <label class="d-block form-label"> {{ __('Hot Deals') }} </label>
                                <label class="switch"><input type="checkbox" value="1" name="topsale"><span class="slider round"></span></label>
                            </div>
                            <div class="col-4 mb-2">
                                <label class="d-block form-label">{{ __('Flash Sale') }}</label>
                                <label class="switch"><input type="checkbox" value="1" name="flashsale"><span class="slider round"></span></label>
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label">{{ __('Brand') }}</label>
                                <select class="form-control select2" name="brand_id">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach($brands as $value)
                                        <option value="{{$value->id}}">{{$value->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 shadow rounded-pill"><i class="fe-check-circle me-1"></i> {{ __('Publish Product') }} </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Reusable Media Gallery picker — "choose image from media library" --}}
@include('backEnd.media._picker')
@endsection 

@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/summernote/summernote-lite.min.js"></script>

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
        $('.select2').select2({ width: '100%' });
        $(".summernote").summernote({
            height: 200,
            placeholder: "Describe your product...",
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

        // Product Type Toggle — variant section shows ONLY for "variable"
        $('#product_type').change(function(){
            let type = $(this).val();
            if(type === 'digital'){
                $('#digital_area').slideDown();
                $('#advance_area').slideUp();
                $('#variant_section').slideUp();
            } else if(type === 'variable'){
                $('#digital_area').slideUp();
                $('#advance_area').slideDown();
                $('#variant_section').slideDown();
            } else { // simple
                $('#digital_area').slideUp();
                $('#advance_area').slideDown();
                $('#variant_section').slideUp();
            }
        });
        // Apply the initial state (default = simple → variants hidden)
        $('#product_type').trigger('change');

        // Initialize Select2 for size (single select)
        $('.variant-size-select').select2({
            width: '100%'
        });
        
        $('.variant-color-select').select2({
            width: '100%'
        });

        // Dynamic Variant Add/Remove
        let variantIndex = 1;
        // Keep a template of the first row so we can always add a new variant,
        // even after every row (including the first) has been removed.
        const variantTemplate = $("#variant-wrapper .variant-item").first().prop('outerHTML');

        $(".add-variant").click(function () {
            let wrapper = $("#variant-wrapper");
            let firstRow = wrapper.find('.variant-item').first();
            if (!firstRow.length && variantTemplate) {
                firstRow = $(variantTemplate); // rebuild a fresh row from the template (not yet in DOM)
            }
            if (!firstRow.length) return;
            firstRow = firstRow.clone();
            
            // Clear inputs and fix select2
            firstRow.find('.select2-container').remove();
            firstRow.find('input').val('');
            firstRow.find('select').each(function(){
                let oldName = $(this).attr('name');
                if (oldName) {
                    if (oldName.includes('variant_image')) {
                        $(this).attr('name', oldName.replace(/\[(\d+)\]/, '[' + variantIndex + ']'));
                    } else {
                        $(this).attr('name', oldName.replace(/\[\d+\]/, '[' + variantIndex + ']'));
                    }
                }
                $(this).val(null).trigger('change');
            });
            // Rename variant_image inputs (hidden media path + file) to the new row index
            firstRow.find('input[name*="variant_image"]').each(function(){
                let oldName = $(this).attr('name');
                if (oldName) $(this).attr('name', oldName.replace(/\[(\d+)\]/, '[' + variantIndex + ']'));
            });
            // Re-index media picker ids + reset previews for the cloned row
            firstRow.find('.variant-media-path').attr('id', 'variant_image_' + variantIndex + '_image');
            firstRow.find('.variant-media-preview').attr('id', 'variant_image_' + variantIndex + '_preview').attr('src', '').hide();
            firstRow.find('.variant-img-preview').hide().find('img').attr('src', '');

            firstRow.find('.btn-remove-row').removeClass('d-none');
            wrapper.append(firstRow);
            
            // Reinitialize Select2 for new row
            setTimeout(() => {
                firstRow.find('.variant-size-select').select2({
                    width: '100%',
                    dropdownParent: $('#variant-wrapper')
                });
                firstRow.find('.variant-color-select').select2({
                    width: '100%',
                    dropdownParent: $('#variant-wrapper')
                });
            }, 100);
            
            variantIndex++;
        });

        $("body").on("click", ".btn-remove-row", function () {
            $(this).parents(".variant-item").remove();
        });

        // Open the Media Library picker for a variant image row
        $("body").on("click", ".variant-media-pick", function () {
            var $cell = $(this).closest(".variant-img-upload");
            var $path = $cell.find(".variant-media-path");
            var $preview = $cell.find(".variant-media-preview");
            if (window.openMediaPicker && $path.length) {
                openMediaPicker("#" + $path.attr("id"), $preview.length ? "#" + $preview.attr("id") : null, "path");
            }
        });


        // Handle form submission - collect variant data (single size per variant)
        $('form[data-parsley-validate]').on('submit', function(e) {
            let variantData = [];
            let variantIndex = 0;
            let rowIndex = 0;
            
            $('#variant-wrapper .variant-item').each(function() {
                let $row = $(this);
                let colorId = $row.find('.variant-color-select').val() || null;
                let sizeId = $row.find('.variant-size-select').val() || null;
                let price = $row.find('input[name*="[price]"]').val() || 0;
                
                if (!colorId && !sizeId) return;
                
                variantData.push({ index: variantIndex++, color_id: colorId, size_id: sizeId, price: price, image_row: rowIndex });
                rowIndex++;
            });
            
            // Remove only non-file variant_price inputs (keep file inputs for images)
            $(this).find('input[name*="variant_price"]:not([type="file"]), select[name*="variant_price"]').remove();
            
            variantData.forEach(function(v) {
                $('<input>').attr({ type: 'hidden', name: 'variant_price[' + v.index + '][color_id]', value: v.color_id }).appendTo($('form[data-parsley-validate]'));
                $('<input>').attr({ type: 'hidden', name: 'variant_price[' + v.index + '][size_id]', value: v.size_id || '' }).appendTo($('form[data-parsley-validate]'));
                $('<input>').attr({ type: 'hidden', name: 'variant_price[' + v.index + '][price]', value: v.price }).appendTo($('form[data-parsley-validate]'));
                $('<input>').attr({ type: 'hidden', name: 'variant_price[' + v.index + '][image_row]', value: v.image_row }).appendTo($('form[data-parsley-validate]'));
            });
        });

        // Wholesale toggle
        $("#is_wholesale").on("change", function () {
            if ($(this).is(':checked')) {
                $("#wholesale_area").slideDown();
                $("#wholesale_area input").prop('required', true);
            } else {
                $("#wholesale_area").slideUp();
                $("#wholesale_area input").prop('required', false);
            }
        });

        // Wholesale pricing tiers
        let wholesaleIndex = 1;
        $("body").on("click", ".add-wholesale-tier", function () {
            let wrapper = $("#wholesale-wrapper");
            let firstRow = wrapper.find(".variant-card").first().clone();
            
            firstRow.find('input').each(function(){
                let oldName = $(this).attr('name');
                $(this).attr('name', oldName.replace(/\[\d+\]/, '[' + wholesaleIndex + ']'));
                $(this).val('');
            });

            // Change add button to remove button
            firstRow.find('.add-wholesale-tier').removeClass('btn-success add-wholesale-tier').addClass('btn-danger btn-remove-wholesale').html('<i class="fa fa-trash"></i>');
            wrapper.append(firstRow);
            wholesaleIndex++;
        });

        $("body").on("click", ".btn-remove-wholesale", function () {
            $(this).parents(".variant-card").remove();
        });

        // AJAX Categories
        $("#category_id").on("change", function () {
            var id = $(this).val();
            if (id) {
                $.get("{{url('ajax-product-subcategory')}}?category_id=" + id, function(res){
                    $("#subcategory_id").empty().append('<option value=""> {{ __('Choose Sub Category') }} </option>');
                    $.each(res, function(key, value){
                        $("#subcategory_id").append('<option value="'+key+'">'+value+'</option>');
                    });
                });
            }
        });

        $("#subcategory_id").on("change", function () {
            var id = $(this).val();
            if (id) {
                $.get("{{url('ajax-product-childcategory')}}?subcategory_id=" + id, function(res){
                    $("#childcategory_id").empty().append('<option value=""> {{ __('Choose Child Category') }} </option>');
                    $.each(res, function(key, value){
                        $("#childcategory_id").append('<option value="'+key+'">'+value+'</option>');
                    });
                });
            }
        });
    });

    // ===== VIDEO SOURCE SWITCHER (Create) =====
    (function () {
        var radios   = document.querySelectorAll('input[name="pro_video_source"]');
        var ytSec    = document.getElementById('yt_section_c');
        var upSec    = document.getElementById('up_section_c');

        function switchVideo(val) {
            if (val === 'upload') {
                ytSec.style.display = 'none';
                upSec.style.display = '';
            } else {
                ytSec.style.display = '';
                upSec.style.display = 'none';
            }
        }

        radios.forEach(function (r) {
            r.addEventListener('change', function () { switchVideo(this.value); });
        });

        // YouTube live preview
        var ytInput = document.getElementById('pro_video_c');
        if (ytInput) {
            ytInput.addEventListener('input', function () {
                var val = this.value.trim();
                var id  = extractYtId(val);
                var box = document.getElementById('yt_preview_c');
                var fr  = document.getElementById('yt_iframe_c');
                if (id) {
                    fr.src = 'https://www.youtube.com/embed/' + id;
                    box.style.display = '';
                } else {
                    fr.src = '';
                    box.style.display = 'none';
                }
            });
        }

        // Upload local preview
        var upInput = document.getElementById('pro_video_file_c');
        if (upInput) {
            upInput.addEventListener('change', function () {
                var file = this.files[0];
                var box  = document.getElementById('up_preview_c');
                var vid  = document.getElementById('up_video_c');
                if (file) {
                    vid.src = URL.createObjectURL(file);
                    box.style.display = '';
                } else {
                    vid.src = '';
                    box.style.display = 'none';
                }
            });
        }

        function extractYtId(input) {
            if (!input) return null;
            if (/^[a-zA-Z0-9_-]{11}$/.test(input)) return input;
            var m = input.match(/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
            return m ? m[1] : null;
        }
    })();
</script>
@endsection