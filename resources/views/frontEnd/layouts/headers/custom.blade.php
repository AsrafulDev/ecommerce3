@php
    use App\Support\HeaderFooterComponents;

    $hRows = HeaderFooterComponents::rows($generalsetting->header_components ?? null, 'header');
@endphp
<header id="navbar_top" class="header-custom" style="{{ ($generalsetting->header_sticky ?? 1) ? 'position:sticky;top:0;z-index:1020;' : '' }}">
    @include('frontEnd.layouts.partials.hf-builder', ['type' => 'header', 'rows' => $hRows])
</header>
