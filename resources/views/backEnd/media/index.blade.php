@extends('backEnd.layouts.master')
@section('title', 'Media Gallery')
@section('css')
<style>
.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:14px}.media-card{position:relative;border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;min-height:150px}.media-card:hover{border-color:#4f46e5;box-shadow:0 5px 16px #1111}.media-thumb{height:110px;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden}.media-thumb img{width:100%;height:100%;object-fit:cover}.media-meta{padding:8px;font-size:12px}.media-name{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600}.media-actions{position:absolute;right:5px;top:5px;display:flex;gap:2px}.media-actions button{border:0;background:#fff;border-radius:5px;width:26px;height:26px}.media-check{position:absolute;left:7px;top:7px;z-index:2}.folder-card{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;color:#d99a00}.dropzone{border:2px dashed #cbd5e1;border-radius:9px;padding:25px;text-align:center;cursor:pointer}.selection{display:none}.selection.show{display:flex}
</style>
@endsection
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="mb-0"><i class="fas fa-photo-video me-2"></i>{{ __('Media Manager') }}</h4>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal"><i class="fas fa-upload me-1"></i>Upload</button>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#folderModal"><i class="fas fa-folder-plus me-1"></i>New Folder</button>
            <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#moveModal" onclick="prepareTransfer('move')">Move</button>
            <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#moveModal" onclick="prepareTransfer('copy')">Copy</button>
        </div>
    </div>
    <div class="bg-light border rounded p-2 mb-3">
        <a href="{{ route('admin.media.index') }}">Media Library</a>
        @foreach($breadcrumbs as $crumb) / @if($loop->last)<strong>{{ $crumb['name'] }}</strong>@else<a href="{{ route('admin.media.index',['path'=>$crumb['path']]) }}">{{ $crumb['name'] }}</a>@endif @endforeach
    </div>
    <div id="selection" class="selection alert alert-info align-items-center py-2"><strong id="selectionCount">0</strong>&nbsp; selected <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="clearSelection()">Clear</button></div>
    <div class="media-grid">
        @foreach($folders as $folder)
        <div class="media-card folder-card" onclick="location.href='{{ route('admin.media.index',['path'=>$folder['path']]) }}'">
            <input class="media-check" type="checkbox" value="{{ $folder['path'] }}" data-kind="folder" onclick="event.stopPropagation();toggleSelection(this)">
            <div class="media-actions" onclick="event.stopPropagation()"><button title="Rename" onclick="renameItem('folder','{{ $folder['path'] }}','{{ $folder['name'] }}')"><i class="fas fa-edit"></i></button><button title="Delete" onclick="deleteItem('folder','{{ $folder['path'] }}')"><i class="fas fa-trash"></i></button></div>
            <i class="fas fa-folder"></i><div class="media-name text-dark">{{ $folder['name'] }}</div><small class="text-muted">{{ $folder['count'] }} item(s)</small>
        </div>
        @endforeach
        @foreach($files as $file)
        <div class="media-card">
            <input class="media-check" type="checkbox" value="{{ $file['path'] }}" data-kind="file" onclick="toggleSelection(this)">
            <div class="media-actions"><button title="Rename" onclick="renameItem('file','{{ $file['path'] }}','{{ $file['name'] }}')"><i class="fas fa-edit"></i></button><button title="Copy URL" onclick="copyUrl('{{ $file['url'] }}')"><i class="fas fa-link"></i></button><button title="Delete" onclick="deleteItem('file','{{ $file['path'] }}')"><i class="fas fa-trash"></i></button></div>
            <div class="media-thumb">@if($file['is_image'])<img src="{{ $file['url'] }}" loading="lazy" alt="{{ $file['name'] }}">@else<i class="fas fa-file-pdf text-danger fa-3x"></i>@endif</div>
            <div class="media-meta"><div class="media-name" title="{{ $file['name'] }}">{{ $file['name'] }}</div><small class="text-muted">{{ $file['size'] }} · {{ $file['ext'] }}</small></div>
        </div>
        @endforeach
    </div>
    @if(!$folders && !$files)<div class="text-center text-muted py-5"><i class="fas fa-folder-open fa-3x mb-3"></i><p>This folder is empty.</p></div>@endif
</div>
<div class="modal fade" id="folderModal"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('admin.media.folder.create') }}">@csrf<input type="hidden" name="path" value="{{ $path }}"><div class="modal-header"><h5>New Folder</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input class="form-control" name="folder_name" required maxlength="100" placeholder="Folder name"></div><div class="modal-footer"><button class="btn btn-primary">Create</button></div></form></div></div>
<div class="modal fade" id="uploadModal"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('admin.media.upload') }}" enctype="multipart/form-data">@csrf<input type="hidden" name="path" value="{{ $path }}"><div class="modal-header"><h5>Upload Media</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="dropzone" onclick="document.getElementById('mediaFiles').click()"><i class="fas fa-cloud-upload-alt fa-2x"></i><br>Choose images or PDF files</div><input id="mediaFiles" class="form-control mt-3" type="file" name="files[]" multiple accept="image/*,.pdf" required></div><div class="modal-footer"><button class="btn btn-primary">Upload</button></div></form></div></div>
<div class="modal fade" id="moveModal"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('admin.media.move') }}" id="transferForm">@csrf<input type="hidden" name="action" id="transferAction"><div id="selectedInputs"></div><div class="modal-header"><h5 id="transferTitle">Move / Copy</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label>Destination</label><input class="form-control" name="target" placeholder="Folder path, blank for root"><small class="text-muted">Current path: {{ $path ?: 'root' }}</small></div><div class="modal-footer"><button class="btn btn-primary">Continue</button></div></form></div></div>
@endsection
@section('script')
<script>
function selected(){return [...document.querySelectorAll('.media-check:checked')]}function toggleSelection(el){el.closest('.media-card').classList.toggle('border-primary',el.checked);document.getElementById('selectionCount').textContent=selected().length;document.getElementById('selection').classList.toggle('show',selected().length>0)}function clearSelection(){document.querySelectorAll('.media-check').forEach(x=>x.checked=false);document.getElementById('selection').classList.remove('show')}function prepareTransfer(action){document.getElementById('transferAction').value=action;document.getElementById('transferTitle').textContent=action==='copy'?'Copy Items':'Move Items';document.getElementById('selectedInputs').innerHTML=selected().map(x=>'<input type="hidden" name="items[]" value="'+x.value.replaceAll('"','&quot;')+'">').join('')}function renameItem(kind,path,current){let name=prompt('New name',current);if(!name)return;let form=document.createElement('form');form.method='POST';form.action=kind==='folder'?'{{ route('admin.media.folder.rename') }}':'{{ route('admin.media.file.rename') }}';form.innerHTML='@csrf<input name="path" value="'+path+'"><input name="new_name" value="'+name+'"><input name="kind" value="'+kind+'">';document.body.appendChild(form);form.submit()}function deleteItem(kind,path){if(!confirm('Delete this '+kind+'?'))return;let form=document.createElement('form');form.method='POST';form.action=kind==='folder'?'{{ route('admin.media.folder.delete') }}':'{{ route('admin.media.file.delete') }}';form.innerHTML='@csrf<input name="path" value="'+path+'">';document.body.appendChild(form);form.submit()}function copyUrl(url){navigator.clipboard?.writeText(url);alert('URL copied')} 
</script>
@endsection
