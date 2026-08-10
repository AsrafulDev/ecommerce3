{{-- ============================================================
    REUSABLE MEDIA PICKER  (backEnd.media._picker)
    ------------------------------------------------------------
    Include this partial anywhere in an admin view, then open it with:

        openMediaPicker('#image')          // target input selector

    The picked file's LIVE URL is written into that input and a
    'change' event is fired (so your page JS/preview can react).

    Optionally pass a preview image element:
        openMediaPicker('#image', '#imagePreview')

    The picker only shows images + PDF (security enforced server-side).
    ============================================================ --}}

<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:14px;overflow:hidden;">
            <div class="modal-header py-2" style="background:#1e293b;color:#fff;">
                <h5 class="modal-title m-0" style="font-size:16px;">
                    <i data-feather="image" style="width:16px;height:16px;"></i> Media Library
                    <span id="pickerSelCount" class="badge bg-info text-dark ms-2 d-none" style="font-size:11px;">0 selected</span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light d-none" id="pickerClearBtn" onclick="pickerClear()">
                        <i data-feather="x" style="width:13px;height:13px;"></i> Clear
                    </button>
                    <button type="button" class="btn btn-sm btn-light" id="pickerInsertBtn" disabled onclick="pickerInsert()">
                        <i data-feather="check" style="width:13px;height:13px;"></i> <span id="pickerInsertLabel">Insert</span>
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="d-flex flex-column" style="height:70vh;">

                    {{-- toolbar: breadcrumb + new folder + upload --}}
                    <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background:#f8fafc;">
                        <span class="text-muted small me-1">Path:</span>
                        <span id="pickerBreadcrumb" class="small"></span>
                        <div class="ms-auto d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="pickerNewFolder()">
                                <i data-feather="folder-plus" style="width:13px;height:13px;"></i> Folder
                            </button>
                            <label class="btn btn-sm btn-outline-primary mb-0" for="pickerUploadInput" title="Upload image / PDF">
                                <i data-feather="upload" style="width:13px;height:13px;"></i> Upload
                            </label>
                            <input type="file" id="pickerUploadInput" class="d-none" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.bmp,.avif,.pdf,image/*,application/pdf">
                        </div>
                    </div>

                    {{-- content area (AJAX loaded) --}}
                    <div id="pickerBody" class="flex-grow-1 overflow-auto p-3" style="background:#fff;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #mediaPickerModal .picker-folder {
        display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid #eef2f7;
        border-radius:10px; margin-bottom:8px; cursor:pointer; background:#fff; transition:all .15s;
    }
    #mediaPickerModal .picker-folder:hover { border-color:#f0ad4e; background:#fffdf5; }
    #mediaPickerModal .picker-folder .pf-icon { color:#f0ad4e; display:flex; }
    #mediaPickerModal .picker-folder .pf-icon svg { width:20px; height:20px; }
    #mediaPickerModal .picker-folder .pf-name { font-weight:600; font-size:13px; color:#334155; flex:1; }
    #mediaPickerModal .picker-file {
        border:1px solid #eef2f7; border-radius:10px; overflow:hidden; cursor:pointer;
        position:relative; background:#fff; transition:all .15s;
    }
    #mediaPickerModal .picker-file:hover { border-color:#4e73df; box-shadow:0 4px 12px rgba(0,0,0,.08); }
    #mediaPickerModal .picker-file.selected { border-color:#4e73df; box-shadow:0 0 0 2px rgba(78,115,223,.25); }
    #mediaPickerModal .picker-file .pf-thumb { height:90px; display:flex; align-items:center; justify-content:center; background:#f6f8fb; overflow:hidden; }
    #mediaPickerModal .picker-file .pf-thumb img { width:100%; height:100%; object-fit:cover; }
    #mediaPickerModal .picker-file .pf-thumb .pf-pdf { color:#dc3545; display:flex; flex-direction:column; align-items:center; font-size:10px; font-weight:700; }
    #mediaPickerModal .picker-file .pf-thumb .pf-pdf svg { width:28px; height:28px; }
    #mediaPickerModal .picker-file .pf-name { padding:6px 8px; font-size:11px; font-weight:600; color:#334155; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    #mediaPickerModal .picker-file .pf-check {
        position:absolute; top:6px; left:6px; width:16px; height:16px; accent-color:#4e73df;
    }
    #mediaPickerModal .picker-tick {
        position:absolute; top:6px; right:6px; width:18px; height:18px; border-radius:50%;
        background:#4e73df; color:#fff; display:none; align-items:center; justify-content:center;
    }
    #mediaPickerModal .picker-file.selected .picker-tick { display:flex; }
    #mediaPickerModal .picker-tick svg { width:12px; height:12px; }
</style>

<script>
    (function () {
        // Guard: only register once even if included multiple times
        if (window.__mediaPickerInit) return;
        window.__mediaPickerInit = true;

        const PICKER_ROUTE = "{{ route('admin.media.picker') }}";
        const PICKER_UPLOAD_ROUTE = "{{ route('admin.media.picker.upload') }}";
        const CSRF = "{{ csrf_token() }}";
        const UPLOAD_EXTS = ['jpg','jpeg','png','gif','webp','svg','bmp','avif','pdf'];

        window._pickerTarget = null;
        window._pickerPreview = null;
        window._pickerPath = '';
        window._pickerMode = 'url'; // 'url' => full live URL (copy), 'path' => relative path (DB storage)
        window._pickerSelected = null; // {path,name,url,rel}
        window._pickerMulti = false;   // multi-select mode (accumulate several files)
        window._pickerMultiItems = []; // [{path,name,url,rel}, ...]
        window._pickerCallback = null; // optional callback (e.g. Summernote image insert)

        function showModal(el) {
            if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(el).show();
            else if (window.jQuery) jQuery(el).modal('show');
        }
        function hideModal(el) {
            if (window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(el).hide();
            else if (window.jQuery) jQuery(el).modal('hide');
        }

        window.openMediaPicker = function (targetSelector, previewSelector, valueMode, multiSelect) {
            window._pickerTarget = targetSelector;
            window._pickerPreview = previewSelector || null;
            window._pickerMode = valueMode || 'url';
            window._pickerMulti = !!multiSelect;
            window._pickerMultiItems = [];
            window._pickerSelected = null;
            window._pickerPath = '';
            window._pickerCallback = null;
            pickerUpdateMultiUI();
            showModal(document.getElementById('mediaPickerModal'));
            pickerLoad('');
        };

        // Open the picker in single-select mode and hand the picked item to a
        // callback (used e.g. by Summernote to insert an image by URL).
        window.openMediaPickerFor = function (callback, valueMode) {
            window._pickerCallback = callback || null;
            window._pickerTarget = null;
            window._pickerPreview = null;
            window._pickerMode = valueMode || 'url';
            window._pickerMulti = false;
            window._pickerMultiItems = [];
            window._pickerSelected = null;
            window._pickerPath = '';
            pickerUpdateMultiUI();
            showModal(document.getElementById('mediaPickerModal'));
            pickerLoad('');
        };

        // Keep the multi-select UI (count badge, button label, clear button) in sync
        function pickerUpdateMultiUI() {
            const multi = !!window._pickerMulti;
            const count = multi ? window._pickerMultiItems.length : 0;
            const badge = document.getElementById('pickerSelCount');
            const label = document.getElementById('pickerInsertLabel');
            const clear = document.getElementById('pickerClearBtn');
            const btn = document.getElementById('pickerInsertBtn');
            if (badge) {
                badge.classList.toggle('d-none', !multi);
                badge.textContent = count + ' selected';
            }
            if (label) {
                label.textContent = multi ? ('Add Selected (' + count + ')') : 'Insert';
            }
            if (clear) {
                clear.classList.toggle('d-none', !multi || count === 0);
            }
            if (btn) {
                // multi: enable when ≥1 selected; single: enable when a file is selected
                btn.disabled = multi ? (count === 0) : !window._pickerSelected;
            }
        }

        window.pickerClear = function () {
            if (!window._pickerMulti) return;
            window._pickerMultiItems = [];
            window._pickerSelected = null;
            document.querySelectorAll('#mediaPickerModal .picker-file').forEach(f => f.classList.remove('selected'));
            pickerUpdateMultiUI();
        };

        window.pickerLoad = function (path) {
            window._pickerPath = path || '';
            window._pickerSelected = null;
            pickerUpdateMultiUI();
            fetch(PICKER_ROUTE + '?path=' + encodeURIComponent(path), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('pickerBody').innerHTML = html;
                    // Re-mark previously selected files when navigating folders in multi mode
                    if (window._pickerMulti && window._pickerMultiItems.length) {
                        document.querySelectorAll('#mediaPickerModal .picker-file').forEach(c => {
                            const p = c.dataset.path;
                            if (p && window._pickerMultiItems.some(i => i.path === p)) {
                                c.classList.add('selected');
                            }
                        });
                    }
                    if (window.feather) feather.replace();
                })
                .catch(() => { document.getElementById('pickerBody').innerHTML = '<div class="alert alert-danger">Failed to load media.</div>'; });
        };

        window.pickerSelect = function (el) {
            const card = (el && el.closest) ? el.closest('.picker-file') : el;
            if (!card) return;
            const item = {
                path: card.dataset.path,
                name: card.dataset.name,
                url: card.dataset.url,
                rel: card.dataset.rel || card.dataset.url
            };

            if (window._pickerMulti) {
                // Toggle this file in the accumulated selection
                const idx = window._pickerMultiItems.findIndex(i => i.path === item.path);
                if (idx >= 0) {
                    window._pickerMultiItems.splice(idx, 1);
                    card.classList.remove('selected');
                } else {
                    window._pickerMultiItems.push(item);
                    card.classList.add('selected');
                }
                pickerUpdateMultiUI();
            } else {
                document.querySelectorAll('#mediaPickerModal .picker-file').forEach(f => f.classList.remove('selected'));
                card.classList.add('selected');
                window._pickerSelected = item;
                pickerUpdateMultiUI();
            }
        };

        window.pickerInsert = function () {
            if (window._pickerMulti) {
                if (!window._pickerMultiItems.length || !window._pickerTarget) return;
                const target = document.querySelector(window._pickerTarget);
                if (target) {
                    const values = window._pickerMultiItems.map(i => window._pickerMode === 'path' ? i.rel : i.url);
                    target.value = JSON.stringify(values);
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                    const labelId = window._pickerTarget.replace('#', '') + '_file';
                    const label = document.getElementById(labelId);
                    if (label) label.textContent = '✓ ' + window._pickerMultiItems.length + ' file(s) selected';
                }
                if (window.toastr) toastr.success(window._pickerMultiItems.length + ' media inserted', 'Success');
                // Keep the accumulated selection so the user can add more later
                hideModal(document.getElementById('mediaPickerModal'));
                return;
            }

            // Callback mode (e.g. Summernote "insert image by URL") — no target input
            if (window._pickerCallback && window._pickerSelected) {
                const cb = window._pickerCallback;
                const item = window._pickerSelected;
                window._pickerCallback = null;
                hideModal(document.getElementById('mediaPickerModal'));
                if (window.toastr) toastr.success('Media inserted: ' + item.name, 'Success');
                cb(item);
                return;
            }

            if (!window._pickerSelected || !window._pickerTarget) return;
            const target = document.querySelector(window._pickerTarget);
            if (target) {
                // 'path' mode => relative storage path (public/uploads/media/...),
                // otherwise the full live URL
                target.value = window._pickerMode === 'path'
                    ? window._pickerSelected.rel
                    : window._pickerSelected.url;
                target.dispatchEvent(new Event('change', { bubbles: true }));
                target.dispatchEvent(new Event('input', { bubbles: true }));

                // Show picked file name next to the button (e.g. #image_one_url_file)
                const labelId = window._pickerTarget.replace('#', '') + '_file';
                const label = document.getElementById(labelId);
                if (label) label.textContent = '✓ ' + window._pickerSelected.name;
            }
            if (window._pickerPreview) {
                const img = document.querySelector(window._pickerPreview);
                if (img) { img.src = window._pickerSelected.url; img.style.display = 'inline-block'; }
            }
            if (window.toastr) toastr.success('Media inserted: ' + window._pickerSelected.name, 'Success');
            hideModal(document.getElementById('mediaPickerModal'));
        };

        window.pickerNewFolder = function () {
            const name = prompt('New folder name in "' + (window._pickerPath || 'root') + '":');
            if (!name || !name.trim()) return;
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('path', window._pickerPath);
            fd.append('folder_name', name.trim());
            fetch("{{ route('admin.media.folder.create') }}", { method: 'POST', body: fd })
                .then(() => pickerLoad(window._pickerPath))
                .catch(() => toastr && toastr.error('Could not create folder.', 'Error'));
        };

        // Upload via AJAX (no nested form — works inside any parent form)
        const upInput = document.getElementById('pickerUploadInput');
        upInput.addEventListener('change', function () {
            if (!this.files.length) return;
            const files = [...this.files].filter(f => UPLOAD_EXTS.includes((f.name.split('.').pop() || '').toLowerCase()));
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('path', window._pickerPath);
            files.forEach(f => fd.append('files[]', f));
            const label = upInput.closest('label');
            const old = label ? label.innerHTML : '';
            if (label) label.innerHTML = '<i data-feather="loader" style="width:13px;height:13px;"></i> Uploading…';
            fetch(PICKER_UPLOAD_ROUTE, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (label && window.feather) feather.replace();
                    if (res && res.uploaded) {
                        if (window.toastr) toastr.success(res.uploaded + ' file(s) uploaded.', 'Success');
                        pickerLoad(window._pickerPath);
                    } else {
                        if (window.toastr) toastr.warning('No valid file (only images & PDF allowed).', 'Notice');
                    }
                })
                .catch(() => { if (label) label.innerHTML = old; toastr && toastr.error('Upload failed.', 'Error'); });
            this.value = '';
        });
    })();
</script>
