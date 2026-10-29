{{-- Top bar: brand with its small subtitle, and the order button on the right. --}}
<header class="fx-nav">
    <div class="fx-nav-inner">
        <div class="fx-brand">
            @if($page->show('nav.brand'))
            <span {!! $page->attr('nav.brand') !!}>{{ $page->text('nav.brand') }}</span>
            @endif
            @if($page->show('nav.brand_sub'))
            <small {!! $page->attr('nav.brand_sub') !!}>{{ $page->text('nav.brand_sub') }}</small>
            @endif
        </div>
        @if($page->show('nav.cta'))
        <a class="fx-nav-cta" href="#checkout" {!! $page->attr('nav.cta') !!}>{{ $page->text('nav.cta') }}</a>
        @endif
    </div>
</header>
