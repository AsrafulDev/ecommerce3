<input type="hidden" name="{{ $field }}_url" id="{{ $field }}_url" value="{{ $current ?? '' }}">
<div class="d-flex align-items-center gap-2 mt-1">
	<button type="button" class="btn btn-sm btn-outline-primary media-picker-open"
			onclick="openMediaPicker('#{{ $field }}_url', {{ isset($preview) && $preview ? "'#{$preview}'" : 'null' }})">
		<i class="fas fa-photo-video me-1"></i>{{ $label ?? __('Choose from Media Library') }}
	</button>
	<small class="text-muted text-truncate" id="{{ $field }}_url_file" style="max-width:220px;"></small>
</div>
