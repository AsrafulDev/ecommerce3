@extends('frontEnd.layouts.master')
@section('title','Customer Register')

@section('content')
{{-- CSS সরাসরি এখানে --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    .modern-auth-section {
        background-color: #f0f2f5;
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 50px 15px;
        font-family: 'Poppins', sans-serif;
    }

    .auth-container {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        overflow: hidden;
        width: 100%;
        max-width: 950px;
        display: flex;
        flex-wrap: wrap;
    }

    /* ---- বাম পাশ (ইমেজ এরিয়া) ---- */
    .auth-image-area {
        width: 50%;
        /* শপিং রিলেটেড একটি সুন্দর ব্যাকগ্রাউন্ড ইমেজ */
        background-image: url('{{ asset('public/frontEnd/images/login.avif') }}');
        background-size: cover;
        background-position: center;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 40px;
        color: #fff;
        text-align: center;
    }

    /* ওভারলে */
    .auth-image-area::before {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: 1;
    }

    /* টেক্সট */
    .auth-image-area h2,
    .auth-image-area p {
        position: relative;
        z-index: 2;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        color: #fff;
    }
    .auth-image-area h2 { font-weight: 700; margin-bottom: 10px; font-size: 32px; }
    .auth-image-area p { font-size: 16px; opacity: 1; }


    /* ---- ডান পাশ (ফর্ম এরিয়া) ---- */
    .auth-form-area {
        width: 50%;
        padding: 60px 50px;
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .auth-header { margin-bottom: 30px; }
    .auth-header h3 { font-weight: 700; color: #333; margin-bottom: 5px; }
    .auth-header p { color: #888; font-size: 14px; }

    /* ইনপুট ডিজাইন */
    .custom-input-group { position: relative; margin-bottom: 20px; }
    .custom-input-group label {
        display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 14px;
    }
    .custom-input {
        width: 100%; height: 50px; padding: 10px 20px 10px 45px; /* আইকনের জন্য বামে প্যাডিং */
        border: 2px solid #eee; border-radius: 10px;
        font-size: 15px; transition: 0.3s; background: #fdfdfd;
    }
    .custom-input:focus {
        border-color: #764ba2; background: #fff; outline: none;
        box-shadow: 0 0 0 4px rgba(118, 75, 162, 0.1);
    }
    textarea.custom-input {
        height: auto; min-height: 80px; padding-top: 15px; padding-bottom: 15px;
        resize: vertical;
    }
    input[type="file"].custom-input {
        padding-left: 20px; height: auto; padding-top: 12px; padding-bottom: 12px;
    }
    .input-icon {
        position: absolute; left: 15px; top: 43px; color: #aaa; font-size: 16px;
    }

    /* সাবমিট বাটন */
    .btn-auth-submit {
        width: 100%; height: 50px;
        background: var(--secondary-color);
        border: none; border-radius: 10px;
        color: #fff; font-weight: 600; font-size: 16px;
        cursor: pointer; transition: 0.3s;
        text-transform: uppercase; letter-spacing: 1px;
    }
    .btn-auth-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
    }

    /* ফুটার লিংক */
    .login-redirect {
        text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px dashed #ddd;
    }
    .login-redirect p { margin-bottom: 5px; color: #666; font-size: 14px; }
    .login-link {
        text-decoration: none; color: var(--primary-color); font-weight: 700; font-size: 15px;
    }
    .login-link:hover { text-decoration: underline; }

    /* মোবাইল রেসপন্সিভ */
    @media (max-width: 768px) {
        .auth-image-area { display: none; }
        .auth-form-area { width: 100%; padding: 40px 20px; }
    }
</style>

<section class="modern-auth-section">
    <div class="container d-flex justify-content-center">
        <div class="auth-container">
            
            {{-- বাম পাশ: ব্যাকগ্রাউন্ড ইমেজ --}}
            <div class="auth-image-area">
                <h2>Join Us Today!</h2>
                <p>নতুন একাউন্ট খুলে আমাদের সেরা শপিং অভিজ্ঞতা উপভোগ করুন।</p>
            </div>

            {{-- ডান পাশ: ফর্ম --}}
            <div class="auth-form-area">
                <div class="auth-header">
                    <h3>রেজিস্ট্রেশন করুন</h3>
                    <p>আপনার তথ্য দিয়ে ফর্মটি পূরণ করুন</p>
                </div>

                <form action="{{route('customer.store')}}" method="POST" enctype="multipart/form-data" data-parsley-validate="">
                    @csrf
                    
                    {{-- Name Input --}}
                    <div class="custom-input-group">
                        <label for="name">আপনার নাম</label>
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="name" 
                               class="custom-input @error('name') is-invalid @enderror" 
                               name="name" value="{{ old('name') }}" 
                               placeholder="পুরো নাম লিখুন" required>
                        @error('name')
                            <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Phone Input --}}
                    <div class="custom-input-group">
                        <label for="phone">মোবাইল নাম্বার</label>
                        <i class="fas fa-phone-alt input-icon"></i>
                        <input type="tel" id="phone" 
                               class="custom-input @error('phone') is-invalid @enderror" 
                               name="phone" value="{{ old('phone') }}" 
                               placeholder="017xxxxxxxx" required>
                        @error('phone')
                            <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Email Input (Optional) --}}
                    <div class="custom-input-group">
                        <label for="email">ইমেইল <small class="text-muted">(ঐচ্ছিক)</small></label>
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email"
                               class="custom-input @error('email') is-invalid @enderror"
                               name="email" value="{{ old('email') }}"
                               placeholder="example@email.com">
                        @error('email')
                            <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Password Input --}}
                    <div class="custom-input-group">
                        <label for="password">পাসওয়ার্ড</label>
                        <i class="fas fa-lock input-icon"></i>
                        <div style="position: relative;">
                            <input type="password" id="password" 
                                   class="custom-input @error('password') is-invalid @enderror" 
                                   name="password" placeholder="********" required>
                            
                            {{-- পাসওয়ার্ড দেখার আইকন --}}
                            <span onclick="showPass()" style="position: absolute; right: 15px; top: 15px; cursor: pointer; color: #999;">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                        @error('password')
                            <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Submit Button --}}
                    <div class="form-group mt-4">
                        <button class="btn-auth-submit" type="submit"> রেজিস্ট্রেশন করুন </button>
                    </div>

                </form>

                {{-- Login Redirect --}}
                <div class="login-redirect">
                    <p>আগেই রেজিস্ট্রেশন করা আছে?</p>
                    <a href="{{route('customer.login')}}" class="login-link">
                        <i class="fas fa-sign-in-alt me-1"></i> লগিন করুন
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- পাসওয়ার্ড শো করার স্ক্রিপ্ট --}}
<script>
    function showPass() {
        var x = document.getElementById("password");
        if (x.type === "password") {
            x.type = "text";
        } else {
            x.type = "password";
        }
    }
</script>

@endsection

@push('script')
<script src="{{asset('public/frontEnd/')}}/js/parsley.min.js"></script>
<script src="{{asset('public/frontEnd/')}}/js/form-validation.init.js"></script>
@endpush