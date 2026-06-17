<?php
    $primaryColor = optional($generalsetting)->primary_color ?? '#007bff';
?>
<div class="sidebar-cart-header" style="background: <?php echo e($primaryColor); ?>;">
    <button type="button" class="sidebar-cart-close" onclick="closeSidebarCart()" aria-label="বন্ধ করুন">
        <i class="fa-solid fa-times"></i>
    </button>
    <h3 class="sidebar-cart-title">আমার কার্ট</h3>
</div>
<div class="sidebar-cart-body">
    <?php if($cartContent->isEmpty()): ?>
        <div class="sidebar-cart-empty">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>আপনার কার্ট খালি</p>
            <a href="<?php echo e(route('shop')); ?>" class="sidebar-cart-checkout-btn" style="background: <?php echo e($primaryColor); ?>;">শপিং করুন</a>
        </div>
    <?php else: ?>
        <?php $__currentLoopData = $cartContent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="sidebar-cart-item">
                <div class="sidebar-cart-item-img">
                    <a href="<?php echo e(route('product', $item->options->slug ?? '#')); ?>">
                        <img src="<?php echo e(asset($item->options->image ?? 'public/uploads/default.webp')); ?>" alt="<?php echo e($item->name); ?>" />
                    </a>
                </div>
                <div class="sidebar-cart-item-details">
                    <a href="<?php echo e(route('product', $item->options->slug ?? '#')); ?>" class="sidebar-cart-item-title"><?php echo e(Str::limit($item->name, 45)); ?></a>
                    <p class="sidebar-cart-item-price">৳ <?php echo e(number_format($item->price, 0)); ?> × <?php echo e($item->qty); ?></p>
                    <?php if(!empty($item->options->old_price) && $item->options->old_price > $item->price): ?>
                        <?php $savings = ($item->options->old_price - $item->price) * $item->qty; ?>
                        <p class="sidebar-cart-item-savings">৳ <?php echo e(number_format($savings, 0)); ?> সাশ্রয়</p>
                    <?php endif; ?>
                    <div class="sidebar-cart-qty">
                        <button type="button" class="sidebar-qty-btn cart_decrement" data-id="<?php echo e($item->rowId); ?>">−</button>
                        <span class="sidebar-qty-num"><?php echo e($item->qty); ?></span>
                        <button type="button" class="sidebar-qty-btn cart_increment" data-id="<?php echo e($item->rowId); ?>">+</button>
                    </div>
                    <button type="button" class="sidebar-cart-item-remove cart_remove" data-id="<?php echo e($item->rowId); ?>" title="রিমুভ করুন">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</div>
<?php if(!$cartContent->isEmpty()): ?>
<div class="sidebar-cart-footer">
    <div class="sidebar-cart-total">
        <span class="sidebar-cart-total-label">সর্বমোট</span>
        <span class="sidebar-cart-total-amount">৳ <?php echo e(number_format($subtotal, 0)); ?></span>
    </div>
    <a href="<?php echo e(route('customer.checkout')); ?>" class="sidebar-cart-checkout-btn" style="background: <?php echo e($primaryColor); ?>;">অর্ডার করুন</a>
</div>
<?php endif; ?>
<?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/frontEnd/layouts/ajax/cart_sidebar.blade.php ENDPATH**/ ?>