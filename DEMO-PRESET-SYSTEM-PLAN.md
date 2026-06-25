# Demo Preset System — Implementation Plan

## Overview
Add a one-click demo preset system to the admin panel with 5 shop types, each having distinct themes, categories, products, and settings. Admins can import any preset with a single click and/or reset the entire site.

---

## 1. Shop Types (Presets)

| # | Preset Name | Source | Description | Live URL |
|---|-------------|--------|-------------|----------|
| 1 | **Gadget + Fashion + Grocery** | Ecommerce1 | Multi-type shop (gadgets, fashion, grocery combined) | https://ecommerce1.creativedesign.com.bd/ |
| 2 | **Electronics** | Ecommerce2 | Pure electronics & tech products | https://ecommerce2.creativedesign.com.bd/ |
| 3 | **Natural Food & Grocery** | Ecommerce3 | Organic food, grocery, health foods | https://ecommerce3.creativedesign.com.bd/ |
| 4 | **Clothing Fashion** | Ecommerce6 | Apparel, fashion wear, accessories | https://ecommerce6.creativedesign.com.bd/ |
| 5 | **Beauty & Cosmetics** | Ecommerce7 | Beauty products, skincare, makeup | https://ecommerce7.creativedesign.com.bd/ |

---

## 2. Directory Structure

```
storage/app/demo-presets/
├── gadget-fashion-grocery/
│   ├── screenshot.jpg        ← Full-page screenshot
│   ├── data.json             ← All preset data
│   └── images/               ← Product images
├── electronics/
│   ├── screenshot.jpg
│   ├── data.json
│   └── images/
├── food-grocery/
│   ├── screenshot.jpg
│   ├── data.json
│   └── images/
├── clothing-fashion/
│   ├── screenshot.jpg
│   ├── data.json
│   └── images/
└── beauty/
    ├── screenshot.jpg
    ├── data.json
    └── images/
```

---

## 3. Preset Data Structure (`data.json`)

Each preset folder contains a `data.json` file with:

```json
{
  "meta": {
    "name": "Gadget + Fashion + Grocery",
    "slug": "gadget-fashion-grocery",
    "description": "Multi-category shop selling gadgets, fashion items, and grocery products.",
    "live_url": "https://ecommerce1.creativedesign.com.bd/",
    "screenshot": "screenshot.jpg"
  },
  "general_settings": {
    "name": "Gadget & Fashion Store",
    "white_logo": "public/assets/images/logo-white.png",
    "dark_logo": "public/assets/images/logo-dark.png",
    "favicon": "public/favicon.ico",
    "copyright": "© 2026 Gadget & Fashion. All rights reserved.",
    "show_all_products": true,
    "show_category_wise_products": true,
    "vendor_enabled": false,
    "reseller_enabled": false
  },
  "theme": {
    "name": "Ocean Blue",
    "slug": "ocean-blue",
    "primary_color": "#0d6efd",
    "secondary_color": "#0b5ed7",
    "accent_color": "#ff6a00",
    "text_color": "#212529",
    "heading_color": "#111111",
    "body_bg_color": "#ffffff",
    "header_bg_color": "#ffffff",
    "header_text_color": "#212529",
    "footer_bg_color": "#1a1a1a",
    "footer_text_color": "#cccccc",
    "is_default": true,
    "is_active": true
  },
  "categories": [
    { "name": "Electronics", "slug": "electronics", ... },
    ...
  ],
  "subcategories": [
    { "category": "electronics", "name": "Mobile Phones", ... },
    ...
  ],
  "brands": [
    { "name": "Samsung", "slug": "samsung", ... },
    ...
  ],
  "products": [
    { "name": "Samsung Galaxy S24", "category": "electronics", "brand": "Samsung", ... },
    ...
  ],
  "banner_categories": [...],
  "banners": [...],
  "blogs": [...],
  "shipping_charges": [...]
}
```

---

## 4. Controller Changes (`DemoController.php`)

### New Methods to Add:

#### `importPreset($slug)`
- Reads `storage/app/demo-presets/{slug}/data.json`
- Truncates existing data (categories, products, brands, etc.)
- Inserts all preset data
- Updates general settings & theme
- Clears cache
- Returns success redirect

#### `resetSite()`
- Truncates ALL data tables (except users/admins)
- Re-runs `DemoDataSeeder` for minimal default data
- Clears cache
- Returns success redirect

### Modified Methods:
- `index()` — Load preset list from folders instead of zip files
- Keep existing import/export for compatibility

---

## 5. Route Changes (`routes/web.php`)

Add these routes under the demo group:

```php
Route::post('demo/import-preset/{slug}', [DemoController::class, 'importPreset'])->name('demo.import-preset');
Route::post('demo/reset', [DemoController::class, 'resetSite'])->name('demo.reset');
```

---

## 6. View Changes (`resources/views/backEnd/demo/index.blade.php`)

### Layout:

```
┌─────────────────────────────────────────────────────────────────┐
│  Demo Management                                                │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐        │
│  │ Stats │ │ Stats │ │ Stats │ │ Stats │ │ Stats │ │ Stats │   │
│  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘        │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Available Demo Presets                                 │   │
│  │                                                         │   │
│  │  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐    │   │
│  │  │ ████████████  │ │ ████████████ │ │ ████████████ │    │   │
│  │  │ Screenshot    │ │ Screenshot   │ │ Screenshot   │    │   │
│  │  │ Gadget+Fashion│ │ Electronics  │ │ Food+Grocery │    │   │
│  │  │ [Preview][Imp]│ │ [Preview][Imp]│ │ [Preview][Imp]│    │   │
│  │  └──────────────┘ └──────────────┘ └──────────────┘    │   │
│  │                                                         │   │
│  │  ┌──────────────┐ ┌──────────────┐                      │   │
│  │  │ ████████████  │ │ ████████████ │                     │   │
│  │  │ Screenshot    │ │ Screenshot   │                     │   │
│  │  │ Clothing/Fash │ │ Beauty Shop  │                     │   │
│  │  │ [Preview][Imp]│ │ [Preview][Imp]│                     │   │
│  │  └──────────────┘ └──────────────┘                      │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Danger Zone — Full Site Reset                           │   │
│  │  [⚠ Reset Everything]                                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Export / Import (existing)                              │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### Preset Cards:
- Each card shows a full-page screenshot thumbnail
- Preset name + short description
- "Live Preview" link (opens live site in new tab)
- "Import This Demo" button
- Loading spinner during import

### Reset Section:
- Red danger zone card with confirmation modal
- "Reset Everything" button with double confirmation

---

## 7. Tables to Manage During Reset

During import/reset, these tables are truncated and re-seeded:

| Table | Action |
|-------|--------|
| `categories` | Truncate → Insert preset |
| `subcategories` | Truncate → Insert preset |
| `brands` | Truncate → Insert preset |
| `products` | Truncate → Insert preset |
| `productimages` | Truncate → Insert preset |
| `banner_categories` | Truncate → Insert preset |
| `banners` | Truncate → Insert preset |
| `blogs` | Truncate → Insert preset |
| `shipping_charges` | Truncate → Insert preset |
| `general_settings` | Update store info |
| `themes` | Insert/update theme |
| (Keep users/admins) | No change |

---

## 8. Screenshots

Screenshots will be captured using browser tools and stored in each preset folder. The view will resize them to card thumbnails.

---

## 9. Implementation Steps

1. ✏️ **Create this plan.md** ✅
2. 📁 **Create preset folders** with `data.json` files
3. 🖼️ **Add screenshots** for each preset
4. 🧩 **Update DemoController** — add `importPreset()` and `resetSite()`
5. 🛣️ **Update routes** — add new routes
6. 🎨 **Update view** — preset cards, preview buttons, reset section
7. 🔗 **Add live preview links** to each preset card
8. 🧪 **Test** — import each preset, verify data
