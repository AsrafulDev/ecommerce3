
<?php $__env->startSection('title', 'Reseller Withdrawals'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Card & Table Styles --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        background: #fff;
    }
    
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
        padding: 1rem;
        vertical-align: middle;
        font-size: 0.875rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:hover td { background-color: #f8fafc; }

    /* --- Status Badges --- */
    .badge-soft {
        padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .badge-approved { background: #dcfce7; color: #166534; }
    .badge-rejected { background: #fee2e2; color: #991b1b; }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    /* --- Method Badges --- */
    .method-badge {
        font-size: 0.75rem; padding: 4px 8px; border-radius: 4px;
        background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
        text-transform: capitalize;
    }

    /* --- Action Buttons --- */
    .btn-icon {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; transition: all 0.2s; border: none;
    }
    .btn-icon:hover { transform: translateY(-2px); }
    .btn-approve { background: #dcfce7; color: #166534; }
    .btn-reject { background: #fee2e2; color: #991b1b; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="dollar-sign" class="text-primary me-2"></i> Reseller Withdrawals
            </h4>
            <p class="text-muted small mb-0">Manage reseller payout requests and history.</p>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4"><i data-feather="check-circle" class="me-2" style="width:16px;"></i> <?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><i data-feather="alert-circle" class="me-2" style="width:16px;"></i> <?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <div class="card card-modern">
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Reseller Details</th>
                        <th width="15%">Amount</th>
                        <th width="10%">Method</th>
                        <th width="20%">Account Info</th>
                        <th width="15%">Request Date</th>
                        <th width="10%">Status</th>
                        <th width="5%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="text-muted"><?php echo e($loop->iteration); ?></td>
                            
                            
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark"><?php echo e($row->user->name ?? 'Unknown'); ?></span>
                                    <span class="small text-muted"><?php echo e($row->user->shop_name ?? ''); ?></span>
                                </div>
                            </td>

                            
                            <td>
                                <span class="fw-bold text-dark fs-6">৳<?php echo e(number_format($row->amount, 2)); ?></span>
                            </td>

                            
                            <td>
                                <span class="method-badge">
                                    <?php if($row->payout_method == 'bank'): ?> <i class="fas fa-university me-1"></i>
                                    <?php elseif(in_array($row->payout_method, ['bkash', 'nagad', 'rocket'])): ?> <i class="fas fa-mobile-alt me-1"></i>
                                    <?php else: ?> <i class="fas fa-money-bill me-1"></i> <?php endif; ?>
                                    <?php echo e(ucfirst($row->payout_method)); ?>

                                </span>
                            </td>

                            
                            <td>
                                <div class="small">
                                    <?php if($row->account_name): ?>
                                        <div class="fw-medium text-dark"><?php echo e($row->account_name); ?></div>
                                    <?php endif; ?>
                                    <?php if($row->account_number): ?>
                                        <div class="text-muted font-monospace"><?php echo e($row->account_number); ?></div>
                                    <?php endif; ?>
                                    <?php if($row->note): ?>
                                        <div class="text-muted fst-italic mt-1" style="font-size: 11px;">"<?php echo e(Str::limit($row->note, 20)); ?>"</div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            
                            <td class="text-muted small">
                                <div><?php echo e($row->created_at->format('d M, Y')); ?></div>
                                <div><?php echo e($row->created_at->format('h:i A')); ?></div>
                            </td>

                            
                            <td>
                                <?php if($row->status === 'approved'): ?>
                                    <span class="badge-soft badge-approved"><span class="status-dot"></span> Approved</span>
                                    <?php if($row->processed_at): ?>
                                        <div class="small text-muted mt-1" style="font-size: 10px;"><?php echo e($row->processed_at->format('d M, Y')); ?></div>
                                    <?php endif; ?>
                                <?php elseif($row->status === 'rejected'): ?>
                                    <span class="badge-soft badge-rejected"><span class="status-dot"></span> Rejected</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-pending"><span class="status-dot"></span> Pending</span>
                                <?php endif; ?>
                            </td>

                            
                            <td class="text-end">
                                <?php if($row->status === 'pending'): ?>
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button" class="btn-icon btn-approve" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo e($row->id); ?>" title="Approve">
                                            <i data-feather="check" style="width:14px;"></i>
                                        </button>
                                        <button type="button" class="btn-icon btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo e($row->id); ?>" title="Reject">
                                            <i data-feather="x" style="width:14px;"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted small">-</div>
                                <?php endif; ?>

                                
                                <div class="modal fade" id="approveModal<?php echo e($row->id); ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg">
                                            <div class="modal-header border-bottom-0 pb-0">
                                                <h5 class="modal-title fw-bold text-success">Approve Withdrawal</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="<?php echo e(route('admin.reseller.withdrawals.approve', $row->id)); ?>" method="POST">
                                                <?php echo csrf_field(); ?>
                                                <div class="modal-body text-start">
                                                    <div class="alert alert-soft-success border-0 mb-3">
                                                        <i data-feather="check-circle" class="me-1" style="width:14px;"></i>
                                                        Balance will be deducted from admin fund.
                                                    </div>
                                                    <div class="mb-3 p-3 bg-light rounded border">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span class="text-muted small">Amount:</span>
                                                            <span class="fw-bold">৳<?php echo e(number_format($row->amount, 2)); ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span class="text-muted small">To:</span>
                                                            <span class="fw-bold"><?php echo e($row->user->name); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-muted">Admin Note (Optional)</label>
                                                        <textarea name="admin_note" class="form-control" rows="2" placeholder="Transaction ID or remarks..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top-0 pt-0">
                                                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success btn-sm px-4">Confirm Approve</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                
                                <div class="modal fade" id="rejectModal<?php echo e($row->id); ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg">
                                            <div class="modal-header border-bottom-0 pb-0">
                                                <h5 class="modal-title fw-bold text-danger">Reject Withdrawal</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="<?php echo e(route('admin.reseller.withdrawals.reject', $row->id)); ?>" method="POST">
                                                <?php echo csrf_field(); ?>
                                                <div class="modal-body text-start">
                                                    <div class="alert alert-soft-warning border-0 mb-3">
                                                        <i data-feather="alert-triangle" class="me-1" style="width:14px;"></i>
                                                        Amount will be refunded to reseller's wallet.
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-muted">Rejection Reason <span class="text-danger">*</span></label>
                                                        <textarea name="admin_note" class="form-control" rows="3" required placeholder="e.g. Invalid bank details..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top-0 pt-0">
                                                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger btn-sm px-4">Confirm Reject</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        
        <div class="p-4 border-top d-flex justify-content-between align-items-center bg-white rounded-bottom">
            <small class="text-muted">
                Showing <strong><?php echo e($data->firstItem()); ?></strong> to <strong><?php echo e($data->lastItem()); ?></strong> of <strong><?php echo e($data->total()); ?></strong> requests
            </small>
            <div>
                <?php echo e($data->links('pagination::bootstrap-4')); ?>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php $__env->stopPush(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/backEnd/reseller/withdrawals/index.blade.php ENDPATH**/ ?>