

<?php $__env->startSection('title', 'Fund Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">💰 Fund Management</h3>
        <a href="<?php echo e(route('admin.fund.logs')); ?>" class="btn btn-info btn-sm">
            <i data-feather="file-text" class="me-1" style="width:14px;height:14px;"></i> 
            View Edit/Delete Logs & Reports
        </a>
    </div>

    
    <div class="row mb-4">

       
<div class="col-md-4 mb-3">
    <div class="card" style="background:#198754; color:#fff;">
        <div class="card-body">
            <h5 class="mb-1" style="color:#fff !important;">Available Balance</h5>
            <h2 class="mb-0" style="color:#fff !important;"><?php echo e(number_format($balance, 2)); ?> ৳</h2>
            <small style="color:#fff !important; opacity:0.85;">In – Out এর পার্থক্য</small>
        </div>
    </div>
</div>


<div class="col-md-4 mb-3">
    <div class="card" style="background:#0d6efd; color:#fff;">
        <div class="card-body">
            <h5 class="mb-1" style="color:#fff !important;">This Year (<?php echo e($currentYear); ?>)</h5>
            <h3 class="mb-0" style="color:#fff !important;"><?php echo e(number_format($yearlyAdded, 2)); ?> ৳</h3>
            <small style="color:#fff !important; opacity:0.85;">এই বছরে মোট ফান্ড যোগ হয়েছে</small>
        </div>
    </div>
</div>


<div class="col-md-4 mb-3">
    <div class="card" style="background:#222275; color:#fff;">
        <div class="card-body">
            <h5 class="mb-1" style="color:#fff !important;">This Month (<?php echo e(\Carbon\Carbon::create()->month($currentMonth)->format('F')); ?>)</h5>
            <h3 class="mb-0" style="color:#fff !important;"><?php echo e(number_format($monthlyAdded, 2)); ?> ৳</h3>
            <small style="color:#fff !important; opacity:0.85;">এই মাসে মোট ফান্ড যোগ হয়েছে</small>
        </div>
    </div>
</div>


    </div>

    
    <div class="row mb-4">
        
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <strong>➕ Add Fund</strong>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.fund.add')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-2">
                            <label class="form-label">Amount (৳)</label>
                            <input type="number" name="amount" class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   placeholder="Amount" step="0.01" min="1" required>
                            <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <small class="text-danger"><?php echo e($message); ?></small>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Note</label>
                            <input type="text" name="note" class="form-control" placeholder="Note (optional)">
                        </div>
                        <button class="btn btn-primary w-100">Add Fund</button>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <strong>➖ Withdraw</strong>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.fund.withdraw')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-2">
                            <label class="form-label">Amount (৳)</label>
                            <input type="number" name="amount" class="form-control"
                                   placeholder="Amount" step="0.01" min="1" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Note</label>
                            <input type="text" name="note" class="form-control" placeholder="Note (optional)">
                        </div>
                        <button class="btn btn-danger w-100">Withdraw</button>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <strong>📤 Export Report</strong>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.fund.export')); ?>" method="GET" id="fundExportForm">
                        <div class="mb-2">
                            <label class="form-label">Filter Type</label>
                            <select name="filter" id="filter_type" class="form-select">
                                <option value="year" selected>Yearly</option>
                                <option value="month">Monthly</option>
                                <option value="custom">Custom Date</option>
                            </select>
                        </div>

                        
                        <div class="mb-2" id="year_field">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" class="form-control"
                                   value="<?php echo e($currentYear); ?>" min="2000" max="2100">
                        </div>

                        
                        <div class="mb-2 d-none" id="month_field">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select">
                                <?php for($m=1;$m<=12;$m++): ?>
                                    <option value="<?php echo e($m); ?>" <?php echo e($m == $currentMonth ? 'selected' : ''); ?>>
                                        <?php echo e(\Carbon\Carbon::create()->month($m)->format('F')); ?>

                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        
                        <div class="mb-2 d-none" id="custom_date_fields">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control mb-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control">
                        </div>

                        <button class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fe-download"></i> Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong>🧾 Fund History</strong>
            <div>
                <a href="<?php echo e(route('admin.fund.logs')); ?>" class="btn btn-sm btn-outline-info">
                    <i data-feather="file-text" class="me-1" style="width:14px;height:14px;"></i> View Logs / Reports
                </a>
                <small class="text-muted ms-2">সর্বশেষ ট্রান্স্যাকশন লিস্ট</small>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Source</th>
                        <th>Amount</th>
                        <th>Note</th>
                        <th>Date & Time</th>
                        <?php
                            // Check if current user is Admin (Super Admin or has Admin role)
                            $isAdmin = false;
                            $user = Auth::guard('admin')->user();
                            if ($user) {
                                if ($user->id == 1) {
                                    $isAdmin = true;
                                } else {
                                    $spatieRoles = $user->getRoleNames()->map(function($role) {
                                        return strtolower($role);
                                    })->toArray();
                                    $isAdmin = in_array('admin', $spatieRoles);
                                }
                            }
                        ?>
                        <?php if($isAdmin): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($t->id); ?></td>
                            <td>
                                <?php if($t->direction == 'in'): ?>
                                    <span class="badge bg-success">IN (+)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">OUT (-)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo e($t->source ?? '-'); ?>

                                <?php if($t->hasBeenEdited()): ?>
                                    <span class="badge bg-warning ms-1" title="This transaction has been edited">
                                        <i class="fe-edit" style="width:12px;height:12px;"></i> Edited
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e(number_format($t->amount, 2)); ?> ৳</td>
                            <td>
                                <?php echo e($t->note ?? '-'); ?>

                                <?php if($t->updated_by): ?>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fe-edit-2" style="width:12px;height:12px;"></i> Last updated: <?php echo e($t->updated_at ? $t->updated_at->format('d M Y, h:i A') : 'N/A'); ?>

                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($t->created_at->format('d M Y, h:i A')); ?></td>
                            <?php if($isAdmin): ?>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo e(route('admin.fund.edit', $t->id)); ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fe-edit"></i>
                                    </a>
                                    <form method="POST" action="<?php echo e(route('admin.fund.destroy', $t->id)); ?>" class="d-inline delete-form">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger delete-confirm" title="Delete" onclick="return confirm('Are you sure you want to delete this transaction?');">
                                            <i class="fe-trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="<?php echo e($isAdmin ? 7 : 6); ?>" class="text-center text-muted">
                                No fund transactions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            
            <div class="mt-2">
                <?php echo e($transactions->links('pagination::bootstrap-4')); ?>

            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
    // Export form filter UI show/hide
    (function () {
        const filterSelect      = document.getElementById('filter_type');
        const yearField         = document.getElementById('year_field');
        const monthField        = document.getElementById('month_field');
        const customDateFields  = document.getElementById('custom_date_fields');

        function updateFilterFields() {
            const val = filterSelect.value;

            yearField.classList.add('d-none');
            monthField.classList.add('d-none');
            customDateFields.classList.add('d-none');

            if (val === 'year') {
                yearField.classList.remove('d-none');
            } else if (val === 'month') {
                yearField.classList.remove('d-none');
                monthField.classList.remove('d-none');
            } else if (val === 'custom') {
                customDateFields.classList.remove('d-none');
            }
        }

        filterSelect.addEventListener('change', updateFilterFields);
        updateFilterFields(); // on page load
    })();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /media/asraful/F/project/20-code/All Code/Ecommerce3/ecommerce3/resources/views/backEnd/fund/index.blade.php ENDPATH**/ ?>