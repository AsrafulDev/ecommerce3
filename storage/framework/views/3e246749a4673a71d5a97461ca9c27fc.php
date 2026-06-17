
<?php $__env->startSection('title','Blog'); ?>

<?php $__env->startPush('css'); ?>
<style>
.blog-card {
    border: 1px solid #eee;
    border-radius: 10px;
    overflow: hidden;
    transition: all .3s ease;
    background: #fff;
    height: 100%;
}

.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
}

.blog-img img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}

.blog-content {
    padding: 15px;
}

.blog-title a {
    font-size: 18px;
    font-weight: 600;
    color: #222;
    text-decoration: none;
}

.blog-title a:hover {
    color: #0d6efd;
}

.blog-meta {
    font-size: 13px;
    color: #777;
    margin-bottom: 10px;
}

.read-more-btn {
    border-radius: 20px;
    padding: 6px 22px;
    font-size: 14px;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<section class="blog-section product-section">
    <div class="container">

        
        <div class="sorting-section mb-4">
            <div class="row">
                <div class="col-sm-12">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="<?php echo e(route('home')); ?>">Home</a>
                        <span>/</span>
                        <strong>Blog</strong>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row">
            <?php $__currentLoopData = $blogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $blog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-4 mb-4 d-flex">

                <div class="blog-card w-100">

                    
                    <div class="blog-img">
                        <a href="<?php echo e(route('blog.details',$blog->slug)); ?>">
                            <?php if($blog->image): ?>
                                <img src="<?php echo e(url('public/'.$blog->image)); ?>" alt="<?php echo e($blog->title); ?>">
                            <?php else: ?>
                                <img src="<?php echo e(url('public/no-image.png')); ?>" alt="No Image">
                            <?php endif; ?>
                        </a>
                    </div>

                    
                    <div class="blog-content">

                        <div class="blog-title mb-1">
                            <a href="<?php echo e(route('blog.details',$blog->slug)); ?>">
                                <?php echo e(Str::limit($blog->title,50)); ?>

                            </a>
                        </div>

                        <div class="blog-meta">
                            <?php echo e($blog->created_at->format('d M Y')); ?> |
                            👁 <?php echo e($blog->views); ?>

                        </div>

                        <p>
                            <?php echo e(Str::limit($blog->short_description,120)); ?>

                        </p>

                        <div class="text-center mt-3">
                            <a href="<?php echo e(route('blog.details', $blog->slug)); ?>"
                               class="btn btn-sm btn-outline-primary read-more-btn">
                                Read More
                            </a>
                        </div>

                    </div>
                </div>

            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="row">
            <div class="col-sm-12">
                <div class="custom_paginate text-center">
                    <?php echo e($blogs->links('pagination::bootstrap-4')); ?>

                </div>
            </div>
        </div>

    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/frontEnd/layouts/pages/blog/index.blade.php ENDPATH**/ ?>