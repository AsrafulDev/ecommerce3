{{-- Body of a landing page. $sections maps each section to the partial that draws it, in page order.
     The checkout always follows the sections and cannot be switched off. --}}
@foreach($sections as $section => $entry)
    @php
        // An entry is a partial name, or [partial, extra data for that partial].
        [$partial, $extra] = is_array($entry) ? [$entry[0], $entry[1]] : [$entry, []];
    @endphp
    @if(($page->enabled($section) || $page->isEditable()) && $page->sectionHasContent($section))
        <div class="{{ $page->enabled($section) ? '' : 'lp-off' }}"{!! $page->sectionAttr($section) !!}>
            @include($partial, array_merge(['page' => $page, 'product' => $product, 'open' => $open, 'section' => $section], $extra))
        </div>
    @endif
@endforeach

@include('frontEnd.layouts.partials.landing_checkout', [
    'page' => $page,
    'product' => $product,
    'variants' => $variants,
    'areas' => $areas,
    'open' => $open,
])

<footer class="lp-foot">{{ $generalsetting?->copyright_text ?? '' }}</footer>
@if($page->isEditable())
    @include('frontEnd.layouts.landing._editor')
@endif
</body>
</html>
