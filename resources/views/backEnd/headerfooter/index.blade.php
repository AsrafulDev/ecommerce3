@extends('backEnd.layouts.master')
@section('title', 'Header & Footer Builder')

@section('css')
<link rel="stylesheet" href="{{ asset('public/backEnd/css/headerfooter-builder.css') }}">
@endsection

@section('content')
<div class="container-fluid hf-builder">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title"><i class="mdi mdi-page-layout-header text-primary"></i> {{ __('Header & Footer Builder') }}</h4>
        <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-open-in-new"></i> {{ __('View Site') }}</a>
    </div>

    <ul class="nav nav-tabs builder-tabs" id="builderTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link {{ ($setting->header_style ?? 'custom') !== 'custom' ? 'active' : '' }}"
                    id="presets-tab" data-bs-toggle="tab" data-bs-target="#presets-panel" type="button">
                <i class="mdi mdi-palette-outline me-1"></i> {{ __('Preset Styles') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ ($setting->header_style ?? 'custom') === 'custom' ? 'active' : '' }}"
                    id="custom-tab" data-bs-toggle="tab" data-bs-target="#custom-panel" type="button">
                <i class="mdi mdi-vector-square me-1"></i> {{ __('Custom Builder') }}
            </button>
        </li>
    </ul>

    <form action="{{ route('headerfooter.update') }}" method="POST" id="headerFooterForm">
        @csrf
        <input type="hidden" name="header_components" id="hfHeaderInput">
        <input type="hidden" name="footer_components" id="hfFooterInput">

        <div class="tab-content">

            {{-- ════════════ PRESETS TAB ════════════ --}}
            @php
                $hIcons = ['default'=>'mdi-home','classic'=>'mdi-page-layout-header','modern'=>'mdi-view-dashboard','minimal'=>'mdi-page-layout-body','centered'=>'mdi-align-horizontal-center','mega'=>'mdi-menu-open','custom'=>'mdi-cog'];
                $fIcons = ['default'=>'mdi-home','classic'=>'mdi-page-layout-footer','modern'=>'mdi-view-dashboard','dark'=>'mdi-invert-colors','minimal'=>'mdi-dots-horizontal','columns'=>'mdi-view-column','custom'=>'mdi-cog'];
            @endphp
            <div class="tab-pane fade {{ ($setting->header_style ?? 'custom') !== 'custom' ? 'show active' : '' }}" id="presets-panel">
                <div class="card mb-4">
                    <div class="card-header bg-light"><h5 class="mb-0"><i class="mdi mdi-page-layout-header me-2"></i>{{ __('Header Style') }}</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($headerStyles as $key => $name)
                            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                                <label class="style-card {{ ($setting->header_style ?? 'custom') == $key ? 'active' : '' }} d-block" data-style-type="header" data-style-value="{{ $key }}">
                                    <input type="radio" name="header_style" value="{{ $key }}" {{ ($setting->header_style ?? 'custom') == $key ? 'checked' : '' }} class="d-none">
                                    <div class="text-center">
                                        <i class="mdi {{ $hIcons[$key] ?? 'mdi-page-layout-header' }} style-icon"></i>
                                        <h6 class="mt-2 mb-1">{{ $name }}</h6>
                                        @if($key==='custom')<small class="text-primary fw-bold">{{ __('← Custom Builder') }}</small>@endif
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="d-flex align-items-center gap-2">
                                    <label class="toggle-switch"><input type="checkbox" name="header_top_bar" value="1" {{ ($setting->header_top_bar ?? 1) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                    <span>{{ __('Show Top Bar') }}</span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="d-flex align-items-center gap-2">
                                    <label class="toggle-switch"><input type="checkbox" name="header_sticky" value="1" {{ ($setting->header_sticky ?? 1) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                    <span>{{ __('Sticky Header') }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="row mt-3 align-items-center border-top pt-3">
                            <div class="col-md-6">
                                <label class="d-flex align-items-center gap-2">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="header_all_category_button" value="1" id="allCatBtnToggle" {{ ($setting->header_all_category_button ?? 1) ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span><i class="mdi mdi-view-grid-plus me-1 text-primary"></i>{{ __('All Category Button') }}</span>
                                </label>
                                <small class="text-muted d-block ms-5 mt-1">{{ __('Show an "All Categories" button in the header nav — dropdown nav / mega menu / icon menu / shop link.') }}</small>
                            </div>
                            <div class="col-md-6" id="allCatTypeWrap">
                                <label class="mb-1 fw-semibold d-block small">{{ __('Button Type') }}</label>
                                <select name="header_all_category_type" class="form-select form-select-sm">
                                    @foreach(['dropdown'=>__('Dropdown Nav'),'mega'=>__('Mega Menu'),'icon'=>__('Icon Menu'),'shop'=>__('Shop Link')] as $tv => $tl)
                                    <option value="{{ $tv }}" {{ ($setting->header_all_category_type ?? 'mega') === $tv ? 'selected' : '' }}>{{ $tl }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('How the "All Categories" button looks & opens on the site header.') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between"><h5 class="mb-0"><i class="mdi mdi-eye me-2"></i>{{ __('Header Preview') }}</h5><span class="badge bg-primary" id="header-preview-label">{{ $headerStyles[$setting->header_style ?? 'custom'] ?? 'Custom' }}</span></div>
                    <div class="card-body p-0"><div class="preview-frame" id="header-preview"><div class="d-flex justify-content-center align-items-center h-100 text-muted">{{ __('Select a style to preview') }}</div></div></div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light"><h5 class="mb-0"><i class="mdi mdi-page-layout-footer me-2"></i>{{ __('Footer Style') }}</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($footerStyles as $key => $name)
                            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                                <label class="style-card {{ ($setting->footer_style ?? 'custom') == $key ? 'active' : '' }} d-block" data-style-type="footer" data-style-value="{{ $key }}">
                                    <input type="radio" name="footer_style" value="{{ $key }}" {{ ($setting->footer_style ?? 'custom') == $key ? 'checked' : '' }} class="d-none">
                                    <div class="text-center">
                                        <i class="mdi {{ $fIcons[$key] ?? 'mdi-page-layout-footer' }} style-icon"></i>
                                        <h6 class="mt-2 mb-1">{{ $name }}</h6>
                                        @if($key==='custom')<small class="text-primary fw-bold">{{ __('← Custom Builder') }}</small>@endif
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between"><h5 class="mb-0"><i class="mdi mdi-eye me-2"></i>{{ __('Footer Preview') }}</h5><span class="badge bg-primary" id="footer-preview-label">{{ $footerStyles[$setting->footer_style ?? 'custom'] ?? 'Custom' }}</span></div>
                    <div class="card-body p-0"><div class="preview-frame" id="footer-preview"><div class="d-flex justify-content-center align-items-center h-100 text-muted">{{ __('Select a style to preview') }}</div></div></div>
                </div>
            </div>

            {{-- ════════════ CUSTOM BUILDER TAB ════════════ --}}
            <div class="tab-pane fade {{ ($setting->header_style ?? 'custom') === 'custom' ? 'show active' : '' }}" id="custom-panel">

                <div class="hf-toolbar">
                    <div class="hf-toolbar-left"><span class="hf-save-state" id="hfSaveState"></span></div>
                    <div class="hf-device-switch" role="group" aria-label="{{ __('Preview device') }}">
                        <button type="button" class="hf-device-btn active" data-device="desktop" title="{{ __('Desktop (≥1200px)') }}"><i class="mdi mdi-monitor"></i></button>
                        <button type="button" class="hf-device-btn" data-device="laptop" title="{{ __('Laptop (992–1199px)') }}"><i class="mdi mdi-laptop"></i></button>
                        <button type="button" class="hf-device-btn" data-device="tablet" title="{{ __('Tablet (576–991px)') }}"><i class="mdi mdi-tablet"></i></button>
                        <button type="button" class="hf-device-btn" data-device="phone" title="{{ __('Phone (<576px)') }}"><i class="mdi mdi-cellphone-iphone"></i></button>
                    </div>
                    <div class="hf-toolbar-right">
                        <button type="button" class="btn btn-sm btn-light border rounded-pill" id="hfRefreshBtn" title="{{ __('Reload the preview') }}"><i class="mdi mdi-refresh"></i> {{ __('Refresh') }}</button>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill" id="hfResetBtn" title="{{ __('Restore all default components') }}"><i class="mdi mdi-history"></i> {{ __('Reset') }}</button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4"><i class="mdi mdi-content-save"></i> {{ __('Save') }}</button>
                    </div>
                </div>

                <div class="hf-grid">
                    {{-- Left: widget library --}}
                    <aside class="hf-pane hf-widgets">
                        <div class="hf-pane-title"><i class="mdi mdi-view-grid-plus"></i> {{ __('Components') }}</div>
                        @foreach(['header' => $hComps, 'footer' => $fComps] as $wType => $pool)
                        <div class="hf-widget-group" data-w-type="{{ $wType }}">
                            <h6 class="hf-widget-group-title">{{ ucfirst($wType) }}</h6>
                            @foreach($pool as $key => $comp)
                            <div class="hf-widget" data-type="{{ $wType }}" data-comp="{{ $key }}">
                                <div class="pool-icon"><i class="mdi {{ $comp['icon'] }}"></i></div>
                                <div class="hf-widget-name">{{ $comp['name'] }}</div>
                                <button type="button" class="btn-ghost hf-widget-add" title="{{ __('Add') }}"><i class="mdi mdi-plus-circle-outline"></i></button>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    </aside>

                    {{-- Center: preview + canvas lists --}}
                    <section class="hf-canvas">
                        <div class="hf-preview-card">
                            <div class="hf-preview-head">
                                <span><i class="mdi mdi-monitor-screenshot me-1"></i> {{ __('Live Preview') }}</span>
                                <span class="badge bg-secondary rounded-pill" id="hfDeviceLabel">{{ __('Desktop') }}</span>
                            </div>
                            <div class="hf-preview-stage" id="hfPreviewStage" data-device="desktop">
                                <div class="hf-stage-hint"><div class="spinner-border spinner-border-sm text-primary"></div> {{ __('Loading live preview…') }}</div>
                                <iframe id="hfPreviewIframe" title="{{ __('Header & footer preview') }}" frameborder="0"></iframe>
                            </div>
                            <div class="hf-preview-sub" id="hfPreviewSub" data-device="desktop"></div>
                        </div>

                        @foreach(['header' => __('Header Rows'), 'footer' => __('Footer Rows')] as $lType => $lLabel)
                        @php
                            $lState = $lType === 'header' ? $activeHeader : $activeFooter;
                            $lCount = count(\App\Support\HeaderFooterComponents::ids($lState));
                        @endphp
                        <div class="hf-list-card">
                            <div class="hf-list-head">
                                <span><i class="mdi mdi-{{ $lType === 'header' ? 'page-layout-header' : 'page-layout-footer' }} me-1"></i> {{ $lLabel }}</span>
                                <span class="badge bg-dark rounded-pill" data-count="{{ $lType }}">{{ $lCount }}</span>
                            </div>

                            {{-- Three fixed row zones: Top / Main / Bottom --}}
                            @foreach(\App\Support\HeaderFooterComponents::ROWS as $zone)
                            <div class="hf-zone" data-type="{{ $lType }}" data-zone="{{ $zone }}">
                                <div class="hf-zone-head">
                                    <span class="hf-zone-name">{{ ucfirst($zone) }} {{ __('Row') }}</span>
                                    <span class="hf-zone-actions">
                                        <span class="badge bg-light text-dark border" data-zone-count="{{ $lType }}-{{ $zone }}">0</span>
                                        <button type="button" class="btn btn-sm btn-light border hf-add-col"
                                                data-type="{{ $lType }}" data-zone="{{ $zone }}"
                                                title="{{ __('Add a column to this row') }}">
                                            <i class="mdi mdi-plus"></i> {{ __('Column') }}
                                        </button>
                                    </span>
                                </div>
                                <div class="hf-columns" data-list="{{ $lType }}" data-zone="{{ $zone }}"></div>
                                <div class="hf-zone-empty" data-empty="{{ $lType }}-{{ $zone }}">
                                    <i class="mdi mdi-view-column-outline"></i>
                                    <span>{{ __('Empty row — add a column, then drag components into it.') }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    </section>

                    {{-- Right: settings --}}
                    <aside class="hf-pane hf-settings" id="hfSettings">
                        <div class="hf-pane-title"><i class="mdi mdi-cog-outline"></i> {{ __('Settings') }}</div>
                        <div id="hfSettingsBody">
                            <p class="text-muted small">{{ __('Select a component on the canvas to edit its device visibility.') }}</p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill"><i class="mdi mdi-content-save"></i> {{ __('Save Header & Footer') }}</button>
        </div>
    </form>
</div>

@section('script')
@php
    use App\Support\HeaderFooterComponents;

    $hfConfig = [
        'csrf'       => csrf_token(),
        'previewUrl' => route('headerfooter.preview'),
        'pools'      => ['header' => $hComps, 'footer' => $fComps],
        'state'      => ['header' => $activeHeader, 'footer' => $activeFooter],
        'rows'       => HeaderFooterComponents::ROWS,
        'devices'    => HeaderFooterComponents::DEVICES,
        'maxColumns' => HeaderFooterComponents::MAX_COLUMNS,
        'fullWidth'  => HeaderFooterComponents::FULL_WIDTH,
        'defaults'   => [
            'header' => HeaderFooterComponents::defaultWidgetIds('header'),
            'footer' => HeaderFooterComponents::defaultWidgetIds('footer'),
        ],
    ];
@endphp
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
window.HF_BUILDER = @json($hfConfig);
</script>
<script src="{{ asset('public/backEnd/js/headerfooter-builder.js') }}"></script>
@endsection
@endsection
