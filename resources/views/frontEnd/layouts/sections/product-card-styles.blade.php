{{-- Theme-controlled product card styling shared by all storefront pages. --}}
<style>
    .main_product_inner,
    .product_sliders {
        display: grid;
        gap: 16px;
    }

    body.product-card-default .product_item,
    body.product-card-classic .product_item,
    body.product-card-rounded .product_item {
        border-radius: var(--border-radius, 8px);
        box-shadow: var(--card-shadow, 0 2px 8px rgba(0, 0, 0, .08));
        overflow: hidden;
    }

    body.product-card-rounded .product_item { border-radius: 16px; }
    body.product-card-dark .product_item { background: #20252b; color: #f8f9fa; }
    body.product-card-gradient .product_item { background: linear-gradient(145deg, #fff, #f4f7fb); }
    body.product-card-minimal .product_item { box-shadow: none; border: 1px solid var(--border-color, #dee2e6); }

    body .product_item .pro_img {
        height: var(--pc-image-height, 200px);
    }

    .product_item .pro_name a {
        white-space: normal;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: var(--pc-title-lines, 2);
        overflow: hidden;
    }
</style>
