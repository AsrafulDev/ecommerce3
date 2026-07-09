{{-- Footer Style: Custom (Dynamic — built via Admin drag-drop builder) --}}
@php
    $fComps = $generalsetting->footer_components ?? ['about','links','support','newsletter','social','copyright'];
@endphp
<footer style="background: var(--footer-bg) !important;">
    <div class="footer-custom-wrapper" style="background: var(--footer-bg) !important; color: var(--footer-text);">
        @foreach($fComps as $comp)
            @includeIf('frontEnd.layouts.footers.parts.' . $comp)
        @endforeach
    </div>
</footer>
