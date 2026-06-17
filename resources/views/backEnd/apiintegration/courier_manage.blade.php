@extends('backEnd.layouts.master') 
@section('title', 'Courier API Settings')

@section('css')
<link href="{{ asset('public/backEnd/assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Professional Card Styling */
    .courier-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        height: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: #fff;
        overflow: hidden;
        border: 1px solid #f1f5f9;
    }
    
    .courier-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
    }

    /* Header Styling */
    .card-header-custom {
        padding: 20px 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f5f9;
    }

    /* ------ LOGO FIX START ------ */
    .courier-icon-box {
        width: 70px;  /* আগে 55px ছিল, এখন বড় করা হয়েছে */
        height: 70px; /* আগে 55px ছিল, এখন বড় করা হয়েছে */
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 8px; /* লোগোর চারপাশে একটু ফাঁকা জায়গা */
    }
    .courier-icon-box img {
        width: 100%; /* পুরো বক্স জুড়ে থাকবে */
        height: 100%; /* পুরো বক্স জুড়ে থাকবে */
        object-fit: contain; /* লোগো চ্যাপ্টা হবে না, রেশিও ঠিক থাকবে */
    }
    /* ------ LOGO FIX END ------ */

    /* Specific Courier Colors */
    .header-steadfast { 
        background: linear-gradient(135deg, #fff5f5 0%, #fff 100%); 
        border-left: 5px solid #ff4d4d; 
    }
    .header-redx { 
        background: linear-gradient(135deg, #fef3c7 0%, #fff 100%); 
        border-left: 5px solid #f59e0b; 
    }

    .card-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    /* Form Elements */
    .form-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 8px;
        letter-spacing: 0.6px;
    }

    .form-control {
        border: 1px solid #e2e8f0;
        padding: 12px 15px;
        border-radius: 8px;
        font-size: 14px;
        background-color: #f8fafc;
        transition: all 0.2s;
    }
    .form-control:focus {
        border-color: #6366f1;
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    /* Status Switch Wrapper */
    .status-wrapper {
        background: #fff;
        padding: 15px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }

    /* Update Button */
    .btn-save {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 1px;
        border: none;
        transition: all 0.3s;
        cursor: pointer;
    }
    .btn-steadfast { 
        background: #ff4d4d; 
        color: white; 
        box-shadow: 0 4px 15px rgba(255, 77, 77, 0.3); 
    }
    .btn-steadfast:hover { 
        background: #e60000; 
        transform: translateY(-2px); 
    }
    .btn-redx { 
        background: #f59e0b; 
        color: white; 
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); 
    }
    .btn-redx:hover { 
        background: #d97706; 
        transform: translateY(-2px); 
    }
</style>
@endsection 

@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0" style="font-size: 22px;">Courier Integration</h4>
            <span class="text-muted">Manage API keys for courier services</span>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-lg-4 col-md-12">
            <div class="card courier-card">
                <div class="card-header-custom header-steadfast">
                    <div>
                        <h5 class="card-title">Steadfast Courier</h5>
                        <small class="text-muted">Fast & Reliable Delivery</small>
                    </div>
                    <div class="courier-icon-box">
                        <img src="{{ asset('public/frontEnd/images/stade.svg') }}" alt="Steadfast">
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('courierapi.update') }}" method="POST" data-parsley-validate>
                        @csrf
                        <input type="hidden" name="id" value="{{ $steadfast->id }}">

                        <div class="mb-3">
                            <label class="form-label">API Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('api_key') is-invalid @enderror" 
                                   name="api_key" value="{{ $steadfast->api_key }}" required />
                            @error('api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Secret Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('secret_key') is-invalid @enderror" 
                                   name="secret_key" value="{{ $steadfast->secret_key }}" required />
                            @error('secret_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="status-wrapper">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shipping-fast me-2 text-danger"></i>
                                <span class="fw-bold text-dark" style="font-size: 14px;">Service Status</span>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="1" 
                                       @if($steadfast->status==1) checked @endif 
                                       style="cursor: pointer; width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-save btn-steadfast">
                            <i class="fas fa-save me-2"></i> Update Configuration
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card courier-card">
                <div class="card-header-custom" style="background: linear-gradient(135deg, #e0f2fe 0%, #fff 100%); border-left: 5px solid #0ea5e9;">
                    <div>
                        <h5 class="card-title">Pathao Courier</h5>
                        <small class="text-muted">Fast Delivery Service</small>
                    </div>
                    <div class="courier-icon-box">
                       <img src="https://merchant.pathao.com/assets/logo_pathao_courier.a3ef9b7c.svg" alt="Pathao">
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('courierapi.update') }}" method="POST" data-parsley-validate id="pathao_form">
                        @csrf
                        <input type="hidden" name="id" value="{{ $pathao->id ?? '' }}">
                        <input type="hidden" name="type" value="pathao">

                        <div class="mb-3">
                            <label class="form-label">API URL <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="url" 
                                   value="{{ $pathao->url ?? 'https://api-hermes.pathao.com' }}" 
                                   placeholder="https://api-hermes.pathao.com" required />
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Client ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('client_id') is-invalid @enderror" 
                                   name="client_id" value="{{ $pathao->client_id ?? '' }}" 
                                   placeholder="Enter Pathao Client ID" required />
                            @error('client_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Client Secret <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('client_secret') is-invalid @enderror" 
                                   name="client_secret" value="{{ $pathao->client_secret ?? '' }}" 
                                   placeholder="Enter Pathao Client Secret" required />
                            @error('client_secret')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username/Email <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                   name="username" value="{{ $pathao->username ?? '' }}" 
                                   placeholder="test@pathao.com (Sandbox) or your email (Production)" required />
                            <small class="text-muted">For Sandbox: test@pathao.com | For Production: Your Pathao account email</small>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   name="password" value="{{ $pathao->password ?? '' }}" 
                                   placeholder="lovePathao (Sandbox) or your password (Production)" required />
                            <small class="text-muted">For Sandbox: lovePathao | For Production: Your Pathao account password</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Access Token</label>
                            <div class="input-group">
                                <input type="text" class="form-control" 
                                       value="{{ $pathao->token ?? '' }}" 
                                       id="pathao_token_display" readonly 
                                       placeholder="Token will be generated automatically" />
                                <button type="button" class="btn btn-outline-primary" id="generate_pathao_token">
                                    <i class="fe-refresh-cw"></i> Generate
                                </button>
                            </div>
                            <small class="text-muted">Token will be auto-generated when you save Client ID & Secret</small>
                        </div>

                        <div class="status-wrapper">
                            <div class="d-flex align-items-center">
                                <i class="fe-truck me-2 text-info"></i>
                                <span class="fw-bold text-dark" style="font-size: 14px;">Service Status</span>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="1" 
                                       @if(isset($pathao) && $pathao->status==1) checked @endif 
                                       style="cursor: pointer; width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-save" style="background: #0ea5e9; color: white; box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);">
                            <i class="fe-save me-2"></i> Update Configuration
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- RedX Courier --}}
        <div class="col-lg-4 col-md-12">
            <div class="card courier-card">
                <div class="card-header-custom header-redx">
                    <div>
                        <h5 class="card-title">RedX Courier</h5>
                        <small class="text-muted">Reliable Delivery Service</small>
                    </div>
                    <div class="courier-icon-box">
                        <img src="https://redx.com.bd/images/logo.png" alt="RedX" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ctext x=%2250%22 y=%2250%22 font-size=%2240%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23f59e0b%22%3ERedX%3C/text%3E%3C/svg%3E'">
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('courierapi.update') }}" method="POST" data-parsley-validate>
                        @csrf
                        <input type="hidden" name="id" value="{{ $redx->id ?? '' }}">
                        <input type="hidden" name="type" value="redx">

                        <div class="mb-3">
                            <label class="form-label">Base URL <span class="text-danger">*</span></label>
                            @php
                                // Get current URL and normalize for comparison (remove https://)
                                $currentUrl = $redx->url ?? '';
                                $currentUrlNormalized = preg_replace('/^https?:\/\//', '', $currentUrl);
                                $currentUrlNormalized = rtrim($currentUrlNormalized, '/');
                            @endphp
                            <select class="form-control" name="url" id="redx_url" required>
                                <option value="sandbox.redx.com.bd/v1.0.0-beta" {{ $currentUrlNormalized == 'sandbox.redx.com.bd/v1.0.0-beta' || strpos($currentUrlNormalized, 'sandbox.redx.com.bd') !== false ? 'selected' : '' }}>Sandbox (Testing)</option>
                                <option value="openapi.redx.com.bd/v1.0.0-beta" {{ $currentUrlNormalized == 'openapi.redx.com.bd/v1.0.0-beta' || strpos($currentUrlNormalized, 'openapi.redx.com.bd') !== false ? 'selected' : '' }}>Production (Live)</option>
                            </select>
                            <small class="text-muted">Select Sandbox for testing or Production for live operations</small>
                            @if(!empty($currentUrl))
                                <small class="text-info d-block mt-1">
                                    <i class="fe-info"></i> Current: {{ $currentUrl }}
                                </small>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">API Access Token <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('token') is-invalid @enderror" 
                                   name="token" value="{{ $redx->token ?? '' }}" 
                                   placeholder="Enter RedX API Access Token (without Bearer prefix)" required />
                            <small class="text-muted">
                                <strong>Important:</strong> Enter token only (without "Bearer " prefix).<br>
                                • For <strong>Sandbox</strong>: Use Sandbox token from RedX Sandbox dashboard<br>
                                • For <strong>Production</strong>: Use Production token from RedX OpenAPI dashboard<br>
                                <span class="text-danger">⚠️ Token must match selected environment (Sandbox/Production)</span>
                            </small>
                            @error('token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Webhook Callback URL <small class="text-muted">(Optional)</small></label>
                            <div class="input-group">
                                <input type="text" class="form-control" 
                                       name="webhook_url" 
                                       id="redx_webhook_url"
                                       value="{{ $redx->webhook_url ?? '' }}" 
                                       placeholder="{{ config('app.url') }}/api/redx/webhook" />
                                <button type="button" class="btn btn-outline-secondary" id="copy_webhook_url" title="Copy URL">
                                    <i class="fe-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted">
                                <strong>Suggested URL:</strong> <code id="suggested_webhook_url">{{ config('app.url') }}/api/redx/webhook</code><br>
                                RedX dashboard এ এই URL configure করুন। Parcel status updates automatically receive হবে।<br>
                                <em>Leave empty if you don't want to use webhook.</em>
                            </small>
                        </div>

                        <div class="status-wrapper">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shipping-fast me-2 text-warning"></i>
                                <span class="fw-bold text-dark" style="font-size: 14px;">Service Status</span>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="1" 
                                       @if(isset($redx) && $redx->status==1) checked @endif 
                                       style="cursor: pointer; width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-save btn-redx">
                            <i class="fas fa-save me-2"></i> Update Configuration
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div> </div>
@endsection 

@section('script')
<script src="{{ asset('public/backEnd/assets/libs/parsleyjs/parsley.min.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/js/pages/form-validation.init.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/libs/select2/js/select2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $(".select2").select2();
        
        // Generate Pathao Token
        $('#generate_pathao_token').on('click', function(){
            var $btn = $(this);
            var originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fe-loader"></i> Generating...');
            
            $.ajax({
                url: "/admin/courierapi/pathao-generate-token",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(res){
                    console.log('Token Generation Response:', res);
                    
                    if(res.status === 'success' && res.token){
                        // Update token field
                        $('#pathao_token_display').val(res.token);
                        
                        // Show success message with token info and expiry
                        var tokenPreview = res.token.substring(0, 20) + '...';
                        var expiryInfo = '';
                        if(res.expiry_info){
                            expiryInfo = '<br><small><strong>Token Validity:</strong> ' + res.expiry_info + '</small>';
                        }
                        if(res.expires_at){
                            expiryInfo += '<br><small><strong>Expires At:</strong> ' + res.expires_at + '</small>';
                        }
                        
                        toastr.success(
                            '✅ Token generated successfully!' + expiryInfo + '<br>' +
                            '<small><strong>Token Preview:</strong> ' + tokenPreview + '</small><br>' +
                            '<small>Token has been saved to database.</small>',
                            'Pathao Token Generated',
                            {timeOut: 7000}
                        );
                        
                        // Highlight token field
                        var $tokenField = $('#pathao_token_display');
                        $tokenField.css('background-color', '#d4edda').css('border-color', '#28a745');
                        setTimeout(function(){
                            $tokenField.css('background-color', '').css('border-color', '');
                        }, 2000);
                        
                    } else {
                        toastr.error(res.message || 'Failed to generate token');
                    }
                    $btn.prop('disabled', false).html(originalHtml);
                },
                error: function(xhr, status, error){
                    console.log('AJAX Error:', {xhr: xhr, status: status, error: error});
                    var errorMsg = 'Failed to generate token';
                    
                    if(xhr.status === 404){
                        errorMsg = 'Route not found. Please check the URL.';
                    } else if(xhr.status === 500){
                        errorMsg = 'Server error. Please check server logs.';
                    } else if(xhr.responseJSON && xhr.responseJSON.message){
                        errorMsg = xhr.responseJSON.message;
                    } else if(xhr.responseText){
                        try {
                            var response = JSON.parse(xhr.responseText);
                            errorMsg = response.message || errorMsg;
                        } catch(e) {
                            errorMsg = xhr.responseText.substring(0, 200);
                        }
                    }
                    
                    toastr.error(errorMsg);
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
        
        // Copy Webhook URL
        $('#copy_webhook_url').on('click', function(){
            var webhookUrl = $('#redx_webhook_url').val();
            if (!webhookUrl) {
                webhookUrl = $('#suggested_webhook_url').text();
            }
            
            // Copy to clipboard
            navigator.clipboard.writeText(webhookUrl).then(function() {
                toastr.success('Webhook URL copied to clipboard!', 'Copied', {timeOut: 3000});
                $(this).html('<i class="fe-check"></i>').addClass('btn-success').removeClass('btn-outline-secondary');
                var $btn = $(this);
                setTimeout(function(){
                    $btn.html('<i class="fe-copy"></i>').removeClass('btn-success').addClass('btn-outline-secondary');
                }, 2000);
            }.bind(this)).catch(function(err) {
                // Fallback for older browsers
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(webhookUrl).select();
                document.execCommand('copy');
                $temp.remove();
                toastr.success('Webhook URL copied to clipboard!', 'Copied', {timeOut: 3000});
            });
        });
        
        // Auto-fill webhook URL if empty
        $('#redx_webhook_url').on('focus', function(){
            if (!$(this).val()) {
                var suggestedUrl = $('#suggested_webhook_url').text();
                $(this).val(suggestedUrl);
            }
        });
    });
</script>
@endsection