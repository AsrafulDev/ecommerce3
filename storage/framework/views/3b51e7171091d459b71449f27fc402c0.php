
<?php $__env->startSection('title', 'Live Ads Result | Ads Analytics'); ?>

<?php $__env->startSection('css'); ?>
<style>
.ads-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 12px rgba(0,0,0,.06); margin-bottom:20px; }
.ads-card.facebook { border-left:4px solid #1877f2; }
.ads-card.google { border-left:4px solid #ea4335; }
.ads-card.tiktok { border-left:4px solid #000; }
.ads-card.messages { border-left:4px solid #25d366; }
.ads-card.spend { border-left:4px solid #f59e0b; }
.live-badge { animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.6} }
.metric-row { display:flex; flex-wrap:wrap; gap:15px; margin-top:12px; }
.metric-item { flex:1; min-width:90px; padding:8px 12px; background:#f8fafc; border-radius:8px; text-align:center; }
.metric-value { font-size:18px; font-weight:700; color:#1e293b; }
.metric-label { font-size:11px; color:#64748b; text-transform:uppercase; }
.refresh-btn { position:absolute; top:15px; right:15px; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-3">
  <div class="row">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="fw-bold mb-1">Live Ads Result & Message Dashboard</h4>
          <small class="text-muted">Facebook Ads • Google Ads • TikTok Ads • Messages & Ad Spend</small>
        </div>
        <div>
          <span class="badge bg-success live-badge me-2"><i class="fe-radio"></i> Live</span>
          <a href="<?php echo e(route('admin.ads_analytics.dashboard', ['refresh' => 1])); ?>" class="btn btn-sm btn-outline-primary">
            <i class="fe-refresh-cw"></i> Refresh
          </a>
          <a href="<?php echo e(route('admin.ads_analytics.settings')); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fe-settings"></i> Settings
          </a>
        </div>
      </div>
    </div>
  </div>

  
  <div class="mb-3">
    <a href="<?php echo e(route('admin.ads_analytics.facebook')); ?>" class="btn btn-sm btn-outline-primary me-2"><i class="fe-facebook"></i> Facebook Ads</a>
    <a href="<?php echo e(route('admin.ads_analytics.google')); ?>" class="btn btn-sm btn-outline-danger me-2"><i class="fe-globe"></i> Google Ads</a>
    <a href="<?php echo e(route('admin.ads_analytics.tiktok')); ?>" class="btn btn-sm btn-outline-dark"><i class="fe-video"></i> TikTok Ads</a>
  </div>

  
  <div class="row">
    <div class="col-md-4">
      <div class="ads-card messages">
        <h6 class="fw-bold mb-2"><i class="fe-message-circle text-success me-1"></i> Message Status</h6>
        <div class="metric-row">
          <div class="metric-item">
            <div class="metric-value" id="totalMessages"><?php echo e(number_format($totalMessages ?? 0)); ?></div>
            <div class="metric-label">Total Messages</div>
          </div>
          <div class="metric-item">
            <div class="metric-value" id="todayMessages"><?php echo e(number_format($todayMessages ?? 0)); ?></div>
            <div class="metric-label">Today</div>
          </div>
          <div class="metric-item">
            <div class="metric-value text-warning" id="unreadMessages"><?php echo e(number_format($unreadMessages ?? 0)); ?></div>
            <div class="metric-label">Unread</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="ads-card spend">
        <h6 class="fw-bold mb-2"><i class="fe-dollar-sign text-warning me-1"></i> Today's Ad Spend</h6>
        <div class="metric-value">$ <span id="totalAdSpend"><?php echo e(number_format($totalAdSpendToday ?? 0, 2)); ?></span></div>
        <small class="text-muted">Facebook + Google + TikTok</small>
      </div>
    </div>
    <div class="col-md-4">
      <div class="ads-card spend">
        <h6 class="fw-bold mb-2"><i class="fe-trending-down text-danger me-1"></i> Expenses</h6>
        <div class="metric-value">৳ <span id="todayExpenses"><?php echo e(number_format($todayExpenses ?? 0, 2)); ?></span></div>
        <small class="text-muted">Today's expense | Monthly: ৳ <?php echo e(number_format($monthlyExpenses ?? 0, 2)); ?></small>
      </div>
    </div>
  </div>

  
  <div class="row">
    <div class="col-12">
      <h5 class="fw-bold mb-2 mt-4">Facebook Ads Result</h5>
      <div class="ads-card facebook position-relative p-4" style="border-left-width:6px;">
        <?php if(($facebook['success'] ?? false)): ?>
          <div class="metric-row">
            <div class="metric-item"><div class="metric-value text-primary">$<?php echo e(number_format($facebook['spend'] ?? 0, 2)); ?></div><div class="metric-label">Spend</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($facebook['clicks'] ?? 0)); ?></div><div class="metric-label">Clicks</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($facebook['impressions'] ?? 0)); ?></div><div class="metric-label">Impressions</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($facebook['reach'] ?? 0)); ?></div><div class="metric-label">Reach</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($facebook['conversions'] ?? 0)); ?></div><div class="metric-label">Conversions</div></div>
          </div>
          <a href="<?php echo e(route('admin.ads_analytics.facebook')); ?>" class="btn btn-sm btn-outline-primary mt-3">View Full Facebook Result</a>
        <?php else: ?>
          <p class="text-muted mb-2"><?php echo e($facebook['message'] ?? 'Configure API'); ?></p>
          <a href="<?php echo e(route('admin.ads_analytics.settings')); ?>" class="btn btn-sm btn-outline-primary">Settings</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <h5 class="fw-bold mb-2 mt-4">Google Ads Result</h5>
      <div class="ads-card google position-relative p-4" style="border-left-width:6px;">
        <?php if(($google['success'] ?? false)): ?>
          <div class="metric-row">
            <div class="metric-item"><div class="metric-value text-danger">$<?php echo e(number_format($google['spend'] ?? 0, 2)); ?></div><div class="metric-label">Spend</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($google['clicks'] ?? 0)); ?></div><div class="metric-label">Clicks</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($google['impressions'] ?? 0)); ?></div><div class="metric-label">Impressions</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($google['conversions'] ?? 0)); ?></div><div class="metric-label">Conversions</div></div>
          </div>
          <a href="<?php echo e(route('admin.ads_analytics.google')); ?>" class="btn btn-sm btn-outline-danger mt-3">View Full Google Result</a>
        <?php else: ?>
          <p class="text-muted mb-2"><?php echo e($google['message'] ?? 'Configure API'); ?></p>
          <a href="<?php echo e(route('admin.ads_analytics.settings')); ?>" class="btn btn-sm btn-outline-danger">Settings</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <h5 class="fw-bold mb-2 mt-4">TikTok Ads Result</h5>
      <div class="ads-card tiktok position-relative p-4" style="border-left-width:6px;">
        <?php if(($tiktok['success'] ?? false)): ?>
          <div class="metric-row">
            <div class="metric-item"><div class="metric-value">$<?php echo e(number_format($tiktok['spend'] ?? 0, 2)); ?></div><div class="metric-label">Spend</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($tiktok['clicks'] ?? 0)); ?></div><div class="metric-label">Clicks</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($tiktok['impressions'] ?? 0)); ?></div><div class="metric-label">Impressions</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($tiktok['reach'] ?? 0)); ?></div><div class="metric-label">Reach</div></div>
            <div class="metric-item"><div class="metric-value"><?php echo e(number_format($tiktok['conversions'] ?? 0)); ?></div><div class="metric-label">Conversions</div></div>
          </div>
          <a href="<?php echo e(route('admin.ads_analytics.tiktok')); ?>" class="btn btn-sm btn-outline-dark mt-3">View Full TikTok Result</a>
        <?php else: ?>
          <p class="text-muted mb-2"><?php echo e($tiktok['message'] ?? 'Configure API'); ?></p>
          <a href="<?php echo e(route('admin.ads_analytics.settings')); ?>" class="btn btn-sm btn-outline-dark">Settings</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <p class="text-muted small mt-2">
    <i class="fe-info"></i> Data is cached every 5 minutes. Click Refresh for fresh data. Configure API credentials in Settings for live updates.
  </p>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
// প্রতি ৬০ সেকেন্ডে অটো রিফ্রেশ (লাইভ আপডেট)
setInterval(function() {
  fetch('<?php echo e(route("admin.ads_analytics.live_data")); ?>', {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    document.getElementById('totalMessages').textContent = parseInt(data.totalMessages || 0).toLocaleString();
    document.getElementById('todayMessages').textContent = parseInt(data.todayMessages || 0).toLocaleString();
    document.getElementById('unreadMessages').textContent = parseInt(data.unreadMessages || 0).toLocaleString();
    document.getElementById('totalAdSpend').textContent = parseFloat(data.totalAdSpendToday || 0).toFixed(2);
    document.getElementById('todayExpenses').textContent = parseFloat(data.todayExpenses || 0).toFixed(2);
    // Platform cards - আপনি চাইলে এখানে DOM আপডেট যুক্ত করতে পারবেন
  })
  .catch(() => {});
}, 60000);
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/backEnd/ads_analytics/dashboard.blade.php ENDPATH**/ ?>