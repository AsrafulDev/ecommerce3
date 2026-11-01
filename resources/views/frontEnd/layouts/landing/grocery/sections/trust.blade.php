{{-- Trust: four proof points, each with an icon the owner picks and a line that backs it up. --}}
@php $rows = $page->items('trust'); @endphp
<section class="gr-section" id="trust">
    <div class="gr-wrap">
        @if($page->show('trust.eyebrow'))
        <p class="gr-eyebrow" {!! $page->attr('trust.eyebrow') !!}>{{ $page->text('trust.eyebrow') }}</p>
        @endif
        @if($page->show('trust.heading'))
        <h2 class="gr-h2" {!! $page->attr('trust.heading') !!}>{{ $page->text('trust.heading') }}</h2>
        @endif
        @if($page->show('trust.subtitle'))
        <p class="gr-sub" {!! $page->attr('trust.subtitle') !!}>{{ $page->text('trust.subtitle') }}</p>
        @endif
        <div class="gr-cards" {!! $page->listAttr('trust') !!}>
            @foreach($rows as $row)
                <div class="gr-card{{ $page->rowClass($row) }}">
                    {!! $page->icon("trust.item.{$row['_n']}.icon", $row['icon'], 'gr-dot') !!}
                    @if($page->isEditable() || $row['title'] !== '')
                    <h3 {!! $page->attr("trust.item.{$row['_n']}.title") !!}>{{ $row['title'] }}</h3>
                    @endif
                    @if($page->isEditable() || $row['text'] !== '')
                    <p {!! $page->attr("trust.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</p>
                    @endif
                    {!! $page->rowRemove('trust', 'item', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton('trust') !!}
    </div>
</section>
