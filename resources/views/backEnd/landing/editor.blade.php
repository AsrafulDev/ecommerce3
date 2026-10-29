@extends('backEnd.layouts.master')
@section('title', 'Edit landing page')

@section('css')
<style>
    .lp-editor-frame { width: 100%; height: calc(100vh - 200px); min-height: 640px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    .lp-panel { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(15, 23, 42, .05); padding: 14px 16px; margin-bottom: 14px; }
    .lp-panel h6 { font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #64748b; margin-bottom: 10px; }
    .lp-panel .form-check { margin-bottom: 6px; }
</style>
@endsection

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">{{ $landing->name }}</h4>
            <small class="text-muted">{{ \App\Services\Landing\TemplateRegistry::label($landing->template_key) }} · {{ $landing->product?->name }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="lpStatus" class="badge bg-success">{{ __('Saved') }}</span>
            <a href="{{ $liveUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary">{{ __('Open live page') }}</a>
            <a href="{{ route('landing.manage.index') }}" class="btn btn-sm btn-light">{{ __('Back to list') }}</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-9">
            <div class="btn-group btn-group-sm mb-2" role="group" aria-label="{{ __('View') }}">
                <button type="button" id="lpModeEdit" class="btn btn-primary">{{ __('Edit') }}</button>
                <button type="button" id="lpModePreview" class="btn btn-outline-primary">{{ __('Preview (read-only)') }}</button>
            </div>
            <iframe id="lpFrame" class="lp-editor-frame" src="{{ $frameUrl }}" title="{{ __('Landing page') }}"></iframe>
        </div>

        <div class="col-lg-3">
            <div class="lp-panel">
                <h6>{{ __('Sections') }}</h6>
                @foreach($sections as $key => $def)
                    <div class="form-check form-switch">
                        <input class="form-check-input lp-section-switch" type="checkbox" id="sec-{{ $key }}" data-key="{{ $key }}"
                            @checked($enabled[$key] ?? true) @disabled(! empty($def['locked']))>
                        <label class="form-check-label" for="sec-{{ $key }}">
                            {{ ucfirst($key) }}
                            @if(! empty($def['locked']))<span class="text-muted small">({{ __('always on') }})</span>@endif
                        </label>
                    </div>
                @endforeach
                <small class="text-muted d-block mt-2">{{ __('At least one section besides checkout must stay on.') }}</small>
            </div>

            <div class="lp-panel">
                <h6>{{ __('Colours') }}</h6>
                @foreach(['primary' => 'Primary', 'accent' => 'Accent', 'background' => 'Background', 'text' => 'Text'] as $key => $label)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="colour-{{ $key }}" class="mb-0">{{ __($label) }}</label>
                        <input type="color" id="colour-{{ $key }}" class="lp-colour form-control form-control-color" data-key="{{ $key }}" value="{{ $theme[$key] }}">
                    </div>
                @endforeach
            </div>

            <form id="lpSettings" class="lp-panel" method="POST" action="{{ $settingsUrl }}">
                @csrf
                <h6>{{ __('Publishing') }}</h6>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="status" id="lp-status" value="1" @checked($landing->status)>
                    <label class="form-check-label" for="lp-status">{{ __('Live (visible to customers)') }}</label>
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1" for="lp-valid-from">{{ __('Valid from') }}</label>
                    <input type="datetime-local" class="form-control form-control-sm" name="valid_from" id="lp-valid-from"
                        value="{{ $landing->valid_from?->format('Y-m-d\TH:i') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label small mb-1" for="lp-valid-until">{{ __('Valid until') }}</label>
                    <input type="datetime-local" class="form-control form-control-sm" name="valid_until" id="lp-valid-until"
                        value="{{ $landing->valid_until?->format('Y-m-d\TH:i') }}">
                </div>

                <h6>{{ __('Extra discount') }}</h6>
                <div class="row g-2 mb-3">
                    <div class="col-5">
                        <select class="form-select form-select-sm" name="extra_discount_type" aria-label="{{ __('Discount type') }}">
                            <option value="">{{ __('None') }}</option>
                            <option value="percent" @selected($landing->extra_discount_type === 'percent')>%</option>
                            <option value="fixed" @selected($landing->extra_discount_type === 'fixed')>৳</option>
                        </select>
                    </div>
                    <div class="col-7">
                        <input type="number" min="0" step="0.01" class="form-control form-control-sm" name="extra_discount_value"
                            value="{{ $landing->extra_discount_value }}" aria-label="{{ __('Discount amount') }}">
                    </div>
                </div>

                <h6>{{ __('Coupon') }}</h6>
                <div class="row g-2 mb-1">
                    <div class="col-5">
                        <select class="form-select form-select-sm" name="coupon_type" aria-label="{{ __('Coupon type') }}">
                            <option value="">{{ __('None') }}</option>
                            <option value="percent" @selected(optional($landing->coupon)->type === 'percent')>%</option>
                            <option value="fixed" @selected(optional($landing->coupon)->type === 'fixed')>৳</option>
                        </select>
                    </div>
                    <div class="col-7">
                        <input type="number" min="0" step="0.01" class="form-control form-control-sm" name="coupon_value"
                            value="{{ optional($landing->coupon)->value }}" aria-label="{{ __('Coupon amount') }}">
                    </div>
                </div>
                <small class="text-muted d-block mb-3">
                    {{ $landing->coupon ? __('Code:') . ' ' . $landing->coupon->code : __('A code is created when you save a coupon.') }}
                </small>

                <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Save settings') }}</button>
            </form>
        </div>
    </div>
</div>

@include('backEnd.media._picker')
@endsection

@section('script')
<script>
(function () {
    var origin = window.location.origin;
    var csrf = @json(csrf_token());
    var saveUrl = @json($saveUrl);
    var settingsUrl = @json($settingsUrl);
    var frame = document.getElementById('lpFrame');
    var statusEl = document.getElementById('lpStatus');
    var pending = { elements: {}, sections: {}, theme: {} };
    var timer = null;

    function hasPending() {
        return Object.keys(pending.elements).length + Object.keys(pending.sections).length + Object.keys(pending.theme).length > 0;
    }

    function setStatus(text, cls) {
        statusEl.textContent = text;
        statusEl.className = 'badge ' + cls;
    }

    function sendToFrame(message) {
        message.source = 'lp-parent';
        frame.contentWindow.postMessage(message, origin);
    }

    function flush() {
        if (!hasPending()) return;
        var body = pending;
        pending = { elements: {}, sections: {}, theme: {} };
        setStatus(@json(__('Saving…')), 'bg-secondary');

        fetch(saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body)
        }).then(function (r) {
            return r.json().then(function (json) { return { ok: r.ok, json: json }; });
        }).then(function (res) {
            if (res.ok) {
                setStatus(@json(__('Saved')), 'bg-success');
            } else {
                setStatus(@json(__('Not saved')), 'bg-danger');
                (res.json.errors || []).forEach(function (msg) { toastr.error(msg); });
                frame.src = frame.src; // show the saved version again
            }
        }).catch(function () {
            setStatus(@json(__('Not saved')), 'bg-danger');
        });
    }

    function queue() {
        setStatus(@json(__('Unsaved changes')), 'bg-warning text-dark');
        clearTimeout(timer);
        timer = setTimeout(flush, 700);
    }

    window.addEventListener('message', function (e) {
        if (e.origin !== origin || !e.data || e.data.source !== 'lp-frame') return;
        var m = e.data;

        if (m.type === 'change') {
            pending.elements[m.id] = { value: m.value };
            queue();
        } else if (m.type === 'image-request') {
            window.openMediaPickerFor(function (item) {
                pending.elements[m.id] = { value: item.rel };
                sendToFrame({ type: 'image', id: m.id, url: item.url });
                queue();
            }, 'path');
        } else if (m.type === 'full') {
            toastr.info(@json(__('Every item slot is in use.')));
        }
    });

    document.querySelectorAll('.lp-section-switch').forEach(function (sw) {
        sw.addEventListener('change', function () {
            pending.sections[sw.dataset.key] = { enabled: sw.checked };
            sendToFrame({ type: 'section', key: sw.dataset.key, enabled: sw.checked });
            queue();
        });
    });

    document.querySelectorAll('.lp-colour').forEach(function (input) {
        input.addEventListener('input', function () {
            pending.theme[input.dataset.key] = input.value;
            sendToFrame({ type: 'theme', key: input.dataset.key, value: input.value });
            queue();
        });
    });

    document.getElementById('lpSettings').addEventListener('submit', function (e) {
        e.preventDefault();
        var form = e.target;
        var data = {};
        new FormData(form).forEach(function (value, key) { data[key] = value; });
        data.status = form.querySelector('[name=status]').checked;
        fetch(settingsUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(data)
        }).then(function (r) {
            return r.json().then(function (json) { return { ok: r.ok, json: json }; });
        }).then(function (res) {
            if (res.ok) {
                toastr.success(@json(__('Settings saved.')));
            } else {
                (res.json.errors || []).forEach(function (msg) { toastr.error(msg); });
            }
        });
    });

    document.getElementById('lpModeEdit').addEventListener('click', function () {
        frame.src = @json($frameUrl);
    });
    document.getElementById('lpModePreview').addEventListener('click', function () {
        frame.src = @json($previewUrl);
    });

    window.addEventListener('beforeunload', function (e) {
        if (hasPending()) { e.preventDefault(); e.returnValue = ''; }
    });
})();
</script>
@endsection
