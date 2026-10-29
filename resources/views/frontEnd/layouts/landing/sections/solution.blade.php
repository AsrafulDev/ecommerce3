<section class="lp-section lp-alt" id="solution">
    <div class="lp-wrap lp-narrow">
        @if($page->show('solution.heading'))
        <h2 class="lp-h2" {!! $page->attr('solution.heading') !!}>{{ $page->text('solution.heading') }}</h2>
        @endif
        @if($page->show('solution.body'))
        <div class="lp-body" {!! $page->attr('solution.body', 'rich') !!}>{!! $page->rich('solution.body') !!}</div>
        @endif
    </div>
</section>
