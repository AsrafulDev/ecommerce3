@extends('backEnd.layouts.master')
@section('title','Theme Manager')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
    :root {
        --theme-card-radius: 16px;
        --theme-card-shadow: 0 4px 20px rgba(0,0,0,0.06);
        --theme-card-hover-shadow: 0 8px 32px rgba(0,0,0,0.12);
    }

    .page-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 20px;
        padding: 30px 35px;
        margin-bottom: 30px;
    }
    .page-header h4 {
        color: #fff;
        font-weight: 700;
        font-size: 22px;
    }
    .page-header p {
        color: #94a3b8;
        margin: 0;
        font-size: 14px;
    }

    /* Theme Card Grid */
    .theme-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 24px;
        margin-top: 10px;
    }

    .theme-card {
        background: #fff;
        border-radius: var(--theme-card-radius);
        box-shadow: var(--theme-card-shadow);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
    }
    .theme-card:hover {
        box-shadow: var(--theme-card-hover-shadow);
        transform: translateY(-4px);
    }
    .theme-card.active-theme {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59,130,246,0.2), var(--theme-card-hover-shadow);
    }

    /* Preview Strip */
    .theme-preview {
        height: 100px;
        position: relative;
        display: flex;
        align-items: flex-end;
        padding: 12px 16px;
    }
    .theme-preview .brand-bar {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 42px;
        display: flex;
        align-items: center;
        padding: 0 16px;
    }
    .theme-preview .brand-bar .logo-dot {
        width: 28px; height: 28px;
        border-radius: 6px;
        opacity: 0.9;
    }
    .theme-preview .footer-bar {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .theme-preview .footer-bar .line {
        width: 60%; height: 4px;
        border-radius: 4px;
        opacity: 0.3;
    }
    .theme-preview .body-block {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 6px;
        padding-top: 30px;
    }
    .theme-preview .body-block .block {
        width: 75%;
        height: 6px;
        border-radius: 4px;
        opacity: 0.12;
    }

    /* Color Swatches Row */
    .color-swatches {
        display: flex;
        gap: 6px;
        padding: 12px 16px 0;
        flex-wrap: wrap;
    }
    .color-swatches .swatch {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        transition: transform 0.2s;
        cursor: pointer;
    }
    .color-swatches .swatch:hover {
        transform: scale(1.3);
        z-index: 2;
    }

    /* Card Body */
    .theme-card-body {
        padding: 12px 16px 16px;
    }
    .theme-card-body .theme-name {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .theme-card-body .theme-meta {
        font-size: 12px;
        color: #64748b;
        margin: 2px 0 8px;
    }

    /* Badges */
    .badge-default {
        background: #3b82f6;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .badge-inactive {
        background: #e2e8f0;
        color: #64748b;
    }

    /* Action buttons */
    .theme-actions {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
    }
    .theme-actions .btn-sm-custom {
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .theme-actions .btn-apply {
        background: #0f172a;
        color: #fff;
        flex: 1;
    }
    .theme-actions .btn-apply:hover {
        background: #1e293b;
    }
    .theme-actions .btn-apply.applied {
        background: #22c55e;
        pointer-events: none;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .theme-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    {{-- Page Header --}}
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h4><i class="mdi mdi-palette-advanced me-2"></i> {{ __('Theme Manager') }} </h4>
            <p>Manage 20+ color themes — Apply, Edit, Duplicate, or Create new themes</p>
        </div>
        <div>
            <a href="{{ route('themes.create') }}" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm">
                <i class="fe-plus me-1"></i> {{ __('Create Theme') }} </a>
        </div>
    </div>

    {{-- Active Theme Banner --}}
    @if($activeTheme)
    <div class="alert alert-info d-flex align-items-center gap-3 rounded-4 border-0 shadow-sm mb-4" style="background:#eff6ff;">
        <i class="mdi mdi-check-circle text-primary fs-3"></i>
        <div>
            <strong class="text-dark">Active Theme:</strong>
            <span class="fw-bold ms-1">{{ $activeTheme->name }}</span>
            <span class="badge bg-primary ms-2" style="font-size:10px;">
                <span class="swatch d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:{{ $activeTheme->primary_color }};vertical-align:middle;"></span>
                {{ $activeTheme->primary_color }}
            </span>
        </div>
        <a href="{{ route('layouts.index') }}" class="btn btn-outline-primary btn-sm rounded-pill ms-auto">
            <i class="mdi mdi-view-dashboard me-1"></i> Manage Layouts
        </a>
    </div>
    @endif

    {{-- Theme Grid --}}
    <div class="theme-grid">
        @forelse($themes as $theme)
        @php
            $isActive = $theme->is_default;
        @endphp
        <div class="theme-card {{ $isActive ? 'active-theme' : '' }}">
            {{-- Preview Strip with actual theme colors --}}
            <div class="theme-preview" style="background:{{ $theme->body_bg_color ?? '#ffffff' }};">
                <div class="brand-bar" style="background:{{ $theme->header_bg_color ?? '#ffffff' }};">
                    <span class="logo-dot" style="background:{{ $theme->primary_color }};"></span>
                </div>
                <div class="body-block">
                    <span class="block" style="background:{{ $theme->heading_color }};"></span>
                    <span class="block" style="background:{{ $theme->text_color }};width:55%;"></span>
                    <span class="block" style="background:{{ $theme->primary_color }};width:40%;height:26px;border-radius:6px;opacity:0.2;"></span>
                </div>
                <div class="footer-bar" style="background:{{ $theme->footer_bg_color ?? '#1a1a1a' }};">
                    <span class="line" style="background:{{ $theme->footer_text_color ?? '#cccccc' }};"></span>
                </div>
                {{-- Active overlay --}}
                @if($isActive)
                <div class="position-absolute top-0 end-0 m-2">
                    <span class="badge-default"><i class="fe-check me-1"></i>{{ __('Active') }}</span>
                </div>
                @endif
            </div>

            {{-- Color swatches --}}
            <div class="color-swatches">
                <span class="swatch" style="background:{{ $theme->primary_color }}" title="Primary: {{ $theme->primary_color }}"></span>
                <span class="swatch" style="background:{{ $theme->secondary_color }}" title="Secondary: {{ $theme->secondary_color }}"></span>
                <span class="swatch" style="background:{{ $theme->accent_color }}" title="Accent: {{ $theme->accent_color }}"></span>
                <span class="swatch" style="background:{{ $theme->button_bg_color }}" title="Button: {{ $theme->button_bg_color }}"></span>
                <span class="swatch" style="background:{{ $theme->text_color }}" title="Text: {{ $theme->text_color }}"></span>
                <span class="swatch" style="background:{{ $theme->footer_bg_color }}" title="Footer BG: {{ $theme->footer_bg_color }}"></span>
            </div>

            {{-- Card body --}}
            <div class="theme-card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="theme-name">{{ $theme->name }}</h5>
                        <div class="theme-meta">
                            @if(!$theme->is_active)
                                <span class="badge badge-inactive">{{ __('Inactive') }}</span>
                            @endif
                            <span>{{ $theme->layout_style ?? 'contained' }}</span>
                            <span class="mx-1">·</span>
                            <span>{{ $theme->border_radius }}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="theme-actions">
                    @if(!$isActive)
                    <a href="{{ route('themes.apply', $theme->id) }}" class="btn-sm-custom btn-apply" onclick="return confirm('Apply &quot;{{ $theme->name }}&quot; theme?')">
                        <i class="fe-check me-1"></i>{{ __('Apply') }}</a>
                    @else
                    <button class="btn-sm-custom btn-apply applied" disabled>
                        <i class="fe-check me-1"></i> Applied
                    </button>
                    @endif
                    <a href="{{ route('themes.edit', $theme->id) }}" class="btn-sm-custom" style="background:#f1f5f9;color:#475569;">
                        <i class="fe-edit-2 me-1"></i>{{ __('Edit') }}</a>
                    <a href="{{ route('themes.duplicate', $theme->id) }}" class="btn-sm-custom" style="background:#f1f5f9;color:#475569;">
                        <i class="fe-copy me-1"></i>
                    </a>
                    @if(!$isActive)
                    <form action="{{ route('themes.destroy') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="hidden_id" value="{{ $theme->id }}">
                        <button type="submit" class="btn-sm-custom" style="background:#fef2f2;color:#ef4444;" onclick="return confirm('Delete &quot;{{ $theme->name }}&quot;?')">
                            <i class="fe-trash-2"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <i class="mdi mdi-palette-swatch-outline" style="font-size:48px;color:#cbd5e1;"></i>
            <h5 class="mt-3 text-muted"> {{ __('No themes yet') }} </h5>
            <a href="{{ route('themes.create') }}" class="btn btn-primary rounded-pill mt-2"> {{ __('Create Your First Theme') }} </a>
        </div>
        @endforelse
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Theme card hover color preview
        $('.swatch').tooltip({ boundary: 'window' });
    });
</script>
@endsection
