
<?php $__env->startSection('title', 'Manage Users'); ?>

<?php $__env->startSection('css'); ?>
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<style>
    /* --- Modern Card --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #fff;
    }

    /* --- Table Styling --- */
    .table-modern th {
        background-color: #fff;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 1rem;
        border-bottom: 2px solid #f1f5f9;
        white-space: nowrap;
    }
    .table-modern td {
        vertical-align: middle;
        padding: 1rem;
        font-size: 0.875rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:hover td { background-color: #f8fafc; }

    /* --- User Avatar --- */
    .user-avatar-circle {
        width: 35px; height: 35px;
        background-color: #e0e7ff; color: #4338ca;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 14px;
        margin-right: 12px;
    }

    /* --- Status Badges --- */
    .badge-soft {
        padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .badge-active { background: #dcfce7; color: #166534; }
    .badge-inactive { background: #fee2e2; color: #991b1b; }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    /* --- Action Buttons --- */
    .btn-icon {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; transition: all 0.2s; border: none; background: #f1f5f9; color: #64748b;
    }
    .btn-icon:hover { transform: translateY(-2px); }
    .btn-edit:hover { background: #e0e7ff; color: #4338ca; }
    .btn-delete:hover { background: #fee2e2; color: #ef4444; }
    .btn-toggle-on:hover { background: #fee2e2; color: #ef4444; }
    .btn-toggle-off:hover { background: #dcfce7; color: #16a34a; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="users" class="text-primary me-2"></i> Manage Users
            </h4>
            <p class="text-muted small mb-0">Overview of all registered users and their roles.</p>
        </div>
        <a href="<?php echo e(route('users.create')); ?>" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
            <i data-feather="plus" class="me-1" style="width: 16px;"></i> Create User
        </a>
    </div>

    <div class="card card-modern">
        <div class="card-body">
            
            <table id="datatable-buttons" class="table table-modern w-100 dt-responsive nowrap">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="30%">User Name</th>
                        <th width="30%">Email Address</th>
                        <th width="15%">Status</th>
                        <th width="20%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        
                        <?php
                            // Role Check Logic
                            $isAdminUser  = method_exists($value, 'hasRole') ? $value->hasRole('Admin') : false;
                            $isLoginAdmin = auth()->check() && method_exists(auth()->user(), 'hasRole')
                                            ? auth()->user()->hasRole('Admin')
                                            : false;
                        ?>

                        
                        <?php if($isAdminUser && !$isLoginAdmin): ?>
                            <?php continue; ?>
                        <?php endif; ?>

                        <tr>
                            <td class="text-muted"><?php echo e($loop->iteration); ?></td>
                            
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar-circle">
                                        <?php echo e(substr($value->name, 0, 1)); ?>

                                    </div>
                                    <span class="fw-bold text-dark"><?php echo e($value->name); ?></span>
                                </div>
                            </td>

                            
                            <td><?php echo e($value->email); ?></td>

                            
                            <td>
                                <?php if($value->status == 1): ?>
                                    <span class="badge-soft badge-active"><span class="status-dot"></span> Active</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-inactive"><span class="status-dot"></span> Inactive</span>
                                <?php endif; ?>
                            </td>

                            
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    
                                    
                                    <form method="post" action="<?php echo e($value->status == 1 ? route('users.inactive') : route('users.active')); ?>" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" value="<?php echo e($value->id); ?>" name="hidden_id">
                                        <button type="submit" class="btn-icon <?php echo e($value->status == 1 ? 'btn-toggle-on' : 'btn-toggle-off'); ?> change-confirm" 
                                                title="<?php echo e($value->status == 1 ? 'Deactivate' : 'Activate'); ?>">
                                            <i data-feather="<?php echo e($value->status == 1 ? 'thumbs-down' : 'thumbs-up'); ?>" style="width:14px;"></i>
                                        </button>
                                    </form>

                                    
                                    <a href="<?php echo e(route('users.edit', $value->id)); ?>" class="btn-icon btn-edit" title="Edit">
                                        <i data-feather="edit-2" style="width:14px;"></i>
                                    </a>

                                    
                                    <form method="post" action="<?php echo e(route('users.destroy')); ?>" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" value="<?php echo e($value->id); ?>" name="hidden_id">
                                        <button type="submit" class="btn-icon btn-delete delete-confirm" title="Delete">
                                            <i data-feather="trash-2" style="width:14px;"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="<?php echo e(asset('/public/backEnd/')); ?>/assets/js/pages/datatables.init.js"></script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/backEnd/users/index.blade.php ENDPATH**/ ?>