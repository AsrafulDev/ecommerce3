{{-- Top bar: the store logo (or the brand text when the store has no logo), and the order button on the right. --}}
<header class="fx-nav">
    <div class="fx-nav-inner">
        <div class="fx-brand">
            @if(filled($generalsetting?->dark_logo))
            <img class="fx-logo" src="{{ asset($generalsetting->dark_logo) }}" alt="{{ $generalsetting->name }}">
            @else
            @if($page->show('nav.brand'))
            <span {!! $page->attr('nav.brand') !!}>{{ $page->text('nav.brand') }}</span>
            @endif
            @if($page->show('nav.brand_sub'))
            <small {!! $page->attr('nav.brand_sub') !!}>{{ $page->text('nav.brand_sub') }}</small>
            @endif
            @endif
        </div>
        @if($page->show('nav.cta'))
        <a class="fx-nav-cta" href="#checkout" {!! $page->attr('nav.cta') !!}>{{ $page->text('nav.cta') }}</a>
        @endif
    </div>
</header>
