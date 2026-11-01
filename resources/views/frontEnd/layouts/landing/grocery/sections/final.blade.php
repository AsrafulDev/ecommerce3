{{-- Closing band: the product line, three reasons, and the order button with its payment note. --}}
@php $points = $page->items('final', 'point'); @endphp
<section class="gr-final-wrap" id="final">
    <div class="gr-wrap">
        <div class="gr-final">
            <div>
                @if($page->show('final.eyebrow'))
                <p class="gr-eyebrow" {!! $page->attr('final.eyebrow') !!}>{{ $page->text('final.eyebrow') }}</p>
                @endif
                @if($page->show('final.heading'))
                <h2 class="gr-h2" {!! $page->attr('final.heading') !!}>{{ $page->text('final.heading') }}</h2>
                @endif
                <ul class="gr-checks" {!! $page->listAttr('final', 'point') !!}>
                    @foreach($points as $row)
                        <li class="{{ trim($page->rowClass($row)) }}" {!! $page->attr("final.point.{$row['_n']}.text") !!}>{{ $row['text'] }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="gr-final-cta">
                @if($open)
                    <a class="gr-btn" href="#checkout" {!! $page->attr('final.cta_label') !!}>{{ $page->text('final.cta_label') }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                @endif
                @if($page->show('final.cta_note'))
                <small {!! $page->attr('final.cta_note') !!}>{{ $page->text('final.cta_note') }}</small>
                @endif
            </div>
        </div>
    </div>
</section>
