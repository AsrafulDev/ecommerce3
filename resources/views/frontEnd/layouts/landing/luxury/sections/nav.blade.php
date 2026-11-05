{{-- Top bar: the store logo (or the brand text when the store has no logo), and the order button on the right. --}}
<header class="lx-nav">
    <div class="lx-nav-inner">
        <div class="lx-brand">
            @if(filled($generalsetting?->dark_logo))
            <img class="lx-logo" src="{{ asset($generalsetting->dark_logo) }}" alt="{{ $generalsetting->name }}">
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
        <a class="lx-nav-cta" href="#checkout" {!! $page->attr('nav.cta') !!}>{{ $page->text('nav.cta') }}</a>
        @endif
    </div>
</header>
