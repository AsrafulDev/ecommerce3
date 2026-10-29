@php $rows = $page->items('reviews'); @endphp
<section class="lp-section lp-alt" id="reviews">
    <div class="lp-wrap">
        @if($page->show('reviews.heading'))
        <h2 class="lp-h2" {!! $page->attr('reviews.heading') !!}>{{ $page->text('reviews.heading') }}</h2>
        @endif
        <div class="lp-grid" {!! $page->listAttr('reviews') !!}>
            @foreach($rows as $row)
                @php $stars = max(0, min(5, (int) $row['rating'])); @endphp
                <figure class="lp-card lp-review{{ $page->rowClass($row) }}">
                    @if($stars > 0)
                        <div class="lp-stars" aria-label="{{ $stars }} out of 5">{{ str_repeat('★', $stars) }}</div>
                    @endif
                    @if($page->isEditable() || $row['text'] !== '')
                    <blockquote {!! $page->attr("reviews.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</blockquote>
                    @endif
                    <figcaption {!! $page->attr("reviews.item.{$row['_n']}.name") !!}>— {{ $row['name'] }}</figcaption>
                    {!! $page->rowRemove('reviews', 'item', $row) !!}
                </figure>
            @endforeach
        </div>
        {!! $page->addButton('reviews') !!}
    </div>
</section>
