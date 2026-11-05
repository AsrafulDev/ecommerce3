{{-- Gallery: one large photo with its caption, and the other photos as thumbnails beside it (right on wide
     screens, underneath on phones). In the editor every photo slot is shown so it can be filled. --}}
@php
    $rows = $page->items('gallery');
    $photos = array_values(array_filter($rows, fn ($row) => $row['image'] !== ''));
@endphp
<section class="lp-section lp-alt" id="gallery">
    <div class="lp-wrap">
        @if($page->show('gallery.heading'))
        <h2 class="lp-h2" {!! $page->attr('gallery.heading') !!}>{{ $page->text('gallery.heading') }}</h2>
        @endif

        @if($page->isEditable())
        <div class="lp-grid" {!! $page->listAttr('gallery') !!}>
            @foreach($rows as $row)
                <figure class="lp-card{{ $page->rowClass($row) }}" style="padding:12px">
                    <div class="lp-img" {!! $page->attr("gallery.item.{$row['_n']}.image", 'image') !!}>
                        @if($row['image'] !== '')
                            <img class="lp-card-img" src="{{ asset($row['image']) }}" alt="{{ $row['title'] }}">
                        @else
                            <div class="lp-img-empty">{{ __('Add image') }}</div>
                        @endif
                        <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                    </div>
                    <figcaption {!! $page->attr("gallery.item.{$row['_n']}.title") !!} style="margin-top:8px;color:var(--accent);font-weight:600">{{ $row['title'] }}</figcaption>
                    <p {!! $page->attr("gallery.item.{$row['_n']}.text") !!}>{{ $row['text'] }}</p>
                    {!! $page->rowRemove('gallery', 'item', $row) !!}
                </figure>
            @endforeach
        </div>
        {!! $page->addButton('gallery') !!}
        @elseif($photos !== [])
        <div class="lp-gallery" data-gallery>
            <div class="lp-gallery-main">
                @foreach($photos as $index => $row)
                    <figure class="lp-gallery-slide{{ $index === 0 ? ' is-active' : '' }}">
                        <img src="{{ asset($row['image']) }}" alt="{{ $row['title'] }}">
                        <figcaption>
                            <strong>{{ $row['title'] }}</strong>
                            @if($row['text'] !== '')<span>{{ $row['text'] }}</span>@endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
            <div class="lp-gallery-thumbs">
                @foreach($photos as $index => $row)
                    <button type="button" class="lp-gallery-thumb{{ $index === 0 ? ' is-active' : '' }}" data-go="{{ $index }}"
                        aria-label="{{ $row['title'] }}">
                        <img src="{{ asset($row['image']) }}" alt="">
                    </button>
                @endforeach
            </div>
        </div>
        <script>
            (function () {
                document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
                    var slides = gallery.querySelectorAll('.lp-gallery-slide');
                    var thumbs = gallery.querySelectorAll('.lp-gallery-thumb');
                    thumbs.forEach(function (thumb) {
                        thumb.addEventListener('click', function () {
                            var n = Number(thumb.dataset.go);
                            slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === n); });
                            thumbs.forEach(function (other, i) { other.classList.toggle('is-active', i === n); });
                        });
                    });
                });
            })();
        </script>
        @endif
    </div>
</section>
