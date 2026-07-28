@php
    $subtotalRaw = Cart::instance('pos_shopping')->subtotal();
    $subtotalNum = (float) preg_replace('/[^\d.]/', '', (string) $subtotalRaw);
    $shippingNum = (float) (Session::get('pos_shipping') ?? 0);
    $couponDiscount = (float) (Session::get('pos_discount') ?? 0);
    $productTotalDiscount = (float) (Session::get('product_discount') ?? 0);
    $allDiscount = $couponDiscount + $productTotalDiscount;
    $grandTotal = max(0, $subtotalNum + $shippingNum - $allDiscount);
@endphp
<tr>
    <td> {{ __('Sub Total') }} </td>
    <td class="text-end">৳{{ number_format($subtotalNum, 2) }}</td>
</tr>
<tr>
    <td> {{ __('Shipping Fee') }} </td>
    <td class="text-end">৳{{ number_format($shippingNum, 2) }}</td>
</tr>
@if($productTotalDiscount > 0)
<tr>
    <td> {{ __('Product Discount') }} </td>
    <td class="text-end">৳{{ number_format($productTotalDiscount, 2) }}</td>
</tr>
@endif
@if($couponDiscount > 0)
<tr>
    <td>{{ __('Coupon Discount') }}</td>
    <td class="text-end">৳{{ number_format($couponDiscount, 2) }}</td>
</tr>
@endif
<tr>
    <td><strong> {{ __('Grand Total') }} </strong></td>
    <td class="text-end pos-grand-total">৳{{ number_format($grandTotal, 2) }}</td>
</tr>