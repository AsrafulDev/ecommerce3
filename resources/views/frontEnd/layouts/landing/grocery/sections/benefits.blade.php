{{-- Benefits: eyebrow, heading and a short line, then four cards, each with a Font Awesome icon the owner picks. --}}
@php $rows = $page->items('benefits'); @endphp
<section class="gr-section" id="benefits">
    <div class="gr-wrap">
        @if($page->show('benefits.eyebrow'))
        <p class="gr-eyebrow" {!! $page->attr('benefits.eyebrow') !!}>{{ $page->text('benefits.eyebrow') }}</p>
        @endif
        @if($page->show('benefits.heading'))
        <h2 class="gr-h2" {!! $page->attr('benefits.heading') !!}>{{ $page->text('benefits.heading') }}</h2>
        @endif
        @if($page->show('benefits.subtitle'))
        <p class="gr-sub" {!! $page->attr('benefits.subtitle') !!}>{{ $page->text('benefits.subtitle') }}</p>
        @endif
        <div class="gr-cards" {!! $page->listAttr('benefits') !!}>
            @foreach($rows as $row)
                <div class="gr-card{{ $page->rowClass($row) }}">
                    {!! $page->icon("benefits.item.{$row['_n']}.icon", $row['icon'], 'gr-dot') !!}
                    @if($page->isEditable() || $row['title'] !== '')
                    <h3 {!! $page->attr("benefits.item.{$row['_n']}.title") !!}>{{ $row['title'] }}</h3>
                    @endif
                    @if($page->isEditable() || $row['text'] !== '')
                    <p {!! $page->attr("benefits.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</p>
                    @endif
                    {!! $page->rowRemove('benefits', 'item', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton('benefits') !!}
    </div>
</section>
