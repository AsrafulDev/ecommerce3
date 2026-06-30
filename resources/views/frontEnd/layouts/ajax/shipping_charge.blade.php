@php
    $sub{{ __('total') }} = {{ __('Cart') }}::instance('shopping')->sub{{ __('total') }}();
    $sub{{ __('total') }}=str_replace(',','',$sub{{ __('total') }});
    $sub{{ __('total') }}=str_replace('.00', '',$sub{{ __('total') }});
    view()->share('sub{{ __('total') }}',$sub{{ __('total') }});
    $shipping = {{ __('Session') }}::get('shipping')?{{ __('Session') }}::get('shipping'):0;
    $discount = {{ __('Session') }}::get('discount')?{{ __('Session') }}::get('discount'):0;
@endphp

<h5>{{ __('{{ __('Cart') }} {{ __('Summary') }}') }}</h5>
    <table class="table">
        <tbody>
            <tr>
                <td>{{ __('{{ __('Item') }}s') }}</td>
                <td>{{{{ __('Cart') }}::instance('shopping')->count()}} (qty)</td>
            </tr>
            <tr>
                <td>{{ __('Total') }}</td>
                <td>৳{{$sub{{ __('total') }}}}</td>
            </tr>
            <tr>
                <td>{{ __('Shipping') }}</td>
                <td>৳{{$shipping}}</td>
            </tr>
            <tr>
                <td>{{ __('Discount') }}</td>
                <td>৳{{$discount}}</td>
            </tr>
            <tr>
                <td>{{ __('Total') }}</td>
                <td>৳{{($sub{{ __('total') }}+$shipping) - $discount}}</td>
            </tr>
        </tbody>
    </table>