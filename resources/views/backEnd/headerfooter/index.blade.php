@extends('backEnd.layouts.master')
@section('title', 'Header & Footer Builder')

@push('css')
<style>
    .style-card { border:2px solid #e5e7eb; border-radius:12px; padding:16px; cursor:pointer; transition:all 0.3s; }
    .style-card:hover { border-color:#6366f1; box-shadow:0 4px 16px rgba(99,102,241,0.12); }
    .style-card.active { border-color:#6366f1; background:#f5f3ff; }
    .style-card .style-icon { font-size:36px; color:#6366f1; }
    .preview-frame { width:100%; height:400px; border:1px solid #e5e7eb; border-radius:12px; background:#fff; overflow:hidden; }
    .preview-frame iframe { width:100%; height:100%; border:none; }
    .toggle-switch { position:relative; display:inline-block; width:44px; height:24px; }
    .toggle-switch input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; cursor:pointer; top:0;left:0;right:0;bottom:0; background:#ccc; transition:.3s; border-radius:24px; }
    .toggle-slider:before { content:""; position:absolute; height:18px;width:18px; left:3px;bottom:3px; background:#fff; transition:.3s; border-radius:50%; }
    input:checked + .toggle-slider { background:#6366f1; }
    input:checked + .toggle-slider:before { transform:translateX(20px); }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title"><i class="mdi mdi-page-layout-header text-primary"></i> Header & Footer Builder</h4>
        <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="mdi mdi-open-in-new"></i> View Site
        </a>
    </div>

    <form action="{{ route('headerfooter.update') }}" method="POST">
        @csrf

        {{-- ===== HEADER STYLES ===== --}}
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="mdi mdi-page-layout-header me-2"></i>Header Style</h5>
            </div>
            <div class="card-body">
                @php $hIcons = ['classic'=>'mdi-page-layout-header','modern'=>'mdi-view-dashboard','minimal'=>'mdi-page-layout-body','centered'=>'mdi-align-horizontal-center','mega'=>'mdi-menu-open','custom'=>'mdi-cog']; @endphp
                <div class="row g-3">
                    @foreach($headerStyles as $key => $name)
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <label class="style-card {{ ($setting->header_style ?? 'classic') == $key ? 'active' : '' }} d-block" onclick="previewStyle('header','{{ $key }}')">
                            <input type="radio" name="header_style" value="{{ $key }}" {{ ($setting->header_style ?? 'classic') == $key ? 'checked' : '' }} class="d-none">
                            <div class="text-center">
                                <i class="mdi {{ $hIcons[$key] ?? 'mdi-page-layout-header' }} style-icon"></i>
                                <h6 class="mt-2 mb-1">{{ $name }}</h6>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
                {{-- Header Options --}}
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2">
                            <label class="toggle-switch"><input type="checkbox" name="header_top_bar" value="1" {{ ($setting->header_top_bar ?? 1) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                            <span>Show Top Bar</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2">
                            <label class="toggle-switch"><input type="checkbox" name="header_sticky" value="1" {{ ($setting->header_sticky ?? 1) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                            <span>Sticky Header</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== HEADER PREVIEW ===== --}}
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="mdi mdi-eye me-2"></i>Header Preview</h5>
                <span class="badge bg-primary" id="header-preview-label">Classic</span>
            </div>
            <div class="card-body p-0">
                <div class="preview-frame" id="header-preview">
                    <div class="d-flex justify-content-center align-items-center h-100 text-muted">
                        <i class="mdi mdi-refresh mdi-spin fs-3 me-2"></i> Select a header style to preview
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== FOOTER STYLES ===== --}}
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="mdi mdi-page-layout-footer me-2"></i>Footer Style</h5>
            </div>
            <div class="card-body">
                @php $fIcons = ['classic'=>'mdi-page-layout-footer','modern'=>'mdi-view-dashboard','dark'=>'mdi-invert-colors','minimal'=>'mdi-minimize','columns'=>'mdi-view-column','custom'=>'mdi-cog']; @endphp
                <div class="row g-3">
                    @foreach($footerStyles as $key => $name)
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <label class="style-card {{ ($setting->footer_style ?? 'classic') == $key ? 'active' : '' }} d-block" onclick="previewStyle('footer','{{ $key }}')">
                            <input type="radio" name="footer_style" value="{{ $key }}" {{ ($setting->footer_style ?? 'classic') == $key ? 'checked' : '' }} class="d-none">
                            <div class="text-center">
                                <i class="mdi {{ $fIcons[$key] ?? 'mdi-page-layout-footer' }} style-icon"></i>
                                <h6 class="mt-2 mb-1">{{ $name }}</h6>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== FOOTER PREVIEW ===== --}}
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="mdi mdi-eye me-2"></i>Footer Preview</h5>
                <span class="badge bg-primary" id="footer-preview-label">Classic</span>
            </div>
            <div class="card-body p-0">
                <div class="preview-frame" id="footer-preview">
                    <div class="d-flex justify-content-center align-items-center h-100 text-muted">
                        <i class="mdi mdi-refresh mdi-spin fs-3 me-2"></i> Select a footer style to preview
                    </div>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="text-end mb-4">
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="mdi mdi-content-save"></i> Save Header & Footer
            </button>
        </div>
    </form>
</div>

@push('script')
<script>
    function previewStyle(type, style) {
        var container = document.getElementById(type + '-preview');
        var label = document.getElementById(type + '-preview-label');
        label.textContent = style.charAt(0).toUpperCase() + style.slice(1);
        container.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100"><div class="spinner-border text-primary"></div></div>';

        // Highlight selected card
        document.querySelectorAll('input[name="' + type + '_style"]').forEach(function(r){
            r.closest('.style-card').classList.remove('active');
            if (r.value === style) r.closest('.style-card').classList.add('active');
        });

        fetch('{{ route("headerfooter.preview") }}', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
            body: JSON.stringify({type:type, style:style})
        })
        .then(r => r.json())
        .then(data => {
            container.innerHTML = '<iframe srcdoc="' + data.html.replace(/"/g,'&quot;') + '"></iframe>';
        })
        .catch(() => {
            container.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100 text-danger">Preview failed</div>';
        });
    }

    // Auto-preview default
    document.addEventListener('DOMContentLoaded', function(){
        var hStyle = document.querySelector('input[name="header_style"]:checked');
        var fStyle = document.querySelector('input[name="footer_style"]:checked');
        if (hStyle) previewStyle('header', hStyle.value);
        if (fStyle) previewStyle('footer', fStyle.value);
    });
</script>
@endpush
@endsection
