{{-- Photo mosaic: a tall photo on the left and up to five more in a grid. Empty slots show only in the editor. --}}
@php $rows = $page->items('gallery'); @endphp
<section class="gr-section gr-tint" id="gallery">
    <div class="gr-wrap">
        <div class="gr-center">
            @if($page->show('gallery.eyebrow'))
            <p class="gr-eyebrow" {!! $page->attr('gallery.eyebrow') !!}>{{ $page->text('gallery.eyebrow') }}</p>
            @endif
            @if($page->show('gallery.heading'))
            <h2 class="gr-h2" {!! $page->attr('gallery.heading') !!}>{{ $page->text('gallery.heading') }}</h2>
            @endif
        </div>
        <div class="gr-mosaic" {!! $page->listAttr('gallery') !!}>
            @foreach($rows as $row)
                @if($page->isEditable() || $row['image'] !== '')
                <figure class="gr-tile gr-tile-{{ $row['_n'] }}{{ $page->rowClass($row) }}">
                    <div class="gr-tile-img" {!! $page->attr("gallery.item.{$row['_n']}.image", 'image') !!}>
                        @if($row['image'] !== '')
                            <img src="{{ asset($row['image']) }}" alt="{{ $row['title'] }}">
                        @else
                            <span class="gr-empty">{{ __('Add photo') }}</span>
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
