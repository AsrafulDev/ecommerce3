<!doctype html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>
	<meta name="csrf-token" content="igEtQwGfz0hpKoVDnpDYhEg17PsP86VmBfjfpIDl">

	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- Favicon -->
	<link rel="shortcut icon" href="<?php echo e(asset($generalsetting->favicon)); ?>" alt="<?php echo e($generalsetting->name); ?>" />
	<title>Admin Login | <?php echo e($generalsetting->name); ?></title>

	<!-- google font -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">

	<!-- aiz core css -->
	<link rel="stylesheet" href="<?php echo e(asset('public/backEnd/')); ?>/assets_login/css/vendors.css">
    	<link rel="stylesheet" href="<?php echo e(asset('public/backEnd/')); ?>/assets_login/css/aiz-core.css">

    <style>
        body {
            font-size: 12px;
        }
    </style>

</head>
<body class="">

	<div class="aiz-main-wrapper d-flex">
        <div class="flex-grow-1">
            
<div class="h-100 bg-cover bg-center py-5 d-flex align-items-center" style="background-image: url(<?php echo e(asset('public/backEnd/')); ?>/assets_login/img/background.jpg)">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-xl-4 mx-auto">
                <div class="card text-left">
                    <div class="card-body">
                        <div class="mb-5 text-center">
                                                            <img src="<?php echo e(asset($generalsetting->dark_logo)); ?>" class="mw-100 mb-4" height="40">
                                                        <h1 class="h3 text-primary mb-0">Welcome to <?php echo e($generalsetting->name); ?></h1>
                            <p>Login to your account.</p>
                        </div>
                        
                        
                        <?php if(session('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> <?php echo e(session('error')); ?>

                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($errors->any()): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong>
                                <ul class="mb-0">
                                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><?php echo e($error); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                          <form method="POST" action="<?php echo e(route('login')); ?>" >
                          <?php echo csrf_field(); ?>
							<div class="form-group">
                                <input id="email" type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="email" value="<?php echo e(old('email')); ?>" required autofocus placeholder="Email">
                                                                                                                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <span class="invalid-feedback" role="alert">
                                                <strong><?php echo e($message); ?></strong>
                                            </span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
															
															</div>
                            <div class="form-group">
                                <input id="password" type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="password" value="<?php echo e(old('password')); ?>" required placeholder="Password">
                                                                                                                                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
															
															</div>
                            <div class="row mb-2">
                                <div class="col-sm-6">
                                    <div class="text-left">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="remember" id="checkbox-signin" value="1" >
                                            <span>Remember Me</span>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                                                                    <div class="col-sm-6">
                                        <div class="text-right">
                                            <a href="<?php echo e(route('admin.password.request')); ?>" class="text-reset fs-14">Forgot password ?</a>
                                        </div>
                                    </div>
                                                            </div>
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                Login
                            </button>
                        </form>

                        <?php if(isset($demoMode) && $demoMode): ?>
                        <div class="mt-4 pt-3 border-top">
                            <p class="text-muted small mb-2">ডেমো একাউন্ট</p>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <input type="text" class="form-control form-control-sm" id="demo-email" value="info@creativedesign.com.bd" readonly style="flex:1;min-width:0;">
                                <input type="text" class="form-control form-control-sm" id="demo-password" value="12345678" readonly style="width:100px;">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="fillDemoCreds()">Use</button>
                            </div>
                        </div>
                        <?php endif; ?>
                                            </div>
                </div>
            </div>
        </div>
    </div>
</div>


        </div>
    </div><!-- .aiz-main-wrapper -->

    

    <script src="<?php echo e(asset('public/backEnd/')); ?>/assets_login/js/vendors.js" ></script>
    <script src="<?php echo e(asset('public/backEnd/')); ?>/assets_login/js/aiz-core.js" ></script>
    <?php if(isset($demoMode) && $demoMode): ?>
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
    <?php endif; ?>

</body>

</html><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/backEnd/auth/login.blade.php ENDPATH**/ ?>