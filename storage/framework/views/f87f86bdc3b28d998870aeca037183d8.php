<nav class="sidebar" id="sidebar">
    <div class="brand-section">
        <div class="d-flex align-items-center">
            <?php if($vendor->logo): ?>
                <img src="<?php echo e(asset($vendor->logo)); ?>" alt="<?php echo e($vendor->shop_name); ?>" class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
            <?php endif; ?>
            <?php echo e($vendor->shop_name); ?>

        </div>
        <button class="btn btn-sm text-secondary d-xl-none" id="closeSidebar" onclick="toggleSidebar()">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>
    <div class="py-3">
        <a href="<?php echo e(route('vendor.dashboard')); ?>" class="nav-link <?php echo e(request()->routeIs('vendor.dashboard') ? 'active' : ''); ?>">
            <i class="fas fa-th-large"></i> ড্যাশবোর্ড
        </a>
        <a href="<?php echo e(route('vendor.products.index')); ?>" class="nav-link <?php echo e(request()->routeIs('vendor.products*') ? 'active' : ''); ?>">
            <i class="fas fa-box-open"></i> প্রোডাক্ট ম্যানেজ
        </a>
        <a href="<?php echo e(route('vendor.orders')); ?>" class="nav-link <?php echo e(request()->routeIs('vendor.orders*') ? 'active' : ''); ?>">
            <i class="fas fa-shopping-cart"></i> অর্ডার সমূহ
            <?php if(isset($pendingOrders) && $pendingOrders > 0): ?>
            <span class="badge bg-danger ms-auto rounded-pill"><?php echo e($pendingOrders); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo e(route('vendor.refunds.index')); ?>" class="nav-link <?php echo e(request()->routeIs('vendor.refunds*') ? 'active' : ''); ?>">
            <i class="fas fa-undo"></i> রিফান্ড
        </a>
        <a href="<?php echo e(route('vendor.customers')); ?>" class="nav-link <?php echo e(request()->routeIs('vendor.customers*') ? 'active' : ''); ?>">
            <i class="fas fa-users"></i> কাস্টমার
        </a>
        <a href="<?php echo e(route('vendor.analytics')); ?>" class="nav-link <?php echo e(request()->routeIs('vendor.analytics*') ? 'active' : ''); ?>">
            <i class="fas fa-chart-pie"></i> অ্যানালিটিক্স
        </a>
        <a href="<?php echo e(route('vendor.withdrawals.index')); ?>" class="nav-link <?php echo e(request()->routeIs('vendor.withdrawals*') ? 'active' : ''); ?>">
            <i class="fas fa-wallet"></i> ওয়ালেট
        </a>
        
        <div class="mt-5 border-top pt-3">
            <a href="<?php echo e(route('vendor.settings')); ?>" class="nav-link <?php echo e(request()->routeIs('vendor.settings*') ? 'active' : ''); ?>">
                <i class="fas fa-cog"></i> সেটিংস
            </a>
            <form action="<?php echo e(route('vendor.logout')); ?>" method="POST" class="d-inline w-100 demo-allow-logout">
                <?php echo csrf_field(); ?>
                <button type="submit" class="nav-link text-danger w-100 text-start border-0 bg-transparent">
                    <i class="fas fa-sign-out-alt"></i> লগ আউট
                </button>
            </form>
        </div>
    </div>
</nav>
<?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/vendor/partials/sidebar.blade.php ENDPATH**/ ?>