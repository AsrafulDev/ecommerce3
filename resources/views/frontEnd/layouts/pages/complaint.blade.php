@extends('frontEnd.layouts.master')
@section('title','Complaint')

@section('content')
<style>
    :root {
        --complaint-red: #e74c3c;
        --soft-bg: #f4f7f6;
        --card-shadow: 0 20px 60px rgba(0,0,0,0.08);
    }

    .complaint-wrapper {
        padding: 80px 0;
        background-color: var(--soft-bg);
    }

    /* উপরের মেনু ডিজাইন */
    .cmn_menu ul {
        display: flex;
        justify-content: center;
        list-style: none;
        padding: 0;
        gap: 15px;
        margin-bottom: 50px;
        flex-wrap: wrap;
    }
    .cmn_menu ul li a {
        text-decoration: none;
        color: #555;
        font-weight: 500;
        padding: 10px 25px;
        border-radius: 50px;
        background: #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        transition: all 0.3s ease;
    }
    .cmn_menu ul li.active a, .cmn_menu ul li a:hover {
        background: var(--complaint-red);
        color: #fff;
    }

    /* মেইন কার্ড */
    .complaint-main-card {
        background: #ffffff;
        border-radius: 25px;
        overflow: hidden;
        box-shadow: var(--card-shadow);
        border: none;
    }

    /* সাইডবার ইমেজ সেকশন */
    .complaint-sidebar-img {
        background-image: url('{{ asset('public/frontEnd/images/contact.png') }}'); 
        background-size: cover;
        background-position: center;
        min-height: 100%;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 40px;
        color: white;
    }

    .complaint-sidebar-img::before {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(to bottom, rgba(0,0,0,0.1) 30%, var(--primary-color) 100%);
    }

    .sidebar-content {
        position: relative;
        z-index: 2;
    }

    /* ফর্ম সেকশন */
    .form-side {
        padding: 50px;
    }

    .account-title {
        font-size: 24px;
        font-weight: 700;
        color: #333;
        margin-bottom: 30px;
        border-left: 5px solid var(--complaint-red);
        padding-left: 15px;
    }

    .form-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: #444;
        margin-bottom: 8px;
    }

    .form-control {
        padding: 12px 15px;
        border-radius: 12px;
        border: 1px solid #e1e1e1;
        background-color: #fdfdfd;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: var(--complaint-red);
        box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
        background-color: #fff;
    }

    .submit-btn {
        background: var(--complaint-red);
        color: white;
        padding: 15px;
        border-radius: 12px;
        font-weight: 600;
        border: none;
        width: 100%;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .submit-btn:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
    }

    /* ছোট ইনফো বক্স */
    .quick-info {
        display: flex;
        gap: 20px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    .info-box {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.85rem;
        color: #666;
    }
    .info-box i {
        color: var(--complaint-red);
    }

    @media (max-width: 991px) {
        .complaint-sidebar-img { min-height: 300px; }
        .form-side { padding: 30px 20px; }
        .complaint-wrapper { padding: 40px 0; }
    }
</style>

<div class="complaint-wrapper">
    <div class="container">
        

        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="complaint-main-card">
                    <div class="row g-0">
                        
                        <div class="col-lg-5">
                            <div class="complaint-sidebar-img">
                                <div class="sidebar-content">
                                    <h3 class="fw-bold mb-2">{{ __('Your feedback is valuable to us') }}</h3>
                                    <p class="small opacity-90"><span style="color: white;">{{ __('If you have any complaints about our service, let us know. We will take immediate action.') }}</span></p>                                    
                                    
                                    <div class="mt-4">
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <i data-feather="phone-call"></i> <span>{{ $contact?->hotline ?? '01XXX-XXXXXX' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <i data-feather="mail"></i> <span>{{ $contact?->email ?? 'support@example.com' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="form-side">
                                <h5 class="account-title">{{ __('Submit a Complaint') }}</h5>

                                @if(session('success'))
                                    <div class="alert alert-success border-0 shadow-sm mb-4">
                                        <i class="me-2" data-feather="check-circle"></i> {{ session('success') }}
                                    </div>
                                @endif

                                

                                <form action="{{ route('complaint.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('Your Name *') }}</label>
                                            <input type="text" name="name" class="form-control" placeholder="{{ __('Enter your name') }}" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('Mobile Number *') }}</label>
                                            <input type="tel" name="phone" class="form-control" placeholder="{{ __('Enter mobile number') }}" required maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g,'')">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">{{ __('Order ID (if any)') }}</label>
                                            <input type="text" name="order_id" class="form-control" placeholder="{{ __('Enter Order ID (if any)') }}">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">{{ __('Complaint Description *') }}</label>
                                            <textarea name="description" class="form-control" rows="4" placeholder="{{ __('Describe your issue in detail...') }}" required></textarea>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">{{ __('Image as evidence (optional)') }}</label>
                                            <input type="file" name="image" class="form-control">
                                            <small class="text-muted">{{ __('You can attach a screenshot or image of the issue.') }}</small>
                                        </div>

                                        <div class="col-12 mt-4">
                                            <button type="submit" class="submit-btn w-100">
                                                {{ __('Submit Complaint') }} <i data-feather="send" style="width: 18px"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <div class="quick-info">
                                    <div class="info-box">
                                        <i data-feather="shield"></i> <span>{{ __('Secure Data') }}</span>
                                    </div>
                                    <div class="info-box">
                                        <i data-feather="clock"></i> <span>{{ __('24-48 hour resolution') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="https://unpkg.com/feather-icons"></script>
<script>
    feather.replace();
</script>
@endpush