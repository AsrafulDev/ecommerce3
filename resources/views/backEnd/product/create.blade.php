@extends('backEnd.layouts.master')
@section('title','Create {{ __('New') }} {{ __('Product') }}')

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
    .slider:before { position: absolute; content: ""; height: {{ __('14px') }}; width: {{ __('14px') }}; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #0acf97; }
    input:checked + .slider:before { transform: translateX(20px); }

    .btn-remove-row { margin-top: 28px; }
</style>
@endsection 

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between py-3">
                <h4 class="page-title mb-0">{{ __('Add {{ __('New') }} {{ __('Product') }}') }}</h4>
                <div class="page-title-right">
                    <a href="{{route('inhouse.products.index')}}" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fe-list me-1"></i>{{ __('{{ __('{{ __('Manage') }} {{ __('Product') }}') }}s') }}</a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{route('products.store')}}" method={{ __('"{{ __('POST') }}"') }} data-parsley-validate="" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-info me-1"></i> {{ __('{{ __('Basic') }} Information') }}</div>
                        
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">{{ __('{{ __('{{ __('Product') }} {{ __('Name') }}') }} *') }}</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="{{ __('Enter product name') }}" required />
                            @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Main {{ __('Category') }} *') }}</label>
                                <select class="form-control select2" name="category_id" id="category_id" required>
                                    <option value="">{{ __('Select {{ __('Category') }}') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Sub {{ __('Category') }}') }}</label>
                                <select class="form-control select2" name="subcategory_id" id="subcategory_id">
                                    <option value="">{{ __('Choose {{ __('Sub {{ __('Category') }}') }}') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Child {{ __('Category') }}') }}</label>
                                <select class="form-control select2" name="childcategory_id" id="childcategory_id">
                                    <option value="">{{ __('Choose {{ __('Child {{ __('Category') }}') }}') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Full {{ __('Description *') }}') }}</label>
                            <textarea name="description" class="summernote" required>{{ old('description') }}</textarea>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label">{{ __('Short {{ __('Note') }}') }}</label>
                            <textarea name="note" rows="2" class="form-control" placeholder="{{ __('Small note for internal use...') }}"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label class="d-block form-label">{{ __('Wholesale {{ __('Product') }}') }}</label>
                            <label class="switch"><input type="checkbox" value="1" name="is_wholesale" id="is_wholesale"><span class="slider round"></span></label>
                        </div>
                    </div>
                </div>

                <div id="wholesale_area" style="display:none;" class="card mb-4">
                    <div class="card-body">
                        <div class="section-title d-flex justify-content-between align-items-center">
                            <span><i class="fe-dollar-sign me-1"></i> {{ __('Wholesale Pricing Tiers') }}</span>
                            <button type="button" class="btn btn-sm btn-success add-wholesale-tier rounded-pill px-3"><i class="fa fa-plus me-1"></i> {{ __('Add {{ __('New') }} Tier') }}</button>
                        </div>
                        
                        <div id="wholesale-wrapper">
                            <div class="variant-card">
                                <div class="row align-items-end">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">{{ __('Min Quantity') }}</label>
                                        <input type="{{ __('number') }}" name="wholesale_price[0][min_quantity]" class="form-control" placeholder="{{ __('e.g. 10') }}">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">{{ __('Max Quantity') }}</label>
                                        <input type="{{ __('number') }}" name="wholesale_price[0][max_quantity]" class="form-control" placeholder="{{ __('e.g. 50 (optional)') }}">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label">{{ __('Wholesale Price') }}</label>
                                        <input type="{{ __('number') }}" step="0.01" name="wholesale_price[0][wholesale_price]" class="form-control" placeholder="{{ __('0.00') }}">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label">{{ __('{{ __('Stock') }} Qty') }}</label>
                                        <input type="{{ __('number') }}" name="wholesale_price[0][{{ __('stock') }}]" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <button type="button" class="btn btn-success add-wholesale-tier w-100"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4" id="variant_section">
                    <div class="card-body">
                        <div class="section-title d-flex justify-content-between align-items-center">
                            <span><i class="fe-layers me-1"></i> {{ __('{{ __('{{ __('Product') }} Variants') }} (Size & {{ __('Color') }})') }}</span>
                            <button type="button" class="btn btn-sm btn-success add-variant rounded-pill px-3"><i class="fa fa-plus me-1"></i> {{ __('Add {{ __('New') }} Variant') }}</button>
                        </div>
                        
                        <div id="variant-wrapper">
                            <div class="variant-card variant-item">
                                <div class="row align-items-end">
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label">{{ __('{{ __('Color') }}') }}<small class="text-muted">{{ __('({{ __('Optional') }})') }}</small></label>
                                        <select name="variant_price[0][color_id]" class="form-control select2 variant-color-select">
                                            <option value="">{{ __('Select {{ __('Color') }}') }}</option>
                                            @foreach($colors as $color)
                                                <option value="{{ $color->id }}">{{ $color->color{{ __('Name') }} ?? $color->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label">{{ __('Size') }}<small class="text-muted">{{ __('({{ __('Optional') }})') }}</small></label>
                                        <select name="variant_price[0][size_id]" class="form-control select2 variant-size-select">
                                            <option value="">{{ __('Select Size') }}</option>
                                            @foreach($sizes as $size)
                                                <option value="{{ $size->id }}">{{ $size->size{{ __('Name') }} ?? $size->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label">{{ __('Price') }}</label>
                                        <input type="{{ __('number') }}" step="0.01" name="variant_price[0][price]" class="form-control" placeholder="{{ __('0.00') }}">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label">{{ __('{{ __('Stock') }}') }}</label>
                                        <input type="{{ __('number') }}" name="variant_price[0][{{ __('stock') }}]" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">{{ __('Variant Image') }}</label>
                                        <div class="variant-img-upload position-relative">
                                            <input type="file" name="variant_image[0][image]" class="form-control form-control-sm variant-img-input" accept="image/*">
                                            <div class="variant-img-preview mt-1" style="display:none;">
                                                <img src="" alt="{{ __('Prev') }}iew" class="rounded border" style="max-width:60px;max-height:60px;object-fit:cover;">
                                                <button type="button" class="btn btn-sm btn-danger variant-img-clear ms-1" title="{{ __('Remove') }}"><i class="fe-x"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <button type="button" class="btn btn-danger btn-remove-row d-none w-100"><i class="fe-trash-2"></i></button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <small class="text-muted">
                                            <i class="fa fa-info-circle"></i> 
                                            {{ __('Color') }} ও Size অনুযায়ী ইমেজ এড করুন। {{ __('Product') }} details পেজে সিলেক্ট করলে সেই ইমেজ দেখাবে।
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-search me-1"></i> {{ __('SEO {{ __('Configuration') }}') }}</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Meta {{ __('Title') }}') }}</label>
                                <input type="text" name="meta_title" class="form-control" placeholder="{{ __('SEO optimized title') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Meta Keywords') }}</label>
                                <input type="text" name="meta_keywords" class="form-control" placeholder="{{ __('keyword1, keyword2') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Meta Description') }}</label>
                                <textarea name="meta_description" class="form-control" rows="2" placeholder="{{ __('Brief description for search engines') }}"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('Meta Image') }}</label>
                                <input type="file" name="meta_image" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-dollar-sign me-1"></i> {{ __('Pricing & {{ __('Inv') }}entory') }}</div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Purchase Price') }} <small class="text-muted">{{ __('({{ __('Optional') }})') }}</small></label>
                            <input type="{{ __('number') }}" name="purchase_price" class="form-control border-primary" placeholder="0">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Old Price') }}</label>
                                <input type="{{ __('number') }}" name="old_price" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('{{ __('New') }} Price') }} <small class="text-muted">{{ __('({{ __('Optional') }})') }}</small></label>
                                <input type="{{ __('number') }}" name="new_price" class="form-control font-weight-bold" placeholder="0">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Reseller Price') }}</label>
                            <input type="{{ __('number') }}" step="0.01" name="reseller_price" class="form-control" placeholder="{{ __('Reseller price (optional)') }}">
                            <small class="text-muted">{{ __('Special price for resellers. Leave empty if not applicable.') }}</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('{{ __('Total') }} {{ __('Stock') }}') }} <small class="text-muted">{{ __('({{ __('Optional') }})') }}</small></label>
                                <input type="{{ __('number') }}" name="{{ __('stock') }}" class="form-control" placeholder="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Unit (kg/pc)') }}</label>
                                <input type="text" name="pro_unit" class="form-control" placeholder="{{ __('e.g. pcs') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-image me-1"></i> {{ __('{{ __('Media') }} & Video') }}</div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('{{ __('{{ __('Product') }} Gallery Images') }} *') }}</label>
                            <div class="increment-wrapper">
                                <div class="control-group increment mb-2 image-row">
                                    <div class="row align-items-end g-2">
                                        <div class="col-md-10">
                                            <label class="form-label small">{{ __('Image') }}</label>
                                            <input type="file" name="image[]" class="form-control form-control-sm" required accept="image/*">
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-success btn-increment btn-sm w-100" type="button"><i class="fa fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="clone d-none">
                                <div class="control-group mt-2 image-row">
                                    <div class="row align-items-end g-2">
                                        <div class="col-md-10">
                                            <label class="form-label small">{{ __('Image') }}</label>
                                            <input type="file" name="image[]" class="form-control form-control-sm" accept="image/*">
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-danger btn-remove-image btn-sm w-100" type="button"><i class="fa fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ===== VIDEO SECTION ===== --}}
                        <div class="form-group mb-0">
                            <label class="form-label fw-semibold">{{ __('{{ __('Product') }}s') }}</label>
                            {{-- {{ __('Source') }} selector --}}
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
                                        <i class="fa fa-upload text-primary me-1"></i> {{ __('bn_9607a518') }} আপলোড
                                    </label>
                                </div>
                            </div>

                            {{-- YouTube input --}}
                            <div id="yt_section_c">
                                <input type="text" name="pro_video" id="pro_video_c" class="form-control"
                                       placeholder="YouTube URL বা Video ID দিন (যেমন: {{ __('https://') }}youtu.be/xxxxx)">
                                <div id="yt_preview_c" class="mt-2" style="display:none;">
                                    <iframe id="yt_iframe_c" width="100%" height="200"
                                            src="" frameborder="0" allowfullscreen
                                            style="border-radius:8px;"></iframe>
                                </div>
                                <small class="text-muted">{{ __('bn_b7bd583c') }}</small>
                            </div>

                            {{-- Upload input --}}
                            <div id="up_section_c" style="display:none;">
                                <input type="file" name="pro_video_file" id="pro_video_file_c"
                                       class="form-control" accept="video/mp4,video/webm,video/ogg">
                                <div id="up_preview_c" class="mt-2" style="display:none;">
                                    <video id="up_video_c" width="100%" height="220" controls
                                           style="border-radius:8px;background:#000;"></video>
                                </div>
                                <small class="text-muted">{{ __('bn_2d369668') }}</small>
                            </div>
                        </div>
                        {{-- ===== /VIDEO SECTION ===== --}}
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-title"><i class="fe-settings me-1"></i> {{ __('{{ __('Product') }} Settings') }}</div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('{{ __('Product') }} Type') }}</label>
                            <select class="form-control bg-light" id="product_type" name="product_type">
                                <option value="simple" selected>📦 {{ __('Simple') }} {{ __('Product') }}</option>
                                <option value="digital">💾 {{ __('Digital {{ __('Product') }}') }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-3" id="advance_area">
                            <label class="form-label">{{ __('{{ __('Advance Payment') }} {{ __('Amount') }}') }}</label>
                            <input type="{{ __('number') }}" name="advance_amount" class="form-control" placeholder="{{ __('0.00') }}">
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Free Delivery') }}</label>
                            <div class="d-flex align-items-center">
                                <label class="switch me-3">
                                    <input type="checkbox" value="1" name="free_delivery">
                                    <span class="slider round"></span>
                                </label>
                                <small class="text-muted">{{ __('{{ __('Enable') }} free delivery for this product (No shipping charge will be applied)') }}</small>
                            </div>
                        </div>

                        <div id="digital_area" style="display:none;" class="p-2 border rounded mb-3 bg-light">
                            <label class="form-label">{{ __('Digital File') }}</label>
                            <input type="file" class="form-control mb-2" name="digital_file">
                            <div class="row">
                                <div class="col-6">
                                    <small>{{ __('Limit') }}</small>
                                    <input type="{{ __('number') }}" class="form-control form-control-sm" name="download_limit" value="5">
                                </div>
                                <div class="col-6">
                                    <small>{{ __('{{ __('Days') }} Exp.') }}</small>
                                    <input type="{{ __('number') }}" class="form-control form-control-sm" name="download_expire_days" value="7">
                                </div>
                            </div>
                        </div>

                        <div class="row text-center mb-3">
                            <div class="col-4 mb-2">
                                <label class="d-block form-label">{{ __('Status') }}</label>
                                <label class="switch"><input type="checkbox" value="1" name="status" checked><span class="slider round"></span></label>
                            </div>
                            <div class="col-4 mb-2">
                                <label class="d-block form-label">{{ __('Hot Deal') }}</label>
                                <label class="switch"><input type="checkbox" value="1" name="topsale"><span class="slider round"></span></label>
                            </div>
                           
                            <div class="col-6">
                                <label class="d-block form-label">{{ __('Flash Sale') }}</label>
                                <label class="switch"><input type="checkbox" value="1" name="flashsale"><span class="slider round"></span></label>
                            </div>
                             <div class="col-6">
                                <label class="form-label">{{ __('Brand') }}</label>
                                <select class="form-control select2" name="brand_id">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach($brands as $value)
                                        <option value="{{$value->id}}">{{$value->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <button type="{{ __('submit') }}" class="btn btn-success btn-lg w-100 shadow rounded-pill"><i class="fe-check-circle me-1"></i> {{ __('{{ __('Publish') }} {{ __('Product') }}') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection 

@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/summernote/summernote-lite.min.js"></script>

<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });
        $(".summernote").summernote({ height: 200, placeholder: "Describe your product..." });

        // Image Increment
        $(".btn-increment").click(function () {
            var html = $(".clone").html();
            $(".increment-wrapper").append(html);
        });
        $("body").on("click", ".btn-remove-image", function () {
            $(this).parents(".control-group").remove();
        });

        // {{ __('{{ __('Product') }} Type') }} Toggle
        $('#product_type').change(function(){
            let type = $(this).val();
            if(type === 'digital'){
                $('#digital_area').slideDown();
                $('#advance_area').slideUp();
                $('#variant_section').slideUp();
            } else {
                $('#digital_area').slideUp();
                $('#advance_area').slideDown();
                $('#variant_section').slideDown();
            }
        });

        // Initialize Select2 for size (single select)
        $('.variant-size-select').select2({
            width: '100%'
        });
        
        $('.variant-color-select').select2({
            width: '100%'
        });

        // Dynamic Variant Add/Remove
        let variantIndex = 1;
        $(".add-variant").click(function () {
            let wrapper = $("#variant-wrapper");
            let firstRow = wrapper.find('.variant-item').first().clone();
            
            // Clear inputs and fix select2
            firstRow.find('.select2-container').remove();
            firstRow.find('input').val('');
            firstRow.find('select').each(function(){
                let old{{ __('Name') }} = $(this).attr('name');
                if (old{{ __('Name') }}) {
                    if (old{{ __('Name') }}.includes('variant_image')) {
                        $(this).attr('name', 'variant_image[' + variantIndex + '][image]');
                    } else {
                        $(this).attr('name', old{{ __('Name') }}.replace(/\[\d+\]/, '[' + variantIndex + ']'));
                    }
                }
                if ($(this).attr('type') !== 'file') $(this).val(null).trigger('change');
                else {
                    $(this).val('');
                    $(this).siblings(".variant-img-preview").hide().find("img").attr("src", "");
                }
            });

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

        // {{ __('Variant Image') }} {{ __('Prev') }}iew & Clear
        $("body").on("change", ".variant-img-input", function() {
            var $input = $(this);
            var $preview = $input.siblings(".variant-img-preview");
            var $img = $preview.find("img");
            var file = this.files[0];
            if (file && file.type.startsWith("image/")) {
                var reader = new FileReader();
                reader.onload = function(e) { $img.attr("src", e.target.result); $preview.show(); };
                reader.readAsDataURL(file);
            } else { $preview.hide(); $img.attr("src", ""); }
        });
        $("body").on("click", ".variant-img-clear", function() {
            var $preview = $(this).closest(".variant-img-preview");
            $preview.siblings(".variant-img-input").val("");
            $preview.find("img").attr("src", "");
            $preview.hide();
        });

        // Handle form submission - collect variant data (single size per variant)
        $('form[data-parsley-validate]').on('{{ __('submit') }}', function(e) {
            let variantData = [];
            let variantIndex = 0;
            let rowIndex = 0;
            
            $('#variant-wrapper .variant-item').each(function() {
                let $row = $(this);
                let colorId = $row.find('.variant-color-select').val() || null;
                let sizeId = $row.find('.variant-size-select').val() || null;
                let price = $row.find('input[name*="[price]"]').val() || 0;
                let {{ __('stock') }} = $row.find('input[name*="[{{ __('stock') }}]"]').val() || 0;
                
                if (!colorId && !sizeId) return;
                
                variantData.push({ index: variantIndex++, color_id: colorId, size_id: sizeId, price: price, {{ __('stock') }}: {{ __('stock') }}, image_row: rowIndex });
                rowIndex++;
            });
            
            // Remove only non-file variant_price inputs (keep file inputs for images)
            $(this).find('input[name*="variant_price"]:not([type="file"]), select[name*="variant_price"]').remove();
            
            variantData.forEach(function(v) {
                $('<input>').attr({ type: 'hidden', name: 'variant_price[' + v.index + '][color_id]', value: v.color_id }).appendTo($('form[data-parsley-validate]'));
                $('<input>').attr({ type: 'hidden', name: 'variant_price[' + v.index + '][size_id]', value: v.size_id || '' }).appendTo($('form[data-parsley-validate]'));
                $('<input>').attr({ type: 'hidden', name: 'variant_price[' + v.index + '][price]', value: v.price }).appendTo($('form[data-parsley-validate]'));
                $('<input>').attr({ type: 'hidden', name: 'variant_price[' + v.index + '][{{ __('stock') }}]', value: v.{{ __('stock') }} }).appendTo($('form[data-parsley-validate]'));
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
                let old{{ __('Name') }} = $(this).attr('name');
                $(this).attr('name', old{{ __('Name') }}.replace(/\[\d+\]/, '[' + wholesaleIndex + ']'));
                $(this).val('');
            });

            // {{ __('Change') }} add button to remove button
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
            if ({{ __('id)') }} {
                $.get("{{url('ajax-product-subcategory')}}?category_id=" + id, function(res){
                    $("#subcategory_id").empty().append('<option value="">{{ __('Choose {{ __('Sub {{ __('Category') }}') }}') }}</option>');
                    $.each(res, function(key, value){
                        $("#subcategory_id").append('<option value="'+key+'">{{ __("'+value+'") }}</option>');
                    });
                });
            }
        });

        $("#subcategory_id").on("change", function () {
            var id = $(this).val();
            if ({{ __('id)') }} {
                $.get("{{url('ajax-product-childcategory')}}?subcategory_id=" + id, function(res){
                    $("#childcategory_id").empty().append('<option value="">{{ __('Choose {{ __('Child {{ __('Category') }}') }}') }}</option>');
                    $.each(res, function(key, value){
                        $("#childcategory_id").append('<option value="'+key+'">{{ __("'+value+'") }}</option>');
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
                if ({{ __('id)') }} {
                    fr.src = '{{ __('https://') }}www.youtube.com/embed/' + id;
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