
<?php $__env->startSection('title', $brand->name); ?>

<?php $__env->startPush('css'); ?>
<link rel="stylesheet" href="<?php echo e(asset('public/frontEnd/css/jquery-ui.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<section class="product-section">
    <div class="container">

        
        <div class="sorting-section">
            <div class="row">
                <div class="col-sm-6">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="<?php echo e(route('home')); ?>">Home</a>
                        <span>/</span>
                        <strong><?php echo e($brand->name); ?></strong>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="showing-data">
                                <span>
                                    Showing <?php echo e($products->firstItem()); ?>-<?php echo e($products->lastItem()); ?>

                                    of <?php echo e($products->total()); ?> Results
                                </span>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="page-sort">
                                <form class="sort-form">
                                    <select name="sort" class="form-control form-select sort">
                                        <option value="1" <?php if(request('sort')==1): echo 'selected'; endif; ?>>Product: Latest</option>
                                        <option value="2" <?php if(request('sort')==2): echo 'selected'; endif; ?>>Product: Oldest</option>
                                        <option value="3" <?php if(request('sort')==3): echo 'selected'; endif; ?>>Price: High To Low</option>
                                        <option value="4" <?php if(request('sort')==4): echo 'selected'; endif; ?>>Price: Low To High</option>
                                        <option value="5" <?php if(request('sort')==5): echo 'selected'; endif; ?>>Name: A-Z</option>
                                        <option value="6" <?php if(request('sort')==6): echo 'selected'; endif; ?>>Name: Z-A</option>
                                    </select>

                                    <input type="hidden" name="min_price" value="<?php echo e(request('min_price')); ?>">
                                    <input type="hidden" name="max_price" value="<?php echo e(request('max_price')); ?>">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row">
            <div class="col-sm-12">
                <div class="category-product main_product_inner">

                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="product_item wist_item wow zoomIn"
                         data-wow-duration="1.5s"
                         data-wow-delay="0.<?php echo e($key); ?>s">

                        <div class="product_item_inner">

                            
                            <?php if($value->old_price): ?>
                            <div class="sale-badge">
                                <div class="sale-badge-inner">
                                    <div class="sale-badge-box">
                                        <span class="sale-badge-text">
                                            <?php
                                                $discount = (($value->old_price - $value->new_price) * 100) / $value->old_price;
                                            ?>
                                            <p><?php echo e(number_format($discount,0)); ?>%</p>
                                            ছাড়
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            
                            <div class="pro_img">
                                <a href="<?php echo e(route('product', $value->slug)); ?>">
                                    <img src="<?php echo e(asset($value->image ? $value->image->image : '')); ?>"
                                         alt="<?php echo e($value->name); ?>">
                                </a>
                            </div>

                            
                            <div class="pro_des">
                                <div class="pro_name">
                                    <a href="<?php echo e(route('product', $value->slug)); ?>">
                                        <?php echo e(Str::limit($value->name, 35)); ?>

                                    </a>
                                </div>
                            </div>
                        </div>

                        
                        <?php
                            $avg = $value->reviews->avg('ratting') ?? 0;
                            $full = floor($avg);
                        ?>
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i class="<?php echo e($i <= $full ? 'fas' : 'far'); ?> fa-star"></i>
                        <?php endfor; ?>

                        
                        <div class="pro_price">
                            <?php if($value->old_price): ?>
                                <del>৳ <?php echo e($value->old_price); ?></del>
                            <?php endif; ?>
                            ৳ <?php echo e($value->new_price); ?>

                        </div>

                        
                        <div class="pro_btn">

                            <?php if(!$value->prosizes->isEmpty() || !$value->procolors->isEmpty()): ?>
                                
                                <a href="<?php echo e(route('product', $value->slug)); ?>"
                                   class="order-btn-link">
                                    অর্ডার করুন
                                </a>

                                <a href="<?php echo e(route('product', $value->slug)); ?>"
                                   class="cart-icon-link">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </a>
                            <?php else: ?>
                                
                                
                                <form action="<?php echo e(route('cart.store')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo e($value->id); ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <input type="hidden" name="order_now" value="1">

                                    <button type="submit" class="order-btn">
                                        অর্ডার করুন
                                    </button>
                                </form>

                                
                                <form action="<?php echo e(route('cart.store')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo e($value->id); ?>">
                                    <input type="hidden" name="qty" value="1">

                                    <button type="submit" class="cart-icon-btn">
                                        <i class="fa-solid fa-cart-shopping"></i>
                                    </button>
                                </form>
                            <?php endif; ?>

                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </div>
            </div>
        </div>

        
        <div class="row">
            <div class="col-sm-12">
                <div class="custom_paginate">
                    <?php echo e($products->links('pagination::bootstrap-4')); ?>

                </div>
            </div>
        </div>

    </div>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
<script>
    $('.sort').change(function () {
        $('.sort-form').submit();
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/frontEnd/layouts/pages/brand.blade.php ENDPATH**/ ?>