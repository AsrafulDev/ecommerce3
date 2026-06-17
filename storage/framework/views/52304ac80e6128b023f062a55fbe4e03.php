<?php $__env->startSection('title','Invoice #' . $order->invoice_id); ?>
<?php $__env->startSection('content'); ?>

<?php
    // ১. পেমেন্ট ইনফো নেওয়া
    $payment = \App\Models\Payment::where('order_id', $order->id)->orderBy('id','desc')->first();

    $gateway_status = $payment ? strtolower(trim($payment->payment_status)) : ''; 
    $payment_method = $payment ? strtolower(trim($payment->payment_method)) : strtolower(trim($order->payment_method ?? ''));
    
    $admin_status   = strtolower(trim($order->payment_status ?? ''));
    $order_status   = strtolower(trim($order->status ?? ''));

    $grand_total = $order->amount;
    $paid_amount = 0;

    // পেমেন্ট রেকর্ড থেকে আসল টাকাটা বের করি
    if ($payment && !in_array($gateway_status, ['failed','cancel','cancelled','rejected'])) {
        $paid_amount = $payment->amount;
    }

    // COD FIX
    $is_cod = in_array($payment_method, ['cod','cash','cash_on_delivery','hand cash','hand_cash']);

    $is_order_completed =
        in_array($order_status, ['completed','delivered']) ||
        in_array($admin_status, ['completed','delivered']);

    if ($is_cod && !$is_order_completed) {
        if ($paid_amount >= $grand_total) {
            $paid_amount = 0;
        }
    }

    // ADMIN PRIORITY
    if ($is_order_completed) {
        $paid_amount = $grand_total;
    }
    elseif (($paid_amount == 0 || !$payment) && in_array($admin_status, ['paid','success','approved'])) {
        $paid_amount = $grand_total;
    }

    // Due
    $due_amount = max(0, $grand_total - $paid_amount);
    $subtotal = ($order->amount + $order->discount) - $order->shipping_charge;

    // ⭐ ডিজিটাল ডাউনলোড লজিক — যদি ফুল পেইড হয় তবেই ডাউনলোড লিংক দেখাবে
    $is_fully_paid = ($paid_amount >= $grand_total);
    $downloads = $is_fully_paid ? \App\Models\DigitalDownload::where('order_id', $order->id)->get() : collect();
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

    .invoice-wrapper { background: #f8fafc; padding: 30px 15px; font-family: 'Plus Jakarta Sans', sans-serif; }
    #invoice-pdf-area { background: #fff; max-width: 850px; margin: 0 auto; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .inv-container { padding: 40px; }
    .inv-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px; margin-bottom: 40px; }
    .inv-logo img { width: 150px; height: auto; margin-bottom: 15px; }
    .inv-title h1 { font-size: 28px; font-weight: 800; color: #0f172a; margin: 0; }
    .inv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .info-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px; }
    .info-val { font-size: 14px; color: #1e293b; line-height: 1.5; }
    .table-responsive { margin: 30px 0; }
    .inv-table { width: 100%; border-collapse: collapse; }
    .inv-table th { background: #f1f5f9; padding: 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569; }
    .inv-table td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .sum-wrapper { display: flex; justify-content: flex-end; }
    .sum-box { width: 100%; max-width: 320px; }
    .sum-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
    .total-row { border-top: 2px solid #0f172a; margin-top: 10px; padding-top: 15px; font-weight: 800; font-size: 18px; color: #000; }
    .payment-badge-box { background: #0f172a; color: #fff; padding: 15px; border-radius: 8px; margin-top: 15px; }
    .status-tag { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; margin-top: 8px; }
    .bg-paid-light { background: #dcfce7; color: #15803d; }
    .bg-due-light { background: #fee2e2; color: #b91c1c; }

    /* ডিজিটাল আইটেম ডাউনলোড বক্স */
    .digital-download-box {
        max-width: 850px;
        margin: 20px auto;
        background: #f0f9ff;
        border: 1px dashed #0ea5e9;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    .dl-btn {
        display: inline-block;
        background: #0284c7;
        color: #fff;
        padding: 10px 25px;
        border-radius: 50px;
        text-decoration: none !important;
        font-weight: 700;
        margin-top: 10px;
        transition: 0.3s;
    }
    .dl-btn:hover { background: #0369a1; transform: translateY(-2px); }

    @media print {
        .no-print { display: none !important; }
        body { background: white; }
        .invoice-wrapper { padding: 0; }
        #invoice-pdf-area { box-shadow: none; width: 100%; }
    }
</style>

<div class="invoice-wrapper">
    <div class="container no-print mb-4">
        <div class="d-flex justify-content-center gap-2">
            <a href="<?php echo e(route('customer.orders')); ?>" class="btn btn-dark btn-sm rounded-pill px-4">
               <i class="fa fa-arrow-left me-1"></i> Back
            </a>
            <button onclick="downloadPDF()" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                <i class="fa fa-download me-1"></i> Download Invoice
            </button>
        </div>
    </div>

    
    <?php if($is_fully_paid && $downloads->count() > 0): ?>
    <div class="digital-download-box no-print">
        <h6 class="fw-bold text-dark mb-1"><i class="fa fa-cloud-download me-2"></i> ডিজিটাল আইটেম প্রস্তুত!</h6>
        <p class="small text-muted mb-3">পেমেন্ট সফল হওয়ায় আপনার ফাইলগুলো ডাউনলোডের জন্য উন্মুক্ত করা হয়েছে।</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php $__currentLoopData = $downloads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('digital.download', $dl->token)); ?>" class="dl-btn">
                    Download: <?php echo e($dl->product->name); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>

    <div id="invoice-pdf-area">
        <div class="inv-container">
            <div class="inv-header">
                <div class="inv-logo">
                    <img src="<?php echo e(asset($generalsetting->white_logo)); ?>" alt="Logo">
                    <div class="info-val">
                        <strong><?php echo e($generalsetting->name); ?></strong><br>
                        <span class="text-muted small"><?php echo e($contact->address); ?></span><br>
                        <span class="text-muted small">Phone: <?php echo e($contact->phone); ?></span>
                    </div>
                </div>
                <div class="text-end">
                    <div class="inv-title"><h1>INVOICE</h1></div>
                    <p class="mb-0 fw-bold">#<?php echo e($order->invoice_id); ?></p>
                    <p class="text-muted small">Date: <?php echo e($order->created_at->format('d M, Y')); ?></p>
                </div>
            </div>

            <hr class="my-4" style="opacity: 0.1;">

            <div class="inv-grid">
                <div>
                    <span class="info-label">Customer Details</span>
                    <div class="info-val">
                        <strong class="d-block mb-1"><?php echo e($order->shipping ? $order->shipping->name : 'N/A'); ?></strong>
                        <?php echo e($order->shipping ? $order->shipping->phone : ''); ?><br>
                        <?php echo e($order->shipping ? $order->shipping->address : ''); ?>

                    </div>
                </div>
                <div class="text-end">
                    <span class="info-label">Payment Information</span>
                    <div class="info-val text-uppercase fw-bold"><?php echo e($payment_method); ?></div>
                    <span class="status-tag <?php echo e($paid_amount >= $grand_total ? 'bg-paid-light' : 'bg-due-light'); ?>">
                        <?php echo e($paid_amount >= $grand_total ? '✔ Verified Paid' : '✘ Payment Outstanding'); ?>

                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>Product Description</th>
                            <th class="text-center">Price</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $order->orderdetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td>
                                <span class="fw-bold d-block"><?php echo e($item->product_name); ?></span>
                                <?php
                                    $sizeDisplay = $item->size ? ($item->size->sizeName ?? $item->size->size_name ?? $item->size->name ?? null) : null;
                                    $colorDisplay = $item->color ? ($item->color->getDisplayName() ?? $item->color->colorName ?? $item->color->color_name ?? $item->color->name ?? null) : null;
                                    if (!$sizeDisplay && $item->product_size) {
                                        $s = \App\Models\Size::find($item->product_size);
                                        $sizeDisplay = $s ? ($s->sizeName ?? $s->size_name ?? null) : null;
                                    }
                                    if (!$colorDisplay && $item->product_color) {
                                        $c = \App\Models\Color::find($item->product_color);
                                        $colorDisplay = $c ? ($c->getDisplayName() ?? $c->colorName ?? $c->color_name ?? null) : null;
                                    }
                                ?>
                                <?php if($sizeDisplay): ?><small class="text-muted d-block">Size: <?php echo e($sizeDisplay); ?></small><?php endif; ?>
                                <?php if($colorDisplay): ?><small class="text-muted d-block">Color: <?php echo e($colorDisplay); ?></small><?php endif; ?>
                            </td>
                            <td class="text-center">৳<?php echo e(number_format($item->sale_price, 2)); ?></td>
                            <td class="text-center"><?php echo e($item->qty); ?></td>
                            <td class="text-end fw-bold">৳<?php echo e(number_format($item->sale_price * $item->qty, 2)); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <div class="sum-wrapper">
                <div class="sum-box">
                    <div class="sum-row">
                        <span class="text-muted">Subtotal</span>
                        <span>৳<?php echo e(number_format($subtotal, 2)); ?></span>
                    </div>
                    <div class="sum-row">
                        <span class="text-muted">Shipping Fee</span>
                        <span>৳<?php echo e(number_format($order->shipping_charge, 2)); ?></span>
                    </div>
                    <?php if($order->discount > 0): ?>
                    <div class="sum-row text-danger">
                        <span>Discount</span>
                        <span>-৳<?php echo e(number_format($order->discount, 2)); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="sum-row total-row">
                        <span>Grand Total</span>
                        <span>৳<?php echo e(number_format($grand_total, 2)); ?></span>
                    </div>

                    <div class="payment-badge-box">
                        <div class="sum-row border-0 p-0 mb-2">
                            <span style="color: #4ade80;">Paid Amount</span>
                            <span class="fw-bold">৳<?php echo e(number_format($paid_amount, 2)); ?></span>
                        </div>
                        <div class="sum-row border-0 p-0">
                            <span style="color: #fb7185;">Remaining Due</span>
                            <span class="fw-bold">৳<?php echo e(number_format($due_amount, 2)); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-4 text-center border-top">
                <p class="small text-muted mb-0">Thank you for your business! This is a computer-generated invoice.</p>
                <p class="fw-bold small text-uppercase mt-1"><?php echo e($generalsetting->name); ?></p>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
<script>
(function () {
    var orderId    = '<?php echo e($order->invoice_id); ?>';
    var storageKey = 'purchase_fired_' + orderId;

    // একই order refresh করলে duplicate fire বন্ধ করতে localStorage চেক
    if (localStorage.getItem(storageKey)) return;
    localStorage.setItem(storageKey, '1');

    <?php
        $purchaseItems = [];
        foreach ($order->orderdetails as $item) {
            $purchaseItems[] = [
                'item_id'       => (string) ($item->product_id ?? $item->id),
                'item_name'     => $item->product_name,
                'price'         => (float) $item->sale_price,
                'quantity'      => (int)   $item->qty,
                'item_category' => optional($item->product?->category)->name ?? '',
            ];
        }
        $couponCode  = $order->coupon_code ?? null;
        $eventId     = 'purchase_' . $order->invoice_id;
        $custPhone   = $order->shipping?->phone ?? '';
        $custName    = $order->shipping?->name  ?? '';
        $custAddress = $order->shipping?->address ?? '';
        $custArea    = $order->shipping?->area ?? '';
    ?>

    var purchaseItems  = <?php echo json_encode($purchaseItems, 15, 512) ?>;
    var grandTotal     = parseFloat("<?php echo e($order->amount); ?>") || 0;
    var shippingCharge = parseFloat("<?php echo e($order->shipping_charge); ?>") || 0;
    var discount       = parseFloat("<?php echo e($order->discount); ?>") || 0;
    var coupon         = <?php echo json_encode($couponCode, 15, 512) ?>;
    var paymentMethod  = "<?php echo e($payment_method); ?>";
    var eventId        = "<?php echo e($eventId); ?>";

    // Facebook browser cookies (CAPI deduplication-এর জন্য)
    var fbp = getCookie('_fbp');
    var fbc = getCookie('_fbc');
    function getCookie(name) {
        var m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return m ? m.pop() : '';
    }

    // ── GTM / GA4 — purchase event (full ecommerce + customer data) ──
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    window.dataLayer.push({
        event: 'purchase',
        ecommerce: {
            transaction_id: orderId,
            value:          grandTotal,
            tax:            0,
            shipping:       shippingCharge,
            currency:       'BDT',
            coupon:         coupon,
            payment_method: paymentMethod,
            items:          purchaseItems
        },
        user_data: {
            customer_id:  '<?php echo e($order->customer_id); ?>',
            name:         "<?php echo e(addslashes($custName)); ?>",
            phone:        "<?php echo e($custPhone); ?>",
            address:      "<?php echo e(addslashes($custAddress)); ?>",
            area:         "<?php echo e(addslashes($custArea)); ?>",
            fbp:          fbp,
            fbc:          fbc,
            client_ip:    '<?php echo e(request()->ip()); ?>'
        },
        order_info: {
            invoice_id:     orderId,
            order_id:       '<?php echo e($order->id); ?>',
            payment_method: paymentMethod,
            payment_status: '<?php echo e($payment ? $payment->payment_status : ""); ?>',
            grand_total:    grandTotal,
            shipping:       shippingCharge,
            discount:       discount,
            coupon:         coupon,
            item_count:     purchaseItems.length
        }
    });

    // ── Facebook Pixel — Purchase (browser-side, event_id দিয়ে CAPI-র সাথে deduplicate) ──
    if (typeof fbq === 'function') {
        fbq('track', 'Purchase', {
            value:        grandTotal,
            currency:     'BDT',
            content_ids:  purchaseItems.map(function(i){ return i.item_id; }),
            contents:     purchaseItems.map(function(i){ return { id: i.item_id, quantity: i.quantity, item_price: i.price }; }),
            content_type: 'product',
            num_items:    purchaseItems.length,
            order_id:     orderId
        }, { eventID: eventId });
    }

    // ── TikTok Pixel — CompletePayment ──
    if (typeof ttq !== 'undefined') {
        ttq.track('CompletePayment', {
            content_type: 'product',
            quantity:     purchaseItems.length,
            value:        grandTotal,
            currency:     'BDT',
            order_id:     orderId,
            contents:     purchaseItems.map(function(i){
                return { content_id: i.item_id, content_name: i.item_name, quantity: i.quantity, price: i.price };
            })
        });
    }
})();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('invoice-pdf-area');
    const invoice_id = "<?php echo e($order->invoice_id); ?>";
    const opt = {
        margin: [10, 10, 10, 10],
        filename: 'Invoice-' + invoice_id + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /media/asraful/F/project/20-code/All Code/Ecommerce3/ecommerce3/resources/views/frontEnd/layouts/customer/order_success.blade.php ENDPATH**/ ?>