@php $rows = $page->items('problem'); @endphp
<section class="lp-section" id="problem">
    <div class="lp-wrap">
        @if($page->show('problem.heading'))
        <h2 class="lp-h2" {!! $page->attr('problem.heading') !!}>{{ $page->text('problem.heading') }}</h2>
        @endif
        <div class="lp-grid" {!! $page->listAttr('problem') !!}>
            @foreach($rows as $row)
                <div class="lp-card{{ $page->rowClass($row) }}">
                    @if($page->isEditable() || $row['title'] !== '')
                    <h3 {!! $page->attr("problem.item.{$row['_n']}.title") !!}>{{ $row['title'] }}</h3>
                    @endif
                    @if($page->isEditable() || $row['text'] !== '')
                    <p {!! $page->attr("problem.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</p>
                    @endif
                    {!! $page->rowRemove('problem', 'item', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton('problem') !!}
    </div>
</section>
