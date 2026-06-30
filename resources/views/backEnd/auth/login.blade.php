<!doctype html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>
	<meta name="csrf-token" content="igEtQwGfz0hpKoVDnpDYhEg17PsP86VmBfjfpIDl">

	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- Favicon -->
	<link rel="shortcut icon" href="{{asset($generalsetting->favicon)}}" alt="{{$generalsetting->name}}" />
	<title>Admin Login | {{$generalsetting->name}}</title>

	<!-- google font -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">

	<!-- aiz core css -->
	<link rel="stylesheet" href="{{asset('public/backEnd/')}}/assets_login/css/vendors.css">
    	<link rel="stylesheet" href="{{asset('public/backEnd/')}}/assets_login/css/aiz-core.css">

    <style>
        body {
            font-size: 12px;
        }
        .toggle-password-btn {
            cursor: pointer;
            background: #fff;
            border: 1px solid #ced4da;
            border-left: 0;
            border-radius: 0 0.25rem 0.25rem 0;
            padding: 0 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .toggle-password-btn:hover {
            background: #e9ecef;
        }
        .toggle-password-btn svg {
            width: 18px;
            height: 18px;
            fill: #6c757d;
        }
    </style>

</head>
<body class="">

	<div class="aiz-main-wrapper d-flex">
        <div class="flex-grow-1">
            
<div class="h-100 bg-cover bg-center py-5 d-flex align-items-center" style="background-image: url({{asset('public/backEnd/')}}/assets_login/img/background.jpg)">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-xl-4 mx-auto">
                <div class="card text-left">
                    <div class="card-body">
                        <div class="mb-5 text-center">
                                                            <img src="{{asset($generalsetting->dark_logo)}}" class="mw-100 mb-4" height="40">
                                                        <h1 class="h3 text-primary mb-0">Welcome to {{$generalsetting->name}}</h1>
                            <p>Login to your account.</p>
                        </div>
                        
                        {{-- Show error messages --}}
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                            </div>
                        @endif
                        
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong>
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                            </div>
                        @endif

                          <form method="POST" action="{{route('login')}}" >
                          @csrf
							<div class="form-group">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus placeholder="{{ __('Email') }}">
                                                                                                                                @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
															
															</div>
                            <div class="form-group">
                                <div class="input-group">
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" value="{{ old('password') }}" required placeholder="{{ __('Password') }}">
                                    <span class="toggle-password-btn" onclick="togglePassword('password', this)" title="Show/Hide Password">
                                        <svg class="eye-open" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                        <svg class="eye-closed" viewBox="0 0 24 24" style="display:none;"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                                    </span>
                                </div>
                                                                                                                                    @error('password')
                                                <span class="invalid-feedback d-block" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
															
															</div>
                            <div class="row mb-2">
                                <div class="col-sm-6">
                                    <div class="text-left">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="remember" id="checkbox-signin" value="1" >
                                            <span>{{ __('Remember Me') }}</span>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                                                                    <div class="col-sm-6">
                                        <div class="text-right">
                                            <a href="{{ route('admin.password.request') }}" class="text-reset fs-14">Forgot password ?</a>
                                        </div>
                                    </div>
                                                            </div>
                            <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Login') }}</button>
                        </form>

                        @if(isset($demoMode) && $demoMode)
                        <div class="mt-4 pt-3 border-top">
                            <p class="text-muted small mb-2">ডেমো একাউন্ট</p>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <input type="text" class="form-control form-control-sm" id="demo-email" value="info@creativedesign.com.bd" readonly style="flex:1;min-width:0;">
                                <input type="text" class="form-control form-control-sm" id="demo-password" value="12345678" readonly style="width:100px;">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="fillDemoCreds()">Use</button>
                            </div>
                        </div>
                        @endif
                                            </div>
                </div>
            </div>
        </div>
    </div>
</div>


        </div>
    </div><!-- .aiz-main-wrapper -->

    

    <script src="{{asset('public/backEnd/')}}/assets_login/js/vendors.js" ></script>
    <script src="{{asset('public/backEnd/')}}/assets_login/js/aiz-core.js" ></script>
    <script>
    function togglePassword(fieldId, btn) {
        var field = document.getElementById(fieldId);
        var eyeOpen = btn.querySelector('.eye-open');
        var eyeClosed = btn.querySelector('.eye-closed');
        if (field.type === 'password') {
            field.type = 'text';
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
        } else {
            field.type = 'password';
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
        }
    }
    </script>
    @if(isset($demoMode) && $demoMode)
    <script>
    function fillDemoCreds() {
        var email = document.getElementById('demo-email').value;
        var pass = document.getElementById('demo-password').value;
        document.getElementById('email').value = email;
        document.getElementById('password').value = pass;
        var btn = document.querySelector('button[onclick="fillDemoCreds()"]');
        if (btn) { btn.textContent = 'Filled!'; setTimeout(function(){ btn.textContent = 'Use'; }, 1200); }
    }
    </script>
    @endif

</body>

</html>