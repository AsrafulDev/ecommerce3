
<?php $__env->startSection('title', 'System License Status'); ?>

<?php $__env->startSection('content'); ?>


<style>
    :root {
        --primary-color: #4f46e5;
        --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
        --danger-gradient: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
        --card-bg: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
    }

    .license-container {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        padding-top: 20px;
        padding-bottom: 40px;
    }

    /* Status Hero Card */
    .status-hero {
        border-radius: 16px;
        padding: 40px;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }
    
    .bg-valid { background: var(--success-gradient); }
    .bg-invalid { background: var(--danger-gradient); }

    .status-icon-large {
        font-size: 60px;
        margin-bottom: 15px;
        opacity: 0.9;
    }

    .status-bg-icon {
        position: absolute;
        right: -20px;
        bottom: -20px;
        font-size: 200px;
        opacity: 0.1;
        transform: rotate(-15deg);
    }

    /* Detail Cards */
    .pro-card {
        background: var(--card-bg);
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        height: 100%;
        transition: transform 0.2s;
    }
    
    .pro-card-header {
        padding: 20px 25px;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 700;
        color: var(--text-main);
        display: flex;
        align-items: center;
    }

    .pro-card-body {
        padding: 25px;
    }

    /* Info Rows */
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        padding-bottom: 18px;
        border-bottom: 1px dashed #e2e8f0;
    }
    .info-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .info-label {
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-weight: 600;
        color: var(--text-main);
        font-size: 15px;
    }

    /* License Key Box */
    .license-key-box {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        padding: 12px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        color: var(--primary-color);
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 5px;
    }
    
    .btn-copy {
        background: white;
        border: 1px solid #e2e8f0;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
    }
    .btn-copy:hover { background: #eff6ff; color: var(--primary-color); }

    /* Support Section */
    .support-list li {
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        color: var(--text-main);
    }
    .support-icon {
        width: 35px;
        height: 35px;
        background: #eff6ff;
        color: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .btn-support-wa {
        background: #25D366;
        color: white;
        display: block;
        text-align: center;
        padding: 12px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: 0.2s;
        margin-bottom: 10px;
    }
    .btn-support-wa:hover { background: #1ebc57; color: white; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3); }

    .watermark-footer {
        text-align: center;
        margin-top: 40px;
        color: #94a3b8;
        font-size: 12px;
    }
</style>

<div class="content-wrapper">
    <div class="container-fluid license-container">
        
        <div class="d-flex align-items-center mb-4">
            <h3 class="m-0 fw-bold text-dark">
                <i class="fas fa-shield-alt text-primary me-2"></i> License Management
            </h3>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                
                <?php
                    $isValid = isset($licenseData['status']) && $licenseData['status'] == 'valid';
                ?>

                <div class="status-hero <?php echo e($isValid ? 'bg-valid' : 'bg-invalid'); ?>">
                    <i class="fa <?php echo e($isValid ? 'fa-shield-check' : 'fa-exclamation-triangle'); ?> status-bg-icon"></i>
                    
                    <div class="status-icon-large">
                        <i class="fa <?php echo e($isValid ? 'fa-check-circle' : 'fa-times-circle'); ?>"></i>
                    </div>
                    
                    <h2 class="fw-bold mb-2"><?php echo e($isValid ? 'System Fully Activated' : 'License Invalid or Expired'); ?></h2>
                    <p class="mb-0 opacity-75">
                        <?php echo e($isValid ? 'Your application is running on a verified authorized license.' : 'Please contact support immediately to restore service.'); ?>

                    </p>
                </div>

                <div class="row g-4">
                    
                    
                    <div class="col-md-7">
                        <div class="pro-card">
                            <div class="pro-card-header">
                                <i class="fas fa-server me-2 text-muted"></i> Configuration Details
                            </div>
                            <div class="pro-card-body">
                                
                                <div class="info-row">
                                    <span class="info-label">Environment Host</span>
                                    <span class="info-value"><?php echo e(request()->getHost()); ?></span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Registered Domain</span>
                                    <span class="info-value text-primary"><?php echo e($licenseData['domain_name'] ?? 'Unverified'); ?></span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Expiry Date</span>
                                    <?php if(isset($licenseData['expiry_date'])): ?>
                                        <span class="badge bg-light text-dark border px-3 py-2">
                                            <?php echo e(\Carbon\Carbon::parse($licenseData['expiry_date'])->format('d M, Y')); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unknown</span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4">
                                    <span class="info-label d-block mb-2">License Key Hash</span>
                                    <div class="license-key-box">
                                        <span id="licenseKeyText" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 80%;">
                                            <?php echo e(env('LICENSE_KEY', 'NO_KEY_FOUND_IN_ENV')); ?>

                                        </span>
                                        <button class="btn-copy" onclick="copyLicense()" title="Copy Key">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    
                    <div class="col-md-5">
                        <div class="pro-card">
                            <div class="pro-card-header">
                                <i class="fas fa-headset me-2 text-muted"></i> Premium Support
                            </div>
                            <div class="pro-card-body">
                                <div class="d-flex align-items-center mb-4">
                                   <img src="https://www.creativedesign.com.bd/assets/images/1766157302fav.png" alt="Logo" class="rounded-circle me-3 border border-primary p-1" style="width:50px; height:50px; object-fit: cover;">
                                    <div>
                                        <h6 class="fw-bold mb-0">Creative Design BD</h6>
                                        <small class="text-muted">Official Vendor</small>
                                    </div>
                                </div>

                                <ul class="list-unstyled support-list">
                                    <li>
                                        <div class="support-icon"><i class="fas fa-envelope"></i></div>
                                        <span>info@creativedesign.com.bd</span>
                                    </li>
                                    <li>
                                        <div class="support-icon"><i class="fas fa-globe"></i></div>
                                        <span>www.creativedesign.com.bd</span>
                                    </li>
                                </ul>

                                <div class="mt-4">
                                    <a href="https://wa.me/8801849832178" target="_blank" class="btn-support-wa">
                                        <i class="fab fa-whatsapp me-2"></i> Chat on WhatsApp
                                    </a>
                                    <a href="https://creativedesign.com.bd/user/support-tickets" target="_blank" class="btn btn-outline-secondary w-100 py-2 fw-bold" style="border-radius: 8px;">
                                        <i class="fas fa-ticket-alt me-2"></i> Open Support Ticket
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                
                <div class="watermark-footer">
                    <i class="fas fa-lock me-1"></i> Secured by Creative Design Licensing System v2.0
                </div>

            </div>
        </div>
    </div>
</div>


<script>
    function copyLicense() {
        const keyText = document.getElementById('licenseKeyText').innerText;
        navigator.clipboard.writeText(keyText).then(() => {
            // Visual Feedback
            const btn = document.querySelector('.btn-copy');
            btn.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => {
                btn.innerHTML = '<i class="far fa-copy"></i>';
            }, 2000);
        });
    }
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/backEnd/license/info.blade.php ENDPATH**/ ?>