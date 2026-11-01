{{-- Reviews: only verified customer reviews are shown. Until there are some, an empty-state card says so. --}}
@php $rows = $page->items('reviews'); @endphp
<section class="gr-section gr-beige" id="reviews">
    <div class="gr-wrap">
        @if($page->show('reviews.eyebrow'))
        <p class="gr-eyebrow" {!! $page->attr('reviews.eyebrow') !!}>{{ $page->text('reviews.eyebrow') }}</p>
        @endif
        @if($page->show('reviews.heading'))
        <h2 class="gr-h2" {!! $page->attr('reviews.heading') !!}>{{ $page->text('reviews.heading') }}</h2>
        @endif
        @if($page->show('reviews.subtitle'))
        <p class="gr-sub" {!! $page->attr('reviews.subtitle') !!}>{{ $page->text('reviews.subtitle') }}</p>
        @endif

        @if($rows === [] && ! $page->isEditable())
            <div class="gr-empty-card">
                <span class="gr-empty-icon" aria-hidden="true"><i class="fa-solid fa-comment-dots"></i></span>
                <h3 {!! $page->attr('reviews.empty_title') !!}>{{ $page->text('reviews.empty_title') }}</h3>
                <p {!! $page->attr('reviews.empty_text') !!}>{{ $page->text('reviews.empty_text') }}</p>
            </div>
        @else
            <div class="gr-reviews" {!! $page->listAttr('reviews') !!}>
                @foreach($rows as $row)
                    <figure class="gr-review{{ $page->rowClass($row) }}">
                        {!! $page->stars("reviews.item.{$row['_n']}.rating") !!}
                        @if($page->show("reviews.item.{$row['_n']}.text"))
                            <blockquote {!! $page->attr("reviews.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</blockquote>
                        @endif
                        <figcaption {!! $page->attr("reviews.item.{$row['_n']}.name") !!}>— {{ $row['name'] }}</figcaption>
                        {!! $page->rowRemove('reviews', 'item', $row) !!}
                    </figure>
                @endforeach
            </div>
            {!! $page->addButton('reviews') !!}
        @endif
    </div>
</section>
