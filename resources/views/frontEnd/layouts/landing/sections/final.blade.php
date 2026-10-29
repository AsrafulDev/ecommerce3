{{-- Closing call to action: heading, a line of text and the order button. --}}
<section class="lp-section lp-final" id="{{ $section }}">
    <div class="lp-wrap lp-narrow">
        <h2 class="lp-h2" {!! $page->attr("{$section}.heading") !!}>{{ $page->text("{$section}.heading") }}</h2>
        <p class="lp-lead" {!! $page->attr("{$section}.text") !!}>{{ $page->text("{$section}.text") }}</p>
        @if($open)
            <a class="lp-btn" href="#checkout" {!! $page->attr("{$section}.cta_label") !!}>{{ $page->text("{$section}.cta_label") }}</a>
        @endif
    </div>
</section>
