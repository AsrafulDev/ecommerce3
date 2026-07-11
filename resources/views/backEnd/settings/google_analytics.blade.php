@extends('backEnd.layouts.master')
@section('title','Google Analytics 4 Settings')

@section('css')
<style>
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 12px;
        overflow: hidden;
    }
    .card-header {
        background: #fff;
        border-bottom: 1px solid #f1f5f7;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 700;
        color: #2d3436;
        margin: 0;
    }
    .header-icon {
        width: 35px;
        height: 35px;
        background: rgba(234, 67, 53, 0.08);
        color: #ea4335;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #636e72;
        margin-bottom: 6px;
    }
    .form-control {
        background-color: #fbfcff;
        border: 1px solid #eef2f7;
        padding: 11px 14px;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-control:focus {
        background-color: #fff;
        border-color: #ea4335;
        box-shadow: 0 0 0 3px rgba(234, 67, 53, 0.15);
    }
    .small-help {
        font-size: 12px;
        color: #95a5a6;
    }
    .btn-submit {
        background: linear-gradient(45deg, #ea4335, #fbbc04);
        border: none;
        color: #fff;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
    }
    .btn-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(234, 67, 53, 0.3);
        color: #fff;
    }
    .status-toggle {
        width: 50px;
        height: 26px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 col-md-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fab fa-google"></i>
                    </div>
                    <h4 class="card-title">Google Analytics 4 Settings</h4>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.google_analytics.update') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label" for="measurement_id">Measurement ID <span class="text-danger">*</span></label>
                            <input type="text" id="measurement_id" name="measurement_id"
                                   class="form-control @error('measurement_id') is-invalid @enderror"
                                   value="{{ old('measurement_id', $setting->measurement_id) }}"
                                   placeholder="G-XXXXXXXXXX" required>
                            <small class="small-help">Your GA4 Measurement ID (starts with <strong>G-</strong>). Found in GA4 Admin → Data Streams.</small>
                            @error('measurement_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="api_secret">API Secret <span class="text-muted">(optional)</span></label>
                            <input type="text" id="api_secret" name="api_secret"
                                   class="form-control @error('api_secret') is-invalid @enderror"
                                   value="{{ old('api_secret', $setting->api_secret) }}"
                                   placeholder="Optional — for Measurement Protocol">
                            <small class="small-help">For Measurement Protocol (server-side) tracking. Found in GA4 Admin → Data Streams → your stream → Measurement Protocol API secrets.</small>
                            @error('api_secret')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input status-toggle" type="checkbox"
                                       name="status" id="status" value="1"
                                       {{ old('status', $setting->status ?? 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="status">
                                    Enable Google Analytics 4 tracking on storefront
                                </label>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-light">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- How to Get Measurement ID --}}
            <div class="card mt-4">
                <div class="card-header">
                    <div class="header-icon" style="background:rgba(52,152,219,0.08);color:#3498db;">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h4 class="card-title">How to Get Your GA4 Measurement ID</h4>
                </div>
                <div class="card-body p-4">
                    <ol class="mb-0" style="line-height: 2;">
                        <li>Go to <a href="https://analytics.google.com" target="_blank">Google Analytics</a></li>
                        <li>Select your GA4 property (or create one)</li>
                        <li>Go to <strong>Admin</strong> → <strong>Data Streams</strong></li>
                        <li>Select your web data stream (or add a new one)</li>
                        <li>Copy the <strong>Measurement ID</strong> (format: <code>G-XXXXXXXXXX</code>)</li>
                        <li>Paste it above and click <strong>Save Settings</strong></li>
                    </ol>
                    <p class="mt-3 small-help mb-0">
                        <i class="fas fa-info-circle"></i>
                        The <code>gtag.js</code> script will be automatically injected into your storefront.
                        All ecommerce events (view_item, add_to_cart, purchase, etc.) are already configured via dataLayer.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
