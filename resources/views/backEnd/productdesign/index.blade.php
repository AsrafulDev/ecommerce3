@extends('backEnd.layouts.master')
@section('title', __('Product Design'))

@section('css')
<style>
    .pd-header { border-radius: 16px; padding: 24px 28px; margin-bottom: 24px; }
    .pd-header h4 { margin: 0 0 5px; font-weight: 700; }
    .pd-header p { margin: 0; color: #64748b; }
    .pd-settings { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; margin-bottom: 24px; }
    .pd-settings-head { padding: 18px 22px; border-bottom: 1px solid #e2e8f0; }
    .pd-settings-head h5 { margin: 0 0 4px; font-weight: 700; }
    .pd-settings-head p { margin: 0; color: #64748b; font-size: 13px; }
    .pd-settings-body { padding: 20px 22px; }
    .pd-group { height: 100%; padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
    .pd-group h6 { margin: 0 0 12px; font-weight: 700; }
    .pd-devices { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .pd-device { background: #fff; padding: 10px; border: 1px solid #e2e8f0; border-radius: 9px; text-align: center; }
    .pd-device label { display: block; color: #64748b; font-size: 11px; margin-bottom: 5px; }
    .pd-device input { width: 100%; text-align: center; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; padding: 5px; }
    .pd-options { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 14px; }
    .pd-option { position: relative; display: block; padding: 18px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; background: #fff; }
    .pd-option:hover, .pd-option.active { border-color: var(--admin-primary, #2563eb); }
    .pd-option input { position: absolute; opacity: 0; }
    .pd-option strong { display: block; margin-bottom: 7px; }
    .pd-swatch { height: 58px; border-radius: 8px; margin-bottom: 12px; background: linear-gradient(135deg, #f8fafc, #dbeafe); }
    .pd-swatch.minimal { background: #fff; border: 1px solid #e2e8f0; }
    .pd-swatch.classic { background: #fff; border: 4px double #475569; }
    .pd-swatch.dark { background: #1e293b; }
    .pd-swatch.rounded { border-radius: 24px; background: #dbeafe; }
    .pd-swatch.gradient { background: linear-gradient(135deg, #2563eb, #14b8a6, #f59e0b); }
    @media (max-width: 700px) { .pd-devices { grid-template-columns: repeat(2, 1fr); } }
</style>
@endsection

@section('content')
<div class="container-fluid py-3">
    <div class="pd-header bg-light">
        <h4><i data-feather="grid"></i> {{ __('Product Design') }}</h4>
        <p>{{ __('Choose the storefront card style and responsive product grid settings.') }}</p>
    </div>

    <form method="POST" action="{{ route('product.design.save') }}">
        @csrf
        <div class="pd-settings">
            <div class="pd-settings-head bg-light">
                <h5>{{ __('Responsive Product Grid') }}</h5>
                <p>{{ __('Set how many cards appear per row on the home page and other storefront pages.') }}</p>
            </div>
            <div class="pd-settings-body">
                <div class="row g-3">
                    @foreach(['home' => ['label' => 'Home Page', 'defaults' => \App\Http\Controllers\Admin\ProductDesignController::ROW_LIMIT_FIELDS_HOME], 'other' => ['label' => 'Other Pages', 'defaults' => \App\Http\Controllers\Admin\ProductDesignController::ROW_LIMIT_FIELDS_OTHER]] as $group => $data)
                    <div class="col-lg-6">
                        <div class="pd-group">
                            <h6>{{ __($data['label']) }}</h6>
                            <div class="pd-devices">
                                @foreach(['desktop', 'laptop', 'tablet', 'phone'] as $device)
                                <div class="pd-device">
                                    <label>{{ __(ucfirst($device)) }}</label>
                                    <input type="number" name="pc_{{ $group }}_{{ $device }}" min="1" max="8" value="{{ old('pc_'.$group.'_'.$device, $setting->{'pc_'.$group.'_'.$device} ?? $data['defaults'][$device]) }}" required>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Product title lines') }}</label>
                        <input class="form-control" type="number" name="pc_title_lines" min="1" max="5" value="{{ old('pc_title_lines', $setting->pc_title_lines ?? 2) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Product image height (px)') }}</label>
                        <input class="form-control" type="number" name="pc_image_height" min="80" max="500" value="{{ old('pc_image_height', $setting->pc_image_height ?? 200) }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="pd-settings">
            <div class="pd-settings-head bg-light">
                <h5>{{ __('Card Style') }}</h5>
                <p>{{ __('Select the visual treatment used across product cards.') }}</p>
            </div>
            <div class="pd-settings-body">
                <div class="pd-options">
                    @foreach($designs as $key => $label)
                    <label class="pd-option {{ $activeDesign === $key ? 'active' : '' }}">
                        <input type="radio" name="style" value="{{ $key }}" {{ $activeDesign === $key ? 'checked' : '' }}>
                        <span class="pd-swatch {{ $key }}"></span>
                        <strong>{{ __($label) }}</strong>
                        <small class="text-muted">{{ $key === 'default' ? __('Layered storefront card') : __('Theme-controlled card style') }}</small>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit"><i data-feather="save"></i> {{ __('Save Product Design') }}</button>
    </form>
</div>
@endsection
