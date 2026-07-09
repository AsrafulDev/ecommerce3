{{-- Header Style: Custom (Dynamic — built via Admin drag-drop builder) --}}
@php
    $hComps = $generalsetting->header_components ?? ['topbar','logo','search','nav','cart'];
@endphp
<header id="navbar_top" class="header-custom" style="{{ ($generalsetting->header_sticky ?? 1) ? 'position:sticky;top:0;z-index:1020;' : '' }}">
    @foreach($hComps as $comp)
        @includeIf('frontEnd.layouts.headers.parts.' . $comp)
    @endforeach
</header>
