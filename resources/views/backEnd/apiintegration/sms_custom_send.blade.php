@extends('backEnd.layouts.master')
@section('title','{{ __('Send Custom SMS') }}')

@section('content')

<style>
    .sms-container {
        max-width: 900px;
        margin: 40px auto;
    }

    /* Sleek Card Design */
    .sms-card {
        border: none;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .sms-card-header {
        background: #fcfcfd;
        border-bottom: 1px solid #f0f0f5;
        padding: 25px 30px;
    }

    .sms-card-header h4 {
        margin: 0;
        font-weight: 700;
        color: #1a1c21;
        letter-spacing: -0.5px;
    }

    /* Input Styling */
    .form-group label {
        font-weight: 600;
        color: #475467;
        margin-bottom: 8px;
        font-size: {{ __('14px') }};
    }

    .form-control-minimal {
        border: 1px solid #d0d5dd;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 15px;
        transition: all 0.2s ease;
        background: #fff;
    }

    .form-control-minimal:focus {
        border-color: #7f56d9;
        box-shadow: 0 0 0 4px rgba(127, 86, 217, 0.1);
        outline: none;
    }

    /* Counters & Badges */
    .badge-soft {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-soft-primary { background: #f4f3ff; color: #5925dc; }
    .badge-soft-info { background: #f0f9ff; color: #026aa2; }

    /* Button Styling */
    .btn-send-sms {
        background: #7f56d9;
        border: 1px solid #7f56d9;
        color: #fff;
        font-weight: 600;
        padding: 12px 25px;
        border-radius: 10px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-send-sms:hover {
        background: #6941c6;
        border-color: #6941c6;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(105, 65, 198, 0.2);
    }

    .helper-text {
        font-size: 13px;
        color: #667085;
        margin-top: 6px;
    }
</style>

<div class="container-fluid">
    <div class="sms-container">
        
        <div class="sms-card">
            <div class="sms-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4>{{ __('Send Custom SMS') }}</h4>
                    <p class="text-muted small mb-0">{{ __('bn_f900340f') }}</p>
                </div>
                <i class="fe-mail fs-2 text-muted"></i>
            </div>

            <div class="card-body p-4">
                @if(session('success'))
                    <div class="alert alert-success border-0 mb-4">{{ session('success') }}</div>
                @endif

                <form action="{{ route('admin.sms.custom.send') }}" method={{ __('"{{ __('POST') }}"') }}>
                    @csrf
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0">{{ __('Recipient Numbers') }}</label>
                            <span class="badge-soft badge-soft-primary" id="{{ __('number') }}_count">{{ __('0 Numbers Detected') }}</span>
                        </div>
                        <textarea name="{{ __('phone') }}" id="{{ __('phone') }}_input" class="form-control form-control-minimal" rows="4" 
                                  placeholder="{{ __('01712345678, 01812345678') }}" required></textarea>
                        <div class="helper-text">
                            <i class="fe-info me-1"></i> একাধিক নাম্বার থাকলে কমা ( , ) বা নতুন লাইন (Enter) ব্যব{{ __('bn_f29420ce') }} করুন।
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0">{{ __('Your {{ __('Message') }}') }}</label>
                            <span class="badge-soft badge-soft-info" id="sms_parts">{{ __('1 SMS Part') }}</span>
                        </div>
                        <textarea name="{{ __('message') }}" id="sms_{{ __('message') }}" class="form-control form-control-minimal" rows="5" 
                                  placeholder="আপনার বার্তার বিষয়বস্তু এখানে লিখুন..." required></textarea>
                        
                        <div class="d-flex justify-content-between mt-2">
                            <div class="helper-text">{{ __('bn_0b1475d7') }}</div>
                            <div class="helper-text fw-bold"><span id="char_count">0</span> {{ __('/ 160 Characters') }}</div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="{{ __('submit') }}" class="btn btn-send-sms w-100">
                            <i class="fe-send"></i> Send {{ __('Message') }} Now
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <p class="text-center text-muted mt-4 small">
            Powered by <strong>{{ __('Creative Design {{ __('SMS Gateway') }}') }}</strong>
        </p>
    </div>
</div>

<script>
    // 1. {{ __('Live') }} Number Counter
    const {{ __('phone') }}Input = document.getElementById('{{ __('phone') }}_input');
    const {{ __('number') }}CountDisplay = document.getElementById('{{ __('number') }}_count');

    {{ __('phone') }}Input.addEventListener('input', function() {
        // স্প্লিট করে খালি ভ্যালুগুলো ফিল্টার করা
        const {{ __('number') }}s = this.value.split(/[\n,]+/).map(s => s.trim()).{{ __('filter') }}(s => s.length > 0);
        {{ __('number') }}CountDisplay.inner{{ __('Text') }} = {{ __('number') }}s.length + " Numbers Detected";
    });

    // 2. Character & SMS Part Counter
    const {{ __('message') }}Input = document.getElementById('sms_{{ __('message') }}');
    const charDisplay = document.getElementById('char_count');
    const partDisplay = document.getElementById('sms_parts');

    {{ __('message') }}Input.addEventListener('input', function() {
        const length = this.value.length;
        charDisplay.inner{{ __('Text') }} = length;

        let parts = 1;
        if (length > 160) {
            parts = Math.ceil(length / 153);
        }
        partDisplay.inner{{ __('Text') }} = parts + " SMS Part" + (parts > 1 ? "s" : "");
    });
</script>

@endsection