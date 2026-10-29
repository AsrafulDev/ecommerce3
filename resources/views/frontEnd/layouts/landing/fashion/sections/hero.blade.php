{{-- Hero: copy and price on the left, the arch-shaped photo on the right, perks under the buttons. --}}
@php
    $heroImage = $page->image('hero.image') ?: ($product->image ? asset($product->image->image) : '');
    $perks = $page->items('hero', 'perk');
@endphp
<section class="fx-hero" id="hero">
    <div class="fx-wrap fx-hero-grid">
        <div class="fx-hero-copy">
            @if($page->show('hero.eyebrow'))
            <p class="fx-eyebrow" {!! $page->attr('hero.eyebrow') !!}>{{ $page->text('hero.eyebrow') }}</p>
            @endif
            @if($page->show('hero.title'))
            <h1 class="fx-title" {!! $page->attr('hero.title') !!}>{{ $page->text('hero.title') }}</h1>
            @endif
            @if($page->show('hero.tagline'))
            <p class="fx-tagline" {!! $page->attr('hero.tagline') !!}>{{ $page->text('hero.tagline') }}</p>
            @endif
            @if($page->show('hero.subtitle'))
            <div class="fx-lead" {!! $page->attr('hero.subtitle', 'rich') !!}>{!! $page->rich('hero.subtitle') !!}</div>
            @endif
            <div class="fx-price-row">
                @if($page->show('hero.price'))
                <span class="fx-price" {!! $page->attr('hero.price') !!}>{{ $page->text('hero.price') }}</span>
                @endif
                @if($page->show('hero.old_price'))
                <span class="fx-old" {!! $page->attr('hero.old_price') !!}>{{ $page->text('hero.old_price') }}</span>
                @endif
                @if($page->show('hero.badge'))
                <span class="fx-badge" {!! $page->attr('hero.badge') !!}>{{ $page->text('hero.badge') }}</span>
                @endif
            </div>
            @if($page->show('hero.note'))
            <p class="fx-note" {!! $page->attr('hero.note') !!}>{{ $page->text('hero.note') }}</p>
            @endif
            <div class="fx-actions">
                @if($open)
                    <a class="fx-btn" href="#checkout" {!! $page->attr('hero.cta_label') !!}>{{ $page->text('hero.cta_label') }} <span aria-hidden="true">→</span></a>
                @endif
                <a class="fx-btn-ghost" href="#details" {!! $page->attr('hero.cta_secondary') !!}>{{ $page->text('hero.cta_secondary') }} <span aria-hidden="true">↓</span></a>
            </div>
            <ul class="fx-perks" {!! $page->listAttr('hero', 'perk') !!}>
                @foreach($perks as $row)
                    <li class="{{ trim($page->rowClass($row)) }}" {!! $page->attr("hero.perk.{$row['_n']}.text") !!}>✓ {{ $row['text'] }}</li>
                @endforeach
            </ul>
        </div>
        <div class="fx-hero-media">
            <div class="fx-arch" {!! $page->attr('hero.image', 'image') !!}>
                @if($heroImage !== '')
                    <img src="{{ $heroImage }}" alt="{{ $page->text('hero.title') }}">
                @endif
                @if($page->isEditable())
                    <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                @endif
            </div>
        </div>
    </div>
</section>
