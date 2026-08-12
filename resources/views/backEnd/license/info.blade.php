@extends('backEnd.layouts.master')
@section('title', 'License')

@section('content')

<style>
    .lic-page {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        padding: 24px 8px 48px;
        background: var(--ct-body-bg, #f8fafc);
        min-height: 100%;
    }
    .lic-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        padding: 22px 24px;
        margin-bottom: 20px;
    }
    .lic-card h3 {
        margin: 0 0 14px;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .lic-hero {
        border-radius: 16px;
        padding: 28px 32px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
    }
    .lic-hero.valid   { background: linear-gradient(135deg, #10b981, #059669); }
    .lic-hero.invalid { background: linear-gradient(135deg, #ef4444, #b91c1c); }
    .lic-hero.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .lic-hero-left { display: flex; align-items: center; gap: 18px; }
    .lic-hero-icon {
        width: 58px; height: 58px; border-radius: 14px;
        background: rgba(255,255,255,0.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 26px;
    }
    .lic-hero-title { font-size: 1.35rem; font-weight: 700; margin-bottom: 4px; }
    .lic-hero-sub   { font-size: 0.9rem; opacity: 0.92; max-width: 640px; }
    .lic-hero-btn {
        background: #fff; color: #1e293b; font-weight: 600;
        padding: 9px 16px; border-radius: 9px; text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .lic-hero-btn:hover { opacity: 0.92; color: #1e293b; }
    .lic-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
    .lic-item { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px; padding: 12px 14px; }
    .lic-item label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; display: block; margin-bottom: 5px; }
    .lic-item .val { font-size: 14px; font-weight: 600; color: #1e293b; word-break: break-all; }
    .lic-item .val.mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
    .lic-status-pill {
        display: inline-block; padding: 3px 12px; border-radius: 999px;
        font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em;
    }
    .lic-status-valid   { background: #d1fae5; color: #047857; }
    .lic-status-invalid { background: #fee2e2; color: #b91c1c; }
    .lic-status-warn    { background: #fef3c7; color: #b45309; }
    .lic-reveal { cursor: pointer; border: 1px solid #e2e8f0; background: #fff; border-radius: 6px; padding: 2px 8px; margin-left: 6px; font-size: 12px; }
    .lic-table { width: 100%; border-collapse: collapse; }
    .lic-table th, .lic-table td { text-align: left; padding: 9px 10px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .lic-table th { color: #64748b; font-weight: 600; width: 40%; }
    .lic-table td { color: #1e293b; }
    .lic-steps { margin: 0; padding-left: 20px; }
    .lic-steps li { margin-bottom: 8px; font-size: 13.5px; line-height: 1.55; color: #334155; }
    .lic-steps code { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 5px; padding: 1px 6px; font-size: 12px; }
    .lic-note { background: #fef9c3; border: 1px solid #fde047; color: #713f12; border-radius: 10px; padding: 12px 14px; font-size: 13px; margin-bottom: 20px; }
</style>

<div class="lic-page">

    @if(session('license_error'))
        <div class="lic-note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b;">
            ⛔ {{ __('License enforcement blocked the admin area:') }} {{ session('license_error') }}
        </div>
    @endif

    @if(session('license_saved'))
        <div class="lic-note">✓ {{ session('license_saved') }}</div>
    @endif

    @if(isset($refreshed) && $refreshed)
        <div class="lic-note">✓ {{ __('License re-checked just now.') }}</div>
    @endif

    @php
        $status = $isValid ? 'valid' : ($isMaster || $isLocal ? 'warning' : 'invalid');
        $statusLabel = $isValid
            ? __('Valid')
            : ($isMaster || $isLocal ? __('Not Required') : __('Invalid / Unlicensed'));
    @endphp

    <div class="lic-hero {{ $status }}">
        <div class="lic-hero-left">
            <div class="lic-hero-icon">
                @if($isValid) ✅ @elseif($isMaster || $isLocal) 🖥️ @else ❌ @endif
            </div>
            <div>
                <div class="lic-hero-title">
                    @if($isValid) {{ __('License Active') }}
                    @elseif($isMaster || $isLocal) {{ __('License Check Skipped') }}
                    @else {{ __('License Required') }} @endif
                </div>
                <div class="lic-hero-sub">{{ $message }}</div>
            </div>
        </div>
        <div>
            <span class="lic-status-pill {{ $isValid ? 'lic-status-valid' : ($isMaster || $isLocal ? 'lic-status-warn' : 'lic-status-invalid') }}">{{ $statusLabel }}</span>
        </div>
    </div>

    <div class="lic-card">
        <h3>⚙️ {{ __('Configuration') }}</h3>
        <div class="lic-grid">
            <div class="lic-item">
                <label>{{ __('Installation Domain') }}</label>
                <div class="val mono">{{ $domain }}</div>
            </div>
            <div class="lic-item">
                <label>{{ __('Script Name') }}</label>
                <div class="val">{{ $scriptName }}</div>
            </div>
            <div class="lic-item">
                <label>{{ __('Current Version') }}</label>
                <div class="val mono">{{ $currentVersion }}</div>
            </div>
        </div>
        <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn btn-primary" href="{{ route('admin.license.info', ['refresh' => 1]) }}">
                <i data-feather="refresh-cw"></i> {{ __('Re-check License') }}
            </a>
            <a class="btn btn-secondary" href="{{ route('admin.updates.index') }}">
                <i data-feather="download"></i> {{ __('Go to Updates') }}
            </a>
        </div>
    </div>

    <div class="lic-card">
        <h3>🔑 {{ __('Update License Key') }}</h3>
        <form method="POST" action="{{ route('admin.license.save') }}">
            @csrf
            <div class="lic-grid">
                <div class="lic-item" style="grid-column: span 2;">
                    <label for="license_key">{{ __('License Key') }}</label>
                    <input type="text" id="license_key" name="license_key" class="form-control mono"
                        value="{{ $licenseKey }}" placeholder="XXXX-XXXX-XXXX-XXXX-XXXX" autocomplete="off">
                </div>
            </div>
            <div style="margin-top:14px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">
                    <i data-feather="save"></i> {{ __('Save License Key') }}
                </button>
                <span class="text-muted">{{ __('Saved to the database. Leave empty to use the hardcoded default key.') }}</span>
            </div>
        </form>
    </div>

    @if($licenseData && is_array($licenseData))
        <div class="lic-card">
            <h3>📋 {{ __('Server Response') }}</h3>
            <table class="lic-table">
                <tbody>
                    @foreach($licenseData as $k => $v)
                        @if($k === 'status' || $k === 'message')
                            @continue
                        @endif
                        <tr>
                            <th>{{ ucwords(str_replace('_', ' ', $k)) }}</th>
                            <td>
                                @if(is_bool($v))
                                    {{ $v ? 'Yes' : 'No' }}
                                @elseif(is_array($v) || is_object($v))
                                    <code>{{ json_encode($v) }}</code>
                                @elseif($k === 'expires_at' && !empty($v))
                                    {{ \Illuminate\Support\Carbon::parse($v)->format('d M Y H:i') }}
                                @else
                                    {{ $v }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!$isValid && !$isMaster && !$isLocal)
        <div class="lic-card">
            <h3>🔑 {{ __('How to Activate') }}</h3>
            <ol class="lic-steps">
                <li>{{ __('Open your license server (WordPress) and go to') }} <strong>License Manager → Licenses</strong> {{ __('and create a license for this product with the key below, or generate a new key.') }}</li>
                <li>{{ __('Copy the key into your') }} <code>.env</code> {{ __('file:') }} <code>LICENSE_KEY=XXXX-XXXX-XXXX-XXXX-XXXX</code></li>
                <li>{{ __('Make sure') }} <code>UPDATE_API_URL</code> {{ __('points to your license server (e.g.') }} <code>https://softmit.xyz</code>{{ __('). You can also set it in Admin → General Settings → update_api_url.') }}</li>
                <li>{{ __('Register the current domain') }} <strong>{{ $domain }}</strong> {{ __('on that license (the plugin auto-registers it on first check if enabled).') }}</li>
                <li>{{ __('Click') }} <strong>{{ __('Re-check License') }}</strong> {{ __('above.') }}</li>
            </ol>
            <p class="text-muted mt-2" style="margin-top:12px;">
                {{ __('Current key in .env:') }} <code>{{ $licenseKey ? $maskedKey : '(not set)' }}</code>
            </p>
        </div>
    @endif

</div>

<script>
    (function () {
        var btn = document.getElementById('lic-reveal-btn');
        var disp = document.getElementById('lic-key-display');
        if (!btn || !disp) return;
        var shown = false;
        btn.addEventListener('click', function () {
            shown = !shown;
            disp.textContent = shown ? btn.getAttribute('data-full') : disp.getAttribute('data-masked') || disp.textContent;
            btn.textContent = shown ? '🙈' : '👁';
        });
    })();
</script>

@endsection
