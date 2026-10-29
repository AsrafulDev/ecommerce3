{{-- Hero: eyebrow, headline, lead text, image and the order button. Shared by every template. --}}
@php
    $heroImage = $page->image('hero.image') ?: ($product->image ? asset($product->image->image) : '');
@endphp
<section class="lp-hero" id="hero">
    <div class="lp-wrap lp-hero-grid">
        <div>
            @if($page->text('hero.eyebrow') !== '' || $page->isEditable())
                @if($page->show('hero.eyebrow'))
                <p class="lp-eyebrow" {!! $page->attr('hero.eyebrow') !!}>{{ $page->text('hero.eyebrow') }}</p>
                @endif
            @endif
            @if($page->show('hero.title'))
            <h1 class="lp-title" {!! $page->attr('hero.title') !!}>{{ $page->text('hero.title') }}</h1>
            @endif
            @if($page->rich('hero.subtitle') !== '' || $page->isEditable())
                @if($page->show('hero.subtitle'))
                <div class="lp-lead" {!! $page->attr('hero.subtitle', 'rich') !!}>{!! $page->rich('hero.subtitle') !!}</div>
                @endif
            @endif
            @if($open)
                @if($page->show('hero.cta_label'))
                <a class="lp-btn" href="#checkout" {!! $page->attr('hero.cta_label') !!}>{{ $page->text('hero.cta_label') }}</a>
                @endif
            @endif
        </div>
        <div class="lp-img" {!! $page->attr('hero.image', 'image') !!}>
            @if($heroImage !== '')
                <img class="lp-hero-img" src="{{ $heroImage }}" alt="{{ $page->text('hero.title') }}">
            @endif
            @if($page->isEditable())
                <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
            @endif
        </div>
    </div>
</section>
