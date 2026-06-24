@extends('backEnd.layouts.master')
@section('title', $edit_data ? 'Edit Theme: ' . $edit_data->name : 'Create New Theme')

@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<style>
    .theme-editor-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .section-title-pro {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        padding: 15px 25px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 16px 16px 0 0;
    }
    .form-label-pro {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .custom-input {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 15px;
        transition: all 0.2s;
        width: 100%;
    }
    .custom-input:focus {
        background: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
        outline: none;
    }

    /* Color picker row */
    .color-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .color-row input[type="color"] {
        width: 44px;
        height: 44px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        padding: 2px;
        background: none;
    }
    .color-row input[type="text"] {
        flex: 1;
    }

    /* Live preview */
    .live-preview-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .live-preview-box .preview-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .preview-header {
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .preview-body {
        padding: 20px;
    }
    .preview-btn {
        padding: 8px 20px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: default;
    }
    .preview-footer {
        padding: 12px 20px;
        font-size: 12px;
        text-align: center;
    }
    .preview-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
    }

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
            <h4 class="fw-bold m-0 text-dark">
                @if($edit_data)
                    <i class="mdi mdi-palette me-2"></i> Edit Theme: {{ $edit_data->name }}
                @else
                    <i class="mdi mdi-palette-plus me-2"></i> Create New Theme
                @endif
            </h4>
            <a href="{{ route('themes.index') }}" class="text-muted small">
                <i class="fe-arrow-left me-1"></i> Back to Themes
            </a>
        </div>
    </div>

    <form action="{{ $edit_data ? route('themes.update') : route('themes.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if($edit_data)
            <input type="hidden" name="id" value="{{ $edit_data->id }}">
        @endif

        {{-- Live Preview --}}
        <div class="live-preview-box">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0"><i class="mdi mdi-eye me-1"></i> Live Preview</h6>
                <small class="text-muted">Colors update as you type hex values</small>
            </div>
            <div class="preview-card" id="livePreview" 
                 style="border:1px solid var(--pv-border, #dee2e6);">
                <div class="preview-header" id="pvHeader" style="background:var(--pv-header-bg, #ffffff);">
                    <span id="pvHeaderText" style="color:var(--pv-header-text, #212529);font-weight:700;font-size:14px;">My Store</span>
                    <span class="preview-btn" id="pvButton" style="background:var(--pv-button-bg, #0d6efd);color:var(--pv-button-text, #ffffff);">Shop Now</span>
                </div>
                <div class="preview-body" id="pvBody" style="background:var(--pv-body-bg, #ffffff);">
                    <h5 id="pvHeading" style="color:var(--pv-heading, #111111);font-weight:700;">Summer Collection 2026</h5>
                    <p id="pvText" style="color:var(--pv-text, #212529);">Get up to <span class="preview-badge" id="pvBadge" style="background:var(--pv-sale-bg, #dc3545);color:var(--pv-sale-text, #ffffff);">50% OFF</span> on selected items. Limited time offer!</p>
                </div>
                <div class="preview-footer" id="pvFooter" style="background:var(--pv-footer-bg, #1a1a1a);color:var(--pv-footer-text, #cccccc);">
                    © 2026 My Store. All rights reserved.
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Left Column: Basic Info + Brand Colors + Text Colors --}}
            <div class="col-lg-6">
                {{-- Basic Info --}}
                <div class="theme-editor-card">
                    <div class="section-title-pro"><i class="mdi mdi-information-outline text-primary"></i> Basic Information</div>
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-pro">Theme Name *</label>
                                <input type="text" name="name" class="custom-input" value="{{ old('name', $edit_data->name ?? '') }}" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-pro">Slug *</label>
                                <input type="text" name="slug" class="custom-input" value="{{ old('slug', $edit_data->slug ?? '') }}" required maxlength="120">
                            </div>
                            <div class="col-12">
                                <label class="form-label-pro">Description</label>
                                <textarea name="description" class="custom-input" rows="2">{{ old('description', $edit_data->description ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-pro">Preview Image</label>
                                <input type="file" name="preview_image" class="custom-input">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-pro">Active?</label>
                                <select name="is_active" class="custom-input">
                                    <option value="1" {{ old('is_active', $edit_data->is_active ?? true) ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('is_active', $edit_data->is_active ?? true) ? '' : 'selected' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-pro">Set as Default?</label>
                                <select name="is_default" class="custom-input">
                                    <option value="1" {{ old('is_default', $edit_data->is_default ?? false) ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('is_default', $edit_data->is_default ?? false) ? '' : 'selected' }}>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Brand Colors --}}
                <div class="theme-editor-card">
                    <div class="section-title-pro"><i class="mdi mdi-palette text-danger"></i> Brand Colors</div>
                    <div class="p-4">
                        <div class="row g-3">
                            @php
                                $brandColors = [
                                    'primary_color' => ['label' => 'Primary', 'default' => '#0d6efd'],
                                    'secondary_color' => ['label' => 'Secondary', 'default' => '#0b5ed7'],
                                    'accent_color' => ['label' => 'Accent', 'default' => '#ff6a00'],
                                ];
                            @endphp
                            @foreach($brandColors as $key => $c)
                            <div class="col-md-4">
                                <label class="form-label-pro">{{ $c['label'] }}</label>
                                <div class="color-row">
                                    <input type="color" id="{{ $key }}_cp" value="{{ old($key, $edit_data->$key ?? $c['default']) }}"
                                           oninput="updateColor('{{ $key }}', this.value);">
                                    <input type="text" name="{{ $key }}" id="{{ $key }}_txt" 
                                           value="{{ old($key, $edit_data->$key ?? $c['default']) }}"
                                           class="custom-input text-uppercase small fw-bold" style="font-size:11px;"
                                           oninput="updateColor('{{ $key }}', this.value);">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Text Colors --}}
                <div class="theme-editor-card">
                    <div class="section-title-pro"><i class="mdi mdi-format-color-text text-warning"></i> Text Colors</div>
                    <div class="p-4">
                        <div class="row g-3">
                            @php
                                $textColors = [
                                    'text_color' => ['label' => 'Body Text', 'default' => '#212529'],
                                    'heading_color' => ['label' => 'Headings', 'default' => '#111111'],
                                    'header_text_color' => ['label' => 'Header Text', 'default' => '#212529'],
                                    'footer_text_color' => ['label' => 'Footer Text', 'default' => '#cccccc'],
                                    'copyright_text_color' => ['label' => 'Copyright Text', 'default' => '#ffffff'],
                                    'button_text_color' => ['label' => 'Button Text', 'default' => '#ffffff'],
                                    'sale_badge_text' => ['label' => 'Sale Badge Text', 'default' => '#ffffff'],
                                ];
                            @endphp
                            @foreach($textColors as $key => $c)
                            <div class="col-md-6">
                                <label class="form-label-pro">{{ $c['label'] }}</label>
                                <div class="color-row">
                                    <input type="color" id="{{ $key }}_cp" value="{{ old($key, $edit_data->$key ?? $c['default']) }}"
                                           oninput="updateColor('{{ $key }}', this.value);">
                                    <input type="text" name="{{ $key }}" id="{{ $key }}_txt" 
                                           value="{{ old($key, $edit_data->$key ?? $c['default']) }}"
                                           class="custom-input text-uppercase small fw-bold" style="font-size:11px;"
                                           oninput="updateColor('{{ $key }}', this.value);">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Backgrounds + UI Elements + Typography --}}
            <div class="col-lg-6">
                {{-- Background Colors --}}
                <div class="theme-editor-card">
                    <div class="section-title-pro"><i class="mdi mdi-invert-colors text-success"></i> Background Colors</div>
                    <div class="p-4">
                        <div class="row g-3">
                            @php
                                $bgColors = [
                                    'body_bg_color' => ['label' => 'Body Background', 'default' => '#ffffff'],
                                    'header_bg_color' => ['label' => 'Header Background', 'default' => '#ffffff'],
                                    'footer_bg_color' => ['label' => 'Footer Background', 'default' => '#1a1a1a'],
                                    'copyright_bg_color' => ['label' => 'Copyright Background', 'default' => '#000000'],
                                    'button_bg_color' => ['label' => 'Button Background', 'default' => '#0d6efd'],
                                    'button_hover_bg_color' => ['label' => 'Button Hover BG', 'default' => '#0b5ed7'],
                                    'sale_badge_bg' => ['label' => 'Sale Badge BG', 'default' => '#dc3545'],
                                ];
                            @endphp
                            @foreach($bgColors as $key => $c)
                            <div class="col-md-6">
                                <label class="form-label-pro">{{ $c['label'] }}</label>
                                <div class="color-row">
                                    <input type="color" id="{{ $key }}_cp" value="{{ old($key, $edit_data->$key ?? $c['default']) }}"
                                           oninput="updateColor('{{ $key }}', this.value);">
                                    <input type="text" name="{{ $key }}" id="{{ $key }}_txt" 
                                           value="{{ old($key, $edit_data->$key ?? $c['default']) }}"
                                           class="custom-input text-uppercase small fw-bold" style="font-size:11px;"
                                           oninput="updateColor('{{ $key }}', this.value);">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 🖥️ Admin Panel Colors --}}
                <div class="theme-editor-card">
                    <div class="section-title-pro"><i class="mdi mdi-monitor-dashboard text-secondary"></i> Admin Panel Colors</div>
                    <div class="p-4">
                        <div class="row g-3">
                            @php
                                $adminColors = [
                                    'sidebar_bg_color' => ['label' => 'Sidebar Background', 'default' => '#1e293b'],
                                    'sidebar_text_color' => ['label' => 'Sidebar Text', 'default' => '#ffffff'],
                                    'topbar_bg_color' => ['label' => 'Topbar Background', 'default' => '#0f172a'],
                                    'admin_card_bg' => ['label' => 'Card Background', 'default' => '#ffffff'],
                                ];
                            @endphp
                            @foreach($adminColors as $key => $c)
                            <div class="col-md-6">
                                <label class="form-label-pro">{{ $c['label'] }}</label>
                                <div class="color-row">
                                    <input type="color" id="{{ $key }}_cp" value="{{ old($key, $edit_data->$key ?? $c['default']) }}"
                                           oninput="updateColor('{{ $key }}', this.value);">
                                    <input type="text" name="{{ $key }}" id="{{ $key }}_txt" 
                                           value="{{ old($key, $edit_data->$key ?? $c['default']) }}"
                                           class="custom-input text-uppercase small fw-bold" style="font-size:11px;"
                                           oninput="updateColor('{{ $key }}', this.value);">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- UI Elements --}}
                <div class="theme-editor-card">
                    <div class="section-title-pro"><i class="mdi mdi-border-all text-info"></i> UI Elements</div>
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-pro">Border Color</label>
                                <div class="color-row">
                                    <input type="color" id="border_color_cp" value="{{ old('border_color', $edit_data->border_color ?? '#dee2e6') }}"
                                           oninput="updateColor('border_color', this.value);">
                                    <input type="text" name="border_color" id="border_color_txt" 
                                           value="{{ old('border_color', $edit_data->border_color ?? '#dee2e6') }}"
                                           class="custom-input text-uppercase small fw-bold" style="font-size:11px;"
                                           oninput="updateColor('border_color', this.value);">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-pro">Border Radius</label>
                                <input type="text" name="border_radius" class="custom-input" 
                                       value="{{ old('border_radius', $edit_data->border_radius ?? '8px') }}" placeholder="e.g. 8px">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-pro">Card Shadow</label>
                                <input type="text" name="card_shadow" class="custom-input" 
                                       value="{{ old('card_shadow', $edit_data->card_shadow ?? '') }}" placeholder="e.g. 0 2px 8px rgba(0,0,0,0.08)">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Typography & Layout --}}
                <div class="theme-editor-card">
                    <div class="section-title-pro"><i class="mdi mdi-format-font text-purple"></i> Typography & Layout</div>
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-pro">Body Font Family</label>
                                <input type="text" name="font_family" class="custom-input" 
                                       value="{{ old('font_family', $edit_data->font_family ?? "'Roboto', sans-serif") }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-pro">Heading Font</label>
                                <input type="text" name="heading_font" class="custom-input" 
                                       value="{{ old('heading_font', $edit_data->heading_font ?? "'Jost', sans-serif") }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-pro">Body Font Size</label>
                                <input type="text" name="body_font_size" class="custom-input" 
                                       value="{{ old('body_font_size', $edit_data->body_font_size ?? '14px') }}" placeholder="14px">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-pro">Heading Weight</label>
                                <select name="heading_font_weight" class="custom-input">
                                    @foreach(['300'=>'Light','400'=>'Normal','500'=>'Medium','600'=>'Semi Bold','700'=>'Bold','800'=>'Extra Bold','900'=>'Black'] as $v=>$l)
                                    <option value="{{ $v }}" {{ old('heading_font_weight', $edit_data->heading_font_weight ?? '700') == $v ? 'selected' : '' }}>{{ $l }} ({{ $v }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-pro">Layout Style</label>
                                <select name="layout_style" class="custom-input">
                                    <option value="contained" {{ old('layout_style', $edit_data->layout_style ?? '') == 'contained' ? 'selected' : '' }}>Contained</option>
                                    <option value="full-width" {{ old('layout_style', $edit_data->layout_style ?? '') == 'full-width' ? 'selected' : '' }}>Full Width</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label-pro">Custom CSS</label>
                                <textarea name="custom_css" class="custom-input" rows="4" placeholder="/* Write custom CSS overrides here */">{{ old('custom_css', $edit_data->custom_css ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="text-center mb-5 mt-3">
            <button type="submit" class="btn-save-pro">
                <i class="mdi mdi-content-save-all me-2"></i> 
                {{ $edit_data ? 'Update Theme' : 'Create Theme' }}
            </button>
            <a href="{{ route('themes.index') }}" class="btn btn-light rounded-pill px-4 ms-2 fw-bold">Cancel</a>
        </div>
    </form>
</div>
@endsection

@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script>
    // Update both color picker + text input and live preview
    function updateColor(field, value) {
        // Sync picker <-> text
        const cp = document.getElementById(field + '_cp');
        const txt = document.getElementById(field + '_txt');
        if (cp && value !== cp.value) cp.value = value;
        if (txt && value !== txt.value) txt.value = value;

        // Update live preview CSS variables
        const preview = document.getElementById('livePreview');
        const map = {
            'primary_color': '--pv-button-bg',
            'button_bg_color': '--pv-button-bg',
            'button_text_color': '--pv-button-text',
            'body_bg_color': '--pv-body-bg',
            'header_bg_color': '--pv-header-bg',
            'header_text_color': '--pv-header-text',
            'footer_bg_color': '--pv-footer-bg',
            'footer_text_color': '--pv-footer-text',
            'text_color': '--pv-text',
            'heading_color': '--pv-heading',
            'sale_badge_bg': '--pv-sale-bg',
            'sale_badge_text': '--pv-sale-text',
            'border_color': '--pv-border',
        };
        const varName = map[field];
        if (varName && preview) {
            preview.style.setProperty(varName, value);
        }
    }

    $(document).ready(function() {
        // Auto-generate slug from name
        $('input[name="name"]').on('blur', function() {
            const slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
            const slugField = $('input[name="slug"]');
            if (!slugField.val()) slugField.val(slug);
        });

        // Initialize select2
        $('.select2').select2();
    });
</script>
@endsection
