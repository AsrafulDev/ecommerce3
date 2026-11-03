{{-- Order form for a landing page, shared by every template; the checkout cannot be switched off.
     Three numbered steps (product options, your details, delivery area) and a summary that follows the choices.
     Options come from the product's variants: colour swatches and sizes, or one kind of option alone.
     Posts to customer.ordersave with the product, the landing, the variant, the quantity and the area.
     Labels and placeholders are page content, so the editor can change them. --}}
@php
    $maxQuantity = \App\Services\Landing\LandingPricing::MAX_QUANTITY;
    $basePrice = (float) $product->new_price;
    $inStock = fn ($variant) => (int) $variant->stock > 0;
    $unitPrice = fn ($variant) => (float) ($variant->price ?: $basePrice);
    $optionLabel = fn ($variant) => collect([optional($variant->color)->colorName, optional($variant->size)->sizeName])
        ->filter()->implode(' / ') ?: __('Standard');
    $swatch = fn ($color) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($color?->color ?? '')) ? $color->color : '#cccccc';

    $default = $variants->first($inStock);
    $colours = $variants->filter(fn ($v) => $v->color_id)->unique('color_id')->values();
    $showColours = $colours->isNotEmpty();
    $showSizes = $variants->contains(fn ($v) => $v->size_id);
    $defaultColour = $default?->color_id ?? $colours->first()?->color_id;
    $defaultPrice = $default ? $unitPrice($default) : $basePrice;
    $qtyMax = $default ? min($maxQuantity, max(1, (int) $default->stock)) : $maxQuantity;
    $image = $product->image ? asset($product->image->image) : '';
@endphp
<section class="lp-section lp-checkout" id="checkout">
    <div class="lp-wrap lp-checkout-wrap">
        @if($open)
            <div class="lp-checkout-head">
                @if($page->show('checkout.eyebrow'))
                <p class="lp-eyebrow" {!! $page->attr('checkout.eyebrow') !!}>{{ $page->text('checkout.eyebrow') }}</p>
                @endif
                <h2 class="lp-h2" {!! $page->attr('checkout.heading') !!}>{{ $page->text('checkout.heading') }}</h2>
                @if($page->show('checkout.subtitle'))
                <p class="lp-lead" {!! $page->attr('checkout.subtitle') !!}>{{ $page->text('checkout.subtitle') }}</p>
                @endif
            </div>

            <form id="lp-order" class="lp-order" action="{{ route('customer.ordersave') }}" method="POST"
                data-base="{{ $basePrice }}" @unless($page->isEditable()) data-incomplete="{{ route('landing.incomplete') }}" @endunless>
                @csrf
                <input type="hidden" name="product" value="{{ $product->id }}">
                <input type="hidden" name="landing" value="{{ $page->landing()->id }}">
                {{-- Landing pages take cash on delivery only. --}}
                <input type="hidden" name="payment_method" value="cod">

                <div class="lp-form">
                    {{-- 1. Product options --}}
                    <h3 class="lp-step" {!! $page->attr('checkout.section_product') !!}>{{ $page->text('checkout.section_product') }}</h3>

                    @if($showColours)
                    <div class="lp-field">
                        <span class="lp-label"><span {!! $page->attr('checkout.label_color') !!}>{{ $page->text('checkout.label_color') }}</span> <b class="lp-req">*</b></span>
                        <div class="lp-options">
                            @foreach($colours as $colour)
                                @php
                                    $colourVariant = $variants->where('color_id', $colour->color_id)->first($inStock)
                                        ?? $variants->firstWhere('color_id', $colour->color_id);
                                    $colourInStock = $variants->where('color_id', $colour->color_id)->contains($inStock);
                                    $colourPicked = $showSizes
                                        ? $colour->color_id === $defaultColour
                                        : $colourVariant->id === optional($default)->id;
                                @endphp
                                <label class="lp-opt lp-swatch-opt{{ $colourPicked ? ' is-picked' : '' }}{{ $colourInStock ? '' : ' is-out' }}">
                                    @if($showSizes)
                                        <input type="radio" name="color" value="{{ $colour->color_id }}"
                                            {{ $colourPicked ? 'checked' : '' }} {{ $colourInStock ? '' : 'disabled' }}>
                                    @else
                                        <input type="radio" name="variant" value="{{ $colourVariant->id }}"
                                            data-price="{{ $unitPrice($colourVariant) }}" data-stock="{{ (int) $colourVariant->stock }}"
                                            data-label="{{ $optionLabel($colourVariant) }}"
                                            {{ $colourPicked ? 'checked' : '' }} {{ $inStock($colourVariant) ? '' : 'disabled' }}>
                                    @endif
                                    <span class="lp-swatch" style="background: {{ $swatch($colour->color) }}"></span>
                                    <span class="lp-opt-name">{{ optional($colour->color)->colorName }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($showSizes)
                    <div class="lp-field">
                        <span class="lp-label"><span {!! $page->attr('checkout.label_size') !!}>{{ $page->text('checkout.label_size') }}</span> <b class="lp-req">*</b></span>
                        <div class="lp-options lp-sizes">
                            @foreach($variants as $variant)
                                @php
                                    $hidden = $showColours && (string) $variant->color_id !== (string) $defaultColour;
                                    $picked = $default !== null && $variant->id === $default->id;
                                    $extra = $unitPrice($variant) - $basePrice;
                                @endphp
                                <label class="lp-opt lp-size{{ $picked ? ' is-picked' : '' }}{{ $inStock($variant) ? '' : ' is-out' }}" @if($hidden) hidden @endif>
                                    <input type="radio" name="variant" value="{{ $variant->id }}" data-color="{{ $variant->color_id }}"
                                        data-price="{{ $unitPrice($variant) }}" data-stock="{{ (int) $variant->stock }}"
                                        data-label="{{ $optionLabel($variant) }}"
                                        {{ $picked ? 'checked' : '' }} {{ $inStock($variant) ? '' : 'disabled' }}>
                                    <span class="lp-opt-name">{{ optional($variant->size)->sizeName ?: __('Standard') }}</span>
                                    @if($extra > 0)<small>+৳{{ number_format($extra, 2) }}</small>@endif
                                </label>
                            @endforeach
                        </div>
                        @if($page->show('checkout.size_help'))
                        <p class="lp-hint" {!! $page->attr('checkout.size_help') !!}>{{ $page->text('checkout.size_help') }}</p>
                        @endif
                    </div>
                    @endif

                    @if(! $showColours && ! $showSizes && $default)
                        {{-- A plain product has one option: its variant is chosen for the customer. --}}
                        <input type="hidden" name="variant" value="{{ $default->id }}" data-price="{{ $defaultPrice }}"
                            data-stock="{{ (int) $default->stock }}" data-label="{{ $optionLabel($default) }}">
                    @endif

                    <div class="lp-field">
                        <span class="lp-label"><span {!! $page->attr('checkout.label_quantity') !!}>{{ $page->text('checkout.label_quantity') }}</span></span>
                        <div class="lp-qty">
                            <button type="button" class="lp-qty-btn" data-step="-1" aria-label="−">−</button>
                            <input type="number" id="lp-qty" name="quantity" value="1" min="1" max="{{ $qtyMax }}" readonly>
                            <button type="button" class="lp-qty-btn" data-step="1" aria-label="+">+</button>
                        </div>
                    </div>

                    <div class="lp-field">
                        <label for="lp-note"><span {!! $page->attr('checkout.label_note') !!}>{{ $page->text('checkout.label_note') }}</span></label>
                        <textarea id="lp-note" name="order_note" rows="3" placeholder="{{ $page->text('checkout.placeholder_note') }}"></textarea>
                        @if($page->isEditable())
                            <small class="lp-ph-edit" {!! $page->attr('checkout.placeholder_note') !!} data-ph-for="lp-note">{{ $page->text('checkout.placeholder_note') }}</small>
                        @endif
                    </div>

                    {{-- 2. Customer details --}}
                    <h3 class="lp-step" {!! $page->attr('checkout.section_info') !!}>{{ $page->text('checkout.section_info') }}</h3>

                    <div class="lp-field">
                        <label for="lp-name"><span {!! $page->attr('checkout.label_name') !!}>{{ $page->text('checkout.label_name') }}</span> <b class="lp-req">*</b></label>
                        <input id="lp-name" type="text" name="name" required placeholder="{{ $page->text('checkout.placeholder_name') }}">
                        @if($page->isEditable())
                            <small class="lp-ph-edit" {!! $page->attr('checkout.placeholder_name') !!} data-ph-for="lp-name">{{ $page->text('checkout.placeholder_name') }}</small>
                        @endif
                    </div>
                    <div class="lp-field">
                        <label for="lp-phone"><span {!! $page->attr('checkout.label_phone') !!}>{{ $page->text('checkout.label_phone') }}</span> <b class="lp-req">*</b></label>
                        <input id="lp-phone" type="tel" name="phone" required minlength="11" maxlength="11"
                            pattern="0(13|14|15|16|17|18|19)[0-9]{8}" placeholder="{{ $page->text('checkout.placeholder_phone') }}">
                        @if($page->show('checkout.phone_hint'))
                        <p class="lp-hint" {!! $page->attr('checkout.phone_hint') !!}>{{ $page->text('checkout.phone_hint') }}</p>
                        @endif
                        @if($page->isEditable())
                            <small class="lp-ph-edit" {!! $page->attr('checkout.placeholder_phone') !!} data-ph-for="lp-phone">{{ $page->text('checkout.placeholder_phone') }}</small>
                        @endif
                    </div>
                    <div class="lp-field">
                        <label for="lp-address"><span {!! $page->attr('checkout.label_address') !!}>{{ $page->text('checkout.label_address') }}</span> <b class="lp-req">*</b></label>
                        <textarea id="lp-address" name="address" rows="3" required placeholder="{{ $page->text('checkout.placeholder_address') }}"></textarea>
                        @if($page->isEditable())
                            <small class="lp-ph-edit" {!! $page->attr('checkout.placeholder_address') !!} data-ph-for="lp-address">{{ $page->text('checkout.placeholder_address') }}</small>
                        @endif
                    </div>

                    {{-- 3. Delivery area: one card per area, the charge shown on it --}}
                    <h3 class="lp-step" {!! $page->attr('checkout.section_area') !!}>{{ $page->text('checkout.section_area') }} <b class="lp-req">*</b></h3>
                    @foreach($areas as $area)
                        <label class="lp-opt lp-area">
                            <input type="radio" name="area" value="{{ $area->id }}" data-amount="{{ (float) $area->amount }}" required>
                            <span class="lp-radio" aria-hidden="true"></span>
                            <span class="lp-area-name">{{ $area->name }}</span>
                            <strong>৳{{ number_format((float) $area->amount, 2) }}</strong>
                        </label>
                    @endforeach
                </div>

                <aside class="lp-summary">
                    <div class="lp-summary-product">
                        <div class="lp-thumb">
                            @if($image !== '')<img src="{{ $image }}" alt="{{ $product->name }}">@endif
                        </div>
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <small id="lp-option">{{ $default ? $optionLabel($default) : '' }}</small>
                            <small id="lp-unit">৳{{ number_format($defaultPrice, 2) }} × 1</small>
                        </div>
                    </div>

                    <dl class="lp-summary-rows">
                        <div>
                            <dt {!! $page->attr('checkout.label_subtotal') !!}>{{ $page->text('checkout.label_subtotal') }}</dt>
                            <dd id="lp-subtotal">৳{{ number_format($defaultPrice, 2) }}</dd>
                        </div>
                        <div>
                            <dt {!! $page->attr('checkout.label_delivery') !!}>{{ $page->text('checkout.label_delivery') }}</dt>
                            <dd id="lp-delivery" data-pick="{{ $page->text('checkout.delivery_pick') }}">{{ $page->text('checkout.delivery_pick') }}</dd>
                        </div>
                    </dl>

                    <div class="lp-summary-total">
                        <span {!! $page->attr('checkout.label_price') !!}>{{ $page->text('checkout.label_price') }}</span>
                        <strong id="lp-price">৳{{ number_format($defaultPrice, 2) }}</strong>
                    </div>

                    <button class="lp-btn lp-btn-wide" type="submit" {{ $variants->isNotEmpty() && ! $variants->contains($inStock) ? 'disabled' : '' }}>
                        <span {!! $page->attr('checkout.submit') !!}>{{ $page->text('checkout.submit') }}</span>
                        — <span id="lp-btn-total">৳{{ number_format($defaultPrice, 2) }}</span>
                    </button>
                    @if($page->text('checkout.note') !== '' || $page->isEditable())
                        <p class="lp-note lp-center" {!! $page->attr('checkout.note') !!}>{{ $page->text('checkout.note') }}</p>
                    @endif
                </aside>
            </form>

            <script>
                (function () {
                    var form = document.getElementById('lp-order');
                    if (!form) return;

                    var qty = document.getElementById('lp-qty');
                    var maxQty = {{ $maxQuantity }};
                    var submit = form.querySelector('button[type=submit]');
                    var delivery = document.getElementById('lp-delivery');

                    var money = function (n) {
                        return '৳' + Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    };
                    var show = function (id, value) { document.getElementById(id).textContent = value; };

                    // The option the customer has chosen: a size or colour-only radio, or a plain product's hidden variant.
                    var chosen = function () {
                        return form.querySelector('input[name=variant]:checked') || form.querySelector('input[name=variant][type=hidden]');
                    };

                    function refresh() {
                        form.querySelectorAll('.lp-opt input').forEach(function (input) {
                            input.closest('.lp-opt').classList.toggle('is-picked', input.checked);
                        });

                        var variant = chosen();
                        var hasOptions = form.querySelectorAll('input[name=variant]').length > 0;
                        submit.disabled = hasOptions && ! variant;

                        var unit = variant ? Number(variant.dataset.price) : Number(form.dataset.base);
                        var stock = variant ? Number(variant.dataset.stock) : maxQty;
                        qty.max = Math.max(1, Math.min(maxQty, stock));
                        if (Number(qty.value) > Number(qty.max)) qty.value = qty.max;

                        var count = Math.max(1, Number(qty.value) || 1);
                        var area = form.querySelector('input[name=area]:checked');
                        var charge = area ? Number(area.dataset.amount) : null;
                        var subtotal = unit * count;
                        var total = subtotal + (charge || 0);

                        show('lp-option', variant ? variant.dataset.label : '');
                        show('lp-unit', money(unit) + ' × ' + count);
                        show('lp-subtotal', money(subtotal));
                        delivery.textContent = charge === null ? delivery.dataset.pick : money(charge);
                        show('lp-price', money(total));
                        show('lp-btn-total', money(total));
                    }

                    // Choosing a colour shows its sizes and picks the first size in stock.
                    form.querySelectorAll('input[name=color]').forEach(function (colour) {
                        colour.addEventListener('change', function () {
                            var first = null;
                            form.querySelectorAll('input[name=variant]').forEach(function (size) {
                                var mine = size.dataset.color === colour.value;
                                size.closest('.lp-opt').hidden = ! mine;
                                size.checked = false;
                                if (mine && ! size.disabled && ! first) first = size;
                            });
                            if (first) first.checked = true;
                        });
                    });

                    form.querySelectorAll('[data-step]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            qty.value = Math.max(1, Math.min(Number(qty.max), Number(qty.value) + Number(button.dataset.step)));
                            refresh();
                        });
                    });

                    form.addEventListener('change', refresh);
                    refresh();

                    // A filled-in order that is not placed is kept, like the shop's incomplete orders: saved two
                    // seconds after the last change once name, phone and address are in, and again when the page is left.
                    var incompleteUrl = form.dataset.incomplete;
                    if (incompleteUrl) {
                        var timer = null;
                        var submitting = false;
                        var details = function () {
                            var fields = new FormData(form);
                            var data = {};
                            ['landing', 'product', 'variant', 'quantity', 'area', 'name', 'phone', 'address', 'order_note', '_token']
                                .forEach(function (key) { data[key] = fields.get(key) || ''; });
                            return data;
                        };
                        var ready = function (data) { return data.name !== '' && data.phone !== '' && data.address !== ''; };

                        var save = function () {
                            if (submitting) return;
                            clearTimeout(timer);
                            timer = setTimeout(function () {
                                var data = details();
                                if (! ready(data)) return;
                                fetch(incompleteUrl, {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': data._token},
                                    body: JSON.stringify(data),
                                    keepalive: true
                                }).catch(function () {});
                            }, 2000);
                        };
                        form.addEventListener('input', save);
                        form.addEventListener('change', save);
                        form.addEventListener('submit', function () { submitting = true; });
                        window.addEventListener('pagehide', function () {
                            var data = details();
                            if (submitting || ! ready(data)) return;
                            navigator.sendBeacon(incompleteUrl, new Blob([JSON.stringify(data)], {type: 'application/json'}));
                        });
                    }
                })();
            </script>
        @else
            <h2 class="lp-h2">{{ __('This offer is closed') }}</h2>
            <p class="lp-closed">{{ __('The order form for this offer is not open right now.') }}</p>
        @endif
    </div>
</section>
