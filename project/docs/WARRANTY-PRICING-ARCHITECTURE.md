# 💰 WARRANTY PRICING ARCHITECTURE

> **Multi-Layered Price Stack** | Version 2.0 | Date: 2026-07-21

---

## 1. The Price Stack Formula

```
FINAL PRICE = BASE PRICE − WHOLESALE DISCOUNT ± WARRANTY ADJUSTMENT
```

### Layer-by-Layer Breakdown

| Layer | Source | Direction | Example |
|-------|--------|-----------|---------|
| **① BASE PRICE** | `products.new_price` or variant price | — | 500 TK |
| **② WHOLESALE DISCOUNT** | `product_wholesale_prices.wholesale_price` (if qty ≥ min_qty) | ↓ reduces | −50 TK |
| **③ WARRANTY ADJUSTMENT** | `product_warranty_tiers.additional_cost` | ± adjusts | +50 TK or −50 TK |
| **= FINAL PRICE** | — | — | **500 TK** or **450 TK** |

### Why This Stack?

```
Customer gets maximum discount AND best warranty service
Admin gets accurate product costing per tier
Reports show exactly how much warranty contributes to revenue
Business can analyze: warranty uptake %, avg warranty premium, warranty-driven margin
```

---

## 2. Warranty Tiers as Adjustments (NOT Replacements)

### 2.1 The `additional_cost` Field

`product_warranty_tiers.additional_cost` is the **key field**. It defines how much the warranty ADDS or SUBTRACTS from the final price:

| Tier | `additional_cost` | Meaning |
|------|-------------------|---------|
| **No Warranty** | `−50` (negative) | Discount of 50 TK — customer saves money by skipping warranty |
| **Supplier Warranty** | `0` (zero) | No change — customer pays base price |
| **Extended Warranty** | `+100` (positive) | Premium of 100 TK — customer pays extra for store warranty |

### 2.2 The `price` Field (For Display Only)

The `price` column becomes **derived/display-only**. Admin sets it visually but the system uses `additional_cost` for calculations:

```php
// Admin sets:
//   No Warranty:     display price = 450 TK  →  additional_cost = 450 - base = -50
//   Supplier:        display price = 500 TK  →  additional_cost = 500 - base = 0
//   Extended:        display price = 550 TK  →  additional_cost = 550 - base = +100
```

### 2.3 Full Example: Product A

```
Product A: base price = 500 TK

┌─────────────────────────────────────────────────────────────┐
│ Customer buys 1 unit:                                       │
│                                                             │
│ ① Base Price                          500 TK               │
│ ② Wholesale (qty=1, no tier)          −0 TK                │
│ ③ Warranty: No Warranty               −50 TK               │
│    FINAL PRICE                         450 TK               │
├─────────────────────────────────────────────────────────────┤
│ Customer buys 10 units (wholesale):                         │
│                                                             │
│ ① Base Price (10×500)                  5,000 TK             │
│ ② Wholesale (tier: 10+ pcs @ 450)      −500 TK             │
│ ③ Warranty: Extended                   +100 TK × 10 = 1,000 │
│    FINAL PRICE                         5,500 TK             │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Database Changes Needed

### 3.1 `product_warranty_tiers` — Rename/Repurpose Fields

```sql
-- additional_cost becomes the primary calculation field
-- price becomes display-only (auto-computed from base + additional_cost)

ALTER TABLE product_warranty_tiers 
  MODIFY additional_cost DECIMAL(12,2) NOT NULL DEFAULT 0 
  COMMENT 'Adjustment: negative=discount, positive=premium, zero=no change';
```

### 3.2 `warranty_sales` — Add `additional_cost` Snapshot

```sql
ALTER TABLE warranty_sales 
  ADD COLUMN additional_cost DECIMAL(12,2) DEFAULT 0 
  COMMENT 'Snapshot of warranty adjustment at time of sale';
```

### 3.3 `order_details` — Clarify `warranty_price`

```sql
-- warranty_price column already exists — it stores the FINAL warranty adjustment
-- Rename conceptually: warranty_price = additional_cost applied
ALTER TABLE order_details 
  MODIFY warranty_price DECIMAL(12,2) DEFAULT 0 
  COMMENT 'Warranty adjustment applied (can be negative for discount)';
```

---

## 4. Price Calculation Flow — Every Touchpoint

### 4.1 Product Detail Page (Frontend)

```
Frontend JS:
  basePrice = variant ? variant.price : product.new_price
  selectedTier = $('.warranty-radio:checked')
  finalPrice = basePrice + (selectedTier.additional_cost || 0)
  $('#newPrice').text(finalPrice)
```

### 4.2 Add to Cart (`ShoppingController::cart_store`)

```php
// current (WRONG): $price = $warrantyTier->price;
// correct:
$price = $variantPrice ?? $product->new_price;  // ① base

// ② wholesale
if ($wholesaleTier) {
    $price = $wholesaleTier->wholesale_price;
}

// ③ warranty adjustment
if ($warrantyTier) {
    $price += (float) $warrantyTier->additional_cost;
}

// Store in cart
Cart::add([
    'price'   => $price,
    'options' => [
        'warranty_tier_id'     => $warrantyTier?->id,
        'warranty_adjustment'  => $warrantyTier?->additional_cost ?? 0,
        'base_price'           => $variantPrice ?? $product->new_price,
    ],
]);
```

### 4.3 Customer Cart Display

```
Cart table shows:
  Product    | Price (base+adj) | Qty | Total
  Product A  | 450 TK           | 1   | 450 TK    (No Warranty, −50 adj)
```

### 4.4 Customer Checkout

```
Order summary shows:
  Subtotal (products):    1,650 TK
  Shipping:                  60 TK
  Discount:                   0 TK
  ────────────────────────────────
  GRAND TOTAL:            1,710 TK
```

### 4.5 Customer Invoice

Each line shows:
```
Product A — Smart Watch
  Size: M  Color: Red
  🛡️ No Warranty (−50 TK discount)
  Price: 450 TK × 1 = 450 TK
```

### 4.6 POS Cart (`OrderController::cart_update`)

```php
// current (WRONG): $newPrice = $tier->price;
// correct:
$newPrice = $basePrice;
if ($warrantyTier) {
    $newPrice += (float) $warrantyTier->additional_cost;
}
```

### 4.7 POS Order Save (`OrderController::order_store`)

```php
// Already saves warranty_tier_id + warranty_price
// warranty_price now = additional_cost (adjustment amount)
$order_details->warranty_price = $warrantyTier->additional_cost;
```

### 4.8 Admin Invoice

Same as customer invoice — shows warranty adjustment as a line note.

### 4.9 Warranty Sale Record

```php
WarrantySale::create([
    'warranty_price'  => $warrantyTier->additional_cost, // adjustment
    'additional_cost' => $warrantyTier->additional_cost,
]);
```

---

## 5. Admin Product Edit — Warranty Tier Form

### 5.1 Updated Fields

| Field | Purpose | Example |
|-------|---------|---------|
| Variant | Per-variant pricing | All / Red-M |
| Warranty Type | none / supplier / extended | No Warranty |
| Days | Read-only / editable | 0 / 180 / 90 |
| **Additional Cost (TK)** | **The adjustment amount** | **−50 / 0 / +100** |
| Display Price (TK) | Auto: base + additional_cost | 450 / 500 / 550 |
| Active | Show on frontend | Yes |

### 5.2 Form Behavior

```
Type: "No Warranty"     → Days: 0 (locked), Additional Cost: typically negative
Type: "Supplier"        → Days: supplier remaining (locked), Additional Cost: 0
Type: "Extended"        → Days: editable, Additional Cost: typically positive
```

### 5.3 JS Auto-Calculation

```javascript
function calcDisplayPrice(row) {
    const base = parseFloat($('#new_price').val()) || 0;
    const adj  = parseFloat(row.find('.additional-cost-input').val()) || 0;
    row.find('.display-price').text((base + adj).toFixed(2));
}
```

---

## 6. Reports & Analytics

### 6.1 Warranty Contribution Report

```sql
SELECT 
    warranty_type,
    COUNT(*) as sales_count,
    SUM(additional_cost) as total_warranty_revenue,
    AVG(additional_cost) as avg_adjustment,
    SUM(CASE WHEN additional_cost > 0 THEN 1 ELSE 0 END) as premium_count,
    SUM(CASE WHEN additional_cost < 0 THEN 1 ELSE 0 END) as discount_count
FROM warranty_sales
GROUP BY warranty_type;
```

### 6.2 Product Margin with Warranty

```
Product:   base_cost = 300 TK
           base_price = 500 TK
           base_margin = 200 TK (40%)

With No Warranty (−50):
           sell_price = 450 TK
           margin = 150 TK (33%)

With Extended (+100):
           sell_price = 600 TK
           margin = 300 TK (50%)
```

---

## 7. Migration Plan

### Phase 1: Schema
1. Ensure `product_warranty_tiers.additional_cost` has proper default (0.00)
2. Add `warranty_sales.additional_cost` column
3. Update `order_details.warranty_price` to store adjustment

### Phase 2: Code — Price Calculation
1. `ShoppingController::cart_store()` — use additional_cost
2. `OrderController::cart_update()` — use additional_cost
3. `OrderHelper::saveOrderDetails()` — store adjustment
4. `WarrantyService::createWarrantySale()` — snapshot adjustment
5. Frontend JS (product page) — use additional_cost

### Phase 3: Code — Display
1. Customer cart — show adjustment
2. Customer checkout — show adjustment
3. Customer invoice — show adjustment
4. Admin invoice — show adjustment
5. POS cart rows — show adjustment
6. Product edit warranty form — add additional_cost field

### Phase 4: Reports
1. Warranty revenue report
2. Product margin with warranty breakdown

---

## 8. Summary

| Concept | Old (Wrong) | New (Correct) |
|---------|------------|---------------|
| Warranty pricing | Tier replaces price | Tier adjusts price (±) |
| No Warranty | Price = 450 (hardcoded) | Price = base − 50 (adjustment) |
| Extended | Price = 550 (hardcoded) | Price = base + 100 (adjustment) |
| Wholesale + Warranty | Conflicting | Wholesale first, then warranty |
| Reporting | Can't tell warranty revenue | Clear adjustment tracking |

---

> **Document Version**: 2.0  
> **Last Updated**: 2026-07-21  
> **Key Change**: Warranty `additional_cost` as adjustment layer, not price replacement
