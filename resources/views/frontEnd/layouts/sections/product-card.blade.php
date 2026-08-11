{{--
  Product Card Component (design-aware)
  =====================================
  Usage:
    @include('frontEnd.layouts.sections.product-card', ['product' => $product])
    @include('frontEnd.layouts.sections.product-card', ['product' => $product, 'classes' => 'wow zoomIn', 'attrs' => 'data-wow-duration="1.5s" data-wow-delay="0.1s"', 'image_url' => $relSrc])

  Variables:
    $product   (required)  product model
    $classes   (optional)  extra CSS classes for the wrapper
    $attrs     (optional)  raw attribute string for the wrapper (e.g. WOW animation)
    $image_url (optional)  override the image src (e.g. details page no-image fallback)

  The active design (general_settings.product_card_style) decides the MARKUP FORMAT:
    default/overlay/ribbon/glass  → unique modern layouts (premium, overlay, ribbon, glassmorphism)
    legacy/minimal/classic/dark/rounded/gradient → the classic .product_item_inner structure
  Every layout keeps .pro_img img + form.ajax-cart-form + .order-btn/.cart-icon-btn
  so the global cart JS + fly-to-cart animation keep working.
--}}
@php
    $pcDesign      = $generalsetting->product_card_style ?? 'default';
    $pcImg         = $image_url ?? asset($product->image ? $product->image->image : '');
    $pcDiscount    = $product->old_price ? (int) round((($product->old_price - $product->new_price) * 100) / $product->old_price) : 0;
    $pcStockOut    = !is_null($product->stock) && $product->stock < 1;
    $pcHasVariants = !$product->prosizes->isEmpty() || !$product->procolors->isEmpty();
    $pcHasWarranty = ($product->warranty_method ?? 'active') === 'active'
        && $product->warrantyTiers()->where('is_active', true)->where('warranty_type', '!=', 'none')->exists();

    // Stars render only where the reviews relation is already eager-loaded (avoids new N+1 queries)
    $pcStarsHtml = '';
    if ($product->relationLoaded('reviews') && $product->reviews->count()) {
        $avg = (float) $product->reviews->avg('ratting');
        if ($avg >= 0 && $avg <= 5) {
            $filled = floor($avg);
            $half   = ($avg - $filled) >= 0.5;
            $empty  = 5 - $filled - ($half ? 1 : 0);
            $s = '';
            for ($i = 0; $i < $filled; $i++) $s .= '<i class="fas fa-star"></i>';
            if ($half) $s .= '<i class="fas fa-star-half-alt"></i>';
            for ($i = 0; $i < $empty; $i++) $s .= '<i class="far fa-star"></i>';
            $pcStarsHtml = $s;
        }
    }
@endphp

@if ($pcDesign === 'default')
{{-- ============ PREMIUM (new default) — layered shadow, hover quick-actions, gradient price ============ --}}
<div class="product_item wist_item pc-premium {{ $classes ?? '' }}" {!! $attrs ?? '' !!}>
    <div class="pc-premium__media">
        @if ($product->old_price)
            <span class="pc-premium__badge">-{{ $pcDiscount }}%</span>
        @endif
        <a class="pro_img pc-premium__img" href="{{ route('product', $product->slug) }}">
            <img src="{{ $pcImg }}" alt="{{ $product->name }}" />
        </a>
        @if ($pcStockOut)
            <div class="stock-out-overlay">STOCK OUT</div>
        @endif
        <div class="pc-premium__actions">
            <a class="pc-premium__act" href="{{ route('product', $product->slug) }}" title="{{ __('View') }}"><i class="fa-solid fa-eye"></i></a>
            @if ($pcHasVariants || $pcStockOut)
                <a class="pc-premium__act" href="{{ route('product', $product->slug) }}" title="{{ __('Add to Cart') }}"><i class="fa-solid fa-cart-plus"></i></a>
            @else
                <form action="{{ route('cart.store') }}" method="POST" class="ajax-cart-form pc-premium__act-form">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}" />
                    <input type="hidden" name="qty" value="1" />
                    <button type="submit" class="pc-premium__act" title="{{ __('Add to Cart') }}"><i class="fa-solid fa-cart-plus"></i></button>
                </form>
            @endif
        </div>
    </div>
    <div class="pc-premium__body">
        @if ($pcStarsHtml)
            <div class="pc-premium__stars">{!! $pcStarsHtml !!}</div>
        @endif
        <a class="pc-premium__name" href="{{ route('product', $product->slug) }}">{{ Str::limit($product->name, 60) }}</a>
        <div class="pc-premium__price">
            @if ($product->old_price) <del>৳ {{ $product->old_price }}</del> @endif
            <span class="pc-premium__now">৳ {{ $product->new_price }}</span>
        </div>
        @if ($pcHasWarranty)
            <small class="pc-premium__warranty">🛡️ {{ __('Warranty') }}</small>
        @endif
    </div>
    <div class="pc-premium__btn">
        @include('frontEnd.layouts.sections.product-card-buttons')
    </div>
</div>

@elseif ($pcDesign === 'overlay')
{{-- ============ OVERLAY — full-bleed image, hover quick-actions, info panel ============ --}}
<div class="product_item wist_item pc-overlay {{ $classes ?? '' }}" {!! $attrs ?? '' !!}>
    <a class="pro_img pc-overlay__media" href="{{ route('product', $product->slug) }}">
        <img src="{{ $pcImg }}" alt="{{ $product->name }}" />
    </a>
    @if ($pcStockOut)
        <div class="stock-out-overlay">STOCK OUT</div>
    @endif
    <div class="pc-overlay__actions">
        <a class="pc-overlay__act" href="{{ route('product', $product->slug) }}" title="{{ __('View') }}"><i class="fa-solid fa-eye"></i></a>
        @if ($pcHasVariants || $pcStockOut)
            <a class="pc-overlay__act" href="{{ route('product', $product->slug) }}" title="{{ __('Add to Cart') }}"><i class="fa-solid fa-cart-plus"></i></a>
        @else
            <form action="{{ route('cart.store') }}" method="POST" class="ajax-cart-form">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}" />
                <input type="hidden" name="qty" value="1" />
                <button type="submit" class="pc-overlay__act" title="{{ __('Add to Cart') }}"><i class="fa-solid fa-cart-plus"></i></button>
            </form>
        @endif
    </div>
    <div class="pc-overlay__panel">
        <div class="pc-overlay__meta">
            @if ($product->old_price)
                <span class="pc-overlay__badge">-{{ $pcDiscount }}%</span>
            @endif
            @if ($pcStarsHtml)
                <div class="pc-overlay__stars">{!! $pcStarsHtml !!}</div>
            @endif
            <a class="pc-overlay__name" href="{{ route('product', $product->slug) }}">{{ Str::limit($product->name, 60) }}</a>
            <div class="pc-overlay__price">
                @if ($product->old_price) <del>৳ {{ $product->old_price }}</del> @endif
                <span>৳ {{ $product->new_price }}</span>
            </div>
        </div>
        <div class="pc-overlay__btn">
            @include('frontEnd.layouts.sections.product-card-buttons')
        </div>
    </div>
</div>

@elseif ($pcDesign === 'ribbon')
{{-- ============ RIBBON — pennant ribbon badge, centered body, split action bar ============ --}}
<div class="product_item wist_item pc-ribbon {{ $classes ?? '' }}" {!! $attrs ?? '' !!}>
    @if ($product->old_price)
        <div class="pc-ribbon__badge"><span>-{{ $pcDiscount }}%</span></div>
    @endif
    <div class="pc-ribbon__media">
        <a class="pro_img pc-ribbon__img" href="{{ route('product', $product->slug) }}">
            <img src="{{ $pcImg }}" alt="{{ $product->name }}" />
        </a>
        @if ($pcStockOut)
            <div class="stock-out-overlay">STOCK OUT</div>
        @endif
    </div>
    <div class="pc-ribbon__body">
        @if ($pcStarsHtml)
            <div class="pc-ribbon__stars">{!! $pcStarsHtml !!}</div>
        @endif
        <a class="pc-ribbon__name" href="{{ route('product', $product->slug) }}">{{ Str::limit($product->name, 60) }}</a>
        <div class="pc-ribbon__price">
            @if ($product->old_price) <del>৳ {{ $product->old_price }}</del> @endif
            <span>৳ {{ $product->new_price }}</span>
        </div>
    </div>
    <div class="pc-ribbon__btn">
        @include('frontEnd.layouts.sections.product-card-buttons')
    </div>
</div>

@elseif ($pcDesign === 'glass')
{{-- ============ GLASS — frosted-glass info bar over image, floating FAB ============ --}}
<div class="product_item wist_item pc-glass {{ $classes ?? '' }}" {!! $attrs ?? '' !!}>
    <div class="pc-glass__media">
        <a class="pro_img pc-glass__img" href="{{ route('product', $product->slug) }}">
            <img src="{{ $pcImg }}" alt="{{ $product->name }}" />
        </a>
        @if ($pcStockOut)
            <div class="stock-out-overlay">STOCK OUT</div>
        @endif
        <div class="pc-glass__info">
            @if ($pcStarsHtml)
                <div class="pc-glass__stars">{!! $pcStarsHtml !!}</div>
            @endif
            <a class="pc-glass__name" href="{{ route('product', $product->slug) }}">{{ Str::limit($product->name, 55) }}</a>
            <div class="pc-glass__price">
                @if ($product->old_price) <del>৳ {{ $product->old_price }}</del> @endif
                <span>৳ {{ $product->new_price }}</span>
            </div>
        </div>
        @if ($product->old_price)
            <span class="pc-glass__badge">-{{ $pcDiscount }}%</span>
        @endif
        @if (!$pcHasVariants && !$pcStockOut)
            <form action="{{ route('cart.store') }}" method="POST" class="ajax-cart-form">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}" />
                <input type="hidden" name="qty" value="1" />
                <button type="submit" class="pc-glass__fab" title="{{ __('Add to Cart') }}"><i class="fa-solid fa-cart-plus"></i></button>
            </form>
        @else
            <a class="pc-glass__fab" href="{{ route('product', $product->slug) }}" title="{{ __('View') }}"><i class="fa-solid fa-eye"></i></a>
        @endif
    </div>
</div>

@else
{{-- ============ CLASSIC STRUCTURE (legacy / minimal / classic / dark / rounded / gradient) ============ --}}
<div class="product_item wist_item {{ $classes ?? '' }}" {!! $attrs ?? '' !!}>
    <div class="product_item_inner">
        @if ($product->old_price)
            <div class="sale-badge">
                <div class="sale-badge-inner">
                    <div class="sale-badge-box">
                        <span class="sale-badge-text">
                            <p>{{ $pcDiscount }}%</p>{{ __('Sale') }}
                        </span>
                    </div>
                </div>
            </div>
        @endif
        <div class="pro_img">
            <a href="{{ route('product', $product->slug) }}">
                <img src="{{ $pcImg }}" alt="{{ $product->name }}" />
            </a>
            @if ($pcStockOut)
                <div class="stock-out-overlay">STOCK OUT</div>
            @endif
        </div>
        <div class="pro_des">
            <div class="pro_name">
                <a href="{{ route('product', $product->slug) }}">{{ Str::limit($product->name, 80) }}</a>
            </div>
            @if ($pcStarsHtml)
                <div class="pc-classic-stars">{!! $pcStarsHtml !!}</div>
            @endif
            <div class="pro_price">
                <p>
                    @if ($product->old_price) <del>৳ {{ $product->old_price }}</del> @endif
                    ৳ {{ $product->new_price }}
                </p>
            </div>
        </div>
    </div>
    <div class="pro_btn">
        @include('frontEnd.layouts.sections.product-card-buttons')
    </div>
</div>
@endif
