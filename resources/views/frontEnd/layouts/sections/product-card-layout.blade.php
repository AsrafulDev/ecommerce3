{{-- Product card responsive layout settings from the theme system. --}}
@php
    $pcLayout = $generalsetting;
    $pcHome = [
        'desktop' => (int) ($pcLayout->pc_home_desktop ?? 5),
        'laptop' => (int) ($pcLayout->pc_home_laptop ?? 4),
        'tablet' => (int) ($pcLayout->pc_home_tablet ?? 3),
        'phone' => (int) ($pcLayout->pc_home_phone ?? 2),
    ];
    $pcOther = [
        'desktop' => (int) ($pcLayout->pc_other_desktop ?? 4),
        'laptop' => (int) ($pcLayout->pc_other_laptop ?? 3),
        'tablet' => (int) ($pcLayout->pc_other_tablet ?? 3),
        'phone' => (int) ($pcLayout->pc_other_phone ?? 2),
    ];
    $pcTitleLines = (int) ($pcLayout->pc_title_lines ?? 2);
    $pcImageHeight = (int) ($pcLayout->pc_image_height ?? 200);
    $pcClamp = 'display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:' . $pcTitleLines . ';overflow:hidden;';
@endphp
<style>
    :root { --pc-title-lines: {{ $pcTitleLines }}; --pc-image-height: {{ $pcImageHeight }}px; }
    body.pc-home .main_product_inner,
    body.pc-home .product_sliders { grid-template-columns: repeat({{ $pcHome['desktop'] }}, 1fr); }
    @media (max-width: 1279px) { body.pc-home .main_product_inner, body.pc-home .product_sliders { grid-template-columns: repeat({{ $pcHome['laptop'] }}, 1fr); } }
    @media (max-width: 991px) { body.pc-home .main_product_inner, body.pc-home .product_sliders { grid-template-columns: repeat({{ $pcHome['tablet'] }}, 1fr); } }
    @media (max-width: 575px) { body.pc-home .main_product_inner, body.pc-home .product_sliders { grid-template-columns: repeat({{ $pcHome['phone'] }}, 1fr); } }
    body.pc-other .main_product_inner,
    body.pc-other .product_sliders { grid-template-columns: repeat({{ $pcOther['desktop'] }}, 1fr); }
    @media (max-width: 1279px) { body.pc-other .main_product_inner, body.pc-other .product_sliders { grid-template-columns: repeat({{ $pcOther['laptop'] }}, 1fr); } }
    @media (max-width: 991px) { body.pc-other .main_product_inner, body.pc-other .product_sliders { grid-template-columns: repeat({{ $pcOther['tablet'] }}, 1fr); } }
    @media (max-width: 575px) { body.pc-other .main_product_inner, body.pc-other .product_sliders { grid-template-columns: repeat({{ $pcOther['phone'] }}, 1fr); } }
    .pc-premium__name, .pc-overlay__name, .pc-ribbon__name, .pc-glass__name, .product_item .pro_name a {
        white-space: normal; {{ $pcClamp }}
    }
    .pc-premium .pc-premium__media, .pc-ribbon .pc-ribbon__media, .pc-glass .pc-glass__media,
    .pc-overlay .pc-overlay__media, body .product_item .pro_img { height: var(--pc-image-height); }
    .pc-premium .pc-premium__img, .pc-ribbon .pc-ribbon__img, .pc-glass .pc-glass__img { height: 100%; aspect-ratio: auto; }
    .pc-overlay .pc-overlay__media { aspect-ratio: auto; }
</style>
<script>
    window.PCPerRow = {
        home: { desktop: {{ $pcHome['desktop'] }}, laptop: {{ $pcHome['laptop'] }}, tablet: {{ $pcHome['tablet'] }}, phone: {{ $pcHome['phone'] }} },
        other: { desktop: {{ $pcOther['desktop'] }}, laptop: {{ $pcOther['laptop'] }}, tablet: {{ $pcOther['tablet'] }}, phone: {{ $pcOther['phone'] }} }
    };
</script>