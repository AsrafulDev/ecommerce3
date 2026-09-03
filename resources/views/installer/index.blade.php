<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('public/assets/images/CurlBazar.svg') }}" />
    <title>Install | Ecommerce Pro</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">
    <link rel="stylesheet" href="{{ asset('public/backEnd/') }}/assets_login/css/vendors.css">
    <link rel="stylesheet" href="{{ asset('public/backEnd/') }}/assets_login/css/aiz-core.css">

    <style>
        body { font-size: 12px; }
        .install-steps { font-size: 13px; }
    </style>
</head>
<body>

<div class="aiz-main-wrapper d-flex">
    <div class="flex-grow-1">
        <div class="h-100 bg-cover bg-center py-5 d-flex align-items-center" style="background-image: url({{ asset('public/backEnd/') }}/assets_login/img/background.jpg)">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 col-xl-6 mx-auto">
                        <div class="card text-left">
                            <div class="card-body">
                                <div class="mb-4 text-center">
                                    <img src="{{ asset('public/assets/images/CurlBazar.svg') }}" class="mw-100 mb-4" height="40">
                                    <h1 class="h3 text-primary mb-0">Setup your store</h1>
                                    <p class="install-steps text-muted">This wizard runs once — it migrates the database, seeds base data, and creates your admin account.</p>
                                </div>

                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <strong>Error!</strong> {{ session('error') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('install.store') }}">
                                    @csrf

                                    <h6 class="text-uppercase fs-12 text-muted mb-2">Store information</h6>
                                    <div class="form-group">
                                        <label>Site name</label>
                                        <input type="text" name="site_name" class="form-control @error('site_name') is-invalid @enderror" value="{{ old('site_name', 'My Store') }}" required>
                                        @error('site_name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                    </div>

                                    <h6 class="text-uppercase fs-12 text-muted mb-2 mt-4">Admin account</h6>
                                    <div class="form-group">
                                        <label>Name</label>
                                        <input type="text" name="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" required>
                                        @error('admin_name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" required>
                                        @error('admin_email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="password" name="admin_password" class="form-control @error('admin_password') is-invalid @enderror" required>
                                                @error('admin_password')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Confirm password</label>
                                                <input type="password" name="admin_password_confirmation" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mt-3">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="seed_demo" value="1" {{ old('seed_demo') ? 'checked' : '' }}>
                                            <span>Also import demo data (categories, products, banners)</span>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-lg btn-block mt-2">Install</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('public/backEnd/') }}/assets_login/js/vendors.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets_login/js/aiz-core.js"></script>
</body>
</html>
