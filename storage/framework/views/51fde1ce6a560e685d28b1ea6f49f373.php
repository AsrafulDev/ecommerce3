
<?php $__env->startSection('title', 'Reseller Deposits'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="wallet" class="text-primary me-2"></i> রিসেলার ডিপোজিট
            </h4>
            <p class="text-muted small mb-0">পেন্ডিং ডিপোজিট এডমিন পেইড মার্ক করলে ওয়ালেটে যোগ হবে</p>
        </div>
    </div>

    
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3">
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo e(route('admin.reseller-deposits.index')); ?>" class="btn btn-sm <?php echo e(!request('status') ? 'btn-primary' : 'btn-outline-secondary'); ?>">
                    সব
                </a>
                <a href="<?php echo e(route('admin.reseller-deposits.index', ['status' => 'pending'])); ?>" class="btn btn-sm <?php echo e(request('status') === 'pending' ? 'btn-warning' : 'btn-outline-warning'); ?>">
                    পেন্ডিং
                </a>
                <a href="<?php echo e(route('admin.reseller-deposits.index', ['status' => 'completed'])); ?>" class="btn btn-sm <?php echo e(request('status') === 'completed' ? 'btn-success' : 'btn-outline-success'); ?>">
                    পেইড
                </a>
                <a href="<?php echo e(route('admin.reseller-deposits.index', ['status' => 'failed'])); ?>" class="btn btn-sm <?php echo e(request('status') === 'failed' ? 'btn-danger' : 'btn-outline-danger'); ?>">
                    ব্যর্থ
                </a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>রিসেলার</th>
                        <th>পরিমাণ</th>
                        <th>স্ট্যাটাস</th>
                        <th>তারিখ</th>
                        <th>ট্রানজেকশন ID</th>
                        <th class="text-end">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $deposits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($d->id); ?></td>
                        <td>
                            <div>
                                <strong><?php echo e($d->user->name ?? 'N/A'); ?></strong>
                                <?php if($d->user->shop_name): ?>
                                <br><small class="text-muted"><?php echo e($d->user->shop_name); ?></small>
                                <?php endif; ?>
                                <br><small class="text-muted"><?php echo e($d->user->email ?? ''); ?></small>
                            </div>
                        </td>
                        <td><strong class="text-success">৳<?php echo e(number_format($d->amount, 2)); ?></strong></td>
                        <td>
                            <?php if($d->status === 'pending'): ?>
                            <span class="badge bg-warning">পেন্ডিং</span>
                            <?php elseif($d->status === 'completed'): ?>
                            <span class="badge bg-success">পেইড</span>
                            <?php else: ?>
                            <span class="badge bg-danger">ব্যর্থ</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($d->created_at->format('d M Y, h:i A')); ?></td>
                        <td><small class="text-muted"><?php echo e($d->transaction_id ?? '-'); ?></small></td>
                        <td class="text-end">
                            <?php if($d->status === 'pending'): ?>
                            <form action="<?php echo e(route('admin.reseller-deposits.mark-paid', $d->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('পেমেন্ট নিশ্চিত করেছেন? ওয়ালেটে টাকা যোগ হবে।');">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="mdi mdi-check-circle"></i> পেইড মার্ক করুন
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">কোনো ডিপোজিট নেই</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($deposits->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($deposits->withQueryString()->links()); ?>

        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/backEnd/reseller/deposits.blade.php ENDPATH**/ ?>