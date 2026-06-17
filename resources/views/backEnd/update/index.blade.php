@extends('backEnd.layouts.master')
@section('title', 'System Updates')

@section('content')

{{-- Professional Update Page Styles --}}
<style>
    :root {
        --up-primary: #4f46e5;
        --up-success: #10b981;
        --up-danger: #ef4444;
        --up-warning: #f59e0b;
        --up-text: #1e293b;
        --up-muted: #64748b;
        --up-border: #e2e8f0;
        --up-bg: #f8fafc;
    }

    .update-page {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        padding: 24px 0 48px;
        background: var(--up-bg);
        min-height: 100%;
    }

    /* Script Hero Banner */
    .script-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        border-radius: 16px;
        padding: 32px 40px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 24px;
        box-shadow: 0 10px 40px -10px rgba(79, 70, 229, 0.4);
        margin-bottom: 24px;
    }
    .script-hero-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .script-hero-icon {
        width: 64px;
        height: 64px;
        background: rgba(255,255,255,0.2);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
    }
    .script-name {
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        margin-bottom: 4px;
    }
    .script-version {
        font-size: 1rem;
        opacity: 0.9;
        font-weight: 500;
    }
    .script-version-num {
        display: inline-block;
        background: rgba(255,255,255,0.25);
        padding: 4px 12px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 1.1rem;
    }
    .script-hero-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .license-badge {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .license-badge.valid {
        background: rgba(16, 185, 129, 0.9);
    }
    .license-badge.invalid {
        background: rgba(239, 68, 68, 0.9);
    }

    /* Main Card */
    .update-main-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--up-border);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .update-card-header {
        padding: 20px 28px;
        border-bottom: 1px solid var(--up-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }
    .update-card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--up-text);
        margin: 0;
    }
    .update-card-body {
        padding: 28px;
    }

    /* Update Item Cards (for each available update) */
    .update-item {
        background: #fff;
        border: 1px solid var(--up-border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 16px;
        transition: all 0.2s;
    }
    .update-item:last-child { margin-bottom: 0; }
    .update-item:hover {
        border-color: var(--up-primary);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08);
    }
    .update-item-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 16px;
    }
    .update-item-version {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--up-primary);
    }
    .update-item-version .badge {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 1rem;
    }
    .update-item-notes {
        color: var(--up-muted);
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 16px;
    }
    .update-item-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .btn-update-action {
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        transition: all 0.2s;
    }
    .btn-update-action:hover {
        transform: translateY(-1px);
    }
    .btn-download {
        background: var(--up-success);
        color: white;
    }
    .btn-download:hover {
        background: #059669;
        color: white;
    }
    .btn-install {
        background: var(--up-primary);
        color: white;
    }
    .btn-install:hover {
        background: #4338ca;
        color: white;
    }
    .btn-check {
        background: var(--up-primary);
        color: white;
        padding: 12px 24px;
        cursor: pointer;
    }
    .btn-check:not(:disabled):hover {
        background: #4338ca;
        color: white;
    }
    .btn-update-action {
        cursor: pointer;
    }

    /* Empty / Up to date state */
    .update-empty-state {
        text-align: center;
        padding: 48px 24px;
        color: var(--up-muted);
    }
    .update-empty-state .icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    .update-empty-state.up-to-date {
        color: var(--up-success);
    }

    /* System Info Grid */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 16px;
    }
    .info-item {
        text-align: center;
        padding: 16px;
        background: var(--up-bg);
        border-radius: 10px;
    }
    .info-item-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--up-muted);
        margin-bottom: 4px;
    }
    .info-item-value {
        font-weight: 600;
        font-size: 14px;
        color: var(--up-text);
    }

    /* Alert */
    .alert-update {
        border-radius: 10px;
        border: none;
        padding: 16px 20px;
    }
</style>

<div class="content-wrapper">
    <div class="container-fluid update-page">
        
        {{-- Page Title --}}
        <div class="d-flex align-items-center mb-4">
            <h3 class="m-0 fw-bold text-dark">
                <i class="fas fa-sync-alt text-primary me-2"></i> System Updates
            </h3>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-11 col-xl-10">
                
                {{-- 1. Script Name & Version Hero --}}
                <div class="script-hero">
                    <div class="script-hero-left">
                        <div class="script-hero-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div>
                            @php
    $displayScriptName = (isset($scriptName) && is_string($scriptName) && trim($scriptName) !== '') ? trim($scriptName) : 'Ecommerce Pro';
@endphp
<div class="script-name">{{ $displayScriptName }}</div>
                            <div class="script-version">
                                বর্তমান ভার্সন: <span class="script-version-num">v{{ $currentVersion }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="script-hero-right">
                        <span class="license-badge {{ $licenseValid ? 'valid' : 'invalid' }}">
                            <i class="fas fa-{{ $licenseValid ? 'shield-check' : 'exclamation-triangle' }}"></i>
                            {{ $licenseValid ? 'License Verified' : 'License Invalid' }}
                        </span>
                    </div>
                </div>

                {{-- License invalid alert --}}
                @if(!$licenseValid)
                <div class="alert alert-danger alert-update mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>আপডেট সীমাবদ্ধ:</strong> আপডেট ডাউনলোড ও ইনস্টল করতে ভ্যালিড লাইসেন্স প্রয়োজন। 
                    <a href="{{ route('admin.license.info') }}" class="alert-link">লাইসেন্স সেটিংস</a> এ যান।
                </div>
                @endif

                {{-- Localhost warning --}}
                @php
                    $host = request()->getHost();
                    $domain = str_replace('www.', '', $host);
                    $isLocal = in_array($domain, ['127.0.0.1', 'localhost']);
                @endphp
                @if($isLocal && $licenseValid)
                <div class="alert alert-warning alert-update mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>লোকালহোস্ট পরিবেশ:</strong> আপনি XAMPP/localhost-এ রান করছেন। আপডেট চেক করার জন্য প্রোডাকশন ডোমেইন এবং ভ্যালিড লাইসেন্স প্রয়োজন। 
                    <a href="{{ route('admin.license.info') }}" class="alert-link">লাইসেন্স সেটিংস</a> এ যান।
                </div>
                @endif

                {{-- 2. Main Update Section --}}
                <div class="update-main-card">
                    <div class="update-card-header">
                        <h4 class="update-card-title mb-0">
                            <i class="fas fa-cloud-download-alt me-2 text-primary"></i> উপলব্ধ আপডেট
                        </h4>
                    </div>
                    <div class="update-card-body">
                        
                        {{-- Update Status: লোডিং স্টেট, আপডেট কার্ড অথবা আপ-টু-ডেট মেসেজ JS দ্বারা সেট হবে --}}
                        <div id="updateStatus">
                            <div class="update-empty-state">
                                <div class="icon"><i class="fas fa-spinner fa-spin"></i></div>
                                <p class="mb-0">আপডেট চেক করা হচ্ছে...</p>
                            </div>
                        </div>

                        {{-- Available Updates List (populated by JS) --}}
                        <div id="updateList" style="display: none;"></div>

                        {{-- Update Actions (Download/Install - shown when update available) --}}
                        <div id="updateActions" style="display: none;"></div>

                        {{-- Backup Files Section - Always visible, works without update --}}
                        <hr class="my-4">
                        <h5 class="mb-3"><i class="fas fa-database me-2 text-info"></i> ব্যাকআপ ফাইল</h5>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <button type="button" id="createBackupBtn" class="btn btn-info btn-sm">
                                <i class="fas fa-save me-1"></i> ব্যাকআপ তৈরি করুন
                            </button>
                            <small class="text-muted">কোড জিপ ও ডাটাবেইস ব্যাকআপ তৈরি করে ডাউনলোড করুন</small>
                        </div>
                        <div id="backupList" class="mb-4">
                            <div class="update-empty-state py-3">
                                <div class="icon"><i class="fas fa-spinner fa-spin"></i></div>
                                <p class="mb-0 small">ব্যাকআপ লোড করা হচ্ছে...</p>
                            </div>
                        </div>

                        {{-- System Info --}}
                        <hr class="my-4">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-item-label">Environment</div>
                                <div class="info-item-value">{{ request()->getHost() }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">PHP</div>
                                <div class="info-item-value">{{ PHP_VERSION }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Laravel</div>
                                <div class="info-item-value">{{ app()->version() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script>
    let currentVersion = '{{ $currentVersion }}';
    let scriptName = '{{ $scriptName }}';
    let licenseValid = {{ $licenseValid ? 'true' : 'false' }};
    let demoMode = {{ (isset($demoMode) && $demoMode) ? 'true' : 'false' }};
    let availableUpdates = [];

    function renderUpdateItems(updates) {
        if (!updates || updates.length === 0) return '';
        let html = '';
        updates.forEach(function(u, idx) {
            html += `
                <div class="update-item" data-version="${u.version}">
                    <div class="update-item-header">
                        <span class="update-item-version">
                            <span class="badge bg-primary">v${u.version}</span>
                        </span>
                    </div>
                    ${u.release_notes ? '<div class="update-item-notes">' + u.release_notes + '</div>' : ''}
                    <div class="update-item-actions">
                        <button type="button" class="btn btn-primary btn-update-action btn-update-single" data-version="${u.version}" data-download-url="${u.download_url || ''}">
                            <i class="fas fa-download me-2"></i> আপডেট করুন
                        </button>
                    </div>
                </div>
            `;
        });
        return html;
    }

    function doCheckUpdates() {
        var statusDiv = document.getElementById('updateStatus');
        var listDiv = document.getElementById('updateList');
        var actionsDiv = document.getElementById('updateActions');

        if (!licenseValid) {
            statusDiv.innerHTML = `
                <div class="alert alert-danger alert-update mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>ত্রুটি:</strong> লাইসেন্স যাচাই ব্যর্থ। লাইসেন্স কী চেক করুন।
                </div>
            `;
            return;
        }

        statusDiv.innerHTML = '<div class="update-empty-state"><div class="icon"><i class="fas fa-spinner fa-spin"></i></div><p class="mb-0">আপডেট চেক করা হচ্ছে...</p></div>';

        fetch('{{ route("admin.updates.check") }}', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                if (data.updates_available) {
                    var updates = [];
                    if (data.update_info && data.update_info.versions && Array.isArray(data.update_info.versions)) {
                        updates = data.update_info.versions;
                    } else {
                        updates = [{
                            version: data.latest_version,
                            release_notes: data.release_notes || '',
                            download_url: data.download_url || null
                        }];
                    }
                    availableUpdates = updates;
                    statusDiv.innerHTML = '';
                    listDiv.innerHTML = renderUpdateItems(updates);
                    listDiv.style.display = 'block';
                    actionsDiv.style.display = 'none';
                } else {
                    statusDiv.innerHTML = `
                        <div class="update-empty-state up-to-date">
                            <div class="icon"><i class="fas fa-check-circle"></i></div>
                            <strong>আপনার স্ক্রিপ্ট আপ টু ডেট ও লেটেস্ট ভার্শন ইউজ করতেছেন।</strong>
                            <p class="mb-0 mt-2">বর্তমান ভার্সন: <span class="badge bg-success">v${data.current_version}</span></p>
                        </div>
                    `;
                    listDiv.style.display = 'none';
                    actionsDiv.style.display = 'none';
                }
            } else {
                statusDiv.innerHTML = `
                    <div class="alert alert-danger alert-update mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>ত্রুটি:</strong> ${data.message}
                    </div>
                `;
                listDiv.style.display = 'none';
            }
        })
        .catch(function(err) {
            statusDiv.innerHTML = `
                <div class="alert alert-danger alert-update mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>ত্রুটি:</strong> আপডেট চেক করতে ব্যর্থ। আবার চেষ্টা করুন।
                </div>
            `;
        });
    }

    function loadBackups() {
        var div = document.getElementById('backupList');
        fetch('{{ route("admin.updates.backups") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success' && data.backups && data.backups.length > 0) {
                var html = '<div class="row g-3">';
                data.backups.forEach(function(b) {
                    var size = (b.size / 1024).toFixed(1) + ' KB';
                    if (b.size > 1024 * 1024) size = (b.size / 1024 / 1024).toFixed(2) + ' MB';
                    var typeLabel = b.type === 'code' ? 'কোড জিপ' : (b.type === 'database' ? 'ডাটাবেইস' : 'ফাইল');
                    var icon = b.type === 'code' ? 'fa-file-archive' : (b.type === 'database' ? 'fa-database' : 'fa-file');
                    html += '<div class="col-md-6 col-lg-4"><div class="update-item d-flex align-items-center justify-content-between p-3">';
                    html += '<div><i class="fas ' + icon + ' me-2 text-primary"></i><small class="text-muted">' + typeLabel + '</small><br><span class="small fw-bold">' + b.name + '</span><br><span class="text-muted" style="font-size:11px">' + size + '</span></div>';
                    var downloadUrl = '{{ route("admin.updates.backup.download", ["filename" => "__NAME__"]) }}'.replace('__NAME__', encodeURIComponent(b.name));
                    html += '<a href="' + downloadUrl + '" class="btn btn-sm btn-success btn-download" target="_blank"><i class="fas fa-download me-1"></i> ডাউনলোড</a>';
                    html += '</div></div>';
                });
                html += '</div>';
                div.innerHTML = html;
            } else {
                div.innerHTML = '<div class="update-empty-state py-3"><div class="icon"><i class="fas fa-folder-open"></i></div><p class="mb-0 small text-muted">কোন ব্যাকআপ ফাইল নেই। আপডেট ইন্সটল করলে ব্যাকআপ তৈরি হবে।</p></div>';
            }
        })
        .catch(function() {
            div.innerHTML = '<div class="update-empty-state py-3"><p class="mb-0 small text-danger">ব্যাকআপ লোড করতে ব্যর্থ।</p></div>';
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('#backupList .btn-download')) {
            if (demoMode) {
                e.preventDefault();
                if (typeof showDemoModeAlert !== 'undefined') showDemoModeAlert('ব্যাকআপ ডাউনলোড ডেমো মুডে বন্ধ আছে।');
                else if (typeof Swal !== 'undefined') Swal.fire('ডেমো মুড', 'ব্যাকআপ ডাউনলোড ডেমো মুডে বন্ধ আছে।', 'info');
                else alert('ডেমো মুড সক্রিয়। ব্যাকআপ ডাউনলোড করা যাবে না।');
                return;
            }
        }
        if (e.target.closest('#createBackupBtn')) {
            if (demoMode) {
                if (typeof showDemoModeAlert !== 'undefined') showDemoModeAlert('ব্যাকআপ তৈরি ডেমো মুডে বন্ধ আছে।');
                else if (typeof Swal !== 'undefined') Swal.fire('ডেমো মুড', 'ব্যাকআপ তৈরি ডেমো মুডে বন্ধ আছে।', 'info');
                else alert('ডেমো মুড সক্রিয়। ব্যাকআপ তৈরি করা যাবে না।');
                return;
            }
            var btn = document.getElementById('createBackupBtn');
            if (btn.disabled) return;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>ব্যাকআপ তৈরি হচ্ছে...';
            fetch('{{ route("admin.updates.create-backup") }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    loadBackups();
                    if (typeof Swal !== 'undefined') Swal.fire('সফল!', data.message, 'success');
                    else alert(data.message);
                } else throw new Error(data.message || 'ব্যাকআপ ব্যর্থ');
            })
            .catch(function(err) {
                if (typeof Swal !== 'undefined') Swal.fire('ত্রুটি!', err.message, 'error');
                else alert('ত্রুটি: ' + err.message);
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i> ব্যাকআপ তৈরি করুন';
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        doCheckUpdates();
        loadBackups();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-update-single')) {
            var btn = e.target.closest('.btn-update-single');
            var version = btn.getAttribute('data-version');
            var downloadUrl = btn.getAttribute('data-download-url') || '';

            if (!confirm('v' + version + ' আপডেট করুন? ডাউনলোড ও ইন্সটল শুরু হবে।')) return;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>ডাউনলোড হচ্ছে...';

            fetch('{{ route("admin.updates.download") }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ version: version, download_url: downloadUrl || null })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>ইন্সটল হচ্ছে...';
                    return fetch('{{ route("admin.updates.install") }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ version: version })
                    });
                } else {
                    throw new Error(data.message || 'ডাউনলোড ব্যর্থ');
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('সফল!', 'আপডেট সম্পন্ন হয়েছে।', 'success').then(function() { location.reload(); });
                    } else {
                        alert('আপডেট সম্পন্ন হয়েছে!');
                        location.reload();
                    }
                } else {
                    throw new Error(data.message || 'ইন্সটল ব্যর্থ');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download me-2"></i> আপডেট করুন';
                alert('ত্রুটি: ' + (err.message || 'আপডেট ব্যর্থ।'));
            });
        }
    });
</script>

@endsection
