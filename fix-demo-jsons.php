<?php
/**
 * Bulk-fix all demo preset data.json files.
 * Replaces every image path with public/uploads/images/{basename.ext}
 *
 * Usage:  php fix-demo-jsons.php
 */

$baseDir = __DIR__ . '/storage/app/demo-presets';
$targetPrefix = 'public/uploads/images/';

if (!is_dir($baseDir)) {
    echo "ERROR: $baseDir not found\n";
    exit(1);
}

$presets = array_filter(glob($baseDir . '/*'), 'is_dir');
$totalFixed = 0;
$totalPresets = 0;

foreach ($presets as $presetDir) {
    $jsonPath = $presetDir . '/data.json';
    if (!file_exists($jsonPath)) {
        echo "SKIP  " . basename($presetDir) . " — no data.json\n";
        continue;
    }

    $json = file_get_contents($jsonPath);
    $data = json_decode($json, true);
    if (!$data) {
        echo "SKIP  " . basename($presetDir) . " — invalid JSON\n";
        continue;
    }

    $fixCount = 0;

    // Simple path fixer
    $fix = function (string $path) use ($targetPrefix): string {
        return $targetPrefix . basename($path);
    };

    // --- Categories ---
    if (!empty($data['categories'])) {
        foreach ($data['categories'] as $i => $c) {
            if (!empty($c['image'])) {
                $new = $fix($c['image']);
                if ($new !== $c['image']) {
                    $data['categories'][$i]['image'] = $new;
                    $fixCount++;
                }
            }
        }
    }

    // --- Products (image + gallery_images) ---
    if (!empty($data['products'])) {
        foreach ($data['products'] as $i => $p) {
            if (!empty($p['image'])) {
                $new = $fix($p['image']);
                if ($new !== $p['image']) {
                    $data['products'][$i]['image'] = $new;
                    $fixCount++;
                }
            }
            if (!empty($p['gallery_images'])) {
                foreach ($p['gallery_images'] as $j => $gi) {
                    if (!empty($gi)) {
                        $new = $fix($gi);
                        if ($new !== $gi) {
                            $data['products'][$i]['gallery_images'][$j] = $new;
                            $fixCount++;
                        }
                    }
                }
            }
        }
    }

    // --- Banners ---
    if (!empty($data['banners'])) {
        foreach ($data['banners'] as $i => $b) {
            if (!empty($b['image'])) {
                $new = $fix($b['image']);
                if ($new !== $b['image']) {
                    $data['banners'][$i]['image'] = $new;
                    $fixCount++;
                }
            }
        }
    }

    // --- Blogs ---
    if (!empty($data['blogs'])) {
        foreach ($data['blogs'] as $i => $b) {
            if (!empty($b['image'])) {
                $new = $fix($b['image']);
                if ($new !== $b['image']) {
                    $data['blogs'][$i]['image'] = $new;
                    $fixCount++;
                }
            }
        }
    }

    // --- Brands ---
    if (!empty($data['brands'])) {
        foreach ($data['brands'] as $i => $b) {
            if (is_array($b) && !empty($b['image'])) {
                $new = $fix($b['image']);
                if ($new !== $b['image']) {
                    $data['brands'][$i]['image'] = $new;
                    $fixCount++;
                }
            }
        }
    }

    // --- Write back ---
    if ($fixCount > 0) {
        $newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($jsonPath, $newJson . "\n");
        echo "FIXED " . basename($presetDir) . " — {$fixCount} paths\n";
        $totalPresets++;
        $totalFixed += $fixCount;
    } else {
        echo "OK    " . basename($presetDir) . " — already correct\n";
    }
}

echo "\nDone. {$totalFixed} paths fixed across {$totalPresets} presets.\n";
