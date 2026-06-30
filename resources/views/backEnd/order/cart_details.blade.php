@php
    $subtotalRaw = Cart::instance('pos_shopping')->subtotal();
    $subtotalNum = (float) preg_replace('/[^\d.]/', '', (string) $subtotalRaw);
    $shippingNum = (float) (Session::get('pos_shipping') ?? 0);
    $couponDiscount = (float) (Session::get('pos_discount') ?? 0);
    $grandTotal = max(0, $subtotalNum + $shippingNum - $couponDiscount);
@endphp
<tr>
    <td> {{ __('Sub Total') }} </td>
    <td class="text-end">৳{{ number_format($subtotalNum, 2) }}</td>
</tr>
<tr>
    <td> {{ __('Shipping Fee') }} </td>
    <td class="text-end">৳{{ number_format($shippingNum, 2) }}</td>
</tr>
<tr>
    <td>কুপন ডিস্কাউন্ট</td>
    <td class="text-end">৳{{ number_format($couponDiscount, 2) }}</td>
</tr>
<tr>
    <td><strong> {{ __('Grand Total') }} </strong></td>
    <td class="text-end pos-grand-total">৳{{ number_format($grandTotal, 2) }}</td>
</tr>