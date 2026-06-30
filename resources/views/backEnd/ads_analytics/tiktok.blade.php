@extends('backEnd.layouts.master')
@section('title', '{{ __('{{ __('TikTok Ads') }} Result') }}')

@section('css')
<style>
.ads-card { background:#fff; border-radius:12px; padding:25px; box-shadow:0 2px 12px rgba(0,0,0,.06); border-left:6px solid #000; }
.metric-row { display:flex; flex-wrap:wrap; gap:20px; margin-top:20px; }
.metric-item { flex:1; min-width:120px; padding:20px; background:#f5f5f5; border-radius:10px; text-align:center; }
.metric-value { font-size:24px; font-weight:700; color:#000; }
.metric-label { font-size:12px; color:#64748b; text-transform:uppercase; margin-top:5px; }
</style>
@endsection

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1"><i class="fe-video me-2"></i> {{ __('{{ __('TikTok Ads') }} Result') }}</h4>
      <small class="text-muted">{{ __('{{ __('Live') }} performance data from {{ __('TikTok Ads') }}') }}</small>
    </div>
    <div>
      <span class="badge bg-success me-2"><i class="fe-radio"></i> {{ __('Live') }}</span>
      <a href="{{ route('admin.ads_analytics.tiktok', ['refresh' => 1]) }}" class="btn btn-sm btn-dark">
        <i class="fe-refresh-cw"></i>{{ __('Refresh') }}</a>
      <a href="{{ route('admin.ads_analytics.dashboard') }}" class="btn btn-sm btn-outline-secondary">{{ __('Overview') }}</a>
      <a href="{{ route('admin.ads_analytics.settings') }}" class="btn btn-sm btn-outline-secondary">{{ __('Settings') }}</a>
    </div>
  </div>

  <div class="ads-card">
    @if(($tiktok['success'] ?? false))
      <h5 class="fw-bold mb-4">{{ __("{{ __('Today') }}'s Performance") }}</h5>
      <div class="metric-row">
        <div class="metric-item">
          <div class="metric-value">{{ number_format($tiktok['spend'] ?? 0, 2) }}</div>
          <div class="metric-label">{{ __('Spend') }}</div>
        </div>
        <div class="metric-item">
          <div class="metric-value">{{ number_format($tiktok['clicks'] ?? 0) }}</div>
          <div class="metric-label">{{ __('Clicks') }}</div>
        </div>
        <div class="metric-item">
          <div class="metric-value">{{ number_format($tiktok['impressions'] ?? 0) }}</div>
          <div class="metric-label">{{ __('Impressions') }}</div>
        </div>
        <div class="metric-item">
          <div class="metric-value">{{ number_format($tiktok['reach'] ?? 0) }}</div>
          <div class="metric-label">{{ __('Reach') }}</div>
        </div>
        <div class="metric-item">
          <div class="metric-value">{{ number_format($tiktok['conversions'] ?? 0) }}</div>
          <div class="metric-label">{{ __('Conversions') }}</div>
        </div>
      </div>
    @else
      <p class="text-muted mb-3">{{ $tiktok['{{ __('message') }}'] ?? 'Configure {{ __('TikTok Ads') }} API in Settings' }}</p>
      <a href="{{ route('admin.ads_analytics.settings') }}" class="btn btn-dark">{{ __('Configure API') }}</a>
    @endif
  </div>
</div>
@endsection
