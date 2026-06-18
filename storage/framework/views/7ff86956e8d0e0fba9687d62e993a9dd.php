<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />

    <title><?php echo $__env->yieldContent('title'); ?><?php if(isset($generalsetting) && $generalsetting): ?> - <?php echo e($generalsetting->name); ?><?php endif; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="<?php echo e(asset(isset($generalsetting->favicon) ? $generalsetting->favicon : 'public/backEnd/assets/images/favicon.ico')); ?>" />

    <!-- Bootstrap css -->
    <link href="<?php echo e(asset('public/backEnd/')); ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- App css -->
    <link href="<?php echo e(asset('public/backEnd/')); ?>/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- icons -->
    <link href="<?php echo e(asset('public/backEnd/')); ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- toastr css -->
    <link rel="stylesheet" href="<?php echo e(asset('public/backEnd/')); ?>/assets/css/toastr.min.css" />
    <!-- SweetAlert2 - ডেমো মুড পপআপের জন্য -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <!-- custom css -->
    <link href="<?php echo e(asset('public/backEnd/')); ?>/assets/css/custom.css" rel="stylesheet" type="text/css" />
    <!-- Head js -->
    <?php echo $__env->yieldContent('css'); ?>
    <style>.navbar-custom .dropdown-menu .noti-scroll{max-height:230px!important;overflow-y:auto!important}</style>
    <script src="<?php echo e(asset('public/backEnd/')); ?>/assets/js/head.js"></script>
  </head>

  <!-- body start -->
  <body data-layout-mode="default" data-theme="light" data-layout-width="fluid" data-topbar-color="dark" data-menu-position="fixed" data-leftbar-color="light" data-leftbar-size="default" data-sidebar-user="false">
    <!-- Begin page -->
    <div id="wrapper">
      <!-- Topbar Start -->
      <div class="navbar-custom">
        <div class="container-fluid">
          <ul class="list-unstyled topnav-menu float-end mb-0">
            <li class="dropdown d-inline-block d-lg-none">
              <a class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <i class="fe-search noti-icon"></i>
              </a>
              <div class="dropdown-menu dropdown-lg dropdown-menu-end p-0">
                <form class="p-3">
                  <input type="text" class="form-control" placeholder="Search ..." aria-label="Recipient's username" />
                </form>
              </div>
            </li>

            <li class="dropdown d-none d-lg-inline-block">
              <a class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-toggle="fullscreen" href="#">
                <i class="fe-maximize noti-icon"></i>
              </a>
            </li>

            <?php if(isset($demoMode) && $demoMode): ?>
            <li class="dropdown d-none d-lg-inline-block">
              <span class="badge bg-warning text-dark px-2 py-1 mt-1" title=".env থেকে DEMO_MODE=true সেট করা আছে"><i class="fe-eye me-1"></i>ডেমো</span>
            </li>
            <?php endif; ?>

            <li class="dropdown notification-list topbar-dropdown">
              <a class="nav-link dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <i class="fe-bell noti-icon"></i>
                <span class="badge bg-danger rounded-circle noti-icon-badge"><?php echo e($neworder); ?></span>
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-lg">
                <!-- item-->
                <div class="dropdown-item noti-title">
                  <h5 class="m-0">
                    <span class="float-end">
                      <a href="<?php echo e(route('admin.orders',['slug'=>'pending'])); ?>" class="text-dark">
                        <small>View All</small>
                      </a>
                    </span>
                    Orders
                  </h5>
                </div>

                <div class="noti-scroll">
                  <?php $__currentLoopData = $pendingorder; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $porder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <!-- item-->
                  <a href="<?php echo e(route('admin.orders',['slug'=>'pending'])); ?>" class="dropdown-item notify-item active">
                    <div class="notify-icon">
                      <img src="<?php echo e(asset($porder->customer?$porder->customer->image:'')); ?>" class="img-fluid rounded-circle" alt="" />
                    </div>
                    <p class="notify-details"><?php echo e($porder->customer?$porder->customer->name:''); ?></p>
                    <p class="text-muted mb-0 user-msg">
                      <small>Invoice : <?php echo e($porder->invoice_id); ?></small>
                    </p>
                  </a>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                  <!-- item-->
                </div>

                <!-- All-->
                <a href="<?php echo e(route('admin.orders',['slug'=>'pending'])); ?>" class="dropdown-item text-center text-primary notify-item notify-all">
                  View all
                  <i class="fe-arrow-right"></i>
                </a>
              </div>
            </li>

            <li class="dropdown notification-list topbar-dropdown">
              <a class="nav-link dropdown-toggle nav-user me-0 waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <img src="<?php echo e(asset(Auth::guard('admin')->user()->image)); ?>" alt="user-image" class="rounded-circle" />
                <span class="pro-user-name ms-1"> <?php echo e(Auth::guard('admin')->user()->name); ?> <i class="mdi mdi-chevron-down"></i> </span>
              </a>
              <div class="dropdown-menu dropdown-menu-end profile-dropdown">
                <!-- item-->
                <div class="dropdown-header noti-title">
                  <h6 class="text-overflow m-0">Welcome !</h6>
                </div>

                <!-- item-->
                <a href="<?php echo e(url('admin/dashboard')); ?>" class="dropdown-item notify-item">
                  <i class="fe-user"></i>
                  <span>Dashboard</span>
                </a>

                <!-- item-->

                <div class="dropdown-divider"></div>

                <!-- item-->
                <a
                  href="<?php echo e(route('logout')); ?>"
                  onclick="event.preventDefault();
                  document.getElementById('logout-form').submit();"
                  class="dropdown-item notify-item"
                >
                  <i class="fe-log-out me-1"></i>
                  <span>Logout</span>
                </a>
                <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" style="display: none;">
                  <?php echo csrf_field(); ?>
                </form>
              </div>
            </li>

            <!--<li class="dropdown notification-list">-->
            <!--    <a href="javascript:void(0);" class="nav-link right-bar-toggle waves-effect waves-light">-->
            <!--        <i class="fe-settings noti-icon"></i>-->
            <!--    </a>-->
            <!--</li>-->
          </ul>

          <!-- LOGO -->
          <div class="logo-box">
            <a href="<?php echo e(url('admin/dashboard')); ?>" class="logo logo-dark text-center">
              <span class="logo-sm">
                <img src="<?php echo e(asset(isset($generalsetting->white_logo) ? $generalsetting->white_logo : 'public/backEnd/assets/images/logo.png')); ?>" alt="" height="50" />
                <!-- <span class="logo-lg-text-light">UBold</span> -->
              </span>
              <span class="logo-lg">
                <img src="<?php echo e(asset(isset($generalsetting->white_logo) ? $generalsetting->white_logo : 'public/backEnd/assets/images/logo.png')); ?>" alt="" height="50" />
                <!-- <span class="logo-lg-text-light">U</span> -->
              </span>
            </a>

            <a href="<?php echo e(url('admin/dashboard')); ?>" class="logo logo-light text-center">
              <span class="logo-sm">
                <img src="<?php echo e(asset(isset($generalsetting->white_logo) ? $generalsetting->white_logo : 'public/backEnd/assets/images/logo.png')); ?>" alt="" height="50" />
              </span>
              <span class="logo-lg">
                <img src="<?php echo e(asset(isset($generalsetting->white_logo) ? $generalsetting->white_logo : 'public/backEnd/assets/images/logo.png')); ?>" alt="" height="50" />
              </span>
            </a>
          </div>

          <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
            <li>
              <button class="button-menu-mobile waves-effect waves-light">
                <i class="fe-menu"></i>
              </button>
            </li>

            <li>
              <!-- Mobile menu toggle (Horizontal Layout)-->
              <a class="navbar-toggle nav-link" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <div class="lines">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
              </a>
              <!-- End mobile menu toggle-->
            </li>

            <li class="dropdown d-none d-xl-block">
              <a class="nav-link dropdown-toggle waves-effect waves-light" href="<?php echo e(route('home')); ?>" target="_blank"> <i data-feather="globe"></i> Visit Site </a>
            </li>
          </ul>
          <div class="clearfix"></div>
        </div>
      </div>
      <!-- end Topbar -->

      <!-- ========== Left Sidebar Start ========== -->
      <div class="left-side-menu">
        <div class="h-100" data-simplebar>
          <!-- User box -->
          <div class="user-box text-center">
            <img src="<?php echo e(asset('public/backEnd/')); ?>/assets/images/users/user-1.jpg" alt="user-img" title="Mat Helme" class="rounded-circle avatar-md" />
            <div class="dropdown">
              <a href="javascript: void(0);" class="text-dark dropdown-toggle h5 mt-2 mb-1 d-block" data-bs-toggle="dropdown"><?php echo e(Auth::guard('admin')->user()->name); ?></a>
              <div class="dropdown-menu user-pro-dropdown">
                <!-- item-->
                <a href="javascript:void(0);" class="dropdown-item notify-item">
                  <i class="fe-user me-1"></i>
                  <span>My Account</span>
                </a>

                <!-- item-->
                <a href="javascript:void(0);" class="dropdown-item notify-item">
                  <i class="fe-settings me-1"></i>
                  <span>Settings</span>
                </a>

                <!-- item-->
                <a href="javascript:void(0);" class="dropdown-item notify-item">
                  <i class="fe-lock me-1"></i>
                  <span>Lock Screen</span>
                </a>

                <!-- item-->
                <a
                  href="<?php echo e(route('logout')); ?>"
                  onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();"
                  class="dropdown-item notify-item"
                >
                  <i class="fe-log-out me-1"></i>
                  <span>Logout</span>
                </a>
                <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" style="display: none;">
                  <?php echo csrf_field(); ?>
                </form>
              </div>
            </div>
            <p class="text-muted">Admin Head</p>
          </div>

          <!--- Sidemenu -->
          <div id="sidebar-menu">
            <ul id="side-menu">
<li>
  <a href="<?php echo e(url('admin/dashboard')); ?>">
    <i data-feather="airplay"></i>
    <span> Dashboard </span>
  </a>
</li>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('order-create')): ?>
<li>
  <a href="<?php echo e(route('admin.order.create')); ?>">
    <i data-feather="cpu"></i>
    <span>POS System</span>
  </a>
</li>
<?php endif; ?>

<?php
  use Illuminate\Support\Facades\Auth;
  // ✅ Use admin guard for permission checks
  $user = Auth::guard('admin')->user();
  $pending_reviews = \App\Models\Review::where('status', 'pending')->count();
?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['order-list', 'order-edit', 'order-create'])): ?>
<li>
  <a href="#sidebar-orders" data-bs-toggle="collapse">
    <i data-feather="shopping-cart"></i>
    <span> Orders </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-orders">
    <ul class="nav-second-level">
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('order-list')): ?>
      <li><a href="<?php echo e(route('admin.orders', ['slug'=>'all'])); ?>"><i data-feather="file-plus"></i> All Order</a></li>

      <li><a href="<?php echo e(route('admin.incomplete-orders.index')); ?>"><i data-feather="file-plus"></i> Incomplete Orders</a></li>
      <?php $__currentLoopData = $orderstatus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><a href="<?php echo e(route('admin.orders', ['slug'=>$value->slug])); ?>"><i data-feather="file-plus"></i><?php echo e($value->name); ?></a></li>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('order-edit')): ?>
      <li><a href="<?php echo e(route('orderstatus.index')); ?>"><i data-feather="file-plus"></i> Order Status</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('order-manage')): ?>
      <li>
        <a href="<?php echo e(route('customers.ip_block')); ?>"><i data-feather="file-plus"></i> IP Block</a>
      </li>
      <?php endif; ?>

    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['order-list', 'order-edit'])): ?>
<li class="<?php echo e(request()->routeIs('admin.refunds.*') ? 'active' : ''); ?>">
  <a href="#sidebar-refunds" data-bs-toggle="collapse">
    <i data-feather="rotate-ccw"></i>
    <span> Refunds </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse <?php echo e(request()->routeIs('admin.refunds.*') ? 'show' : ''); ?>" id="sidebar-refunds">
    <ul class="nav-second-level">
      <li><a href="<?php echo e(route('admin.refunds.index')); ?>"><i data-feather="list"></i> All Refunds</a></li>
      <li><a href="<?php echo e(route('admin.refunds.index', ['status' => 'pending'])); ?>"><i data-feather="clock"></i> Pending Refunds</a></li>
      <li><a href="<?php echo e(route('admin.refunds.index', ['status' => 'approved'])); ?>"><i data-feather="check-circle"></i> Approved Refunds</a></li>
      <li><a href="<?php echo e(route('admin.refunds.index', ['status' => 'processed'])); ?>"><i data-feather="check"></i> Processed Refunds</a></li>
    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['product-list', 'category-list', 'subcategory-list', 'childcategory-list'])): ?>
<li>
  <a href="#siebar-product" data-bs-toggle="collapse">
    <i data-feather="database"></i>
    <span> Products </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="siebar-product">
    <ul class="nav-second-level">
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product-list')): ?>
      <li><a href="<?php echo e(route('inhouse.products.index')); ?>"><i data-feather="package"></i> All Inhouse Products</a></li>
      <li><a href="<?php echo e(route('products.pending')); ?>"><i data-feather="clock"></i> Pending Products</a></li>
      <li><a href="<?php echo e(route('admin.products.wholesale')); ?>"><i data-feather="layers"></i> Wholesale Products</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product-create')): ?>
      <li><a href="<?php echo e(route('products.create')); ?>"><i data-feather="plus-circle"></i> Add Product</a></li>
      <?php endif; ?>
      <li><hr class="dropdown-divider"></li>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('category-list')): ?>
      <li><a href="<?php echo e(route('categories.index')); ?>"><i data-feather="file-plus"></i> Categories</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('subcategory-list')): ?>
      <li><a href="<?php echo e(route('subcategories.index')); ?>"><i data-feather="file-plus"></i> Subcategories</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('childcategory-list')): ?>
      <li><a href="<?php echo e(route('childcategories.index')); ?>"><i data-feather="file-plus"></i> Childcategories</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['brand-list', 'brand-create', 'brand-edit'])): ?>
      <li><a href="<?php echo e(route('brands.index')); ?>"><i data-feather="file-plus"></i> Brands</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['color-list', 'color-create', 'color-edit'])): ?>
      <li><a href="<?php echo e(route('colors.index')); ?>"><i data-feather="file-plus"></i> Colors</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['size-list', 'size-create', 'size-edit'])): ?>
      <li><a href="<?php echo e(route('sizes.index')); ?>"><i data-feather="file-plus"></i> Sizes</a></li>
      <?php endif; ?>
    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['blog-list','blog-create','blog-edit','blog-delete'])): ?>
<li>
    <a href="#sidebar-blog" data-bs-toggle="collapse">
        <i data-feather="edit"></i>
        <span> Blog </span>
        <span class="menu-arrow"></span>
    </a>

    <div class="collapse" id="sidebar-blog">
        <ul class="nav-second-level">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('blog-list')): ?>
            <li>
                <a href="<?php echo e(route('admin.blog.index')); ?>">
                    <i data-feather="list"></i>
                    All Blogs
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('blog-create')): ?>
            <li>
                <a href="<?php echo e(route('admin.blog.create')); ?>">
                    <i data-feather="plus-circle"></i>
                    Add New Blog
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['purchase-list', 'purchase-create', 'purchase-edit'])): ?>
<li>
  <a href="<?php echo e(route('purchases.index')); ?>">
    <i data-feather="file-text"></i>
    <span>Purchases</span>
  </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['supplier-list', 'supplier-create', 'supplier-edit'])): ?>
<li>
  <a href="<?php echo e(route('admin.suppliers.index')); ?>">
    <i data-feather="truck"></i>
    <span>Suppliers</span>
  </a>
</li>
<?php endif; ?>


<li>
  <a href="#sidebar-crm" data-bs-toggle="collapse">
    <i data-feather="users"></i>
    <span> CRM / HR </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-crm">
    <ul class="nav-second-level">
      <li><a href="<?php echo e(route('admin.employees.index')); ?>"><i data-feather="user"></i> Employees</a></li>
      <li><a href="<?php echo e(route('admin.attendances.index')); ?>"><i data-feather="check-circle"></i> Attendance</a></li>
      <li><a href="<?php echo e(route('admin.leaves.index')); ?>"><i data-feather="calendar"></i> Leaves</a></li>
      <li><a href="<?php echo e(route('admin.salaries.index')); ?>"><i data-feather="dollar-sign"></i> Salaries</a></li>
      <li><a href="<?php echo e(route('admin.bonuses.index')); ?>"><i data-feather="gift"></i> Bonuses</a></li>
      <li><a href="<?php echo e(route('admin.salary_payments.index')); ?>"><i data-feather="credit-card"></i> Salary Payments</a></li>
    </ul>
  </div>
</li>



<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['coupon-list', 'coupon-create', 'coupon-edit', 'coupon-delete'])): ?>
<li>
  <a href="#sidebar-coupon" data-bs-toggle="collapse">
    <i data-feather="gift"></i> 
    <span> Coupons </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-coupon">
    <ul class="nav-second-level">
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('coupon-list')): ?>
      <li>
        <a href="<?php echo e(route('admin.coupons.index')); ?>">
          <i data-feather="list"></i> All Coupons
        </a>
      </li>
      <?php endif; ?>

      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('coupon-create')): ?>
      <li>
        <a href="<?php echo e(route('admin.coupons.create')); ?>">
          <i data-feather="plus-circle"></i> Add New
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </div>
</li>
<?php endif; ?>











<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('review-list')): ?>
<li>
  <a href="#sidebar-product-review" data-bs-toggle="collapse">
    <i data-feather="star"></i>
    <span> Reviews </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-product-review">
    <ul class="nav-second-level">
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('review-list')): ?>
      <li><a href="<?php echo e(route('reviews.pending')); ?>"><i data-feather="file-plus"></i> Pending Reviews (<?php echo e($pending_reviews); ?>)</a></li>
      <li><a href="<?php echo e(route('reviews.index')); ?>"><i data-feather="file-plus"></i> All Reviews</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('review-create')): ?>
      <li><a href="<?php echo e(route('reviews.pending')); ?>"><i data-feather="file-plus"></i> Create</a></li>
      <?php endif; ?>
    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['campaign-list', 'campaign-create'])): ?>
<li>
  <a href="#sidebar-landing-page" data-bs-toggle="collapse">
    <i data-feather="airplay"></i>
    <span> Landing Page </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-landing-page">
    <ul class="nav-second-level">
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('campaign-list')): ?>
      <li><a href="<?php echo e(route('campaign.index')); ?>"><i data-feather="file-plus"></i> Campaign</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('campaign-create')): ?>
      <li><a href="<?php echo e(route('campaign.create')); ?>"><i data-feather="file-plus"></i> Create</a></li>
      <?php endif; ?>
    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('fraud-check')): ?>
<li>
  <a href="<?php echo e(route('manualFraud.page')); ?>">
    <i data-feather="search"></i>
    <span>Manual Fraud Check</span>
  </a>
</li>
<?php endif; ?>




<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('sms-send')): ?>
<li>
  <a href="<?php echo e(route('admin.sms.custom.page')); ?>">
    <i data-feather="send"></i>
    <span>Send Custom SMS</span>
  </a>
</li>
<?php endif; ?>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['complaint-list', 'complaint-create', 'complaint-edit'])): ?>
<li class="<?php echo e(request()->routeIs('backEnd.complaints.*') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('backEnd.complaints.index')); ?>">
        <i data-feather="alert-circle"></i>
        <span> Complaints </span>
    </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('contact-list')): ?>
<li class="<?php echo e(request()->routeIs('admin.contact.messages*') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('admin.contact.messages')); ?>">
        <i data-feather="mail"></i>
        <span> Contact Messages </span>
    </a>
</li>
<?php endif; ?>
<li class="<?php echo e(request()->routeIs('admin.newsletter.subscribers*') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('admin.newsletter.subscribers')); ?>">
        <i data-feather="mail"></i>
        <span> Newsletter Subscribers </span>
    </a>
</li>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['fund-list', 'fund-create', 'fund-edit'])): ?>
<li>
  <a href="<?php echo e(route('admin.fund.index')); ?>">
    <i data-feather="briefcase"></i>
    <span> Fund / Account</span>
  </a>
</li>
<?php endif; ?>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['expense-list', 'expense-create', 'expense-edit'])): ?>
<li class="<?php echo e(request()->routeIs('admin.expenses.*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('admin.expenses.index')); ?>">
    <i data-feather="credit-card"></i>
    <span>Expenses</span>
  </a>
</li>
<?php endif; ?>






<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['user-list', 'role-list', 'permission-list'])): ?>
<li>
  <a href="#sidebar-users" data-bs-toggle="collapse">
    <i data-feather="user"></i>
    <span> Users </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-users">
    <ul class="nav-second-level">
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user-list')): ?>
      <li><a href="<?php echo e(route('users.index')); ?>"><i data-feather="file-plus"></i> User</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('role-list')): ?>
      <li><a href="<?php echo e(route('roles.index')); ?>"><i data-feather="file-plus"></i> Roles</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('permission-list')): ?>
      <li><a href="<?php echo e(route('permissions.index')); ?>"><i data-feather="file-plus"></i> Permissions</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['customer-list', 'customer-create', 'customer-edit'])): ?>
      <li><a href="<?php echo e(route('customers.index')); ?>"><i data-feather="file-plus"></i> Customers</a></li>
      <?php endif; ?>
    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['setting-list', 'social-list', 'contact-list'])): ?>
<li>
  <a href="#siebar-sitesetting" data-bs-toggle="collapse">
    <i data-feather="settings"></i>
    <span> Site Setting </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="siebar-sitesetting">
    <ul class="nav-second-level">
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('setting-list')): ?>
      <li><a href="<?php echo e(route('settings.index')); ?>"><i data-feather="file-plus"></i> General Setting</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('social-list')): ?>
      <li><a href="<?php echo e(route('socialmedias.index')); ?>"><i data-feather="file-plus"></i> Social Media</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('contact-list')): ?>
      <li><a href="<?php echo e(route('contact.index')); ?>"><i data-feather="file-plus"></i> Contact</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['page-list', 'page-create', 'page-edit'])): ?>
      <li><a href="<?php echo e(route('pages.index')); ?>"><i data-feather="file-plus"></i> Create Page</a></li>
      <?php endif; ?>
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['shipping-list', 'shipping-create', 'shipping-edit'])): ?>
      <li><a href="<?php echo e(route('shippingcharges.index')); ?>"><i data-feather="file-plus"></i> Shipping Charge</a></li>
      <?php endif; ?>
    </ul>
  </div>
</li>
<?php endif; ?>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('email-setting-list')): ?>
<li class="<?php echo e(request()->routeIs('email_setting*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('email_setting')); ?>">
    <i data-feather="mail"></i>
    <span>Email Settings</span>
  </a>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['fraud-setting-list', 'fraud-setting-edit'])): ?>
<li>
  <a href="#sidebar-fraud" data-bs-toggle="collapse">
    <i data-feather="shield"></i>
    <span> Fraud API Settings </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-fraud">
    <ul class="nav-second-level">

      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('fraud-setting-list')): ?>
      <li>
        <a href="<?php echo e(route('admin.fraud.index')); ?>">
          <i data-feather="key"></i> Manage Fraud API
        </a>
      </li>
      <?php endif; ?>

    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['setting-list', 'setting-edit'])): ?>
<li>
  <a href="<?php echo e(route('admin.order.restriction.setting.index')); ?>">
    <i data-feather="clock"></i>
    <span> Order Restriction</span>
  </a>
</li>
<?php endif; ?>






<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['api-manage'])): ?>
<li>
  <a href="#sidebar-api-integration" data-bs-toggle="collapse">
    <i data-feather="save"></i>
    <span> API Integration </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-api-integration">
    <ul class="nav-second-level">
      <li><a href="<?php echo e(route('paymentgeteway.manage')); ?>"><i data-feather="file-plus"></i> Payment Gateway</a></li>
      <li><a href="<?php echo e(route('smsgeteway.manage')); ?>"><i data-feather="file-plus"></i> SMS Gateway</a></li>
      <li><a href="<?php echo e(route('courierapi.manage')); ?>"><i data-feather="file-plus"></i> Courier API</a></li>
      <li><a href="<?php echo e(route('admin.facebook_capi.edit')); ?>"><i data-feather="facebook"></i> Facebook CAPI</a></li>
    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['api-manage'])): ?>
<li>
  <a href="<?php echo e(route('admin.cron.index')); ?>">
    <i data-feather="clock"></i>
    <span> Cron Job </span>
  </a>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['pixel-manage'])): ?>
<li>
  <a href="#sidebar-pixel-gtm" data-bs-toggle="collapse">
    <i data-feather="save"></i>
    <span> G. Pixel and GTM </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-pixel-gtm">
    <ul class="nav-second-level">
      <li><a href="<?php echo e(route('tagmanagers.index')); ?>"><i data-feather="file-plus"></i> Tag Manager</a></li>
      <li><a href="<?php echo e(route('pixels.index')); ?>"><i data-feather="file-plus"></i> Pixel Manage</a></li>
      <li><a href="<?php echo e(route('tiktok.pixels.index')); ?>"><i data-feather="film"></i> TikTok Pixel</a></li>
    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['pixel-manage'])): ?>
<li>
  <a href="#sidebar-ads-analytics" data-bs-toggle="collapse">
    <i data-feather="trending-up"></i>
    <span> Live Ads Result </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse <?php echo e(request()->routeIs('admin.ads_analytics.*') ? 'show' : ''); ?>" id="sidebar-ads-analytics">
    <ul class="nav-second-level">
      <li><a href="<?php echo e(route('admin.ads_analytics.dashboard')); ?>"><i data-feather="layout"></i> Overview</a></li>
      <li><a href="<?php echo e(route('admin.ads_analytics.facebook')); ?>"><i data-feather="facebook"></i> Facebook Ads</a></li>
      <li><a href="<?php echo e(route('admin.ads_analytics.google')); ?>"><i data-feather="globe"></i> Google Ads</a></li>
      <li><a href="<?php echo e(route('admin.ads_analytics.tiktok')); ?>"><i data-feather="video"></i> TikTok Ads</a></li>
    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['pixel-manage'])): ?>
<li class="<?php echo e(request()->routeIs('admin.facebook_page.*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('admin.facebook_page.settings')); ?>">
    <i data-feather="share-2"></i>
    <span> Facebook Page Post </span>
  </a>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['banner-list'])): ?>
<li class="<?php echo e(request()->routeIs('banners.index.*') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('banners.index')); ?>">
      <i data-feather="image"></i>
        <span> Banner & Sliders </span>
    </a>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['popup-list','popup-manage'])): ?>
<li class="<?php echo e(request()->routeIs('admin.popup.*') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('admin.popup.index')); ?>">
        <i data-feather="message-square"></i>
        <span> Popup Offer </span>
    </a>
</li>
<?php endif; ?>



<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['report-view','order-report','purchase-report','expense-report','stock-report','profit-loss-report'])): ?>
<li>
  <a href="#sidebar-report" data-bs-toggle="collapse">
    <i data-feather="pie-chart"></i>
    <span> Reports </span>
    <span class="menu-arrow"></span>
  </a>
  <div class="collapse" id="sidebar-report">
    <ul class="nav-second-level">
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['order-report','report-view'])): ?>
      <li>
        <a href="<?php echo e(route('admin.reports.orders')); ?>">
          <i data-feather="file-text"></i> Order Report
        </a>
      </li>
      <?php endif; ?>

      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['purchase-report','report-view'])): ?>
      <li>
        <a href="<?php echo e(route('admin.reports.purchases')); ?>">
          <i data-feather="shopping-bag"></i> Purchase Report
        </a>
      </li>
      <?php endif; ?>

      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['expense-report','report-view'])): ?>
      <li>
        <a href="<?php echo e(route('admin.reports.expenses')); ?>">
          <i data-feather="trending-down"></i> Expense Report
        </a>
      </li>
      <?php endif; ?>

      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['stock-report','report-view'])): ?>
      <li>
        <a href="<?php echo e(route('admin.reports.stock')); ?>">
          <i data-feather="archive"></i> Stock Report
        </a>
      </li>
      <?php endif; ?>

      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['profit-loss-report','report-view'])): ?>
      <li>
        <a href="<?php echo e(route('admin.reports.profit_loss')); ?>">
          <i data-feather="activity"></i> Profit & Loss
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </div>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('seo-manage')): ?> 
<li class="<?php echo e(request()->routeIs('admin.seo_settings.*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('admin.seo_settings.index')); ?>">
    <i data-feather="globe"></i>
    <span>SEO Settings</span>
  </a>
</li>
<?php endif; ?>

			  

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('sitemap-manage')): ?> 
<li class="<?php echo e(request()->routeIs('admin.sitemap.*') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('admin.sitemap.index')); ?>">
        <i data-feather="map"></i>
        <span> Sitemap Settings </span>
    </a>
</li>
<?php endif; ?>



			  <li>
  <a href="<?php echo e(route('admin.clear.cache')); ?>"
     onclick="return confirm('Are you sure you want to clear all cache?')">
    <i data-feather="refresh-cw"></i>
    <span>Clear Cache</span>
  </a>
</li>

<li>
  <a href="<?php echo e(route('error-log.index')); ?>">
    <i data-feather="file-text"></i>
    <span>Error Log</span>
  </a>
</li>

			  
            </ul>
          </div>
		  
		  
		  
          <!-- End Sidebar -->

          <div class="clearfix"></div>
        </div>
        <!-- Sidebar -left -->
      </div>
      <!-- Left Sidebar End -->

      <div class="content-page">
        <div class="content">
          <?php echo $__env->yieldContent('content'); ?>
        </div>
        <!-- content -->

        <!-- end Footer -->
      </div>
    </div>
    <!-- END wrapper -->

    <!-- Right Sidebar -->
    <div class="right-bar">
      <div data-simplebar class="h-100">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs nav-bordered nav-justified" role="tablist">
          <li class="nav-item">
            <a class="nav-link py-2" data-bs-toggle="tab" href="#chat-tab" role="tab">
              <i class="mdi mdi-message-text d-block font-22 my-1"></i>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-2" data-bs-toggle="tab" href="#tasks-tab" role="tab">
              <i class="mdi mdi-format-list-checkbox d-block font-22 my-1"></i>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-2 active" data-bs-toggle="tab" href="#settings-tab" role="tab">
              <i class="mdi mdi-cog-outline d-block font-22 my-1"></i>
            </a>
          </li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content pt-0">
          <div class="tab-pane" id="chat-tab" role="tabpanel">
            <form class="search-bar p-3">
              <div class="position-relative">
                <input type="text" class="form-control" placeholder="Search..." />
                <span class="mdi mdi-magnify"></span>
              </div>
            </form>
          </div>

          <div class="tab-pane" id="tasks-tab" role="tabpanel">
            <h6 class="fw-medium p-3 m-0 text-uppercase">Working Tasks</h6>
          </div>
          <div class="tab-pane active" id="settings-tab" role="tabpanel">
            <h6 class="fw-medium px-3 m-0 py-2 font-13 text-uppercase bg-light">
              <span class="d-block py-1">Theme Settings</span>
            </h6>

            <div class="p-3">
              <div class="alert alert-warning" role="alert"><strong>Customize </strong> the overall color scheme, sidebar menu, etc.</div>

              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Color Scheme</h6>
              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-color" value="light" id="light-mode-check" checked />
                <label class="form-check-label" for="light-mode-check">Light Mode</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-color" value="dark" id="dark-mode-check" />
                <label class="form-check-label" for="dark-mode-check">Dark Mode</label>
              </div>

              <!-- Width -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Width</h6>
              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-width" value="fluid" id="fluid-check" checked />
                <label class="form-check-label" for="fluid-check">Fluid</label>
              </div>
              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-width" value="boxed" id="boxed-check" />
                <label class="form-check-label" for="boxed-check">Boxed</label>
              </div>

              <!-- Menu positions -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Menus (Leftsidebar and Topbar) Positon</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="menu-position" value="fixed" id="fixed-check" checked />
                <label class="form-check-label" for="fixed-check">Fixed</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="menu-position" value="scrollable" id="scrollable-check" />
                <label class="form-check-label" for="scrollable-check">Scrollable</label>
              </div>

              <!-- Left Sidebar-->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Left Sidebar Color</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="light" id="light-check" />
                <label class="form-check-label" for="light-check">Light</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="dark" id="dark-check" checked />
                <label class="form-check-label" for="dark-check">Dark</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="brand" id="brand-check" />
                <label class="form-check-label" for="brand-check">Brand</label>
              </div>

              <div class="form-check form-switch mb-3">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="gradient" id="gradient-check" />
                <label class="form-check-label" for="gradient-check">Gradient</label>
              </div>

              <!-- size -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Left Sidebar Size</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-size" value="default" id="default-size-check" checked />
                <label class="form-check-label" for="default-size-check">Default</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-size" value="condensed" id="condensed-check" />
                <label class="form-check-label" for="condensed-check">Condensed <small>(Extra Small size)</small></label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-size" value="compact" id="compact-check" />
                <label class="form-check-label" for="compact-check">Compact <small>(Small size)</small></label>
              </div>

              <!-- User info -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Sidebar User Info</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="sidebar-user" value="fixed" id="sidebaruser-check" />
                <label class="form-check-label" for="sidebaruser-check">Enable</label>
              </div>

              <!-- Topbar -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Topbar</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="topbar-color" value="dark" id="darktopbar-check" checked />
                <label class="form-check-label" for="darktopbar-check">Dark</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="topbar-color" value="light" id="lighttopbar-check" />
                <label class="form-check-label" for="lighttopbar-check">Light</label>
              </div>

              <div class="d-grid mt-4">
                <button class="btn btn-primary" id="resetBtn">Reset to Default</button>
                <a href="https://1.envato.market/uboldadmin" class="btn btn-danger mt-3" target="_blank"><i class="mdi mdi-basket me-1"></i> Purchase Now</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- end slimscroll-menu-->
    </div>
    <!-- /Right-bar -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="<?php echo e(asset('public/backEnd/')); ?>/assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="<?php echo e(asset('public/backEnd/')); ?>/assets/js/app.min.js"></script>
    <!-- Feather Icons - Ensure library is loaded and initialized -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        // Force Feather Icons initialization after all scripts load
        (function() {
            'use strict';
            
            function initFeather() {
                if (typeof feather !== 'undefined' && typeof feather.replace === 'function') {
                    try {
                        feather.replace();
                        return true;
                    } catch(e) {
                        console.error('Feather replace error:', e);
                        return false;
                    }
                }
                return false;
            }
            
            // Wait for scripts to load
            function waitForFeather(callback, maxAttempts) {
                maxAttempts = maxAttempts || 50;
                var attempts = 0;
                
                var checkInterval = setInterval(function() {
                    attempts++;
                    if (typeof feather !== 'undefined' && typeof feather.replace === 'function') {
                        clearInterval(checkInterval);
                        if (callback) callback();
                    } else if (attempts >= maxAttempts) {
                        clearInterval(checkInterval);
                        console.warn('Feather icons library not found after', maxAttempts, 'attempts');
                    }
                }, 100);
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    waitForFeather(function() {
                        setTimeout(initFeather, 100);
                    });
                });
            } else {
                waitForFeather(function() {
                    setTimeout(initFeather, 100);
                });
            }
            
            // Also initialize on window load
            window.addEventListener('load', function() {
                waitForFeather(function() {
                    setTimeout(initFeather, 200);
                });
            });
            
            // jQuery ready handler
            if (typeof jQuery !== 'undefined') {
                jQuery(document).ready(function($) {
                    waitForFeather(function() {
                        setTimeout(initFeather, 150);
                    });
                    
                    // Reinitialize on menu collapse/expand
                    $(document).on('shown.bs.collapse hidden.bs.collapse', '[data-bs-toggle="collapse"]', function() {
                        setTimeout(initFeather, 100);
                    });
                    
                    // Watch sidebar for DOM changes
                    var sidebarEl = document.getElementById('sidebar-menu');
                    if (sidebarEl && typeof MutationObserver !== 'undefined') {
                        var observer = new MutationObserver(function() {
                            setTimeout(initFeather, 50);
                        });
                        observer.observe(sidebarEl, {
                            childList: true,
                            subtree: true
                        });
                    }
                });
            }
            
            // Fallback: Check periodically for unrendered icons
            setTimeout(function() {
                var checkInterval = setInterval(function() {
                    var unrendered = document.querySelectorAll('[data-feather]:not(svg)');
                    if (unrendered.length === 0) {
                        clearInterval(checkInterval);
                    } else {
                        initFeather();
                    }
                }, 500);
                
                // Stop checking after 10 seconds
                setTimeout(function() {
                    clearInterval(checkInterval);
                }, 10000);
            }, 1000);
        })();
    </script>
    <script src="<?php echo e(asset('public/backEnd/')); ?>/assets/js/toastr.min.js"></script>
    <script src="<?php echo e(asset('public/backEnd/')); ?>/assets/js/sweetalert.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php echo Toastr::message(); ?>

	<script>
<?php if(Session::has('success')): ?>
    toastr.success("<?php echo e(Session::get('success')); ?>");
<?php endif; ?>
<?php if(Session::has('error') && !Session::has('demo_mode_blocked')): ?>
    toastr.error("<?php echo e(Session::get('error')); ?>");
<?php endif; ?>
<?php if(Session::has('info')): ?>
    toastr.info("<?php echo e(Session::get('info')); ?>");
<?php endif; ?>
<?php if(Session::has('warning')): ?>
    toastr.warning("<?php echo e(Session::get('warning')); ?>");
<?php endif; ?>
<?php if(Session::has('demo_mode_blocked')): ?>
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: '<strong style="font-size:1.4rem;color:#2c3e50;">ডেমো মুড সক্রিয়</strong>',
            html: '<div style="text-align:center;padding:10px 0;"><div style="width:70px;height:70px;margin:0 auto 15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><i class="fe-eye" style="font-size:32px;color:#fff;"></i></div><p style="font-size:1rem;color:#5a6c7d;margin-bottom:8px;line-height:1.6;">অ্যাডমিন প্যানেল থেকে কোন ডাটা পরিবর্তন বা সংযোজন করা যাবে না।</p><p style="font-size:0.9rem;color:#95a5a6;margin:0;">কাস্টমার সাইটে অর্ডার, ট্রাকিং ও অন্যান্য সেবা স্বাভাবিকভাবে কাজ করবে।</p></div>',
            confirmButtonText: 'বুঝেছি',
            confirmButtonColor: '#667eea',
            customClass: { popup: 'demo-mode-popup', confirmButton: 'demo-mode-btn' },
            width: '420px',
            backdrop: 'rgba(0,0,0,0.5)',
        });
    } else {
        toastr.info("ডেমো মুড চালু আছে। অ্যাডমিন প্যানেল থেকে কোন পরিবর্তন করা যাবে না।");
    }
<?php endif; ?>
</script>
    <style>
    .demo-mode-popup { border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    .demo-mode-btn { padding: 10px 28px; font-weight: 600; border-radius: 8px; }
    </style>
    <script>
    function showDemoModeAlert(msg) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: '<strong style="font-size:1.4rem;color:#2c3e50;">ডেমো মুড সক্রিয়</strong>',
                html: '<div style="text-align:center;padding:10px 0;"><div style="width:70px;height:70px;margin:0 auto 15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><i class="fe-eye" style="font-size:32px;color:#fff;"></i></div><p style="font-size:1rem;color:#5a6c7d;margin-bottom:8px;line-height:1.6;">' + (msg || 'অ্যাডমিন প্যানেল থেকে কোন ডাটা পরিবর্তন বা সংযোজন করা যাবে না।') + '</p><p style="font-size:0.9rem;color:#95a5a6;margin:0;">কাস্টমার সাইটে অর্ডার, ট্রাকিং ও অন্যান্য সেবা স্বাভাবিকভাবে কাজ করবে।</p></div>',
                confirmButtonText: 'বুঝেছি',
                confirmButtonColor: '#667eea',
                customClass: { popup: 'demo-mode-popup', confirmButton: 'demo-mode-btn' },
                width: '420px',
                backdrop: 'rgba(0,0,0,0.5)',
            });
        }
    }
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (xhr.status === 403) {
            try {
                var data = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText || '{}');
                if (data.demo_mode && typeof Swal !== 'undefined') {
                    showDemoModeAlert(data.message || '');
                }
            } catch (e) {}
        }
    });
    </script>
    <script type="text/javascript">
      $(document).on('click', '.delete-confirm', function (event) {
        event.preventDefault();
        var form = $(this).closest("form");
        <?php if(isset($demoMode) && $demoMode): ?>
        showDemoModeAlert();
        return;
        <?php endif; ?>
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
          }).then(function(result) {
            if (result.isConfirmed) { form.submit(); }
          });
        } else {
          if (confirm('Are you sure you want to delete this record?')) { form.submit(); }
        }
      });
      $(document).on('click', '.change-confirm', function (event) {
        event.preventDefault();
        var form = $(this).closest("form");
        <?php if(isset($demoMode) && $demoMode): ?>
        showDemoModeAlert();
        return;
        <?php endif; ?>
        swal({
          title: `Are you sure you want to change this record?`,
          icon: "warning",
          buttons: true,
          dangerMode: true,
        }).then((willDelete) => {
          if (willDelete) {
            form.submit();
          }
        });
      });
      <?php if(isset($demoMode) && $demoMode): ?>
      $(document).on('submit', 'form', function(e) {
        var action = (this.action || '').toLowerCase();
        if (action.indexOf('logout') !== -1) return;
        var method = ($(this).find('input[name="_method"]').val() || $(this).attr('method') || 'get').toLowerCase();
        if (method === 'get') return;
        e.preventDefault();
        showDemoModeAlert();
        return false;
      });
      document.addEventListener('click', function(e) {
        var el = e.target.closest ? e.target.closest('a[href*="destroy"], a[href*="bulk_destroy"], a[href*="/delete"], a.order_delete') : null;
        if (el && el.href && el.href.indexOf('#') !== 0) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          showDemoModeAlert();
          return false;
        }
      }, true);
      <?php endif; ?>
    </script>
    <!--patho courier-->
    <script type="text/javascript">
        $(document).ready(function() {
            $('.pathaocity').change(function() {
                var id = $(this).val();
                if (id) {
                    $.ajax({
                        type: "GET",
                        url: "<?php echo e(url('admin/pathao-city')); ?>?city_id=" + id,
                        success: function(res) {
                            if (res && res.data && res.data.data) {
                                $(".pathaozone").empty();
                                $(".pathaozone").append('<option value="">Select..</option>');
                                $.each(res.data.data, function(index, zone) {
                                    $(".pathaozone").append('<option value="' + zone.zone_id + '">' + zone.zone_name + '</option>');
                                    $('.pathaozone').trigger("chosen:updated");
                                });
                            } else {
                                 $(".pathaoarea").empty();
                                $(".pathaozone").empty();
                            }
                        }
                    });
                } else {
                     $(".pathaoarea").empty();
                    $(".pathaozone").empty();
                }
            });
        });
    </script>
    <script type="text/javascript"> 
        $(document).ready(function() {
            $('.pathaozone').change(function() {
                var id = $(this).val();
                if (id) {
                    $.ajax({
                        type: "GET",
                        url: "<?php echo e(url('admin/pathao-zone')); ?>?zone_id=" + id,
                        success: function(res) {
                            if (res && res.data && res.data.data) {
                                $(".pathaoarea").empty();
                                $(".pathaoarea").append('<option value="">Select..</option>');
                                $.each(res.data.data, function(index, area) {
                                    $(".pathaoarea").append('<option value="' + area.area_id + '">' + area.area_name + '</option>');
                                    $('.pathaoarea').trigger("chosen:updated");
                                });
                            } else {
                                $(".pathaoarea").empty();
                            }
                        }
                    });
                } else {
                    $(".pathaoarea").empty();
                }
            });
        });
    </script>
    <?php echo $__env->yieldContent('script'); ?>
  </body>
</html>
<?php /**PATH /media/asraful/F/project/20-code/All Code/Ecommerce3/ecommerce3/resources/views/backEnd/layouts/master.blade.php ENDPATH**/ ?>