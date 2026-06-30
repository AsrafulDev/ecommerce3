@php
    $sub{{ __('total') }}Raw = {{ __('Cart') }}::instance('pos_shopping')->sub{{ __('total') }}();
    $sub{{ __('total') }}Num = (float) preg_replace('/[^\d.]/', '', (string) $sub{{ __('total') }}Raw);
    $shippingNum = (float) ({{ __('Session') }}::get('pos_shipping') ?? 0);
    $coupon{{ __('Discount') }} = (float) ({{ __('Session') }}::get('pos_discount') ?? 0);
    $grand{{ __('Total') }} = max(0, $sub{{ __('total') }}Num + $shippingNum - $coupon{{ __('Discount') }});
@endphp
<tr>
    <td>{{ __('Sub {{ __('Total') }}') }}</td>
    <td class="text-end">৳{{ number_format($sub{{ __('total') }}Num, 2) }}</td>
</tr>
<tr>
    <td>{{ __('{{ __('Shipping') }} Fee') }}</td>
    <td class="text-end">৳{{ number_format($shippingNum, 2) }}</td>
</tr>
<tr>
    <td>{{ __('bn_4f6e559f') }}</td>
    <td class="text-end">৳{{ number_format($coupon{{ __('Discount') }}, 2) }}</td>
</tr>
<tr>
    <td><strong>{{ __('Grand {{ __('Total') }}') }}</strong></td>
    <td class="text-end pos-grand-{{ __('total') }}">৳{{ number_format($grand{{ __('Total') }}, 2) }}</td>
</tr>