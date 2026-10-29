{{-- Closing banner: a full-width photo with the offer text over it, and the order button. --}}
@php $bg = $page->image('final.image'); @endphp
<section class="fx-final" id="final" data-bg="1" {!! $page->attr('final.image', 'image') !!} @if($bg !== '') style="background-image:url('{{ $bg }}')" @endif>
    <div class="fx-final-overlay"></div>
    <div class="fx-wrap fx-final-copy">
        @if($page->show('final.eyebrow'))
        <p class="fx-eyebrow" {!! $page->attr('final.eyebrow') !!}>{{ $page->text('final.eyebrow') }}</p>
        @endif
        @if($page->show('final.heading'))
        <h2 class="fx-h2" {!! $page->attr('final.heading') !!}>{{ $page->text('final.heading') }}</h2>
        @endif
        @if($page->show('final.text'))
        <p class="fx-sub" {!! $page->attr('final.text') !!}>{{ $page->text('final.text') }}</p>
        @endif
        <p class="fx-price-row">
            @if($page->show('final.price'))
            <span class="fx-price" {!! $page->attr('final.price') !!}>{{ $page->text('final.price') }}</span>
            @endif
            @if($page->show('final.old_price'))
            <span class="fx-old" {!! $page->attr('final.old_price') !!}>{{ $page->text('final.old_price') }}</span>
            @endif
        </p>
        @if($open)
            <a class="fx-btn" href="#checkout" {!! $page->attr('final.cta_label') !!}>{{ $page->text('final.cta_label') }} <span aria-hidden="true">→</span></a>
        @endif
        @if($page->isEditable())
            <button type="button" class="lp-img-change">{{ __('Change background') }}</button>
        @endif
    </div>
</section>
