
<?php $__env->startSection('title', 'Manual Fraud Check'); ?>

<?php $__env->startSection('content'); ?>

<style>
    /* Success Circle */
    .success-circle {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        border: 12px solid #28a745;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e9f7ef;
        margin: 20px auto;
    }

    /* Courier Logo */
    .courier-logo {
        width: 60px;
        height: 45px;
        object-fit: contain;
        margin-right: 8px;
        border-radius: 4px;
    }

    /* Status Box */
    .status-box {
        padding: 12px;
        border-radius: 10px;
        text-align: center;
        margin-top: 15px;
    }
    .status-green { border: 2px solid green; color: green; background: #eaffea; }
    .status-blue { border: 2px solid #0d6efd; color: #0d6efd; background: #eef6ff; }
    .status-orange { border: 2px solid orange; color: orange; background: #fff8e1; }
    .status-red { border: 2px solid red; color: red; background: #ffeeee; }

    /* Table Fix */
    table thead tr th {
        background: #2e8b57 !important;
        color: white !important;
    }

    /* Success Rate Round Badge */
    .rate-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: 4px solid #28a745;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
        font-weight: bold;
        color: #28a745;
        background: #e9ffe9;
        font-size: 14px;
    }
</style>

<div class="container-fluid py-4">
    <div class="card shadow-sm p-4">

        <h4 class="text-center fw-bold mb-4">
            আপনার যাচাই করতে চাওয়া মোবাইল নাম্বারটি দিয়ে সার্চ দিন
        </h4>

        
        
        <form action="<?php echo e(route('manualFraud.check')); ?>" method="POST" class="text-center mb-4">
            <?php echo csrf_field(); ?>
            <div class="input-group justify-content-center" style="max-width:400px; margin:auto;">
                <input type="text" name="mobile" class="form-control text-center"
                    value="<?php echo e($mobile ?? ''); ?>" placeholder="017XXXXXXXX" required>
                <button class="btn btn-success px-4">সার্চ দিন</button>
            </div>
        </form>

        
        <?php if(session('error')): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="alert alert-danger text-center">
                        <strong><?php echo e(session('error')); ?></strong>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <?php if(isset($data) && !empty($data)): ?>
        <div class="row">

            
            <div class="col-md-4">
                <div class="card shadow-sm p-4">

                    <h5 class="fw-bold bg-success text-white py-2 rounded text-center">
                        মোট সফলতার হার
                    </h5>

                    <h3 class="text-center fw-bold text-success mt-3">
                        # <?php echo e($mobile); ?>

                    </h3>

                    <?php
                        $totalParcels = (int) ($data['total_parcels'] ?? 0);
                        $totalDelivered = (int) ($data['total_delivered'] ?? 0);
                        $totalCancel = (int) ($data['total_cancel'] ?? 0);
                        $overallRate = $totalParcels > 0 ? round(($totalDelivered / $totalParcels) * 100) : null;
                        $rateText = $overallRate !== null ? $overallRate.'%' : 'N/A';
                    ?>

                    
                    <div class="success-circle" style="border-color: <?php echo e($overallRate < 50 ? '#dc3545' : ($overallRate < 80 ? '#fd7e14' : '#28a745')); ?>">
                        <div class="text-center">
                            <span class="fw-bold fs-2" style="color: <?php echo e($overallRate < 50 ? '#dc3545' : ($overallRate < 80 ? '#fd7e14' : '#28a745')); ?>">
                                <?php echo e($rateText); ?>

                            </span>
                            <br>
                            <small class="text-muted" style="font-size: 12px;">(<?php echo e($totalParcels); ?> টি অর্ডার)</small>
                        </div>
                    </div>

                    
                    <?php if($overallRate !== null): ?>
                        <?php
                            if ($overallRate >= 90) {
                                $class = "status-green";
                                $msg = "✔ নিরাপদ - ঝুঁকিমুক্ত অবস্থা 😎";
                                $desc = "এই কাস্টমারের সফলতার হার চমৎকার। নিশ্চিন্তে অর্ডার প্রসেস করুন।";
                            }
                            elseif ($overallRate >= 70) {
                                $class = "status-blue";
                                $msg = "ℹ️ ভালো - তবে সতর্ক থাকুন 🙂";
                                $desc = "সফলতার হার ভালো, তবে লোকেশন বা অন্য বিষয়গুলো চেক করে নিন।";
                            }
                            elseif ($overallRate >= 40) {
                                $class = "status-orange";
                                $msg = "⚠ ঝুঁকি আছে – কনফার্ম হয়ে নিন ⚠";
                                $desc = "রিটার্নের হার বেশি। অবশ্যই ডেলিভারি চার্জ অগ্রিম নিন।";
                            }
                            else {
                                $class = "status-red";
                                $msg = "❗ উচ্চ ঝুঁকি – অর্ডার না নেওয়াই ভালো ❗";
                                $desc = "এই কাস্টমারের বেশিরভাগ পার্সেল ক্যানসেল হয়। সাবধান!";
                            }
                        ?>

                        <div class="status-box <?php echo e($class); ?>">
                            <h5 class="fw-bold"><?php echo e($msg); ?></h5>
                            <p class="mb-0"><?php echo e($desc); ?></p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            
            <div class="col-md-8">
                <div class="card shadow-sm p-3">

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle">

                            <thead>
                                <tr>
                                    <th>কুরিয়ার</th>
                                    <th>মোট অর্ডার</th>
                                    <th>সফল</th>
                                    <th>বাতিল</th>
                                    <th>হার</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php
                                    $apiCouriers = $data['apis'] ?? [];
                                    $courierMapping = [
                                        'Pathao'  => ['key' => 'pathao', 'name' => 'Pathao', 'logo' => 'pathao-logo.png'],
                                        'Redex'   => ['key' => 'redx', 'name' => 'RedX', 'logo' => 'redx-logo.png'],
                                        'CarryBee' => ['key' => 'carrybee', 'name' => 'CarryBee', 'logo' => 'carrybee-logo.webp'],
                                    ];

                                    $myLogos = [
                                        'pathao'    => asset('public/assets/images/courier/pathao-logo.png'),
                                        'redx'      => asset('public/assets/images/courier/redx-logo.png'),
                                        'carrybee'  => asset('public/assets/images/courier/carrybee-logo.webp'),
                                    ];
                                ?>

                                <?php $__currentLoopData = $courierMapping; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $apiName => $mapped): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                    <?php
                                        $info = $apiCouriers[$apiName] ?? [];
                                        $s = (int) ($info['total_delivered_parcels'] ?? 0);
                                        $c = (int) ($info['total_cancelled_parcels'] ?? 0);
                                        $t = (int) ($info['total_parcels'] ?? ($s + $c));
                                        $rate = $t > 0 ? round(($s/$t)*100) : 0;
                                        
                                        $logo = $myLogos[$mapped['key']] ?? null;
                                        $name = $mapped['name'];
                                    ?>

                                    <tr>
                                        <td class="text-start ps-4">
                                            <?php if($logo): ?>
                                                <img src="<?php echo e($logo); ?>" class="courier-logo" alt="<?php echo e($name); ?>">
                                            <?php endif; ?>
                                            <span class="fw-bold text-dark"><?php echo e($name); ?></span>
                                        </td>

                                        <td class="fw-bold"><?php echo e($t); ?></td>
                                        <td class="text-success fw-bold"><?php echo e($s); ?></td>
                                        <td class="text-danger fw-bold"><?php echo e($c); ?></td>

                                        <td>
                                            <?php
                                                $borderColor = $rate < 50 ? '#dc3545' : ($rate < 80 ? '#fd7e14' : '#28a745');
                                                $bgColor     = $rate < 50 ? '#ffeeee' : ($rate < 80 ? '#fff8e1' : '#e9ffe9');
                                                $textColor   = $rate < 50 ? '#dc3545' : ($rate < 80 ? '#fd7e14' : '#28a745');
                                            ?>

                                            <div class="rate-circle" style="border-color: <?php echo e($borderColor); ?>; background: <?php echo e($bgColor); ?>; color: <?php echo e($textColor); ?>">
                                                <?php echo e($rate); ?>%
                                            </div>

                                            <small class="d-block mt-1 text-muted" style="font-size: 11px;">
                                                <?php if($t == 0): ?> তথ্য নেই
                                                <?php elseif($rate == 100): ?> চমৎকার
                                                <?php elseif($rate >= 80): ?> ভালো
                                                <?php elseif($rate >= 50): ?> সাধারণ
                                                <?php else: ?> ঝুঁকিপূর্ণ
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                    </tr>

                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        </div>
        <?php endif; ?>

    </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /media/asraful/F/project/20-code/All Code/Ecommerce3/ecommerce3/resources/views/backEnd/fraud/manual_check.blade.php ENDPATH**/ ?>