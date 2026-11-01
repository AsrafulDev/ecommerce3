{{-- Product details: the ingredient and spec table on the left; care tips and label facts stacked on the right. --}}
@php
    $rows = $page->items('details', 'row');
    $tips = $page->items('details', 'care');
    $labels = $page->items('details', 'label');
@endphp
<section class="gr-section gr-tint" id="details">
    <div class="gr-wrap">
        @if($page->show('details.eyebrow'))
        <p class="gr-eyebrow" {!! $page->attr('details.eyebrow') !!}>{{ $page->text('details.eyebrow') }}</p>
        @endif
        @if($page->show('details.heading'))
        <h2 class="gr-h2" {!! $page->attr('details.heading') !!}>{{ $page->text('details.heading') }}</h2>
        @endif
        @if($page->show('details.body'))
        <div class="gr-sub" {!! $page->attr('details.body', 'rich') !!}>{!! $page->rich('details.body') !!}</div>
        @endif

        <div class="gr-details-grid">
            <div class="gr-panel">
                @if($page->show('details.ingredient_title'))
                <h3 {!! $page->attr('details.ingredient_title') !!}>{{ $page->text('details.ingredient_title') }}</h3>
                @endif
                @if($page->show('details.ingredient'))
                <p class="gr-ingredient" {!! $page->attr('details.ingredient') !!}>{{ $page->text('details.ingredient') }}</p>
                @endif
                <div {!! $page->listAttr('details', 'row') !!}>
                    @foreach($rows as $row)
                        <div class="gr-row{{ $page->rowClass($row) }}">
                            @if($page->isEditable() || $row['label'] !== '')
                            <span {!! $page->attr("details.row.{$row['_n']}.label") !!}>{{ $row['label'] }}</span>
                            @endif
                            @if($page->isEditable() || $row['value'] !== '')
                            <span {!! $page->attr("details.row.{$row['_n']}.value") !!}>{{ $row['value'] }}</span>
                            @endif
                            {!! $page->rowRemove('details', 'row', $row) !!}
                        </div>
                    @endforeach
                </div>
                {!! $page->addButton('details', 'row') !!}
            </div>

            <div class="gr-stack">
                <div class="gr-panel">
                    @if($page->show('details.care_title'))
                    <h3 {!! $page->attr('details.care_title') !!}>{{ $page->text('details.care_title') }}</h3>
                    @endif
                    <ul class="gr-tips" {!! $page->listAttr('details', 'care') !!}>
                        @foreach($tips as $row)
                            <li class="{{ trim($page->rowClass($row)) }}">
                                <span {!! $page->attr("details.care.{$row['_n']}.text") !!}>{{ $row['text'] }}</span>
                                {!! $page->rowRemove('details', 'care', $row) !!}
                            </li>
                        @endforeach
                    </ul>
                    {!! $page->addButton('details', 'care') !!}
                </div>

                <div class="gr-panel gr-label-rows">
                    @if($page->show('details.label_title'))
                    <h3 {!! $page->attr('details.label_title') !!}>{{ $page->text('details.label_title') }}</h3>
                    @endif
                    <div {!! $page->listAttr('details', 'label') !!}>
                        @foreach($labels as $row)
                            <div class="gr-row{{ $page->rowClass($row) }}">
                                @if($page->isEditable() || $row['label'] !== '')
                                <span {!! $page->attr("details.label.{$row['_n']}.label") !!}>{{ $row['label'] }}</span>
                                @endif
                                @if($page->isEditable() || $row['value'] !== '')
                                <span {!! $page->attr("details.label.{$row['_n']}.value") !!}>{{ $row['value'] }}</span>
                                @endif
                                {!! $page->rowRemove('details', 'label', $row) !!}
                            </div>
                        @endforeach
                    </div>
                    {!! $page->addButton('details', 'label') !!}
                </div>
            </div>
        </div>
    </div>
</section>
