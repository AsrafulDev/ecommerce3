{{--
  Product Card CSS for the Customer Panel
  ========================================
  The customer panel (frontEnd/layouts/customer/panel.blade.php) is a Tailwind-based
  layout that does NOT load the storefront base stylesheet (style.blade.php). Pages
  such as the account dashboard render product cards via
  @include('frontEnd.layouts.sections.product-card'), so the card CSS must be loaded here:

    1. product-card-styles  → premium / overlay / ribbon / glass + classic restyles
    2. product-card-layout  → body.pc-other .main_product_inner responsive columns
    3. a small base (normally in style.blade.php) that the grid and the classic
       family depend on: the .main_product_inner grid, the classic .product_item
       structure and its buttons.

  Requires $generalsetting (shared globally, also set by the panel layout).
  The panel's <body> must carry classes: product-card-<style> pc-other.
--}}
@include('frontEnd.layouts.sections.product-card-styles')
@include('frontEnd.layouts.sections.product-card-layout')
<style>
    /* ---- base grid (from style.blade.php, not loaded in the panel) ---- */
    .main_product_inner {
        display: grid;
        grid-gap: 10px;
    }
    .category-product {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    }

    /* ---- classic-family base (legacy / minimal / classic / dark / rounded / gradient) ---- */
    .product_item {
        position: relative;
        background: #fff;
        border: 1px solid var(--border-color, #ddd);
        border-radius: var(--border-radius, 5px);
        padding: 5px;
        overflow: hidden;
        transition: .35s all;
    }
    .product_item_inner { transition: .35s all; }
    .product_item .pro_img {
        position: relative;
        overflow: hidden;
        background: #f6f6f8;
    }
    .product_item .pro_img img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: .35s all;
    }
    .product_item .pro_des { text-align: center; padding: 8px 6px 2px; }
    .product_item .pro_name { margin-top: 5px; padding: 0 5px; }
    .product_item .pro_name a {
        color: var(--heading-color, #000);
        font-size: 15px;
        text-transform: capitalize;
    }
    .product_item .pro_price p {
        color: var(--primary-color, #f04e23);
        font-weight: 500;
        margin-top: 5px;
        text-align: center;
    }
    .product_item .pro_btn { display: flex; gap: 6px; padding: 6px 8px 10px; }
    .product_item .pro_btn .order-btn,
    .product_item .pro_btn .order-btn-link { flex: 1 1 auto; }
    .product_item .pro_btn .cart-icon-btn,
    .product_item .pro_btn .cart-icon-link { flex: 0 0 48px; }
    .product_item .order-btn,
    .product_item .order-btn-link {
        display: flex; align-items: center; justify-content: center;
        width: 100%; height: 40px;
        background: var(--primary-color, #f04e23);
        color: #fff !important;
        border-radius: 4px; font-size: 14px; font-weight: 600;
        cursor: pointer; text-decoration: none;
    }
    .product_item .cart-icon-btn,
    .product_item .cart-icon-link {
        display: flex; align-items: center; justify-content: center;
        width: 100%; height: 40px;
        background: #fff; border: 1px solid var(--border-color, #eee);
        border-radius: 4px; cursor: pointer; font-size: 18px; text-decoration: none;
    }
    .product_item .cart-icon-btn i,
    .product_item .cart-icon-link i { color: var(--primary-color, #f04e23); }

    /* ---- sale badge + stock-out (classic / shared) ---- */
    .product_item .sale-badge { position: absolute; top: 15px; right: 4px; z-index: 1; }
    .product_item .sale-badge-box {
        background: var(--sale-badge-bg, #FF0034);
        border-radius: 50%; width: 45px; height: 45px;
        display: flex; align-items: center; justify-content: center;
    }
    .product_item .sale-badge-text {
        color: var(--sale-badge-text, #fff);
        font-size: 10px; font-weight: 600;
    }
    .stock-out-overlay {
        position: absolute; inset: 0; z-index: 2;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,.72);
        color: #e53935; font-size: 13px; font-weight: 700;
    }
</style>
