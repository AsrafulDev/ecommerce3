@extends('frontEnd.layouts.master')
@section('title','{{ __('{{ __('Shop') }}ping {{ __('Cart') }}') }}')
@section('content')

<section class="breadcrumb-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="custom-breadcrumb">
                    <ul>
                        <li><a href="{{route('home')}}">{{ __('Home') }}</a></li>
                        <li>
                            <a><i class="fa-solid fa-angles-right"></i> </a>
                        </li>
                        <li><a href="">{{ __('{{ __('Shop') }}ping {{ __('Cart') }}') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- breadcrumb end -->

<section class="vcart-section">
    @php
        $sub{{ __('total') }} = {{ __('Cart') }}::instance('shopping')->sub{{ __('total') }}();
        $sub{{ __('total') }} = str_replace(',', '', $sub{{ __('total') }});
        $sub{{ __('total') }} = str_replace('.00', '', $sub{{ __('total') }});
        view()->share('sub{{ __('total') }}', $sub{{ __('total') }});

        $shipping   = {{ __('Session') }}::get('shipping') ? {{ __('Session') }}::get('shipping') : 0;
        $discount   = {{ __('Session') }}::get('discount') ? {{ __('Session') }}::get('discount') : 0;
        $grand{{ __('Total') }} = ($sub{{ __('total') }} + $shipping) - $discount;
        $cartCount  = {{ __('Cart') }}::instance('shopping')->count();
    @endphp

    <div class="container">
        <div class="row" id="cartlist">
            <div class="col-sm-9">
                <div class="vcart-inner">
                    <div class="cart-title">
                        <h4>{{ __('{{ __('Shop') }}ping {{ __('Cart') }}') }}</h4>
                    </div>
                    <div class="vcart-content">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Image') }}</th>
                                        <th>{{ __('Product') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Qty') }}</th>
                                        <th>{{ __('{{ __('Total') }}') }}</th>
                                        <th>{{ __('Remove') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data as $value)
                                    <tr
                                        data-row-id="{{ $value->rowId }}"
                                        data-product-id="{{ $value->id }}"
                                        data-product-name="{{ e($value->name) }}"
                                        data-price="{{ (float) $value->price }}"
                                    >
                                        <td>
                                            <img height="30" src="{{asset($value->options->image)}}" alt="" />
                                        </td>
                                        <td class="cart_name">{{$value->name}}</td>
                                        <td>{{$value->price}} ৳</td>
                                        <td>
                                            <div class="qty-cart vcart-qty">
                                                <div class="quantity">
                                                    <button class="minus cart_decrement" data-id="{{$value->rowId}}">-</button>
                                                    <input type="text" value="{{$value->qty}}" readonly />
                                                    <button class="plus cart_increment" data-id="{{$value->rowId}}">+</button>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{$value->price * $value->qty}} ৳</td>
                                        <td>
                                            <button class="remove-cart cart_remove" data-id="{{$value->rowId}}">
                                                <i data-feather="x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="coupon-form">
                    <form action="">
                        <input type="text" placeholder="{{ __('apply coupon') }}" id="cart{{ __('Coupon') }}" />
                        <button type="{{ __('submit') }}" id="apply{{ __('Coupon') }}Btn">{{ __('Apply') }}</button>
                    </form>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="cart-summary">
                    <h5>{{ __('{{ __('Cart') }} {{ __('Summary') }}') }}</h5>
                    <table class="table">
                        <tbody>
                            <tr>
                                <td>{{ __('{{ __('Item') }}s') }}</td>
                                <td>{{ $cartCount }} (qty)</td>
                            </tr>
                            <tr>
                                <td>{{ __('{{ __('Total') }}') }}</td>
                                <td>৳{{ $sub{{ __('total') }} }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Shipping') }}</td>
                                <td>৳{{ $shipping }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Discount') }}</td>
                                <td>৳{{ $discount }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('{{ __('Total') }}') }}</td>
                                <td>৳{{ $grand{{ __('Total') }} }}</td>
                            </tr>
                        </tbody>
                    </table>
                    {{-- {{ __('Check') }}out button – ট্র্যাকিংয়ের জন্য ID দিলাম --}}
                    <a href="{{route('customer.checkout')}}" class="go_cart" id="checkoutButton">
                        PROCESS TO CHECKOUT
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('script')
<script>
// =============================
//   CART PAGE TRACKING SCRIPT
// =============================

window.dataLayer = window.dataLayer || [];

(function() {

    // ---- {{ __('Cart') }} items array (GA4 / GTM এর জন্য) ----
    var cart{{ __('{{ __('Item') }}s') }} = [
        @foreach($data as $item)
        {
            item_id: '{{ $item->id }}',
            item_name: @json($item->name),
            price: {{ (float) $item->price }},
            quantity: {{ (int) $item->qty }}
            // চাইলে এখানে brand, category ইত্যাদি অ্যাড করা যাবে
        }@if(!$loop->last),@endif
        @endforeach
    ];

    var cart{{ __('Value') }}     = {{ (float) $grand{{ __('Total') }} }};
    var cart{{ __('Item') }}Count = {{ (int) $cartCount }};
    var currency      = 'BDT';

    // ১) Page load -> view_cart (GA4/GTM)
    window.dataLayer.push({
        event: 'view_cart',
        ecommerce: {
            currency: currency,
            value: cart{{ __('Value') }},
            items: cart{{ __('{{ __('Item') }}s') }}
        }
    });

    // ২) Page load -> Facebook Pixel View{{ __('Cart') }}
    if (typeof fbq === 'function') {
        fbq('trackCustom', 'View{{ __('Cart') }}', {
            value: cart{{ __('Value') }},
            currency: currency,
            num_items: cart{{ __('Item') }}Count
        });
    }

    // Helper: row থেকে product data নেওয়া
    // TikTok: View{{ __('Cart') }} (page load)
    if (typeof ttq !== 'undefined') {
        ttq.track('ViewContent', {
            content_type: 'product_group',
            value: cart{{ __('Value') }},
            currency: currency,
            quantity: cart{{ __('Item') }}Count
        });
    }

    function get{{ __('Item') }}Data($row) {
        return {
            item_id: $row.data('product-id'),
            item_name: $row.data('product-name'),
            price: parseFloat($row.data('price')) || 0
        };
    }

    // Helper: GA4 + FB Pixel push
    function push{{ __('Cart') }}Event(type, item, quantity{{ __('Change') }}) {

        var event{{ __('Name') }}Gtm;
        var qty = Math.abs(quantity{{ __('Change') }}) || 1;
        var value = (item.price || 0) * qty;

        if (type === 'add_to_cart') {
            event{{ __('Name') }}Gtm = 'add_to_cart';
        } else if (type === 'remove_from_cart') {
            event{{ __('Name') }}Gtm = 'remove_from_cart';
        } else {
            event{{ __('Name') }}Gtm = 'update_cart';
        }

        // ---- GA4 / GTM ----
        window.dataLayer.push({
            event: event{{ __('Name') }}Gtm,
            ecommerce: {
                currency: currency,
                value: value,
                items: [
                    Object.assign({}, item, { quantity: qty })
                ]
            }
        });

        // ---- Facebook Pixel ----
        if (typeof fbq === 'function') {
            if (type === 'add_to_cart') {
                fbq('track', 'AddTo{{ __('Cart') }}', {
                    value: value,
                    currency: currency,
                    content_ids: [item.item_id],
                    content_name: item.item_name,
                    contents: [
                        { id: item.item_id, quantity: qty }
                    ]
                });
            } else if (type === 'remove_from_cart') {
                fbq('trackCustom', 'Remove{{ __('From') }}{{ __('Cart') }}', {
                    value: value,
                    currency: currency,
                    content_ids: [item.item_id],
                    content_name: item.item_name,
                    contents: [
                        { id: item.item_id, quantity: qty }
                    ]
                });
            } else {
                fbq('trackCustom', 'Update{{ __('Cart') }}', {
                    value: value,
                    currency: currency,
                    content_ids: [item.item_id],
                    content_name: item.item_name,
                    contents: [
                        { id: item.item_id, quantity: qty }
                    ]
                });
            }
        }
    }

    // ৩) {{ __('Check') }}out button -> Initiate{{ __('Check') }}out + begin_checkout
    var checkoutBtn = document.getElementById('checkoutButton');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            // Facebook Pixel
            if (typeof fbq === 'function') {
                fbq('track', 'Initiate{{ __('Check') }}out', {
                    value: cart{{ __('Value') }},
                    currency: currency,
                    num_items: cart{{ __('Item') }}Count,
                    content_ids: cart{{ __('{{ __('Item') }}s') }}.map(function(i){ return i.item_id; }),
                    contents: cart{{ __('{{ __('Item') }}s') }}.map(function(i){ return { id: i.item_id, quantity: i.quantity, item_price: i.price }; })
                });
            }

            // TikTok Pixel
            if (typeof ttq !== 'undefined') {
                ttq.track('Initiate{{ __('Check') }}out', {
                    value: cart{{ __('Value') }},
                    currency: currency,
                    quantity: cart{{ __('Item') }}Count,
                    content_type: 'product'
                });
            }

            // GA4 / GTM
            window.dataLayer.push({
                event: 'begin_checkout',
                ecommerce: {
                    currency: currency,
                    value: cart{{ __('Value') }},
                    items: cart{{ __('{{ __('Item') }}s') }}
                }
            });
        });
    }

    // ৪) Qty Increment -> add_to_cart / update_cart
    $(document).on('click', '.cart_increment', function() {
        var $row = $(this).closest('tr');
        var item = get{{ __('Item') }}Data($row);
        var currentQty = parseInt($row.find('input').val()) || 1;
        var newQty = currentQty + 1;

        // এখানে আমরা ধরছি increment মানে add_to_cart type event
        push{{ __('Cart') }}Event('add_to_cart', item, newQty - currentQty);
    });

    // ৫) Qty Decrement -> update_cart (বা remove এর আগে quantity কমছে)
    $(document).on('click', '.cart_decrement', function() {
        var $row = $(this).closest('tr');
        var item = get{{ __('Item') }}Data($row);
        var currentQty = parseInt($row.find('input').val()) || 1;
        var newQty = Math.max(currentQty - 1, 0);

        if (newQty < currentQty) {
            // Qty কমে গেলে update_cart
            push{{ __('Cart') }}Event('update_cart', item, newQty - currentQty);
        }
    });

    // ৬) Remove button -> remove_from_cart
    $(document).on('click', '.cart_remove', function() {
        var $row = $(this).closest('tr');
        var item = get{{ __('Item') }}Data($row);
        var currentQty = parseInt($row.find('input').val()) || 1;

        push{{ __('Cart') }}Event('remove_from_cart', item, currentQty);
    });

    // ৭) {{ __('Coupon') }} Apply -> apply_coupon (GA4) + Apply{{ __('Coupon') }} (FB)
    $('.coupon-form form').on('{{ __('submit') }}', function() {
        var code = $('#cart{{ __('Coupon') }}').val() || '';

        // GA4 / GTM
        window.dataLayer.push({
            event: 'apply_coupon',
            ecommerce: {
                coupon: code
            }
        });

        // Facebook Pixel
        if (typeof fbq === 'function') {
            fbq('trackCustom', 'Apply{{ __('Coupon') }}', {
                coupon: code
            });
        }
        // preventDefault করা হয়নি, যাতে তোমার existing কুপন লজিক স্বাভাবিক মতোই কাজ করে
    });

})();
</script>
@endpush
