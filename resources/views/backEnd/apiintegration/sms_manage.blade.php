@extends('backEnd.layouts.master') 
@section('title','{{ __('SMS Gateway') }} Settings')

@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<style>
    /* Professional Card Styling */
    .card-box {
        background-color: #fff;
        padding: 1.5rem;
        box-shadow: 0 0.75rem 1.5rem rgba(18, 38, 63, .03);
        margin-bottom: 24px;
        border-radius: 0.25rem;
        border: 1px solid #edf2f9;
    }
    
    .card-header-custom {
        background: #f8f9fa;
        padding: 15px;
        border-bottom: 1px solid #eee;
        margin: -1.5rem -1.5rem 1.5rem -1.5rem;
        border-radius: 0.25rem 0.25rem 0 0;
        display: flex;
        align-items: center;
    }

    .card-header-custom h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #343a40;
        text-transform: uppercase;
    }

    /* --- [FIX] INPUT GROUP MERGING STYLES --- */
    /* এই অংশটি আপনার আইকন এবং ইনপুট ফিল্ডকে জোড়া লাগিয়ে রাখবে */
    .input-group {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        width: 100%;
    }
    .input-group-prepend {
        margin-right: -1px; /* বর্ডার ডাবল হওয়া আটকাবে */
        display: flex;
    }
    .input-group-text {
        display: flex;
        align-items: center;
        padding: 0.375rem 0.75rem;
        margin-bottom: 0;
        font-size: .875rem;
        font-weight: 400;
        line-height: 1.5;
        color: #495057;
        text-align: center;
        white-space: nowrap;
        background-color: #f1f5f7; /* হালকা ব্যাকগ্রাউন্ড */
        border: 1px solid #ced4da;
        border-radius: 0.25rem 0 0 0.25rem; /* শুধু বাম পাশ গোল হবে */
    }
    .input-group > .form-control {
        position: relative;
        flex: 1 1 auto;
        width: 1%;
        min-width: 0;
        margin-bottom: 0;
        border-top-left-radius: 0; /* বাম পাশের কোনা সোজা হবে */
        border-bottom-left-radius: 0;
    }
    /* ---------------------------------------- */

    /* Custom Switch Toggle */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 26px;
        margin-bottom: 0;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px; width: 18px;
        left: 4px; bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider { background-color: #28a745; }
    input:focus + .slider { box-shadow: 0 0 1px #28a745; }
    input:checked + .slider:before { transform: translateX(24px); }

    /* Code Block Styling */
    .code-block {
        background: #2d2d2d;
        color: #ccc;
        padding: 15px;
        border-radius: 5px;
        font-family: '{{ __('Courier') }} {{ __('New') }}', {{ __('Courier') }}, monospace;
        font-size: 13px;
        overflow-x: auto;
        margin-top: 10px;
    }
    .keyword { color: #cc99cd; }
    .string { color: #7ec699; }
    .variable { color: #f08d49; }
    
    .instruction-list li {
        margin-bottom: 8px;
        font-size: {{ __('14px') }};
        color: #555;
    }
    .badge-soft-primary {
        background-color: rgba(59,130,246,.1);
        color: #3b82f6;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
</style>
@endsection 

@section('content')
<div class="container-fluid">
    
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between" style="padding: 20px 0;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-sms" style="font-size: 28px; margin-right: 15px; color: #556ee6;"></i>
                    <h4 class="mb-0 font-size-18">{{ __('{{ __('SMS Gateway') }} Integration') }}</h4>
                </div>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('SMS Settings') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7">
            <div class="card-box">
                <div class="card-header-custom">
                    <i class="fas fa-cogs" style="margin-right: 10px; color: #556ee6;"></i>
                    <h4>{{ __('{{ __('Configuration') }} Settings') }}</h4>
                </div>

                <form action="{{route('smsgeteway.update')}}" method={{ __('"{{ __('POST') }}"') }} data-parsley-validate="" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" value="{{$sms->id}}">

                    <div class="form-group mb-4">
                        <label for="api_key" class="form-label font-weight-bold">{{ __('API Key') }}<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                            </div>
                            <input type="text" class="form-control @error('api_key') is-invalid @enderror" 
                                name="api_key" value="{{ $sms->api_key }}" id="api_key" 
                                placeholder="{{ __('Ex: C20023485e9XXXXXX') }}" required />
                        </div>
                        <small class="text-muted">{{ __('bn_21c75349') }}</small>
                        @error('api_key')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group mb-4">
                        <label for="url" class="form-label font-weight-bold">{{ __('API URL') }}<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-link"></i></span>
                            </div>
                            <input type="text" class="form-control @error('url') is-invalid @enderror" 
                                name="url" value="{{ $sms->url }}" id="url" 
                                placeholder="{{ __('{{ __('https://') }}api.smsprovider.com/send') }}" required />
                        </div>
                        <small class="text-muted">{{ __('bn_8ab5cd89') }}</small>
                        @error('url')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <label for="method" class="form-label font-weight-bold">{{ __('API {{ __('Method') }}') }}</label>
                            <select name="method" id="method" class="form-control">
                                <option value={{ __('"{{ __('POST') }}"') }} {{ ($sms->method ?? '{{ __('POST') }}') == '{{ __('POST') }}' ? 'selected' : '' }}>{{ __('POST') }}</option>
                                <option value="{{ __('GET') }}" {{ ($sms->method ?? '{{ __('POST') }}') == '{{ __('GET') }}' ? 'selected' : '' }}>{{ __('GET') }}</option>
                            </select>
                            <small class="text-muted">{{ __('HTTP {{ __('Method') }} ({{ __('POST') }}/{{ __('GET') }})') }}</small>
                        </div>
                        <div class="col-md-3 mb-4">
                            <label for="{{ __('phone') }}_key" class="form-label font-weight-bold">{{ __('{{ __('Phone') }} Key') }}</label>
                            <input type="text" name="{{ __('phone') }}_key" id="{{ __('phone') }}_key" class="form-control" 
                                value="{{ $sms->{{ __('phone') }}_key ?? '{{ __('number') }}' }}" placeholder="{{ __('number') }}" />
                            <small class="text-muted">{{ __('bn_7be7afd1') }}</small>
                        </div>
                        <div class="col-md-3 mb-4">
                            <label for="{{ __('message') }}_key" class="form-label font-weight-bold">{{ __('{{ __('Message') }} Key') }}</label>
                            <input type="text" name="{{ __('message') }}_key" id="{{ __('message') }}_key" class="form-control" 
                                value="{{ $sms->{{ __('message') }}_key ?? '{{ __('message') }}' }}" placeholder="{{ __('message') }}" />
                            <small class="text-muted">{{ __('bn_27f71603') }}</small>
                        </div>
                        <div class="col-md-3 mb-4">
                            <label for="serderid" class="form-label font-weight-bold">{{ __('Sender ID') }}</label>
                            <input type="text" name="serderid" id="serderid" class="form-control" 
                                value="{{ $sms->serderid }}" placeholder="{{ __('Ex: 8801234') }}" />
                            <small class="text-muted">{{ __('SMS {{ __('Sender ID') }} (optional)') }}</small>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="admin_{{ __('phone') }}_list" class="form-label font-weight-bold">{{ __('Admin Notification Numbers') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                            </div>
                            <input type="text" class="form-control @error('admin_{{ __('phone') }}_list') is-invalid @enderror" 
                                name="admin_{{ __('phone') }}_list" id="admin_{{ __('phone') }}_list"
                                value="{{ old('admin_{{ __('phone') }}_list', env('ADMIN_PHONE_LIST', $sms->admin_{{ __('phone') }} ?? '')) }}" 
                                placeholder="{{ __('01711111111, 01822222222') }}" />
                        </div>
                        <small class="text-muted">{{ __('bn_dec96314') }}</small>
                        @error('admin_{{ __('phone') }}_list')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <hr class="mt-4 mb-4">
                    <h5 class="font-size-14 mb-3 text-uppercase text-muted"><i class="fas fa-bell mr-2"></i>{{ __('Automation Triggers') }}</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center border p-3 rounded bg-light">
                                <div>
                                    <h6 class="mb-1">{{ __('Gateway {{ __('Status') }}') }}</h6>
                                    <small class="text-muted">{{ __('{{ __('Enable') }}/Disable SMS System') }}</small>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" value="1" @if($sms->status==1)checked @endif name="status" />
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center border p-3 rounded">
                                <div>
                                    <h6 class="mb-1">{{ __('Order Confirmation') }}</h6>
                                    <small class="text-muted">{{ __('SMS when order placed') }}</small>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" value="1" @if($sms->order==1)checked @endif name="order" />
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center border p-3 rounded">
                                <div>
                                    <h6 class="mb-1">{{ __('Forgot Password') }}</h6>
                                    <small class="text-muted">{{ __('{{ __('OTP') }} for password reset') }}</small>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" value="1" @if($sms->forget_pass==1)checked @endif name="forget_pass" />
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="d-flex justify-content-between align-items-center border p-3 rounded">
                                <div>
                                    <h6 class="mb-1">{{ __('{{ __('{{ __('Use') }}r') }} Registration') }}</h6>
                                    <small class="text-muted">{{ __('Send generated password') }}</small>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" value="1" @if($sms->password_g==1)checked @endif name="password_g" />
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="{{ __('submit') }}" class="btn btn-primary btn-lg waves-effect waves-light px-5">
                            <i class="fas fa-save mr-1"></i> Save {{ __('Configuration') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card-box bg-white border-info">
                <div class="card-header-custom" style="background: #eef2ff;">
                    <i class="fas fa-book-reader" style="margin-right: 10px; color: #556ee6;"></i>
                    <h4>{{ __('{{ __('API Integration') }} Guide') }}</h4>
                </div>
                
                <div class="p-2">
                    <h5 class="text-primary mb-3">কিভাবে সেটআপ করবেন?</h5>
                    <ul class="instruction-list pl-3">
                        <li><strong>{{ __('bn_59cad639') }}:</strong> {{ __('bn_732c0254') }}</li>
                        <li><strong>{{ __('bn_2beb1c7d') }}:</strong> {{ __('bn_c5228f06') }}</li>
                        <li><strong>{{ __('bn_f3773f2d') }}:</strong> {{ __('bn_8274c6ab') }}</li>
                        <li><strong>{{ __('bn_e8c1bf95') }}:</strong> {{ __('bn_898add85') }}</li>
                    </ul>

                    <h5 class="text-primary mt-4 mb-3">{{ __('API {{ __('Parameter') }}s') }}</h5>
                    <table class="table table-sm table-bordered font-size-13">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ __('Parameter') }}</th>
                                <th>{{ __('Value') }}</th>
                                <th>{{ __('Description') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>api_key</code></td>
                                <td>{{ __('String') }}</td>
                                <td>{{ __('Your unique API key') }}</td>
                            </tr>
                            <tr>
                                <td><code>{{ $sms->{{ __('phone') }}_key ?? '{{ __('number') }}' }}</code></td>
                                <td>88017...</td>
                                <td>{{ __('bn_783a0e9b') }}</td>
                            </tr>
                            <tr>
                                <td><code>{{ $sms->{{ __('message') }}_key ?? '{{ __('message') }}' }}</code></td>
                                <td>{{ __('Text') }}</td>
                                <td>{{ __('bn_79d56cf8') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <h5 class="text-primary mt-4 mb-2">{{ __('{{ __('PHP') }} Integration {{ __('Example') }}') }}</h5>
                    <p class="text-muted font-size-12 mb-2">{{ __('bn_96464f34') }}:</p>
                    
                    <div class="code-block">
<pre>
<span class="keyword">$url</span> = <span class="string">"YOUR_API_URL"</span>;
<span class="keyword">$method</span> = <span class="string">{{ __('"{{ __('POST') }}"') }}</span>; <span class="variable">{{ __('// or {{ __('GET') }}') }}</span>
<span class="keyword">$data</span> = [
  <span class="string">"api_key"</span> => <span class="string">"YOUR_API_KEY"</span>,
  <span class="string">"YOUR_PHONE_KEY"</span> => <span class="string">{{ __('"88{{ __('017XXXXXXXX') }}"') }}</span>,
  <span class="string">"YOUR_MESSAGE_KEY"</span> => <span class="string">{{ __('"Test SMS"') }}</span>
];

<span class="keyword">$ch</span> = curl_init();
curl_setopt(<span class="keyword">$ch</span>, CURLOPT_URL, <span class="keyword">$url</span>);
<span class="keyword">if</span>(<span class="keyword">$method</span> == <span class="string">{{ __('"{{ __('POST') }}"') }}</span>) {
    curl_setopt(<span class="keyword">$ch</span>, CURLOPT_{{ __('POST') }}, 1);
    curl_setopt(<span class="keyword">$ch</span>, CURLOPT_{{ __('POST') }}FIELDS, <span class="keyword">$data</span>);
}
curl_setopt(<span class="keyword">$ch</span>, CURLOPT_RETURNTRANSFER, true);
<span class="keyword">$response</span> = curl_exec(<span class="keyword">$ch</span>);
curl_close(<span class="keyword">$ch</span>);
</pre>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection 

@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-validation.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $(".select2").select2();
    });
</script>
@endsection