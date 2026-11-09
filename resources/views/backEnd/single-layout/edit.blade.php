@extends('backEnd.layouts.master')
@section('title', __('Single Layout').': '.$layout->name)

@section('css')
<style>
    .sl-frame { width: 100%; height: calc(100vh - 230px); min-height: 640px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; position: sticky; }
    .sl-panel { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(15, 23, 42, .05); padding: 14px 16px; margin-bottom: 14px; }
    .sl-panel h6 { font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #64748b; margin-bottom: 10px; }
    .sl-panel .form-check { margin-bottom: 6px; }
    .sl-theme-preset { display: flex; align-items: center; gap: 10px; }
    .sl-theme-preset.active { color: #1c1917; border-color: #1c1917; background: #f1f5f9; font-weight: 600; }
    .sl-swatches { display: inline-flex; }
    .sl-swatches i { width: 16px; height: 16px; border-radius: 50%; border: 1px solid #e2e8f0; margin-right: -4px; display: inline-block; }
    .sl-colour-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 10px; }
    .sl-colour-grid label { display: flex; justify-content: space-between; align-items: center; gap: 6px; margin: 0; }
    .sl-colour-grid input[type="color"] { width: 40px; height: 28px; padding: 2px; }
</style>
@endsection

@section('content')
@php
    $customRows = old('custom_buttons', $settings['custom_buttons']);
    $nextCustomIndex = $customRows ? max(array_keys($customRows)) + 1 : 0;
@endphp
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">{{ $layout->name }}</h4>
            <small class="text-muted">{{ $layout->is_active ? __('Active: customers see this layout') : __('Not active. Activate it from the list to use it.') }}</small>
        </div>
        <a href="{{ route('single-layout.index') }}" class="btn btn-sm btn-light">{{ __('Back to layouts') }}</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-9">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <label for="sl-product" class="small text-muted mb-0">{{ __('Preview on product') }}</label>
                <select id="sl-product" name="product" class="form-select form-select-sm" style="max-width: 380px" onchange="this.form.submit()">
                    @foreach($products as $p)
                        <option value="{{ $p->slug }}" @selected($product && $product->id === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </form>
            @if($product)
                <iframe id="slFrame" class="sl-frame" src="{{ route('product', $product->slug) }}?sl_preview=1" title="{{ __('Product page preview') }}"></iframe>
            @else
                <div class="alert alert-info">{{ __('There is no approved, active product to preview yet.') }}</div>
            @endif
        </div>

        <div class="col-lg-3">
            <form id="slForm" method="POST" action="{{ route('single-layout.update', $layout->id) }}">
                @csrf
                @if($errors->any())
                    <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
                @endif

                <div class="sl-panel">
                    <h6>{{ __('Name') }}</h6>
                    <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name', $layout->name) }}" required maxlength="100">
                </div>

                <div class="sl-panel">
                    <h6>{{ __('Sections') }}</h6>
                    @foreach($sectionLabels as $key => $label)
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="sections[{{ $key }}]" value="1" id="sl-sec-{{ $key }}" @checked($settings['sections'][$key])>
                            <label class="form-check-label" for="sl-sec-{{ $key }}">{{ __($label) }}</label>
                        </div>
                    @endforeach
                </div>

                <div class="sl-panel">
                    <h6>{{ __('Gallery and buy box') }}</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="layout[gallery]" value="left" id="sl-gal-left" @checked($settings['layout']['gallery'] === 'left')>
                        <label class="form-check-label" for="sl-gal-left">{{ __('Gallery on the left') }}</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="layout[gallery]" value="right" id="sl-gal-right" @checked($settings['layout']['gallery'] === 'right')>
                        <label class="form-check-label" for="sl-gal-right">{{ __('Gallery on the right') }}</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="layout[sticky_info]" value="1" id="sl-sticky" @checked($settings['layout']['sticky_info'])>
                        <label class="form-check-label" for="sl-sticky">{{ __('Keep the buy box in view while scrolling') }}</label>
                    </div>
                </div>

                <div class="sl-panel">
                    <h6>{{ __('Theme') }}</h6>
                    <div class="d-grid gap-2">
                        @foreach($themePresets as $key => $preset)
                            <button type="button" class="btn btn-sm btn-outline-secondary text-start sl-theme-preset" data-preset="{{ $key }}" data-colours="{{ json_encode($preset['colours']) }}">
                                <span class="sl-swatches" aria-hidden="true">
                                    @foreach(['primary', 'accent', 'background', 'text'] as $colourKey)
                                        <i style="background: {{ $preset['colours'][$colourKey] }}"></i>
                                    @endforeach
                                </span>
                                {{ __($preset['label']) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="sl-panel">
                    <h6>{{ __('Customise colours') }}</h6>
                    @foreach(['primary', 'accent', 'badge_hover', 'background', 'text'] as $key)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="sl-colour-{{ $key }}" class="mb-0 small">{{ __($colourLabels[$key]) }}</label>
                            <input id="sl-colour-{{ $key }}" type="color" name="colours[{{ $key }}]" class="form-control form-control-color" value="{{ $settings['colours'][$key] }}">
                        </div>
                    @endforeach
                </div>

                <div class="sl-panel">
                    <h6>{{ __('Buttons') }}</h6>
                    <small class="text-muted d-block mb-2">{{ __('Each button has four colours: its text and background, and both again on hover.') }}</small>
                    @foreach(\App\Services\SingleLayout\SingleLayout::BUTTON_KEYS as $key)
                        <div class="mb-3">
                            <div class="small fw-semibold mb-1">{{ __(\App\Services\SingleLayout\SingleLayout::BUTTON_LABELS[$key]) }}</div>
                            <div class="sl-colour-grid">
                                @foreach(\App\Services\SingleLayout\SingleLayout::BUTTON_PARTS as $suffix => $partLabel)
                                    <label class="small">{{ __($partLabel) }}
                                        <input type="color" name="colours[{{ $key.$suffix }}]" value="{{ $settings['colours'][$key.$suffix] }}">
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <h6 class="mt-3">{{ __('Custom buttons') }}</h6>
                    <div id="sl-custom-list">
                        @foreach($customRows as $index => $button)
                            @include('backEnd.single-layout._custom-button', ['index' => $index, 'button' => $button])
                        @endforeach
                    </div>
                    <button type="button" id="sl-custom-add" class="btn btn-sm btn-outline-secondary w-100" @disabled(count($customRows) >= $customButtonMax)>{{ __('Add custom button') }}</button>
                </div>

                <div class="sl-panel">
                    <h6>{{ __('Shape') }}</h6>
                    @foreach(\App\Services\SingleLayout\SingleLayout::SHAPE_KEYS as $key)
                        <label for="sl-shape-{{ $key }}" class="form-label small mb-1">{{ __(\App\Services\SingleLayout\SingleLayout::SHAPE_LABELS[$key]) }}</label>
                        <select id="sl-shape-{{ $key }}" name="shapes[{{ $key }}]" class="form-select form-select-sm mb-2">
                            @foreach(\App\Services\SingleLayout\SingleLayout::SHAPES as $shape)
                                <option value="{{ $shape }}" @selected($settings['shapes'][$key] === $shape)>{{ __(\App\Services\SingleLayout\SingleLayout::SHAPE_NAMES[$shape]) }}</option>
                            @endforeach
                        </select>
                    @endforeach
                </div>

                <div class="sl-panel">
                    <h6>{{ __('Extra text') }}</h6>
                    <input type="text" name="extra_text" class="form-control form-control-sm @error('extra_text') is-invalid @enderror" value="{{ old('extra_text', $settings['extra_text']) }}" maxlength="{{ \App\Services\SingleLayout\SingleLayout::EXTRA_TEXT_MAX }}" placeholder="{{ __('Shown under the buy box. Leave empty to hide it.') }}">
                </div>

                <button type="submit" class="btn btn-primary w-100">{{ __('Save layout') }}</button>
            </form>

            <template id="sl-custom-template">
                @include('backEnd.single-layout._custom-button', ['index' => '__INDEX__', 'button' => null])
            </template>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var origin = window.location.origin;
    var frame = document.getElementById('slFrame');
    var form = document.getElementById('slForm');
    var sections = @json(\App\Services\SingleLayout\SingleLayout::SECTION_KEYS);
    var colours = @json(\App\Services\SingleLayout\SingleLayout::COLOUR_KEYS);
    var shapes = @json(\App\Services\SingleLayout\SingleLayout::SHAPE_KEYS);
    var customMax = {{ $customButtonMax }};
    var customList = document.getElementById('sl-custom-list');
    var customTemplate = document.getElementById('sl-custom-template');
    var customAdd = document.getElementById('sl-custom-add');
    var customNext = {{ $nextCustomIndex }};

    // The settings as the form shows them now, in the shape the product page reads.
    function collect() {
        var s = { colours: {}, shapes: {}, sections: {}, layout: {} };
        colours.forEach(function (k) { s.colours[k] = form.querySelector('[name="colours[' + k + ']"]').value; });
        shapes.forEach(function (k) { s.shapes[k] = form.querySelector('[name="shapes[' + k + ']"]').value; });
        sections.forEach(function (k) { s.sections[k] = form.querySelector('[name="sections[' + k + ']"]').checked; });
        s.layout.gallery = form.querySelector('input[name="layout[gallery]"]:checked').value;
        s.layout.sticky_info = form.querySelector('[name="layout[sticky_info]"]').checked;
        s.extra_text = form.querySelector('[name="extra_text"]').value;
        s.custom_buttons = Array.prototype.map.call(customList.querySelectorAll('[data-sl-custom-row]'), function (row) {
            var value = function (name) { return row.querySelector('[name$="[' + name + ']"]').value; };
            return {
                label: value('label').trim(),
                link: value('link').trim(),
                background: value('background'),
                hover_background: value('hover_background'),
                text: value('text'),
                hover_text: value('hover_text'),
            };
        }).filter(function (b) { return b.label !== ''; });
        return s;
    }

    function push() {
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ source: 'sl-parent', type: 'apply', settings: collect() }, origin);
        }
    }

    // Theme presets: choosing one sets every colour, and the preview follows.
    var themeButtons = document.querySelectorAll('.sl-theme-preset');
    function markActiveTheme() {
        themeButtons.forEach(function (button) {
            var values = JSON.parse(button.getAttribute('data-colours'));
            var same = colours.every(function (k) {
                return form.querySelector('[name="colours[' + k + ']"]').value.toLowerCase() === values[k].toLowerCase();
            });
            button.classList.toggle('active', same);
        });
    }
    themeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var values = JSON.parse(button.getAttribute('data-colours'));
            colours.forEach(function (k) { form.querySelector('[name="colours[' + k + ']"]').value = values[k]; });
            markActiveTheme();
            push();
        });
    });

    // Custom buttons: up to the maximum, each with its own text, link and colours.
    function syncCustomAdd() {
        customAdd.disabled = customList.querySelectorAll('[data-sl-custom-row]').length >= customMax;
    }
    customAdd.addEventListener('click', function () {
        customList.insertAdjacentHTML('beforeend', customTemplate.innerHTML.replace(/__INDEX__/g, customNext++));
        syncCustomAdd();
        push();
    });
    customList.addEventListener('click', function (e) {
        var remove = e.target.closest('[data-sl-remove-custom]');
        if (remove) {
            remove.closest('[data-sl-custom-row]').remove();
            syncCustomAdd();
            push();
        }
    });
    syncCustomAdd();

    // The product page tells us when it is ready; until then the form's own values are shown after each change.
    window.addEventListener('message', function (e) {
        if (e.origin === origin && e.data && e.data.source === 'sl-frame' && e.data.type === 'ready') push();
    });
    form.addEventListener('input', markActiveTheme);
    form.addEventListener('input', push);
    form.addEventListener('change', push);
    markActiveTheme();
})();
</script>
@endsection
