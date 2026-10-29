@php $rows = $page->items('problem'); @endphp
<section class="lp-section" id="problem">
    <div class="lp-wrap">
        <h2 class="lp-h2" {!! $page->attr('problem.heading') !!}>{{ $page->text('problem.heading') }}</h2>
        <div class="lp-grid" {!! $page->listAttr('problem') !!}>
            @foreach($rows as $row)
                <div class="lp-card{{ $page->rowClass($row) }}">
                    <h3 {!! $page->attr("problem.item.{$row['_n']}.title") !!}>{{ $row['title'] }}</h3>
                    <p {!! $page->attr("problem.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</p>
                    {!! $page->rowRemove('problem', 'item', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton('problem') !!}
    </div>
</section>
