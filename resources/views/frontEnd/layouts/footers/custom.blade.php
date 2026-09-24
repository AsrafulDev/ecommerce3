{{-- Footer Style: Custom (Dynamic — built via Admin row/column drag-drop builder) --}}
@php
    use App\Support\HeaderFooterComponents;

    $fRows = HeaderFooterComponents::rows($generalsetting->footer_components ?? null, 'footer');
@endphp
<footer style="background: var(--footer-bg) !important;">
    <div class="footer-custom-wrapper" style="background: var(--footer-bg) !important; color: var(--footer-text);">
        @include('frontEnd.layouts.partials.hf-builder', ['type' => 'footer', 'rows' => $fRows])
    </div>
</footer>
