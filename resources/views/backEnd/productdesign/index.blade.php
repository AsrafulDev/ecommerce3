@extends('backEnd.layouts.master')
@section('title','Product Design')

@section('css')
<style>
    :root {
        --pd-radius: 16px;
        --pd-shadow: 0 4px 20px rgba(0,0,0,0.06);
        --pd-hover-shadow: 0 8px 32px rgba(0,0,0,0.12);
    }

    .page-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 20px;
        padding: 30px 35px;
        margin-bottom: 30px;
    }
    .page-header h4 { color: #fff; font-weight: 700; font-size: 22px; }
    .page-header p { color: #94a3b8; margin: 0; font-size: 14px; }

    .pd-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 24px;
        margin-top: 10px;
    }
    .pd-card {
        background: #fff;
        border-radius: var(--pd-radius);
        box-shadow: var(--pd-shadow);
        border: 2px solid #e2e8f0;
        overflow: hidden;
        transition: all .3s ease;
        position: relative;
        cursor: pointer;
    }
    .pd-card:hover { box-shadow: var(--pd-hover-shadow); transform: translateY(-4px); }
    .pd-card.active-design { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,.25), var(--pd-hover-shadow); }

    .pd-active-ribbon {
        position: absolute;
        top: 12px;
        left: -34px;
        background: #22c55e;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 36px;
        transform: rotate(-45deg);
        z-index: 5;
        box-shadow: 0 2px 6px rgba(0,0,0,.2);
    }
    .pd-preview-wrap {
        background: linear-gradient(135deg, #f8fafc, #eef2f7);
        padding: 22px 18px;
        display: flex;
        justify-content: center;
        min-height: 250px;
    }
    .pd-meta { padding: 16px 18px; border-top: 1px solid #f1f5f9; }
    .pd-meta h5 { font-size: 15px; font-weight: 700; margin: 0 0 2px; color: #0f172a; }
    .pd-meta small { color: #64748b; }

    .pd-radio-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
    .pd-radio-row input[type="radio"] { width: 17px; height: 17px; accent-color: #3b82f6; }

    /* ---------- Row limit settings panel ---------- */
    .pc-settings-card { background:#fff; border-radius:20px; box-shadow:var(--pd-shadow); border:1px solid #e2e8f0; margin-bottom:26px; overflow:hidden; }
    .pc-settings-head { background:linear-gradient(135deg,#0f172a,#1e293b); padding:20px 26px; display:flex; align-items:center; gap:14px; }
    .pc-settings-head .pc-head-icon { width:44px; height:44px; border-radius:12px; background:rgba(255,255,255,.1); display:flex; align-items:center; justify-content:center; font-size:20px; color:#38bdf8; }
    .pc-settings-head h5 { color:#fff; font-weight:700; margin:0; font-size:16px; }
    .pc-settings-head small { color:#94a3b8; }
    .pc-settings-body { padding:22px 26px; }
    .pc-col-group { background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:16px 18px; height:100%; }
    .pc-col-group h6 { font-weight:700; color:#0f172a; margin-bottom:4px; font-size:14px; }
    .pc-col-group small { color:#64748b; }
    .pc-device-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px; margin-top:14px; }
    .pc-device-item { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:12px; text-align:center; transition:all .2s; }
    .pc-device-item:hover { border-color:#3b82f6; box-shadow:0 4px 12px rgba(59,130,246,.12); }
    .pc-device-item .pc-dev-icon { font-size:20px; color:#3b82f6; margin-bottom:4px; }
    .pc-device-item label { display:block; font-size:11px; font-weight:600; color:#475569; text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; }
    .pc-device-item input[type="number"] {
        width:100%; text-align:center; font-size:18px; font-weight:700; color:#0f172a;
        border:1.5px solid #cbd5e1; border-radius:8px; padding:6px 4px; outline:none; transition:border .2s;
        -moz-appearance:textfield; appearance:textfield;
    }
    .pc-device-item input[type="number"]::-webkit-outer-spin-button,
    .pc-device-item input[type="number"]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
    .pc-device-item input[type="number"]:focus { border-color:#3b82f6; }
    .pc-title-lines-wrap { margin-top:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:16px 18px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
    .pc-title-lines-wrap label { font-weight:700; color:#0f172a; font-size:14px; }
    .pc-title-lines-wrap small { color:#64748b; display:block; }
    .pc-title-lines-wrap input[type="number"] {
        width:80px; text-align:center; font-size:18px; font-weight:700; color:#0f172a;
        border:1.5px solid #cbd5e1; border-radius:8px; padding:6px 4px; outline:none;
        -moz-appearance:textfield; appearance:textfield;
    }
    .pc-title-lines-wrap input[type="number"]::-webkit-outer-spin-button,
    .pc-title-lines-wrap input[type="number"]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }

    /* Miniature product card used in previews */
    .pd-mini { width: 170px; }
    .pd-mini .pd-img { height: 95px; background: #fff; display:flex; align-items:center; justify-content:center; font-size: 40px; color:#94a3b8; }
    .pd-mini .pd-name { font-size: 12px; font-weight: 600; margin-top: 6px; line-height: 1.3; }
    .pd-mini .pd-price { font-size: 13px; font-weight: 700; margin-top: 4px; }
    .pd-mini .pd-btns { display: flex; gap: 5px; margin-top: 8px; }
    .pd-mini .pd-btns span { flex: 1; font-size: 10px; text-align: center; padding: 5px 0; color: #fff; background: var(--admin-primary, #d32f2f); border-radius: 4px; }
    .pd-mini .pd-btns i { flex: 0 0 30px; font-size: 11px; display:flex; align-items:center; justify-content:center; background:#eee; border-radius: 4px; color:#333; }
    .pd-mini .pd-badge { position: relative; width: 0; height: 0; }
    .pd-mini .pd-badge span { position: absolute; top: -6px; right: -10px; font-size: 9px; font-weight:700; color:#fff; background:var(--admin-accent, #FF0034); border-radius: 50%; width: 30px; height: 30px; display:flex; align-items:center; justify-content:center; }

    /* ---------- Structural preview pieces (premium / overlay / ribbon / glass) ---------- */
    .pd-mini { position: relative; }
    .pd-mini-media { position: relative; overflow: hidden; border-radius: 8px; }
    .pd-mini-img { height: 95px; background: #fff; display:flex; align-items:center; justify-content:center; font-size: 40px; color:#94a3b8; }
    .pd-mini-img i { font-size: 34px; color: #cbd5e1; }
    .pd-mini-badge-mini {
        position:absolute; top:6px; left:6px; z-index:3;
        background:#e53935; color:#fff; font-size:9px; font-weight:700;
        padding:2px 7px; border-radius:20px; box-shadow:0 2px 6px rgba(0,0,0,.2);
    }
    .pd-mini-actions { position:absolute; top:6px; right:6px; z-index:3; display:flex; flex-direction:column; gap:5px; }
    .pd-mini-actions i {
        width:24px; height:24px; display:flex; align-items:center; justify-content:center;
        background:#fff; color:#64748b; border-radius:50%; font-size:10px;
        box-shadow:0 2px 6px rgba(0,0,0,.18);
    }
    .pd-mini-stars { color:#f5b301; font-size:9px; letter-spacing:1px; line-height:1; }
    .pd-mini-name { font-size:11px; font-weight:600; line-height:1.3; color:#0f172a; margin-top:3px; }
    .pd-mini-price { font-size:12px; margin-top:3px; }
    .pd-mini-price del { color:#94a3b8; font-weight:400; font-size:10px; margin-right:4px; }
    .pd-mini-price b { color:#e53935; font-weight:800; }
    .pd-mini-bar { 
        display:flex; gap:5px; margin-top:8px; 
        display:none; /* hide by default, on hover show buttons */
        }
    .pd-mini-bar span { flex:1; font-size:10px; text-align:center; padding:5px 0; color:#fff; background:var(--admin-primary, #d32f2f); border-radius:6px; }
    .pd-mini-bar i { flex:0 0 30px; font-size:11px; display:flex; align-items:center; justify-content:center; background:#eee; border-radius:6px; color:#333; }

    /* ---------- Design-specific mini previews (mirror the frontend) ---------- */
    /* Premium (new default) — layered card, floating circular quick-actions, split action bar */
    body .pd-preview-default .pd-mini {
        border:1px solid var(--admin-border-color, #e2e8f0); border-radius:12px;
        background:var(--admin-card-bg, #fff); box-shadow:var(--admin-card-shadow, 0 4px 20px rgba(0,0,0,.08));
        overflow:hidden; text-align:left; padding:0;
    }
    body .pd-preview-default .pd-mini .pd-mini-media { margin:6px; }
    body .pd-preview-default .pd-mini .pd-mini-img { height:84px; border-radius:8px; }
    body .pd-preview-default .pd-mini .pd-mini-body { padding:2px 12px 0; }
    body .pd-preview-default .pd-mini .pd-mini-name { min-height:26px; }
    body .pd-preview-default .pd-mini .pd-mini-bar { border-top:1px solid var(--admin-border-color, #f1f5f9); margin:8px 12px 10px; padding-top:8px; }

    /* Overlay — full-bleed image, info panel slides over bottom */
    body .pd-preview-overlay .pd-mini {
        border:1px solid var(--admin-border-color, #e2e8f0); border-radius:12px;
        background:var(--admin-card-bg, #fff); overflow:hidden; box-shadow:var(--admin-card-shadow, 0 4px 20px rgba(0,0,0,.08));
        padding:0;
    }
    body .pd-preview-overlay .pd-mini .pd-mini-img { height:110px; border-radius:0; }
    body .pd-preview-overlay .pd-mini .pd-mini-actions { flex-direction:row; top:10px; left:50%; transform:translateX(-50%); right:auto; }
    body .pd-preview-overlay .pd-mini .pd-mini-panel {
        position:absolute; left:0; right:0; bottom:0; z-index:2;
        background:#fff; padding:10px 12px 12px; text-align:center;
        border-top:1px solid var(--admin-border-color, #f1f5f9);
    }
    body .pd-preview-overlay .pd-mini .pd-mini-name { font-size:10px; }

    /* Ribbon — triangular pennant + centered body */
    body .pd-preview-ribbon .pd-mini {
        border:1px solid var(--admin-border-color, #e2e8f0); border-radius:12px;
        background:var(--admin-card-bg, #fff); box-shadow:var(--admin-card-shadow, 0 4px 20px rgba(0,0,0,.08));
        overflow:hidden; text-align:center; padding:0;
    }
    body .pd-preview-ribbon .pd-mini .pd-mini-rib {
        position:absolute; top:0; right:14px; z-index:4; width:0; height:0;
        border-left:24px solid transparent; border-right:24px solid transparent;
        border-top:38px solid #e53935;
    }
    body .pd-preview-ribbon .pd-mini .pd-mini-rib span {
        position:absolute; top:-32px; left:-13px; width:26px; text-align:center;
        color:#fff; font-size:9px; font-weight:800;
    }
    body .pd-preview-ribbon .pd-mini .pd-mini-img { height:86px; border-radius:0; }
    body .pd-preview-ribbon .pd-mini .pd-mini-body { padding:10px 10px 0; }
    body .pd-preview-ribbon .pd-mini .pd-mini-bar { padding:0 10px 10px; }

    /* Glassmorphism — frosted bar + floating FAB */
    body .pd-preview-glass .pd-mini {
        border:1px solid var(--admin-border-color, #e2e8f0); border-radius:14px;
        background:var(--admin-card-bg, #fff); box-shadow:var(--admin-card-shadow, 0 4px 20px rgba(0,0,0,.08));
        overflow:hidden; padding:0;
    }
    body .pd-preview-glass .pd-mini .pd-mini-img { height:110px; border-radius:0; }
    body .pd-preview-glass .pd-mini .pd-mini-fab {
        position:absolute; top:8px; right:8px; z-index:3;
        width:28px; height:28px; display:flex; align-items:center; justify-content:center;
        background:var(--admin-primary, #d32f2f); color:#fff; border-radius:50%; font-size:12px;
        box-shadow:0 3px 8px rgba(0,0,0,.25);
    }
    body .pd-preview-glass .pd-mini .pd-mini-glassbar {
        position:absolute; left:6px; right:6px; bottom:6px; z-index:2;
        background:rgba(255,255,255,.85); border:1px solid rgba(255,255,255,.6);
        border-radius:10px; padding:8px 10px; box-shadow:0 4px 14px rgba(0,0,0,.12);
        backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    }
    body .pd-preview-glass .pd-mini .pd-mini-name { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    body .pd-preview-glass .pd-mini .pd-mini-price b { font-size:12px; }

    /* Legacy (Original) — the original classic card, unchanged */
    body .pd-preview-legacy .pd-mini { border:1px solid var(--admin-border-color, #ddd); border-radius:5px; background:var(--admin-card-bg, #fff); padding:5px; }
    body .pd-preview-legacy .pd-mini .pd-img { height:85px; }
    body .pd-preview-legacy .pd-mini .pd-name { color:var(--admin-text-color, #000); text-transform:capitalize; }
    body .pd-preview-legacy .pd-mini .pd-price { color:var(--admin-primary, #0d6efd); }
    body .pd-preview-legacy .pd-mini .pd-price del { color:var(--admin-secondary, blue); font-weight:400; margin-right:3px; font-size:11px; }
    body .pd-preview-legacy .pd-mini .pd-btns span { background:var(--admin-primary, #d32f2f); }
    /* Default */
    body .pd-preview-default .pd-mini { border:1px solid var(--admin-border-color, #ddd); border-radius:5px; background:var(--admin-card-bg, #fff); padding:5px; }
    body .pd-preview-default .pd-mini .pd-img { height:85px; }
    body .pd-preview-default .pd-mini .pd-name { color:var(--admin-text-color, #000); text-transform:capitalize; }
    body .pd-preview-default .pd-mini .pd-price { color:var(--admin-primary, #0d6efd); }
    body .pd-preview-default .pd-mini .pd-price del { color:var(--admin-secondary, blue); font-weight:400; margin-right:3px; font-size:11px; }
    body .pd-preview-default .pd-mini .pd-btns span { background:var(--admin-primary, #d32f2f); }

    /* Minimal */
    body .pd-preview-minimal .pd-mini { border:none; border-radius:0; background:transparent; box-shadow:var(--admin-card-shadow, 0 4px 16px rgba(0,0,0,.08)); overflow:hidden; padding-bottom:6px; }
    body .pd-preview-minimal .pd-mini .pd-img { height:90px; }
    body .pd-preview-minimal .pd-mini .pd-name { color:var(--admin-text-color, #212529); font-weight:600; }
    body .pd-preview-minimal .pd-mini .pd-price { color:var(--admin-primary, #0d6efd); }
    body .pd-preview-minimal .pd-mini .pd-price del { color:var(--admin-text-color, #adb5bd); opacity:.6; font-weight:400; font-size:11px; }
    body .pd-preview-minimal .pd-mini .pd-btns span { background:var(--admin-primary, #0d6efd); border-radius:6px; }
    body .pd-preview-minimal .pd-mini .pd-btns i { background:var(--admin-primary, #0d6efd); color:#fff; border-radius:6px; }

    /* Classic */
    body .pd-preview-classic .pd-mini { border:2px solid var(--admin-border-color, #dee2e6); border-radius:0; background:var(--admin-card-bg, #fff); padding:8px; }
    body .pd-preview-classic .pd-mini .pd-img { height:84px; }
    body .pd-preview-classic .pd-mini .pd-name { color:var(--admin-heading-color, #111); text-transform:uppercase; font-weight:700; font-size:11px; letter-spacing:.3px; }
    body .pd-preview-classic .pd-mini .pd-price { color:var(--admin-heading-color, #111); }
    body .pd-preview-classic .pd-mini .pd-price del { color:var(--admin-text-color, #6c757d); font-weight:400; font-size:11px; }
    body .pd-preview-classic .pd-mini .pd-btns span { background:var(--admin-primary, #111); border-radius:0; text-transform:uppercase; }
    body .pd-preview-classic .pd-mini .pd-btns i { border:2px solid var(--admin-heading-color, #111); color:var(--admin-heading-color, #111); border-radius:0; }
    body .pd-preview-classic .pd-mini .pd-badge span { border-radius:0; padding:2px 8px; width:auto; height:auto; top:2px; right:auto; left:-4px; background:var(--admin-accent, #dc3545); }

    /* Dark */
    body .pd-preview-dark .pd-mini { border:1px solid var(--admin-border-color, #2a2a2a); border-radius:10px; background:var(--admin-footer-bg, #1e1e1e); padding:8px; box-shadow:0 6px 16px rgba(0,0,0,.35); }
    body .pd-preview-dark .pd-mini .pd-img { height:82px; background:#2a2a2a; color:#6b6b6b; }
    body .pd-preview-dark .pd-mini .pd-name { color:var(--admin-footer-text, #f1f1f1); }
    body .pd-preview-dark .pd-mini .pd-price { color:var(--admin-accent, #ff6a00); }
    body .pd-preview-dark .pd-mini .pd-price del { color:var(--admin-footer-text, #8b8b8b); opacity:.7; font-weight:400; font-size:11px; }
    body .pd-preview-dark .pd-mini .pd-btns span { background:var(--admin-accent, #ff6a00); border-radius:6px; }
    body .pd-preview-dark .pd-mini .pd-btns i { background:#333; color:var(--admin-accent, #ff6a00); border-radius:6px; }
    body .pd-preview-dark .pd-mini .pd-badge span { background:var(--admin-accent, #ff6a00); border:2px solid #fff; }

    /* Rounded */
    body .pd-preview-rounded .pd-mini { border:1px solid var(--admin-border-color, #f0f0f0); border-radius:20px; background:var(--admin-card-bg, #fff); padding:10px; box-shadow:var(--admin-card-shadow, 0 8px 22px rgba(0,0,0,.08)); }
    body .pd-preview-rounded .pd-mini .pd-img { height:80px; border-radius:12px; background:#f7f7ff; }
    body .pd-preview-rounded .pd-mini .pd-name { color:var(--admin-text-color, #333); }
    body .pd-preview-rounded .pd-mini .pd-price { color:var(--admin-primary, #0d6efd); }
    body .pd-preview-rounded .pd-mini .pd-price del { color:var(--admin-text-color, #adb5bd); opacity:.5; font-weight:400; font-size:11px; }
    body .pd-preview-rounded .pd-mini .pd-btns span { background:var(--admin-primary, #0d6efd); border-radius:999px; }
    body .pd-preview-rounded .pd-mini .pd-btns i { background:var(--admin-primary, #eef2ff); opacity:.15; color:var(--admin-primary, #0d6efd); border-radius:999px; }
    body .pd-preview-rounded .pd-mini .pd-badge span { background:linear-gradient(135deg, var(--admin-accent, #f7971e), var(--admin-accent, #ffd200)); color:#7a4b00; }

    /* Gradient */
    body .pd-preview-gradient .pd-mini { border-radius:12px; padding:5px;
        background: linear-gradient(var(--admin-card-bg, #fff), var(--admin-card-bg, #fff)) padding-box, linear-gradient(135deg, var(--admin-primary, #667eea), var(--admin-secondary, #764ba2), var(--admin-accent, #f093fb)) border-box;
        border:1px solid transparent; box-shadow:var(--admin-card-shadow, 0 6px 18px rgba(0,0,0,.15)); }
    body .pd-preview-gradient .pd-mini .pd-img { height:84px; border-radius:8px; }
    body .pd-preview-gradient .pd-mini .pd-name { color:var(--admin-text-color, #333); font-weight:700; }
    body .pd-preview-gradient .pd-mini .pd-price { color:var(--admin-primary, #667eea); }
    body .pd-preview-gradient .pd-mini .pd-price del { color:var(--admin-text-color, #adb5bd); opacity:.5; font-weight:400; font-size:11px; }
    body .pd-preview-gradient .pd-mini .pd-btns span { background:linear-gradient(135deg, var(--admin-primary, #667eea), var(--admin-secondary, #764ba2)); border-radius:8px; }
    body .pd-preview-gradient .pd-mini .pd-btns i { background:linear-gradient(135deg, var(--admin-primary, #667eea), var(--admin-secondary, #764ba2)); color:#fff; border-radius:8px; }
    body .pd-preview-gradient .pd-mini .pd-badge span { background:linear-gradient(135deg, var(--admin-primary, #667eea), var(--admin-secondary, #764ba2)); }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h4><i data-feather="grid"></i> Product Design</h4>
            <p>Choose how product cards look across the entire storefront. Changes apply instantly to all pages.</p>
        </div>
    </div>

    <form action="{{ route('product.design.save') }}" method="POST">
        @csrf

        {{-- ⚙️ Products Per Row & Title Limit Settings --}}
        @php
            $devices = [
                'desktop' => ['fa-desktop', 'Desktop', '≥1280px'],
                'laptop'  => ['fa-laptop', 'Laptop', '992–1279px'],
                'tablet'  => ['fa-tablet-screen-button', 'Tablet', '576–991px'],
                'phone'   => ['fa-mobile-screen', 'Phone', '<576px'],
            ];
            $deviceDefaultsHome  = \App\Http\Controllers\Admin\ProductDesignController::ROW_LIMIT_FIELDS_HOME;
            $deviceDefaultsOther = \App\Http\Controllers\Admin\ProductDesignController::ROW_LIMIT_FIELDS_OTHER;
        @endphp
        <div class="pc-settings-card">
            <div class="pc-settings-head">
                <div class="pc-head-icon"><i class="fa-solid fa-table-columns"></i></div>
                <div>
                    <h5>Products Per Row (Responsive)</h5>
                    <small>How many product cards fit in one row per device. Front page and other pages (shop, category, brand, search, related, account) can be different because sidebar pages are narrower.</small>
                </div>
            </div>
            <div class="pc-settings-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="pc-col-group">
                            <h6>🏠 Front Page <small class="d-block">(home — no sidebar)</small></h6>
                            <div class="pc-device-grid">
                                @foreach($devices as $devKey => [$icon, $label, $bp])
                                    <div class="pc-device-item">
                                        <div class="pc-dev-icon"><i class="fa-solid {{ $icon }}"></i></div>
                                        <label>{{ $label }}</label>
                                        <input type="number" name="pc_home_{{ $devKey }}" min="1" max="8"
                                            value="{{ $setting->{'pc_home_'.$devKey} ?? $deviceDefaultsHome[$devKey] }}" />
                                        <small style="color:#94a3b8;font-size:10px;">{{ $bp }}</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="pc-col-group">
                            <h6>🛒 Other Pages <small class="d-block">(shop, category, brand, search, related, account)</small></h6>
                            <div class="pc-device-grid">
                                @foreach($devices as $devKey => [$icon, $label, $bp])
                                    <div class="pc-device-item">
                                        <div class="pc-dev-icon"><i class="fa-solid {{ $icon }}"></i></div>
                                        <label>{{ $label }}</label>
                                        <input type="number" name="pc_other_{{ $devKey }}" min="1" max="8"
                                            value="{{ $setting->{'pc_other_'.$devKey} ?? $deviceDefaultsOther[$devKey] }}" />
                                        <small style="color:#94a3b8;font-size:10px;">{{ $bp }}</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pc-title-lines-wrap">
                    <div>
                        <label>Product Title Lines</label>
                        <small>Maximum number of lines for the product name inside every card (default 2).</small>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;margin-left:auto;">
                        <i class="fa-solid fa-text-height" style="color:#3b82f6;font-size:18px;"></i>
                        <input type="number" name="pc_title_lines" min="1" max="5" value="{{ $setting->pc_title_lines ?? 2 }}" />
                    </div>
                </div>
                <div class="pc-title-lines-wrap">
                    <div>
                        <label>Product Image Height (px)</label>
                        <small>Fixed image area height for every card. Wider screens look best with a taller image (default 200).</small>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;margin-left:auto;">
                        <i class="fa-solid fa-image" style="color:#8b5cf6;font-size:18px;"></i>
                        <input type="number" name="pc_image_height" min="80" max="500" step="10" value="{{ $setting->pc_image_height ?? 200 }}" />
                        <small style="color:#94a3b8;font-size:11px;">px</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="pd-grid">
            @foreach($designs as $key => $label)
                <label class="pd-card {{ $activeDesign === $key ? 'active-design' : '' }} pd-preview-{{ $key }}">

                    @if($activeDesign === $key)
                        <div class="pd-active-ribbon">ACTIVE</div>
                    @endif

                    <div class="pd-preview-wrap">
                        @if(in_array($key, ['default','overlay','ribbon','glass']))
                            @if($key === 'default')
                                {{-- Premium: layered card + circular quick-actions + split bar --}}
                                <div class="pd-mini">
                                    <div class="pd-mini-media">
                                        <span class="pd-mini-badge-mini">-10%</span>
                                        <div class="pd-mini-actions">
                                            <i class="fa-solid fa-eye"></i>
                                            <i class="fa-solid fa-cart-plus"></i>
                                        </div>
                                        <div class="pd-mini-img"><i class="fa-solid fa-cart-shopping"></i></div>
                                    </div>
                                    <div class="pd-mini-body">
                                        <div class="pd-mini-stars">★★★★★</div>
                                        <div class="pd-mini-name">Premium Wireless Headphones</div>
                                        <div class="pd-mini-price"><del>৳ 2500</del> <b>৳ 2250</b></div>
                                    </div>
                                    <div class="pd-mini-bar"><span>Order Now</span><i class="fa-solid fa-cart-plus"></i></div>
                                </div>
                            @elseif($key === 'overlay')
                                {{-- Overlay: full-bleed image + panel --}}
                                <div class="pd-mini">
                                    <div class="pd-mini-img"><i class="fa-solid fa-cart-shopping"></i></div>
                                    <div class="pd-mini-actions">
                                        <i class="fa-solid fa-eye"></i>
                                        <i class="fa-solid fa-cart-plus"></i>
                                    </div>
                                    <div class="pd-mini-panel">
                                        <div class="pd-mini-stars">★★★★★</div>
                                        <div class="pd-mini-name">Premium Wireless Headphones</div>
                                        <div class="pd-mini-price"><del>৳ 2500</del> <b>৳ 2250</b></div>
                                    </div>
                                </div>
                            @elseif($key === 'ribbon')
                                {{-- Ribbon: pennant badge + centered body --}}
                                <div class="pd-mini">
                                    <div class="pd-mini-rib"><span>-10%</span></div>
                                    <div class="pd-mini-img"><i class="fa-solid fa-cart-shopping"></i></div>
                                    <div class="pd-mini-body">
                                        <div class="pd-mini-stars">★★★★★</div>
                                        <div class="pd-mini-name">Premium Wireless Headphones</div>
                                        <div class="pd-mini-price"><del>৳ 2500</del> <b>৳ 2250</b></div>
                                    </div>
                                    <div class="pd-mini-bar"><span>Order Now</span><i class="fa-solid fa-cart-plus"></i></div>
                                </div>
                            @else
                                {{-- Glass: frosted bar + floating FAB --}}
                                <div class="pd-mini">
                                    <div class="pd-mini-img"><i class="fa-solid fa-cart-shopping"></i></div>
                                    <span class="pd-mini-badge-mini">-10%</span>
                                    <i class="fa-solid fa-cart-plus pd-mini-fab"></i>
                                    <div class="pd-mini-glassbar">
                                        <div class="pd-mini-stars">★★★★★</div>
                                        <div class="pd-mini-name">Premium Wireless Headphones</div>
                                        <div class="pd-mini-price"><del>৳ 2500</del> <b>৳ 2250</b></div>
                                    </div>
                                </div>
                            @endif
                        @else
                            {{-- Classic-family previews --}}
                            <div class="pd-mini">
                                <div class="pd-badge"><span>10%<br>Sale</span></div>
                                <div class="pd-img"><i class="fa-solid fa-cart-shopping"></i></div>
                                <div class="pd-name">Premium Wireless Headphones</div>
                                <div class="pd-price"><del>৳ 2500</del> ৳ 2250</div>
                                <div class="pd-btns">
                                    <span>Order Now</span>
                                    <i class="fa-solid fa-cart-plus"></i>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="pd-meta">
                        <h5>{{ $label }}</h5>
                        <small>
                            @php
                                $descs = [
                                    'default'  => 'Premium layered card with circular quick-actions & gradient price — the new default.',
                                    'overlay'  => 'Full-bleed image with an info panel that slides up on hover.',
                                    'ribbon'   => 'Pennant ribbon badge, centered body & action bar.',
                                    'glass'    => 'Frosted-glass info bar over the image with a floating cart button.',
                                    'legacy'   => 'The original card (previously "Default"), kept unchanged.',
                                    'minimal'  => 'Borderless, soft shadow, cover image.',
                                    'classic'  => 'Bold border, square corners, uppercase name.',
                                    'dark'     => 'Dark card with light text & accent buttons.',
                                    'rounded'  => 'Big radius, pastel shadow, pill buttons.',
                                    'gradient' => 'Gradient border & buttons with hover lift.',
                                ];
                            @endphp
                            {{ $descs[$key] ?? '' }}
                        </small>
                        <div class="pd-radio-row">
                            <input type="radio" name="style" value="{{ $key }}"
                                {{ $activeDesign === $key ? 'checked' : '' }} />
                            <span><strong>{{ $activeDesign === $key ? 'Active' : 'Select' }}</strong> this design</span>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="text-end mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i data-feather="save"></i> Save Design
            </button>
        </div>
    </form>
</div>
@endsection
