
<?php $__env->startSection('title','Page'); ?>
<?php $__env->startSection('content'); ?>

<section class="comn_sec">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="cmn_menu">
                    <ul>
                        <?php $__currentLoopData = $cmnmenu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <a href="<?php echo e(route('page',$value->slug)); ?>"><?php echo e($value->name); ?></a>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <a href="<?php echo e(route('contact')); ?>">Contact Us</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="createpage-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="page-content">
                    <div class="page-title mb-2">
                        <h5><?php echo e($page->title); ?></h5>
                    </div>
                    <div class="page-description">
                        <?php echo $page->description; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/frontEnd/layouts/pages/page.blade.php ENDPATH**/ ?>