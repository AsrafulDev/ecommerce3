{{-- Dark band: text on the left, four points in a two-column grid on the right. --}}
@php $rows = $page->items('craft'); @endphp
<section class="fx-dark" id="craft">
    <div class="fx-wrap fx-split">
        <div>
            @if($page->show('craft.eyebrow'))
            <p class="fx-eyebrow" {!! $page->attr('craft.eyebrow') !!}>{{ $page->text('craft.eyebrow') }}</p>
            @endif
            @if($page->show('craft.heading'))
            <h2 class="fx-h2" {!! $page->attr('craft.heading') !!}>{{ $page->text('craft.heading') }}</h2>
            @endif
            @if($page->show('craft.body'))
            <div class="fx-body" {!! $page->attr('craft.body', 'rich') !!}>{!! $page->rich('craft.body') !!}</div>
            @endif
        </div>
        <div class="fx-points" {!! $page->listAttr('craft') !!}>
            @foreach($rows as $row)
                <div class="fx-point{{ $page->rowClass($row) }}">
                    @if($page->isEditable() || $row['title'] !== '')
                    <h3 {!! $page->attr("craft.item.{$row['_n']}.title") !!}>{{ $row['title'] }}</h3>
                    @endif
                    @if($page->isEditable() || $row['text'] !== '')
                    <p {!! $page->attr("craft.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</p>
                    @endif
                    {!! $page->rowRemove('craft', 'item', $row) !!}
                </div>
            @endforeach
        </div>
    </div>
</section>
