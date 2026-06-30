<div class="section-row sortable-item" data-ls-id="{{ $ls->id }}" data-section-id="{{ $ls->section_id }}" data-section-slug="{{ $ls->section->slug ?? '' }}">
    <div class="section-row-header">
        <div class="drag-handle"><i class="fe-menu"></i></div>
        <span class="section-title">
            <i class="mdi mdi-{{ $ls->section->icon ?? 'view-module' }} text-primary me-1"></i>
            {{ $ls->section->name ?? $ls->section->slug ?? 'Untitled' }}
        </span>
        <span class="badge {{ $ls->is_visible ? 'bg-success' : 'bg-secondary' }} rounded-pill visibility-badge" 
              style="font-size:9px;font-weight:700;letter-spacing:0.5px;">
            <i class="fe-{{ $ls->is_visible ? 'eye' : 'eye-off' }} me-1"></i> 
            {{ $ls->is_visible ? 'Visible' : 'Hidden' }}
        </span>
        <span class="badge bg-light rounded-pill" style="font-size:9px;color:#475569;">
            #{{ $ls->sort_order }}
        </span>
        <button type="button" class="btn-ghost toggle-visibility" title="Toggle visibility">
            <i class="fe-{{ $ls->is_visible ? 'eye' : 'eye-off' }}"></i>
        </button>
        <button type="button" class="btn-ghost toggle-settings" title="{{ __('Settings') }}">
            <i class="fe-settings"></i>
        </button>
        <button type="button" class="btn-ghost capture-section-btn" title="Capture screenshot" style="color:#8b5cf6;">
            <i class="mdi mdi-camera"></i>
        </button>
        <button type="button" class="btn-ghost remove-section" title="{{ __('Remove') }}" style="color:#ef4444;">
            <i class="fe-trash-2"></i>
        </button>
    </div>

    {{-- Section screenshot preview --}}
    <div class="section-row-body">
        <div class="section-screenshot" id="screenshot-{{ $ls->id }}">
            @if($ls->section->preview_image)
                <img src="{{ asset($ls->section->preview_image) }}" alt="{{ $ls->section->name }}">
            @else
                <span><i class="mdi mdi-camera me-1"></i> {{ __('Screenshot placeholder') }} </span>
            @endif
        </div>

        {{-- Settings panel --}}
        <div class="settings-panel" id="settings-{{ $ls->id }}">
            <form action="{{ route('layouts.sections.update-settings') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $ls->id }}">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label-pro" style="font-size:10px;"> {{ __('Columns') }} </label>
                        <select name="columns_config" class="form-select form-select-sm">
                            <option value="col-sm-12" {{ $ls->columns_config == 'col-sm-12' ? 'selected' : '' }}> {{ __('Full Width (1 col)') }} </option>
                            <option value="col-sm-6" {{ $ls->columns_config == 'col-sm-6' ? 'selected' : '' }}> {{ __('Half (2 cols)') }} </option>
                            <option value="col-sm-4" {{ $ls->columns_config == 'col-sm-4' ? 'selected' : '' }}> {{ __('One Third (3 cols)') }} </option>
                            <option value="col-sm-3" {{ $ls->columns_config == 'col-sm-3' ? 'selected' : '' }}> {{ __('One Quarter (4 cols)') }} </option>
                            <option value="col-sm-8" {{ $ls->columns_config == 'col-sm-8' ? 'selected' : '' }}> {{ __('Two Thirds') }} </option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label-pro" style="font-size:10px;"> {{ __('Responsive Visibility') }} </label>
                        <div class="d-flex gap-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="visible_desktop" value="1" 
                                    {{ ($ls->breakpoints['desktop'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label small"> {{ __('Desktop') }} </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="visible_tablet" value="1"
                                    {{ ($ls->breakpoints['tablet'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label small"> {{ __('Tablet') }} </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="visible_mobile" value="1"
                                    {{ ($ls->breakpoints['mobile'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label small"> {{ __('Mobile') }} </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4">
                            <i class="fe-check me-1"></i> {{ __('Save Settings') }} </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
