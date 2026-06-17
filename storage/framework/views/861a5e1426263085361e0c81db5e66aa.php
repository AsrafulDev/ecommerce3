
<?php $__env->startSection('title','Sales Dashboard'); ?>

<?php $__env->startSection('css'); ?>
<link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.5/dist/apexcharts.css" rel="stylesheet">
<style>
.dashboard-banner{
  background:linear-gradient(90deg,#5e72e4 0%,#825ee4 100%);
  color:#fff;border-radius:15px;padding:25px;
  display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap
}
.dashboard-card{
  background:#fff;border-radius:15px;padding:25px;
  box-shadow:0 2px 10px rgba(0,0,0,.06);transition:.3s
}
.dashboard-card:hover{transform:translateY(-3px)}
.metric-title{font-size:14px;color:#9da5b4}
.metric-value{font-size:26px;font-weight:700;color:#273444}
.chart-box{
  background:#fff;border-radius:15px;padding:20px;
  box-shadow:0 2px 10px rgba(0,0,0,.06)
}
.table td,.table th{vertical-align:middle}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-3">

  
  <div class="mb-3">
    <h4 class="fw-bold">Hi! Welcome To Dashboard</h4>
    <small class="text-muted">Home → Sales Dashboard</small>
  </div>

  
  <div class="dashboard-banner mb-4">
    <div>
      <h3>Congratulations <?php echo e(Auth::user()->name ?? 'Admin'); ?> 🎉</h3>
      <p>You have reached your sales milestone! Keep going strong 💪</p>
    </div>
    <div class="text-end">
      <h2 class="mb-0">
        TK <?php echo e(number_format($today_profit ?? 0,2)); ?>

      </h2>
      <p class="mb-0">Today's Profit</p>
    </div>
  </div>

  
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="metric-title">Total Orders</div>
        <div class="metric-value"><?php echo e(number_format($total_order ?? 0)); ?></div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="metric-title">Fund Balance</div>
        <div class="metric-value">
          TK <?php echo e(number_format($fund_balance ?? 0,2)); ?>

        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="metric-title">Total Expenses</div>
        <div class="metric-value">
          TK <?php echo e(number_format($total_expenses ?? 0,2)); ?>

        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="metric-title">Delivered Orders</div>
        <div class="metric-value"><?php echo e(number_format($total_delivery ?? 0)); ?></div>
      </div>
    </div>
  </div>

  
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="chart-box">
        <h5 class="fw-bold mb-3">Sales By Category</h5>
        <div id="categoryChart"></div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="chart-box">
        <h5 class="fw-bold mb-3">Monthly Sales Statistics</h5>
        <div id="salesChart"></div>
      </div>
    </div>
  </div>

  
  <div class="row g-3 mt-3">
    <div class="col-lg-8">
      <div class="chart-box">
        <h5 class="fw-bold mb-3">Recent Orders</h5>
        <div class="table-responsive">
          <table class="table table-borderless">
            <thead class="table-light">
              <tr>
                <th>Customer</th>
                <th>Invoice</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $latest_order ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><?php echo e($order->customer->name ?? 'Guest'); ?></td>
                <td>#<?php echo e($order->invoice_id ?? '-'); ?></td>
                <td>
                  <?php if(($order->order_status ?? 0) == 5): ?>
                    <span class="badge bg-success">Delivered</span>
                  <?php elseif(($order->order_status ?? 0) == 1): ?>
                    <span class="badge bg-info">Pending</span>
                  <?php else: ?>
                    <span class="badge bg-warning">Processing</span>
                  <?php endif; ?>
                </td>
                <td><?php echo e(optional($order->created_at)->format('d M Y')); ?></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr>
                <td colspan="4" class="text-center text-muted">
                  No recent orders found
                </td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="chart-box">
        <h5 class="fw-bold mb-3">Recent Customers</h5>
        <ul class="list-unstyled mb-0">
          <?php $__empty_1 = true; $__currentLoopData = $latest_customer ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cust): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <li class="py-2 border-bottom">
              <strong><?php echo e($cust->name); ?></strong><br>
              <small class="text-muted"><?php echo e($cust->phone ?? 'N/A'); ?></small>
            </li>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <li class="text-muted">No customers found</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.5"></script>
<script>
// Category Chart - Dynamic Data from Database
new ApexCharts(document.querySelector("#categoryChart"),{
  chart:{type:'donut',height:260},
  labels:<?php echo json_encode($categoryLabels ?? ['No Sales'], 15, 512) ?>,
  series:<?php echo json_encode($categorySeries ?? [0], 15, 512) ?>,
  legend:{position:'bottom'},
  tooltip:{
    y:{
      formatter:function(val){
        return '৳ ' + val.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
      }
    }
  },
  plotOptions:{
    pie:{
      donut:{
        labels:{
          show:true,
          total:{
            show:true,
            label:'Total Sales',
            formatter:function(){
              var total = <?php echo json_encode(array_sum($categorySeries ?? [0]), 15, 512) ?>;
              return '৳ ' + total.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
            }
          }
        }
      }
    }
  }
}).render();

// Monthly Sales Chart
new ApexCharts(document.querySelector("#salesChart"),{
  chart:{type:'area',height:300,toolbar:{show:false}},
  series:[{
    name:'Sales',
    data:<?php echo json_encode(($monthly_sale ?? collect())->pluck('amount'), 15, 512) ?>
  }],
  xaxis:{
    categories:<?php echo json_encode(($monthly_sale ?? collect())->pluck('date'), 15, 512) ?>
  },
  stroke:{curve:'smooth'},
}).render();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/backEnd/admin/dashboard.blade.php ENDPATH**/ ?>