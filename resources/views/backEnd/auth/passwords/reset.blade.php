<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>Reset Password | {{$generalsetting->name}}</title>
	<link rel="shortcut icon" href="{{asset($generalsetting->favicon)}}" alt="{{$generalsetting->name}}" />

	<!-- google font -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">

	<!-- aiz core css -->
	<link rel="stylesheet" href="{{asset('public/backEnd/')}}/assets_login/css/vendors.css">
	<link rel="stylesheet" href="{{asset('public/backEnd/')}}/assets_login/css/aiz-core.css">

	<style>
		body {
			font-size: 12px;
		}
	</style>
</head>
<body>

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
									<h1 class="h3 text-primary mb-0">Reset Password</h1>
									<p>Enter your new password below</p>
								</div>

								<form method="POST" action="{{ route('admin.password.update') }}">
									@csrf
									<input type="hidden" name="token" value="{{ $token }}">

									<div class="form-group">
										<input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="Email" required>
										@error('email')
											<span class="invalid-feedback" role="alert">
												<strong>{{ $message }}</strong>
											</span>
										@enderror
									</div>

									<div class="form-group">
										<input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="New Password" required>
										@error('password')
											<span class="invalid-feedback" role="alert">
												<strong>{{ $message }}</strong>
											</span>
										@enderror
									</div>

									<div class="form-group">
										<input id="password-confirm" type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password" required>
									</div>

									<button type="submit" class="btn btn-primary btn-lg btn-block">
										Reset Password
									</button>
								</form>
								
								<div class="mt-3 text-center">
									<a href="{{ route('login') }}" class="text-reset fs-14">Back to Login</a>
								</div>
							</div>
						</div><!-- card -->
					</div>
				</div>
			</div>
		</div>
	</div>
</div><!-- .aiz-main-wrapper -->

<script src="{{asset('public/backEnd/')}}/assets_login/js/vendors.js"></script>
<script src="{{asset('public/backEnd/')}}/assets_login/js/aiz-core.js"></script>
</body>
</html>
