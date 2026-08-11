<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />

    <title>@yield('title')@if(isset($generalsetting) && $generalsetting) - {{$generalsetting->name}}@endif</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{asset(isset($generalsetting->favicon) ? $generalsetting->favicon : 'public/backEnd/assets/images/favicon.ico')}}" />

    <!-- Bootstrap css -->
    <link href="{{asset('public/backEnd/')}}/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- App css -->
    <link href="{{asset('public/backEnd/')}}/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- icons -->
    <link href="{{asset('public/backEnd/')}}/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- toastr css -->
    <link rel="stylesheet" href="{{asset('public/backEnd/')}}/assets/css/toastr.min.css" />
    <!-- SweetAlert2 - ডেমো মুড পপআপের জন্য -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <!-- custom css -->
    <link href="{{asset('public/backEnd/')}}/assets/css/custom.css" rel="stylesheet" type="text/css" />
    <!-- Head js -->
    @yield('css')
    
    {{-- 🎨 Dynamic Admin Theme from System Theme --}}
    @if(isset($activeTheme) && $activeTheme)
    @php
        // ── Compute safe, contrast-aware colors from theme ──
        $sidebarBg  = $activeTheme->sidebar_bg_color ?? $activeTheme->header_bg_color ?? '#1e293b';
        $topbarBg   = $activeTheme->topbar_bg_color ?? $activeTheme->header_bg_color ?? '#0f172a';
        $bodyBg     = $activeTheme->body_bg_color ?? '#f8f9fa';
        $cardBg     = $activeTheme->admin_card_bg ?? '#ffffff';
        $footerBg   = $activeTheme->footer_bg_color ?? '#f8fafc';
        
        // Text colors — ALWAYS validated against their background for contrast
        $sidebarText = $activeTheme->sidebar_text_color 
            ? ensure_text_contrast($activeTheme->sidebar_text_color, $sidebarBg) 
            : get_contrast_color($sidebarBg);
        $headingClr = $activeTheme->heading_color 
            ? ensure_text_contrast($activeTheme->heading_color, $bodyBg) 
            : get_contrast_color($bodyBg);
        $bodyText   = $activeTheme->text_color 
            ? ensure_text_contrast($activeTheme->text_color, $bodyBg) 
            : get_contrast_color($bodyBg);
        $topbarText = get_contrast_color($topbarBg);
        
        $sidebarIsDark = is_color_dark($sidebarBg);
        $topbarIsDark  = is_color_dark($topbarBg);
        $bodyIsDark    = is_color_dark($bodyBg);
        $cardIsDark    = is_color_dark($cardBg);
    @endphp
    <style>
        :root {
            --admin-primary: {{ $activeTheme->primary_color ?? '#6658dd' }};
            --admin-secondary: {{ $activeTheme->secondary_color ?? '#04a31e' }};
            --admin-accent: {{ $activeTheme->accent_color ?? '#eab308' }};
            --admin-sidebar-bg: {{ $sidebarBg }};
            --admin-sidebar-text: {{ $sidebarText }};
            --admin-sidebar-is-dark: {{ $sidebarIsDark ? '1' : '0' }};
            --admin-topbar-bg: {{ $topbarBg }};
            --admin-topbar-text: {{ $topbarText }};
            --admin-topbar-is-dark: {{ $topbarIsDark ? '1' : '0' }};
            --admin-footer-bg: {{ $footerBg }};
            --admin-card-bg: {{ $cardBg }};
            --admin-card-is-dark: {{ $cardIsDark ? '1' : '0' }};
            --admin-card-shadow: 0 2px 12px rgba(0,0,0,0.06);
            --admin-border-color: {{ $activeTheme->border_color ?? '#e2e8f0' }};
            --admin-text-color: {{ $bodyText }};
            --admin-body-bg: {{ $bodyBg }};
            --admin-body-is-dark: {{ $bodyIsDark ? '1' : '0' }};
            --admin-heading-color: {{ $headingClr }};
            --admin-button-hover: {{ $activeTheme->button_hover_bg_color ?? '#0b5ed7' }};
        }
        /* ── Override app.min.css CSS Custom Properties ── */
        :root {
            --ct-component-active-bg: var(--admin-primary);
            --ct-link-color: var(--admin-primary);
            --ct-link-hover-color: var(--admin-primary);
            --ct-progress-bar-bg: var(--admin-primary);
            --ct-btn-link-color: var(--admin-primary);
            --ct-twocolumn-sidebar-iconview-bg: var(--admin-primary);
            --ct-input-btn-focus-color: color-mix(in srgb, var(--admin-primary) 25%, transparent);
            --ct-menu-item-hover: var(--admin-secondary);
            --ct-menu-item-active: var(--admin-secondary);
            --ct-menu-sub-item-active: var(--admin-secondary);
            --ct-menuitem-active-bg: color-mix(in srgb, var(--admin-secondary) 7%, transparent);
            --ct-bg-leftbar: var(--admin-sidebar-bg);
            --ct-form-range-thumb-active-bg: color-mix(in srgb, var(--admin-primary) 25%, transparent);
            --ct-bg-topbar-light: var(--admin-topbar-bg);
            --ct-form-check-input-checked-bg-color: var(--admin-primary);
            --ct-form-check-input-checked-border-color: var(--admin-primary);
            --ct-form-check-input-indeterminate-bg-color: var(--admin-primary);
            --ct-form-check-input-indeterminate-border-color: var(--admin-primary);
        }
        /* ── Modern Sidebar UI ── */
        #sidebar-menu .menuitem-active > a { color: var(--admin-secondary) !important; }
        #sidebar-menu > ul > li > a:hover { color: var(--admin-secondary) !important; }
        .left-side-menu { background-color: var(--admin-sidebar-bg) !important; }
        #sidebar-menu > ul > li > a { color: var(--admin-sidebar-text) !important; }
        .navbar-custom { background-color: var(--admin-topbar-bg) !important; }
        .footer { background-color: var(--admin-footer-bg) !important; }
        .btn-primary { background-color: var(--admin-primary) !important; border-color: var(--admin-primary) !important; }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active { background-color: var(--admin-button-hover) !important; border-color: var(--admin-button-hover) !important; }
        .btn-success { background-color: var(--admin-secondary) !important; border-color: var(--admin-secondary) !important; }
        .btn-success:hover, .btn-success:focus, .btn-success:active { background-color: color-mix(in srgb, var(--admin-secondary) 85%, #000) !important; border-color: var(--admin-secondary) !important; }
        .btn-info { background-color: var(--admin-accent) !important; border-color: var(--admin-accent) !important; }
        .btn-outline-primary { color: var(--admin-primary) !important; border-color: var(--admin-primary) !important; }
        .btn-outline-primary:hover { background-color: var(--admin-primary) !important; border-color: var(--admin-primary) !important; color: #fff !important; }
        .page-title-box .page-title { color: var(--admin-heading-color) !important; }
        a { color: var(--admin-primary); }
        a:hover { color: var(--admin-button-hover); }
        .card { border-color: var(--admin-border-color) !important; background-color: var(--admin-card-bg) !important; }
        .card-header { background-color: color-mix(in srgb, var(--admin-card-bg) 97%, #000) !important; border-bottom-color: var(--admin-border-color) !important; }
        body { background-color: var(--admin-body-bg) !important; color: var(--admin-text-color) !important; }
        .text-muted { color: color-mix(in srgb, var(--admin-text-color) 80%, transparent) !important; }
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 { color: var(--admin-heading-color) !important; }

        /* ═══════════════════════════════════════════════
           ── Sidebar: Base Layout ──
           ═══════════════════════════════════════════════ */
        .left-side-menu {
            box-shadow: 2px 0 24px rgba(0,0,0,0.06);
            border-right: 1px solid rgba(128,128,128,0.15);
        }
        #side-menu > li { margin: 2px 8px; border-radius: 10px; overflow: hidden; }
        #side-menu > li > a {
            display: flex !important; align-items: center; gap: 12px;
            padding: 11px 16px !important; border-radius: 10px;
            font-size: 0.9rem; font-weight: 500;
            transition: all 0.2s ease; position: relative;
        }
        #side-menu > li > a i, #side-menu > li > a svg {
            width: 20px; height: 20px; flex-shrink: 0; opacity: 0.65;
            transition: opacity 0.2s;
        }
        #side-menu > li > a:hover i, #side-menu > li > a:hover svg { opacity: 1; }
        .menu-arrow { margin-left: auto; font-size: 10px; opacity: 0.4; transition: transform 0.2s; }
        [aria-expanded="true"] .menu-arrow { transform: rotate(90deg); opacity: 0.8; }

        /* ── Sidebar: DARK mode (body[data-leftbar-color="dark"]) ── */
        body[data-leftbar-color="dark"] #side-menu > li > a:hover {
            background: rgba(255,255,255,0.07) !important; padding-left: 20px !important;
        }
        body[data-leftbar-color="dark"] #side-menu > li.menuitem-active > a {
            background: rgba(255,255,255,0.1) !important; font-weight: 600; position: relative;
        }
        body[data-leftbar-color="dark"] .nav-second-level li a:hover {
            background: rgba(255,255,255,0.06) !important; padding-left: 16px !important;
        }
        body[data-leftbar-color="dark"] .left-side-menu .simplebar-scrollbar::before {
            background: rgba(255,255,255,0.2) !important; width: 4px; border-radius: 4px;
        }
        body[data-leftbar-color="dark"] .user-box {
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        body[data-leftbar-color="dark"] .left-side-menu {
            border-right: 1px solid rgba(255,255,255,0.05);
        }

        /* ── Sidebar: LIGHT mode (body[data-leftbar-color="light"]) ── */
        body[data-leftbar-color="light"] #side-menu > li > a:hover {
            background: rgba(0,0,0,0.04) !important; padding-left: 20px !important;
        }
        body[data-leftbar-color="light"] #side-menu > li.menuitem-active > a {
            background: rgba(0,0,0,0.06) !important; font-weight: 600; position: relative;
        }
        body[data-leftbar-color="light"] .nav-second-level li a:hover {
            background: rgba(0,0,0,0.04) !important; padding-left: 16px !important;
        }
        body[data-leftbar-color="light"] .left-side-menu .simplebar-scrollbar::before {
            background: rgba(0,0,0,0.15) !important; width: 4px; border-radius: 4px;
        }
        body[data-leftbar-color="light"] .user-box {
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        body[data-leftbar-color="light"] .left-side-menu {
            border-right: 1px solid rgba(0,0,0,0.08);
        }

        /* ── Sidebar: BRAND / GRADIENT modes ── */
        body[data-leftbar-color="brand"] #side-menu > li > a:hover,
        body[data-leftbar-color="gradient"] #side-menu > li > a:hover {
            background: rgba(255,255,255,0.08) !important; padding-left: 20px !important;
        }
        body[data-leftbar-color="brand"] #side-menu > li.menuitem-active > a,
        body[data-leftbar-color="gradient"] #side-menu > li.menuitem-active > a {
            background: rgba(255,255,255,0.12) !important; font-weight: 600; position: relative;
        }
        body[data-leftbar-color="brand"] .nav-second-level li a:hover,
        body[data-leftbar-color="gradient"] .nav-second-level li a:hover {
            background: rgba(255,255,255,0.06) !important; padding-left: 16px !important;
        }

        /* Active indicator (all modes) */
        #side-menu > li.menuitem-active > a::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 24px; border-radius: 3px;
            background: var(--admin-secondary, #04a31e);
        }

        /* ── Submenu ── */
        .nav-second-level { padding: 4px 0 8px 0; }
        .nav-second-level li a {
            padding: 9px 16px 9px 10px !important; border-radius: 8px;
            font-size: 0.84rem; margin: 0 8px; transition: all 0.2s;
            display: flex; align-items: center; gap: 10px;
        }
        .nav-second-level li a i, .nav-second-level li a svg {
            width: 15px; height: 15px; flex-shrink: 0; opacity: 0.55;
        }
        .collapse.show { animation: fadeSlideIn 0.25s ease; }

        /* ── User Box ── */
        .user-box {
            padding: 1.5rem 1rem 1rem; margin-bottom: 0.5rem;
        }
        .user-box img.avatar-md {
            width: 52px; height: 52px; border-radius: 14px;
            border: 2px solid rgba(128,128,128,0.2);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .user-box .dropdown-toggle { color: var(--admin-sidebar-text) !important; font-size: 0.9rem !important; }

        /* ── Animations ── */
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Mobile Tweaks ── */
        @media (max-width: 992px) {
            #side-menu > li { margin: 1px 4px; }
            #side-menu > li > a { padding: 10px 12px !important; }
        }

        /* ── Tables ── */
        .table thead th { background-color: color-mix(in srgb, var(--admin-body-bg) 95%, #000) !important; color: var(--admin-heading-color) !important; border-bottom-color: var(--admin-border-color) !important; }
        .table td { border-top-color: var(--admin-border-color) !important; }
        .table-hover tbody tr:hover { background-color: color-mix(in srgb, var(--admin-primary) 5%, transparent) !important; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: color-mix(in srgb, var(--admin-body-bg) 97%, #000) !important; }
        .card-body thead { background: color-mix(in srgb, var(--admin-primary) 8%, transparent) !important; }

        /* ── Badges & Labels ── */
        .badge-primary { background-color: var(--admin-primary) !important; color: #fff !important; }
        .badge-success { background-color: var(--admin-secondary) !important; color: #fff !important; }
        .badge-warning { background-color: var(--admin-accent) !important; }
        .badge-info { background-color: var(--admin-accent) !important; }
        .label-primary { background-color: var(--admin-primary) !important; color: #fff !important; }

        /* ── Alerts ── */
        .alert-primary { background-color: color-mix(in srgb, var(--admin-primary) 12%, transparent) !important; border-color: color-mix(in srgb, var(--admin-primary) 30%, transparent) !important; color: var(--admin-text-color) !important; }
        .alert-success { background-color: color-mix(in srgb, var(--admin-secondary) 12%, transparent) !important; border-color: color-mix(in srgb, var(--admin-secondary) 30%, transparent) !important; color: var(--admin-text-color) !important; }
        .alert-warning { color: var(--admin-text-color) !important; }
        .alert-info { color: var(--admin-text-color) !important; }
        .alert-danger { color: var(--admin-text-color) !important; }

        /* ── Pagination ── */
        .page-item.active .page-link { color: #fff !important; background-color: var(--admin-primary) !important; border-color: var(--admin-primary) !important; }
        .page-link { color: var(--admin-primary) !important; }
        .page-link:hover { color: var(--admin-button-hover) !important; }

        /* ── Nav Tabs ── */
        .nav-tabs .nav-link.active { color: var(--admin-primary) !important; border-bottom-color: var(--admin-primary) !important; }
        .nav-tabs .nav-link:hover { color: var(--admin-button-hover) !important; }

        /* ── Modal ── */
        .modal-header { background-color: color-mix(in srgb, var(--admin-primary) 8%, transparent) !important; border-bottom-color: var(--admin-border-color) !important; }
        .modal-header .modal-title { color: var(--admin-heading-color) !important; }
        .modal-header .btn-close { filter: none; }
        .modal-footer { border-top-color: var(--admin-border-color) !important; }

        /* ── Dropdown ── */
        .dropdown-menu { border-color: var(--admin-border-color) !important; }
        .dropdown-item:hover { background-color: color-mix(in srgb, var(--admin-primary) 8%, transparent) !important; color: var(--admin-primary) !important; }

        /* ── Form Controls ── */
        .form-control:focus { border-color: var(--admin-primary) !important; box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--admin-primary) 25%, transparent) !important; }
        .select2-container--default .select2-selection--single:focus, 
        .select2-container--default .select2-selection--multiple:focus { border-color: var(--admin-primary) !important; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: var(--admin-primary) !important; }

        /* ── Progress Bars ── */
        .progress-bar { background-color: var(--admin-primary) !important; }

        /* ── List Group ── */
        .list-group-item { border-color: var(--admin-border-color) !important; }
        .list-group-item.active { background-color: var(--admin-primary) !important; border-color: var(--admin-primary) !important; }

        /* ── Custom Switch ── */
        .custom-control-input:checked ~ .custom-control-label::before { background-color: var(--admin-secondary) !important; border-color: var(--admin-secondary) !important; }

        /* ── Links in cards/widgets ── */
        .card-footer { border-top-color: var(--admin-border-color) !important; background-color: color-mix(in srgb, var(--admin-card-bg) 97%, #000) !important; }

        /* ── Topbar dropdown ── */
        .topbar-dropdown .dropdown-menu { border-color: var(--admin-border-color) !important; }
        .notify-item:hover { background-color: color-mix(in srgb, var(--admin-primary) 5%, transparent) !important; }
        .notify-item .notify-details { color: var(--admin-heading-color) !important; }

        /* ── Widget box stats ── */
        .widget-box-2 .widget-detail-2 .badge { background-color: var(--admin-primary) !important; }

        /* ── Switch slider (custom.css uses #2196F3) ── */
        input:checked + .slider { background-color: var(--admin-secondary) !important; }
        input:focus + .slider { box-shadow: 0 0 1px var(--admin-secondary) !important; }

        /* ── Select2 theme overrides ── */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--admin-primary) !important;
            border-color: var(--admin-primary) !important;
        }

        /* ═══════════════════════════════════════════════
           ── Topbar: Text, Icons, Dropdowns ──
           ═══════════════════════════════════════════════ */
        .navbar-custom { 
            background-color: var(--admin-topbar-bg) !important; 
            background-image: none !important;
        }
        /* Topbar DARK mode */
        body[data-topbar-color="dark"] .navbar-custom .topnav-menu .nav-link { 
            color: color-mix(in srgb, var(--admin-topbar-text) 85%, transparent) !important;
        }
        body[data-topbar-color="dark"] .navbar-custom .topnav-menu .nav-link:hover { 
            color: #fff !important; 
            background-color: rgba(255,255,255,0.1) !important;
        }
        body[data-topbar-color="dark"] .navbar-custom .noti-icon { color: var(--admin-topbar-text) !important; opacity: 0.75; }
        body[data-topbar-color="dark"] .navbar-custom .logo-box {
            background-color: color-mix(in srgb, var(--admin-topbar-bg) 90%, #000) !important;
        }
        /* Topbar LIGHT mode */
        body[data-topbar-color="light"] .navbar-custom .topnav-menu .nav-link { 
            color: color-mix(in srgb, var(--admin-topbar-text) 85%, transparent) !important;
        }
        body[data-topbar-color="light"] .navbar-custom .topnav-menu .nav-link:hover { 
            color: var(--admin-topbar-text) !important; 
            background-color: rgba(0,0,0,0.05) !important;
        }
        body[data-topbar-color="light"] .navbar-custom .noti-icon { color: var(--admin-topbar-text) !important; opacity: 0.8; }
        body[data-topbar-color="light"] .navbar-custom .logo-box {
            background-color: color-mix(in srgb, var(--admin-topbar-bg) 95%, #fff) !important;
        }
        /* Topbar common */
        .navbar-custom .dropdown .nav-link.show {
            background-color: rgba(128,128,128,0.12) !important;
        }
        .navbar-custom .noti-icon-badge { border-color: var(--admin-topbar-bg) !important; }

        /* ═══════════════════════════════════════════════
           ── Sidebar: All States & Submenu ──
           ═══════════════════════════════════════════════ */
        .left-side-menu { 
            /* Override app.min.css default #ddd */
            background: var(--admin-sidebar-bg) !important;
            background-color: var(--admin-sidebar-bg) !important;
            background-image: none !important;
        }
        #sidebar-menu > ul > li > a { color: var(--admin-sidebar-text) !important; }
        #sidebar-menu > ul > li > a i, 
        #sidebar-menu > ul > li > a svg { color: var(--admin-sidebar-text) !important; opacity: 0.65; }
        #sidebar-menu > ul > li > a:hover i,
        #sidebar-menu > ul > li > a:hover svg { opacity: 1; }
        #sidebar-menu .menuitem-active > a { color: var(--admin-secondary) !important; }
        #sidebar-menu .menuitem-active .active { color: var(--admin-secondary) !important; }
        #sidebar-menu > ul > li > a.mm-active { color: var(--admin-secondary) !important; }
        #sidebar-menu .menu-title { color: color-mix(in srgb, var(--admin-sidebar-text) 50%, transparent) !important; }
        .nav-second-level li a { color: var(--admin-sidebar-text) !important; opacity: 0.8; }
        .nav-second-level li a:hover, .nav-second-level li a:focus { color: var(--admin-secondary) !important; opacity: 1; }
        .nav-second-level li.active > a { color: var(--admin-secondary) !important; }

        /* ═══════════════════════════════════════════════
           ── User Dropdown in Sidebar ──
           ═══════════════════════════════════════════════ */
        .user-box .dropdown > a { color: var(--admin-sidebar-text) !important; }
        .user-pro-dropdown .dropdown-item:hover {
            background-color: var(--admin-primary) !important; 
            color: #fff !important;
        }

        /* ═══════════════════════════════════════════════
           ── Dark Sidebar Mode Overrides ──
           (When data-leftbar-color="dark" on body) 
           ─────────────────────────────────────────── */
        body[data-leftbar-color="dark"] .left-side-menu #sidebar-menu > ul > li > a { color: var(--admin-sidebar-text) !important; }
        body[data-leftbar-color="dark"] .left-side-menu #sidebar-menu .menu-title { color: color-mix(in srgb, var(--admin-sidebar-text) 50%, transparent) !important; }
        body[data-leftbar-color="dark"] .left-side-menu #sidebar-menu .menuitem-active > a { color: var(--admin-secondary) !important; }
        body[data-leftbar-color="dark"] .left-side-menu #sidebar-menu .menuitem-active .active { color: var(--admin-secondary) !important; }
        body[data-leftbar-color="dark"] .left-side-menu .nav-second-level li a,
        body[data-leftbar-color="dark"] .left-side-menu .nav-thrid-level li a { color: var(--admin-sidebar-text) !important; opacity: 0.75; }
        body[data-leftbar-color="dark"] .left-side-menu .nav-second-level li a:hover,
        body[data-leftbar-color="dark"] .left-side-menu .nav-second-level li.active > a,
        body[data-leftbar-color="dark"] .left-side-menu .nav-thrid-level li a:hover,
        body[data-leftbar-color="dark"] .left-side-menu .nav-thrid-level li.active > a { color: var(--admin-secondary) !important; }
        body[data-leftbar-color="dark"] .left-side-menu .user-box .dropdown > a { color: var(--admin-sidebar-text) !important; }

        /* ═══════════════════════════════════════════════
           ── Light Sidebar Mode Overrides ──
           (When data-leftbar-color="light" on body)
           ─────────────────────────────────────────── */
        body[data-leftbar-color="light"] .left-side-menu { 
            background: var(--admin-sidebar-bg) !important; 
            background-color: var(--admin-sidebar-bg) !important;
        }
        body[data-leftbar-color="light"] .left-side-menu #sidebar-menu > ul > li > a { color: var(--admin-sidebar-text) !important; }
        body[data-leftbar-color="light"] .left-side-menu #sidebar-menu .menu-title { color: color-mix(in srgb, var(--admin-sidebar-text) 60%, transparent) !important; }
        body[data-leftbar-color="light"] .left-side-menu #sidebar-menu .menuitem-active > a { color: var(--admin-secondary) !important; }
        body[data-leftbar-color="light"] .left-side-menu #sidebar-menu .menuitem-active .active { color: var(--admin-secondary) !important; }
        body[data-leftbar-color="light"] .left-side-menu .nav-second-level li a { color: var(--admin-sidebar-text) !important; }
        body[data-leftbar-color="light"] .left-side-menu .nav-second-level li a:hover,
        body[data-leftbar-color="light"] .left-side-menu .nav-second-level li.active > a { color: var(--admin-secondary) !important; }

        /* ═══════════════════════════════════════════════
           ── Brand/Gradient Sidebar Mode Overrides ──
           ─────────────────────────────────────────── */
        body[data-leftbar-color="brand"] .left-side-menu,
        body[data-leftbar-color="gradient"] .left-side-menu {
            background: var(--admin-sidebar-bg) !important;
            background-color: var(--admin-sidebar-bg) !important;
        }
        body[data-leftbar-color="brand"] .left-side-menu #sidebar-menu > ul > li > a,
        body[data-leftbar-color="gradient"] .left-side-menu #sidebar-menu > ul > li > a { color: var(--admin-sidebar-text) !important; }
        body[data-leftbar-color="brand"] .left-side-menu #sidebar-menu .menuitem-active > a,
        body[data-leftbar-color="gradient"] .left-side-menu #sidebar-menu .menuitem-active > a { color: var(--admin-secondary) !important; }
        body[data-leftbar-color="brand"] .left-side-menu .nav-second-level li a,
        body[data-leftbar-color="gradient"] .left-side-menu .nav-second-level li a { color: var(--admin-sidebar-text) !important; opacity: 0.8; }
        body[data-leftbar-color="brand"] .left-side-menu .nav-second-level li a:hover,
        body[data-leftbar-color="gradient"] .left-side-menu .nav-second-level li a:hover { color: var(--admin-secondary) !important; }

        /* ═══════════════════════════════════════════════
           ── Topbar Mode (dark/light) ──
           Handled above in main Topbar section
           ═══════════════════════════════════════════════ */

        /* ═══════════════════════════════════════════════
           ── Twotones Icons ──
           ─────────────────────────────────────────── */
        body[data-sidebar-icon="twotones"] #sidebar-menu > ul > li > a i { color: var(--admin-primary) !important; }
        body[data-sidebar-icon="twotones"] #sidebar-menu > ul > li > a svg { 
            color: var(--admin-primary) !important; 
            fill: color-mix(in srgb, var(--admin-primary) 20%, transparent) !important;
        }

        /* ═══════════════════════════════════════════════
           ── Right-bar Theme Settings Panel ──
           ─────────────────────────────────────────── */
        .right-bar .nav-link.active { color: var(--admin-primary) !important; }

        /* ═══════════════════════════════════════════════
           ── Misc: Breadcrumb, Input Groups, etc. ──
           ─────────────────────────────────────────── */
        .breadcrumb-item.active { color: var(--admin-primary) !important; }
        .input-group-text { border-color: var(--admin-border-color) !important; background-color: color-mix(in srgb, var(--admin-body-bg) 95%, #000) !important; }
        hr { border-top-color: var(--admin-border-color) !important; }
    </style>
    @endif
    <style>.navbar-custom .dropdown-menu .noti-scroll{max-height:230px!important;overflow-y:auto!important}</style>
    <script src="{{asset('public/backEnd/')}}/assets/js/head.js"></script>
  </head>

  <!-- body start -->
  @php
      $sidebarBg  = optional($activeTheme)->sidebar_bg_color ?? optional($activeTheme)->header_bg_color ?? null;
      $topbarBg   = optional($activeTheme)->topbar_bg_color ?? optional($activeTheme)->header_bg_color ?? null;
      $sidebarIsDark = is_color_dark($sidebarBg);
      $topbarIsDark  = is_color_dark($topbarBg);
  @endphp
  <body data-layout-mode="default" data-theme="light" data-layout-width="fluid" data-topbar-color="{{ $topbarIsDark ? 'dark' : 'light' }}" data-menu-position="fixed" data-leftbar-color="{{ $sidebarIsDark ? 'dark' : 'light' }}" data-leftbar-size="default" data-sidebar-user="false">
    <!-- Begin page -->
    <div id="wrapper">
      <!-- Topbar Start -->
      <div class="navbar-custom">
        <div class="container-fluid">
          <ul class="list-unstyled topnav-menu float-end mb-0">
            <li class="dropdown d-inline-block d-lg-none">
              <a class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <i class="fe-search noti-icon"></i>
              </a>
              <div class="dropdown-menu dropdown-lg dropdown-menu-end p-0">
                <form class="p-3">
                  <input type="text" class="form-control" placeholder="Search ..." aria-label="Recipient's username" />
                </form>
              </div>
            </li>

            <li class="dropdown d-none d-lg-inline-block">
              <a class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-toggle="fullscreen" href="#">
                <i class="fe-maximize noti-icon"></i>
              </a>
            </li>

            @if(isset($demoMode) && $demoMode)
            <li class="dropdown d-none d-lg-inline-block">
              <span class="badge bg-warning text-dark px-2 py-1 mt-1" title=".env থেকে DEMO_MODE=true সেট করা আছে"><i class="fe-eye me-1"></i>ডেমো</span>
            </li>
            @endif

            <li class="dropdown notification-list topbar-dropdown">
              <a class="nav-link dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <i class="fe-bell noti-icon"></i>
                <span class="badge bg-danger rounded-circle noti-icon-badge">{{$neworder}}</span>
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-lg">
                <!-- item-->
                <div class="dropdown-item noti-title">
                  <h5 class="m-0">
                    <span class="float-end">
                      <a href="{{route('admin.orders',['slug'=>'pending'])}}" class="text-dark">
                        <small> {{ __('View All') }} </small>
                      </a>
                    </span>{{ __('Orders') }}</h5>
                </div>

                <div class="noti-scroll">
                  @foreach($pendingorder as $porder)
                  <!-- item-->
                  <a href="{{route('admin.orders',['slug'=>'pending'])}}" class="dropdown-item notify-item active">
                    <div class="notify-icon">
                      <img src="{{asset($porder->customer?$porder->customer->image:'')}}" class="img-fluid rounded-circle" alt="" />
                    </div>
                    <p class="notify-details">{{$porder->customer?$porder->customer->name:''}}</p>
                    <p class="text-muted mb-0 user-msg">
                      <small>Invoice : {{$porder->invoice_id}}</small>
                    </p>
                  </a>
                  @endforeach

                  <!-- item-->
                </div>

                <!-- All-->
                <a href="{{route('admin.orders',['slug'=>'pending'])}}" class="dropdown-item text-center text-primary notify-item notify-all">
                  View all
                  <i class="fe-arrow-right"></i>
                </a>
              </div>
            </li>

            <li class="dropdown notification-list topbar-dropdown">
              <a class="nav-link dropdown-toggle nav-user me-0 waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <img src="{{asset(Auth::guard('admin')->user()->image)}}" alt="user-image" class="rounded-circle" />
                <span class="pro-user-name ms-1"> {{Auth::guard('admin')->user()->name}} <i class="mdi mdi-chevron-down"></i> </span>
              </a>
              <div class="dropdown-menu dropdown-menu-end profile-dropdown">
                <!-- item-->
                <div class="dropdown-header noti-title">
                  <h6 class="text-overflow m-0"> {{ __('Welcome !') }} </h6>
                </div>

                <!-- item-->
                <a href="{{url('admin/dashboard')}}" class="dropdown-item notify-item">
                  <i class="fe-user"></i>
                  <span>{{ __('Dashboard') }}</span>
                </a>

                {{-- Quick Theme Switcher --}}
                @if(isset($activeTheme))
                <div class="dropdown-divider"></div>
                <div class="dropdown-header noti-title">
                    <h6 class="text-overflow m-0">
                        <i class="fe-feather me-1" style="color:var(--admin-secondary)"></i> 
                        Theme: <span style="color:var(--admin-secondary)">{{ $activeTheme->name }}</span>
                    </h6>
                </div>
                <div class="px-3 py-1" style="max-height:140px;overflow-y:auto;">
                    @php $quickThemes = \App\Models\Theme::where('is_active', true)->orderBy('name')->limit(6)->get(); @endphp
                    @foreach($quickThemes as $t)
                    <a href="{{ route('themes.apply', $t->id) }}" class="dropdown-item notify-item py-1 px-2" style="font-size:12px;">
                        <span class="d-inline-block rounded-circle me-2" style="width:12px;height:12px;background:{{ $t->primary_color }};border:1px solid rgba(0,0,0,0.1);"></span>
                        {{ $t->name }} @if($activeTheme->id == $t->id)<i class="fe-check text-success ms-1"></i>@endif
                    </a>
                    @endforeach
                    <a href="{{ route('themes.index') }}" class="dropdown-item notify-item py-1 px-2 text-primary" style="font-size:11px;">
                        <i class="fe-settings me-1"></i> Manage Themes
                    </a>
                </div>
                @endif

                <!-- item-->

                <div class="dropdown-divider"></div>

                <!-- item-->
                <a
                  href="{{ route('logout') }}"
                  onclick="event.preventDefault();
                  document.getElementById('logout-form').submit();"
                  class="dropdown-item notify-item"
                >
                  <i class="fe-log-out me-1"></i>
                  <span>{{ __('Logout') }}</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                  @csrf
                </form>
              </div>
            </li>

            <!--<li class="dropdown notification-list">-->
            <!--    <a href="javascript:void(0);" class="nav-link right-bar-toggle waves-effect waves-light">-->
            <!--        <i class="fe-settings noti-icon"></i>-->
            <!--    </a>-->
            <!--</li>-->
          </ul>

          <!-- LOGO -->
          <div class="logo-box">
            <a href="{{url('admin/dashboard')}}" class="logo logo-dark text-center">
              <span class="logo-sm">
                <img src="{{asset(isset($generalsetting->white_logo) ? $generalsetting->white_logo : 'public/assets/images/CurlBazar.svg')}}" alt="" height="50" />
                <!-- <span class="logo-lg-text-light"> {{ __('UBold') }} </span> -->
              </span>
              <span class="logo-lg">
                <img src="{{asset(isset($generalsetting->white_logo) ? $generalsetting->white_logo : 'public/assets/images/CurlBazar.svg')}}" alt="" height="50" />
                <!-- <span class="logo-lg-text-light">U</span> -->
              </span>
            </a>

            <a href="{{url('admin/dashboard')}}" class="logo logo-light text-center">
              <span class="logo-sm">
                <img src="{{asset(isset($generalsetting->white_logo) ? $generalsetting->white_logo : 'public/assets/images/CurlBazar.svg')}}" alt="" height="50" />
              </span>
              <span class="logo-lg">
                <img src="{{asset(isset($generalsetting->white_logo) ? $generalsetting->white_logo : 'public/assets/images/CurlBazar.svg')}}" alt="" height="50" />
              </span>
            </a>
          </div>

          <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
            <li>
              <button class="button-menu-mobile waves-effect waves-light">
                <i class="fe-menu"></i>
              </button>
            </li>

            <li>
              <!-- Mobile menu toggle (Horizontal Layout)-->
              <a class="navbar-toggle nav-link" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <div class="lines">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
              </a>
              <!-- End mobile menu toggle-->
            </li>

            <li class="dropdown d-none d-xl-block">
              <a class="nav-link dropdown-toggle waves-effect waves-light" href="{{route('home')}}" target="_blank"> <i data-feather="globe"></i> {{ __('Visit Site') }} </a>
            </li>
          </ul>
          <div class="clearfix"></div>
        </div>
      </div>
      <!-- end Topbar -->

      <!-- ========== Left Sidebar Start ========== -->
      <div class="left-side-menu">
        <div class="h-100" data-simplebar>
          <!-- User box -->
          <div class="user-box text-center">
            <img src="{{asset('public/backEnd/')}}/assets/images/users/user-1.jpg" alt="user-img" title="Mat Helme" class="rounded-circle avatar-md" />
            <div class="dropdown">
              <a href="javascript: void(0);" class="text-dark dropdown-toggle h5 mt-2 mb-1 d-block" data-bs-toggle="dropdown">{{Auth::guard('admin')->user()->name}}</a>
              <div class="dropdown-menu user-pro-dropdown">
                <!-- item-->
                <a href="javascript:void(0);" class="dropdown-item notify-item">
                  <i class="fe-user me-1"></i>
                  <span>{{ __('My Account') }}</span>
                </a>

                <!-- item-->
                <a href="javascript:void(0);" class="dropdown-item notify-item">
                  <i class="fe-settings me-1"></i>
                  <span>{{ __('Settings') }}</span>
                </a>

                <!-- item-->
                <a href="javascript:void(0);" class="dropdown-item notify-item">
                  <i class="fe-lock me-1"></i>
                  <span> {{ __('Lock Screen') }} </span>
                </a>

                <!-- item-->
                <a
                  href="{{ route('logout') }}"
                  onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();"
                  class="dropdown-item notify-item"
                >
                  <i class="fe-log-out me-1"></i>
                  <span>{{ __('Logout') }}</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                  @csrf
                </form>
              </div>
            </div>
            <p class="text-muted"> {{ __('Admin Head') }} </p>
          </div>

          <!--- Sidemenu -->
          <div id="sidebar-menu">
            <ul id="side-menu">
<li>
  <a href="{{ url('admin/dashboard') }}">
    <i data-feather="airplay"></i>
    <span>{{ __('Dashboard') }}</span>
  </a>
</li>

@can('order-create')
<li>
  <a href="{{route('admin.order.create')}}">
    <i data-feather="cpu"></i>
    <span> {{ __('POS System') }} </span>
  </a>
</li>
@endcan

@php
  use Illuminate\Support\Facades\Auth;
  $user = Auth::guard('admin')->user();
  $pending_reviews = \App\Models\Review::where('status', 'pending')->count();
@endphp

{{-- ============================================= --}}
{{--  SECTION 1: SALES & ORDERS                    --}}
{{-- ============================================= --}}
@canany(['order-list', 'order-edit', 'order-create'])
<li>
  <a href="#sidebar-orders" data-bs-toggle="collapse">
    <i data-feather="shopping-cart"></i>
    <span>{{ __('Orders') }}</span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-orders">
    <ul class="nav-second-level">
      @can('order-list')
      <li><a href="{{ route('admin.orders', ['slug'=>'all']) }}"><i data-feather="file-plus"></i> {{ __('All Order') }} </a></li>
      <li><a href="{{ route('admin.incomplete-orders.index') }}"><i data-feather="file-plus"></i> {{ __('Incomplete Orders') }} </a></li>
      @foreach($orderstatus as $value)
        <li><a href="{{ route('admin.orders', ['slug'=>$value->slug]) }}"><i data-feather="file-plus"></i>{{ $value->name }}</a></li>
      @endforeach
      @endcan
      @can('order-edit')
      {{-- Order Status now managed via app/Enums/OrderStatus.php (enum-driven) --}}
      @endcan
      @can('order-manage')
      <li><a href="{{route('customers.ip_block')}}"><i data-feather="file-plus"></i> {{ __('IP Block') }} </a></li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

{{-- ============================================= --}}
{{--  SECTION 2: PRODUCT CATALOG                   --}}
{{-- ============================================= --}}
@canany(['product-list', 'category-list', 'subcategory-list', 'childcategory-list'])
<li>
  <a href="#siebar-product" data-bs-toggle="collapse">
    <i data-feather="database"></i>
    <span>{{ __('Products') }}</span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="siebar-product">
    <ul class="nav-second-level">
      @can('product-list')
      <li><a href="{{ route('inhouse.products.index') }}"><i data-feather="package"></i> {{ __('All Inhouse Products') }} </a></li>
      <li><a href="{{ route('products.pending') }}"><i data-feather="clock"></i> {{ __('Pending Products') }} </a></li>
      <li><a href="{{ route('admin.products.wholesale') }}"><i data-feather="layers"></i> {{ __('Wholesale Products') }} </a></li>
      @endcan
      @can('product-create')
      <li><a href="{{ route('products.create') }}"><i data-feather="plus-circle"></i>{{ __('Add Product') }}</a></li>
      @endcan
      <li><hr class="dropdown-divider"></li>
      @can('category-list')
      <li><a href="{{ route('categories.index') }}"><i data-feather="file-plus"></i>{{ __('Categories') }}</a></li>
      @endcan
      @can('subcategory-list')
      <li><a href="{{ route('subcategories.index') }}"><i data-feather="file-plus"></i> {{ __('Subcategories') }} </a></li>
      @endcan
      @can('childcategory-list')
      <li><a href="{{ route('childcategories.index') }}"><i data-feather="file-plus"></i> {{ __('Childcategories') }} </a></li>
      @endcan
      @canany(['brand-list', 'brand-create', 'brand-edit'])
      <li><a href="{{ route('brands.index') }}"><i data-feather="file-plus"></i>{{ __('Brands') }}</a></li>
      @endcanany
      @canany(['color-list', 'color-create', 'color-edit'])
      <li><a href="{{ route('colors.index') }}"><i data-feather="file-plus"></i> {{ __('Colors') }} </a></li>
      @endcanany
      @canany(['size-list', 'size-create', 'size-edit'])
      <li><a href="{{ route('sizes.index') }}"><i data-feather="file-plus"></i> {{ __('Sizes') }} </a></li>
      @endcanany
    </ul>
  </div>
</li>
@endcanany

{{-- 🛡️ Warranty Management --}}
<li class="{{ request()->routeIs('admin.warranty.*') ? 'active menuitem-active' : '' }}">
  <a href="#sidebar-warranty" data-bs-toggle="collapse">
    <i data-feather="shield"></i>
    <span> {{ __('Warranty') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse {{ request()->routeIs('admin.warranty.*') ? 'show' : '' }}" id="sidebar-warranty">
    <ul class="nav-second-level">
      <li><a href="{{ route('admin.warranty.dashboard') }}"><i data-feather="grid"></i> {{ __('Dashboard') }} </a></li>
      <li><a href="{{ route('admin.warranty.supplier.index') }}"><i data-feather="truck"></i> {{ __('Supplier Warranties') }} </a></li>
      <li><a href="{{ route('admin.warranty.sales.index') }}"><i data-feather="shopping-bag"></i> {{ __('Warranty Sales') }} </a></li>
      <li><a href="{{ route('admin.warranty.claims.index') }}"><i data-feather="tool"></i> {{ __('Claims') }} </a></li>
      <li><a href="{{ route('admin.warranty.damage.index') }}"><i data-feather="alert-triangle"></i> {{ __('Damage Products') }} </a></li>
    </ul>
  </div>
</li>

{{-- ============================================= --}}
{{--  🆕 SECTION 6: STOCK & PROCUREMENT            --}}
{{-- ============================================= --}}
<li>
  <a href="#sidebar-stock" data-bs-toggle="collapse">
    <i data-feather="box"></i>
    <span>{{ __('Stock Management') }}</span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-stock">
    <ul class="nav-second-level">
      <li><a href="{{ route('admin.stock.dashboard') }}"><i data-feather="airplay"></i> {{ __('Dashboard') }}</a></li>
      <li><a href="{{ route('purchases.index') }}"><i data-feather="file-text"></i> {{ __('Purchases') }}</a></li>
      <li><a href="{{ route('admin.suppliers.index') }}"><i data-feather="truck"></i> {{ __('Suppliers') }}</a></li>
      <li><a href="{{ route('admin.stock.batches') }}"><i data-feather="layers"></i> {{ __('Batches') }}</a></li>
      <li><a href="{{ route('admin.stock.adjustments') }}"><i data-feather="edit"></i> {{ __('Adjustments') }}</a></li>
      <li><a href="{{ route('admin.stock.valuation') }}"><i data-feather="dollar-sign"></i> {{ __('Valuation') }}</a></li>
      <li><a href="{{ route('admin.stock.cogs') }}"><i data-feather="trending-down"></i> {{ __('COGS Report') }} </a></li>
      <li><a href="{{ route('admin.stock.barcode.print') }}"><i data-feather="tag"></i> {{ __('Barcode Labels') }} </a></li>
      <li><a href="{{ route('admin.stock.supplier-returns') }}"><i data-feather="rotate-ccw"></i> {{ __('Supplier Returns') }} </a></li>
    </ul>
  </div>
</li>

@canany(['fund-list', 'fund-create', 'fund-edit'])
<li>
  <a href="{{ route('admin.fund.index') }}">
    <i data-feather="briefcase"></i>
    <span> {{ __('Fund / Account') }} </span>
  </a>
</li>
@endcanany

@canany(['expense-list', 'expense-create', 'expense-edit'])
<li class="{{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
  <a href="{{ route('admin.expenses.index') }}">
    <i data-feather="credit-card"></i>
    <span> {{ __('Expenses') }} </span>
  </a>
</li>
@endcanany

{{-- Refunds --}}
@canany(['order-list', 'order-edit'])
<li class="{{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
  <a href="#sidebar-refunds" data-bs-toggle="collapse">
    <i data-feather="rotate-ccw"></i>
    <span> {{ __('Refunds') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse {{ request()->routeIs('admin.refunds.*') ? 'show' : '' }}" id="sidebar-refunds">
    <ul class="nav-second-level">
      <li><a href="{{ route('admin.refunds.index') }}"><i data-feather="list"></i> {{ __('All Refunds') }} </a></li>
      <li><a href="{{ route('admin.refunds.index', ['status' => 'pending']) }}"><i data-feather="clock"></i> {{ __('Pending Refunds') }} </a></li>
      <li><a href="{{ route('admin.refunds.index', ['status' => 'approved']) }}"><i data-feather="check-circle"></i> {{ __('Approved Refunds') }} </a></li>
      <li><a href="{{ route('admin.refunds.index', ['status' => 'processed']) }}"><i data-feather="check"></i> {{ __('Processed Refunds') }} </a></li>
    </ul>
  </div>
</li>
@endcanany

{{-- ============================================= --}}
{{--  SECTION 3: MARKETING & CONTENT               --}}
{{-- ============================================= --}}
@canany(['coupon-list', 'coupon-create', 'coupon-edit', 'coupon-delete'])
<li>
  <a href="#sidebar-coupon" data-bs-toggle="collapse">
    <i data-feather="gift"></i>
    <span> {{ __('Coupons') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-coupon">
    <ul class="nav-second-level">
      @can('coupon-list')
      <li><a href="{{ route('admin.coupons.index') }}"><i data-feather="list"></i> {{ __('All Coupons') }} </a></li>
      @endcan
      @can('coupon-create')
      <li><a href="{{ route('admin.coupons.create') }}"><i data-feather="plus-circle"></i>{{ __('Add New') }}</a></li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

@canany(['campaign-list', 'campaign-create'])
<li>
  <a href="#sidebar-landing-page" data-bs-toggle="collapse">
    <i data-feather="airplay"></i>
    <span> {{ __('Landing Page') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-landing-page">
    <ul class="nav-second-level">
      @can('campaign-list')
      <li><a href="{{ route('campaign.index') }}"><i data-feather="file-plus"></i> {{ __('Campaign') }} </a></li>
      @endcan
      @can('campaign-create')
      <li><a href="{{ route('campaign.create') }}"><i data-feather="file-plus"></i>{{ __('Create') }}</a></li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

@canany(['banner-list'])
<li class="{{ request()->routeIs('banners.index.*') ? 'active' : '' }}">
    <a href="{{ route('banners.index') }}">
      <i data-feather="image"></i>
        <span> {{ __('Banner & Sliders') }} </span>
    </a>
</li>
@endcanany

<li class="{{ request()->routeIs('admin.media.*') ? 'active' : '' }}">
    <a href="{{ route('admin.media.index') }}">
      <i data-feather="folder"></i>
        <span> {{ __('Media Gallery') }} </span>
    </a>
</li>

@canany(['popup-list','popup-manage'])
<li class="{{ request()->routeIs('admin.popup.*') ? 'active' : '' }}">
    <a href="{{ route('admin.popup.index') }}">
        <i data-feather="message-square"></i>
        <span> {{ __('Popup Offer') }} </span>
    </a>
</li>
@endcanany

@canany(['blog-list','blog-create','blog-edit','blog-delete'])
<li>
    <a href="#sidebar-blog" data-bs-toggle="collapse">
        <i data-feather="edit"></i>
        <span>{{ __('Blog') }}</span>
        <span class="menu-arrow"></span>
    </a>
    <div class="collapse" id="sidebar-blog">
        <ul class="nav-second-level">
            @can('blog-list')
            <li><a href="{{ route('admin.blog.index') }}"><i data-feather="list"></i> {{ __('All Blogs') }} </a></li>
            @endcan
            @can('blog-create')
            <li><a href="{{ route('admin.blog.create') }}"><i data-feather="plus-circle"></i> {{ __('Add New Blog') }} </a></li>
            @endcan
        </ul>
    </div>
</li>
@endcanany

@can('review-list')
<li>
  <a href="#sidebar-product-review" data-bs-toggle="collapse">
    <i data-feather="star"></i>
    <span> {{ __('Reviews') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-product-review">
    <ul class="nav-second-level">
      @can('review-list')
      <li><a href="{{ route('reviews.pending') }}"><i data-feather="file-plus"></i> Pending Reviews ({{ $pending_reviews }})</a></li>
      <li><a href="{{ route('reviews.index') }}"><i data-feather="file-plus"></i> {{ __('All Reviews') }} </a></li>
      @endcan
      @can('review-create')
      <li><a href="{{ route('reviews.pending') }}"><i data-feather="file-plus"></i>{{ __('Create') }}</a></li>
      @endcan
    </ul>
  </div>
</li>
@endcan

{{-- ============================================= --}}
{{--  SECTION 4: CUSTOMERS & PEOPLE                --}}
{{-- ============================================= --}}
<li>
  <a href="#sidebar-crm" data-bs-toggle="collapse">
    <i data-feather="users"></i>
    <span> {{ __('CRM / HR') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-crm">
    <ul class="nav-second-level">
      <li><a href="{{ route('admin.employees.index') }}"><i data-feather="user"></i> {{ __('Employees') }} </a></li>
      <li><a href="{{ route('admin.attendances.index') }}"><i data-feather="check-circle"></i>{{ __('Attendance') }}</a></li>
      <li><a href="{{ route('admin.leaves.index') }}"><i data-feather="calendar"></i> {{ __('Leaves') }} </a></li>
      <li><a href="{{ route('admin.salaries.index') }}"><i data-feather="dollar-sign"></i> {{ __('Salaries') }} </a></li>
      <li><a href="{{ route('admin.bonuses.index') }}"><i data-feather="gift"></i> {{ __('Bonuses') }} </a></li>
      <li><a href="{{ route('admin.salary_payments.index') }}"><i data-feather="credit-card"></i> {{ __('Salary Payments') }} </a></li>
    </ul>
  </div>
</li>

@canany(['user-list', 'role-list', 'permission-list'])
<li>
  <a href="#sidebar-users" data-bs-toggle="collapse">
    <i data-feather="user"></i>
    <span> {{ __('Users & Roles') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-users">
    <ul class="nav-second-level">
      @can('user-list')
      <li><a href="{{ route('users.index') }}"><i data-feather="file-plus"></i> {{ __('User') }} </a></li>
      @endcan
      @can('role-list')
      <li><a href="{{ route('roles.index') }}"><i data-feather="file-plus"></i> {{ __('Roles') }} </a></li>
      @endcan
      @can('permission-list')
      <li><a href="{{ route('permissions.index') }}"><i data-feather="file-plus"></i> {{ __('Permissions') }} </a></li>
      @endcan
      @canany(['customer-list', 'customer-create', 'customer-edit'])
      <li><a href="{{ route('customers.index') }}"><i data-feather="file-plus"></i>{{ __('Customers') }}</a></li>
      @endcanany
    </ul>
  </div>
</li>
@endcanany

{{-- ============================================= --}}
{{--  SECTION 5: SUPPORT                           --}}
{{-- ============================================= --}}
@canany(['complaint-list', 'complaint-create', 'complaint-edit'])
<li class="{{ request()->routeIs('backEnd.complaints.*') ? 'active' : '' }}">
    <a href="{{ route('backEnd.complaints.index') }}">
        <i data-feather="alert-circle"></i>
        <span> {{ __('Complaints') }} </span>
    </a>
</li>
@endcanany

@can('contact-list')
<li class="{{ request()->routeIs('admin.contact.messages*') ? 'active' : '' }}">
    <a href="{{ route('admin.contact.messages') }}">
        <i data-feather="mail"></i>
        <span> {{ __('Contact Messages') }} </span>
    </a>
</li>
@endcan

<li class="{{ request()->routeIs('admin.newsletter.subscribers*') ? 'active' : '' }}">
    <a href="{{ route('admin.newsletter.subscribers') }}">
        <i data-feather="mail"></i>
        <span> {{ __('Newsletter Subscribers') }} </span>
    </a>
</li>

@can('sms-send')
<li>
  <a href="{{ route('admin.sms.custom.page') }}">
    <i data-feather="send"></i>
    <span> {{ __('Send Custom SMS') }} </span>
  </a>
</li>
@endcan

{{-- ============================================= --}}
{{--  SECTION 8: ANALYTICS & TRACKING              --}}
{{-- ============================================= --}}
@canany(['pixel-manage'])
<li>
  <a href="#sidebar-pixel-gtm" data-bs-toggle="collapse">
    <i data-feather="save"></i>
    <span> {{ __('G. Pixel and GTM') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-pixel-gtm">
    <ul class="nav-second-level">
      <li><a href="{{ route('tagmanagers.index') }}"><i data-feather="file-plus"></i> {{ __('Tag Manager') }} </a></li>
      <li><a href="{{ route('pixels.index') }}"><i data-feather="file-plus"></i> {{ __('Pixel Manage') }} </a></li>
      <li><a href="{{ route('tiktok.pixels.index') }}"><i data-feather="film"></i>{{ __('TikTok Pixel') }}</a></li>
    </ul>
  </div>
</li>
@endcanany

@canany(['pixel-manage'])
<li>
  <a href="#sidebar-ads-analytics" data-bs-toggle="collapse">
    <i data-feather="trending-up"></i>
    <span> {{ __('Live Ads Result') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse {{ request()->routeIs('admin.ads_analytics.*') ? 'show' : '' }}" id="sidebar-ads-analytics">
    <ul class="nav-second-level">
      <li><a href="{{ route('admin.ads_analytics.dashboard') }}"><i data-feather="layout"></i> {{ __('Overview') }} </a></li>
      <li><a href="{{ route('admin.ads_analytics.facebook') }}"><i data-feather="facebook"></i> {{ __('Facebook Ads') }} </a></li>
      <li><a href="{{ route('admin.ads_analytics.google') }}"><i data-feather="globe"></i> {{ __('Google Ads') }} </a></li>
      <li><a href="{{ route('admin.ads_analytics.tiktok') }}"><i data-feather="video"></i> {{ __('TikTok Ads') }} </a></li>
    </ul>
  </div>
</li>
@endcanany

@canany(['pixel-manage'])
<li class="{{ request()->routeIs('admin.facebook_page.*') ? 'active' : '' }}">
  <a href="{{ route('admin.facebook_page.settings') }}">
    <i data-feather="share-2"></i>
    <span> {{ __('Facebook Page Post') }} </span>
  </a>
</li>
@endcanany

{{-- ============================================= --}}
{{--  SECTION 9: REPORTS                           --}}
{{-- ============================================= --}}
@canany(['report-view','order-report','purchase-report','expense-report','stock-report','profit-loss-report'])
<li>
  <a href="#sidebar-report" data-bs-toggle="collapse">
    <i data-feather="pie-chart"></i>
    <span>{{ __('Reports') }}</span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-report">
    <ul class="nav-second-level">
      @canany(['order-report','report-view'])
      <li><a href="{{ route('admin.reports.orders') }}"><i data-feather="file-text"></i> {{ __('Order Report') }} </a></li>
      @endcanany
      @canany(['purchase-report','report-view'])
      <li><a href="{{ route('admin.reports.purchases') }}"><i data-feather="shopping-bag"></i> {{ __('Purchase Report') }} </a></li>
      @endcanany
      @canany(['expense-report','report-view'])
      <li><a href="{{ route('admin.reports.expenses') }}"><i data-feather="trending-down"></i> {{ __('Expense Report') }} </a></li>
      @endcanany
      @canany(['stock-report','report-view'])
      <li><a href="{{ route('admin.reports.stock') }}"><i data-feather="archive"></i> {{ __('Stock Report') }} </a></li>
      @endcanany
      @canany(['profit-loss-report','report-view'])
      <li><a href="{{ route('admin.reports.profit_loss') }}"><i data-feather="activity"></i> {{ __('Profit & Loss') }} </a></li>
      @endcanany
    </ul>
  </div>
</li>
@endcanany

{{-- ============================================= --}}
{{--  SECTION 10: CONFIGURATION                    --}}
{{-- ============================================= --}}
@canany(['setting-list', 'social-list', 'contact-list'])
<li>
  <a href="#siebar-sitesetting" data-bs-toggle="collapse">
    <i data-feather="settings"></i>
    <span> {{ __('Site Setting') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="siebar-sitesetting">
    <ul class="nav-second-level">
      @can('setting-list')
      <li><a href="{{ route('settings.index') }}"><i data-feather="file-plus"></i> {{ __('General Setting') }} </a></li>
      @endcan
      @can('social-list')
      <li><a href="{{ route('socialmedias.index') }}"><i data-feather="file-plus"></i> {{ __('Social Media') }} </a></li>
      @endcan
      @can('contact-list')
      <li><a href="{{ route('contact.index') }}"><i data-feather="file-plus"></i> {{ __('Contact') }} </a></li>
      @endcan
      @canany(['page-list', 'page-create', 'page-edit'])
      <li><a href="{{ route('pages.index') }}"><i data-feather="file-plus"></i> {{ __('Create Page') }} </a></li>
      @endcanany
      @canany(['shipping-list', 'shipping-create', 'shipping-edit'])
      <li><a href="{{ route('shippingcharges.index') }}"><i data-feather="file-plus"></i> {{ __('Shipping Charge') }} </a></li>
      @endcanany
      <li><a href="{{ route('admin.district.index') }}"><i data-feather="map-pin"></i> {{ __('Districts') }} </a></li>
    </ul>
  </div>
</li>
@endcanany

@can('email-setting-list')
<li class="{{ request()->routeIs('email_setting*') ? 'active' : '' }}">
  <a href="{{ route('email_setting') }}">
    <i data-feather="mail"></i>
    <span>{{ __('Email Settings') }}</span>
  </a>
</li>
@endcan

@canany(['theme-list', 'theme-create', 'theme-edit'])
<li>
  <a href="#sidebar-theme" data-bs-toggle="collapse">
    <i data-feather="feather"></i>
    <span> {{ __('Theme System') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-theme">
    <ul class="nav-second-level">
      @can('theme-list')
      <li><a href="{{ route('themes.index') }}"><i data-feather="file-plus"></i> {{ __('Theme Manager') }} </a></li>
      @endcan
      @can('theme-create')
      <li><a href="{{ route('themes.create') }}"><i data-feather="file-plus"></i> {{ __('Create Theme') }} </a></li>
      @endcan
      @canany(['layout-list', 'layout-create'])
      <li><a href="{{ route('layouts.index') }}"><i data-feather="file-plus"></i> {{ __('Layout Builder') }} </a></li>
      @endcanany
      <li><a href="{{ route('headerfooter.index') }}"><i data-feather="layout"></i> {{ __('Header & Footer') }} </a></li>
      @canany(['theme-list', 'theme-create', 'theme-edit'])
      <li><a href="{{ route('product.design') }}"><i data-feather="grid"></i> {{ __('Product Design') }} </a></li>
      @endcanany
      <li><a href="{{ route('demo.index') }}"><i data-feather="upload"></i> {{ __('Demo Import/Export') }} </a></li>
    </ul>
  </div>
</li>
@endcanany

@canany(['api-manage'])
<li>
  <a href="#sidebar-api-integration" data-bs-toggle="collapse">
    <i data-feather="save"></i>
    <span> {{ __('API Integration') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-api-integration">
    <ul class="nav-second-level">
      <li><a href="{{ route('paymentgeteway.manage') }}"><i data-feather="file-plus"></i> {{ __('Payment Gateway') }} </a></li>
      <li><a href="{{ route('smsgeteway.manage') }}"><i data-feather="file-plus"></i> {{ __('SMS Gateway') }} </a></li>
      <li><a href="{{ route('courierapi.manage') }}"><i data-feather="file-plus"></i> {{ __('Courier API') }} </a></li>
      <li><a href="{{ route('admin.facebook_capi.edit') }}"><i data-feather="facebook"></i> {{ __('Facebook CAPI') }} </a></li>
      <li><a href="{{ route('admin.google_analytics.edit') }}"><i data-feather="bar-chart-2"></i> {{ __('Google Analytics 4') }} </a></li>
    </ul>
  </div>
</li>
@endcanany

{{-- ============================================= --}}
{{--  SECTION 11: SECURITY                         --}}
{{-- ============================================= --}}
@canany(['fraud-setting-list', 'fraud-setting-edit'])
<li>
  <a href="#sidebar-fraud" data-bs-toggle="collapse">
    <i data-feather="shield"></i>
    <span> {{ __('Fraud API Settings') }} </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-fraud">
    <ul class="nav-second-level">
      @can('fraud-setting-list')
      <li><a href="{{ route('admin.fraud.index') }}"><i data-feather="key"></i> {{ __('Manage Fraud API') }} </a></li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

@can('fraud-check')
<li>
  <a href="{{ route('manualFraud.page') }}">
    <i data-feather="search"></i>
    <span> {{ __('Manual Fraud Check') }} </span>
  </a>
</li>
@endcan

@canany(['setting-list', 'setting-edit'])
<li>
  <a href="{{ route('admin.order.restriction.setting.index') }}">
    <i data-feather="clock"></i>
    <span> {{ __('Order Restriction') }} </span>
  </a>
</li>
@endcanany

{{-- ============================================= --}}
{{--  SECTION 12: SYSTEM                           --}}
{{-- ============================================= --}}
@canany(['api-manage'])
<li>
  <a href="{{ route('admin.cron.index') }}">
    <i data-feather="clock"></i>
    <span> {{ __('Cron Job') }} </span>
  </a>
</li>
@endcanany

@can('seo-manage')
<li class="{{ request()->routeIs('admin.seo_settings.*') ? 'active' : '' }}">
  <a href="{{ route('admin.seo_settings.index') }}">
    <i data-feather="globe"></i>
    <span>{{ __('SEO Settings') }}</span>
  </a>
</li>
@endcan

@can('sitemap-manage')
<li class="{{ request()->routeIs('admin.sitemap.*') ? 'active' : '' }}">
    <a href="{{ route('admin.sitemap.index') }}">
        <i data-feather="map"></i>
        <span> {{ __('Sitemap Settings') }} </span>
    </a>
</li>
@endcan

<li>
  <a href="{{ route('admin.clear.cache') }}"
     onclick="return confirm('Are you sure you want to clear all cache?')">
    <i data-feather="refresh-cw"></i>
    <span>{{ __('Clear Cache') }}</span>
  </a>
</li>

<li>
  <a href="{{ route('error-log.index') }}">
    <i data-feather="file-text"></i>
    <span> {{ __('Error Log') }} </span>
  </a>
</li>

<li>
  <a href="{{ route('admin.activity_logs.index') }}">
    <i data-feather="shield"></i>
    <span>{{ __('Activity Logs') }}</span>
  </a>
</li>
            </ul>
          </div>
		  
		  
		  

          <!-- End Sidebar -->

          <div class="clearfix"></div>
        </div>
        <!-- Sidebar -left -->
      </div>
      <!-- Left Sidebar End -->

      <div class="content-page">
        <div class="content">
          @yield('content')
        </div>
        <!-- content -->

        <!-- end Footer -->
      </div>
    </div>
    <!-- END wrapper -->

    <!-- Right Sidebar -->
    <div class="right-bar">
      <div data-simplebar class="h-100">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs nav-bordered nav-justified" role="tablist">
          <li class="nav-item">
            <a class="nav-link py-2" data-bs-toggle="tab" href="#chat-tab" role="tab">
              <i class="mdi mdi-message-text d-block font-22 my-1"></i>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-2" data-bs-toggle="tab" href="#tasks-tab" role="tab">
              <i class="mdi mdi-format-list-checkbox d-block font-22 my-1"></i>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-2 active" data-bs-toggle="tab" href="#settings-tab" role="tab">
              <i class="mdi mdi-cog-outline d-block font-22 my-1"></i>
            </a>
          </li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content pt-0">
          <div class="tab-pane" id="chat-tab" role="tabpanel">
            <form class="search-bar p-3">
              <div class="position-relative">
                <input type="text" class="form-control" placeholder="Search..." />
                <span class="mdi mdi-magnify"></span>
              </div>
            </form>
          </div>

          <div class="tab-pane" id="tasks-tab" role="tabpanel">
            <h6 class="fw-medium p-3 m-0 text-uppercase"> {{ __('Working Tasks') }} </h6>
          </div>
          <div class="tab-pane active" id="settings-tab" role="tabpanel">
            <h6 class="fw-medium px-3 m-0 py-2 font-13 text-uppercase bg-light">
              <span class="d-block py-1">{{ __('Theme Settings') }}</span>
            </h6>

            <div class="p-3">
              <div class="alert alert-warning" role="alert"><strong> {{ __('Customize') }} </strong> {{ __('the overall color scheme, sidebar menu, etc.') }} </div>

              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1"> {{ __('Color Scheme') }} </h6>
              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-color" value="light" id="light-mode-check" checked />
                <label class="form-check-label" for="light-mode-check"> {{ __('Light Mode') }} </label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-color" value="dark" id="dark-mode-check" />
                <label class="form-check-label" for="dark-mode-check"> {{ __('Dark Mode') }} </label>
              </div>

              <!-- Width -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1"> {{ __('Width') }} </h6>
              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-width" value="fluid" id="fluid-check" checked />
                <label class="form-check-label" for="fluid-check"> {{ __('Fluid') }} </label>
              </div>
              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-width" value="boxed" id="boxed-check" />
                <label class="form-check-label" for="boxed-check"> {{ __('Boxed') }} </label>
              </div>

              <!-- Menu positions -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1"> {{ __('Menus (Leftsidebar and Topbar) Positon') }} </h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="menu-position" value="fixed" id="fixed-check" checked />
                <label class="form-check-label" for="fixed-check"> {{ __('Fixed') }} </label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="menu-position" value="scrollable" id="scrollable-check" />
                <label class="form-check-label" for="scrollable-check"> {{ __('Scrollable') }} </label>
              </div>

              <!-- Left Sidebar-->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1"> {{ __('Left Sidebar Color') }} </h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="light" id="light-check" />
                <label class="form-check-label" for="light-check"> {{ __('Light') }} </label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="dark" id="dark-check" checked />
                <label class="form-check-label" for="dark-check"> {{ __('Dark') }} </label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="brand" id="brand-check" />
                <label class="form-check-label" for="brand-check">{{ __('Brand') }}</label>
              </div>

              <div class="form-check form-switch mb-3">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="gradient" id="gradient-check" />
                <label class="form-check-label" for="gradient-check"> {{ __('Gradient') }} </label>
              </div>

              <!-- size -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1"> {{ __('Left Sidebar Size') }} </h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-size" value="default" id="default-size-check" checked />
                <label class="form-check-label" for="default-size-check">{{ __('Default') }}</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-size" value="condensed" id="condensed-check" />
                <label class="form-check-label" for="condensed-check"> {{ __('Condensed') }} <small>(Extra Small size)</small></label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-size" value="compact" id="compact-check" />
                <label class="form-check-label" for="compact-check"> {{ __('Compact') }} <small>(Small size)</small></label>
              </div>

              <!-- User info -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1"> {{ __('Sidebar User Info') }} </h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="sidebar-user" value="fixed" id="sidebaruser-check" />
                <label class="form-check-label" for="sidebaruser-check"> {{ __('Enable') }} </label>
              </div>

              <!-- Topbar -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1"> {{ __('Topbar') }} </h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="topbar-color" value="dark" id="darktopbar-check" checked />
                <label class="form-check-label" for="darktopbar-check"> {{ __('Dark') }} </label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="topbar-color" value="light" id="lighttopbar-check" />
                <label class="form-check-label" for="lighttopbar-check"> {{ __('Light') }} </label>
              </div>

              <div class="d-grid mt-4">
                <button class="btn btn-primary" id="resetBtn"> {{ __('Reset to Default') }} </button>
                <a href="https://1.envato.market/uboldadmin" class="btn btn-danger mt-3" target="_blank"><i class="mdi mdi-basket me-1"></i> {{ __('Purchase Now') }} </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- end slimscroll-menu-->
    </div>
    <!-- /Right-bar -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="{{asset('public/backEnd/')}}/assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="{{asset('public/backEnd/')}}/assets/js/app.min.js"></script>

    {{-- Fix: stop the Hyper sidebar from auto-scrolling to the active menu item
         on every page load (app.min.js smooth-scrolls the sidebar so the active
         item is visible; this is jarring for deep submenu links). We instead
         restore the sidebar's previous scroll position (or top on first visit). --}}
    <script>
        (function () {
            var KEY = 'admin_sidebar_scroll';
            function sidebar() {
                return document.querySelector('.left-side-menu .simplebar-content-wrapper');
            }
            // Remember where the sidebar was before navigating away
            window.addEventListener('beforeunload', function () {
                var w = sidebar();
                if (w) { try { sessionStorage.setItem(KEY, String(w.scrollTop)); } catch (e) {} }
            });
            // After app.min.js's auto-scroll fires (~200ms + 600ms animation), put it back
            function restore() {
                var w = sidebar();
                if (!w) return;
                var saved = parseInt(sessionStorage.getItem(KEY) || '0', 10) || 0;
                w.scrollTop = saved;
            }
            if (document.readyState === 'complete') {
                setTimeout(restore, 250); setTimeout(restore, 900);
            } else {
                window.addEventListener('load', function () {
                    setTimeout(restore, 250); // interrupt app.min.js's scroll early
                    setTimeout(restore, 900); // final restore after the animation
                });
            }
        })();
    </script>

    <!-- Feather Icons - Ensure library is loaded and initialized -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        // Force Feather Icons initialization after all scripts load
        (function() {
            'use strict';
            
            function initFeather() {
                if (typeof feather !== 'undefined' && typeof feather.replace === 'function') {
                    try {
                        feather.replace();
                        return true;
                    } catch(e) {
                        console.error('Feather replace error:', e);
                        return false;
                    }
                }
                return false;
            }
            
            // Wait for scripts to load
            function waitForFeather(callback, maxAttempts) {
                maxAttempts = maxAttempts || 50;
                var attempts = 0;
                
                var checkInterval = setInterval(function() {
                    attempts++;
                    if (typeof feather !== 'undefined' && typeof feather.replace === 'function') {
                        clearInterval(checkInterval);
                        if (callback) callback();
                    } else if (attempts >= maxAttempts) {
                        clearInterval(checkInterval);
                        console.warn('Feather icons library not found after', maxAttempts, 'attempts');
                    }
                }, 100);
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    waitForFeather(function() {
                        setTimeout(initFeather, 100);
                    });
                });
            } else {
                waitForFeather(function() {
                    setTimeout(initFeather, 100);
                });
            }
            
            // Also initialize on window load
            window.addEventListener('load', function() {
                waitForFeather(function() {
                    setTimeout(initFeather, 200);
                });
            });
            
            // jQuery ready handler
            if (typeof jQuery !== 'undefined') {
                jQuery(document).ready(function($) {
                    waitForFeather(function() {
                        setTimeout(initFeather, 150);
                    });
                    
                    // Reinitialize on menu collapse/expand
                    $(document).on('shown.bs.collapse hidden.bs.collapse', '[data-bs-toggle="collapse"]', function() {
                        setTimeout(initFeather, 100);
                    });
                    
                    // Watch sidebar for DOM changes
                    var sidebarEl = document.getElementById('sidebar-menu');
                    if (sidebarEl && typeof MutationObserver !== 'undefined') {
                        var observer = new MutationObserver(function() {
                            setTimeout(initFeather, 50);
                        });
                        observer.observe(sidebarEl, {
                            childList: true,
                            subtree: true
                        });
                    }
                });
            }
            
            // Fallback: Check periodically for unrendered icons
            setTimeout(function() {
                var checkInterval = setInterval(function() {
                    var unrendered = document.querySelectorAll('[data-feather]:not(svg)');
                    if (unrendered.length === 0) {
                        clearInterval(checkInterval);
                    } else {
                        initFeather();
                    }
                }, 500);
                
                // Stop checking after 10 seconds
                setTimeout(function() {
                    clearInterval(checkInterval);
                }, 10000);
            }, 1000);
        })();
    </script>
    <script src="{{asset('public/backEnd/')}}/assets/js/toastr.min.js"></script>
    <script src="{{asset('public/backEnd/')}}/assets/js/sweetalert.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {!! Toastr::message() !!}
	<script>
@if(Session::has('success'))
    toastr.success("{{ Session::get('success') }}");
@endif
@if(Session::has('error') && !Session::has('demo_mode_blocked'))
    toastr.error("{{ Session::get('error') }}");
@endif
@if(Session::has('info'))
    toastr.info("{{ Session::get('info') }}");
@endif
@if(Session::has('warning'))
    toastr.warning("{{ Session::get('warning') }}");
@endif
@if(Session::has('demo_mode_blocked'))
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: '<strong style="font-size:1.4rem;color:#2c3e50;">ডেমো মুড সক্রিয়</strong>',
            html: '<div style="text-align:center;padding:10px 0;"><div style="width:70px;height:70px;margin:0 auto 15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><i class="fe-eye" style="font-size:32px;color:#fff;"></i></div><p style="font-size:1rem;color:#5a6c7d;margin-bottom:8px;line-height:1.6;">অ্যাডমিন প্যানেল থেকে কোন ডাটা পরিবর্তন বা সংযোজন করা যাবে না।</p><p style="font-size:0.9rem;color:#95a5a6;margin:0;">কাস্টমার সাইটে অর্ডার, ট্রাকিং ও অন্যান্য সেবা স্বাভাবিকভাবে কাজ করবে।</p></div>',
            confirmButtonText: 'বুঝেছি',
            confirmButtonColor: '#667eea',
            customClass: { popup: 'demo-mode-popup', confirmButton: 'demo-mode-btn' },
            width: '420px',
            backdrop: 'rgba(0,0,0,0.5)',
        });
    } else {
        toastr.info("ডেমো মুড চালু আছে। অ্যাডমিন প্যানেল থেকে কোন পরিবর্তন করা যাবে না।");
    }
@endif
</script>
    <style>
    .demo-mode-popup { border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    .demo-mode-btn { padding: 10px 28px; font-weight: 600; border-radius: 8px; }
    </style>
    <script>
    function showDemoModeAlert(msg) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: '<strong style="font-size:1.4rem;color:#2c3e50;">ডেমো মুড সক্রিয়</strong>',
                html: '<div style="text-align:center;padding:10px 0;"><div style="width:70px;height:70px;margin:0 auto 15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><i class="fe-eye" style="font-size:32px;color:#fff;"></i></div><p style="font-size:1rem;color:#5a6c7d;margin-bottom:8px;line-height:1.6;">' + (msg || 'অ্যাডমিন প্যানেল থেকে কোন ডাটা পরিবর্তন বা সংযোজন করা যাবে না।') + '</p><p style="font-size:0.9rem;color:#95a5a6;margin:0;">কাস্টমার সাইটে অর্ডার, ট্রাকিং ও অন্যান্য সেবা স্বাভাবিকভাবে কাজ করবে।</p></div>',
                confirmButtonText: 'বুঝেছি',
                confirmButtonColor: '#667eea',
                customClass: { popup: 'demo-mode-popup', confirmButton: 'demo-mode-btn' },
                width: '420px',
                backdrop: 'rgba(0,0,0,0.5)',
            });
        }
    }
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (xhr.status === 403) {
            try {
                var data = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText || '{}');
                if (data.demo_mode && typeof Swal !== 'undefined') {
                    showDemoModeAlert(data.message || '');
                }
            } catch (e) {}
        }
    });
    </script>
    <script type="text/javascript">
      $(document).on('click', '.delete-confirm', function (event) {
        event.preventDefault();
        var form = $(this).closest("form");
        @if(isset($demoMode) && $demoMode)
        showDemoModeAlert();
        return;
        @endif
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
          }).then(function(result) {
            if (result.isConfirmed) { form.submit(); }
          });
        } else {
          if (confirm('Are you sure you want to delete this record?')) { form.submit(); }
        }
      });
      $(document).on('click', '.change-confirm', function (event) {
        event.preventDefault();
        var form = $(this).closest("form");
        @if(isset($demoMode) && $demoMode)
        showDemoModeAlert();
        return;
        @endif
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Are you sure you want to change this record?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, update it!'
          }).then(function(result) {
            if (result.isConfirmed) { form.submit(); }
          });
        } else if (typeof swal !== 'undefined') {
          swal({
            title: "Are you sure you want to change this record?",
            icon: "warning",
            buttons: true,
            dangerMode: true,
          }).then((willChange) => {
            if (willChange) { form.submit(); }
          });
        } else {
          if (confirm('Are you sure you want to change this record?')) { form.submit(); }
        }
      });
      @if(isset($demoMode) && $demoMode)
      $(document).on('submit', 'form', function(e) {
        var action = (this.action || '').toLowerCase();
        if (action.indexOf('logout') !== -1) return;
        var method = ($(this).find('input[name="_method"]').val() || $(this).attr('method') || 'get').toLowerCase();
        if (method === 'get') return;
        e.preventDefault();
        showDemoModeAlert();
        return false;
      });
      document.addEventListener('click', function(e) {
        var el = e.target.closest ? e.target.closest('a[href*="destroy"], a[href*="bulk_destroy"], a[href*="/delete"], a.order_delete') : null;
        if (el && el.href && el.href.indexOf('#') !== 0) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          showDemoModeAlert();
          return false;
        }
      }, true);
      @endif
    </script>
    <!--patho courier-->
    <script type="text/javascript">
        $(document).ready(function() {
            $('.pathaocity').change(function() {
                var id = $(this).val();
                if (id) {
                    $.ajax({
                        type: "GET",
                        url: "{{ url('admin/pathao-city') }}?city_id=" + id,
                        success: function(res) {
                            if (res && res.data && res.data.data) {
                                $(".pathaozone").empty();
                                $(".pathaozone").append('<option value=""> {{ __('Select..') }} </option>');
                                $.each(res.data.data, function(index, zone) {
                                    $(".pathaozone").append('<option value="' + zone.zone_id + '">' + zone.zone_name + '</option>');
                                    $('.pathaozone').trigger("chosen:updated");
                                });
                            } else {
                                 $(".pathaoarea").empty();
                                $(".pathaozone").empty();
                            }
                        }
                    });
                } else {
                     $(".pathaoarea").empty();
                    $(".pathaozone").empty();
                }
            });
        });
    </script>
    <script type="text/javascript"> 
        $(document).ready(function() {
            $('.pathaozone').change(function() {
                var id = $(this).val();
                if (id) {
                    $.ajax({
                        type: "GET",
                        url: "{{ url('admin/pathao-zone') }}?zone_id=" + id,
                        success: function(res) {
                            if (res && res.data && res.data.data) {
                                $(".pathaoarea").empty();
                                $(".pathaoarea").append('<option value=""> {{ __('Select..') }} </option>');
                                $.each(res.data.data, function(index, area) {
                                    $(".pathaoarea").append('<option value="' + area.area_id + '">' + area.area_name + '</option>');
                                    $('.pathaoarea').trigger("chosen:updated");
                                });
                            } else {
                                $(".pathaoarea").empty();
                            }
                        }
                    });
                } else {
                    $(".pathaoarea").empty();
                }
            });
        });
    </script>
    @yield('script')
    @stack('scripts')
  </body>
</html>
