<?php
    $subtotalRaw = Cart::instance('pos_shopping')->subtotal();
    $subtotalNum = (float) preg_replace('/[^\d.]/', '', (string) $subtotalRaw);
    $shippingNum = (float) (Session::get('pos_shipping') ?? 0);
    $couponDiscount = (float) (Session::get('pos_discount') ?? 0);
    $grandTotal = max(0, $subtotalNum + $shippingNum - $couponDiscount);
?>
<tr>
    <td>Sub Total</td>
    <td class="text-end">৳<?php echo e(number_format($subtotalNum, 2)); ?></td>
</tr>
<tr>
    <td>Shipping Fee</td>
    <td class="text-end">৳<?php echo e(number_format($shippingNum, 2)); ?></td>
</tr>
<tr>
    <td>কুপন ডিস্কাউন্ট</td>
    <td class="text-end">৳<?php echo e(number_format($couponDiscount, 2)); ?></td>
</tr>
<tr>
    <td><strong>Grand Total</strong></td>
    <td class="text-end pos-grand-total">৳<?php echo e(number_format($grandTotal, 2)); ?></td>
</tr><?php /**PATH C:\xampp\htdocs\resources\views/backEnd/order/cart_details.blade.php ENDPATH**/ ?>