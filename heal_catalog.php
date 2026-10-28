<?php
/**
 * Auto-Heal & Verify Product Images
 * CV Asianindo E-Commerce
 */
header('Content-Type: application/json');

$DATA_JSON = __DIR__ . '/data/products.json';
$DATA_JS   = __DIR__ . '/data/products.js';
$DEFAULT_JSON = __DIR__ . '/default_products.json';

$products = [];
if (file_exists($DATA_JSON)) {
    $products = json_decode(file_get_contents($DATA_JSON), true) ?? [];
}
if (empty($products) && file_exists($DEFAULT_JSON)) {
    $products = json_decode(file_get_contents($DEFAULT_JSON), true) ?? [];
}

if (empty($products)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada file produk yang ditemukan.'
    ]);
    exit;
}

$repaired = [];
$totalCount = count($products);

foreach ($products as $idx => &$p) {
    if (!isset($p['id'])) continue;
    $id = (string)$p['id'];
    $wasChanged = false;
    $oldImages = $p['images'] ?? [];

    if (isset($p['images']) && is_array($p['images'])) {
        foreach ($p['images'] as $imgIdx => $img) {
            if (is_string($img)) {
                $isBrokenMedia = (strpos($img, 'media_6ac') !== false);
                $fileMissing = (strpos($img, 'images/media_') === 0 && !file_exists(__DIR__ . '/' . $img));

                if ($isBrokenMedia || $fileMissing) {
                    $originalCandidate = 'images/products/' . $id . '_' . $imgIdx . '.webp';
                    $fallbackCover = 'images/products/' . $id . '_0.webp';
                    
                    if (file_exists(__DIR__ . '/' . $originalCandidate)) {
                        $p['images'][$imgIdx] = $originalCandidate;
                    } elseif (file_exists(__DIR__ . '/' . $fallbackCover)) {
                        $p['images'][$imgIdx] = $fallbackCover;
                    } else {
                        $p['images'][$imgIdx] = 'images/placeholder.webp';
                    }
                    $wasChanged = true;
                }
            }
        }
    } elseif (!isset($p['images']) || empty($p['images'])) {
        $fallbackCover = 'images/products/' . $id . '_0.webp';
        $p['images'] = [file_exists(__DIR__ . '/' . $fallbackCover) ? $fallbackCover : 'images/placeholder.webp'];
        $wasChanged = true;
    }

    // Video preservation: preserve existing video configuration set by admin
    // Promo Image Healing: default to leftmost cover image
    if ((!isset($p['promo_image']) || trim($p['promo_image']) === '') && !empty($p['images']) && is_array($p['images'])) {
        $p['promo_image'] = $p['images'][0];
        $wasChanged = true;
    }

    // Capacity Healing: Fix truncated capacities (e.g. '1' -> '1,5 kg', '3' -> '3,5 kg')
    if (isset($p['capacity'])) {
        $capTrim = trim($p['capacity']);
        $pName = $p['name'] ?? '';
        if ($capTrim === '1' && (stripos($pName, '1,5') !== false || stripos($pName, '1.5') !== false)) {
            $p['capacity'] = '1,5 kg';
            $p['capacitySize'] = 'small';
            $wasChanged = true;
        } elseif ($capTrim === '3' && (stripos($pName, '3,5') !== false || stripos($pName, '3.5') !== false)) {
            $p['capacity'] = '3,5 kg';
            $p['capacitySize'] = 'small';
            $wasChanged = true;
        }
    }

    if ($wasChanged) {
        $repaired[] = [
            'id' => $id,
            'name' => $p['name'] ?? '',
            'before' => $oldImages,
            'after' => $p['images'],
            'video' => $p['video'] ?? ''
        ];
    }
}
unset($p);

// Write back changes if any
if (!empty($repaired)) {
    $dir = dirname($DATA_JSON);
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $jsonString = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($DATA_JSON, $jsonString);
    file_put_contents($DATA_JS, "window.CATALOG_PRODUCTS = " . $jsonString . ";");
}

$jsonString = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents(__DIR__ . '/default_products.js', "window.CATALOG_PRODUCTS = " . $jsonString . ";");

echo json_encode([
    'success' => true,
    'total_products' => $totalCount,
    'repaired_count' => count($repaired),
    'repaired_items' => $repaired,
    'message' => count($repaired) > 0 
        ? count($repaired) . ' produk berhasil dipulihkan gambarnya.' 
        : 'Semua foto produk sudah dalam kondisi baik.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
