{{-- Order form for a landing page. Shared by every template; the checkout cannot be switched off.
     Posts to customer.ordersave with the product and, when the product has variants, the chosen variant.
     Labels and placeholders are page content, so the editor can change them. --}}
<section class="lp-section lp-checkout" id="checkout">
    <div class="lp-wrap lp-narrow">
        @if($open)
            <h2 class="lp-h2" {!! $page->attr('checkout.heading') !!}>{{ $page->text('checkout.heading') }}</h2>

            <form action="{{ route('customer.ordersave') }}" method="POST" class="lp-form">
                @csrf
                <input type="hidden" name="product" value="{{ $product->id }}">
                <input type="hidden" name="landing" value="{{ $page->landing()->id }}">

                @if($variants->isNotEmpty())
                    <fieldset class="lp-variants">
                        <legend {!! $page->attr('checkout.variants_label') !!}>{{ $page->text('checkout.variants_label') }}</legend>
                        @foreach($variants as $variant)
                            @php
                                $label = collect([optional($variant->color)->colorName, optional($variant->size)->sizeName])->filter()->implode(' / ') ?: __('Standard');
                                $price = (float) ($variant->price ?: $product->new_price);
                                $inStock = (int) $variant->stock > 0;
                                $first = $inStock && $variants->firstWhere('stock', '>', 0)?->id === $variant->id;
                            @endphp
                            <label class="lp-variant {{ $inStock ? '' : 'is-out' }}">
                                <input type="radio" name="variant" value="{{ $variant->id }}" data-price="{{ $price }}"
                                    {{ $first ? 'checked' : '' }} {{ $inStock ? '' : 'disabled' }}>
                                <span>{{ $label }}</span>
                                <strong>৳{{ number_format($price, 2) }}</strong>
                                @unless($inStock)<em>{{ __('Out of stock') }}</em>@endunless
                            </label>
                        @endforeach
                    </fieldset>
                @endif

                <div class="lp-field">
                    <label for="lp-name"><span {!! $page->attr('checkout.label_name') !!}>{{ $page->text('checkout.label_name') }}</span> *</label>
                    <input id="lp-name" type="text" name="name" required placeholder="{{ $page->text('checkout.placeholder_name') }}">
                    @if($page->isEditable())
                        <small class="lp-ph-edit" {!! $page->attr('checkout.placeholder_name') !!} data-ph-for="lp-name">{{ $page->text('checkout.placeholder_name') }}</small>
                    @endif
                </div>
                <div class="lp-field">
                    <label for="lp-phone"><span {!! $page->attr('checkout.label_phone') !!}>{{ $page->text('checkout.label_phone') }}</span> *</label>
                    <input id="lp-phone" type="tel" name="phone" required minlength="11" maxlength="11"
                        pattern="0(13|14|15|16|17|18|19)[0-9]{8}" placeholder="{{ $page->text('checkout.placeholder_phone') }}">
                    @if($page->isEditable())
                        <small class="lp-ph-edit" {!! $page->attr('checkout.placeholder_phone') !!} data-ph-for="lp-phone">{{ $page->text('checkout.placeholder_phone') }}</small>
                    @endif
                </div>
                <div class="lp-field">
                    <label for="lp-address"><span {!! $page->attr('checkout.label_address') !!}>{{ $page->text('checkout.label_address') }}</span> *</label>
                    <input id="lp-address" type="text" name="address" required placeholder="{{ $page->text('checkout.placeholder_address') }}">
                    @if($page->isEditable())
                        <small class="lp-ph-edit" {!! $page->attr('checkout.placeholder_address') !!} data-ph-for="lp-address">{{ $page->text('checkout.placeholder_address') }}</small>
                    @endif
                </div>
                <div class="lp-field">
                    <label for="lp-area"><span {!! $page->attr('checkout.label_area') !!}>{{ $page->text('checkout.label_area') }}</span> *</label>
                    <select id="lp-area" name="area" required>
                        <option value="">{{ $page->text('checkout.placeholder_area') }}</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }} (৳{{ number_format((float) $area->amount, 2) }})</option>
                        @endforeach
                    </select>
                    @if($page->isEditable())
                        <small class="lp-ph-edit" {!! $page->attr('checkout.placeholder_area') !!} data-ph-for="lp-area">{{ $page->text('checkout.placeholder_area') }}</small>
                    @endif
                </div>

                <p class="lp-total"><span {!! $page->attr('checkout.label_price') !!}>{{ $page->text('checkout.label_price') }}</span>: <strong id="lp-price">৳{{ number_format((float) $product->new_price, 2) }}</strong></p>

                <button class="lp-btn" type="submit" {{ $variants->isNotEmpty() && ! $variants->contains(fn ($v) => (int) $v->stock > 0) ? 'disabled' : '' }}>
                    <span {!! $page->attr('checkout.submit') !!}>{{ $page->text('checkout.submit') }}</span>
                </button>
                @if($page->text('checkout.note') !== '' || $page->isEditable())
                    <p class="lp-note" {!! $page->attr('checkout.note') !!}>{{ $page->text('checkout.note') }}</p>
                @endif
            </form>

            <script>
                (function () {
                    var price = document.getElementById('lp-price');
                    document.querySelectorAll('.lp-variant input[type=radio]').forEach(function (radio) {
                        radio.addEventListener('change', function () {
                            price.textContent = '৳' + Number(radio.dataset.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        });
                    });
                })();
            </script>
        @else
            <h2 class="lp-h2">{{ __('This offer is closed') }}</h2>
            <p class="lp-closed">{{ __('The order form for this offer is not open right now.') }}</p>
        @endif
    </div>
</section>
