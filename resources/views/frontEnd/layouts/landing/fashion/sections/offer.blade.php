{{-- Offer: a product card with the photo on the left; name, price, the offer tiers, the order button and the notes on the right. --}}
@php
    $tiers = $page->items('offer', 'tier');
    $image = $page->image('offer.image') ?: ($product->image ? asset($product->image->image) : '');
@endphp
<section class="fx-section fx-alt" id="offer">
    <div class="fx-wrap">
        @if($page->show('offer.eyebrow'))
        <p class="fx-eyebrow" {!! $page->attr('offer.eyebrow') !!}>{{ $page->text('offer.eyebrow') }}</p>
        @endif
        @if($page->show('offer.heading'))
        <h2 class="fx-h2" {!! $page->attr('offer.heading') !!}>{{ $page->text('offer.heading') }}</h2>
        @endif
        <div class="fx-offer">
            <div class="fx-offer-photo" {!! $page->attr('offer.image', 'image') !!}>
                @if($image !== '')<img src="{{ $image }}" alt="{{ $page->text('offer.product_name') }}">@endif
                @if($page->isEditable())
                    <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                @endif
            </div>
            <div class="fx-offer-body">
                @if($page->show('offer.product_sub'))
                <p class="fx-sub" {!! $page->attr('offer.product_sub') !!}>{{ $page->text('offer.product_sub') }}</p>
                @endif
                @if($page->show('offer.product_name'))
                <h3 class="fx-h3" {!! $page->attr('offer.product_name') !!}>{{ $page->text('offer.product_name') }}</h3>
                @endif
                <p class="fx-price-row">
                    @if($page->show('offer.price'))
                    <span class="fx-price" {!! $page->attr('offer.price') !!}>{{ $page->text('offer.price') }}</span>
                    @endif
                    @if($page->show('offer.old_price'))
                    <span class="fx-old" {!! $page->attr('offer.old_price') !!}>{{ $page->text('offer.old_price') }}</span>
                    @endif
                </p>
                <div class="fx-tiers" {!! $page->listAttr('offer', 'tier') !!}>
                    @foreach($tiers as $row)
                        <div class="fx-tier{{ $row['_n'] === 1 ? ' is-picked' : '' }}{{ $page->rowClass($row) }}">
                            @if($page->isEditable() || $row['title'] !== '')
                            <strong {!! $page->attr("offer.tier.{$row['_n']}.title") !!}>{{ $row['title'] }}</strong>
                            @endif
                            @if($page->isEditable() || $row['text'] !== '')
                            <span {!! $page->attr("offer.tier.{$row['_n']}.text") !!}>{{ $row['text'] }}</span>
                            @endif
                            {!! $page->rowRemove('offer', 'tier', $row) !!}
                        </div>
                    @endforeach
                </div>
                @if($open)
                    <a class="fx-btn fx-btn-wide" href="#checkout" {!! $page->attr('offer.cta_label') !!}>{{ $page->text('offer.cta_label') }} <span aria-hidden="true">→</span></a>
                @endif
                @if($page->show('offer.note'))
                <div class="fx-body fx-small" {!! $page->attr('offer.note', 'rich') !!}>{!! $page->rich('offer.note') !!}</div>
                @endif
            </div>
        </div>
    </div>
</section>
