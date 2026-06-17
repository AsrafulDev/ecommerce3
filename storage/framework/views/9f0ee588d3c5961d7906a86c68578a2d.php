
<?php $__env->startSection('title', $blog->title); ?>

<?php $__env->startPush('css'); ?>
<style>
.blog-details img {
    max-width: 100%;
    border-radius: 6px;
}

.blog-meta {
    font-size: 14px;
    color: #777;
    margin-bottom: 15px;
}

.sidebar-blog {
    padding: 10px;
}

.sidebar-blog img {
    width: 80px;
    height: 65px;
    object-fit: cover;
    border-radius: 6px;
}

.sidebar-blog-title {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
    color: #222;
    text-decoration: none;
}

.sidebar-blog-title:hover {
    color: #0d6efd;
}

.sidebar-blog-meta {
    font-size: 12px;
    color: #777;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<section class="blog-details product-section">
    <div class="container">

        
        <div class="sorting-section mb-4">
            <div class="row">
                <div class="col-sm-12">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="<?php echo e(route('home')); ?>">Home</a>
                        <span>/</span>
                        <a href="<?php echo e(route('blogs')); ?>">Blog</a>
                        <span>/</span>
                        <strong><?php echo e(Str::limit($blog->title, 40)); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            
            <div class="col-md-8">

                <h3 class="mb-2"><?php echo e($blog->title); ?></h3>

                <div class="blog-meta">
                <?php echo e($blog->created_at->format('d M Y')); ?>

                    &nbsp; | &nbsp;
                    👁 <?php echo e($blog->views); ?> Views
                </div>

                
                <?php if($blog->image): ?>
                    <img src="<?php echo e(url('public/'.$blog->image)); ?>"
                         class="img-fluid mb-4"
                         alt="<?php echo e($blog->title); ?>">
                <?php else: ?>
                    <img src="<?php echo e(url('public/no-image.png')); ?>"
                         class="img-fluid mb-4"
                         alt="No Image">
                <?php endif; ?>

                
                <div class="blog-content">
                    <?php echo $blog->description; ?>

                </div>

            </div>

            
            <div class="col-md-4">

                <div class="card">
                    <div class="card-header">
                        Latest Blogs
                    </div>

                    <ul class="list-group list-group-flush">

                        <?php $__currentLoopData = $recentBlogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rblog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="list-group-item sidebar-blog">

                            <div class="d-flex">

                                
                                <div class="me-2">
                                    <?php if($rblog->image): ?>
                                        <img src="<?php echo e(url('public/'.$rblog->image)); ?>"
                                             alt="<?php echo e($rblog->title); ?>">
                                    <?php else: ?>
                                        <img src="<?php echo e(url('public/no-image.png')); ?>"
                                             alt="No Image">
                                    <?php endif; ?>
                                </div>

                                
                                <div>
                                    <a href="<?php echo e(route('blog.details', $rblog->slug)); ?>"
                                       class="sidebar-blog-title">
                                        <?php echo e(Str::limit($rblog->title, 45)); ?>

                                    </a>

                                    <div class="sidebar-blog-meta mt-1">
                                       <?php echo e($rblog->created_at->format('d M Y')); ?>

                                       |
                                      👁 <?php echo e($rblog->views); ?> Views
                                    </div>
                                </div>

                            </div>

                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    </ul>
                </div>

            </div>

        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/frontEnd/layouts/pages/blog/details.blade.php ENDPATH**/ ?>