{{-- Heading and a grid of images with captions. An empty image shows a placeholder the editor can fill. --}}
@php $rows = $page->items($section); @endphp
<section class="lp-section" id="{{ $section }}">
    <div class="lp-wrap">
        <h2 class="lp-h2" {!! $page->attr("{$section}.heading") !!}>{{ $page->text("{$section}.heading") }}</h2>
        <div class="lp-grid" {!! $page->listAttr($section) !!}>
            @foreach($rows as $row)
                <figure class="lp-card{{ $page->rowClass($row) }}" style="padding:12px">
                    <div class="lp-img" {!! $page->attr("{$section}.item.{$row['_n']}.image", 'image') !!}>
                        @if($row['image'] !== '')
                            <img class="lp-card-img" src="{{ asset($row['image']) }}" alt="{{ $row['title'] }}">
                        @else
                            <div class="lp-img-empty">{{ __('Add image') }}</div>
                        @endif
                        @if($page->isEditable())
                            <button type="button" class="lp-img-change">{{ __('Change image') }}</button>
                        @endif
                    </div>
                    @if($page->isEditable() || $row['title'] !== '')
                    <figcaption {!! $page->attr("{$section}.item.{$row['_n']}.title") !!} style="margin-top:8px;color:var(--accent);font-weight:600">{{ $row['title'] }}</figcaption>
                    @endif
                    {!! $page->rowRemove($section, 'item', $row) !!}
                </figure>
            @endforeach
        </div>
        {!! $page->addButton($section) !!}
    </div>
</section>
