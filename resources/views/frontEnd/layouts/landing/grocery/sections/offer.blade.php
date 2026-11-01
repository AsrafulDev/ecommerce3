{{-- Offer: three size tiers, the bundle discount band with the order button, and the offer notes. --}}
@php $sizes = $page->items('offer', 'size'); @endphp
<section class="gr-section gr-tint" id="offer">
    <div class="gr-wrap">
        <div class="gr-center">
            @if($page->show('offer.eyebrow'))
            <p class="gr-eyebrow" {!! $page->attr('offer.eyebrow') !!}>{{ $page->text('offer.eyebrow') }}</p>
            @endif
            @if($page->show('offer.heading'))
            <h2 class="gr-h2" {!! $page->attr('offer.heading') !!}>{{ $page->text('offer.heading') }}</h2>
            @endif
            @if($page->show('offer.subtitle'))
            <p class="gr-sub" {!! $page->attr('offer.subtitle') !!}>{{ $page->text('offer.subtitle') }}</p>
            @endif
        </div>

        <div class="gr-tiers" {!! $page->listAttr('offer', 'size') !!}>
            @foreach($sizes as $row)
                <div class="gr-tier{{ $row['_n'] === 2 ? ' is-picked' : '' }}{{ $page->rowClass($row) }}">
                    @if($page->isEditable() || $row['badge'] !== '')
                    <span class="gr-tier-badge" {!! $page->attr("offer.size.{$row['_n']}.badge") !!}>{{ $row['badge'] }}</span>
                    @endif
                    @if($page->isEditable() || $row['name'] !== '')
                    <div class="gr-tier-name" {!! $page->attr("offer.size.{$row['_n']}.name") !!}>{{ $row['name'] }}</div>
                    @endif
                    @if($page->isEditable() || $row['size'] !== '')
                    <div class="gr-tier-size" {!! $page->attr("offer.size.{$row['_n']}.size") !!}>{{ $row['size'] }}</div>
                    @endif
                    <div class="gr-tier-price">
                        @if($page->isEditable() || $row['price'] !== '')
                        <strong {!! $page->attr("offer.size.{$row['_n']}.price") !!}>{{ $row['price'] }}</strong>
                        @endif
                        @if($page->isEditable() || $row['old_price'] !== '')
                        <s {!! $page->attr("offer.size.{$row['_n']}.old_price") !!}>{{ $row['old_price'] }}</s>
                        @endif
                    </div>
                    @if($page->isEditable() || $row['unit'] !== '')
                    <div class="gr-tier-unit" {!! $page->attr("offer.size.{$row['_n']}.unit") !!}>{{ $row['unit'] }}</div>
                    @endif
                    @if($page->isEditable() || $row['saving'] !== '')
                    <span class="gr-tier-save" {!! $page->attr("offer.size.{$row['_n']}.saving") !!}>{{ $row['saving'] }}</span>
                    @endif
                    {!! $page->rowRemove('offer', 'size', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton('offer', 'size') !!}

        <div class="gr-bundle">
            <div>
                @if($page->show('offer.bundle_title'))
                <p class="gr-bundle-title" {!! $page->attr('offer.bundle_title') !!}>{{ $page->text('offer.bundle_title') }}</p>
                @endif
                @if($page->show('offer.bundle_1'))
                <span class="gr-pill" {!! $page->attr('offer.bundle_1') !!}>{{ $page->text('offer.bundle_1') }}</span>
                @endif
                @if($page->show('offer.bundle_2'))
                <span class="gr-pill" {!! $page->attr('offer.bundle_2') !!}>{{ $page->text('offer.bundle_2') }}</span>
                @endif
                @if($page->show('offer.note'))
                <div class="gr-bundle-note" {!! $page->attr('offer.note', 'rich') !!}>{!! $page->rich('offer.note') !!}</div>
                @endif
            </div>
            @if($open)
                <a class="gr-btn" href="#checkout" {!! $page->attr('offer.cta_label') !!}>{{ $page->text('offer.cta_label') }} <span aria-hidden="true">→</span></a>
            @endif
        </div>
    </div>
</section>
