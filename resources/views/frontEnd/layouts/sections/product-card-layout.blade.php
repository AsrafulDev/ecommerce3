{{--
  Product Card Responsive Layout (row limits + title clamp)
  ==========================================================
  Controlled from Admin → Theme System → Product Design.
  Injects CSS that sets the number of product cards per row on each device,
  separately for the FRONT page (body.pc-home) and OTHER pages (body.pc-other,
  e.g. shop/category/brand/search/related/account which have a sidebar).
  Also sets --pc-title-lines (card title line limit) and --pc-image-height
  (card image area height) used by every card layout.

  Included AFTER the base style/responsive partials so these higher-specificity
  rules (body.pc-* .main_product_inner) win over the defaults.
--}}
@php
    $pcLayout      = $generalsetting;
    $pcHome        = [
        'desktop' => (int) ($pcLayout->pc_home_desktop  ?? 5),
        'laptop'  => (int) ($pcLayout->pc_home_laptop   ?? 4),
        'tablet'  => (int) ($pcLayout->pc_home_tablet   ?? 3),
        'phone'   => (int) ($pcLayout->pc_home_phone    ?? 2),
    ];
    $pcOther       = [
        'desktop' => (int) ($pcLayout->pc_other_desktop ?? 4),
        'laptop'  => (int) ($pcLayout->pc_other_laptop  ?? 3),
        'tablet'  => (int) ($pcLayout->pc_other_tablet  ?? 3),
        'phone'   => (int) ($pcLayout->pc_other_phone   ?? 2),
    ];
    $pcTitleLines  = (int) ($pcLayout->pc_title_lines ?? 2);
    $pcImageHeight = (int) ($pcLayout->pc_image_height ?? 200);
    $pcClamp       = 'display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:' . $pcTitleLines . ';overflow:hidden;';
@endphp
<style>
    :root { --pc-title-lines: {{ $pcTitleLines }}; --pc-image-height: {{ $pcImageHeight }}px; }

    /* ---------- Front page (no sidebar) ---------- */
    body.pc-home .main_product_inner,
    body.pc-home .product_sliders { grid-template-columns: repeat({{ $pcHome['desktop'] }}, 1fr); }
    @media (max-width: 1279px) { body.pc-home .main_product_inner, body.pc-home .product_sliders { grid-template-columns: repeat({{ $pcHome['laptop'] }}, 1fr); } }
    @media (max-width: 991px)  { body.pc-home .main_product_inner, body.pc-home .product_sliders { grid-template-columns: repeat({{ $pcHome['tablet'] }}, 1fr); } }
    @media (max-width: 575px)  { body.pc-home .main_product_inner, body.pc-home .product_sliders { grid-template-columns: repeat({{ $pcHome['phone'] }}, 1fr); } }

    /* ---------- Other pages (sidebar / narrower) ---------- */
    body.pc-other .main_product_inner,
    body.pc-other .product_sliders { grid-template-columns: repeat({{ $pcOther['desktop'] }}, 1fr); }
    @media (max-width: 1279px) { body.pc-other .main_product_inner, body.pc-other .product_sliders { grid-template-columns: repeat({{ $pcOther['laptop'] }}, 1fr); } }
    @media (max-width: 991px)  { body.pc-other .main_product_inner, body.pc-other .product_sliders { grid-template-columns: repeat({{ $pcOther['tablet'] }}, 1fr); } }
    @media (max-width: 575px)  { body.pc-other .main_product_inner, body.pc-other .product_sliders { grid-template-columns: repeat({{ $pcOther['phone'] }}, 1fr); } }

    /* ---------- Card title line limit (all layouts) ---------- */
    .pc-premium__name,
    .pc-overlay__name,
    .pc-ribbon__name,
    .pc-glass__name,
    .product_item .pro_name a {
        white-space: normal; /* override single-line rules so clamping can wrap */
        {{ $pcClamp }}
    }

    /* ---------- Card image area height (all layouts) ---------- */
    /* Structural formats: fixed-height media box; the image link fills it. */
    .pc-premium .pc-premium__media,
    .pc-ribbon .pc-ribbon__media,
    .pc-glass .pc-glass__media {
        height: var(--pc-image-height);
    }
    .pc-premium .pc-premium__img,
    .pc-ribbon .pc-ribbon__img,
    .pc-glass .pc-glass__img {
        height: 100%; aspect-ratio: auto;
    }
    /* Overlay's media box IS the link: height + clear the square ratio. */
    .pc-overlay .pc-overlay__media {
        height: var(--pc-image-height); aspect-ratio: auto;
    }
    /* Classic family uses .pro_img (fixed 200px in base css). */
    body .product_item .pro_img {
        height: var(--pc-image-height);
    }
</style>

{{-- Per-row settings exposed to JS so owl-carousel product sliders
     (hot deals, flash sale, related, etc.) honour the same values. --}}
<script>
    window.PCPerRow = {
        home:  { desktop: {{ $pcHome['desktop'] }}, laptop: {{ $pcHome['laptop'] }}, tablet: {{ $pcHome['tablet'] }}, phone: {{ $pcHome['phone'] }} },
        other: { desktop: {{ $pcOther['desktop'] }}, laptop: {{ $pcOther['laptop'] }}, tablet: {{ $pcOther['tablet'] }}, phone: {{ $pcOther['phone'] }} }
    };
</script>
