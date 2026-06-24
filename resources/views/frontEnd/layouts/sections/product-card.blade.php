{{-- 
  Product Card Component 
  Usage: @include('frontEnd.layouts.sections.product-card', ['product' => $product, 'classes' => ''])
  Variables: $product (required), $classes (optional extra CSS classes)
--}}
<div class="product_item wist_item {{ $classes ?? '' }}">
    <div class="product_item_inner">
        @if($product->old_price)
        <div class="sale-badge">
            <div class="sale-badge-inner">
                <div class="sale-badge-box">
                    <span class="sale-badge-text">
                        <p>
                            @php 
                                $discount = (((($product->old_price)-($product->new_price))*100) / ($product->old_price)); 
                            @endphp 
                            {{ number_format($discount, 0) }}%
                        </p>
                        ছাড়
                    </span>
                </div>
            </div>
        </div>
        @endif
        <div class="pro_img">
            <a href="{{ route('product', $product->slug) }}">
                <img src="{{ asset($product->image ? $product->image->image : '') }}"
                    alt="{{ $product->name }}" />
            </a>
            @if(!is_null($product->stock) && $product->stock < 1)
                <div class="stock-out-overlay">STOCK OUT</div>
            @endif
        </div>
        <div class="pro_des">
            <div class="pro_name">
                <a href="{{ route('product', $product->slug) }}">{{ Str::limit($product->name, 80) }}</a>
            </div>
            <div class="pro_price">
                <p>
                    @if ($product->old_price)
                        <del>৳ {{ $product->old_price }}</del>
                    @endif
                    ৳ {{ $product->new_price }} 
                </p>
            </div>
        </div>
    </div>

    {{-- Button logic: variant/stock-out → detail page, simple → direct cart --}}
    @if (!$product->prosizes->isEmpty() || !$product->procolors->isEmpty() || (!is_null($product->stock) && $product->stock < 1))
        <div class="pro_btn">
            <a href="{{ route('product', $product->slug) }}" class="order-btn-link">
                অর্ডার করুন
            </a>
            <a href="{{ route('product', $product->slug) }}" class="cart-icon-link">
                <i class="fa-solid fa-cart-shopping"></i>
            </a>
        </div>
    @else
        <div class="pro_btn">
            <form action="{{ route('cart.store') }}" method="POST" class="ajax-cart-form">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}" />
                <input type="hidden" name="qty" value="1" />
                <input type="hidden" name="order_now" value="1">
                <button type="submit" class="order-btn">অর্ডার করুন</button>
            </form>
            <form action="{{ route('cart.store') }}" method="POST" class="ajax-cart-form">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}" />
                <input type="hidden" name="qty" value="1" />
                <button type="submit" class="cart-icon-btn">
                    <i class="fa-solid fa-cart-shopping"></i>
                </button>
            </form>
        </div>
    @endif
</div>
