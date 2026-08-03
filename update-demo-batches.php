<?php
/**
 * Update all demo preset data.json files so product quantity is organised
 * by batch per supplier (matching the StockBatch / suppliers tables).
 *
 * For every preset it:
 *   - Adds a top-level "suppliers" array (derived from brand names + generics).
 *   - Converts each product's single "stock" into a "stock_batches" array of
 *     1–2 batches, each with: supplier, batch_no, quantity, unit_cost.
 *   - Keeps "stock" as the total (sum of batch quantities) for back-compat.
 *
 * Usage:  php update-demo-batches.php
 */

$baseDir = __DIR__ . '/storage/app/demo-presets';

if (!is_dir($baseDir)) {
    fwrite(STDERR, "ERROR: $baseDir not found\n");
    exit(1);
}

$presets = array_filter(glob($baseDir . '/*'), 'is_dir');
$totalProducts = 0;
$totalBatches = 0;

// Generic fallback suppliers when a preset has no brands.
$genericSuppliers = ['General Supplier', 'Demo Distributor', 'Wholesale Hub BD'];

foreach ($presets as $presetDir) {
    $jsonPath = $presetDir . '/data.json';
    if (!file_exists($jsonPath)) {
        echo "SKIP  " . basename($presetDir) . " — no data.json\n";
        continue;
    }

    $data = json_decode(file_get_contents($jsonPath), true);
    if (!$data) {
        echo "SKIP  " . basename($presetDir) . " — invalid JSON\n";
        continue;
    }

    // ── Build supplier list ─────────────────────────────────────
    // Use brand names as suppliers (deduplicated), plus generic fallbacks.
    $supplierNames = [];
    foreach ($data['brands'] ?? [] as $b) {
        $name = is_string($b) ? $b : ($b['name'] ?? null);
        if ($name && trim($name) !== '') {
            $supplierNames[] = trim($name);
        }
    }
    foreach ($genericSuppliers as $g) {
        $supplierNames[] = $g;
    }
    $supplierNames = array_values(array_unique($supplierNames));

    // Phone / email / address for realism.
    $phoneBase = 1700000000;
    $suppliers = [];
    foreach ($supplierNames as $idx => $name) {
        $suppliers[] = [
            'name'    => $name,
            'phone'   => '0' . ($phoneBase + $idx),
            'email'   => strtolower(preg_replace('/[^a-z0-9]+/i', '', $name)) . '@demo.com',
            'address' => 'Demo Address, Dhaka, Bangladesh',
            'status'  => 1,
        ];
    }
    $data['suppliers'] = $suppliers;

    // ── Convert each product stock → stock_batches ─────────────
    // NOTE: iterate over the plain variable $data['products'] (not `?? []`) so
    // that `&$p` reference-writes actually persist back into the array.
    $presetProductCount = 0;
    $presetBatchCount = 0;

    if (!empty($data['products'])) {
        foreach ($data['products'] as $i => &$p) {
            $presetProductCount++;
            $stock = (int) ($p['stock'] ?? 100);
            $price = (float) ($p['price'] ?? $p['new_price'] ?? 0);
            $unitCost = (int) round($price * 0.7);
            if ($unitCost <= 0) {
                $unitCost = 50;
            }

            $batches = [];
            if ($stock > 0) {
                // Two batches when there is enough stock and ≥2 suppliers to spread.
                if ($stock > 60 && count($supplierNames) >= 2) {
                    $firstQty  = (int) floor($stock * 0.6);
                    $secondQty = $stock - $firstQty;
                    $batches[] = [
                        'supplier'  => $supplierNames[$i % count($supplierNames)],
                        'batch_no'  => 'DEMO-' . ($i + 1) . '-A',
                        'quantity'  => $firstQty,
                        'unit_cost' => $unitCost,
                    ];
                    $batches[] = [
                        'supplier'  => $supplierNames[($i + 1) % count($supplierNames)],
                        'batch_no'  => 'DEMO-' . ($i + 1) . '-B',
                        'quantity'  => $secondQty,
                        'unit_cost' => $unitCost,
                    ];
                } else {
                    $batches[] = [
                        'supplier'  => $supplierNames[$i % count($supplierNames)],
                        'batch_no'  => 'DEMO-' . ($i + 1) . '-A',
                        'quantity'  => $stock,
                        'unit_cost' => $unitCost,
                    ];
                }
            }

            $p['stock_batches'] = $batches;
            $presetBatchCount += count($batches);
        }
        unset($p);

        // Keep "stock" in sync with batch totals.
        foreach ($data['products'] as $i => &$p) {
            $sum = array_sum(array_column($p['stock_batches'] ?? [], 'quantity'));
            if ($sum > 0) {
                $p['stock'] = $sum;
            }
        }
        unset($p);
    }

    // Re-encode (4-space pretty, keep / unescaped — matches existing format).
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        echo "ERROR " . basename($presetDir) . " — could not encode\n";
        continue;
    }
    file_put_contents($jsonPath, $encoded . "\n");

    $totalProducts += $presetProductCount;
    $totalBatches += $presetBatchCount;
    echo sprintf(
        "OK    %-28s products: %4d | batches: %4d | suppliers: %3d\n",
        basename($presetDir),
        $presetProductCount,
        $presetBatchCount,
        count($suppliers)
    );
}

echo "\nDONE — total products: {$totalProducts} | total batches: {$totalBatches}\n";
