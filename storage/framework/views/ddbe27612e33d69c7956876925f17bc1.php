 

<?php $__env->startSection('title', $seo->meta_title ?? 'Home'); ?>

<?php $__env->startPush('seo'); ?>
 
<meta name="app-url" content="<?php echo e(url('/')); ?>" />
<meta name="robots" content="index, follow" />

<meta name="description" content="<?php echo e($seo->meta_description ?? ''); ?>" />
<meta name="keywords" content="<?php echo e($seo->meta_tags ?? ''); ?>" />

<!-- Open Graph data -->
<meta property="og:title" content="<?php echo e($seo->meta_title ?? ''); ?>" />
<meta property="og:type" content="website" />
<meta property="og:url" content="<?php echo e(url()->current()); ?>" />
<meta property="og:image" content="<?php echo e(asset($generalsetting->og_baner ?? 'public/logo.png')); ?>" />
<meta property="og:description" content="<?php echo e($seo->meta_description ?? ''); ?>" />

<?php if(!empty($seo->search_console_verification)): ?>
<meta name="google-site-verification" content="<?php echo e($seo->search_console_verification); ?>">
<?php endif; ?>
<?php $__env->stopPush(); ?> 

<?php $__env->startPush('css'); ?>
<link rel="stylesheet" href="<?php echo e(asset('public/frontEnd/css/owl.carousel.min.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('public/frontEnd/css/owl.theme.default.min.css')); ?>" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" rel="stylesheet" />
<?php $__env->stopPush(); ?> 

<?php $__env->startSection('content'); ?>
<section class="slider-section">
    <div class="container">
        <div class="row">
            
            <div class="col-sm-12">
                <div class="home-slider-container">
                    <div class="main_slider owl-carousel">
                        <?php $__currentLoopData = $sliders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="slider-item">
                                <img src="<?php echo e(asset($value->image)); ?>" alt="" />
                            </div>
                            <!-- slider item -->
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- slider end -->


<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <div class="timer_inner">
                            <div class="">
                                <span class="section-title-name"> Top Categories </span>
                            </div>
                        </div>
                    </h3>
                </div>
            </div>

            
            
            <div class="col-sm-12">
                <div class="category-sliger owl-carousel">
                    <?php $__currentLoopData = $menucategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div>
                            <div class="text-center ">
                                <a href="<?php echo e(route('category', $value->slug)); ?>">
                                    <img class="" src="<?php echo e(asset($value->image)); ?>" alt="" style="border: 2px solid #3c7d17; border-radius: 50%; width: 100%; height: auto;" />
                                </a>
                            </div>
                            <div class="text-center">
                                <a href="<?php echo e(route('category', $value->slug)); ?>">
                                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo e($value->name); ?>

                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

        </div>
    </div>
</section>

<?php
    $hotDealEndDate = $generalsetting->hot_deal_end_date.'T23:59:59';
    $flashSaleEndDate = $generalsetting->flash_sale_end_date.'T23:59:59';
    $isHotDealActive = $hotDealEndDate && \Carbon\Carbon::parse($hotDealEndDate)->isFuture(); 
    $isFlashSaleActive = $flashSaleEndDate && \Carbon\Carbon::parse($flashSaleEndDate)->isFuture(); 
?>

<!--//Flash sales-->
<?php if($isFlashSaleActive): ?>
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <div class="timer_inner">
                            <div class="">
                                <span class="section-title-name">Flash Sales </span>
                            </div>

                            <div class="">
                                <div class="offer_timer" id="flash_sale_timer"></div>
                            </div>
                        </div>
                    </h3>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="flash_sale_slider owl-carousel">
                    <?php $__currentLoopData = $flas_sales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="product_item wist_item">
                            <div class="product_item_inner">
                                <?php if($value->old_price): ?>
                                <div class="sale-badge">
                                    <div class="sale-badge-inner">
                                        <div class="sale-badge-box">
                                            <span class="sale-badge-text">
                                                <p>
                                                    <?php 
                                                        $discount = (((($value->old_price)-($value->new_price))*100) / ($value->old_price)); 
                                                    ?> 
                                                    <?php echo e(number_format($discount, 0)); ?>%
                                                </p>
                                                ছাড়
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="pro_img">
                                    <a href="<?php echo e(route('product', $value->slug)); ?>">
                                        <img src="<?php echo e(asset($value->image ? $value->image->image : '')); ?>"
                                            alt="<?php echo e($value->name); ?>" />
                                    </a>
                                    <?php if(!is_null($value->stock) && $value->stock < 1): ?>
                                        <div class="stock-out-overlay">STOCK OUT</div>
                                    <?php endif; ?>
                                </div>
                                <div class="pro_des">
                                    <div class="pro_name">
                                        <a href="<?php echo e(route('product', $value->slug)); ?>"><?php echo e(Str::limit($value->name, 80)); ?></a>
                                    </div>
                                    
                                    <span style="background-color:#FFBCA5" class="px-3 py-1 rounded-pill">Sold <?php echo e($value->sold??0); ?></span>
                                  
                                    <div class="pro_price">
                                        <p>
                                            <?php if($value->old_price): ?>
                                                <del>৳ <?php echo e($value->old_price); ?></del>
                                            <?php endif; ?>

                                            ৳ <?php echo e($value->new_price); ?> 
                                        </p>
                                    </div>
                                </div>
                            </div>

                            
                            <?php if(!$value->prosizes->isEmpty() || !$value->procolors->isEmpty() || (!is_null($value->stock) && $value->stock < 1)): ?>
                                
                                <div class="pro_btn">
                                    <a href="<?php echo e(route('product', $value->slug)); ?>" class="order-btn-link">
                                        অর্ডার করুন
                                    </a>
                                    <a href="<?php echo e(route('product', $value->slug)); ?>" class="cart-icon-link">
                                        <i class="fa-solid fa-cart-shopping"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                
                                <div class="pro_btn">
                                    <form action="<?php echo e(route('cart.store')); ?>" method="POST" class="ajax-cart-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                        <input type="hidden" name="qty" value="1" />
                                        <input type="hidden" name="order_now" value="1">
                                        <button type="submit" class="order-btn">অর্ডার করুন</button>
                                    </form>

                                    <form action="<?php echo e(route('cart.store')); ?>" method="POST" class="ajax-cart-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                        <input type="hidden" name="qty" value="1" />
                                        <button type="submit" class="cart-icon-btn">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!--//hot deals-->
<?php if($isHotDealActive): ?>
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <div class="timer_inner">
                            <div class="">
                                <span class="section-title-name">Hot Deal </span>
                            </div>

                            <div class="">
                                <div class="offer_timer" id="simple_timer"></div>
                            </div>
                        </div>
                    </h3>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="product_slider owl-carousel">
                    <?php $__currentLoopData = $hotdeal_top; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="product_item wist_item">
                            <div class="product_item_inner">
                                <?php if($value->old_price): ?>
                                <div class="sale-badge">
                                    <div class="sale-badge-inner">
                                        <div class="sale-badge-box">
                                            <span class="sale-badge-text">
                                                <p>
                                                    <?php 
                                                        $discount=(((($value->old_price)-($value->new_price))*100) / ($value->old_price)) 
                                                    ?> 
                                                    <?php echo e(number_format($discount, 0)); ?>%
                                                </p>
                                                ছাড়
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="pro_img">
                                    <a href="<?php echo e(route('product', $value->slug)); ?>">
                                        <img src="<?php echo e(asset($value->image ? $value->image->image : '')); ?>"
                                            alt="<?php echo e($value->name); ?>" />
                                    </a>
                                    <?php if(!is_null($value->stock) && $value->stock < 1): ?>
                                        <div class="stock-out-overlay">STOCK OUT</div>
                                    <?php endif; ?>
                                </div>
                                <div class="pro_des">
                                    <div class="pro_name">
                                        <a href="<?php echo e(route('product', $value->slug)); ?>"><?php echo e(Str::limit($value->name, 80)); ?></a>
                                    </div>
                                    <div class="pro_price">
                                        <p>
                                            <?php if($value->old_price): ?>
                                                <del>৳ <?php echo e($value->old_price); ?></del>
                                            <?php endif; ?>

                                            ৳ <?php echo e($value->new_price); ?> 
                                        </p>
                                    </div>
                                </div>
                            </div>

                            
                            <?php if(!$value->prosizes->isEmpty() || !$value->procolors->isEmpty() || (!is_null($value->stock) && $value->stock < 1)): ?>
                                <div class="pro_btn">
                                    <a href="<?php echo e(route('product', $value->slug)); ?>" class="order-btn-link">
                                        অর্ডার করুন
                                    </a>
                                    <a href="<?php echo e(route('product', $value->slug)); ?>" class="cart-icon-link">
                                        <i class="fa-solid fa-cart-shopping"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="pro_btn">
                                    <form action="<?php echo e(route('cart.store')); ?>" method="POST" class="ajax-cart-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                        <input type="hidden" name="qty" value="1" />
                                        <input type="hidden" name="order_now" value="1">
                                        <button type="submit" class="order-btn">অর্ডার করুন</button>
                                    </form>

                                    <form action="<?php echo e(route('cart.store')); ?>" method="POST" class="ajax-cart-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                        <input type="hidden" name="qty" value="1" />
                                        <button type="submit" class="cart-icon-btn">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>


<?php if($generalsetting->show_all_products): ?>
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <div class="timer_inner">
                            <div class="">
                                <span class="section-title-name">All Products</span>
                            </div>
                        </div>
                    </h3>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="category-product main_product_inner">
                    <?php $__currentLoopData = $all_products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="product_item wist_item">
                        <div class="product_item_inner">
                             <?php if($value->old_price): ?>
                            <div class="sale-badge">
                                <div class="sale-badge-inner">
                                    <div class="sale-badge-box">
                                        <span class="sale-badge-text">
                                           <p>
                                                <?php 
                                                    $discount=(((($value->old_price)-($value->new_price))*100) / ($value->old_price)) 
                                                ?> 
                                                <?php echo e(number_format($discount,0)); ?>%
                                           </p>
                                            ছাড়
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="pro_img">
                                <a href="<?php echo e(route('product',$value->slug)); ?>">
                                    <img src="<?php echo e(asset($value->image ? $value->image->image : '')); ?>" alt="<?php echo e($value->name); ?>" />
                                </a>
                                <?php if(!is_null($value->stock) && $value->stock < 1): ?>
                                    <div class="stock-out-overlay">STOCK OUT</div>
                                <?php endif; ?>
                            </div>
                            <div class="pro_des">
                                <div class="pro_name">
                                    <a href="<?php echo e(route('product',$value->slug)); ?>"><?php echo e(Str::limit($value->name,80)); ?></a>
                                </div>
                                <div class="pro_price">
                                    <p>
                                        <del>৳ <?php echo e($value->old_price); ?></del>
                                        ৳ <?php echo e($value->new_price); ?> <?php if($value->old_price): ?> <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        
                        <?php if(!$value->prosizes->isEmpty() || !$value->procolors->isEmpty() || (!is_null($value->stock) && $value->stock < 1)): ?>
                            <div class="pro_btn">
                                <a href="<?php echo e(route('product',$value->slug)); ?>" class="order-btn-link">
                                    অর্ডার করুন
                                </a>
                                <a href="<?php echo e(route('product',$value->slug)); ?>" class="cart-icon-link">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="pro_btn">
                                <form action="<?php echo e(route('cart.store')); ?>" method="POST" class="ajax-cart-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                    <input type="hidden" name="qty" value="1" />
                                    <input type="hidden" name="order_now" value="1">
                                    <button type="submit" class="order-btn">অর্ডার করুন</button>
                                </form>

                                <form action="<?php echo e(route('cart.store')); ?>" method="POST" class="ajax-cart-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="cart-icon-btn">
                                        <i class="fa-solid fa-cart-shopping"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

        </div>
    </div>
</section>
<?php endif; ?>


<section>
    <div class="container">
        <div class="row">
            <?php $__currentLoopData = $sliderbottomads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bottomAds): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-12">
                <a href="<?php echo e($bottomAds->link); ?>?sold=show">
                    <img class="img-fluid w-100" src="<?php echo e($bottomAds->image); ?>"/>
                </a>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>



<?php if($generalsetting->show_category_wise_products): ?>
    <?php $__currentLoopData = $homeproducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $homecat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <section class="homeproduct">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="sec_title">
                            <h3 class="section-title-header">
                                <span class="section-title-name"><?php echo e($homecat->name); ?></span>
                            </h3>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="product_sliders">
                            <?php $__currentLoopData = $homecat->products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                               <div class="product_item wist_item">
                                <div class="product_item_inner">
                                    <?php if($value->old_price): ?>
                                    <div class="sale-badge">
                                        <div class="sale-badge-inner">
                                            <div class="sale-badge-box">
                                                <span class="sale-badge-text">
                                                    <p>
                                                        <?php 
                                                            $discount=(((($value->old_price)-($value->new_price))*100) / ($value->old_price)) 
                                                        ?> 
                                                        <?php echo e(number_format($discount, 0)); ?>%
                                                    </p>
                                                    ছাড়
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="pro_img">
                                        <a href="<?php echo e(route('product', $value->slug)); ?>">
                                            <img src="<?php echo e(asset($value->image ? $value->image->image : '')); ?>"
                                                alt="<?php echo e($value->name); ?>" />
                                        </a>
                                        <?php if(!is_null($value->stock) && $value->stock < 1): ?>
                                            <div class="stock-out-overlay">STOCK OUT</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pro_des">
                                        <div class="pro_name">
                                            <a href="<?php echo e(route('product', $value->slug)); ?>"><?php echo e(Str::limit($value->name, 80)); ?></a>
                                        </div>
                                        <div class="pro_price">
                                            <p>
                                                <?php if($value->old_price): ?>
                                                    <del>৳ <?php echo e($value->old_price); ?></del>
                                                <?php endif; ?>
                                                ৳ <?php echo e($value->new_price); ?> 
                                            </p>
                                        </div>
                                    </div>
                                </div>
    
                                
                                <?php if(!$value->prosizes->isEmpty() || !$value->procolors->isEmpty() || (!is_null($value->stock) && $value->stock < 1)): ?>
                                    <div class="pro_btn">
                                        <a href="<?php echo e(route('product', $value->slug)); ?>" class="order-btn-link">
                                            অর্ডার করুন
                                        </a>
                                        <a href="<?php echo e(route('product', $value->slug)); ?>" class="cart-icon-link">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="pro_btn">
                                        <form action="<?php echo e(route('cart.store')); ?>" method="POST" class="ajax-cart-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                            <input type="hidden" name="qty" value="1" />
                                            <input type="hidden" name="order_now" value="1">
                                            <button type="submit" class="order-btn">অর্ডার করুন</button>
                                        </form>

                                        <form action="<?php echo e(route('cart.store')); ?>" method="POST" class="ajax-cart-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo e($value->id); ?>" />
                                            <input type="hidden" name="qty" value="1" />
                                            <button type="submit" class="cart-icon-btn">
                                                <i class="fa-solid fa-cart-shopping"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="show_more_btn">
                            <a href="<?php echo e(route('category', $homecat->slug)); ?>" class="view_more_btn">View More</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>




<section>
    <div class="container">
        <div class="row">
          <?php $__currentLoopData = $campaognads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaignAds): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-12">
                <a href="<?php echo e($campaignAds->link); ?>?sold=show">
                    <img class="img-fluid w-100" src="<?php echo e($campaignAds->image); ?>"/>
                </a>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>



<?php if(isset($brands) && $brands->count() > 0): ?>
<section class="homeproduct brand-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <span class="section-title-name">Brands</span>
                    </h3>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="row brand-grid">

                    <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                            <a href="<?php echo e(route('brand.products', $brand->slug)); ?>"
                               class="brand-item text-center">

                                <div class="brand-img">
                                    <img src="<?php echo e(asset($brand->image)); ?>"
                                         alt="<?php echo e($brand->name); ?>"
                                         class="img-fluid"
                                         loading="lazy">
                                </div>

                                <div class="brand-name">
                                    <?php echo e($brand->name); ?>

                                </div>

                            </a>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>



<?php if(isset($vendors) && $vendors->count() > 0): ?>
<section class="homeproduct vendor-shops-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <span class="section-title-name">Our Featured Shops</span>
                    </h3>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="row vendor-shop-grid">
                    <?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                        <a href="<?php echo e(route('vendor.shop', $vendor->slug)); ?>" class="vendor-shop-item">
                            
                            <div class="shop-banner-bg" style="background-image: url('<?php echo e($vendor->banner ? asset($vendor->banner) : asset('public/frontEnd/images/default-banner.jpg')); ?>');">
                            </div>
                            
                            
                            <div class="shop-content-wrapper">
                                <div class="shop-logo-container">
                                    <div class="shop-logo-circle">
                                        <?php if($vendor->logo): ?>
                                            <img src="<?php echo e(asset($vendor->logo)); ?>" alt="<?php echo e($vendor->shop_name); ?>" />
                                        <?php else: ?>
                                            <div class="shop-logo-initial">
                                                <?php echo e(strtoupper(substr($vendor->shop_name, 0, 1))); ?>

                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($vendor->verification_status == 'approved'): ?>
                                    <div class="shop-verified-badge">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="shop-details">
                                    <h4 class="shop-title"><?php echo e($vendor->shop_name); ?></h4>
                                    
                                    
                                    <div class="shop-rating-stars">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= floor($vendor->average_rating)): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif($i - 0.5 <= $vendor->average_rating): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="shop-review-text">(<?php echo e($vendor->total_reviews); ?> reviews)</span>
                                    </div>
                                </div>
                                
                                
                                <div class="shop-visit-btn">
                                    <span class="visit-btn-icon"><i class="fas fa-arrow-right"></i></span>
                                    <span class="visit-btn-text">VISIT STORE</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>


<?php if(isset($blogs) && $blogs->count() > 0): ?>
<section class="homeproduct blog-home-section">
    <div class="container">

        
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <span class="section-title-name">Latest Blogs</span>
                        <a href="<?php echo e(route('blogs')); ?>" class="view_more_btn">
                            View All
                        </a>
                    </h3>
                </div>
            </div>
        </div>

        
        <div class="row">

            <?php $__currentLoopData = $blogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $blog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-lg-4 col-md-6 mb-4">

                <div class="blog-home-card">

                    
                    <div class="blog-home-img">
                        <a href="<?php echo e(route('blog.details', $blog->slug)); ?>">
                            <?php if($blog->image): ?>
                        <img 
                            src="<?php echo e(url('public/'.$blog->image)); ?>"
                            alt="<?php echo e($blog->title); ?>"
                            loading="lazy"
                            width="100%"
                            height="220"
                        >
                    <?php else: ?>
                        <img 
                            src="<?php echo e(url('public/no-image.png')); ?>"
                            alt="No Image"
                            loading="lazy"
                            width="100%"
                            height="220"
                        >
                    <?php endif; ?>
                        </a>
                    </div>

                    
                    <div class="blog-home-content">

                        <div class="blog-home-meta">
                           <?php echo e($blog->created_at->format('d M Y')); ?>

                            |<?php echo e($blog->views); ?>

                        </div>

                        <h5 class="blog-home-title">
                            <a href="<?php echo e(route('blog.details', $blog->slug)); ?>">
                                <?php echo e(Str::limit($blog->title, 55)); ?>

                            </a>
                        </h5>

                        <p>
                            <?php echo e(Str::limit($blog->short_description, 110)); ?>

                        </p>

                        <a href="<?php echo e(route('blog.details', $blog->slug)); ?>"
                           class="read-more-link">
                            Read More →
                        </a>

                    </div>

                </div>

            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        </div>

    </div>
</section>
<?php endif; ?>











<style>
/* ===== CLEAR BRAND LOGO SECTION ===== */
.brand-section {
    background: #ffffff;
}

/* brand card */
.brand-section .brand-item {
    display: block;
    background: #ffffff;
    border-radius: 10px;
    padding: 20px 15px;
    text-decoration: none;
    border: 1px solid #eaeaea;
    transition: all 0.3s ease;
}

.brand-section .brand-item:hover {
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transform: translateY(-4px);
}

/* logo container */
.brand-section .brand-img {
    height: 95px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff; /* white bg for clarity */
}

/* LOGO IMAGE – FULL CLEAR */
.brand-section .brand-img img {
    max-height: 80px;
    max-width: 100%;
    object-fit: contain;

    /* IMPORTANT FOR CLEAR LOGO */
    filter: none !important;
    opacity: 1 !important;
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

/* brand name */
.brand-section .brand-name {
    margin-top: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #000;
    text-align: center;
}

/* mobile */
@media (max-width: 576px) {
    .brand-section .brand-img {
        height: 75px;
    }
    .brand-section .brand-img img {
        max-height: 55px;
    }
}

</style>


















<style>
.blog-home-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #eee;
    height: 100%;
    transition: all .3s ease;
}

.blog-home-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.08);
}

.blog-home-img img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}

.blog-home-content {
    padding: 16px;
}

.blog-home-meta {
    font-size: 13px;
    color: #777;
    margin-bottom: 6px;
}

.blog-home-title a {
    font-size: 17px;
    font-weight: 600;
    color: #222;
    text-decoration: none;
}

.blog-home-title a:hover {
    color: #0d6efd;
}

.read-more-link {
    display: inline-block;
    margin-top: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #0d6efd;
    text-decoration: none;
}

.read-more-link:hover {
    text-decoration: underline;
}

/* ===== VENDOR SHOPS SECTION ===== */
.vendor-shops-section {
    background: #ffffff;
}

.vendor-shop-item {
    display: block;
    position: relative;
    background: #ffffff;
    border-radius: 10px;
    overflow: hidden;
    text-decoration: none;
    border: 1px solid #eaeaea;
    transition: all 0.3s ease;
    height: 100%;
}

.vendor-shop-item:hover {
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transform: translateY(-4px);
    text-decoration: none;
}

/* Background Banner */
.shop-banner-bg {
    position: relative;
    width: 100%;
    height: 100px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

/* Shop Content Wrapper */
.shop-content-wrapper {
    position: relative;
    padding: 15px;
    text-align: center;
    padding-top: 50px;
}

/* Logo Container */
.shop-logo-container {
    position: relative;
    margin-top: -50px;
    margin-bottom: 12px;
    display: flex;
    justify-content: center;
}

.shop-logo-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #ffffff;
    border: 4px solid #ffffff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.shop-logo-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.shop-logo-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Verified Badge */
.shop-verified-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 24px;
    height: 24px;
    background: #0d6efd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.shop-verified-badge i {
    color: #ffffff;
    font-size: 12px;
}

/* Shop Details */
.shop-details {
    margin-bottom: 12px;
}

.shop-title {
    font-size: 15px;
    font-weight: 600;
    color: #222;
    margin: 0 0 4px 0;
    line-height: 1.3;
}

.shop-type {
    font-size: 11px;
    color: #666;
    margin: 0 0 8px 0;
}

.shop-rating-stars {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    margin-bottom: 0;
}

.shop-rating-stars i {
    font-size: 11px;
    color: #ffc107;
}

.shop-rating-stars .far.fa-star {
    color: #ddd;
}

.shop-review-text {
    font-size: 10px;
    color: #777;
    margin-left: 4px;
}

/* Visit Store Button */
.shop-visit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 8px 12px;
    background: #f0f0f0;
    border-radius: 20px;
    color: #333;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-top: 8px;
}

.vendor-shop-item:hover .shop-visit-btn {
    background: #0d6efd;
    color: #ffffff;
}

.visit-btn-icon {
    width: 24px;
    height: 24px;
    background: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.visit-btn-icon i {
    font-size: 10px;
    color: #333;
    transition: all 0.3s ease;
}

.vendor-shop-item:hover .visit-btn-icon {
    background: rgba(255,255,255,0.2);
}

.vendor-shop-item:hover .visit-btn-icon i {
    color: #ffffff;
}

/* Responsive */
@media (max-width: 768px) {
    .shop-banner-bg {
        height: 80px;
    }
    
    .shop-logo-circle {
        width: 70px;
        height: 70px;
    }
    
    .shop-content-wrapper {
        padding-top: 40px;
    }
    
    .shop-title {
        font-size: 14px;
    }
    
    .shop-type {
        font-size: 10px;
    }
}

@media (max-width: 576px) {
    .shop-banner-bg {
        height: 70px;
    }
    
    .shop-logo-circle {
        width: 60px;
        height: 60px;
    }
    
    .shop-content-wrapper {
        padding: 12px;
        padding-top: 35px;
    }
    
    .shop-logo-initial {
        font-size: 28px;
    }
}
</style>



<?php if($reviews->count()>0): ?>
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h5 class="text-center text-light py-2" style="background-color:<?php echo e($generalsetting->secodery_color); ?>">
                        সম্মানীত কাষ্টমারদের পজিটিভ রিভিউ
                    </h5>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="customer-review owl-carousel">
                    <?php $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="border rounded">
                        <img class="img-fluid w-100" src="<?php echo e(asset($review->image)); ?>" />
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            
        </div>
    </div>
</section>
<?php endif; ?>

<section>
    <div class="container">
        <div class="row">
            <?php $__currentLoopData = $footertopads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $footerAds): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-12">
                <a href="<?php echo e($footerAds->link); ?>?sold=show">
                    <img class="img-fluid w-100" src="<?php echo e($footerAds->image); ?>"/>
                </a>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>

<?php $__env->stopSection(); ?> 

<?php $__env->startPush('script'); ?>
<script src="<?php echo e(asset('public/frontEnd/js/owl.carousel.min.js')); ?>"></script>
<script src="<?php echo e(asset('public/frontEnd/js/jquery.syotimer.min.js')); ?>"></script>

<script>
    $(document).ready(function() {
        $(".main_slider").owlCarousel({
            items: 1,
            loop: true,
            dots: false,
            autoplay: true,
            nav: true,
            autoplayHoverPause: true,
            margin: 0,
            mouseDrag: true,
            smartSpeed: 8000,
            autoplayTimeout: 3000,
            animateOut: "fadeOutRight",
            animateIn: "slideInLeft",

            navText: ["<i class='fa-solid fa-angle-left'></i>",
                "<i class='fa-solid fa-angle-right'></i>"
            ],
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".hotdeals-slider").owlCarousel({
            margin: 15,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 3,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 6,
                    nav: true,
                    loop: false,
                },
            },
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".category-slider").owlCarousel({
            margin: 15,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 5,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 8,
                    nav: true,
                    loop: false,
                },
            },
        });

        $(".product_slider").owlCarousel({
            margin: 15,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                },
                600: {
                    items: 5,
                    nav: false,
                },
                1000: {
                    items: 6,
                    nav: false,
                },
            },
        });
        
        $(".flash_sale_slider").owlCarousel({
            margin: 8,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                },
                600: {
                    items: 6,
                    nav: false,
                },
                1000: {
                    items: 7,
                    nav: false,
                },
            },
        });
        
        $(".category-sliger").owlCarousel({
            margin: 8,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 3,
                    nav: false,
                },
                600: {
                    items: 6,
                    nav: false,
                },
                1000: {
                    items: 7,
                    nav: false,
                },
            },
        });
        $(".customer-review").owlCarousel({
            margin: 8,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 5,
                    nav: false,
                },
            },
        });
    });
</script>

<script>
    $("#simple_timer").syotimer({
        date: new Date("<?php echo e($generalsetting->hot_deal_end_date); ?>T23:59:59"),
        layout: "hms",
        doubleNumbers: false,
        effectType: "opacity",
        periodUnit: "d",
        periodic: false
    });
   $("#flash_sale_timer").syotimer({
        date: new Date("<?php echo e($generalsetting->flash_sale_end_date); ?>T23:59:59"),
        layout: "hms",
        doubleNumbers: false,
        effectType: "opacity",
        periodUnit: "d",
        periodic: false,
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/frontEnd/layouts/pages/index.blade.php ENDPATH**/ ?>