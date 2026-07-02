@extends('backEnd.layouts.master')
@section('title', 'Layout Builder - ' . $layout->name)

@section('css')
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/sortable.css" rel="stylesheet">
<style>
    .layout-builder {
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }
    .available-sections {
        flex: 0 0 320px;
        max-height: calc(100vh - 180px);
        overflow-y: auto;
        position: sticky;
        top: 90px;
    }
    .builder-canvas {
        flex: 1;
        min-height: 400px;
    }

    /* Section cards */
    .section-pool-item {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 8px;
        cursor: grab;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .section-pool-item:hover {
        border-color: #3b82f6;
        background: #f8faff;
        transform: translateX(3px);
    }
    .section-pool-item:active {
        cursor: grabbing;
    }
    .section-pool-item .pool-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 16px;
    }

    /* Canvas rows */
    .sections-sortable {
        min-height: 200px;
    }
    .section-row {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 10px;
        transition: all 0.2s;
        position: relative;
    }
    .section-row:hover {
        border-color: #94a3b8;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .section-row.sortable-chosen {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }
    .section-row.sortable-ghost {
        border-color: #3b82f6;
        border-style: dashed;
        background: #f8faff;
        opacity: 0.7;
    }
    .section-row-header {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        gap: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .section-row-header .drag-handle {
        cursor: grab;
        color: #94a3b8;
        font-size: 18px;
        line-height: 1;
    }
    .section-row-header .drag-handle:active {
        cursor: grabbing;
    }
    .section-row-header .section-title {
        flex: 1;
        font-weight: 700;
        font-size: 13px;
        color: #0f172a;
    }
    .section-row-body {
        padding: 16px;
    }
    .section-row-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 80px;
        color: #94a3b8;
        font-size: 13px;
        border: 2px dashed #e2e8f0;
        border-radius: 10px;
        margin: 8px;
    }

    .btn-ghost {
        background: none;
        border: none;
        padding: 4px 8px;
        border-radius: 6px;
        transition: 0.2s;
        font-size: 13px;
    }
    .btn-ghost:hover {
        background: #f1f5f9;
    }

    /* Screenshot area */
    .section-screenshot {
        width: 100%;
        height: 120px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 12px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    .section-screenshot img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .empty-canvas {
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        padding: 40px;
        text-align: center;
        color: #94a3b8;
    }

    /* Width / visibility controls */
    .settings-panel {
        background: #f8fafc;
        border-radius: 10px;
        padding: 16px;
        margin-top: 12px;
        display: none;
    }
    .settings-panel.open {
        display: block;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold m-0">
                <i class="mdi mdi-drag me-2"></i> Builder: {{ $layout->name }}
            </h4>
            <span class="text-muted small">{{ $layout->description ?? 'Drag & drop to arrange homepage sections' }}</span>
        </div>
        <div>
            <span class="badge bg-dark rounded-pill px-3 py-2 me-2" id="sectionCount">{{ $layout->sections->count() }} sections</span>
            <a href="{{ route('layouts.index') }}" class="btn btn-outline-secondary rounded-pill px-3 me-2">{{ __('Back') }}</a>
            <a href="{{ route('layouts.edit', $layout->id) }}" class="btn btn-light rounded-pill px-3"> {{ __('Edit Name') }} </a>
        </div>
    </div>

    <div class="layout-builder">
        {{-- Left: Available Sections --}}
        <div class="available-sections">
            <div class="card shadow-none border rounded-4">
                <div class="card-header bg-transparent border-bottom-0 pt-3">
                    <h6 class="fw-bold m-0"><i class="mdi mdi-view-grid-plus me-1"></i> {{ __('Add Section') }} </h6>
                    <p class="small text-muted m-0 mt-1"> {{ __('Click or drag to canvas') }} </p>
                </div>
                <div class="card-body p-3" id="sectionPool">
                    @foreach($availableSections as $section)
                    <div class="section-pool-item" data-section-id="{{ $section->id }}" data-section-slug="{{ $section->slug }}" data-section-name="{{ $section->name }}">
                        <div class="pool-icon">
                            @if($section->preview_image)
                                <img src="{{ asset($section->preview_image) }}" style="width:36px;height:36px;border-radius:8px;object-fit:cover;" alt="">
                            @else
                                <i class="mdi mdi-{{ $section->icon ?? 'view-module' }} text-primary"></i>
                            @endif
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:12px;">{{ $section->name }}</div>
                            <small class="text-muted">{{ $section->slug }}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-light rounded-circle ms-auto add-section-btn"
                                data-section-id="{{ $section->id }}"
                                title="Add section">
                            <i class="fe-plus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light rounded-circle capture-screenshot-btn"
                                data-section-id="{{ $section->id }}"
                                data-section-slug="{{ $section->slug }}"
                                title="Capture screenshot"
                                style="color:#8b5cf6;">
                            <i class="mdi mdi-camera"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right: Builder Canvas --}}
        <div class="builder-canvas">
            <div class="card shadow-none border rounded-4">
                <div class="card-header bg-transparent border-bottom-0 pt-3">
                    <h6 class="fw-bold m-0">
                        <i class="mdi mdi-view-dashboard me-1"></i> Section Order
                    </h6>
                    <p class="small text-muted m-0 mt-1">Drag rows to reorder, click ⚙ to configure</p>
                </div>
                <div class="card-body p-3" id="builderCanvas">
                    <div class="sections-sortable" id="sectionsSortable" data-layout-id="{{ $layout->id }}">
                        @forelse($layout->sections as $ls)
                            @include('backEnd.layout.partials.section-item', ['ls' => $ls])
                        @empty
                            <div class="empty-canvas" id="emptyCanvas">
                                <i class="mdi mdi-drag-variant" style="font-size:36px;"></i>
                                <h6 class="mt-2"> {{ __('Your layout is empty') }} </h6>
                                <p> {{ __('Drag sections from the left panel or click + to add them') }} </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const LAYOUT_ID = {{ $layout->id }};

    // Initialize SortableJS on the sections container (canvas)
    const sectionsSortable = new Sortable(document.getElementById('sectionsSortable'), {
        group: 'layout-sections',
        handle: '.drag-handle',
        animation: 200,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            updateOrder();
        },
        onAdd: function(evt) {
            // Item dragged from pool → add to layout via AJAX
            const sectionId = evt.item.dataset.sectionId;
            if (sectionId) {
                addSectionToLayout(sectionId, evt.item);
            }
        }
    });

    // Initialize SortableJS on the pool (left panel) for drag-to-canvas
    const poolSortable = new Sortable(document.getElementById('sectionPool'), {
        group: {
            name: 'layout-sections',
            pull: 'clone',
            put: false
        },
        sort: false,
        animation: 200,
        onStart: function(evt) {
            // Hide the "no sections" empty state when dragging starts
            const empty = document.getElementById('emptyCanvas');
            if (empty) empty.style.display = 'none';
        }
    });

    // Add section from pool (click)
    document.querySelectorAll('.add-section-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const sectionId = this.dataset.sectionId;
            addSectionToLayout(sectionId);
        });
    });

    // Also make pool items draggable into the sortable
    document.querySelectorAll('.section-pool-item').forEach(item => {
        item.addEventListener('dragstart', function(e) {
            // Not using native HTML5 drag; Sortable handles it
        });
    });

    // Click on pool item to add
    document.querySelectorAll('.section-pool-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.closest('.add-section-btn')) return;
            if (e.target.closest('.capture-screenshot-btn')) return;
            const sectionId = this.dataset.sectionId;
            addSectionToLayout(sectionId);
        });
    });

    // Pool capture screenshot buttons
    document.querySelectorAll('.capture-screenshot-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const sectionId = this.dataset.sectionId;
            const sectionSlug = this.dataset.sectionSlug;
            captureSectionScreenshot(sectionId, sectionSlug, null);
        });
    });

    function addSectionToLayout(sectionId, draggedEl = null) {
        fetch('{{ route('layouts.sections.add') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ layout_id: LAYOUT_ID, section_id: sectionId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Hide empty canvas
                const empty = document.getElementById('emptyCanvas');
                if (empty) empty.remove();

                const container = document.getElementById('sectionsSortable');

                if (draggedEl) {
                    // Replace the cloned pool item with the real section HTML
                    draggedEl.outerHTML = data.html;
                    const newEl = container.querySelector(`[data-ls-id="${data.section.ls_id || ''}"]`);
                    if (newEl) attachSectionEvents(newEl);
                } else {
                    container.insertAdjacentHTML('beforeend', data.html);
                    if (container.lastElementChild) attachSectionEvents(container.lastElementChild);
                }

                updateSectionCount();
                updateOrder();
            }
        })
        .catch(err => console.error(err));
    }

    function updateOrder() {
        const items = document.querySelectorAll('#sectionsSortable .sortable-item');
        const orderData = [];
        items.forEach((item, index) => {
            orderData.push({ id: item.dataset.lsId, sort_order: index + 1 });
        });

        fetch('{{ route('layouts.sections.reorder') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ layout_id: LAYOUT_ID, sections: orderData })
        })
        .then(r => r.json())
        .catch(err => console.error(err));
    }

    function toggleVisibility(id, isVisible) {
        fetch('{{ route('layouts.sections.toggle') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ id: id, is_visible: isVisible })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector(`.sortable-item[data-ls-id="${id}"] .visibility-badge`);
                if (badge) {
                    badge.innerHTML = data.is_visible 
                        ? '<i class="fe-eye me-1"></i> {!! __("Visible") !!}' 
                        : '<i class="fe-eye-off me-1"></i> {!! __("Hidden") !!}';
                    badge.className = 'badge ' + (data.is_visible ? 'bg-success' : 'bg-secondary') + ' rounded-pill visibility-badge';
                }
            }
        });
    }

    function removeSection(id) {
        Swal.fire({
            title: 'Remove section?',
            text: 'This section will be removed from the layout',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Remove',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                fetch('{{ route('layouts.sections.remove') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ id: id })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const el = document.querySelector(`.sortable-item[data-ls-id="${id}"]`);
                        if (el) el.remove();
                        updateSectionCount();
                        // Show empty canvas if no sections left
                        if (document.querySelectorAll('#sectionsSortable .sortable-item').length === 0) {
                            const container = document.getElementById('sectionsSortable');
                            container.innerHTML = `
                                <div class="empty-canvas" id="emptyCanvas">
                                    <i class="mdi mdi-drag-variant" style="font-size:36px;"></i>
                                    <h6 class="mt-2"> {{ __('Your layout is empty') }} </h6>
                                    <p> {{ __('Drag sections from the left panel or click + to add them') }} </p>
                                </div>
                            `;
                        }
                    }
                });
            }
        });
    }

    function toggleSettings(id) {
        const panel = document.getElementById('settings-' + id);
        if (panel) {
            panel.classList.toggle('open');
        }
    }

    // === Screenshot Capture System ===
    function captureSectionScreenshot(sectionId, sectionSlug, targetEl) {
        Swal.fire({
            title: 'Capturing screenshot…',
            text: 'Rendering section preview, please wait',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        // Create a hidden iframe to render the section
        const iframe = document.createElement('iframe');
        iframe.style.cssText = 'position:fixed;top:-10000px;left:-10000px;width:1200px;height:800px;border:none;';
        iframe.src = '{{ url('admin/layout/section') }}/' + sectionSlug + '/preview';
        document.body.appendChild(iframe);

        let retries = 0;
        const maxRetries = 30;

        function tryCapture() {
            retries++;
            if (retries > maxRetries) {
                document.body.removeChild(iframe);
                Swal.fire('Timeout', 'Could not render section preview', 'error');
                return;
            }

            try {
                const doc = iframe.contentDocument || iframe.contentWindow.document;
                const captureArea = doc.getElementById('captureArea');
                if (!captureArea || captureArea.innerHTML.trim() === '') {
                    setTimeout(tryCapture, 500);
                    return;
                }

                // Found the content, wait a bit more for images to load
                setTimeout(() => {
                    html2canvas(captureArea, {
                        scale: 2,
                        useCORS: true,
                        allowTaint: false,
                        backgroundColor: '#ffffff',
                        logging: false,
                    }).then(canvas => {
                        const imageData = canvas.toDataURL('image/png');
                        document.body.removeChild(iframe);

                        // Send to server
                        fetch('{{ route('layouts.sections.capture-screenshot') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                            body: JSON.stringify({ section_id: sectionId, image_data: imageData })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                // Update the screenshot preview in the builder
                                const screenshotDiv = targetEl ? 
                                    targetEl.closest('.sortable-item')?.querySelector('.section-screenshot') : 
                                    null;
                                
                                if (screenshotDiv) {
                                    screenshotDiv.innerHTML = '<img src="' + data.image_url + '?t=' + Date.now() + '" alt="Section screenshot">';
                                }

                                // Also update pool item preview if exists
                                const poolItem = document.querySelector(`.section-pool-item[data-section-id="${sectionId}"]`);
                                if (poolItem) {
                                    const oldIcon = poolItem.querySelector('.pool-icon i');
                                    if (oldIcon) {
                                        oldIcon.className = 'mdi mdi-camera-check text-success';
                                    }
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Screenshot captured!',
                                    timer: 1500,
                                    showConfirmButton: false,
                                });
                            } else {
                                Swal.fire('Error', data.message || 'Failed to save screenshot', 'error');
                            }
                        })
                        .catch(() => {
                            Swal.fire('Error', 'Network error while saving screenshot', 'error');
                        });
                    }).catch(err => {
                        document.body.removeChild(iframe);
                        Swal.fire('Error', 'html2canvas failed: ' + err.message, 'error');
                    });
                }, 1500); // extra wait for images
            } catch (e) {
                setTimeout(tryCapture, 500);
            }
        }

        setTimeout(tryCapture, 1000);
    }

    function attachSectionEvents(el) {
        // Visibility toggle
        const visBtn = el.querySelector('.toggle-visibility');
        if (visBtn) {
            visBtn.addEventListener('click', function() {
                const id = parseInt(el.dataset.lsId);
                const isVisible = !el.querySelector('.visibility-badge')?.classList.contains('bg-success');
                toggleVisibility(id, isVisible);
            });
        }

        // Capture screenshot button (in row header)
        const capBtn = el.querySelector('.capture-section-btn');
        if (capBtn) {
            capBtn.addEventListener('click', function() {
                const sectionId = parseInt(el.dataset.sectionId);
                const slug = el.querySelector('.section-screenshot')?.dataset?.sectionSlug;
                // Find slug from the pool item
                const poolItem = document.querySelector(`.section-pool-item[data-section-id="${sectionId}"]`);
                const sectionSlug = poolItem?.dataset?.sectionSlug || slug || 'unknown';
                captureSectionScreenshot(sectionId, sectionSlug, el);
            });
        }

        // Remove button
        const rmBtn = el.querySelector('.remove-section');
        if (rmBtn) {
            rmBtn.addEventListener('click', function() {
                const id = parseInt(el.dataset.lsId);
                removeSection(id);
            });
        }

        // Settings toggle
        const settingsBtn = el.querySelector('.toggle-settings');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', function() {
                const id = parseInt(el.dataset.lsId);
                toggleSettings(id);
            });
        }
    }

    function updateSectionCount() {
        const count = document.querySelectorAll('#sectionsSortable .sortable-item').length;
        document.getElementById('sectionCount').innerHTML = count + ' sections';
    }

    // Attach events to existing sections
    document.querySelectorAll('.sortable-item').forEach(el => {
        attachSectionEvents(el);
    });

    // Also make pool items work with native drag for Sortable
    document.querySelectorAll('.section-pool-item').forEach(item => {
        item.setAttribute('draggable', 'true');
        item.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.dataset.sectionId);
        });
    });

    // Allow drop on sortable container
    const sortableContainer = document.getElementById('sectionsSortable');
    sortableContainer.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    sortableContainer.addEventListener('drop', function(e) {
        e.preventDefault();
        const sectionId = e.dataTransfer.getData('text/plain');
        if (sectionId) {
            addSectionToLayout(sectionId);
        }
    });
</script>
@endsection
