@extends('backEnd.layouts.master')
@section('title', 'Ads Analytics Settings | API Configuration')

@section('content')
<div class="container-fluid py-4">

  {{-- Page Header --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="page-header-modern d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <h4 class="mb-1">
            <i class="fe-sliders me-2" style="color: #1877F2;"></i>Ads Analytics API Settings
          </h4>
          <p class="text-muted mb-0">
            <i class="fe-info me-1"></i>Configure your advertising platform API connections
          </p>
        </div>
        <a href="{{ route('admin.ads_analytics.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">
          <i class="fe-arrow-left me-1"></i>{{ __('Dashboard') }}</a>
      </div>
    </div>
  </div>

  <form id="settingsForm" action="{{ route('admin.ads_analytics.save_settings') }}" method="POST">
    @csrf

    @php
      $fbActive = optional($settings['facebook'] ?? null)->is_active;
      $googleActive = optional($settings['google'] ?? null)->is_active;
      $tiktokActive = optional($settings['tiktok'] ?? null)->is_active;
      $activeCount = ($fbActive ? 1 : 0) + ($googleActive ? 1 : 0) + ($tiktokActive ? 1 : 0);
    @endphp

    {{-- Facebook Ads Card --}}
    <div class="modern-card mb-4">
      <div class="modern-card-header fb-gradient d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#facebookBody" aria-expanded="true">
        <div class="d-flex align-items-center gap-3">
          <div class="platform-icon">
            <i class="fe-facebook"></i>
          </div>
          <div>
            <h5 class="mb-0 text-white fw-semibold"> {{ __('Facebook Ads Manager') }} </h5>
            <small class="text-white-50"> {{ __('Meta Ads API Configuration') }} </small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-3">
          <div class="form-check form-switch modern-switch" onclick="event.stopPropagation();">
            <input class="form-check-input" type="checkbox" role="switch" id="facebook_active" name="facebook_is_active" value="1" {{ $fbActive ? 'checked' : '' }} onchange="updateProgress()">
            <label class="form-check-label text-white" for="facebook_active">{{ __('Active') }}</label>
          </div>
          <i class="fe-chevron-down collapse-chevron" id="fbChevron"></i>
        </div>
      </div>
      <div class="modern-card-body-wrap collapse show" id="facebookBody">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="modern-label" for="facebook_ad_account_id">
                <i class="fe-hash me-1"></i>Ad Account ID
                <span class="field-tooltip" title="Your Facebook Ad Account ID starting with 'act_'">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-briefcase"></i></span>
                <input type="text" id="facebook_ad_account_id" name="facebook_ad_account_id" class="form-control modern-input" value="{{ optional($settings['facebook'] ?? null)->ad_account_id ?? '' }}" placeholder="act_1234567890">
                <button type="button" class="copy-btn" data-copy="{{ optional($settings['facebook'] ?? null)->ad_account_id ?? '' }}" title="Copy to clipboard"><i class="fe-copy"></i></button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="modern-label" for="facebook_access_token">
                <i class="fe-key me-1"></i> {{ __('Access Token') }} <span class="field-tooltip" title="Long-lived System User Token or User Access Token from Meta">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-lock"></i></span>
                <input type="password" id="facebook_access_token" name="facebook_access_token" class="form-control modern-input" value="{{ optional($settings['facebook'] ?? null)->access_token ?? '' }}" placeholder="Long-lived token" autocomplete="off">
                <button type="button" class="password-toggle" onclick="togglePassword('facebook_access_token', this)" title="Show/Hide password">
                  <i class="fe-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="modern-label" for="facebook_app_id">
                <i class="fe-box me-1"></i>App ID
                <span class="field-tooltip" title="Your Facebook App ID from the Meta Developer Portal">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-cpu"></i></span>
                <input type="text" id="facebook_app_id" name="facebook_app_id" class="form-control modern-input" value="{{ optional($settings['facebook'] ?? null)->app_id ?? '' }}" placeholder="Enter App ID">
                <button type="button" class="copy-btn" data-copy="{{ optional($settings['facebook'] ?? null)->app_id ?? '' }}" title="Copy to clipboard"><i class="fe-copy"></i></button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="modern-label" for="facebook_app_secret">
                <i class="fe-shield me-1"></i> {{ __('App Secret') }} <span class="field-tooltip" title="Your Facebook App Secret from the Meta Developer Portal">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-eye-off"></i></span>
                <input type="password" id="facebook_app_secret" name="facebook_app_secret" class="form-control modern-input" value="{{ optional($settings['facebook'] ?? null)->app_secret ?? '' }}" placeholder="Enter App Secret" autocomplete="off">
                <button type="button" class="password-toggle" onclick="togglePassword('facebook_app_secret', this)" title="Show/Hide password">
                  <i class="fe-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="help-block">
            <i class="fe-info text-primary me-1"></i>
            <strong> {{ __('Where to find:') }} </strong> Facebook Developers → App → Marketing API → System User Token or User Access Token
          </div>
        </div>
      </div>
    </div>

    {{-- Google Ads Card --}}
    <div class="modern-card mb-4">
      <div class="modern-card-header google-gradient d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#googleBody" aria-expanded="true">
        <div class="d-flex align-items-center gap-3">
          <div class="platform-icon">
            <i class="fe-globe"></i>
          </div>
          <div>
            <h5 class="mb-0 text-white fw-semibold"> {{ __('Google Ads') }} </h5>
            <small class="text-white-50"> {{ __('Google Ads API Configuration') }} </small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-3">
          <div class="form-check form-switch modern-switch" onclick="event.stopPropagation();">
            <input class="form-check-input" type="checkbox" role="switch" id="google_active" name="google_is_active" value="1" {{ $googleActive ? 'checked' : '' }} onchange="updateProgress()">
            <label class="form-check-label text-white" for="google_active">{{ __('Active') }}</label>
          </div>
          <i class="fe-chevron-down collapse-chevron" id="googleChevron"></i>
        </div>
      </div>
      <div class="modern-card-body-wrap collapse show" id="googleBody">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="modern-label" for="google_ad_account_id">
                <i class="fe-hash me-1"></i>Customer ID
                <span class="field-tooltip" title="Your Google Ads Customer ID (10-digit number)">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-briefcase"></i></span>
                <input type="text" id="google_ad_account_id" name="google_ad_account_id" class="form-control modern-input" value="{{ optional($settings['google'] ?? null)->ad_account_id ?? '' }}" placeholder="1234567890">
                <button type="button" class="copy-btn" data-copy="{{ optional($settings['google'] ?? null)->ad_account_id ?? '' }}" title="Copy to clipboard"><i class="fe-copy"></i></button>
              </div>
              <small class="text-muted"> {{ __('Format: 123-456-7890') }} </small>
            </div>
            <div class="col-md-6">
              <label class="modern-label" for="google_client_id">
                <i class="fe-user me-1"></i> {{ __('Client ID') }} <span class="field-tooltip" title="OAuth 2.0 Client ID from Google Cloud Console">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-file-text"></i></span>
                <input type="text" id="google_client_id" name="google_client_id" class="form-control modern-input" value="{{ optional($settings['google'] ?? null)->client_id ?? '' }}" placeholder="Enter Client ID">
                <button type="button" class="copy-btn" data-copy="{{ optional($settings['google'] ?? null)->client_id ?? '' }}" title="Copy to clipboard"><i class="fe-copy"></i></button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="modern-label" for="google_client_secret">
                <i class="fe-shield me-1"></i> {{ __('Client Secret') }} <span class="field-tooltip" title="OAuth 2.0 Client Secret from Google Cloud Console">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-lock"></i></span>
                <input type="password" id="google_client_secret" name="google_client_secret" class="form-control modern-input" value="{{ optional($settings['google'] ?? null)->client_secret ?? '' }}" placeholder="Enter Client Secret" autocomplete="off">
                <button type="button" class="password-toggle" onclick="togglePassword('google_client_secret', this)" title="Show/Hide password">
                  <i class="fe-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="modern-label" for="google_refresh_token">
                <i class="fe-refresh-cw me-1"></i>Refresh Token
                <span class="field-tooltip" title="OAuth 2.0 Refresh Token for offline access">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-rotate-cw"></i></span>
                <input type="text" id="google_refresh_token" name="google_refresh_token" class="form-control modern-input" value="{{ optional($settings['google'] ?? null)->refresh_token ?? '' }}" placeholder="1//...">
                <button type="button" class="copy-btn" data-copy="{{ optional($settings['google'] ?? null)->refresh_token ?? '' }}" title="Copy to clipboard"><i class="fe-copy"></i></button>
              </div>
            </div>
          </div>
          <div class="help-block">
            <i class="fe-info text-danger me-1"></i>
            <strong> {{ __('Where to find:') }} </strong> Google Cloud Console → OAuth 2.0 credentials. Application verification required for Google Ads API.
          </div>
        </div>
      </div>
    </div>

    {{-- TikTok Ads Card --}}
    <div class="modern-card mb-4">
      <div class="modern-card-header tiktok-gradient d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#tiktokBody" aria-expanded="true">
        <div class="d-flex align-items-center gap-3">
          <div class="platform-icon">
            <i class="fe-video"></i>
          </div>
          <div>
            <h5 class="mb-0 text-white fw-semibold"> {{ __('TikTok Ads') }} </h5>
            <small class="text-white-50"> {{ __('TikTok for Business API Configuration') }} </small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-3">
          <div class="form-check form-switch modern-switch" onclick="event.stopPropagation();">
            <input class="form-check-input" type="checkbox" role="switch" id="tiktok_active" name="tiktok_is_active" value="1" {{ $tiktokActive ? 'checked' : '' }} onchange="updateProgress()">
            <label class="form-check-label text-white" for="tiktok_active">{{ __('Active') }}</label>
          </div>
          <i class="fe-chevron-down collapse-chevron" id="tiktokChevron"></i>
        </div>
      </div>
      <div class="modern-card-body-wrap collapse show" id="tiktokBody">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="modern-label" for="tiktok_advertiser_id">
                <i class="fe-hash me-1"></i>Advertiser ID
                <span class="field-tooltip" title="Your TikTok Advertiser ID from TikTok for Business">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-briefcase"></i></span>
                <input type="text" id="tiktok_advertiser_id" name="tiktok_advertiser_id" class="form-control modern-input" value="{{ optional($settings['tiktok'] ?? null)->ad_account_id ?? '' }}" placeholder="1234567890123456789">
                <button type="button" class="copy-btn" data-copy="{{ optional($settings['tiktok'] ?? null)->ad_account_id ?? '' }}" title="Copy to clipboard"><i class="fe-copy"></i></button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="modern-label" for="tiktok_access_token">
                <i class="fe-key me-1"></i> {{ __('Access Token') }} <span class="field-tooltip" title="API Access Token from TikTok for Business">?</span>
              </label>
              <div class="modern-input-group input-group">
                <span class="input-group-text"><i class="fe-lock"></i></span>
                <input type="password" id="tiktok_access_token" name="tiktok_access_token" class="form-control modern-input" value="{{ optional($settings['tiktok'] ?? null)->access_token ?? '' }}" placeholder="Enter Access Token" autocomplete="off">
                <button type="button" class="password-toggle" onclick="togglePassword('tiktok_access_token', this)" title="Show/Hide password">
                  <i class="fe-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="help-block">
            <i class="fe-info text-dark me-1"></i>
            <strong> {{ __('Where to find:') }} </strong> TikTok for Business → Tools → API → Create Access Token
          </div>
        </div>
      </div>
    </div>

    {{-- Sticky Save Bar --}}
    <div class="sticky-save-bar" id="stickySaveBar">
      <div class="d-flex justify-content-between align-items-center px-3">
        <div class="d-flex align-items-center gap-2">
          <span class="status-badge {{ $fbActive ? 'active' : 'inactive' }}">
            <i class="fe-facebook"></i> FB
          </span>
          <span class="status-badge {{ $googleActive ? 'active' : 'inactive' }}">
            <i class="fe-globe"></i> Google
          </span>
          <span class="status-badge {{ $tiktokActive ? 'active' : 'inactive' }}">
            <i class="fe-video"></i> TikTok
          </span>
          <span class="setup-progress ms-3">
            <span id="progressText">{{ $activeCount }}/3 active</span>
            <span class="progress-track">
              <span class="progress-fill" id="progressFill" style="width: {{ ($activeCount/3)*100 }}%"></span>
            </span>
          </span>
        </div>
        <button type="submit" class="btn btn-modern-save" id="saveBtn">
          <span class="spinner"></span>
          <span class="btn-text"><i class="fe-save"></i> {{ __('Save Settings') }} </span>
        </button>
      </div>
    </div>

  </form>
</div>
@endsection

@section('script')
<script>
// --- Password Toggle ---
function togglePassword(fieldId, btn) {
  const field = document.getElementById(fieldId);
  if (!field) return;
  const isPassword = field.type === 'password';
  field.type = isPassword ? 'text' : 'password';
  btn.innerHTML = isPassword ? '<i class="fe-eye-off"></i>' : '<i class="fe-eye"></i>';
  btn.title = isPassword ? 'Hide' : 'Show';
}

// --- Copy to Clipboard ---
document.querySelectorAll('.copy-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const text = this.getAttribute('data-copy');
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
      this.classList.add('copied');
      this.innerHTML = '<i class="fe-check"></i>';
      setTimeout(() => {
        this.classList.remove('copied');
        this.innerHTML = '<i class="fe-copy"></i>';
      }, 2000);
    }).catch(() => {});
  });
});

// --- Collapse Chevron Rotation ---
document.querySelectorAll('.modern-card-header[data-bs-toggle]').forEach(header => {
  const targetId = header.getAttribute('data-bs-target');
  const target = document.querySelector(targetId);
  const chevron = header.querySelector('.collapse-chevron');
  if (!target || !chevron) return;

  target.addEventListener('show.bs.collapse', () => {
    chevron.classList.remove('collapsed');
  });
  target.addEventListener('hide.bs.collapse', () => {
    chevron.classList.add('collapsed');
  });
});

// --- Setup Progress ---
function updateProgress() {
  const fb = document.getElementById('facebook_active').checked;
  const google = document.getElementById('google_active').checked;
  const tiktok = document.getElementById('tiktok_active').checked;
  const count = (fb ? 1 : 0) + (google ? 1 : 0) + (tiktok ? 1 : 0);
  const pct = (count / 3) * 100;

  document.getElementById('progressText').textContent = count + '/3 active';
  document.getElementById('progressFill').style.width = pct + '%';

  // Update status badges
  document.querySelectorAll('.status-badge').forEach((badge, i) => {
    const isActive = [fb, google, tiktok][i];
    badge.className = 'status-badge ' + (isActive ? 'active' : 'inactive');
  });
}

// --- Save Button Loading State ---
document.getElementById('settingsForm').addEventListener('submit', function() {
  const btn = document.getElementById('saveBtn');
  btn.classList.add('loading');
  btn.disabled = true;
});

// --- Keyboard shortcut: Ctrl+S to save ---
document.addEventListener('keydown', function(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    document.getElementById('settingsForm').requestSubmit();
  }
});
</script>
@endsection
