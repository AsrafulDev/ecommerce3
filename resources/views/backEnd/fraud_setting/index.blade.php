@extends('backEnd.layouts.master')

@section('title','Fraud API Settings')

@section('content')

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --glass-white: rgba(255, 255, 255, 0.95);
        --text-dark: #2d3748;
        --text-muted: #718096;
        --border-color: #e2e8f0;
    }

    .fraud-page-wrapper {
        padding-top: 30px;
        background-color: #f8f9fc;
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }

    /* Header Styling */
    .fraud-header-card {
        background: var(--primary-gradient);
        border-radius: 16px;
        padding: 30px;
        color: white;
        box-shadow: 0 10px 25px rgba(118, 75, 162, 0.2);
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
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
    }

    /* Timeline Styling */
    .timeline {
        position: relative;
        padding-left: 10px;
    }
    .timeline-item {
        position: relative;
        padding-left: 40px;
        padding-bottom: 30px;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 2px;
        height: 100%;
        background: #e2e8f0;
    }
    .timeline-item:last-child::before {
        display: none;
    }
    .timeline-badge {
        position: absolute;
        left: -9px;
        top: 0;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #667eea;
        border: 4px solid #fff;
        box-shadow: 0 0 0 1px #667eea;
    }
    .timeline-content h6 {
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 5px;
    }
    .timeline-content p {
        color: var(--text-muted);
        font-size: 0.9rem;
        line-height: 1.5;
    }

    /* Alert Styling */
    .alert-custom {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
</style>

<div class="container-fluid fraud-page-wrapper">

    <div class="row justify-content-center">
        
        <div class="col-lg-5 mb-4">
            
            @if(session()->has('success') || session()->has('message'))
                <div class="alert alert-success alert-custom alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                    <i class="fe-check-circle fs-4 me-2"></i>
                    <div>
                        <strong>সফল হয়েছে!</strong> {{ session('success') ?? session('message') }}
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
                    <i class="fe-sliders me-2 text-primary"></i> API Configuration
                </div>
                
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    
                    <form action="{{ route('admin.fraud.update') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                                <div>
                                    <label class="fw-bold text-dark mb-1 d-block"> {{ __('Fraud Check (API)') }} </label>
                                    <small class="text-muted">ফ্রড চেকিং ফিচার অন/অফ করুন</small>
                                </div>
                                <div class="form-check form-switch form-switch-lg">
                                    <input type="checkbox" name="fraud_check_enabled" id="fraud_check_enabled" 
                                           class="form-check-input" 
                                           value="1" 
                                           {{ old('fraud_check_enabled', $data->fraud_check_enabled ?? 1) ? 'checked' : '' }}
                                           style="width: 3rem; height: 1.5rem; cursor: pointer;">
                                    <label class="form-check-label fw-bold ms-2" for="fraud_check_enabled" id="fraud_status_label">
                                        {{ ($data->fraud_check_enabled ?? 1) ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                                    </label>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fe-shield me-1"></i> বন্ধ থাকলে অর্ডার প্লেস করার সময় API কল করা হবে না।
                            </small>
                        </div>

                        @section('js')
                        <script>
                            document.getElementById('fraud_check_enabled').addEventListener('change', function() {
                                document.getElementById('fraud_status_label').textContent = this.checked ? 'সক্রিয়' : 'নিষ্ক্রিয়';
                            });
                        </script>
                        @endsection

                        <button type="submit" class="btn btn-primary btn-save w-100 text-white rounded-pill">
                            <i class="fe-save me-2"></i> সেটিংস আপডেট করুন
                        </button>
                    </form>

                    <div class="mt-4 p-3 bg-light rounded border border-light">
                        <div class="d-flex">
                            <i class="fe-info text-primary mt-1 me-2"></i>
                            <p class="small text-muted mb-0">
                                <strong>বিঃদ্রঃ</strong> ডুপ্লিকেট অর্ডার চেক করার জন্য আপনার API প্রদানকারীর তথ্য দিন। API ব্যর্থ হলে অর্ডার পেন্ডিং রাখা হবে এবং নোট যোগ করা হবে।
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="ms-lg-3">
                <h5 class="mb-4 fw-bold text-dark px-2 border-start border-4 border-primary">
                    &nbsp;Fraud Check API সেটআপ গাইড
                </h5>

                <div class="timeline mt-2">
                    <div class="timeline-item">
                        <div class="timeline-badge"></div>
                        <div class="timeline-content ms-3">
                            <h6>ডায়নামিক API কনফিগারেশন</h6>
                            <p>এখন আপনি নিজের পছন্দের Fraud Check API প্রদানকারীর এন্ডপয়েন্ট, মেথড (POST/GET), এবং ফোন নম্বর প্যারামিটার কী কনফিগার করতে পারবেন।</p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-badge"></div>
                        <div class="timeline-content ms-3">
                            <h6>কিভাবে কাজ করে?</h6>
                            <p>অর্ডার প্লেস করার সময় আপনার দেওয়া API URL-এ কল যাবে। GET মেথডে প্যারামিটারগুলো URL-এর সাথে সংযুক্ত হবে, POST মেথডে ফর্ম ডাটা হিসেবে যাবে।</p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-badge"></div>
                        <div class="timeline-content ms-3">
                            <h6> {{ __('API Response') }} </h6>
                            <p>API সফলভাবে রেসপন্স করলে অর্ডারের ফ্রড রেট আপডেট হবে। ব্যর্থ হলে অর্ডারটি <strong>পেন্ডিং</strong> স্ট্যাটাসে রাখা হবে এবং একটি নোট যোগ করা হবে।</p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-badge"></div>
                        <div class="timeline-content ms-3">
                            <h6>On / Off সুবিধা</h6>
                            <p>আপনি চাইলে উপরের টগল সুইচ দিয়ে Fraud Check ফিচারটি <strong>বন্ধ</strong> রাখতে পারেন। বন্ধ থাকলে অর্ডার প্লেস করার সময় কোনো API কল হবে না।</p>
                        </div>
                    </div>
                </div>

                <div class="mt-2 ms-4 ps-2">
                    <a href="https://www.fraudcheck.online" target="_blank" 
                       class="btn btn-outline-primary btn-sm rounded-pill px-4">
                        <i class="fe-external-link me-1"></i> fraudcheck.online
                    </a>
                </div>
            </div>
        </div>

    </div>
</div> 

@endsection