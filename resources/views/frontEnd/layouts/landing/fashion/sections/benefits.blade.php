{{-- Benefits: eyebrow, two-line heading, then four cards that join into one strip. --}}
@php $rows = $page->items('benefits'); @endphp
<section class="fx-section" id="benefits">
    <div class="fx-wrap">
        @if($page->show('benefits.eyebrow'))
        <p class="fx-eyebrow" {!! $page->attr('benefits.eyebrow') !!}>{{ $page->text('benefits.eyebrow') }}</p>
        @endif
        @if($page->show('benefits.heading'))
        <h2 class="fx-h2" {!! $page->attr('benefits.heading') !!}>{{ $page->text('benefits.heading') }}</h2>
        @endif
        <div class="fx-strip" {!! $page->listAttr('benefits') !!}>
            @foreach($rows as $row)
                <div class="fx-strip-cell{{ $page->rowClass($row) }}">
                    <span class="fx-dot" aria-hidden="true"></span>
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
