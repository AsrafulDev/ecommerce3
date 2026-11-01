{{-- Notice bar, then the store logo (or the brand text when the store has no logo) and the order button on the right. --}}
@if($page->show('nav.notice'))
<div class="gr-notice" {!! $page->attr('nav.notice') !!}>{{ $page->text('nav.notice') }}</div>
@endif
<header class="gr-nav">
    <div class="gr-nav-inner">
        <div class="gr-brand">
            @if(filled($generalsetting?->dark_logo))
            <img class="gr-logo" src="{{ asset($generalsetting->dark_logo) }}" alt="{{ $generalsetting->name }}">
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
        <a class="gr-nav-cta" href="#checkout" {!! $page->attr('nav.cta') !!}>{{ $page->text('nav.cta') }}</a>
        @endif
    </div>
</header>
