{{-- Hero: the product name and price on the left, the bottle photo on the right, perks under the buttons. --}}
@php
    $heroImage = $page->image('hero.image') ?: ($product->image ? asset($product->image->image) : '');
    $perks = $page->items('hero', 'perk');
@endphp
<section class="gr-hero" id="hero">
    <div class="gr-wrap gr-hero-grid">
        <div>
            @if($page->show('hero.eyebrow'))
            <p class="gr-eyebrow" {!! $page->attr('hero.eyebrow') !!}>{{ $page->text('hero.eyebrow') }}</p>
            @endif
            @if($page->show('hero.title'))
            <h1 class="gr-title" {!! $page->attr('hero.title') !!}>{{ $page->text('hero.title') }}</h1>
            @endif
            @if($page->show('hero.tagline'))
            <p class="gr-tagline" {!! $page->attr('hero.tagline') !!}>{{ $page->text('hero.tagline') }}</p>
            @endif
            @if($page->show('hero.subtitle'))
            <div class="gr-lead" {!! $page->attr('hero.subtitle', 'rich') !!}>{!! $page->rich('hero.subtitle') !!}</div>
            @endif
            <div class="gr-price-row">
                @if($page->show('hero.price'))
                <span class="gr-price" {!! $page->attr('hero.price') !!}>{{ $page->text('hero.price') }}</span>
                @endif
                @if($page->show('hero.old_price'))
                <span class="gr-old" {!! $page->attr('hero.old_price') !!}>{{ $page->text('hero.old_price') }}</span>
                @endif
                @if($page->show('hero.badge'))
                <span class="gr-badge" {!! $page->attr('hero.badge') !!}>{{ $page->text('hero.badge') }}</span>
                @endif
            </div>
            @if($page->show('hero.size_note'))
            <p class="gr-size" {!! $page->attr('hero.size_note') !!}>{{ $page->text('hero.size_note') }}</p>
            @endif
            <div class="gr-actions">
                @if($open)
                    <a class="gr-btn" href="#checkout" {!! $page->attr('hero.cta_label') !!}>{{ $page->text('hero.cta_label') }} <span aria-hidden="true">→</span></a>
                @endif
                <a class="gr-btn-ghost" href="#details" {!! $page->attr('hero.cta_secondary') !!}>{{ $page->text('hero.cta_secondary') }} <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
            </div>
            <ul class="gr-checks" {!! $page->listAttr('hero', 'perk') !!}>
                @foreach($perks as $row)
                    <li class="{{ trim($page->rowClass($row)) }}" {!! $page->attr("hero.perk.{$row['_n']}.text") !!}>{{ $row['text'] }}</li>
                @endforeach
            </ul>
        </div>
        <div class="gr-hero-media" {!! $page->attr('hero.image', 'image') !!}>
            @if($heroImage !== '')
                <img src="{{ $heroImage }}" alt="{{ $page->text('hero.title') }}">
            @endif
            @if($page->isEditable())
                <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
            @endif
        </div>
    </div>
</section>
