@extends('backEnd.layouts.master')
@section('title','{{ __('General Setting') }}s {{ __('Configuration') }}')

@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
<style>
    /* 1. PROFESSIONAL CARD DESIGN */
    .settings-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 30px;
    }
    
    .section-title-pro {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        padding: 15px 25px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* 2. LOGO PREVIEW STYLING */
    .logo-preview-box {
        background: #f1f5f9;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        border: 1px dashed #cbd5e1;
        margin-top: 10px;
    }
    .edit-image-pro {
        max-height: 60px;
        width: auto;
        border-radius: 6px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* 3. INPUT REFINEMENT */
    .form-label-pro {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
    }
    .custom-input {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 15px;
        transition: all 0.2s;
    }
    .custom-input:focus {
        background: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    /* 4. COLOR PICKER WRAPPER */
    .color-box-pro {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* 5. ACTION BUTTON */
    .btn-save-pro {
        background: #0f172a;
        color: #fff;
        padding: 12px 35px;
        border-radius: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
        transition: 0.3s;
    }
    .btn-save-pro:hover {
        background: #334155;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        color: #fff;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">{{ __('{{ __('General Setting') }}s') }}</h4>
            <span class="text-muted small">{{ __('Update site identity, appearance and business rules') }}</span>
        </div>
    </div>

    <form action="{{route('settings.update')}}" method={{ __('"{{ __('POST') }}"') }} data-parsley-validate="" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{$edit_data->id}}">

        <div class="row">
            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="section-title-pro">
                        <i class="mdi mdi-web text-primary"></i> {{ __('{{ __('Basic') }} Information') }}
                    </div>
                    <div class="p-4 row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-pro">{{ __('Site {{ __('{{ __('Name') }} *') }}') }}</label>
                            <input type="text" name="name" class="form-control custom-input" value="{{ $edit_data->name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-pro">{{ __('FB Page {{ __('{{ __('Use') }}r') }}name') }}</label>
                            <input type="text" name="facebook_page_username" class="form-control custom-input" value="{{ $edit_data->facebook_page_username }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-pro">{{ __('Default Language') }}</label>
                            <select name="default_language" class="form-control custom-input">
                                <option value="en" {{ ($edit_data->default_language ?? 'en') == 'en' ? 'selected' : '' }}>{{ __('English') }}</option>
                                <option value="bn" {{ ($edit_data->default_language ?? 'en') == 'bn' ? 'selected' : '' }}>{{ __('bn_8a0d39fb') }}</option>
                            </select>
                            <small class="text-muted">{{ __('Frontend & customer-facing language') }}</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-pro">{{ __('Admin Language') }}</label>
                            <select name="admin_language" class="form-control custom-input">
                                <option value="en" {{ ($edit_data->admin_language ?? 'en') == 'en' ? 'selected' : '' }}>{{ __('English') }}</option>
                                <option value="bn" {{ ($edit_data->admin_language ?? 'en') == 'bn' ? 'selected' : '' }}>{{ __('bn_8a0d39fb') }}</option>
                            </select>
                            <small class="text-muted">{{ __('Admin panel language') }}</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label-pro">{{ __('Top Headline / Scrolling {{ __('New') }}s') }}</label>
                            <textarea name="top_headline" class="form-control custom-input" rows="2">{{ $edit_data->top_headline }}</textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label-pro">{{ __('Footer About {{ __('Text') }}') }}</label>
                            <textarea name="footer_about_text" class="form-control custom-input" rows="3" placeholder="আপনার ব্যবসার ডিজিটাল পার্টনার। আমরা বিশ্বাস করি গুণগত মান এবং গ্রাহক সন্তুষ্টিতে। প্রযুক্তির সাথে এগিয়ে চলুন আমাদের সাথে।">{{ $edit_data->footer_about_text ?? '' }}</textarea>
                            <small class="text-muted">{{ __('This text appears in the footer about section on the frontend') }}</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-pro">{{ __('Google Play App {{ __('Link') }}') }}</label>
                            <input type="url" name="google_play_link" class="form-control custom-input" value="{{ $edit_data->google_play_link ?? '' }}" placeholder="{{ __('{{ __('https://') }}play.google.com/store/apps/...') }}">
                            <small class="text-muted">{{ __('Footer - Google Play download button') }}</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-pro">{{ __('App {{ __('Store') }} {{ __('Link') }}') }}</label>
                            <input type="url" name="app_store_link" class="form-control custom-input" value="{{ $edit_data->app_store_link ?? '' }}" placeholder="{{ __('{{ __('https://') }}apps.apple.com/...') }}">
                            <small class="text-muted">{{ __('Footer - App {{ __('Store') }} download button') }}</small>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="section-title-pro">
                        <i class="mdi mdi-palette text-success"></i> Theme Appearance
                    </div>
                    <div class="p-4">
                        {{-- {{ __('Active Theme') }} Dropdown --}}
                        <div class="row mb-4 g-3">
                            <div class="col-md-6">
                                <label class="form-label-pro">{{ __('Active Theme') }}</label>
                                <select name="theme_id" class="form-control custom-input select2">
                                    <option value="">— {{ __('Select Theme') }} —</option>
                                    @foreach($themes as $theme)
                                    <option value="{{ $theme->id }}" 
                                        data-primary="{{ $theme->primary_color }}"
                                        data-secondary="{{ $theme->secondary_color }}"
                                        data-accent="{{ $theme->accent_color }}"
                                        data-footer="{{ $theme->footer_bg_color }}"
                                        {{ $edit_data->theme_id == $theme->id ? 'selected' : '' }}>
                                        {{ $theme->name }} @if($theme->is_default)(Default)@endif
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    <a href="{{ route('themes.index') }}">{{ __('Manage') }} {{ __('Themes') }} →</a>
                                </small>
                                {{-- {{ __('Live') }} color swatches --}}
                                @php $activeTheme = $edit_data->activeTheme; @endphp
                                <div class="theme-swatches mt-2 d-flex gap-1" id="themeSwatches">
                                    <span class="swatch d-inline-block rounded-circle" style="width:20px;height:20px;background:{{ optional($activeTheme)->primary_color ?? '#0d6efd' }};border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.2);"></span>
                                    <span class="swatch d-inline-block rounded-circle" style="width:20px;height:20px;background:{{ optional($activeTheme)->secondary_color ?? '#198754' }};border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.2);"></span>
                                    <span class="swatch d-inline-block rounded-circle" style="width:20px;height:20px;background:{{ optional($activeTheme)->accent_color ?? '#ff6a00' }};border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.2);"></span>
                                    <span class="swatch d-inline-block rounded-circle" style="width:20px;height:20px;background:{{ optional($activeTheme)->footer_bg_color ?? '#1a1a1a' }};border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.2);"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-pro">{{ __('Active Layout') }}</label>
                                <select name="active_layout_id" class="form-control custom-input select2">
                                    <option value="">— {{ __('Default Order') }} —</option>
                                    @foreach($layouts as $layout)
                                    <option value="{{ $layout->id }}" 
                                        {{ $edit_data->active_layout_id == $layout->id ? 'selected' : '' }}>
                                        {{ $layout->name }} @if($layout->is_default)(Default)@endif
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    <a href="{{ route('layouts.index') }}">{{ __('Manage') }} {{ __('Layouts') }} →</a>
                                </small>
                            </div>
                        </div>

                        <div class="row g-3">
                            @php
                                $colors = [
                                    'primary_color' => ['label' => 'Primary {{ __('Color') }}', 'default' => '#0d6efd'],
                                    'secodery_color' => ['label' => 'Secondary {{ __('Color') }}', 'default' => '#198754'],
                                    'footer_color' => ['label' => 'Footer {{ __('Color') }}', 'default' => '#222222'],
                                    'copyright_color' => ['label' => 'Copyright {{ __('Color') }}', 'default' => '#111111']
                                ];
                            @endphp
                            @foreach($colors as $key => $color)
                            <div class="col-md-6 col-xl-3">
                                <label class="form-label-pro">{{ $color['label'] }}</label>
                                <div class="color-box-pro">
                                    <input type="color" name="{{ $key }}" id="{{ $key }}_cp" 
                                           value="{{ old($key, $edit_data->$key ?? $color['default']) }}"
                                           class="form-control-color border-0 bg-transparent" 
                                           oninput="document.getElementById('{{ $key }}_txt').value=this.value;">
                                    <input type="text" id="{{ $key }}_txt" value="{{ old($key, $edit_data->$key ?? $color['default']) }}" 
                                           class="form-control border-0 p-0 small text-uppercase fw-bold" 
                                           style="font-size: 11px;"
                                           oninput="document.getElementById('{{ $key }}_cp').value=this.value;">
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="row mt-4 g-3">
                            @php
                                $logos = [
                                    'white_logo' => 'White Logo (For {{ __('Dark') }} Bg)',
                                    'dark_logo' => '{{ __('Dark') }} Logo (For {{ __('Light') }} Bg)',
                                    'favicon' => 'Favicon Icon',
                                    'og_baner' => 'Social Banner (OG)'
                                ];
                            @endphp
                            @foreach($logos as $slug => $label)
                            <div class="col-md-6">
                                <label class="form-label-pro">{{ $label }}</label>
                                <input type="file" name="{{ $slug }}" class="form-control custom-input mb-2">
                                <div class="logo-preview-box">
                                    <img src="{{asset($edit_data->$slug)}}" class="edit-image-pro" alt="{{ __('Prev') }}iew">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="settings-card">
                    <div class="section-title-pro">
                        <i class="mdi mdi-shield-check text-danger"></i> Business Logic
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <label class="form-label-pro">{{ __('{{ __('Hot Deal') }} {{ __('End {{ __('Date') }}') }}') }}</label>
                            <input type="date" name="hot_deal_end_date" class="form-control custom-input" value="{{ $edit_data->hot_deal_end_date }}">
                        </div>
                        <div class="mb-4">
                            <label class="form-label-pro">{{ __('Flash Sale {{ __('End {{ __('Date') }}') }}') }}</label>
                            <input type="date" name="flash_sale_end_date" class="form-control custom-input" value="{{ $edit_data->flash_sale_end_date }}">
                        </div>
                        <div class="mb-4">
                            <label class="form-label-pro">{{ __('{{ __('{{ __('Visibility') }} Control') }}s') }}</label>
                            <div class="d-grid gap-2">
                                <select class="form-select custom-input" name="show_all_products">
                                    <option value="1" @if($edit_data->show_all_products==1) selected @endif>{{ __('Home: Show {{ __('All {{ __('Product') }}s') }}') }}</option>
                                    <option value="0" @if($edit_data->show_all_products==0) selected @endif>{{ __('Home: Hide {{ __('All {{ __('Product') }}s') }}') }}</option>
                                </select>
                                <select class="form-select custom-input" name="show_category_wise_products">
                                    <option value="1" @if($edit_data->show_category_wise_products==1) selected @endif>{{ __('Home: {{ __('Category') }} Wise On') }}</option>
                                    <option value="0" @if($edit_data->show_category_wise_products==0) selected @endif>{{ __('Home: {{ __('Category') }} Wise Off') }}</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="settings-card">
                    <div class="section-title-pro">
                        <i class="mdi mdi-text-box-outline text-info"></i> Policies & {{ __('Note') }}s
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <label class="form-label-pro">{{ __('{{ __('Check') }}out {{ __('Note') }}') }}</label>
                            <textarea class="summernote" name="checkout_note">{{ $edit_data->checkout_note }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-pro">{{ __('Order Policy') }}</label>
                            <textarea class="summernote" name="order_policy">{{ $edit_data->order_policy }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 text-center mb-5">
                <button type="{{ __('submit') }}" class="btn-save-pro">
                    <i class="mdi mdi-content-save-all me-2"></i> Update Global Settings
                </button>
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
<script src="{{asset('public/backEnd/')}}/assets/libs/summernote/summernote-lite.min.js"></script>
<script>
    $(document).ready(function() {
        $('.summernote').summernote({
            placeholder: 'Type your policy or notes {{ __('here') }}...',
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview']]
            ]
        });

        // Theme selection → update swatches
        $('select[name="theme_id"]').on('change', function() {
            const selected = $(this).find('option:selected');
            const swatches = $('#themeSwatches .swatch');
            const colors = [
                selected.data('primary') || '#0d6efd',
                selected.data('secondary') || '#198754',
                selected.data('accent') || '#ff6a00',
                selected.data('footer') || '#1a1a1a'
            ];
            swatches.each(function(i) {
                $(this).css('background', colors[i] || '#ccc');
            });
        });
    });
</script>
@endsection