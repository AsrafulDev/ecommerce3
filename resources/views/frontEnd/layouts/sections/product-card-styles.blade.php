{{--
  Product Card Design Styles
  ==========================
  Controlled from Admin → Theme System → Product Design (general_settings.product_card_style).
  The active design is injected as a body class: body.product-card-<style>.
  There are two families:

  A. MARKUP LAYOUTS  (real structural formats, unique modern layouts):
       default  → Premium   (new default: layered card, hover quick-actions, gradient price)
       overlay  → Overlay   (full-bleed image + info panel)
       ribbon   → Ribbon    (pennant ribbon badge + centered body)
       glass    → Glassmorphism (frosted info bar over image + floating FAB)

  B. THEMED CLASSICS  (shared .product_item structure, restyled via body class):
       legacy   → Legacy (the ORIGINAL card, now kept under this name)
       minimal / classic / dark / rounded / gradient → CSS-only restyles of the classic structure

  @include('frontEnd.layouts.sections.product-card-styles')
--}}
<style>
/* ============================================================
   A. MARKUP LAYOUTS — real structural formats
   ============================================================ */

/* ---------- PREMIUM (default) — layered card with hover quick-actions ---------- */
body.product-card-default .pc-premium {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--border-color, #eee);
    border-radius: var(--border-radius, 12px);
    background: #fff;
    box-shadow: var(--card-shadow, 0 6px 24px rgba(0,0,0,.08));
    transition: transform .3s ease, box-shadow .3s ease;
}
body.product-card-default .pc-premium:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 40px rgba(0,0,0,.16);
}
.pc-premium__media {
    position: relative;
    overflow: hidden;
    background: #f6f6f8;
}
.pc-premium__img { display: block; aspect-ratio: 1 / 1; }
.pc-premium__img img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s ease; }
.pc-premium:hover .pc-premium__img img { transform: scale(1.08); }
.pc-premium__badge {
    position: absolute; top: 12px; left: 12px; z-index: 3;
    background: var(--sale-badge-bg, #e53935); color: var(--sale-badge-text, #fff);
    font-size: 12px; font-weight: 700; letter-spacing: .5px;
    padding: 4px 10px; border-radius: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,.18);
}
.pc-premium__actions {
    position: absolute; right: 12px; top: 12px; z-index: 3;
    display: flex; flex-direction: column; gap: 8px;
    opacity: 0; transform: translateX(14px);
    transition: opacity .3s ease, transform .3s ease;
}
.pc-premium:hover .pc-premium__actions { opacity: 1; transform: translateX(0); }
.pc-premium__act,
.pc-premium__act-form .pc-premium__act {
    width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
    border: none; border-radius: 50%; background: #fff; color: var(--text-color, #333);
    font-size: 15px; cursor: pointer; text-decoration: none;
    box-shadow: 0 4px 14px rgba(0,0,0,.18); transition: background .25s, color .25s, transform .25s;
}
.pc-premium__act:hover {
    background: var(--primary-color, #f04e23); color: #fff; transform: scale(1.08);
}
.pc-premium__body { padding: 14px 14px 4px; }
.pc-premium__stars { color: #f5b301; font-size: 11px; margin-bottom: 4px; letter-spacing: 1px; }
.pc-premium__name {
    display: block; color: var(--heading-color, #222); font-size: 14px; font-weight: 600;
    line-height: 1.4; min-height: 40px; text-decoration: none;
    transition: color .2s;
}
.pc-premium__name:hover { color: var(--primary-color, #f04e23); }
.pc-premium__price {
    display: flex; align-items: center; gap: 8px; margin: 8px 0 2px;
}
.pc-premium__price del { color: #aaa; font-size: 13px; }
.pc-premium__now {
    font-size: 17px; font-weight: 800; color: var(--primary-color, #f04e23);
}
.pc-premium__warranty { display: block; font-size: 11px; color: #2e7d32; margin: 6px 0 4px; }
.pc-premium__btn {
    display: flex; align-items: stretch; border-top: 1px solid var(--border-color, #f0f0f0);
    margin-top: 10px; padding: 10px 14px 14px; gap: 8px;
    display:none; /* hide by default, on hover show icon button */
}
.pc-premium__btn .order-btn,
.pc-premium__btn .order-btn-link { flex: 1 1 auto; }
.pc-premium__btn .order-btn,
.pc-premium__btn .order-btn-link,
.pc-premium__btn .cart-icon-btn,
.pc-premium__btn .cart-icon-link {
    border-radius: var(--border-radius, 10px);
}
.pc-premium__btn .cart-icon-btn,
.pc-premium__btn .cart-icon-link { flex: 0 0 48px; }

/* ---------- OVERLAY — full-bleed image with info panel ---------- */
body.product-card-overlay .pc-overlay {
    position: relative; overflow: hidden; border-radius: var(--border-radius, 12px);
    background: #fff; box-shadow: var(--card-shadow, 0 6px 24px rgba(0,0,0,.08));
    border: 1px solid var(--border-color, #eee);
    transition: transform .3s ease, box-shadow .3s ease;
}
body.product-card-overlay .pc-overlay:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(0,0,0,.18); }
.pc-overlay__media { display: block; aspect-ratio: 1 / 1; overflow: hidden; }
.pc-overlay__media img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
.pc-overlay:hover .pc-overlay__media img { transform: scale(1.1); }
.pc-overlay__actions {
    position: absolute; left: 50%; top: 18%; transform: translate(-50%, -50%);
    display: flex; gap: 10px; z-index: 3; white-space: nowrap;
    opacity: 0; transition: opacity .3s ease, transform .3s ease;
}
.pc-overlay:hover .pc-overlay__actions { opacity: 1; transform: translate(-50%, -50%) translateY(0); }
.pc-overlay__act {
    width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center;
    border: none; border-radius: 50%; background: #fff; color: var(--text-color, #333);
    font-size: 16px; cursor: pointer; text-decoration: none;
    box-shadow: 0 6px 20px rgba(0,0,0,.22); transition: background .25s, color .25s, transform .25s;
}
.pc-overlay__act:hover { background: var(--primary-color, #f04e23); color: #fff; transform: translateY(-3px); }
.pc-overlay__panel {
    position: absolute; left: 0; right: 0; bottom: 0; z-index: 2;
    background: #fff; padding: 12px 14px 14px;
    transform: translateY(100%);
    transition: transform .35s ease;
}
.pc-overlay:hover .pc-overlay__panel { transform: translateY(0); }
.pc-overlay__meta { text-align: center; }
.pc-overlay__badge {
    position: absolute; top: -30px; left: 50%; transform: translateX(-50%);
    background: var(--sale-badge-bg, #e53935); color: var(--sale-badge-text, #fff);
    font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,.2);
}
.pc-overlay__stars { color: #f5b301; font-size: 11px; margin: 6px 0 2px; letter-spacing: 1px; }
.pc-overlay__name {
    display: block; color: var(--heading-color, #222); font-size: 14px; font-weight: 600;
    line-height: 1.4; text-decoration: none;
}
.pc-overlay__name:hover { color: var(--primary-color, #f04e23); }
.pc-overlay__price { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 6px 0 2px; }
.pc-overlay__price del { color: #aaa; font-size: 12px; }
.pc-overlay__price span { font-size: 16px; font-weight: 800; color: var(--primary-color, #f04e23); }
.pc-overlay__btn { display: flex; gap: 6px; margin-top: 6px; }
.pc-overlay:hover .pc-overlay__btn { display: flex; }
.pc-overlay__btn .order-btn,
.pc-overlay__btn .cart-icon-btn,
.pc-overlay__btn .order-btn-link { flex: 1 1 auto; }
/* .pc-overlay__btn .cart-icon-btn,
.pc-overlay__btn .cart-icon-link { flex: 0 0 44px; } */
.pc-overlay__btn { display: none !important; /* hide by default, on hover show buttons */ }

/* ---------- RIBBON — pennant badge + centered body ---------- */
body.product-card-ribbon .pc-ribbon {
    position: relative; overflow: hidden; text-align: center;
    background: #fff; border: 1px solid var(--border-color, #eee);
    border-radius: var(--border-radius, 12px);
    box-shadow: var(--card-shadow, 0 6px 24px rgba(0,0,0,.08));
    transition: transform .3s ease, box-shadow .3s ease;
}
body.product-card-ribbon .pc-ribbon:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(0,0,0,.18); }
.pc-ribbon__badge {
    position: absolute; top: 0; right: 18px; z-index: 4;
    width: 0; height: 0; border-left: 34px solid transparent; border-right: 34px solid transparent;
    border-top: 52px solid var(--sale-badge-bg, #e53935);
}
.pc-ribbon__badge span {
    position: absolute; top: -46px; left: -18px; width: 36px; text-align: center;
    color: var(--sale-badge-text, #fff); font-size: 12px; font-weight: 800;
    transform: rotate(0deg);
}
.pc-ribbon__media { overflow: hidden; }
.pc-ribbon__img { display: block; aspect-ratio: 1 / 1; }
.pc-ribbon__img img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s ease; }
.pc-ribbon:hover .pc-ribbon__img img { transform: scale(1.08); }
.pc-ribbon__body { padding: 14px 14px 4px; }
.pc-ribbon__stars { color: #f5b301; font-size: 11px; margin-bottom: 4px; letter-spacing: 1px; }
.pc-ribbon__name {
    display: block; color: var(--heading-color, #222); font-size: 14px; font-weight: 600;
    line-height: 1.4; min-height: 40px; text-decoration: none;
}
.pc-ribbon__name:hover { color: var(--primary-color, #f04e23); }
.pc-ribbon__price { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 8px 0 2px; }
.pc-ribbon__price del { color: #aaa; font-size: 13px; }
.pc-ribbon__price span { font-size: 16px; font-weight: 800; color: var(--primary-color, #f04e23); }
.pc-ribbon__btn { display: flex; gap: 6px; padding: 10px 14px 14px; }
.pc-ribbon__btn .order-btn,
.pc-ribbon__btn .order-btn-link { flex: 1 1 auto; }
.pc-ribbon__btn .cart-icon-btn,
.pc-ribbon__btn .cart-icon-link { flex: 1 1 auto; }
.pc-ribbon__btn .cart-icon-btn {
    background: var(--button-bg, var(--primary-color, #0d6efd));
    color: var(--button-text, #fff);
    border: none; border-radius: 4px; cursor: pointer;
    padding: 8px 12px; font-size: 14px; font-weight: 600;
    width: 100%; height: 38px; display: inline-flex; align-items: center; justify-content: center;
}
.pc-ribbon__btn .cart-icon-btn::before { content: "{{ __('Add to cart') }}"; padding-right: 6px; color: var(--button-text, #fff);}

/* ---------- GLASSMORPHISM — frosted info bar + floating FAB ---------- */
body.product-card-glass .pc-glass {
    position: relative; overflow: hidden; border-radius: var(--border-radius, 14px);
    background: #fff; box-shadow: var(--card-shadow, 0 6px 24px rgba(0,0,0,.08));
    border: 1px solid var(--border-color, #eee);
    transition: transform .3s ease, box-shadow .3s ease;
}
body.product-card-glass .pc-glass:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(0,0,0,.18); }
.pc-glass__media { position: relative; overflow: hidden; }
.pc-glass__img { display: block; aspect-ratio: 1 / 1; }
.pc-glass__img img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s ease; }
.pc-glass:hover .pc-glass__img img { transform: scale(1.08); }
.pc-glass__info {
    position: absolute; left: 10px; right: 10px; bottom: 10px; z-index: 2;
    background: rgba(255,255,255,.82); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.55);
    border-radius: 12px; padding: 10px 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
}
.pc-glass__stars { color: #f5b301; font-size: 11px; margin-bottom: 2px; letter-spacing: 1px; }
.pc-glass__name {
    display: block; color: var(--heading-color, #222); font-size: 13px; font-weight: 600;
    line-height: 1.35; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.pc-glass__name:hover { color: var(--primary-color, #f04e23); }
.pc-glass__price { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
.pc-glass__price del { color: #aaa; font-size: 11px; }
.pc-glass__price span { font-size: 15px; font-weight: 800; color: var(--primary-color, #f04e23); }
.pc-glass__badge {
    position: absolute; top: 12px; left: 12px; z-index: 3;
    background: var(--sale-badge-bg, #e53935); color: var(--sale-badge-text, #fff);
    font-size: 11px; font-weight: 700; padding: 4px 9px; border-radius: 30px;
}
.pc-glass__fab {
    position: absolute; top: 12px; right: 12px; z-index: 3;
    width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center;
    border: none; border-radius: 50%; background: var(--primary-color, #f04e23); color: #fff;
    font-size: 16px; cursor: pointer; text-decoration: none;
    box-shadow: 0 6px 18px rgba(0,0,0,.25); transition: transform .25s, background .25s;
}
.pc-glass__fab:hover { transform: scale(1.1); background: var(--button-hover-bg, #c53a16); }

/* ---------- Shared stars for classic family ---------- */
.pc-classic-stars { color: #f5b301; font-size: 11px; line-height: 1; margin: 2px 0 4px; letter-spacing: 1px; }

/* ============================================================
   1. LEGACY  —  the ORIGINAL card, kept under this name
   (no overrides — identical to the original design)
   ============================================================ */


/* ============================================================
   2. MINIMAL  —  borderless, soft shadow, contained image
   ============================================================ */
body.product-card-minimal .product_item {
    border: none;
    border-radius: 0;
    padding: 0;
    background: transparent;
    box-shadow: var(--card-shadow, 0 4px 20px rgba(0,0,0,0.06));
    overflow: hidden;
    transition: box-shadow .35s ease, transform .35s ease;
}
body.product-card-minimal .product_item:hover {
    border-color: transparent !important;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    transform: translateY(-4px);
}
body.product-card-minimal .product_item_inner .sale-badge {
    top: 10px;
    right: 10px;
}
body.product-card-minimal .product_item_inner .sale-badge-inner {
    --sale-badge-width: 42px;
}
body.product-card-minimal .product_item_inner .sale-badge-box {
    background: var(--sale-badge-bg, var(--accent-color, #ff6a00));
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
body.product-card-minimal .product_item_inner span.sale-badge-text {
    color: var(--sale-badge-text, #fff);
}
body.product-card-minimal .pro_img img {
    object-fit: cover;
}
body.product-card-minimal .pro_name a {
    color: var(--text-color, #212529);
    font-weight: 600;
    font-size: 14px;
}
body.product-card-minimal .pro_name a:hover {
    text-decoration: none;
    color: var(--primary-color);
}
body.product-card-minimal .pro_price p {
    color: var(--primary-color);
    font-weight: 700;
    font-size: 16px;
}
body.product-card-minimal .pro_price del {
    color: var(--text-color);
    opacity: .55;
    font-size: 13px;
}
body.product-card-minimal .product_item .order-btn,
body.product-card-minimal .product_item .order-btn-link {
    background: var(--button-bg, var(--primary-color, #0d6efd));
    color: var(--button-text, #fff);
    border-radius: 6px;
}
body.product-card-minimal .product_item .order-btn:hover,
body.product-card-minimal .product_item .order-btn-link:hover {
    background: var(--button-hover-bg, #0b5ed7);
}
body.product-card-minimal .product_item .cart-icon-btn,
body.product-card-minimal .product_item .cart-icon-link {
    background: var(--button-bg, var(--primary-color, #0d6efd));
    border-radius: 6px;
}
body.product-card-minimal .product_item .cart-icon-btn i,
body.product-card-minimal .product_item .cart-icon-link i {
    color: var(--button-text, #fff);
}
body.product-card-minimal .product_item .cart-icon-btn:hover,
body.product-card-minimal .product_item .cart-icon-link:hover {
    background: var(--button-hover-bg, #0b5ed7);
}
body.product-card-minimal .fa-star,
body.product-card-minimal .fa-star-half-alt {
    color: var(--accent-color, #f5a623);
}
body.product-card-minimal .stock-out-overlay {
    background: rgba(255,255,255,0.85);
    color: var(--sale-badge-bg, #d32f2f);
    font-size: 12px;
    letter-spacing: 1px;
}


/* ============================================================
   3. CLASSIC  —  bold border, square corners, uppercase name,
                  rectangular corner-tag badge
   ============================================================ */
body.product-card-classic .product_item {
    border: 2px solid var(--border-color, #dee2e6);
    border-radius: 0;
    padding: 10px;
    background: #fff;
    box-shadow: none;
}
body.product-card-classic .product_item:hover {
    border-color: var(--primary-color) !important;
    box-shadow: 4px 4px 0 rgba(0,0,0,0.08);
    transform: translate(-2px,-2px);
}
body.product-card-classic .product_item_inner .sale-badge {
    top: 0;
    right: auto;
    left: 0;
}
body.product-card-classic .product_item_inner .sale-badge-inner {
    --sale-badge-width: auto;
    width: auto;
    height: auto;
}
body.product-card-classic .product_item_inner .sale-badge-box {
    background: var(--sale-badge-bg, #dc3545);
    border-radius: 0;
    padding: 3px 10px;
    width: auto;
    height: auto;
}
body.product-card-classic .product_item_inner span.sale-badge-text {
    color: var(--sale-badge-text, #fff);
    font-size: 12px;
    letter-spacing: 0.5px;
}
body.product-card-classic .pro_img img {
    object-fit: contain;
    padding: 6px;
}
body.product-card-classic .pro_name {
    height: 40px;
}
body.product-card-classic .pro_name a {
    color: var(--heading-color, #111);
    text-transform: uppercase;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 0.3px;
}
body.product-card-classic .pro_name a:hover {
    text-decoration: none;
    color: var(--primary-color);
}
body.product-card-classic .pro_price p {
    color: var(--heading-color, #111);
    font-weight: 700;
    font-size: 15px;
}
body.product-card-classic .pro_price del {
    color: var(--text-color, #6c757d);
}
body.product-card-classic .product_item .order-btn,
body.product-card-classic .product_item .order-btn-link {
    background: var(--button-bg, var(--heading-color, #111));
    color: var(--button-text, #fff);
    border-radius: 0;
    text-transform: uppercase;
}
body.product-card-classic .product_item .order-btn:hover,
body.product-card-classic .product_item .order-btn-link:hover {
    background: var(--button-hover-bg, var(--primary-color));
}
body.product-card-classic .product_item .cart-icon-btn,
body.product-card-classic .product_item .cart-icon-link {
    background: #fff;
    border: 2px solid var(--heading-color, #111);
    border-radius: 0;
}
body.product-card-classic .product_item .cart-icon-btn i,
body.product-card-classic .product_item .cart-icon-link i {
    color: var(--heading-color, #111);
}
body.product-card-classic .product_item .cart-icon-btn:hover,
body.product-card-classic .product_item .cart-icon-link:hover {
    background: var(--heading-color, #111);
    border-color: var(--heading-color, #111);
}
body.product-card-classic .product_item .cart-icon-btn:hover i,
body.product-card-classic .product_item .cart-icon-link:hover i {
    color: var(--button-text, #fff);
}
body.product-card-classic .fa-star,
body.product-card-classic .fa-star-half-alt {
    color: var(--accent-color, #ffc107);
}
body.product-card-classic .stock-out-overlay {
    background: rgba(0,0,0,0.75);
    color: var(--button-text, #fff);
    font-size: 12px;
    letter-spacing: 2px;
}


/* ============================================================
   4. DARK  —  dark card, light text, accent button
   ============================================================ */
body.product-card-dark .product_item {
    border: 1px solid var(--border-color, #2a2a2a);
    border-radius: 10px;
    padding: 10px;
    background: var(--footer-bg, #1e1e1e);
    box-shadow: 0 6px 18px rgba(0,0,0,0.35);
}
body.product-card-dark .product_item:hover {
    border-color: var(--accent-color, #ff6a00) !important;
    box-shadow: 0 12px 26px rgba(0,0,0,0.5);
    transform: translateY(-4px);
}
body.product-card-dark .product_item_inner .sale-badge {
    top: 12px;
    right: 12px;
}
body.product-card-dark .product_item_inner .sale-badge-inner {
    --sale-badge-width: 44px;
}
body.product-card-dark .product_item_inner .sale-badge-box {
    background: var(--sale-badge-bg, var(--accent-color, #ff6a00));
    border: 2px solid var(--footer-text, #fff);
    box-shadow: 0 2px 10px rgba(0,0,0,0.4);
}
body.product-card-dark .product_item_inner span.sale-badge-text {
    color: var(--sale-badge-text, #fff);
}
body.product-card-dark .pro_name a {
    color: var(--footer-text, #f1f1f1);
    font-weight: 600;
}
body.product-card-dark .pro_name a:hover {
    text-decoration: none;
    color: var(--accent-color, #ff6a00);
}
body.product-card-dark .pro_price p {
    color: var(--accent-color, #ff6a00);
    font-weight: 700;
    font-size: 16px;
}
body.product-card-dark .pro_price del {
    color: var(--footer-text, #8b8b8b);
    opacity: .7;
}
body.product-card-dark .product_item .order-btn,
body.product-card-dark .product_item .order-btn-link {
    background: var(--button-bg, var(--accent-color, #ff6a00));
    color: var(--button-text, #fff);
    border-radius: 6px;
    font-weight: 700;
}
body.product-card-dark .product_item .order-btn:hover,
body.product-card-dark .product_item .order-btn-link:hover {
    background: var(--button-hover-bg, #e65c00);
}
body.product-card-dark .product_item .cart-icon-btn,
body.product-card-dark .product_item .cart-icon-link {
    background: var(--footer-bg, #333);
    border: 1px solid var(--border-color, #333);
    border-radius: 6px;
}
body.product-card-dark .product_item .cart-icon-btn i,
body.product-card-dark .product_item .cart-icon-link i {
    color: var(--accent-color, #ff6a00);
}
body.product-card-dark .product_item .cart-icon-btn:hover,
body.product-card-dark .product_item .cart-icon-link:hover {
    background: var(--accent-color, #ff6a00);
    border-color: var(--accent-color, #ff6a00);
}
body.product-card-dark .product_item .cart-icon-btn:hover i,
body.product-card-dark .product_item .cart-icon-link:hover i {
    color: var(--button-text, #fff);
}
body.product-card-dark .fa-star,
body.product-card-dark .fa-star-half-alt {
    color: var(--accent-color, #ffb400);
}
body.product-card-dark .stock-out-overlay {
    background: rgba(0,0,0,0.7);
    color: var(--accent-color, #ff6a00);
    font-size: 12px;
    letter-spacing: 1px;
}


/* ============================================================
   5. ROUNDED  —  big radius, pastel shadow, pill buttons
   ============================================================ */
body.product-card-rounded .product_item {
    border: 1px solid var(--border-color, #f0f0f0);
    border-radius: 20px;
    padding: 12px;
    background: #fff;
    box-shadow: var(--card-shadow, 0 8px 24px rgba(0,0,0,0.08));
    transition: box-shadow .35s ease, transform .35s ease;
}
body.product-card-rounded .product_item:hover {
    border-color: var(--primary-color) !important;
    box-shadow: 0 16px 36px rgba(0,0,0,0.12);
    transform: translateY(-5px);
}
body.product-card-rounded .product_item_inner .sale-badge {
    top: 14px;
    right: 14px;
}
body.product-card-rounded .product_item_inner .sale-badge-inner {
    --sale-badge-width: 46px;
}
body.product-card-rounded .product_item_inner .sale-badge-box {
    background: linear-gradient(135deg, var(--sale-badge-bg, #f7971e), var(--accent-color, #ffd200));
    border-radius: 50%;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
body.product-card-rounded .product_item_inner span.sale-badge-text {
    color: var(--sale-badge-text, #fff);
}
body.product-card-rounded .pro_img {
    border-radius: 14px;
    overflow: hidden;
}
body.product-card-rounded .pro_img img {
    object-fit: cover;
    border-radius: 14px;
}
body.product-card-rounded .pro_name a {
    color: var(--text-color, #333);
    font-weight: 600;
    font-size: 14px;
}
body.product-card-rounded .pro_name a:hover {
    text-decoration: none;
    color: var(--primary-color);
}
body.product-card-rounded .pro_price p {
    color: var(--primary-color);
    font-weight: 700;
    font-size: 16px;
}
body.product-card-rounded .pro_price del {
    color: var(--text-color);
    opacity: .5;
}
body.product-card-rounded .product_item .order-btn,
body.product-card-rounded .product_item .order-btn-link {
    background: var(--button-bg, var(--primary-color, #0d6efd));
    color: var(--button-text, #fff);
    border-radius: 999px;
    font-weight: 700;
    height: 42px;
}
body.product-card-rounded .product_item .order-btn:hover,
body.product-card-rounded .product_item .order-btn-link:hover {
    background: var(--button-hover-bg, #0b5ed7);
}
body.product-card-rounded .product_item .cart-icon-btn,
body.product-card-rounded .product_item .cart-icon-link {
    background: var(--primary-color);
    opacity: .12;
    border-radius: 999px;
}
body.product-card-rounded .product_item .cart-icon-btn i,
body.product-card-rounded .product_item .cart-icon-link i {
    color: var(--primary-color);
}
body.product-card-rounded .product_item .cart-icon-btn:hover,
body.product-card-rounded .product_item .cart-icon-link:hover {
    background: var(--button-bg, var(--primary-color));
    opacity: 1;
}
body.product-card-rounded .product_item .cart-icon-btn:hover i,
body.product-card-rounded .product_item .cart-icon-link:hover i {
    color: var(--button-text, #fff);
}
body.product-card-rounded .fa-star,
body.product-card-rounded .fa-star-half-alt {
    color: var(--accent-color, #f5a623);
}
body.product-card-rounded .stock-out-overlay {
    background: rgba(255,255,255,0.88);
    color: var(--sale-badge-bg, #dc3545);
    font-size: 12px;
    border-radius: 999px;
    left: 10px;
    width: calc(100% - 20px);
}


/* ============================================================
   6. GRADIENT  —  gradient accent border + button, hover lift
   ============================================================ */
body.product-card-gradient .product_item {
    border: 1px solid transparent;
    border-radius: 12px;
    padding: 5px;
    background: linear-gradient(#fff, #fff) padding-box,
                linear-gradient(135deg, var(--primary-color, #0d6efd), var(--secondary-color, #198754), var(--accent-color, #ff6a00)) border-box;
    box-shadow: var(--card-shadow, 0 6px 20px rgba(0,0,0,0.15));
    transition: box-shadow .35s ease, transform .35s ease;
}
body.product-card-gradient .product_item:hover {
    border-color: transparent !important;
    box-shadow: 0 16px 36px rgba(0,0,0,0.2);
    transform: translateY(-6px) scale(1.01);
}
body.product-card-gradient .product_item_inner .sale-badge {
    top: 12px;
    right: 12px;
}
body.product-card-gradient .product_item_inner .sale-badge-inner {
    --sale-badge-width: 44px;
}
body.product-card-gradient .product_item_inner .sale-badge-box {
    background: linear-gradient(135deg, var(--primary-color, #667eea), var(--secondary-color, #764ba2));
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}
body.product-card-gradient .product_item_inner span.sale-badge-text {
    color: var(--sale-badge-text, #fff);
}
body.product-card-gradient .pro_img img {
    object-fit: cover;
}
body.product-card-gradient .pro_name a {
    color: var(--text-color, #333);
    font-weight: 700;
    font-size: 14px;
}
body.product-card-gradient .pro_name a:hover {
    text-decoration: none;
    background: linear-gradient(135deg, var(--primary-color, #667eea), var(--accent-color, #f093fb));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
body.product-card-gradient .pro_price p {
    background: linear-gradient(135deg, var(--primary-color, #667eea), var(--accent-color, #f093fb));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 800;
    font-size: 16px;
}
body.product-card-gradient .pro_price del {
    color: var(--text-color);
    opacity: .5;
}
body.product-card-gradient .product_item .order-btn,
body.product-card-gradient .product_item .order-btn-link {
    background: linear-gradient(135deg, var(--primary-color, #667eea), var(--secondary-color, #764ba2));
    color: var(--button-text, #fff);
    border-radius: 8px;
    font-weight: 700;
    border: none;
}
body.product-card-gradient .product_item .order-btn:hover,
body.product-card-gradient .product_item .order-btn-link:hover {
    background: linear-gradient(135deg, var(--secondary-color, #764ba2), var(--accent-color, #f093fb));
}
body.product-card-gradient .product_item .cart-icon-btn,
body.product-card-gradient .product_item .cart-icon-link {
    background: linear-gradient(135deg, var(--primary-color, #667eea), var(--secondary-color, #764ba2));
    border-radius: 8px;
    border: none;
}
body.product-card-gradient .product_item .cart-icon-btn i,
body.product-card-gradient .product_item .cart-icon-link i {
    color: var(--button-text, #fff);
}
body.product-card-gradient .product_item .cart-icon-btn:hover,
body.product-card-gradient .product_item .cart-icon-link:hover {
    background: linear-gradient(135deg, var(--secondary-color, #764ba2), var(--accent-color, #f093fb));
}
body.product-card-gradient .fa-star,
body.product-card-gradient .fa-star-half-alt {
    color: var(--secondary-color, #764ba2);
}
body.product-card-gradient .stock-out-overlay {
    background: rgba(255,255,255,0.85);
    color: var(--secondary-color, #764ba2);
    font-size: 12px;
    letter-spacing: 1px;
}
</style>
