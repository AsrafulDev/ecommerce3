@extends('backEnd.layouts.master')
@section('title', 'Live Ads Result | Ads Analytics')

@section('content')
<div class="container-fluid py-4">

  {{-- Hero Header --}}
  <div class="dashboard-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h4 class="mb-1"><i class="fe-bar-chart-2 me-2"></i>Live Ads Dashboard</h4>
        <p class="text-light-emphasis mb-0">
          <i class="fe-radio me-1"></i>Real-time overview of Facebook, Google &amp; TikTok Ads performance
        </p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="live-pulse">
          <span class="pulse-dot"></span>
          <span id="liveStatus">Live</span>
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
      <i class="fe-facebook me-1"></i>Facebook Ads
    </a>
    <a href="{{ route('admin.ads_analytics.google') }}" class="btn btn-outline-danger">
      <i class="fe-globe me-1"></i>Google Ads
    </a>
    <a href="{{ route('admin.ads_analytics.tiktok') }}" class="btn btn-outline-dark">
      <i class="fe-video me-1"></i>TikTok Ads
    </a>
  </div>

  {{-- Summary Stats Row --}}
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="dashboard-stat-card d-flex align-items-center gap-3">
        <div class="stat-icon messages">
          <i class="fe-message-circle"></i>
        </div>
        <div>
          <div class="stat-label">{{ __('Messages') }}</div>
          <div class="d-flex align-items-center gap-3 mt-1">
            <div>
              <span class="stat-value" id="totalMessages">{{ number_format($totalMessages ?? 0) }}</span>
              <span class="stat-sub"> total</span>
            </div>
            <div>
              <span class="stat-value" id="todayMessages">{{ number_format($todayMessages ?? 0) }}</span>
              <span class="stat-sub"> today</span>
            </div>
            <div>
              <span class="stat-value text-warning" id="unreadMessages">{{ number_format($unreadMessages ?? 0) }}</span>
              <span class="stat-sub"> unread</span>
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
          <div class="stat-label">Today's Ad Spend</div>
          <div class="stat-value mt-1">$ <span id="totalAdSpend">{{ number_format($totalAdSpendToday ?? 0, 2) }}</span></div>
          <span class="stat-sub">Facebook + Google + TikTok</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="dashboard-stat-card d-flex align-items-center gap-3">
        <div class="stat-icon expenses">
          <i class="fe-trending-down"></i>
        </div>
        <div>
          <div class="stat-label">Expenses</div>
          <div class="stat-value mt-1">৳ <span id="todayExpenses">{{ number_format($todayExpenses ?? 0, 2) }}</span></div>
          <span class="stat-sub">Today · Monthly: ৳ {{ number_format($monthlyExpenses ?? 0, 2) }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- Platform Result Cards --}}
  <div class="row g-4">
    {{-- Facebook --}}
    <div class="col-12">
      <div class="platform-card">
        <div class="platform-card-header fb-gradient">
          <div class="mini-icon"><i class="fe-facebook"></i></div>
          <h6>Facebook Ads Manager</h6>
          <span class="ms-auto badge rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:0.7rem;">
            @if(($facebook['success'] ?? false)) Active @else Not Configured @endif
          </span>
        </div>
        <div class="platform-card-body">
          @if(($facebook['success'] ?? false))
            <div class="metrics-grid">
              <div class="metric-cell">
                <div class="m-value text-primary">${{ number_format($facebook['spend'] ?? 0, 2) }}</div>
                <div class="m-label">Spend</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($facebook['clicks'] ?? 0) }}</div>
                <div class="m-label">Clicks</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($facebook['impressions'] ?? 0) }}</div>
                <div class="m-label">Impressions</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($facebook['reach'] ?? 0) }}</div>
                <div class="m-label">Reach</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($facebook['conversions'] ?? 0) }}</div>
                <div class="m-label">Conversions</div>
              </div>
            </div>
            <a href="{{ route('admin.ads_analytics.facebook') }}" class="btn btn-sm btn-outline-primary mt-3 rounded-pill">
              <i class="fe-arrow-right me-1"></i>View Full Report
            </a>
          @else
            <div class="text-center py-3">
              <i class="fe-facebook" style="font-size:2rem;color:#dee2e6;"></i>
              <p class="text-muted mt-2 mb-2">{{ $facebook['message'] ?? 'API not configured yet' }}</p>
              <a href="{{ route('admin.ads_analytics.settings') }}" class="btn btn-sm btn-primary rounded-pill px-3">
                <i class="fe-settings me-1"></i>Configure API
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
          <h6>Google Ads</h6>
          <span class="ms-auto badge rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:0.7rem;">
            @if(($google['success'] ?? false)) Active @else Not Configured @endif
          </span>
        </div>
        <div class="platform-card-body">
          @if(($google['success'] ?? false))
            <div class="metrics-grid">
              <div class="metric-cell">
                <div class="m-value text-danger">${{ number_format($google['spend'] ?? 0, 2) }}</div>
                <div class="m-label">Spend</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($google['clicks'] ?? 0) }}</div>
                <div class="m-label">Clicks</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($google['impressions'] ?? 0) }}</div>
                <div class="m-label">Impressions</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($google['conversions'] ?? 0) }}</div>
                <div class="m-label">Conversions</div>
              </div>
              {{-- Google doesn't have 'reach' --}}
            </div>
            <a href="{{ route('admin.ads_analytics.google') }}" class="btn btn-sm btn-outline-danger mt-3 rounded-pill">
              <i class="fe-arrow-right me-1"></i>View Full Report
            </a>
          @else
            <div class="text-center py-3">
              <i class="fe-globe" style="font-size:2rem;color:#dee2e6;"></i>
              <p class="text-muted mt-2 mb-2">{{ $google['message'] ?? 'API not configured yet' }}</p>
              <a href="{{ route('admin.ads_analytics.settings') }}" class="btn btn-sm btn-danger rounded-pill px-3">
                <i class="fe-settings me-1"></i>Configure API
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
          <h6>TikTok Ads</h6>
          <span class="ms-auto badge rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:0.7rem;">
            @if(($tiktok['success'] ?? false)) Active @else Not Configured @endif
          </span>
        </div>
        <div class="platform-card-body">
          @if(($tiktok['success'] ?? false))
            <div class="metrics-grid">
              <div class="metric-cell">
                <div class="m-value">${{ number_format($tiktok['spend'] ?? 0, 2) }}</div>
                <div class="m-label">Spend</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($tiktok['clicks'] ?? 0) }}</div>
                <div class="m-label">Clicks</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($tiktok['impressions'] ?? 0) }}</div>
                <div class="m-label">Impressions</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($tiktok['reach'] ?? 0) }}</div>
                <div class="m-label">Reach</div>
              </div>
              <div class="metric-cell">
                <div class="m-value">{{ number_format($tiktok['conversions'] ?? 0) }}</div>
                <div class="m-label">Conversions</div>
              </div>
            </div>
            <a href="{{ route('admin.ads_analytics.tiktok') }}" class="btn btn-sm btn-outline-dark mt-3 rounded-pill">
              <i class="fe-arrow-right me-1"></i>View Full Report
            </a>
          @else
            <div class="text-center py-3">
              <i class="fe-video" style="font-size:2rem;color:#dee2e6;"></i>
              <p class="text-muted mt-2 mb-2">{{ $tiktok['message'] ?? 'API not configured yet' }}</p>
              <a href="{{ route('admin.ads_analytics.settings') }}" class="btn btn-sm btn-dark rounded-pill px-3">
                <i class="fe-settings me-1"></i>Configure API
              </a>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Footer Info --}}
  <div class="dashboard-footer d-flex flex-wrap justify-content-between align-items-center mt-4">
    <span><i class="fe-info me-1"></i>Data cached every 5 minutes. Click <strong>{{ __('Refresh') }}</strong> for fresh data.</span>
    <span class="small">Configure API credentials in Settings</span>
  </div>
</div>
@endsection

@section('script')
<script>
(function() {
  const liveStatus = document.getElementById('liveStatus');

  function fetchLiveData() {
    fetch('{{ route("admin.ads_analytics.live_data") }}', {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
      document.getElementById('totalMessages').textContent = parseInt(data.totalMessages || 0).toLocaleString();
      document.getElementById('todayMessages').textContent = parseInt(data.todayMessages || 0).toLocaleString();
      document.getElementById('unreadMessages').textContent = parseInt(data.unreadMessages || 0).toLocaleString();
      document.getElementById('totalAdSpend').textContent = parseFloat(data.totalAdSpendToday || 0).toFixed(2);
      document.getElementById('todayExpenses').textContent = parseFloat(data.todayExpenses || 0).toFixed(2);
      if (liveStatus) liveStatus.textContent = 'Live • Updated';
      setTimeout(() => { if (liveStatus) liveStatus.textContent = 'Live'; }, 3000);
    })
    .catch(() => {});
  }

  // Auto-refresh every 60 seconds
  setInterval(fetchLiveData, 60000);
})();
</script>
@endsection
