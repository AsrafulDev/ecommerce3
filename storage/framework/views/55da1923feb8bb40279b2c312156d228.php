<?php $__env->startSection('title',$order_status->name.' Order'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="<?php echo e(route('admin.order.create')); ?>" class="btn btn-danger rounded-pill"><i class="fe-shopping-cart"></i> POS Create</a>
                </div>
                <h4 class="page-title"><?php echo e($order_status->name); ?> Order (<?php echo e($order_status->orders_count); ?>)</h4>
            </div>
        </div>
    </div>        
    <div class="row order_page">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-8">
                            <ul class="action2-btn list-unstyled d-flex gap-2 p-0 m-0">
                                <li><a data-bs-toggle="modal" data-bs-target="#asignUser" class="btn rounded-pill btn-success"><i class="fe-plus"></i> Assign</a></li>
                                <li><a data-bs-toggle="modal" data-bs-target="#changeStatus" class="btn rounded-pill btn-primary"><i class="fe-plus"></i> Status</a></li>
                                <li><a href="<?php echo e(route('admin.order.bulk_destroy')); ?>" class="btn rounded-pill btn-danger order_delete"><i class="fe-plus"></i> Delete</a></li>
                                <li><a href="<?php echo e(route('admin.order.order_print')); ?>" class="btn rounded-pill btn-info multi_order_print"><i class="fe-printer"></i> Print</a></li>
                                <li><a href="<?php echo e(route('admin.order.order_print')); ?>" class="btn rounded-pill btn-secondary multi_label_print"><i class="fe-tag"></i> Label</a></li>
                                <?php if($steadfast): ?>
                                    <li><a href="<?php echo e(route('admin.bulk_courier', 'steadfast')); ?>?status=5" class="btn rounded-pill btn-info multi_order_courier"><i class="fe-truck"></i> Steadfast</a></li>
                                <?php endif; ?>
                                <?php if($pathao_info): ?>
                                    <li><a data-bs-toggle="modal" data-bs-target="#pathao" class="btn rounded-pill btn-warning"><i class="fe-truck"></i> Pathao</a></li>
                                <?php endif; ?>
                                <?php if(isset($redx_info) && $redx_info): ?>
                                    <li><a href="<?php echo e(route('admin.bulk_courier', 'redx')); ?>?status=5" class="btn rounded-pill btn-warning multi_order_courier" style="background-color: #f59e0b; border-color: #f59e0b;"><i class="fe-truck"></i> RedX</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="col-sm-4">
                            <form class="custom_form" method="GET">
                                <div class="form-group d-flex">
                                    <input type="text" name="keyword" placeholder="Search" class="form-control me-2">
                                    <button class="btn rounded-pill btn-info">Search</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="datatable-buttons" class="table table-striped w-100">
                            <thead>
                                <tr>
                                    <th style="width:2%;">
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input type="checkbox" class="form-check-input checkall" value="">
                                            </label>
                                        </div>
                                    </th>
                                    <th style="width:2%;">SL</th>
                                    <th style="width:8%;">Action</th>
                                    <th style="width:8%;">Invoice</th>
                                    <th style="width:10%;">Date</th>
                                    <th style="width:10%;">Name</th>
                                    <th style="width:8%;">Type</th>
                                    <th style="width:10%;">Vendor</th>
                                    <th style="width:8%;">Reseller</th>
                                    <th style="width:8%;">IP</th>
                                    <th style="width:10%;">Order Note</th>
                                    <th style="width:10%;">Admin Note</th>
                                    <th style="width:10%;">Amount</th>
                                    <th style="width:10%;">Status</th>
                                    <th style="width:12%;">Courier</th>
                                    <th>Track</th>
                                    <th>Fraud Check</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $show_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><input type="checkbox" class="checkbox form-check-input" value="<?php echo e($value->id); ?>"></td>
                                        <td><?php echo e($loop->iteration); ?></td>
                                        <td>
                                            <div class="button-list custom-btn-list">
                                                <a href="<?php echo e(route('admin.order.invoice', ['invoice_id' => $value->invoice_id])); ?>" title="Invoice"><i class="fe-eye"></i></a>
                                                <a href="<?php echo e(route('admin.order.process', ['invoice_id' => $value->invoice_id])); ?>" title="Process"><i class="fe-settings"></i></a>
                                                <a href="<?php echo e(route('admin.order.edit', ['invoice_id' => $value->invoice_id])); ?>" title="Edit"><i class="fe-edit"></i></a>
                                                <form method="post" action="<?php echo e(route('admin.order.destroy')); ?>" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" value="<?php echo e($value->id); ?>" name="id">
                                                    <button type="submit" title="Delete" class="delete-confirm btn btn-link p-0" style="color:inherit;"><i class="fe-trash-2"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                        <td><?php echo e($value->invoice_id); ?></td>
                                        <td>
                                            <?php echo e(date('d-m-Y', strtotime($value->updated_at))); ?><br>
                                            <?php echo e(date('h:i:s a', strtotime($value->updated_at))); ?>

                                        </td>
                                        <td>
                                            <strong><?php echo e($value->shipping ? $value->shipping->name : ''); ?></strong>
                                            <p class="mb-0"><?php echo e($value->shipping ? $value->shipping->phone : ''); ?></p>
                                        </td>
                                        <td>
                                            <?php
                                                $items = $value->orderDetails;
                                                $types = [];
                                                foreach ($items as $item) {
                                                    if ($item->product && $item->product->is_digital == 1) {
                                                        $types[] = 'Digital';
                                                    } else {
                                                        $types[] = 'Physical';
                                                    }
                                                }
                                                $types = array_unique($types);
                                                if (count($types) === 1) {
                                                    echo $types[0];
                                                } else {
                                                    echo "Mixed";
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $vendors = [];
                                                if ($value->orderDetails) {
                                                    foreach ($value->orderDetails as $item) {
                                                        if ($item->vendor && $item->vendor->shop_name) {
                                                            $vendors[$item->vendor->id] = $item->vendor->shop_name;
                                                        }
                                                    }
                                                }
                                                $uniqueVendors = array_unique($vendors);
                                            ?>
                                            <?php if(count($uniqueVendors) > 0): ?>
                                                <?php $__currentLoopData = $uniqueVendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendorName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <span class="badge bg-primary mb-1 d-block"><?php echo e($vendorName); ?></span>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($value->reseller_profit): ?>
                                                <?php if($value->user): ?>
                                                    <span class="badge bg-info" title="Reseller: <?php echo e($value->user->name); ?>">
                                                        <i class="fe-user"></i> <?php echo e(Str::limit($value->user->name, 15)); ?>

                                                    </span>
                                                    <br>
                                                    <small class="text-muted" style="font-size: 0.7rem;">Profit: ৳<?php echo e(number_format($value->reseller_profit, 0)); ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Reseller Order</span>
                                                    <br>
                                                    <small class="text-muted" style="font-size: 0.7rem;">Profit: ৳<?php echo e(number_format($value->reseller_profit, 0)); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span><?php echo e($value->ip_address); ?></span>
                                                <?php if($value->ip_address): ?>
                                                    <?php
                                                        $isBlocked = in_array($value->ip_address, isset($blockedIps) ? $blockedIps : []);
                                                    ?>
                                                    <?php if($isBlocked): ?>
                                                        <span class="badge bg-secondary" title="This IP is already blocked">
                                                            <i class="fe-shield"></i> Blocked
                                                        </span>
                                                    <?php else: ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger block-ip-btn" 
                                                                data-ip="<?php echo e($value->ip_address); ?>"
                                                                data-reason="ফেইক অর্ডার"
                                                                title="Block this IP - ফেইক অর্ডার">
                                                            <i class="fe-shield-off"></i> Block
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <?php
                                                $orderNote = isset($value->order_note) ? $value->order_note : (isset($value->note) ? $value->note : '');
                                            ?>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-info note-modal-btn"
                                                data-type="order"
                                                data-id="<?php echo e($value->id); ?>"
                                                data-note="<?php echo e($orderNote); ?>"
                                            >
                                                <?php echo e($orderNote ? 'View' : 'Add'); ?>

                                            </button>
                                        </td>

                                        
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-warning note-modal-btn"
                                                data-type="admin"
                                                data-id="<?php echo e($value->id); ?>"
                                                data-note="<?php echo e(isset($value->admin_note) ? $value->admin_note : ''); ?>"
                                            >
                                                <?php echo e($value->admin_note ? 'View' : 'Add'); ?>

                                            </button>
                                        </td>

                                        
                                        <td>
                                            <?php
                                                $payment = \App\Models\Payment::where('order_id', $value->id)->first();
                                                $paid = $payment ? floatval($payment->amount) : 0;
                                                $total = floatval($value->amount);
                                                $showAmount = $total;
                                                if ($paid > 0 && $paid < $total) {
                                                    $showAmount = $total - $paid;
                                                }
                                            ?>
                                            ৳<?php echo e(number_format($showAmount, 2)); ?>

                                        </td>

                                        <td><?php echo e($value->status ? $value->status->name : ''); ?></td>

                                        
                                        <td>
                                            <?php
                                                // Priority: courier_tracking_id > consignment_id
                                                $trackingId = isset($value->courier_tracking_id) ? $value->courier_tracking_id : $value->consignment_id;
                                                $courierType = $value->courier_type;
                                                
                                                // If no courier_type but has consignment_id, assume it's steadfast (backward compatibility)
                                                // This handles old orders that were sent via Steadfast but don't have courier_type
                                                if (!$courierType && $value->consignment_id) {
                                                    $courierType = 'steadfast';
                                                }
                                                
                                                // If still no courier_type but has tracking_id, assume steadfast
                                                if (!$courierType && $trackingId) {
                                                    $courierType = 'steadfast';
                                                }
                                            ?>
                                            
                                            <?php if($trackingId): ?>
                                                <?php
                                                    $courierName = ucfirst(isset($courierType) ? $courierType : 'Steadfast');
                                                    $ct = isset($courierType) ? strtolower($courierType) : 'steadfast';
                                                    if ($ct === 'pathao') { $courierColor = 'info'; }
                                                    elseif ($ct === 'steadfast') { $courierColor = 'primary'; }
                                                    elseif ($ct === 'redx') { $courierColor = 'warning'; }
                                                    else { $courierColor = 'primary'; }
                                                ?>
                                                <div>
                                                    <span class="badge bg-<?php echo e($courierColor); ?> mb-1">
                                                        <i class="fe-truck"></i> <?php echo e($courierName); ?>

                                                    </span>
                                                    <br>
                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                        ID: <?php echo e(Str::limit($trackingId, 15)); ?>

                                                    </small>
                                                    <?php if($value->courier_sent_at): ?>
                                                        <br>
                                                        <small class="text-muted" style="font-size: 0.7rem;">
                                                            <?php echo e(date('d-m-Y', strtotime($value->courier_sent_at))); ?>

                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php
                                                // Get tracking ID (new field or fallback to old consignment_id)
                                                $trackingId = isset($value->courier_tracking_id) ? $value->courier_tracking_id : $value->consignment_id;
                                                $courierType = $value->courier_type;
                                                
                                                // If no courier_type but has consignment_id, assume it's steadfast
                                                if (!$courierType && $value->consignment_id) {
                                                    $courierType = 'steadfast';
                                                }
                                            ?>
                                            
                                            <?php if(!empty($trackingId)): ?>
                                                <?php if($courierType == 'pathao'): ?>
                                                    <a href="https://merchant.pathao.com/public-tracking?consignment_id=<?php echo e($trackingId); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-info">
                                                        <i class="fe-truck"></i> Track
                                                    </a>
                                                <?php elseif($courierType == 'steadfast' || (!$courierType && $trackingId)): ?>
                                                    <a href="https://steadfast.com.bd/t/<?php echo e($trackingId); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                                                        <i class="fe-truck"></i> Track
                                                    </a>
                                                <?php elseif($courierType == 'redx'): ?>
                                                    <a href="https://redx.com.bd/track/<?php echo e($trackingId); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-warning">
                                                        <i class="fe-truck"></i> Track
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo e($trackingId); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">কোন রেকর্ড নেই</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            
                                            <?php if(is_null($value->fraud_rate)): ?>
                                                 <a href="javascript:void(0);" 
                                                class="btn btn-sm fraud-check"
                                                data-mobile="<?php echo e($value->shipping ? $value->shipping->phone : ''); ?>"
                                                style="background:#fb8709; color:#fff; padding:5px 12px; border-radius:6px; font-size:13px;">
                                                চেকিং
                                            </a>
                                            <?php else: ?>
                                                <a href="javascript:void(0);" 
                                                   class="btn btn-sm fraud-check <?php echo e($value->fraud_rate >= 80 ? 'btn-success' : 'btn-danger'); ?>"
                                                   data-mobile="<?php echo e($value->shipping ? $value->shipping->phone : ''); ?>"
                                                   data-id="<?php echo e($value->id); ?>"
                                                   style="padding:5px 12px; border-radius:6px; font-size:13px;">
                                                    <?php echo e($value->fraud_rate); ?>% <?php echo e($value->fraud_rate >= 80 ? 'নিরাপদ' : 'ঝুঁকি'); ?>

                                                </a>
                                            <?php endif; ?>
                                        </td>

                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="custom-paginate mt-3">
                        <?php echo e($show_data->links('pagination::bootstrap-4')); ?>

                    </div>
                </div> </div> </div></div>
</div>

<div class="modal fade" id="asignUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Assign User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo e(route('admin.order.assign')); ?>" id="order_assign">
        <div class="modal-body">
            <div class="form-group">
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">Select..</option>
                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($u->id); ?>"><?php echo e($u->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="changeStatus" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo e(route('admin.order.status')); ?>" id="order_status_form" novalidate>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Select Status <span class="text-danger">*</span></label>
                <select name="order_status" id="order_status" class="form-control">
                    <option value="">Select Status..</option>
                    <?php if(isset($orderstatus) && $orderstatus->count() > 0): ?>
                        <?php $__currentLoopData = $orderstatus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($s->id); ?>"><?php echo e($s->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <option value="">No status available</option>
                    <?php endif; ?>
                </select>
                <small class="text-muted">Select orders first, then choose status</small>
                <div class="invalid-feedback" id="status_error" style="display: none;">Please select a status</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="pathao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pathao Courier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo e(route('admin.order.pathao')); ?>" id="order_sendto_pathao" method="POST">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="order_ids" id="pathao_order_ids" value="">
      <div class="modal-body">
        <div class="form-group">
            <label for="pathaostore" class="form-label">Store</label>
           <select name="pathaostore" id="pathaostore" class="pathaostore form-control" >
             <option value="">Select Store...</option>
             <?php if(isset($pathaostore['data']['data'])): ?>
                 <?php $__currentLoopData = $pathaostore['data']['data']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                     <option value="<?php echo e($store['store_id']); ?>"><?php echo e($store['store_name']); ?></option>
                 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
             <?php endif; ?>
           </select>
        </div>

        <div class="form-group mt-3">
          <label for="pathaocity" class="form-label">City</label>
           <select name="pathaocity" id="pathaocity" class="chosen-select pathaocity form-control" style="width:100%" >
             <option value="">Select City...</option>
             <?php if(isset($pathaocities['data']['data'])): ?>
                 <?php $__currentLoopData = $pathaocities['data']['data']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                     <option value="<?php echo e($city['city_id']); ?>"><?php echo e($city['city_name']); ?></option>
                 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
             <?php endif; ?>
           </select>
        </div>

        <div class="form-group mt-3">
          <label class="form-label">Zone</label>
             <select name="pathaozone" id="pathaozone" class="pathaozone chosen-select form-control" style="width:100%"></select>
        </div>

        <div class="form-group mt-3">
          <label class="form-label">Area</label>
             <select name="pathaoarea" id="pathaoarea" class="pathaoarea chosen-select form-control" style="width:100%"></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success">Submit</button>
      </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="noteModalLabel">Note</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="note_order_id">
        <input type="hidden" id="note_type">

        <div class="form-group">
            <label id="note_label">Note</label>
            <textarea id="note_modal_text" class="form-control" rows="5" placeholder="Write note here..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="saveNoteBtn">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="fraudCheckModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header" style="background:#10b981; color:#fff;">
                <h5 class="modal-title">
                    <i class="fe-shield"></i> ফ্রড চেকার রিপোর্ট
                </h5>
                <button type="button" class="btn-close btn-light" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="fraudModalBody" style="min-height:250px;">
                <div class="text-center py-5">
                    <div class="spinner-border text-success" style="width:3rem;height:3rem;"></div>
                    <p class="mt-3 fw-bold">ডাটা লোড হচ্ছে...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Safe number helper
    function toNum(v) {
        if (v === null || v === undefined || v === '') return 0;
        var n = Number(v);
        return isNaN(n) ? 0 : n;
    }

    // buildSummary: Updated to handle New API keys
    function buildSummary(raw) {
        var pathao = raw.pathao || raw.Pathao || raw.pathao_data || raw.pathao || {};
        var redx = raw.redx || raw.RedX || raw.redx_data || raw.redx || {};
        var steadfast = raw.steadfast || raw.Steadfast || raw.steadfast_data || raw.steadfast || {};
        var parceldex = raw.parceldex || raw.ParcelDex || {};
        var paperfly = raw.paperfly || raw.PaperFly || {};

        function getStats(obj) {
            var t = toNum(obj.total_parcel || obj.total || obj.orders || obj.count);
            var s = toNum(obj.success_parcel || obj.success || obj.complete || obj.delivered);
            var c = toNum(obj.cancelled_parcel || obj.cancel || obj.cancelled || obj.failed);
            var r = (obj.success_ratio !== undefined) ? toNum(obj.success_ratio) : (t > 0 ? Math.round((s / t) * 100) : 0);
            return { total: t, success: s, cancel: c, rate: r };
        }

        var p = getStats(pathao);
        var r = getStats(redx);
        var s = getStats(steadfast);
        var pd = getStats(parceldex);
        var pf = getStats(paperfly);

        var total = p.total + r.total + s.total + pd.total + pf.total;
        var success = p.success + r.success + s.success + pd.success + pf.success;
        var cancel = p.cancel + r.cancel + s.cancel + pd.cancel + pf.cancel;

        var rate = 0;
        if (total > 0) rate = Math.round((success / total) * 100);

        return {
            total: total,
            success: success,
            cancel: cancel,
            rate: rate,
            couriers: {
                Pathao: p,
                RedX: r,
                Steadfast: s,
                ParcelDex: pd,
                PaperFly: pf
            }
        };
    }

    // Render HTML for modal from canonical summary (IN BANGLA)
    function loadFraudHtml(data, mobile) {
        if (data.total === 0) {
            return `
            <div class="container-fluid">
                <div class="p-3 mb-3" style="background:#f8f9fa;border-radius:8px;">
                    <h5><i class="fe-phone-call"></i> ${mobile}</h5>
                    <small>সফলতার হার: 0%</small>
                    <span class="badge bg-secondary float-end">কোন তথ্য নেই</span>
                </div>
                <div class="alert alert-light text-center py-3" style="border:1px solid #ddd;">
                    <h5 class="text-muted mb-0">😕 কোনো তথ্য খুঁজে পাওয়া যায়নি</h5>
                    <small>এই কাস্টমারের সম্পর্কে কোনো তথ্য পাওয়া যায়নি। অতিরিক্ত সতর্কতার জন্য নিজের যাচাই করুন।</small>
                </div>
            </div>`;
        }

        var rateText = (data.rate || data.rate === 0) ? (data.rate + '%') : 'N/A';
        
        // Bangla Risk Tags
        var riskTag = '<span class="badge bg-success">নিরাপদ</span>';
        var showWarning = (data.total > 0 && data.rate < 80);
        if (showWarning) { riskTag = '<span class="badge bg-danger">উচ্চ ঝুঁকি</span>'; }

        var courierRows = '';
        Object.entries(data.couriers).forEach(function([name, c]) {
            if(c.total === 0) return;

            var cRateNum = toNum(c.rate);
            var cRate = (c.total === 0) ? 'N/A' : (cRateNum + '%');
            var badgeClass = 'bg-secondary';
            if (c.total === 0) { badgeClass = 'bg-secondary'; }
            else if (cRateNum >= 90) { badgeClass = 'bg-success'; }
            else if (cRateNum >= 70) { badgeClass = 'bg-warning text-dark'; }
            else { badgeClass = 'bg-danger'; }

            courierRows += `
                <tr>
                    <td>${name}</td>
                    <td>${c.total}</td>
                    <td class="text-success">${c.success}</td>
                    <td class="text-danger">${c.cancel}</td>
                    <td><span class="badge ${badgeClass}">${cRate}</span></td>
                </tr>`;
        });

        var warningHtml = '';
        if (showWarning) {
            warningHtml = `<div class="alert alert-danger text-center py-2">⚠️ সতর্কতা: ডেলিভারি হার কম - COD যাচাই করুন অথবা এডভান্স নিন</div>`;
        } else {
            warningHtml = `<div class="text-start mb-3"><small class="text-success">✓ নিরাপদ - কাস্টমারের ডেলিভারি রেকর্ড ভালো।</small></div>`;
        }

        return `
            <div class="container-fluid">
                <div class="p-3 mb-3" style="background:#e8fff3;border-radius:8px;">
                    <h5><i class="fe-phone-call"></i> ${mobile}</h5>
                    <small>সফলতার হার: ${rateText}</small>
                    <span class="float-end">${riskTag}</span>
                </div>
                ${warningHtml}
                <div class="row text-center mb-4">
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#6366f1;border-radius:10px;">
                            <h3>${data.total}</h3><span>মোট পার্সেল</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#10b981;border-radius:10px;">
                            <h3>${data.success}</h3><span>ডেলিভারি</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#ef4444;border-radius:10px;">
                            <h3>${data.cancel}</h3><span>বাতিল/রিটার্ন</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#f97316;border-radius:10px;">
                            <h3>${rateText}</h3><span>হার</span>
                        </div>
                    </div>
                </div>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>কুরিয়ার</th><th>মোট</th><th>সফল</th><th>বাতিল</th><th>হার</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${courierRows}
                    </tbody>
                </table>
            </div>
        `;
    }
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

    // Order Note / Admin Note popup open
    $(document).on('click', '.note-modal-btn', function (e) {
        e.preventDefault();
        let orderId = $(this).data('id');
        let type    = $(this).data('type');
        let note    = $(this).data('note') || '';

        $('#note_order_id').val(orderId);
        $('#note_type').val(type);
        $('#note_modal_text').val(note);

        if (type === 'admin') {
            $('#noteModalLabel').text('Admin Note');
            $('#note_label').text('Admin Note');
        } else {
            $('#noteModalLabel').text('Order Note (Customer)');
            $('#note_label').text('Order Note (Customer)');
        }

        $('#noteModal').modal('show');
    });

    // Save Note (AJAX)
    $('#saveNoteBtn').on('click', function () {
        let orderId = $('#note_order_id').val();
        let type    = $('#note_type').val();
        let note    = $('#note_modal_text').val();

        $.ajax({
            url: "<?php echo e(route('admin.order.update_note')); ?>",
            type: "POST",
            data: {
                _token: "<?php echo e(csrf_token()); ?>",
                order_id: orderId,
                note_type: type,
                note: note
            },
            success: function (res) {
                if (res.status === 'success') {
                    toastr.success('Note updated successfully');
                    let selector = '.note-modal-btn[data-id="' + orderId + '"][data-type="' + type + '"]';
                    let $btn = $(selector);
                    $btn.data('note', note);
                    $btn.text(note ? 'View' : 'Add');
                    $('#noteModal').modal('hide');
                } else {
                    toastr.error(res.message || 'Update failed');
                }
            },
            error: function () {
                toastr.error('Something went wrong');
            }
        });
    });

    // checkall
    $(".checkall").on('change',function(){
      $(".checkbox").prop('checked',$(this).is(":checked"));
    });

    // Fraud check → Popup Modal Open
    $(document).on('click', '.fraud-check', function(e){
        e.preventDefault();
        let mobile  = $(this).data('mobile');
        
        if (!mobile) { return toastr.error("No mobile number found"); }

        $("#fraudModalBody").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-success" style="width:3rem;height:3rem;"></div>
                <p class="mt-3 fw-bold">তথ্য যাচাই করা হচ্ছে...</p>
            </div>
        `);

        $("#fraudCheckModal").modal("show");

        $.ajax({
            url: "<?php echo e(route('admin.fraud.check')); ?>",
            type: "POST",
            data: { 
                mobile: mobile,
                // আমরা এখানে order_id পাঠাচ্ছি না, কারণ কন্ট্রোলার মোবাইল নম্বর দিয়ে 
                // সব অর্ডার আপডেট করবে।
                _token: "<?php echo e(csrf_token()); ?>" 
            },
            timeout: 60000, // 60 seconds timeout
            beforeSend: function() {
                // Show loading state
                $("#fraudModalBody").html(`
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">ফ্রড চেক করা হচ্ছে... অনুগ্রহ করে অপেক্ষা করুন।</p>
                    </div>
                `);
            },
            success: function(res) {
                
                if (res && res.status === "success") {
                    let apiData = {};
                    
                    if(res.data && res.data.data) {
                        apiData = res.data.data;
                    } else if (res.data) {
                        apiData = res.data;
                    }

                    // এখন আমরা পেইজে থাকা ওই মোবাইল নাম্বারের *সব বাটন* খুঁজে বের করব
                    let allBtns = $('.fraud-check[data-mobile="'+mobile+'"]');

                    if(res.data && res.data.is_fraud === true) {
                         $("#fraudModalBody").html(`
                            <div class="alert alert-danger text-center p-5">
                                <h3>⚠️ ফ্রড ডিটেক্টেড!</h3>
                                <p>এই নাম্বারটি ফ্রড তালিকায় রয়েছে।</p>
                            </div>
                         `);
                         
                         // সব বাটন লাল করে দেওয়া
                         allBtns.removeClass('btn-warning text-dark btn-success').addClass('btn-danger').text('ফ্রড (ঝুঁকি)');
                         return;
                    }

                    // Build Summary
                    var summary = buildSummary(apiData);
                    $("#fraudModalBody").html(loadFraudHtml(summary, mobile));

                    // ==========================================
                    // INSTANT BUTTON UPDATE LOGIC (ALL BUTTONS)
                    // ==========================================
                    let r = summary.rate;
                    
                    // আগের ক্লাস রিমুভ
                    allBtns.removeClass('btn-warning text-dark btn-success btn-danger');

                    if(r >= 80) {
                        // Safe
                        allBtns.addClass('btn-success');
                        allBtns.text(r + '% নিরাপদ');
                    } else {
                        // Risk
                        allBtns.addClass('btn-danger');
                        allBtns.text(r + '% ঝুঁকি');
                    }

                    toastr.success('স্ট্যাটাস সফলভাবে সেভ হয়েছে!');

                } else {
                    var msg = (res && res.message) ? res.message : 'No data returned';
                    $("#fraudModalBody").html(`<div class="alert alert-danger text-center p-4">${msg}</div>`);
                }
            },

            error: function(xhr, status, error) {
                console.error('Fraud Check AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseJSON,
                    statusCode: xhr.status
                });
                
                let errorMessage = 'অনুগ্রহ করে আবার চেষ্টা করুন।';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (status === 'timeout') {
                    errorMessage = 'Request timeout! API server response নেওয়া যায়নি। অনুগ্রহ করে আবার চেষ্টা করুন।';
                } else if (status === 'error') {
                    errorMessage = 'Connection error! API server-এ connection করতে পারছে না।';
                } else if (xhr.status === 400) {
                    errorMessage = 'Invalid request! দয়া করে মোবাইল নাম্বার চেক করুন।';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error! দয়া করে admin-কে জানান।';
                } else if (xhr.status === 404) {
                    errorMessage = 'API endpoint not found!';
                }
                
                $("#fraudModalBody").html(`
                    <div class="alert alert-danger text-center p-4">
                        <h5>❌ Error!</h5>
                        <p>${errorMessage}</p>
                        ${xhr.responseJSON && xhr.responseJSON.message ? `<small>${xhr.responseJSON.message}</small>` : ''}
                    </div>
                `);
                
                // Reset button to original state
                let allBtns = $('.fraud-check[data-mobile="'+mobile+'"]');
                allBtns.removeClass('btn-success btn-danger').addClass('btn-warning').text('চেকিং');
                
                toastr.error('Fraud check failed: ' + errorMessage);
            }
        });
    });

    // order assign
    $(document).on('submit', 'form#order_assign', function(e){
        e.preventDefault();
        var url = $(this).attr('action');
        let user_id = $('#user_id').val();

        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return;
        }

        $.ajax({
           type: 'GET',
           url: url,
           data: { user_id: user_id, order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   toastr.success(res.message);
                   window.location.reload();
               } else {
                   toastr.error(res.message || 'Failed something wrong');
               }
           },
           error: function(){
               toastr.error('Something went wrong');
           }
        });
    });

    // order status change
    $(document).on('submit', 'form#order_status_form', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        var url = $(this).attr('action');
        let order_status = $('#order_status').val();
        var $statusSelect = $('#order_status');
        var $statusError = $('#status_error');
        
        // Clear any previous validation state
        $statusSelect.removeClass('is-invalid is-valid');
        $statusError.hide();

        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        // Validate orders selected FIRST
        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return false;
        }
        
        // Validate status selected - check multiple conditions
        var statusValue = String(order_status || '').trim();
        if(!statusValue || statusValue === '' || statusValue === 'null' || statusValue === 'undefined' || statusValue === '0'){
            $statusSelect.addClass('is-invalid');
            $statusError.text('Please select a status').show();
            toastr.error('Please Select A Status First !');
            // Focus on select field and scroll to it
            $statusSelect.focus();
            $('html, body').animate({
                scrollTop: $statusSelect.offset().top - 100
            }, 300);
            return false;
        }
        
        // Additional check - make sure it's a valid number
        if(isNaN(parseInt(statusValue)) || parseInt(statusValue) <= 0){
            $statusSelect.addClass('is-invalid');
            $statusError.text('Please select a valid status').show();
            toastr.error('Please Select A Valid Status !');
            $statusSelect.focus();
            return false;
        }

        // Show loading
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fe-loader"></i> Updating...');

        $.ajax({
           type: 'GET',
           url: url,
           data: { order_status: order_status, order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   toastr.success(res.message);
                   $('#changeStatus').modal('hide');
                   setTimeout(function(){
                       window.location.reload();
                   }, 1000);
               } else {
                   toastr.error(res.message || 'Failed something wrong');
                   $submitBtn.prop('disabled', false).html(originalHtml);
               }
           },
           error: function(xhr){
               console.error('Status update error:', xhr);
               var errorMsg = 'Something went wrong';
               
               // Handle Laravel validation errors
               if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors){
                   var errors = xhr.responseJSON.errors;
                   if(errors.order_status){
                       $statusSelect.addClass('is-invalid');
                       $statusError.text(errors.order_status[0]).show();
                       errorMsg = errors.order_status[0];
                   } else if(errors.order_ids){
                       errorMsg = errors.order_ids[0];
                   }
               } else if(xhr.responseJSON && xhr.responseJSON.message){
                   errorMsg = xhr.responseJSON.message;
               } else if(xhr.status === 400){
                   errorMsg = 'Bad request. Please check your selection.';
               }
               
               toastr.error(errorMsg);
               $submitBtn.prop('disabled', false).html(originalHtml);
           }
        });
        
        return false;
    });

    // order delete (bulk)
    $(document).on('click', '.order_delete', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return;
        }

        $.ajax({
           type: 'GET',
           url: url,
           data: { order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   toastr.success(res.message);
                   window.location.reload();
               } else {
                   toastr.error(res.message || 'Failed something wrong');
               }
           },
           error: function(){
               toastr.error('Something went wrong');
           }
        });
    });

    // multiple print
    $(document).on('click', '.multi_order_print', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select Atleast One Order!');
            return;
        }
        $.ajax({
           type: 'GET',
           url: url,
           data: { order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   var myWindow = window.open("", "_blank");
                   myWindow.document.write(res.view);
               } else {
                   toastr.error(res.message || 'Failed something wrong');
               }
           },
           error: function(){
               toastr.error('Something went wrong');
           }
        });
    });

    // label print
    $(document).on('click', '.multi_label_print', function(e){
        e.preventDefault();
        var order_ids = $('input.checkbox:checked').map(function(){ return $(this).val(); }).get();
        if(order_ids.length == 0){ toastr.error('Please Select Atleast One Order!'); return; }
        $.ajax({
            type: 'GET',
            url: $(this).attr('href'),
            data: { order_ids: order_ids, type: 'label' },
            success: function(res){
                if(res.status == 'success'){
                    var w = window.open("","_blank");
                    w.document.write(res.view);
                } else { toastr.error(res.message || 'Failed'); }
            },
            error: function(){ toastr.error('Something went wrong'); }
        });
    });

    // multiple courier
    $(document).on('click', '.multi_order_courier', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return;
        }
        
        // Show loading
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fe-loader"></i> Sending...');

        $.ajax({
           type: 'GET',
           url: url,
           data: { order_ids: order_ids },
           success: function(res){
               console.log('Courier Response:', res); // Debug log
               
               if(res.status == 'success'){
                    if(res.success && res.success.length > 0){
                        toastr.success('Orders sent to courier successfully!');
                    }
                    if(res.failed && res.failed.length > 0){
                        res.failed.forEach(function(fail){
                            console.error('Failed order:', fail);
                            toastr.warning('Order ' + fail.order_id + ': ' + fail.message);
                        });
                    }
                    // Reload page to show courier information
                    setTimeout(function(){
                        window.location.reload();
                    }, 1000);
               } else {
                    toastr.error(res.message || 'Failed something wrong');
                    $btn.prop('disabled', false).html(originalHtml);
               }
           },
           error: function(xhr){
               console.error('Courier Error:', xhr);
               var errorMsg = 'Something went wrong';
               
               if(xhr.responseJSON){
                   // Check for failed orders with detailed messages
                   if(xhr.responseJSON.failed && xhr.responseJSON.failed.length > 0){
                       xhr.responseJSON.failed.forEach(function(fail){
                           var msg = fail.message || 'Failed to send order';
                           if(fail.status_code === 401){
                               msg = 'Account is not active! Please check your Steadfast account status and API credentials.';
                           } else if(fail.status_code === 403){
                               msg = 'Access forbidden! Please check your API credentials.';
                           } else if(fail.status_code === 404){
                               msg = 'API endpoint not found! Please check the API URL.';
                           }
                           toastr.error('Order ' + fail.order_id + ': ' + msg);
                       });
                   } else if(xhr.responseJSON.message){
                       errorMsg = xhr.responseJSON.message;
                   }
               } else if(xhr.status === 401){
                   errorMsg = 'Account is not active! Please check your Steadfast account status and API credentials.';
               } else if(xhr.status === 403){
                   errorMsg = 'Access forbidden! Please check your API credentials.';
               } else if(xhr.status === 404){
                   errorMsg = 'API endpoint not found! Please check the API URL.';
               }
               
               toastr.error(errorMsg);
               $btn.prop('disabled', false).html(originalHtml);
           }
        });
    });

    // Quick IP Block from order page
    $(document).on('click', '.block-ip-btn', function(e){
        e.preventDefault();
        var $btn = $(this);
        var ip = $btn.data('ip');
        var reason = $btn.data('reason') || 'ফেইক অর্ডার';
        
        if(!ip){
            toastr.error('IP address not found');
            return;
        }
        
        // Disable button and show loading
        $btn.prop('disabled', true);
        var originalHtml = $btn.html();
        $btn.html('<i class="fe-loader"></i> Blocking...');
        
        $.ajax({
            url: "<?php echo e(route('customers.ipblock.quick')); ?>",
            type: "POST",
            data: {
                _token: "<?php echo e(csrf_token()); ?>",
                ip: ip,
                reason: reason
            },
            success: function(res){
                if(res.status === 'success'){
                    toastr.success(res.message || 'IP blocked successfully');
                    // Change button to show blocked state (badge style)
                    $btn.replaceWith('<span class="badge bg-secondary" title="This IP is already blocked"><i class="fe-shield"></i> Blocked</span>');
                } else {
                    toastr.error(res.message || 'Failed to block IP');
                    $btn.prop('disabled', false);
                    $btn.html(originalHtml);
                }
            },
            error: function(xhr){
                var errorMsg = 'Failed to block IP';
                if(xhr.responseJSON && xhr.responseJSON.message){
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }
        });
    });

    // Pathao Modal Open - Set selected order IDs
    $(document).on('click', '[data-bs-target="#pathao"]', function(e){
        var order = $('input.checkbox:checked').map(function(){
            return $(this).val();
        });
        var order_ids = order.get();
        
        if(order_ids.length == 0){
            toastr.error('Please Select Atleast One Order First!');
            e.preventDefault();
            return false;
        }
        
        $('#pathao_order_ids').val(order_ids.join(','));
    });

    // Pathao City Change - Load Zones
    $(document).on('change', '#pathaocity', function(){
        var cityId = $(this).val();
        if(!cityId){
            $('#pathaozone').html('<option value="">Select Zone...</option>');
            $('#pathaoarea').html('<option value="">Select Area...</option>');
            return;
        }
        
        $.ajax({
            url: "<?php echo e(route('pathaocity')); ?>",
            type: "GET",
            data: { city_id: cityId },
            success: function(res){
                var options = '<option value="">Select Zone...</option>';
                if(res && res.data && res.data.data && res.data.data.length > 0){
                    $.each(res.data.data, function(key, zone){
                        options += '<option value="' + zone.zone_id + '">' + zone.zone_name + '</option>';
                    });
                } else {
                    toastr.warning('No zones found for this city');
                }
                $('#pathaozone').html(options);
                $('#pathaoarea').html('<option value="">Select Area...</option>');
            },
            error: function(xhr){
                var errorMsg = 'Failed to load zones';
                if(xhr.responseJSON && xhr.responseJSON.message){
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                $('#pathaozone').html('<option value="">Select Zone...</option>');
                $('#pathaoarea').html('<option value="">Select Area...</option>');
            }
        });
    });

    // Pathao Zone Change - Load Areas
    $(document).on('change', '#pathaozone', function(){
        var zoneId = $(this).val();
        if(!zoneId){
            $('#pathaoarea').html('<option value="">Select Area...</option>');
            return;
        }
        
        $.ajax({
            url: "<?php echo e(route('pathaozone')); ?>",
            type: "GET",
            data: { zone_id: zoneId },
            success: function(res){
                var options = '<option value="">Select Area...</option>';
                if(res && res.data && res.data.data && res.data.data.length > 0){
                    $.each(res.data.data, function(key, area){
                        options += '<option value="' + area.area_id + '">' + area.area_name + '</option>';
                    });
                } else {
                    toastr.warning('No areas found for this zone');
                }
                $('#pathaoarea').html(options);
            },
            error: function(xhr){
                var errorMsg = 'Failed to load areas';
                if(xhr.responseJSON && xhr.responseJSON.message){
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                $('#pathaoarea').html('<option value="">Select Area...</option>');
            }
        });
    });

    // Pathao Form Submit
    $(document).on('submit', '#order_sendto_pathao', function(e){
        e.preventDefault();
        
        var orderIds = $('#pathao_order_ids').val();
        if(!orderIds){
            toastr.error('Please select orders first');
            return;
        }
        
        var formData = $(this).serialize();
        formData += '&order_ids=' + orderIds.split(',').map(function(id){ return id.trim(); }).join(',');
        
        // Validate required fields
        if(!$('#pathaostore').val() || !$('#pathaocity').val() || !$('#pathaozone').val() || !$('#pathaoarea').val()){
            toastr.error('Please fill all required fields (Store, City, Zone, Area)');
            return;
        }
        
        $.ajax({
            url: $(this).attr('action'),
            type: "POST",
            data: formData,
            success: function(res){
                if(res.status === 'success'){
                    var successCount = res.result.success ? res.result.success.length : 0;
                    var failedCount = res.result.failed ? res.result.failed.length : 0;
                    
                    if(successCount > 0){
                        toastr.success(successCount + ' order(s) sent to Pathao successfully');
                    }
                    if(failedCount > 0){
                        toastr.warning(failedCount + ' order(s) failed to send');
                    }
                    
                    $('#pathao').modal('hide');
                    setTimeout(function(){
                        window.location.reload();
                    }, 1500);
                } else {
                    toastr.error(res.message || 'Failed to send orders');
                }
            },
            error: function(xhr){
                var errorMsg = 'Failed to send orders';
                if(xhr.responseJSON && xhr.responseJSON.message){
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
            }
        });
    });

});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/backEnd/order/index.blade.php ENDPATH**/ ?>