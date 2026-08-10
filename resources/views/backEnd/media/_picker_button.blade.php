{{--
    REUSABLE "Media Library" picker button + hidden value field.
    ------------------------------------------------------------
    Include AFTER the corresponding upload input, and include
    @include('backEnd.media._picker') ONCE on the page.

    Usage:
      @include('backEnd.media._picker_button', [
          'field'   => 'image_one',            // required: base field name -> hidden input name="image_one_url"
          'preview' => 'preview_image_one',    // optional: id (without #) of an <img> to update on selection
          'label'   => 'Choose from Media Library',
          'current' => old('image_one_url') ?? '', // optional prefill
      ])
--}}
<input type="hidden" name="{{ $field }}_url" id="{{ $field }}_url" value="{{ $current ?? '' }}">
<div class="d-flex align-items-center gap-2 mt-1">
    <button type="button" class="btn btn-sm btn-outline-primary"
            onclick="openMediaPicker('#{{ $field }}_url', {{ isset($preview) && $preview ? "'#{$preview}'" : 'null' }}, 'path')">
        <i class="fe-image"></i> {{ $label ?? 'Media Library' }}
    </button>
    <small class="text-muted text-truncate" id="{{ $field }}_url_file" style="max-width:220px;"></small>
</div>
