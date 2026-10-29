{{-- Photo mosaic: one tall photo, a wide photo, and three more in a row. Empty slots show a placeholder. --}}
@php $rows = $page->items('gallery'); @endphp
<section class="fx-section" id="gallery">
    <div class="fx-wrap">
        @if($page->show('gallery.eyebrow'))
        <p class="fx-eyebrow" {!! $page->attr('gallery.eyebrow') !!}>{{ $page->text('gallery.eyebrow') }}</p>
        @endif
        @if($page->show('gallery.heading'))
        <h2 class="fx-h2" {!! $page->attr('gallery.heading') !!}>{{ $page->text('gallery.heading') }}</h2>
        @endif
        <div class="fx-mosaic" {!! $page->listAttr('gallery') !!}>
            @foreach($rows as $row)
                {{-- On the live page only photos that have an image are shown. --}}
                @if($page->isEditable() || $row['image'] !== '')
                <figure class="fx-tile fx-tile-{{ $row['_n'] }}{{ $page->rowClass($row) }}">
                    <div class="fx-tile-img" {!! $page->attr("gallery.item.{$row['_n']}.image", 'image') !!}>
                        @if($row['image'] !== '')
                            <img src="{{ asset($row['image']) }}" alt="{{ $row['title'] }}">
                        @endif
                        @if($page->isEditable())
                            <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                        @endif
                    </div>
                    {!! $page->rowRemove('gallery', 'item', $row) !!}
                </figure>
                @endif
            @endforeach
        </div>
        {!! $page->addButton('gallery') !!}
    </div>
</section>
