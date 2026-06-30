@extends('backEnd.layouts.master')

@section('title','{{ __('{{ __('Order Restriction') }} Settings') }}')

@section('content')

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --glass-white: rgba(255, 255, 255, 0.95);
        --text-dark: #2d3748;
        --text-muted: #718096;
        --border-color: #e2e8f0;
    }

    .order-restriction-page-wrapper {
        padding-top: 30px;
        background-color: #f8f9fc;
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }

    /* Header Styling */
    .order-restriction-header-card {
        background: var(--primary-gradient);
        border-radius: 16px;
        padding: 30px;
        color: white;
        box-shadow: 0 10px 25px rgba(245, 87, 108, 0.2);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    /* Form Card Styling */
    .settings-card {
        background: var(--glass-white);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .settings-card-header {
        background: transparent;
        border-bottom: 1px solid var(--border-color);
        padding: 20px 25px;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
    }

    .form-control-lg-custom {
        padding: 12px 15px;
        font-size: 0.95rem;
        border-radius: 8px;
        border: 1px solid #cbd5e0;
    }

    .form-control-lg-custom:focus {
        border-color: #f5576c;
        box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
    }

    /* Button Styling */
    .btn-save {
        background: var(--primary-gradient);
        border: 0;
        padding: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(245, 87, 108, 0.3);
    }

    /* Info Card */
    .info-card {
        background: #f8f9fc;
        border-left: 4px solid #f5576c;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }

    /* Alert Styling */
    .alert-custom {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
</style>

<div class="container-fluid order-restriction-page-wrapper">

    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="order-restriction-header-card d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <div class="bg-white p-2 rounded-circle me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fe-clock fs-2 text-danger"></i>
                    </div>
                    <div>
                        <h2 class="mb-1 text-white fw-bold">{{ __('{{ __('Order Restriction') }} Settings') }}</h2>
                        <p class="mb-0 text-white-50 small">{{ __('Control order limits and restrictions') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        
        <div class="col-lg-8 mb-4">
            
            @if(session()->has('success') || session()->has('{{ __('message') }}'))
                <div class="alert alert-success alert-custom alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                    <i class="fe-check-circle fs-4 me-2"></i>
                    <div>
                        <strong>{{ __('bn_dadb998f') }}</strong> {{ session('success') ?? session('{{ __('message') }}') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-custom alert-dismissible fade show mb-4" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                </div>
            @endif

            <div class="card settings-card h-100">
                <div class="settings-card-header">
                    <i class="fe-sliders me-2 text-danger"></i> {{ __('Order Restriction') }} {{ __('Configuration') }}
                </div>
                
                <div class="card-body p-4">
                    
                    <form action="{{ route('admin.order.restriction.setting.update') }}" method={{ __('"{{ __('POST') }}"') }}>
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2">{{ __('{{ __('Order Restriction') }} Time') }} <span class="text-danger">*</span></label>
                            
                            <div class="input-group">
                                <input type="{{ __('number') }}" name="order_limit_time" 
                                       class="form-control form-control-lg-custom" 
                                       placeholder="{{ __('Enter time in hours') }}"
                                       value="{{ old('order_limit_time', $data->order_limit_time ?? 48) }}"
                                       min="1"
                                       required>
                                <span class="input-group-text bg-light">{{ __('Hours') }}</span>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fe-clock me-1"></i> এই সময়ের মধ্যে একজন কাস্টমার একই {{ __('{{ __('Product') }}s') }} কতবার অর্ডার করতে পারবে তা নির্ধারণ করে। {{ __('bn_9f4bc027') }}: 24 ঘন্টা মানে গত 24 ঘন্টার মধ্যে।
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2">{{ __('Max Order Quantity {{ __('Limit') }}') }} <span class="text-danger">*</span></label>
                            
                            <div class="input-group">
                                <input type="{{ __('number') }}" name="order_limit_qty" 
                                       class="form-control form-control-lg-custom" 
                                       placeholder="{{ __('Enter maximum quantity') }}"
                                       value="{{ old('order_limit_qty', $data->order_limit_qty ?? 2) }}"
                                       min="1"
                                       required>
                                <span class="input-group-text bg-light">{{ __('Times') }}</span>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fe-shopping-cart me-1"></i> নির্ধারিত সময়ের মধ্যে একজন কাস্টমার সর্বোচ্চ কতবার অর্ডার করতে পারবে। {{ __('bn_9f4bc027') }}: 2 মানে সর্বোচ্চ 2 বার।
                            </small>
                        </div>

                        <button type="{{ __('submit') }}" class="btn btn-danger btn-save w-100 text-white rounded-pill">
                            <i class="fe-save me-2"></i> {{ __('Settings') }} আপডেট করুন
                        </button>
                    </form>

                    <div class="info-card">
                        <div class="d-flex">
                            <i class="fe-info text-danger mt-1 me-2"></i>
                            <div>
                                <h6 class="fw-bold mb-2">কিভাবে কাজ করে?</h6>
                                <p class="small text-muted mb-2">
                                    <strong>{{ __('bn_9f4bc027') }}:</strong> যদি {{ __('{{ __('Order Restriction') }} Time') }} = 24 {{ __('Hours') }} এবং {{ __('Max Order Quantity {{ __('Limit') }}') }} = 2 {{ __('bn_290a7f61') }}, তাহলে:
                                </p>
                                <ul class="small text-muted mb-0">
                                    <li>{{ __('bn_b6565af2') }}</li>
                                    <li>{{ __('bn_cb13e02e') }}</li>
                                    <li>{{ __('bn_916e1374') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card settings-card">
                <div class="settings-card-header">
                    <i class="fe-alert-circle me-2 text-warning"></i> {{ __('Important') }} {{ __('Note') }}s
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-2">⚙️ {{ __('Current') }} Settings</h6>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-2"><strong>{{ __('Restriction Time') }}:</strong> <span class="text-primary">{{ $data->order_limit_time ?? 48 }} {{ __('Hours') }}</span></p>
                            <p class="mb-0"><strong>{{ __('Max Quantity') }}:</strong> <span class="text-primary">{{ $data->order_limit_qty ?? 2 }} {{ __('Times') }}</span></p>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning border-0">
                        <small>
                            <strong>{{ __('bn_4652c7ab') }}:</strong> এই {{ __('Settings') }} পরিবর্তন করলে তা সাথে সাথে কার্যকর হবে। 
                            নতুন অর্ডারগুলো এই নিয়ম অনুসরণ করবে।
                        </small>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div> 

@endsection
