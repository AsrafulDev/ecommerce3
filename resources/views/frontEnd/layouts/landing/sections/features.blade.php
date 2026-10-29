@php $rows = $page->items('features'); @endphp
<section class="lp-section" id="features">
    <div class="lp-wrap">
        <h2 class="lp-h2" {!! $page->attr('features.heading') !!}>{{ $page->text('features.heading') }}</h2>
        <div class="lp-grid" {!! $page->listAttr('features') !!}>
            @foreach($rows as $row)
                <div class="lp-card{{ $page->rowClass($row) }}">
                    @if($row['image'] !== '')
                        <img class="lp-card-img" src="{{ asset($row['image']) }}" alt="{{ $row['title'] }}">
                    @endif
                    <h3 {!! $page->attr("features.item.{$row['_n']}.title") !!}>{{ $row['title'] }}</h3>
                    <p {!! $page->attr("features.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</p>
                    {!! $page->rowRemove('features', 'item', $row) !!}
                </div>
            @endforeach
        </div>
        {!! $page->addButton('features') !!}
    </div>
</section>
