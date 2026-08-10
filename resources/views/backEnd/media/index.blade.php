@extends('backEnd.layouts.master')
@section('title', 'Media Gallery')

@section('css')
<style>
    .media-page .page-title { font-weight: 700; color: #333; }
    .media-toolbar .btn { border-radius: 8px; }
    .media-path-box {
        background: #f8f9fa;
        border: 1px solid #eef2f7;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        color: #6c757d;
    }
    .media-breadcrumb a { color: var(--admin-primary, #4e73df); text-decoration: none; }
    .media-breadcrumb a:hover { text-decoration: underline; }

    /* Grid */
    .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px; }

    /* Folder card */
    .media-folder {
        background: #fff;
        border: 1px solid #eef2f7;
        border-radius: 12px;
        padding: 16px 10px;
        text-align: center;
        cursor: pointer;
        position: relative;
        transition: all .2s ease;
        box-shadow: 0 2px 6px rgba(0,0,0,.04);
    }
    .media-folder:hover { border-color: var(--admin-primary, #4e73df); box-shadow: 0 6px 16px rgba(0,0,0,.08); transform: translateY(-2px); }
    .media-folder .folder-icon { color: #ffc107; font-size: 44px; line-height: 1; }
    .media-folder .folder-name {
        font-size: 13px; font-weight: 600; color: #333; margin-top: 8px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .media-folder .folder-count { font-size: 11px; color: #98a6ad; }

    /* File card */
    .media-file {
        background: #fff;
        border: 1px solid #eef2f7;
        border-radius: 12px;
        overflow: hidden;
        position: relative;
        transition: all .2s ease;
        box-shadow: 0 2px 6px rgba(0,0,0,.04);
    }
    .media-file:hover { border-color: var(--admin-primary, #4e73df); box-shadow: 0 6px 16px rgba(0,0,0,.08); transform: translateY(-2px); }
    .media-file .file-thumb {
        height: 110px; display: flex; align-items: center; justify-content: center;
        background: #f6f8fb; overflow: hidden;
    }
    .media-file .file-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .media-file .file-thumb .pdf-badge {
        display: flex; flex-direction: column; align-items: center; color: #dc3545;
    }
    .media-file .file-thumb .pdf-badge svg { width: 40px; height: 40px; }
    .media-file .file-info { padding: 8px 10px; border-top: 1px solid #f1f3f7; }
    .media-file .file-name {
        font-size: 12px; font-weight: 600; color: #333;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .media-file .file-meta { font-size: 10px; color: #98a6ad; display: flex; justify-content: space-between; }

    /* Item checkbox */
    .media-check {
        position: absolute; top: 8px; left: 8px; z-index: 5;
        width: 18px; height: 18px; cursor: pointer; accent-color: var(--admin-primary, #4e73df);
    }

    /* Hover actions */
    .media-actions {
        position: absolute; top: 6px; right: 6px; z-index: 6;
        display: none; gap: 2px; background: rgba(255,255,255,.92);
        border-radius: 8px; padding: 2px; box-shadow: 0 2px 8px rgba(0,0,0,.12);
    }
    .media-folder:hover .media-actions, .media-file:hover .media-actions { display: inline-flex; }
    .media-actions .ma-btn {
        width: 26px; height: 26px; border: none; background: transparent; border-radius: 6px;
        display: inline-flex; align-items: center; justify-content: center; color: #6c757d;
    }
    .media-actions .ma-btn:hover { background: #eef2f7; }
    .media-actions .ma-btn.danger:hover { color: #dc3545; background: #fdecec; }
    .media-actions .ma-btn svg { width: 14px; height: 14px; }

    /* Selected state */
    .media-file.selected, .media-folder.selected {
        border-color: var(--admin-primary, #4e73df);
        box-shadow: 0 0 0 2px rgba(78,115,223,.25);
    }

    .media-selection-bar {
        display: none; align-items: center; gap: 10px;
        background: #eef4ff; border: 1px solid #d3e0ff; border-radius: 10px;
        padding: 10px 14px; margin-bottom: 14px;
    }
    .media-selection-bar.show { display: flex; }

    .media-empty {
        text-align: center; padding: 60px 20px; color: #98a6ad;
    }
    .media-empty svg { width: 60px; height: 60px; color: #ccd6e0; }
    .media-dropzone {
        border: 2px dashed #c3cdd9; border-radius: 12px; padding: 24px;
        text-align: center; color: #98a6ad; cursor: pointer; transition: all .2s;
    }
    .media-dropzone:hover, .media-dropzone.dragover { border-color: var(--admin-primary, #4e73df); background: #f4f7ff; }
    .media-file-upload-list { margin-top: 12px; }
    .media-file-upload-list .up-item {
        display: flex; justify-content: space-between; align-items: center;
        background: #f8f9fa; border-radius: 6px; padding: 6px 10px; margin-bottom: 6px; font-size: 13px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid media-page">

    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0"><i data-feather="folder" class="me-1"></i> {{ __('Media Gallery') }}</h4>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fe-upload me-1"></i>{{ __('Upload') }}
                </button>
                <button type="button" class="btn btn-warning rounded-pill px-3 shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#newFolderModal">
                    <i class="fe-folder-plus me-1"></i>{{ __('New Folder') }}
                </button>
                <button type="button" class="btn btn-secondary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#moveCopyModal" onclick="prepareMoveCopy('move')">
                    <i class="fe-move me-1"></i>{{ __('Move') }}
                </button>
                <button type="button" class="btn btn-info rounded-pill px-3 shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#moveCopyModal" onclick="prepareMoveCopy('copy')">
                    <i class="fe-copy me-1"></i>{{ __('Copy') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Path / breadcrumb --}}
    <div class="media-path-box mb-3 d-flex align-items-center flex-wrap gap-2">
        <i data-feather="map-pin" class="me-1" style="width:15px;height:15px;"></i>
        <span class="media-breadcrumb">
            <a href="{{ route('admin.media.index') }}"><strong>{{ __('Media Library') }}</strong></a>
            @foreach($breadcrumbs as $crumb)
                <span class="mx-1">/</span>
                @if(!$loop->last)
                    <a href="{{ route('admin.media.index', ['path' => $crumb['path']]) }}">{{ $crumb['name'] }}</a>
                @else
                    <strong>{{ $crumb['name'] }}</strong>
                @endif
            @endforeach
        </span>
        <span class="ms-auto text-muted">{{ $folders ? count($folders).' folder(s)' : '' }} {{ $files ? count($files).' file(s)' : '' }}</span>
    </div>

    {{-- Selection bar --}}
    <div class="media-selection-bar" id="mediaSelectionBar">
        <i data-feather="check-square" style="width:16px;height:16px;color:var(--admin-primary,#4e73df);"></i>
        <strong id="selCount" class="me-1">0</strong> {{ __('selected') }}
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()"><i class="fe-x me-1"></i>{{ __('Clear') }}</button>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#moveCopyModal" onclick="prepareMoveCopy('move')"><i class="fe-move me-1"></i>{{ __('Move') }}</button>
            <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#moveCopyModal" onclick="prepareMoveCopy('copy')"><i class="fe-copy me-1"></i>{{ __('Copy') }}</button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-body">
                    @if(empty($folders) && empty($files))
                        <div class="media-empty">
                            <i data-feather="folder"></i>
                            <h5 class="mt-2 text-muted">{{ __('This folder is empty') }}</h5>
                            <p>{{ __('Upload files or create a new folder.') }}</p>
                        </div>
                    @else
                        <div class="media-grid">

                            @foreach($folders as $folder)
                            <div class="media-folder" onclick="window.location='{{ route('admin.media.index', ['path' => $folder['path']]) }}'" title="{{ $folder['name'] }}">
                                <input type="checkbox" class="media-check" value="{{ $folder['path'] }}" data-type="folder" data-name="{{ $folder['name'] }}"
                                       onclick="event.stopPropagation(); toggleSelection(this)">
                                <div class="media-actions" onclick="event.stopPropagation()">
                                    <button type="button" class="ma-btn" title="Rename" onclick="openRenameModal('folder','{{ $folder['path'] }}','{{ $folder['name'] }}')"><i data-feather="edit-2"></i></button>
                                    <button type="button" class="ma-btn danger" title="Delete" onclick="confirmDelete('folder','{{ $folder['path'] }}','{{ $folder['name'] }}')"><i data-feather="trash-2"></i></button>
                                </div>
                                <div class="folder-icon"><i data-feather="folder"></i></div>
                                <div class="folder-name">{{ $folder['name'] }}</div>
                                <div class="folder-count">{{ $folder['count'] }} {{ __('item(s)') }}</div>
                            </div>
                            @endforeach

                            @foreach($files as $file)
                            <div class="media-file" title="{{ $file['name'] }}">
                                <input type="checkbox" class="media-check" value="{{ $file['path'] }}" data-type="file" data-name="{{ $file['name'] }}"
                                       onclick="toggleSelection(this)">
                                <div class="media-actions">
                                    <button type="button" class="ma-btn" title="Rename" onclick="openRenameModal('file','{{ $file['path'] }}','{{ $file['name'] }}')"><i data-feather="edit-2"></i></button>
                                    <button type="button" class="ma-btn" title="Copy URL" onclick="copyUrl('{{ $file['url'] }}')"><i data-feather="link-2"></i></button>
                                    <button type="button" class="ma-btn" title="View" onclick="viewFile('{{ $file['url'] }}','{{ $file['is_image'] ? 'image' : 'pdf' }}')"><i data-feather="eye"></i></button>
                                    <button type="button" class="ma-btn danger" title="Delete" onclick="confirmDelete('file','{{ $file['path'] }}','{{ $file['name'] }}')"><i data-feather="trash-2"></i></button>
                                </div>
                                <div class="file-thumb">
                                    @if($file['is_image'])
                                        <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" loading="lazy">
                                    @else
                                        <div class="pdf-badge">
                                            <i data-feather="file-text"></i>
                                            <span style="font-size:11px;font-weight:700;color:#dc3545;">PDF</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="file-info">
                                    <div class="file-name">{{ $file['name'] }}</div>
                                    <div class="file-meta">
                                        <span>{{ $file['size'] }}</span>
                                        <span>{{ $file['ext'] }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ MODALS ============ --}}

{{-- New Folder --}}
<div class="modal fade" id="newFolderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.media.folder.create') }}">
                @csrf
                <input type="hidden" name="path" value="{{ $path }}">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fe-folder-plus me-1"></i>{{ __('Create New Folder') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Folder Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="folder_name" class="form-control" required maxlength="100" placeholder="e.g. Products">
                    </div>
                    <div class="text-muted small">
                        {{ __('Will be created inside:') }} <code>{{ $path ?: '(Media Library root)' }}</code>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-warning text-white">{{ __('Create') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Upload --}}
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.media.upload') }}" enctype="multipart/form-data" id="uploadForm">
                @csrf
                <input type="hidden" name="path" value="{{ $path }}">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fe-upload me-1"></i>{{ __('Upload Files') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="media-dropzone" id="uploadDropzone" onclick="$('#uploadInput').trigger('click')">
                        <i data-feather="upload-cloud" style="width:38px;height:38px;"></i>
                        <div class="mt-2 fw-semibold">{{ __('Click or drag & drop files here') }}</div>
                        <div class="small">{{ __('Only images (JPG, PNG, GIF, WEBP, SVG, BMP) and PDF are allowed') }}</div>
                    </div>
                    <input type="file" name="files[]" id="uploadInput" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.bmp,.avif,.pdf,image/*,application/pdf" class="d-none">
                    <div class="media-file-upload-list" id="uploadFileList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Upload') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Rename (generic for folder & file) --}}
<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.media.file.rename') }}" id="renameForm">
                @csrf
                <input type="hidden" name="path" id="renamePath">
                <input type="hidden" name="kind" id="renameKind">
                <div class="modal-header">
                    <h5 class="modal-title" id="renameTitle">{{ __('Rename') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">{{ __('New Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="new_name" id="renameInput" class="form-control" required maxlength="150">
                    <div class="text-muted small mt-2" id="renameHint"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Rename') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Move / Copy --}}
<div class="modal fade" id="moveCopyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.media.move') }}" id="moveCopyForm">
                @csrf
                <input type="hidden" name="action" id="moveCopyAction" value="move">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveCopyTitle">{{ __('Move Items') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="moveCopyItems" class="mb-3"></div>
                    <label class="form-label">{{ __('Destination Folder') }} <span class="text-danger">*</span></label>
                    <select name="target" id="moveCopyTarget" class="form-select" required>
                        <option value="">— {{ __('Select folder') }} —</option>
                        <option value="">({{ __('Media Library root') }})</option>
                        @foreach($allFolders as $af)
                            @if($af !== $path)
                                <option value="{{ $af }}">{{ $af }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="moveCopySubmit">{{ __('Move') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View file --}}
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('File Preview') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="viewModalBody"></div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // ── Feather icons ──
    if (window.feather) { feather.replace(); }

    // ── Selection state ──
    let selectedItems = new Map(); // path -> {type,name}

    function toggleSelection(el) {
        const path = el.value, type = el.dataset.type, name = el.dataset.name;
        if (el.checked) {
            selectedItems.set(path, { type, name });
            el.closest('.media-folder, .media-file').classList.add('selected');
        } else {
            selectedItems.delete(path);
            el.closest('.media-folder, .media-file').classList.remove('selected');
        }
        updateSelectionUI();
    }

    function clearSelection() {
        selectedItems.clear();
        document.querySelectorAll('.media-check').forEach(c => { c.checked = false; c.closest('.media-folder, .media-file').classList.remove('selected'); });
        updateSelectionUI();
    }

    function updateSelectionUI() {
        const bar = document.getElementById('mediaSelectionBar');
        document.getElementById('selCount').textContent = selectedItems.size;
        if (selectedItems.size > 0) { bar.classList.add('show'); } else { bar.classList.remove('show'); }
    }

    // ── Rename (works for both folder & file) ──
    function openRenameModal(kind, path, currentName) {
        const form = document.getElementById('renameForm');
        const isFolder = kind === 'folder';
        // Route: folder vs file rename
        form.action = isFolder ? "{{ route('admin.media.folder.rename') }}" : "{{ route('admin.media.file.rename') }}";
        document.getElementById('renameKind').value = kind;
        document.getElementById('renamePath').value = path;
        document.getElementById('renameInput').value = currentName;
        document.getElementById('renameTitle').textContent = isFolder ? 'Rename Folder' : 'Rename File';
        document.getElementById('renameHint').textContent = isFolder
            ? 'Enter a new name for the folder "' + currentName + '".'
            : 'Enter a new name. The original extension (' + (currentName.split('.').pop() || '') + ') will be kept.';
        showModal('renameModal');
    }

    // ── Delete confirm (SweetAlert2) ──
    function confirmDelete(kind, path, name) {
        const isFolder = kind === 'folder';
        const route = isFolder ? "{{ route('admin.media.folder.delete') }}" : "{{ route('admin.media.file.delete') }}";
        Swal.fire({
            title: isFolder ? 'Delete folder?' : 'Delete file?',
            text: 'Are you sure you want to delete "' + name + '"? This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = route;
                form.innerHTML = '@csrf<input type="hidden" name="path" value="">';
                form.querySelector('input[name=path]').value = path;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // ── Copy live URL ──
    function copyUrl(url) {
        const done = () => {
            if (window.toastr) { toastr.success('URL copied to clipboard!', 'Success'); }
            else { alert('URL copied: ' + url); }
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done).catch(() => fallbackCopy(url, done));
        } else {
            fallbackCopy(url, done);
        }
    }

    function fallbackCopy(text, cb) {
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta); cb();
    }

    // ── View file ──
    function viewFile(url, type) {
        const body = document.getElementById('viewModalBody');
        if (type === 'pdf') {
            body.innerHTML = '<iframe src="' + url + '" style="width:100%;height:70vh;border:0;" frameborder="0"></iframe>';
        } else {
            body.innerHTML = '<img src="' + url + '" style="max-width:100%;max-height:70vh;border-radius:8px;" alt="preview">';
        }
        showModal('viewModal');
    }

    // ── Move / Copy ──
    function prepareMoveCopy(action) {
        const isMove = action === 'move';
        document.getElementById('moveCopyAction').value = isMove ? 'move' : 'copy';
        document.getElementById('moveCopyForm').action = isMove ? "{{ route('admin.media.move') }}" : "{{ route('admin.media.copy') }}";
        document.getElementById('moveCopyTitle').textContent = isMove ? 'Move Items' : 'Copy Items';
        document.getElementById('moveCopySubmit').textContent = isMove ? 'Move' : 'Copy';

        // Render selected items summary
        const wrap = document.getElementById('moveCopyItems');
        if (selectedItems.size === 0) {
            wrap.innerHTML = '<div class="alert alert-warning py-2 small">No item selected. Select files/folders first using the checkboxes.</div>';
            return;
        }
        let html = '<div class="alert alert-info py-2 small mb-2"><strong>' + selectedItems.size + '</strong> item(s) selected:</div><ul class="list-unstyled small mb-0" style="max-height:150px;overflow:auto;">';
        selectedItems.forEach((v, k) => { html += '<li><i data-feather="' + (v.type === 'folder' ? 'folder' : 'file') + '" style="width:13px;height:13px;"></i> ' + k + '</li>'; });
        html += '</ul>';
        wrap.innerHTML = html;
        if (window.feather) { feather.replace(); }
    }

    // Submitting move/copy — build items[]
    document.getElementById('moveCopyForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (selectedItems.size === 0) { toastr?.warning('Select at least one item.', 'Notice'); return; }
        selectedItems.forEach((v, k) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'items[]'; inp.value = k;
            this.appendChild(inp);
        });
        this.submit();
    });

    // ── Upload: dropzone + file list ──
    const dropzone = document.getElementById('uploadDropzone');
    const fileInput = document.getElementById('uploadInput');
    const fileList = document.getElementById('uploadFileList');
    const allowed = ['jpg','jpeg','png','gif','webp','svg','bmp','avif','pdf'];

    fileInput.addEventListener('change', renderUploadList);
    ['dragover','dragenter'].forEach(ev => dropzone.addEventListener(ev, (e) => { e.preventDefault(); dropzone.classList.add('dragover'); }));
    ['dragleave','drop'].forEach(ev => dropzone.addEventListener(ev, (e) => { e.preventDefault(); dropzone.classList.remove('dragover'); }));
    dropzone.addEventListener('drop', (e) => { if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; renderUploadList(); } });

    function renderUploadList() {
        fileList.innerHTML = '';
        [...fileInput.files].forEach((f, i) => {
            const ext = (f.name.split('.').pop() || '').toLowerCase();
            const ok = allowed.includes(ext);
            fileList.innerHTML += '<div class="up-item"><span>' + (i + 1) + '. ' + f.name + ' <small class="text-muted">(' + (f.size/1024).toFixed(1) + ' KB)</small></span>' +
                (ok ? '<span class="text-success">OK</span>' : '<span class="text-danger">Not allowed</span>') + '</div>';
        });
    }

    document.getElementById('uploadForm').addEventListener('submit', function (e) {
        if (!fileInput.files.length) { e.preventDefault(); toastr?.warning('Choose at least one file.', 'Notice'); }
    });

    // ── Generic modal show (Bootstrap 5 API with jQuery fallback) ──
    function showModal(id) {
        const el = document.getElementById(id);
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        } else if (window.jQuery) {
            jQuery(el).modal('show');
        }
    }
</script>
@endsection
