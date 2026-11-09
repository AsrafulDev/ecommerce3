<div class="sl-custom-row border rounded p-2 mb-2" data-sl-custom-row>
    <div class="d-flex justify-content-between align-items-center mb-1">
        <small class="text-muted">{{ __('Custom button') }}</small>
        <button type="button" class="btn btn-sm btn-link text-danger p-0" data-sl-remove-custom>{{ __('Remove') }}</button>
    </div>
    <input type="text" class="form-control form-control-sm mb-1" name="custom_buttons[{{ $index }}][label]" value="{{ $button['label'] ?? '' }}" maxlength="40" placeholder="{{ __('Button text') }}">
    <input type="text" class="form-control form-control-sm mb-2" name="custom_buttons[{{ $index }}][link]" value="{{ $button['link'] ?? '' }}" maxlength="255" placeholder="{{ __('https://… or tel:… or mailto:…') }}">
    <div class="sl-colour-grid">
        <label class="small">{{ __('Text') }} <input type="color" name="custom_buttons[{{ $index }}][text]" value="{{ $button['text'] ?? '#ffffff' }}"></label>
        <label class="small">{{ __('Background') }} <input type="color" name="custom_buttons[{{ $index }}][background]" value="{{ $button['background'] ?? '#374151' }}"></label>
        <label class="small">{{ __('Hover text') }} <input type="color" name="custom_buttons[{{ $index }}][hover_text]" value="{{ $button['hover_text'] ?? '#ffffff' }}"></label>
        <label class="small">{{ __('Hover background') }} <input type="color" name="custom_buttons[{{ $index }}][hover_background]" value="{{ $button['hover_background'] ?? '#1f2937' }}"></label>
    </div>
</div>
