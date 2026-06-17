<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    :root {
        --primary-color: #4e73df;
        --secondary-color: #858796;
        --success-color: #1cc88a;
        --info-color: #36b9cc;
        --warning-color: #f6c23e;
        --danger-color: #e74a3b;
        --dark-bg: #f8f9fc;
        --sidebar-bg: #ffffff;
        --text-color: #5a5c69;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--dark-bg);
        color: var(--text-color);
        overflow-x: hidden;
    }

    /* Cards */
    .dashboard-card {
        background: #fff;
        border-radius: 15px;
        padding: 25px;
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.05);
        height: 100%;
        transition: transform 0.2s;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
    }

    .card-icon-bg {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .trend-badge {
        font-size: 0.8rem;
        padding: 3px 8px;
        border-radius: 20px;
        background: #e6fffa;
        color: var(--success-color);
    }
    
    .trend-down {
        background: #ffebeb;
        color: var(--danger-color);
    }

    /* Table */
    .custom-table-container {
        background: #fff;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.05);
    }
    
    .table thead th {
        border-bottom: 2px solid #eaecf4;
        color: #4e73df;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
    }
    
    .table td {
        vertical-align: middle;
        padding: 15px 10px;
        color: #555;
    }

    .avatar-sm {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
    }

    .search-bar input {
        border-radius: 20px;
        border: none;
        padding: 10px 20px;
        background: #fff;
        box-shadow: 0 0.15rem 1rem 0 rgba(58,59,69,0.05);
        width: 300px;
    }

    .menu-toggle { 
        display: none; 
        font-size: 1.5rem; 
        cursor: pointer; 
        color: var(--primary-color); 
    }

    /* Chart Container */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        margin-top: 10px;
    }

    @media (max-width: 991px) {
        .search-bar input {
            width: 200px;
        }
        .menu-toggle { 
            display: block !important; 
        }
        .chart-container {
            height: 250px;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('header-search'); ?>
    <!-- Search bar will be shown in header -->
<?php $__env->stopPush(); ?>

<?php $__env->startPush('header-notifications'); ?>
    <?php if(isset($pendingOrders) && $pendingOrders > 0): ?>
    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
    <?php endif; ?>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('notification-items'); ?>
    <!-- Pending Orders Notification (Dashboard specific) -->
    <?php if(isset($pendingOrders) && $pendingOrders > 0): ?>
    <li><hr class="dropdown-divider"></li>
    <li>
        <div class="px-3 py-2 border-bottom">
            <h6 class="mb-1 fw-bold text-dark">
                <i class="fas fa-shopping-cart text-info me-2"></i>
                নতুন অর্ডার
            </h6>
        </div>
    </li>
    <li>
        <a class="dropdown-item py-3" href="<?php echo e(route('vendor.orders')); ?>">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-bell text-info"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <p class="mb-0 fw-semibold">নতুন অর্ডার</p>
                    <small class="text-muted"><?php echo e($pendingOrders); ?> টি পেন্ডিং অর্ডার</small>
                </div>
                <div class="flex-shrink-0">
                    <span class="badge bg-danger rounded-pill"><?php echo e($pendingOrders); ?></span>
                </div>
            </div>
        </a>
    </li>
    <?php endif; ?>
    
    <!-- Empty State -->
    <?php if($vendor->verification_status == 'approved' && (!isset($pendingOrders) || $pendingOrders == 0)): ?>
    <li>
        <div class="px-3 py-4 text-center text-muted">
            <i class="fas fa-check-circle fa-2x mb-2 opacity-25"></i>
            <p class="mb-0 small">কোন নোটিফিকেশন নেই</p>
        </div>
    </li>
    <?php endif; ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-secondary mb-1 text-uppercase font-size-sm fw-bold" style="font-size: 12px;">মোট বিক্রি (মাসিক)</p>
                    <h3 class="fw-bold mb-0">৳ <?php echo e(number_format($monthlySales ?? 0, 0)); ?></h3>
                    <?php if(isset($salesGrowth) && $salesGrowth != 0): ?>
                    <small class="trend-badge <?php echo e($salesGrowth < 0 ? 'trend-down' : ''); ?> mt-2 d-inline-block">
                        <i class="fas fa-arrow-<?php echo e($salesGrowth > 0 ? 'up' : 'down'); ?>"></i> 
                        <?php echo e(number_format(abs($salesGrowth), 1)); ?>% <?php echo e($salesGrowth > 0 ? 'বৃদ্ধি' : 'হ্রাস'); ?>

                    </small>
                    <?php else: ?>
                    <small class="text-secondary mt-2 d-inline-block" style="font-size: 12px;">গত মাস: ৳<?php echo e(number_format($lastMonthSales ?? 0, 0)); ?></small>
                    <?php endif; ?>
                </div>
                <div class="card-icon-bg" style="background: #e8f0fe; color: #4e73df;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-secondary mb-1 text-uppercase fw-bold" style="font-size: 12px;">নতুন অর্ডার</p>
                    <h3 class="fw-bold mb-0"><?php echo e($newOrders ?? 0); ?> টি</h3>
                    <small class="trend-badge mt-2 d-inline-block">
                        <i class="fas fa-arrow-up"></i> 
                        <?php echo e($pendingOrders ?? 0); ?> পেন্ডিং
                    </small>
                </div>
                <div class="card-icon-bg" style="background: #e6fffa; color: #1cc88a;">
                    <i class="fas fa-shopping-basket"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-secondary mb-1 text-uppercase fw-bold" style="font-size: 12px;">রিটার্ন এসেছে</p>
                    <h3 class="fw-bold mb-0"><?php echo e($returnOrders ?? 0); ?> টি</h3>
                    <?php if(($returnOrders ?? 0) > 0): ?>
                    <small class="trend-badge trend-down mt-2 d-inline-block">
                        <i class="fas fa-arrow-down"></i> 
                        অ্যাকশন প্রয়োজন
                    </small>
                    <?php else: ?>
                    <small class="text-secondary mt-2 d-inline-block" style="font-size: 12px;">কোন রিটার্ন নেই</small>
                    <?php endif; ?>
                </div>
                <div class="card-icon-bg" style="background: #ffebeb; color: #e74a3b;">
                    <i class="fas fa-undo"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-secondary mb-1 text-uppercase fw-bold" style="font-size: 12px;">পেন্ডিং রিকোয়েস্ট</p>
                    <h3 class="fw-bold mb-0"><?php echo e($pendingOrders ?? 0); ?> টি</h3>
                    <small class="text-secondary mt-2 d-inline-block" style="font-size: 12px;">অ্যাকশন প্রয়োজন</small>
                </div>
                <div class="card-icon-bg" style="background: #fff8e1; color: #f6c23e;">
                    <i class="fas fa-comments"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0 text-primary">বিক্রয় পরিসংখ্যান</h5>
                    <select class="form-select form-select-sm w-auto border-0 bg-light" id="chartPeriod">
                        <option value="week">এই সপ্তাহ</option>
                        <option value="month">এই মাস</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dashboard-card">
                <h5 class="fw-bold mb-4 text-primary">জনপ্রিয় পণ্য</h5>
                
                <?php $__empty_1 = true; $__currentLoopData = $popularProducts ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-light p-2 rounded me-3">
                        <?php if($item->product && $item->product->image && $item->product->image->image): ?>
                            <img src="<?php echo e(asset($item->product->image->image)); ?>" alt="<?php echo e($item->product->name); ?>" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
                        <?php else: ?>
                            <i class="fas fa-box fa-lg text-secondary"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-semibold"><?php echo e($item->product->name ?? 'N/A'); ?></h6>
                        <small class="text-muted"><?php echo e($item->total_sold ?? 0); ?> টি বিক্রি</small>
                    </div>
                    <span class="fw-bold text-success">৳<?php echo e(number_format($item->total_revenue ?? 0, 0)); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                    <p>কোন জনপ্রিয় পণ্য নেই</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="custom-table-container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0 text-primary">সাম্প্রতিক অর্ডার</h5>
            <a href="<?php echo e(route('vendor.orders')); ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">সব দেখুন</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>আইডি</th>
                        <th>কাস্টমার</th>
                        <th>তারিখ</th>
                        <th>অ্যামাউন্ট</th>
                        <th>পেমেন্ট</th>
                        <th>স্ট্যাটাস</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $recentOrders ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="fw-bold">#<?php echo e($order->invoice_id ?? $order->id); ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if($order->customer && $order->customer->image): ?>
                                    <img src="<?php echo e(asset($order->customer->image)); ?>" class="avatar-sm me-2" alt="">
                                <?php else: ?>
                                    <div class="avatar-sm bg-light d-flex align-items-center justify-content-center me-2">
                                        <i class="fas fa-user text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                                <?php echo e($order->customer->name ?? 'N/A'); ?>

                            </div>
                        </td>
                        <td><?php echo e($order->created_at ? $order->created_at->format('d M, Y') : 'N/A'); ?></td>
                        <td>৳<?php echo e(number_format($order->amount ?? 0, 2)); ?></td>
                        <td>
                            <?php if($order->payment): ?>
                                <?php
                                    $paymentMethod = strtolower($order->payment->payment_method ?? '');
                                ?>
                                <?php if(str_contains($paymentMethod, 'card') || str_contains($paymentMethod, 'visa')): ?>
                                    <span class="badge bg-light text-dark border"><i class="fab fa-cc-visa text-primary"></i> কার্ড</span>
                                <?php elseif(str_contains($paymentMethod, 'mastercard')): ?>
                                    <span class="badge bg-light text-dark border"><i class="fab fa-cc-mastercard text-danger"></i> মাস্টারকার্ড</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border"><i class="fas fa-money-bill text-success"></i> <?php echo e(ucfirst($paymentMethod)); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $status = (string)($order->order_status ?? '');
                                $statusClass = '';
                                $statusText = '';
                                
                                if($status == '6') {
                                    $statusClass = 'bg-success bg-opacity-10 text-success';
                                    $statusText = 'ডেলিভারড';
                                } elseif($status == '11') {
                                    $statusClass = 'bg-danger bg-opacity-10 text-danger';
                                    $statusText = 'বাতিল';
                                } elseif(in_array($status, ['1', '2', '3'])) {
                                    $statusClass = 'bg-warning bg-opacity-10 text-warning';
                                    $statusText = 'পেন্ডিং';
                                } elseif($status == '4') {
                                    $statusClass = 'bg-info bg-opacity-10 text-info';
                                    $statusText = 'শিপিং';
                                } else {
                                    $statusClass = 'bg-secondary bg-opacity-10 text-secondary';
                                    $statusText = 'অন্যান্য';
                                }
                            ?>
                            <span class="badge <?php echo e($statusClass); ?> px-3"><?php echo e($statusText); ?></span>
                        </td>
                        <td>
                            <a href="<?php echo e(route('vendor.orders')); ?>?search=<?php echo e($order->invoice_id ?? $order->id); ?>" class="text-muted">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-shopping-cart fa-3x mb-3 opacity-25"></i>
                            <p>কোন অর্ডার নেই</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Toggle Sidebar
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.toggle('show');
        if (overlay) overlay.classList.toggle('show');
    }

    // Chart Data
    const weeklyData = {
        labels: <?php echo json_encode($weeklyLabels ?? []); ?>,
        data: <?php echo json_encode($weeklySales ?? []); ?>

    };
    
    const monthlyData = {
        labels: <?php echo json_encode($monthlyLabels ?? []); ?>,
        data: <?php echo json_encode($monthlySalesData ?? []); ?>

    };

    // Initialize Sales Chart
    const ctx = document.getElementById('salesChart');
    let salesChart = null;
    
    function updateChart(period) {
        const chartData = period === 'month' ? monthlyData : weeklyData;
        
        if (salesChart) {
            salesChart.destroy();
        }
        
        if (ctx) {
            salesChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'বিক্রয় (টাকা)',
                        data: chartData.data,
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: '#4e73df',
                        borderWidth: 2,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4e73df',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'বিক্রয়: ৳' + context.parsed.y.toLocaleString('bn-BD');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [2, 4],
                                color: '#eaecf4',
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return '৳' + value.toLocaleString('bn-BD');
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        }
    }

    // Initialize with weekly data
    if (ctx) {
        updateChart('week');
    }

    // Handle period change
    const chartPeriod = document.getElementById('chartPeriod');
    if (chartPeriod) {
        chartPeriod.addEventListener('change', function() {
            updateChart(this.value);
        });
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('vendor.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/vendor/dashboard.blade.php ENDPATH**/ ?>