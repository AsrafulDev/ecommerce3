{{-- Care rules: heading and a numbered list in two columns. --}}
@php $rows = $page->items('care'); @endphp
<section class="fx-section fx-care" id="care">
    <div class="fx-wrap">
        @if($page->show('care.heading'))
        <h3 class="fx-h3" {!! $page->attr('care.heading') !!}>{{ $page->text('care.heading') }}</h3>
        @endif
        <ol class="fx-steps" {!! $page->listAttr('care') !!}>
            @foreach($rows as $row)
                <li class="{{ trim($page->rowClass($row)) }}">
                    <span class="fx-num">{{ str_pad((string) $row['_n'], 2, '0', STR_PAD_LEFT) }}</span>
                    @if($page->isEditable() || $row['text'] !== '')
                    <span {!! $page->attr("care.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</span>
                    @endif
                    {!! $page->rowRemove('care', 'item', $row) !!}
                </li>
            @endforeach
        </ol>
        {!! $page->addButton('care') !!}
    </div>
</section>
