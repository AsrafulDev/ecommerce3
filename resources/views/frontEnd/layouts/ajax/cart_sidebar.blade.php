<div class="sidebar-cart-header" style="background: var(--primary-color);">
    <button type="button" class="sidebar-cart-close" onclick="closeSidebarCart()" aria-label="বন্ধ করুন">
        <i class="fa-solid fa-times"></i>
    </button>
    <h3 class="sidebar-cart-title">আমার কার্ট</h3>
</div>
<div class="sidebar-cart-body">
    @if($cartContent->isEmpty())
        <div class="sidebar-cart-empty">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>আপনার কার্ট খালি</p>
            <a href="{{ route('shop') }}" class="sidebar-cart-checkout-btn" style="background: var(--primary-color);">শপিং করুন</a>
        </div>
    @else
        @foreach($cartContent as $item)
            <div class="sidebar-cart-item">
                <div class="sidebar-cart-item-img">
                    <a href="{{ route('product', $item->options->slug ?? '#') }}">
                        <img src="{{ asset($item->options->image ?? 'public/uploads/default.webp') }}" alt="{{ $item->name }}" />
                    </a>
                </div>
                <div class="sidebar-cart-item-details">
                    <a href="{{ route('product', $item->options->slug ?? '#') }}" class="sidebar-cart-item-title">{{ Str::limit($item->name, 45) }}</a>
                    <p class="sidebar-cart-item-price">৳ {{ number_format($item->price, 0) }} × {{ $item->qty }}</p>
                    @if(!empty($item->options->old_price) && $item->options->old_price > $item->price)
                        @php $savings = ($item->options->old_price - $item->price) * $item->qty; @endphp
                        <p class="sidebar-cart-item-savings">৳ {{ number_format($savings, 0) }} সাশ্রয়</p>
                    @endif
                    <div class="sidebar-cart-qty">
                        <button type="button" class="sidebar-qty-btn cart_decrement" data-id="{{ $item->rowId }}">−</button>
                        <span class="sidebar-qty-num">{{ $item->qty }}</span>
                        <button type="button" class="sidebar-qty-btn cart_increment" data-id="{{ $item->rowId }}">+</button>
                    </div>
                    <button type="button" class="sidebar-cart-item-remove cart_remove" data-id="{{ $item->rowId }}" title="রিমুভ করুন">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        @endforeach
    @endif
</div>
@if(!$cartContent->isEmpty())
<div class="sidebar-cart-footer">
    <div class="sidebar-cart-total">
        <span class="sidebar-cart-total-label">সর্বমোট</span>
        <span class="sidebar-cart-total-amount">৳ {{ number_format($subtotal, 0) }}</span>
    </div>
    <a href="{{ route('customer.checkout') }}" class="sidebar-cart-checkout-btn" style="background: var(--primary-color);">অর্ডার করুন</a>
</div>
@endif
