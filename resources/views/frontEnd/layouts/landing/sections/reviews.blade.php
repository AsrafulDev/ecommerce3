@php
    $rows = $page->items('reviews');
    $photos = $page->items('reviews', 'photo');
    $livePhotos = array_values(array_filter($photos, fn ($row) => $row['image'] !== ''));
@endphp
<section class="lp-section lp-alt" id="reviews">
    <div class="lp-wrap">
        @if($page->show('reviews.heading'))
        <h2 class="lp-h2" {!! $page->attr('reviews.heading') !!}>{{ $page->text('reviews.heading') }}</h2>
        @endif
        <div class="lp-grid" {!! $page->listAttr('reviews') !!}>
            @foreach($rows as $row)
                <figure class="lp-card lp-review{{ $page->rowClass($row) }}">
                    {!! $page->stars("reviews.item.{$row['_n']}.rating", 'lp-stars') !!}
                    @if($page->isEditable() || $row['text'] !== '')
                    <blockquote {!! $page->attr("reviews.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</blockquote>
                    @endif
                    @if($page->isEditable() || $row['name'] !== '')
                    <figcaption>— <span {!! $page->attr("reviews.item.{$row['_n']}.name") !!}>{{ $row['name'] }}</span></figcaption>
                    @endif
                    {!! $page->rowRemove('reviews', 'item', $row) !!}
                </figure>
            @endforeach
        </div>
        {!! $page->addButton('reviews') !!}

        {{-- Photo reviews: a slider. The live page shows only the photos that are set; the editor shows every slot. --}}
        @if($page->isEditable() || $livePhotos !== [])
        <div class="lp-slider" data-slider {!! $page->listAttr('reviews', 'photo') !!}>
            <div class="lp-slider-track">
                @foreach($photos as $row)
                    @if($page->isEditable() || $row['image'] !== '')
                    <figure class="lp-slide{{ $page->rowClass($row) }}">
                        <div class="lp-slide-img" {!! $page->attr("reviews.photo.{$row['_n']}.image", 'image') !!}>
                            @if($row['image'] !== '')
                                <img src="{{ asset($row['image']) }}" alt="">
                            @endif
                            @if($page->isEditable())
                                <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                            @endif
                        </div>
                        {!! $page->rowRemove('reviews', 'photo', $row) !!}
                    </figure>
                    @endif
                @endforeach
            </div>
            @if(! $page->isEditable() && count($livePhotos) > 1)
            <div class="lp-slider-nav">
                <button type="button" class="lp-slider-btn" data-dir="-1" aria-label="{{ __('Previous') }}">‹</button>
                <button type="button" class="lp-slider-btn" data-dir="1" aria-label="{{ __('Next') }}">›</button>
            </div>
            @endif
        </div>
        {!! $page->addButton('reviews', 'photo') !!}
        @endif
    </div>
</section>
@if(! $page->isEditable() && $livePhotos !== [])
<script>
    (function () {
        document.querySelectorAll('[data-slider]').forEach(function (slider) {
            var track = slider.querySelector('.lp-slider-track');
            slider.querySelectorAll('.lp-slider-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    var step = track.firstElementChild ? track.firstElementChild.offsetWidth + 18 : track.clientWidth;
                    track.scrollBy({left: Number(button.dataset.dir) * step, behavior: 'smooth'});
                });
            });
        });
    })();
</script>
@endif
