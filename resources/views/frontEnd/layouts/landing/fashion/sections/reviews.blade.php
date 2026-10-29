{{-- Reviews: text reviews and photo reviews, each with a star rating. Empty until verified reviews exist. --}}
@php
    $texts = $page->items('reviews');
    $photos = $page->items('reviews', 'photo');
@endphp
<section class="fx-section fx-alt" id="reviews">
    <div class="fx-wrap">
        <p class="fx-eyebrow" {!! $page->attr('reviews.eyebrow') !!}>{{ $page->text('reviews.eyebrow') }}</p>
        <h2 class="fx-h2" {!! $page->attr('reviews.heading') !!}>{{ $page->text('reviews.heading') }}</h2>
        @if($texts === [] && $photos === [] && ! $page->isEditable())
            <div class="fx-empty-card">
                <span class="fx-quote" aria-hidden="true">“</span>
                <div>
                    <h3 {!! $page->attr('reviews.empty_title') !!}>{{ $page->text('reviews.empty_title') }}</h3>
                    <p {!! $page->attr('reviews.empty_text') !!}>{{ $page->text('reviews.empty_text') }}</p>
                </div>
            </div>
        @else
            <h3 class="fx-h4" style="margin-top:8px">{{ __('Written reviews') }}</h3>
            <div class="fx-strip" {!! $page->listAttr('reviews') !!}>
                @foreach($texts as $row)
                    <figure class="fx-strip-cell{{ $page->rowClass($row) }}">
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

            <h3 class="fx-h4" style="margin-top:32px">{{ __('Photo reviews') }}</h3>
            <div class="fx-photo-reviews" {!! $page->listAttr('reviews', 'photo') !!}>
                @foreach($photos as $row)
                    <figure class="fx-photo-review{{ $page->rowClass($row) }}">
                        <div class="fx-tile-img" {!! $page->attr("reviews.photo.{$row['_n']}.image", 'image') !!}>
                            @if($row['image'] !== '')
                                <img src="{{ asset($row['image']) }}" alt="{{ $row['name'] }}">
                            @else
                                <span class="fx-empty">{{ __('Add photo') }}</span>
                            @endif
                            @if($page->isEditable())
                                <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                            @endif
                        </div>
                        {!! $page->stars("reviews.photo.{$row['_n']}.rating") !!}
                        <figcaption {!! $page->attr("reviews.photo.{$row['_n']}.name") !!}>— {{ $row['name'] }}</figcaption>
                        {!! $page->rowRemove('reviews', 'photo', $row) !!}
                    </figure>
                @endforeach
            </div>
            {!! $page->addButton('reviews', 'photo') !!}
        @endif
    </div>
</section>
