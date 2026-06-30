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
        border-radius: {{ __('14px') }};
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

    /* Update {{ __('Item') }} Cards (for each available update) */
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
        font-size: {{ __('14px') }};
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
        font-size: {{ __('14px') }};
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
        font-size: {{ __('14px') }};
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
        
        {{-- {{ __('Page {{ __('Title') }}') }} --}}
        <div class="d-flex align-items-center mb-4">
            <h3 class="m-0 fw-bold text-dark">
                <i class="fas fa-sync-alt text-primary me-2"></i> System Updates
            </h3>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-11 col-xl-10">
                
                {{-- 1. Script {{ __('Name') }} & Version Hero --}}
                <div class="script-hero">
                    <div class="script-hero-left">
                        <div class="script-hero-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div>
                            @php
    $displayScript{{ __('Name') }} = (isset($script{{ __('Name') }}) && is_string($script{{ __('Name') }}) && trim($script{{ __('Name') }}) !== '') ? trim($script{{ __('Name') }}) : 'Ecommerce Pro';
@endphp
<div class="script-name">{{ $displayScript{{ __('Name') }} }}</div>
                            <div class="script-version">
                                {{ __('bn_311eecab') }}: <span class="script-version-num">v{{ $currentVersion }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="script-hero-right">
                        <span class="license-badge {{ $licenseValid ? 'valid' : 'invalid' }}">
                            <i class="fas fa-{{ $licenseValid ? 'shield-check' : 'exclamation-triangle' }}"></i>
                            {{ $licenseValid ? 'License {{ __('Verified') }}' : 'License {{ __('Inv') }}alid' }}
                        </span>
                    </div>
                </div>

                {{-- License invalid alert --}}
                @if(!$licenseVal{{ __('id)') }}
                <div class="alert alert-danger alert-update mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>{{ __('bn_803e7266') }}:</strong> আপডেট {{ __('Download') }} ও ইনস্টল করতে ভ্যালিড লাইসেন্স প্রয়োজন। 
                    <a href="{{ route('admin.license.info') }}" class="alert-link">{{ __('bn_fefbab51') }}</a> এ যান।
                </div>
                @endif

                {{-- Localhost warning --}}
                @php
                    $host = request()->getHost();
                    $domain = str_replace('www.', '', $host);
                    $isLocal = in_array($domain, ['127.0.0.1', 'localhost']);
                @endphp
                @if($isLocal && $licenseVal{{ __('id)') }}
                <div class="alert alert-warning alert-update mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>{{ __('bn_7a62e578') }}:</strong> আপনি XAMPP/localhost-এ রান করছেন। আপডেট চেক করার জন্য প্রোডাকশন ডোমেইন এবং ভ্যালিড লাইসেন্স প্রয়োজন। 
                    <a href="{{ route('admin.license.info') }}" class="alert-link">{{ __('bn_fefbab51') }}</a> এ যান।
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
                        
                        {{-- {{ __('Update {{ __('Status') }}') }}: লোডিং স্টেট, আপডেট কার্ড {{ __('bn_6bbacc71') }} আপ-টু-ডেট মেসেজ JS দ্বারা সেট হবে --}}
                        <div id="update{{ __('Status') }}">
                            <div class="update-empty-state">
                                <div class="icon"><i class="fas fa-spinner fa-spin"></i></div>
                                <p class="mb-0">{{ __('bn_c4e61036') }}</p>
                            </div>
                        </div>

                        {{-- Available Updates List (populated by JS) --}}
                        <div id="updateList" style="display: none;"></div>

                        {{-- Update {{ __('Actions') }} (Download/Install - shown when update available) --}}
                        <div id="update{{ __('Actions') }}" style="display: none;"></div>

                        {{-- Backup Files Section - Always visible, works without update --}}
                        <hr class="my-4">
                        <h5 class="mb-3"><i class="fas fa-database me-2 text-info"></i> {{ __('bn_2e112107') }}</h5>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <button type="button" id="createBackupBtn" class="btn btn-info btn-sm">
                                <i class="fas fa-save me-1"></i> ব্যাকআপ তৈরি করুন
                            </button>
                            <small class="text-muted">{{ __('bn_a24e53bd') }}</small>
                        </div>
                        <div id="backupList" class="mb-4">
                            <div class="update-empty-state py-3">
                                <div class="icon"><i class="fas fa-spinner fa-spin"></i></div>
                                <p class="mb-0 small">{{ __('bn_f396e666') }}</p>
                            </div>
                        </div>

                        {{-- System Info --}}
                        <hr class="my-4">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-item-label">{{ __('Environment') }}</div>
                                <div class="info-item-value">{{ request()->getHost() }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">{{ __('PHP') }}</div>
                                <div class="info-item-value">{{ {{ __('PHP') }}_VERSION }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">{{ __('Laravel') }}</div>
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
    let script{{ __('Name') }} = '{{ $script{{ __('Name') }} }}';
    let licenseValid = {{ $licenseValid ? 'true' : 'false' }};
    let demoMode = {{ (isset($demoMode) && $demoMode) ? 'true' : 'false' }};
    let availableUpdates = [];

    function renderUpdate{{ __('{{ __('Item') }}s') }}(updates) {
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

    function do{{ __('Check') }}Updates() {
        var statusDiv = document.getElementById('update{{ __('Status') }}');
        var listDiv = document.getElementById('updateList');
        var actionsDiv = document.getElementById('update{{ __('Actions') }}');

        if (!licenseVal{{ __('id)') }} {
            statusDiv.innerHTML = `
                <div class="alert alert-danger alert-update mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>{{ __('bn_8183e331') }}:</strong> লাইসেন্স যাচাই {{ __('bn_84d84036') }}। লাইসেন্স কী চেক করুন।
                </div>
            `;
            return;
        }

        statusDiv.innerHTML = '<div class="update-empty-state"><div class="icon"><i class="fas fa-spinner fa-spin"></i></div><p class="mb-0">{{ __('bn_c4e61036') }}</p></div>';

        fetch('{{ route("admin.updates.check") }}', {
            method: '{{ __('GET') }}',
            headers: { 'X-{{ __('Requested') }}-With': 'XMLHttpRequest', 'Accept': 'application/json' }
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
                    listDiv.innerHTML = renderUpdate{{ __('{{ __('Item') }}s') }}(updates);
                    listDiv.style.display = 'block';
                    actionsDiv.style.display = 'none';
                } else {
                    statusDiv.innerHTML = `
                        <div class="update-empty-state up-to-date">
                            <div class="icon"><i class="fas fa-check-circle"></i></div>
                            <strong>{{ __('bn_862fce8a') }}</strong>
                            <p class="mb-0 mt-2">{{ __('bn_311eecab') }}: <span class="badge bg-success">v${data.current_version}</span></p>
                        </div>
                    `;
                    listDiv.style.display = 'none';
                    actionsDiv.style.display = 'none';
                }
            } else {
                statusDiv.innerHTML = `
                    <div class="alert alert-danger alert-update mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>{{ __('bn_8183e331') }}:</strong> ${data.{{ __('message') }}}
                    </div>
                `;
                listDiv.style.display = 'none';
            }
        })
        .catch(function(err) {
            statusDiv.innerHTML = `
                <div class="alert alert-danger alert-update mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>{{ __('bn_8183e331') }}:</strong> আপডেট চেক করতে {{ __('bn_84d84036') }}। আবার চেষ্টা করুন।
                </div>
            `;
        });
    }

    function loadBackups() {
        var div = document.getElementById('backupList');
        fetch('{{ route("admin.updates.backups") }}', {
            headers: { 'X-{{ __('Requested') }}-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success' && data.backups && data.backups.length > 0) {
                var html = '<div class="row g-3">';
                data.backups.forEach(function(b) {
                    var size = (b.size / 1024).to{{ __('Fixed') }}(1) + ' KB';
                    if (b.size > 1024 * 1024) size = (b.size / 1024 / 1024).to{{ __('Fixed') }}(2) + ' MB';
                    var type{{ __('Label') }} = b.type === 'code' ? 'কোড জিপ' : (b.type === 'database' ? 'ডাটাবেইস' : '{{ __('File') }}');
                    var icon = b.type === 'code' ? 'fa-file-archive' : (b.type === 'database' ? 'fa-database' : 'fa-file');
                    html += '<div class="col-md-6 col-lg-4"><div class="update-item d-flex align-items-center justify-content-between p-3">';
                    html += '<div><i class="fas ' + icon + ' me-2 text-primary"></i><small class="text-muted">{{ __("' + type{{ __('Label') }} + '") }}</small><br><span class="small fw-bold">{{ __("' + b.name + '") }}</span><br><span class="text-muted" style="font-size:11px">{{ __("' + size + '") }}</span></div>';
                    var downloadUrl = '{{ route("admin.updates.backup.download", ["filename" => "__NAME__"]) }}'.replace('__NAME__', encodeURIComponent(b.name));
                    html += '<a href="' + downloadUrl + '" class="btn btn-sm btn-success btn-download" target="_blank"><i class="fas fa-download me-1"></i> {{ __('Download') }}</a>';
                    html += '</div></div>';
                });
                html += '</div>';
                div.innerHTML = html;
            } else {
                div.innerHTML = '<div class="update-empty-state py-3"><div class="icon"><i class="fas fa-folder-open"></i></div><p class="mb-0 small text-muted">{{ __('bn_ed986592') }}</p></div>';
            }
        })
        .catch(function() {
            div.innerHTML = '<div class="update-empty-state py-3"><p class="mb-0 small text-danger">{{ __('bn_5ca781ab') }}</p></div>';
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('#backupList .btn-download')) {
            if (demoMode) {
                e.preventDefault();
                if (typeof showDemoModeAlert !== 'undefined') showDemoModeAlert('ব্যাকআপ {{ __('Download') }} {{ __('bn_67543547') }} মুডে {{ __('Close') }} আছে।');
                else if (typeof Swal !== 'undefined') Swal.fire('{{ __('bn_67543547') }} মুড', 'ব্যাকআপ {{ __('Download') }} {{ __('bn_67543547') }} মুডে {{ __('Close') }} আছে।', 'info');
                else alert('{{ __('bn_391c505f') }}। ব্যাকআপ {{ __('Download') }} করা যাবে না।');
                return;
            }
        }
        if (e.target.closest('#createBackupBtn')) {
            if (demoMode) {
                if (typeof showDemoModeAlert !== 'undefined') showDemoModeAlert('ব্যাকআপ তৈরি {{ __('bn_67543547') }} মুডে {{ __('Close') }} আছে।');
                else if (typeof Swal !== 'undefined') Swal.fire('{{ __('bn_67543547') }} মুড', 'ব্যাকআপ তৈরি {{ __('bn_67543547') }} মুডে {{ __('Close') }} আছে।', 'info');
                else alert('{{ __('bn_391c505f') }}। ব্যাকআপ তৈরি করা যাবে না।');
                return;
            }
            var btn = document.getElementById('createBackupBtn');
            if (btn.disabled) return;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>ব্যাকআপ তৈরি হচ্ছে...';
            fetch('{{ route("admin.updates.create-backup") }}', {
                method: '{{ __('POST') }}',
                headers: { 'X-{{ __('Requested') }}-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    loadBackups();
                    if (typeof Swal !== 'undefined') Swal.fire('{{ __('bn_fbbc3031') }}!', data.{{ __('message') }}, 'success');
                    else alert(data.{{ __('message') }});
                } else throw new Error(data.{{ __('message') }} || 'ব্যাকআপ {{ __('bn_84d84036') }}');
            })
            .catch(function(err) {
                if (typeof Swal !== 'undefined') Swal.fire('{{ __('bn_8183e331') }}!', err.{{ __('message') }}, 'error');
                else alert('{{ __('bn_8183e331') }}: ' + err.{{ __('message') }});
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i> ব্যাকআপ তৈরি করুন';
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        do{{ __('Check') }}Updates();
        loadBackups();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-update-single')) {
            var btn = e.target.closest('.btn-update-single');
            var version = btn.getAttribute('data-version');
            var downloadUrl = btn.getAttribute('data-download-url') || '';

            if (!confirm('v' + version + ' আপডেট করুন? {{ __('Download') }} ও ইন্সটল শুরু হবে।')) return;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>{{ __('Download') }} হচ্ছে...';

            fetch('{{ route("admin.updates.download") }}', {
                method: '{{ __('POST') }}',
                headers: {
                    'X-{{ __('Requested') }}-With': 'XMLHttpRequest',
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
                        method: '{{ __('POST') }}',
                        headers: {
                            'X-{{ __('Requested') }}-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ version: version })
                    });
                } else {
                    throw new Error(data.{{ __('message') }} || '{{ __('Download') }} {{ __('bn_84d84036') }}');
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('{{ __('bn_fbbc3031') }}!', 'আপডেট সম্পন্ন {{ __('bn_290a7f61') }}েছে।', 'success').then(function() { location.reload(); });
                    } else {
                        alert('আপডেট সম্পন্ন {{ __('bn_290a7f61') }}েছে!');
                        location.reload();
                    }
                } else {
                    throw new Error(data.{{ __('message') }} || 'ইন্সটল {{ __('bn_84d84036') }}');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download me-2"></i> আপডেট করুন';
                alert('{{ __('bn_8183e331') }}: ' + (err.{{ __('message') }} || 'আপডেট {{ __('bn_84d84036') }}।'));
            });
        }
    });
</script>

@endsection
