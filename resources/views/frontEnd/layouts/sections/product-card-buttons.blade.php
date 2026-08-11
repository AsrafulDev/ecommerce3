{{--
  Product Card Buttons (shared)
  =============================
  Renders the two-button action row used by every product-card layout.
  Expects $product.
  Variant / stock-out products → both buttons link to the details page.
  Simple in-stock products  → Order Now (buy now) + Add to Cart (icon) via cart.store.
  Keeps classes form.ajax-cart-form / .order-btn / .cart-icon-btn for global JS.
--}}
@if (!$product->prosizes->isEmpty() || !$product->procolors->isEmpty() || (!is_null($product->stock) && $product->stock < 1))
    <a href="{{ route('product', $product->slug) }}" class="order-btn-link order-btn">{{ __('Order Now') }}</a>
    <a href="{{ route('product', $product->slug) }}" class="cart-icon-link cart-icon-btn">
        <i class="fa-solid fa-cart-shopping"></i>
    </a>
@else
    <form action="{{ route('cart.store') }}" method="POST" class="ajax-cart-form">
        @csrf
        <input type="hidden" name="id" value="{{ $product->id }}" />
        <input type="hidden" name="qty" value="1" />
        <input type="hidden" name="order_now" value="1" />
        <button type="submit" class="order-btn">{{ __('Order Now') }}</button>
    </form>
    <form action="{{ route('cart.store') }}" method="POST" class="ajax-cart-form">
        @csrf
        <input type="hidden" name="id" value="{{ $product->id }}" />
        <input type="hidden" name="qty" value="1" />
        <button type="submit" class="cart-icon-btn">
            <i class="fa-solid fa-cart-shopping"></i>
        </button>
    </form>
@endif
