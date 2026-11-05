{{-- Reviews: text reviews with a star rating, and photo reviews shown as photos only. On a desktop four photos show
     at once; more than four slide on by themselves. The editor shows every photo slot, so each can be changed or removed. --}}
@php
    $texts = $page->items('reviews');
    $photos = $page->items('reviews', 'photo');
    $livePhotos = array_values(array_filter($photos, fn ($row) => $row['image'] !== ''));
@endphp
<section class="fx-section fx-alt" id="reviews">
    <div class="fx-wrap">
        <p class="fx-eyebrow" {!! $page->attr('reviews.eyebrow') !!}>{{ $page->text('reviews.eyebrow') }}</p>
        <h2 class="fx-h2" {!! $page->attr('reviews.heading') !!}>{{ $page->text('reviews.heading') }}</h2>
        @if($texts === [] && $livePhotos === [] && ! $page->isEditable())
            <div class="fx-empty-card">
                <span class="fx-quote" aria-hidden="true">“</span>
                <div>
                    <h3 {!! $page->attr('reviews.empty_title') !!}>{{ $page->text('reviews.empty_title') }}</h3>
                    <p {!! $page->attr('reviews.empty_text') !!}>{{ $page->text('reviews.empty_text') }}</p>
                </div>
            </div>
        @else
            @if($texts !== [] || $page->isEditable())
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
            @endif

            @if($livePhotos !== [] || $page->isEditable())
            <h3 class="fx-h4" style="margin-top:32px">{{ __('Photo reviews') }}</h3>
            @if($page->isEditable())
            <div class="fx-photo-reviews" {!! $page->listAttr('reviews', 'photo') !!}>
                @foreach($photos as $row)
                    <figure class="fx-photo-review{{ $page->rowClass($row) }}">
                        <div class="fx-tile-img" {!! $page->attr("reviews.photo.{$row['_n']}.image", 'image') !!}>
                            @if($row['image'] !== '')
                                <img src="{{ asset($row['image']) }}" alt="">
                            @else
                                <span class="fx-empty">{{ __('Add photo') }}</span>
                            @endif
                            <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                        </div>
                        {!! $page->rowRemove('reviews', 'photo', $row) !!}
                    </figure>
                @endforeach
            </div>
            {!! $page->addButton('reviews', 'photo') !!}
            @else
            <div class="fx-photo-slider" data-photo-slider data-count="{{ count($livePhotos) }}">
                <div class="fx-photo-track">
                    @foreach($livePhotos as $row)
                        <figure class="fx-photo-slide"><img src="{{ asset($row['image']) }}" alt=""></figure>
                    @endforeach
                </div>
            </div>
            @endif
            @endif
        @endif
    </div>
</section>
@if(! $page->isEditable() && $livePhotos !== [])
<script>
    (function () {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('[data-photo-slider]').forEach(function (slider) {
            var track = slider.querySelector('.fx-photo-track');
            var slides = track.children;
            var index = 0;
            var timer = null;

            // Four a row on a desktop, two on a tablet, one on a phone. Matches the breakpoints in the stylesheet.
            function perView() { return window.innerWidth > 900 ? 4 : (window.innerWidth > 560 ? 2 : 1); }
            function step() { return slides.length ? slides[0].getBoundingClientRect().width + 18 : 0; }
            function layout() {
                var per = perView();
                var last = Math.max(0, slides.length - per);
                index = Math.min(index, last);
                slider.classList.toggle('is-fixed', slides.length <= per);
                track.style.transform = 'translateX(' + (-index * step()) + 'px)';
                return last;
            }
            function next() {
                var last = layout();
                if (last === 0) return;
                index = index >= last ? 0 : index + 1;
                layout();
            }
            function stop() { if (timer) { clearInterval(timer); timer = null; } }
            function start() {
                stop();
                if (reduce || layout() === 0) return;
                timer = setInterval(next, 4000);
            }

            slider.addEventListener('mouseenter', stop);
            slider.addEventListener('mouseleave', start);
            window.addEventListener('resize', start);
            start();
        });
    })();
</script>
@endif
