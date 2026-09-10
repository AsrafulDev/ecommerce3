@forelse($files as $file)
<button type="button" class="media-picker-item border rounded bg-white p-1 text-start" data-path="{{ $file['path'] }}" data-url="{{ $file['url'] }}" data-name="{{ $file['name'] }}" style="width:120px;">
    <div style="height:80px;display:flex;align-items:center;justify-content:center;background:#f8fafc;overflow:hidden;">
        @if($file['is_image'])<img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" style="width:100%;height:100%;object-fit:cover;">@else<i class="fas fa-file-pdf fa-2x text-danger"></i>@endif
    </div>
    <small class="d-block text-truncate mt-1">{{ $file['name'] }}</small>
</button>
@empty
<div class="text-muted p-3">This folder is empty.</div>
@endforelse
