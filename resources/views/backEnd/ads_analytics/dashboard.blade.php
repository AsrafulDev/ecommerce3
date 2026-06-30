@extends('backEnd.layouts.master')
@section('title', '{{ __('{{ __('Live') }} Ads Result') }} | Ads Analytics')

@section('content')
<div class="container-fluid py-4">

  {{-- Hero Header --}}
  <div class="dashboard-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h4 class="mb-1"><i class="fe-bar-chart-2 me-2"></i>{{ __('{{ __('Live') }} Ads Dashboard') }}</h4>
        <p class="text-light-emphasis mb-0">
          <i class="fe-radio me-1"></i>Real-time overview of Facebook, Google &amp; {{ __('TikTok Ads') }} performance
        </p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="live-pulse">
          <span class="pulse-dot"></span>
          <span id="live{{ __('Status') }}">{{ __('Live') }}</span>
        </span>
        <a href="{{ route('admin.ads_analytics.dashboard', ['refresh' => 1]) }}" class="btn btn-light btn-sm rounded-pill px-3">
          <i class="fe-refresh-cw me-1"></i>{{ __('Refresh') }}</a>
        <a href="{{ route('admin.ads_analytics.settings') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
          <i class="fe-settings me-1"></i>{{ __('Settings') }}</a>
      </div>
    </div>
  </div>

  {{-- Quick Nav --}}
  <div class="quick-nav mb-4">
    <a href="{{ route('admin.ads_analytics.facebook') }}" class="btn btn-outline-primary">
      <i class="{{ __('fe-facebook') }} me-1"></i>{{ __('Facebook Ads') }}
    </a>
    <a href="{{ route('admin.ads_analytics.google') }}" class="btn btn-outline-danger">
      <i class="fe-globe me-1"></i>{{ __('Google Ads') }}
    </a>
    <a href="{{ route('admin.ads_analytics.tiktok') }}" class="btn btn-outline-dark">
      <i class="fe-video me-1"></i>{{ __('TikTok Ads') }}
    </a>
  </div>

  {{-- {{ __('Summary') }} Stats Row --}}
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="dashboard-stat-card d-flex align-items-center gap-3">
        <div class="stat-icon {{ __('message') }}s">
          <i class="fe-{{ __('message') }}-circle"></i>
        </div>
        <div>
          <div class="stat-label">{{ __('{{ __('Message') }}s') }}</div>
          <div class="d-flex align-items-center gap-3 mt-1">
            <div>
              <span class="stat-value" id="{{ __('total') }}{{ __('Message') }}s">{{ number_format($total{{ __('Message') }}s ?? 0) }}</span>
              <span class="stat-sub"> {{ __('total') }}</span>
            </div>
            <div>
              <span class="stat-value" id="{{ __('today') }}{{ __('Message') }}s">{{ number_format($today{{ __('Message') }}s ?? 0) }}</span>
              <span class="stat-sub"> {{ __('today') }}</span>
            </div>
            <div>
              <span class="stat-value text-warning" id="{{ __('unread') }}{{ __('Message') }}s">{{ number_format($unread{{ __('Message') }}s ?? 0) }}</span>
              <span class="stat-sub"> {{ __('unread') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="dashboard-stat-card d-flex align-items-center gap-3">
        <div class="stat-icon spend">
          <i class="fe-dollar-sign"></i>
        </div>
        <div>
          <div class="stat-label">{{ __("{{ __('Today') }}'s Ad {{ __('Spend') }}") }}</div>
          <div class="stat-value mt-1">$ <span id="{{ __('total') }}Ad{{ __('Spend') }}">{{ number_format($totalAd{{ __('Spend') }}{{ __('Today') }} ?? 0, 2) }}</span></div>
          <span class="stat-sub">{{ __('Facebook + Google + TikTok') }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="dashboard-stat-card d-flex align-items-center gap-3">
        <div class="stat-icon expenses">
          <i class="fe-trending-down"></i>
        </div>
        <div>
          <div class="stat-label">{{ __('{{ __('Expense') }}s') }}</div>
          <div class="stat-value mt-1">৳ <span id="{{ __('today') }}{{ __('{{ __('Expense') }}s') }}">{{ number_format($today{{ __('{{ __('Expense') }}s') }} ?? 0, 2) }}</span></div>
          <span class="stat-sub">{{ __('Today') }} · {{ __('Monthly') }}: ৳ {{ number_format($monthly{{ __('{{ __('Expense') }}s') }} ?? 0, 2) }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- {{ __('Platform') }} Result Cards --}}
  <div class="row g-4">
    {{-- Facebook --}}
    <div class="col-12">
      <div class="platform-card">
        <div class="platform-card-header fb-gradient">
          <div class="mini-icon"><i class="{{ __('fe-facebook') }}"></i></div>
          <h6>{{ __('{{ __('Facebook Ads') }} {{ __('Manage') }}r') }}</h6>
          <span class="ms-auto badge rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:0.7rem;">
            @if(($facebook['success'] ?? false)) Active @else Not Configured @endif
          </span>
        </div>
        <div class="platform-card-body">
          @if(($facebook['success'] ?? false))
            <div class="metrics-grid">
              <div class="metric-cell">
                <div class="m-value text-primary">{{ number_format($facebook['spend'] ?? 0, 2) }}</div>
                <div class="m-label">{{ __('Spend') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($facebook['clicks'] ?? 0) }}</div>
                <div class="m-label">{{ __('Clicks') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($facebook['impressions'] ?? 0) }}</div>
                <div class="m-label">{{ __('Impressions') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($facebook['reach'] ?? 0) }}</div>
                <div class="m-label">{{ __('Reach') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($facebook['conversions'] ?? 0) }}</div>
                <div class="m-label">{{ __('Conversions') }}</div>
              </div>
            </div>
            <a href="{{ route('admin.ads_analytics.facebook') }}" class="btn btn-sm btn-outline-primary mt-3 rounded-pill">
              <i class="fe-arrow-right me-1"></i>View Full Report
            </a>
          @else
            <div class="text-center py-3">
              <i class="{{ __('fe-facebook') }}" style="font-size:2rem;color:#dee2e6;"></i>
              <p class="text-muted mt-2 mb-2">{{ $facebook['{{ __('message') }}'] ?? 'API not configured yet' }}</p>
              <a href="{{ route('admin.ads_analytics.settings') }}" class="btn btn-sm btn-primary rounded-pill px-3">
                <i class="fe-settings me-1"></i>{{ __('Configure API') }}
              </a>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Google --}}
    <div class="col-12">
      <div class="platform-card">
        <div class="platform-card-header google-gradient">
          <div class="mini-icon"><i class="fe-globe"></i></div>
          <h6>{{ __('Google Ads') }}</h6>
          <span class="ms-auto badge rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:0.7rem;">
            @if(($google['success'] ?? false)) Active @else Not Configured @endif
          </span>
        </div>
        <div class="platform-card-body">
          @if(($google['success'] ?? false))
            <div class="metrics-grid">
              <div class="metric-cell">
                <div class="m-value text-danger">{{ number_format($google['spend'] ?? 0, 2) }}</div>
                <div class="m-label">{{ __('Spend') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($google['clicks'] ?? 0) }}</div>
                <div class="m-label">{{ __('Clicks') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($google['impressions'] ?? 0) }}</div>
                <div class="m-label">{{ __('Impressions') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($google['conversions'] ?? 0) }}</div>
                <div class="m-label">{{ __('Conversions') }}</div>
              </div>
              {{-- Google doesn't have 'reach' --}}
            </div>
            <a href="{{ route('admin.ads_analytics.google') }}" class="btn btn-sm btn-outline-danger mt-3 rounded-pill">
              <i class="fe-arrow-right me-1"></i>View Full Report
            </a>
          @else
            <div class="text-center py-3">
              <i class="fe-globe" style="font-size:2rem;color:#dee2e6;"></i>
              <p class="text-muted mt-2 mb-2">{{ $google['{{ __('message') }}'] ?? 'API not configured yet' }}</p>
              <a href="{{ route('admin.ads_analytics.settings') }}" class="btn btn-sm btn-danger rounded-pill px-3">
                <i class="fe-settings me-1"></i>{{ __('Configure API') }}
              </a>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- TikTok --}}
    <div class="col-12">
      <div class="platform-card">
        <div class="platform-card-header tiktok-gradient">
          <div class="mini-icon"><i class="fe-video"></i></div>
          <h6>{{ __('TikTok Ads') }}</h6>
          <span class="ms-auto badge rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:0.7rem;">
            @if(($tiktok['success'] ?? false)) Active @else Not Configured @endif
          </span>
        </div>
        <div class="platform-card-body">
          @if(($tiktok['success'] ?? false))
            <div class="metrics-grid">
              <div class="metric-cell">
                <div class="m-value">{{ number_format($tiktok['spend'] ?? 0, 2) }}</div>
                <div class="m-label">{{ __('Spend') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($tiktok['clicks'] ?? 0) }}</div>
                <div class="m-label">{{ __('Clicks') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($tiktok['impressions'] ?? 0) }}</div>
                <div class="m-label">{{ __('Impressions') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($tiktok['reach'] ?? 0) }}</div>
                <div class="m-label">{{ __('Reach') }}</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($tiktok['conversions'] ?? 0) }}</div>
                <div class="m-label">{{ __('Conversions') }}</div>
              </div>
            </div>
            <a href="{{ route('admin.ads_analytics.tiktok') }}" class="btn btn-sm btn-outline-dark mt-3 rounded-pill">
              <i class="fe-arrow-right me-1"></i>View Full Report
            </a>
          @else
            <div class="text-center py-3">
              <i class="fe-video" style="font-size:2rem;color:#dee2e6;"></i>
              <p class="text-muted mt-2 mb-2">{{ $tiktok['{{ __('message') }}'] ?? 'API not configured yet' }}</p>
              <a href="{{ route('admin.ads_analytics.settings') }}" class="btn btn-sm btn-dark rounded-pill px-3">
                <i class="fe-settings me-1"></i>{{ __('Configure API') }}
              </a>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Footer Info --}}
  <div class="dashboard-footer d-flex flex-wrap justify-content-between align-items-center mt-4">
    <span><i class="fe-info me-1"></i>{{ __('Data cached every 5 minutes. Click') }} <strong>{{ __('Refresh') }}</strong> {{ __('for fresh data.') }}</span>
    <span class="small">{{ __('{{ __('Configure API') }} credentials in Settings') }}</span>
  </div>
</div>
@endsection

@section('script')
<script>
(function() {
  const live{{ __('Status') }} = document.getElementById('live{{ __('Status') }}');

  function fetch{{ __('Live') }}Data() {
    fetch('{{ route("admin.ads_analytics.live_data") }}', {
      headers: { 'X-{{ __('Requested') }}-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
      document.getElementById('{{ __('total') }}{{ __('Message') }}s').textContent = parseInt(data.{{ __('total') }}{{ __('Message') }}s || 0).toLocale{{ __('String') }}();
      document.getElementById('{{ __('today') }}{{ __('Message') }}s').textContent = parseInt(data.{{ __('today') }}{{ __('Message') }}s || 0).toLocale{{ __('String') }}();
      document.getElementById('{{ __('unread') }}{{ __('Message') }}s').textContent = parseInt(data.{{ __('unread') }}{{ __('Message') }}s || 0).toLocale{{ __('String') }}();
      document.getElementById('{{ __('total') }}Ad{{ __('Spend') }}').textContent = parseFloat(data.{{ __('total') }}Ad{{ __('Spend') }}{{ __('Today') }} || 0).to{{ __('Fixed') }}(2);
      document.getElementById('{{ __('today') }}{{ __('{{ __('Expense') }}s') }}').textContent = parseFloat(data.{{ __('today') }}{{ __('{{ __('Expense') }}s') }} || 0).to{{ __('Fixed') }}(2);
      if (live{{ __('Status') }}) live{{ __('Status') }}.textContent = '{{ __('Live') }} • Updated';
      setTimeout(() => { if (live{{ __('Status') }}) live{{ __('Status') }}.textContent = '{{ __('Live') }}'; }, 3000);
    })
    .catch(() => {});
  }

  // Auto-refresh every 60 seconds
  setInterval(fetch{{ __('Live') }}Data, 60000);
})();
</script>
@endsection
