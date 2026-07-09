@extends('backEnd.layouts.master')
@section('title', 'Header & Footer Builder')

@push('css')
<style>
    :root {
        --accent: #6366f1;
        --accent-light: #f5f3ff;
    }

    /* ── Tabs ── */
    .builder-tabs { border-bottom:2px solid #e5e7eb; margin-bottom:24px; }
    .builder-tabs .nav-link { font-weight:600; padding:12px 28px; border:none; color:#6b7280; border-radius:10px 10px 0 0; }
    .builder-tabs .nav-link.active { color:#fff !important; background:var(--accent); }
    .builder-tabs .nav-link:hover:not(.active) { color:var(--accent); background:var(--accent-light); }

    /* ── Preset Cards ── */
    .style-card { border:2px solid #e5e7eb; border-radius:12px; padding:16px; cursor:pointer; transition:all 0.3s; }
    .style-card:hover { border-color:var(--accent); box-shadow:0 4px 16px rgba(99,102,241,0.12); }
    .style-card.active { border-color:var(--accent); background:var(--accent-light); }
    .style-card .style-icon { font-size:36px; color:var(--accent); }
    .preview-frame { width:100%; height:500px; border:1px solid #e5e7eb; border-radius:12px; background:#fff; overflow:hidden; }
    .preview-frame iframe { width:100%; height:100%; border:none; display:block; }

    .toggle-switch { position:relative; display:inline-block; width:44px; height:24px; }
    .toggle-switch input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; cursor:pointer; top:0;left:0;right:0;bottom:0; background:#ccc; transition:.3s; border-radius:24px; }
    .toggle-slider:before { content:""; position:absolute; height:18px;width:18px; left:3px;bottom:3px; background:#fff; transition:.3s; border-radius:50%; }
    input:checked + .toggle-slider { background:var(--accent); }
    input:checked + .toggle-slider:before { transform:translateX(20px); }

    /* ═══════════════════════════════════════════ */
    /*  LAYOUT-BUILDER STYLE (matched from layout builder) */
    /* ═══════════════════════════════════════════ */
    .layout-builder {
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }
    .available-sections {
        flex: 0 0 280px;
        max-height: calc(100vh - 200px);
        overflow-y: auto;
        position: sticky;
        top: 90px;
    }
    .builder-canvas {
        flex: 1;
        min-height: 200px;
    }

    /* Pool items */
    .section-pool-item {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size:13px;
    }
    .section-pool-item:hover {
        border-color: #3b82f6;
        background: #f8faff;
        transform: translateX(3px);
    }
    .section-pool-item .pool-icon {
        width: 34px; height: 34px;
        border-radius: 8px;
        background: #f1f5f9;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 16px;
        color: var(--accent);
    }
    .section-pool-item .pool-badge {
        margin-left: auto;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 10px;
    }
    .section-pool-item .pool-badge.used { background:#d1fae5; color:#065f46; }
    .section-pool-item .pool-badge.available { background:#e5e7eb; color:#6b7280; }

    /* Canvas rows */
    .sections-sortable { min-height: 80px; }
    .section-row {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 8px;
        transition: all 0.2s;
        position: relative;
    }
    .section-row:hover { border-color: #94a3b8; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .section-row.sortable-chosen { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
    .section-row.sortable-ghost { border-color: #3b82f6; border-style: dashed; background: #f8faff; opacity: 0.7; }
    .section-row-header {
        display: flex; align-items: center; padding: 12px 16px; gap: 12px;
    }
    .section-row-header .drag-handle {
        cursor: grab; color: #94a3b8; font-size: 18px; line-height: 1;
    }
    .section-row-header .drag-handle:active { cursor: grabbing; }
    .section-row-header .section-title {
        flex: 1; font-weight: 600; font-size: 13px; color: #0f172a;
    }
    .section-row-header .section-icon {
        width: 30px; height: 30px; border-radius: 6px; background: #f1f5f9;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; color: var(--accent);
    }

    .btn-ghost {
        background: none; border: none; padding: 4px 8px;
        border-radius: 6px; transition: 0.2s; font-size: 14px; cursor:pointer;
    }
    .btn-ghost:hover { background: #f1f5f9; }
    .btn-ghost.text-danger:hover { background: #fef2f2; }
    .btn-ghost.text-success:hover { background: #f0fdf4; }

    .empty-canvas {
        border: 2px dashed #cbd5e1; border-radius: 16px;
        padding: 36px; text-align: center; color: #94a3b8;
    }
    .empty-canvas i { font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.5; }

    /* Live Preview */
    .live-preview-panel {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
        margin-top: 20px; overflow: hidden;
    }
    .live-preview-panel .preview-header {
        background: #f9fafb; padding: 10px 16px; font-weight: 600; font-size: 13px;
        color: #374151; border-bottom: 1px solid #e5e7eb;
        display: flex; justify-content: space-between; align-items: center;
    }
    .live-preview-panel .preview-body { padding: 0; min-height: 200px; background: #f8fafc; }
    .live-preview-panel .preview-body iframe { width:100%; height:400px; border:none; display:block; }

    @media (max-width:992px) {
        .layout-builder { flex-direction: column; }
        .available-sections { flex: none; max-height: none; position: static; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title"><i class="mdi mdi-page-layout-header text-primary"></i> {{ __('Header & Footer Builder') }}</h4>
        <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-open-in-new"></i> View Site</a>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs builder-tabs" id="builderTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link {{ ($setting->header_style ?? 'custom') !== 'custom' ? 'active' : '' }}" 
                    id="presets-tab" data-bs-toggle="tab" data-bs-target="#presets-panel" type="button">
                <i class="mdi mdi-palette-outline me-1"></i> {{ __('Preset Styles') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ ($setting->header_style ?? 'custom') === 'custom' ? 'active' : '' }}" 
                    id="custom-tab" data-bs-toggle="tab" data-bs-target="#custom-panel" type="button">
                <i class="mdi mdi-cog me-1"></i> {{ __('Live Builder') }}
            </button>
        </li>
    </ul>

    <form action="{{ route('headerfooter.update') }}" method="POST" id="headerFooterForm">
        @csrf
        <div class="tab-content">

            {{-- ════════════ PRESETS TAB ════════════ --}}
            <div class="tab-pane fade {{ ($setting->header_style ?? 'custom') !== 'custom' ? 'show active' : '' }}" id="presets-panel">
                @php $hIcons = ['default'=>'mdi-home','classic'=>'mdi-page-layout-header','modern'=>'mdi-view-dashboard','minimal'=>'mdi-page-layout-body','centered'=>'mdi-align-horizontal-center','mega'=>'mdi-menu-open','custom'=>'mdi-cog']; @endphp
                <div class="card mb-4"><div class="card-header bg-light"><h5 class="mb-0"><i class="mdi mdi-page-layout-header me-2"></i>{{ __('Header Style') }}</h5></div>
                <div class="card-body"><div class="row g-3">
                    @foreach($headerStyles as $key => $name)
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <label class="style-card {{ ($setting->header_style ?? 'custom') == $key ? 'active' : '' }} d-block" 
                               onclick="if('{{ $key }}'==='custom'){switchToBuilder();return;}previewPresetStyle('header','{{ $key }}')">
                            <input type="radio" name="header_style" value="{{ $key }}" {{ ($setting->header_style ?? 'custom') == $key ? 'checked' : '' }} class="d-none">
                            <div class="text-center"><i class="mdi {{ $hIcons[$key] ?? 'mdi-page-layout-header' }} style-icon"></i><h6 class="mt-2 mb-1">{{ $name }}</h6>@if($key==='custom')<small class="text-primary fw-bold">← Live Builder</small>@endif</div>
                        </label>
                    </div>@endforeach
                </div>
                <div class="row mt-3">
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2"><label class="toggle-switch"><input type="checkbox" name="header_top_bar" value="1" {{ ($setting->header_top_bar ?? 1) ? 'checked' : '' }}><span class="toggle-slider"></span></label><span>{{ __('Show Top Bar') }}</span></label></div>
                    <div class="col-md-6"><label class="d-flex align-items-center gap-2"><label class="toggle-switch"><input type="checkbox" name="header_sticky" value="1" {{ ($setting->header_sticky ?? 1) ? 'checked' : '' }}><span class="toggle-slider"></span></label><span>{{ __('Sticky Header') }}</span></label></div>
                </div></div></div>
                <div class="card mb-4"><div class="card-header bg-light d-flex justify-content-between"><h5 class="mb-0"><i class="mdi mdi-eye me-2"></i>{{ __('Header Preview') }}</h5><span class="badge bg-primary" id="header-preview-label">{{ $headerStyles[$setting->header_style ?? 'custom'] ?? 'Custom' }}</span></div><div class="card-body p-0"><div class="preview-frame" id="header-preview"><div class="d-flex justify-content-center align-items-center h-100 text-muted">Select a style to preview</div></div></div></div>

                @php $fIcons = ['default'=>'mdi-home','classic'=>'mdi-page-layout-footer','modern'=>'mdi-view-dashboard','dark'=>'mdi-invert-colors','minimal'=>'mdi-minimize','columns'=>'mdi-view-column','custom'=>'mdi-cog']; @endphp
                <div class="card mb-4"><div class="card-header bg-light"><h5 class="mb-0"><i class="mdi mdi-page-layout-footer me-2"></i>{{ __('Footer Style') }}</h5></div>
                <div class="card-body"><div class="row g-3">
                    @foreach($footerStyles as $key => $name)
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <label class="style-card {{ ($setting->footer_style ?? 'custom') == $key ? 'active' : '' }} d-block" 
                               onclick="if('{{ $key }}'==='custom'){switchToBuilder();return;}previewPresetStyle('footer','{{ $key }}')">
                            <input type="radio" name="footer_style" value="{{ $key }}" {{ ($setting->footer_style ?? 'custom') == $key ? 'checked' : '' }} class="d-none">
                            <div class="text-center"><i class="mdi {{ $fIcons[$key] ?? 'mdi-page-layout-footer' }} style-icon"></i><h6 class="mt-2 mb-1">{{ $name }}</h6>@if($key==='custom')<small class="text-primary fw-bold">← Live Builder</small>@endif</div>
                        </label>
                    </div>@endforeach
                </div></div></div>
                <div class="card mb-4"><div class="card-header bg-light d-flex justify-content-between"><h5 class="mb-0"><i class="mdi mdi-eye me-2"></i>{{ __('Footer Preview') }}</h5><span class="badge bg-primary" id="footer-preview-label">{{ $footerStyles[$setting->footer_style ?? 'custom'] ?? 'Custom' }}</span></div><div class="card-body p-0"><div class="preview-frame" id="footer-preview"><div class="d-flex justify-content-center align-items-center h-100 text-muted">Select a style to preview</div></div></div></div>
            </div>

            {{-- ════════════ LIVE BUILDER TAB (LAYOUT BUILDER STYLE) ════════════ --}}
            <div class="tab-pane fade {{ ($setting->header_style ?? 'custom') === 'custom' ? 'show active' : '' }}" id="custom-panel">

                {{-- ===== HEADER BUILDER ===== --}}
                <div class="card shadow-none border rounded-4 mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-3 d-flex justify-content-between">
                        <div>
                            <h6 class="fw-bold m-0"><i class="mdi mdi-page-layout-header me-1"></i> {{ __('Header Builder') }}</h6>
                            <span class="small text-muted">Drag & drop to arrange header components</span>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge bg-dark rounded-pill px-3 py-2" id="headerCount">{{ count($activeHeader) }} components</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="resetBuilder('header')"><i class="mdi mdi-refresh"></i> Reset</button>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="layout-builder" id="headerLayoutBuilder">
                            {{-- Left: Pool --}}
                            <div class="available-sections">
                                <div class="card shadow-none border rounded-4">
                                    <div class="card-header bg-transparent border-bottom-0 pt-3">
                                        <h6 class="fw-bold m-0"><i class="mdi mdi-view-grid-plus me-1"></i> Add Component</h6>
                                        <p class="small text-muted m-0 mt-1">Click to add to layout</p>
                                    </div>
                                    <div class="card-body p-3" id="headerPool">
                                        @foreach($hComps as $key => $comp)
                                        @php $isUsed = in_array($key, $activeHeader); @endphp
                                        <div class="section-pool-item {{ $isUsed ? 'opacity-50' : '' }}" 
                                             data-comp="{{ $key }}" data-type="header"
                                             @if(!$isUsed) onclick="addComponent('header','{{ $key }}')" @endif>
                                            <div class="pool-icon"><i class="mdi {{ $comp['icon'] }}"></i></div>
                                            <div><div class="fw-bold">{{ $comp['name'] }}</div></div>
                                            <span class="pool-badge {{ $isUsed ? 'used' : 'available' }}">{{ $isUsed ? '✓ Added' : '+ Add' }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            {{-- Right: Canvas --}}
                            <div class="builder-canvas">
                                <div class="card shadow-none border rounded-4">
                                    <div class="card-header bg-transparent border-bottom-0 pt-3">
                                        <h6 class="fw-bold m-0"><i class="mdi mdi-view-dashboard me-1"></i> Active Layout</h6>
                                        <p class="small text-muted m-0 mt-1">Drag rows to reorder</p>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="sections-sortable" id="headerSortable">
                                            @forelse($activeHeader as $compKey)
                                            <div class="section-row" data-comp="{{ $compKey }}" data-type="header">
                                                <div class="section-row-header">
                                                    <i class="mdi mdi-drag-horizontal drag-handle"></i>
                                                    <div class="section-icon"><i class="mdi {{ $hComps[$compKey]['icon'] ?? 'mdi-puzzle' }}"></i></div>
                                                    <span class="section-title">{{ $hComps[$compKey]['name'] ?? $compKey }}</span>
                                                    <button type="button" class="btn-ghost text-danger" onclick="removeComponent('header','{{ $compKey }}')" title="Remove"><i class="mdi mdi-close-circle"></i></button>
                                                </div>
                                            </div>
                                            @empty
                                            <div class="empty-canvas" id="headerEmpty">
                                                <i class="mdi mdi-drag-variant"></i>
                                                <h6 class="mt-2">Your header layout is empty</h6>
                                                <p>Click components from the left panel to add them</p>
                                            </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Live Preview --}}
                        <div class="live-preview-panel mt-3">
                            <div class="preview-header">
                                <span><i class="mdi mdi-monitor me-1"></i> Live Header Preview</span>
                                <button type="button" class="btn btn-sm btn-light rounded-pill" onclick="refreshPreview('header')"><i class="mdi mdi-refresh"></i> Refresh</button>
                            </div>
                            <div class="preview-body" id="headerPreviewBody">
                                <div class="d-flex justify-content-center align-items-center" style="height:200px;color:#94a3b8;">
                                    <div class="spinner-border spinner-border-sm me-2"></div> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== FOOTER BUILDER ===== --}}
                <div class="card shadow-none border rounded-4 mb-4">
                    <div class="card-header bg-transparent border-bottom-0 pt-3 d-flex justify-content-between">
                        <div>
                            <h6 class="fw-bold m-0"><i class="mdi mdi-page-layout-footer me-1"></i> {{ __('Footer Builder') }}</h6>
                            <span class="small text-muted">Drag & drop to arrange footer components</span>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge bg-dark rounded-pill px-3 py-2" id="footerCount">{{ count($activeFooter) }} components</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="resetBuilder('footer')"><i class="mdi mdi-refresh"></i> Reset</button>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="layout-builder" id="footerLayoutBuilder">
                            <div class="available-sections">
                                <div class="card shadow-none border rounded-4">
                                    <div class="card-header bg-transparent border-bottom-0 pt-3">
                                        <h6 class="fw-bold m-0"><i class="mdi mdi-view-grid-plus me-1"></i> Add Component</h6>
                                    </div>
                                    <div class="card-body p-3" id="footerPool">
                                        @foreach($fComps as $key => $comp)
                                        @php $isUsed = in_array($key, $activeFooter); @endphp
                                        <div class="section-pool-item {{ $isUsed ? 'opacity-50' : '' }}" 
                                             data-comp="{{ $key }}" data-type="footer"
                                             @if(!$isUsed) onclick="addComponent('footer','{{ $key }}')" @endif>
                                            <div class="pool-icon"><i class="mdi {{ $comp['icon'] }}"></i></div>
                                            <div><div class="fw-bold">{{ $comp['name'] }}</div></div>
                                            <span class="pool-badge {{ $isUsed ? 'used' : 'available' }}">{{ $isUsed ? '✓ Added' : '+ Add' }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="builder-canvas">
                                <div class="card shadow-none border rounded-4">
                                    <div class="card-header bg-transparent border-bottom-0 pt-3">
                                        <h6 class="fw-bold m-0"><i class="mdi mdi-view-dashboard me-1"></i> Active Layout</h6>
                                        <p class="small text-muted m-0 mt-1">Drag rows to reorder</p>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="sections-sortable" id="footerSortable">
                                            @forelse($activeFooter as $compKey)
                                            <div class="section-row" data-comp="{{ $compKey }}" data-type="footer">
                                                <div class="section-row-header">
                                                    <i class="mdi mdi-drag-horizontal drag-handle"></i>
                                                    <div class="section-icon"><i class="mdi {{ $fComps[$compKey]['icon'] ?? 'mdi-puzzle' }}"></i></div>
                                                    <span class="section-title">{{ $fComps[$compKey]['name'] ?? $compKey }}</span>
                                                    <button type="button" class="btn-ghost text-danger" onclick="removeComponent('footer','{{ $compKey }}')" title="Remove"><i class="mdi mdi-close-circle"></i></button>
                                                </div>
                                            </div>
                                            @empty
                                            <div class="empty-canvas" id="footerEmpty">
                                                <i class="mdi mdi-drag-variant"></i>
                                                <h6 class="mt-2">Your footer layout is empty</h6>
                                                <p>Click components from the left panel to add them</p>
                                            </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="live-preview-panel mt-3">
                            <div class="preview-header">
                                <span><i class="mdi mdi-monitor me-1"></i> Live Footer Preview</span>
                                <button type="button" class="btn btn-sm btn-light rounded-pill" onclick="refreshPreview('footer')"><i class="mdi mdi-refresh"></i> Refresh</button>
                            </div>
                            <div class="preview-body" id="footerPreviewBody">
                                <div class="d-flex justify-content-center align-items-center" style="height:200px;color:#94a3b8;">
                                    <div class="spinner-border spinner-border-sm me-2"></div> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill"><i class="mdi mdi-content-save"></i> {{ __('Save Header & Footer') }}</button>
        </div>
    </form>
</div>

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
const CSRF = '{{ csrf_token() }}';

// ═══════════════════════════════════════
//  SORTABLE INIT (header + footer)
// ═══════════════════════════════════════
initSortable('header');
initSortable('footer');

function initSortable(type) {
    const el = document.getElementById(type + 'Sortable');
    if (!el) return;
    new Sortable(el, {
        handle: '.drag-handle',
        animation: 200,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function() { saveOrder(type); }
    });
}

function saveOrder(type) {
    const items = document.querySelectorAll('#' + type + 'Sortable .section-row');
    const order = [...items].map(el => el.dataset.comp);
    fetch('{{ route("headerfooter.reorder-components") }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({type:type, order:order})
    }).then(r=>r.json()).then(d=>{ if(d.success) refreshPreview(type); });
}

// ═══════════════════════════════════════
//  ADD / REMOVE COMPONENTS
// ═══════════════════════════════════════
function addComponent(type, comp) {
    fetch('{{ route("headerfooter.add-component") }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({type:type, component:comp})
    })
    .then(r=>r.json())
    .then(d=>{ if(d.success) location.reload(); });
}

function removeComponent(type, comp) {
    Swal.fire({
        title: 'Remove component?',
        text: 'This component will be removed from the layout.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;
        fetch('{{ route("headerfooter.remove-component") }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify({type:type, component:comp})
        })
        .then(r=>r.json())
        .then(d=>{ if(d.success) location.reload(); });
    });
}

function resetBuilder(type) {
    Swal.fire({
        title: 'Reset layout?',
        text: 'This will restore all default components.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, reset',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;
        const defaults = type === 'header' 
            ? @json(array_keys($hComps)) 
            : @json(array_keys($fComps));
        fetch('{{ route("headerfooter.reorder-components") }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify({type:type, order:defaults})
        })
        .then(r=>r.json())
        .then(d=>{ if(d.success) location.reload(); });
    });
}

// ═══════════════════════════════════════
//  LIVE PREVIEW
// ═══════════════════════════════════════
function refreshPreview(type) {
    const body = document.getElementById(type + 'PreviewBody');
    body.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="height:200px;"><div class="spinner-border spinner-border-sm me-2"></div> Loading...</div>';
    
    fetch('{{ route("headerfooter.preview") }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({type:type, style:'custom'})
    })
    .then(r=>r.json())
    .then(data=>{
        body.innerHTML = '';
        const iframe = document.createElement('iframe');
        iframe.srcdoc = data.html;
        iframe.style.cssText = 'width:100%;height:400px;border:none;display:block;';
        iframe.setAttribute('frameborder','0');
        iframe.setAttribute('scrolling','auto');
        body.appendChild(iframe);
    })
    .catch(()=>{
        body.innerHTML = '<div class="text-center text-danger py-4">Preview failed</div>';
    });
}

// ═══════════════════════════════════════
//  PRESET PREVIEW
// ═══════════════════════════════════════
function previewPresetStyle(type, style) {
    var c = document.getElementById(type+'-preview');
    var l = document.getElementById(type+'-preview-label');
    l.textContent = style.charAt(0).toUpperCase()+style.slice(1);
    c.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100"><div class="spinner-border text-primary"></div></div>';
    document.querySelectorAll('input[name="'+type+'_style"]').forEach(function(r){
        r.closest('.style-card').classList.remove('active');
        if(r.value===style) r.closest('.style-card').classList.add('active');
    });
    fetch('{{ route("headerfooter.preview") }}', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({type:type, style:style})
    }).then(r=>r.json()).then(d=>{
        c.innerHTML = '';
        const iframe = document.createElement('iframe');
        iframe.srcdoc = d.html;
        iframe.style.cssText = 'width:100%;height:100%;border:none;display:block;';
        iframe.setAttribute('frameborder','0');
        iframe.setAttribute('scrolling','auto');
        c.appendChild(iframe);
    }).catch(()=>{ c.innerHTML='<div class="d-flex justify-content-center align-items-center h-100 text-danger">Preview failed</div>'; });
}

function switchToBuilder() {
    document.getElementById('custom-tab').click();
}

// Tab switch
document.getElementById('custom-tab').addEventListener('shown.bs.tab', function() {
    document.querySelector('input[name="header_style"][value="custom"]').checked = true;
    document.querySelector('input[name="footer_style"][value="custom"]').checked = true;
    document.querySelectorAll('.style-card').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('input[value="custom"]').forEach(r => r.closest('.style-card')?.classList.add('active'));
    refreshPreview('header');
    refreshPreview('footer');
});

// Form submit
document.getElementById('headerFooterForm').addEventListener('submit', function() {
    if (document.getElementById('custom-panel').classList.contains('active')) {
        document.querySelector('input[name="header_style"][value="custom"]').checked = true;
        document.querySelector('input[name="footer_style"][value="custom"]').checked = true;
    }
});

// Init
document.addEventListener('DOMContentLoaded', function(){
    if (document.getElementById('presets-panel').classList.contains('active')) {
        var h = document.querySelector('#presets-panel input[name="header_style"]:checked');
        var f = document.querySelector('#presets-panel input[name="footer_style"]:checked');
        if (h && h.value!=='custom') previewPresetStyle('header', h.value);
        if (f && f.value!=='custom') previewPresetStyle('footer', f.value);
    }
    if (document.getElementById('custom-panel').classList.contains('active')) {
        refreshPreview('header');
        refreshPreview('footer');
    }
});
</script>
@endsection
@endsection
