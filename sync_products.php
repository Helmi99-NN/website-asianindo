<?php
/**
 * sync_products.php
 * 
 * Sinkronisasi semua produk dari default_products.js ke data/products.json
 * dan memecah semua poin yang menyamping menjadi berderet ke bawah (vertikal).
 * 
 * PENTING: Jalankan sekali saja melalui browser, lalu hapus file ini.
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔄 Sinkronisasi Produk & Pemisahan Poin Vertikal</h2>";

function cleanDescHtml($text) {
    if (!$text) return '';
    $str = $text;
    if (strpos($str, '<li>') !== false) {
        $str = preg_replace('/<ul[^>]*>/i', '', $str);
        $str = preg_replace('/<\/ul>/i', '', $str);
        $parts = explode('</li>', $str);
        $items = [];
        foreach ($parts as $part) {
            $cleaned = trim(strip_tags($part));
            if ($cleaned) $items[] = $cleaned;
        }
        $str = implode("\n", $items);
    } else if (strpos($str, '<br') !== false) {
        $str = preg_replace('/<br\s*\/?>/i', "\n", $str);
    }

    $lines = explode("\n", $str);
    $finalItems = [];
    foreach ($lines as $line) {
        $trimmed = trim(strip_tags($line));
        if (!$trimmed) continue;
        if (strpos($trimmed, '·') !== false) {
            $subItems = array_filter(array_map('trim', explode('·', $trimmed)));
            foreach ($subItems as $item) {
                $finalItems[] = (strpos($item, '•') === 0 || strpos($item, '·') === 0) ? $item : '• ' . $item;
            }
        } else {
            $finalItems[] = (strpos($trimmed, '•') === 0 || strpos($trimmed, '·') === 0) ? $trimmed : '• ' . $trimmed;
        }
    }
    return implode("\n", $finalItems);
}

$defaultJsFile = __DIR__ . '/default_products.js';
$dataJsonFile = __DIR__ . '/data/products.json';
$dataJsFile = __DIR__ . '/data/products.js';

// 1. Parse default_products.js
if (!file_exists($defaultJsFile)) {
    echo "<p style='color:red'>❌ default_products.js tidak ditemukan!</p>";
    exit;
}

$jsContent = file_get_contents($defaultJsFile);
$startPos = strpos($jsContent, '[');
$endPos = strrpos($jsContent, ']');
if ($startPos === false || $endPos === false) {
    echo "<p style='color:red'>❌ Gagal parsing default_products.js!</p>";
    exit;
}
$jsonStr = substr($jsContent, $startPos, $endPos - $startPos + 1);
$defaultProducts = json_decode($jsonStr, true);

if (!is_array($defaultProducts)) {
    echo "<p style='color:red'>❌ Gagal decode JSON dari default_products.js!</p>";
    exit;
}

echo "<p>📦 Ditemukan <strong>" . count($defaultProducts) . "</strong> produk di default_products.js</p>";

// 2. Load existing data/products.json
$cmsProducts = [];
if (file_exists($dataJsonFile)) {
    $cmsProducts = json_decode(file_get_contents($dataJsonFile), true) ?? [];
}

// 3. Build index
$cmsIndex = [];
foreach ($cmsProducts as $i => $p) {
    if (isset($p['id'])) $cmsIndex[$p['id']] = $i;
}

// 4. Merge & Clean
$added = 0;
$updated = 0;

foreach ($defaultProducts as $defProduct) {
    $id = $defProduct['id'] ?? null;
    if (!$id) continue;
    
    if (isset($defProduct['desc'])) {
        $defProduct['desc'] = cleanDescHtml($defProduct['desc']);
    }
    
    if (isset($cmsIndex[$id])) {
        $cmsIdx = $cmsIndex[$id];
        $existing = $cmsProducts[$cmsIdx];
        
        $changed = false;
        foreach ($defProduct as $key => $val) {
            if (!isset($existing[$key]) || $existing[$key] === '' || $existing[$key] === null) {
                $existing[$key] = $val;
                $changed = true;
            }
        }
        if (isset($existing['desc'])) {
            $cleaned = cleanDescHtml($existing['desc']);
            if ($cleaned !== $existing['desc']) {
                $existing['desc'] = $cleaned;
                $changed = true;
            }
        }
        
        if ($changed) {
            $cmsProducts[$cmsIdx] = $existing;
            $updated++;
        }
    } else {
        $cmsProducts[] = $defProduct;
        $added++;
    }
}

foreach ($cmsProducts as &$p) {
    if (isset($p['desc'])) {
        $p['desc'] = cleanDescHtml($p['desc']);
    }
}

// 5. Save
$dir = dirname($dataJsonFile);
if (!is_dir($dir)) mkdir($dir, 0777, true);

$jsonOutput = json_encode($cmsProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($dataJsonFile, $jsonOutput);
file_put_contents($dataJsFile, "window.CATALOG_PRODUCTS = " . $jsonOutput . ";");

echo "<hr>";
echo "<h3 style='color: green'>✅ Pemisahan Poin Vertikal Berhasil!</h3>";
echo "<p>Semua poin deskripsi telah dipecah secara vertikal per baris.</p>";
echo "<p>Sekarang di CMS maupun Website Katalog tampilannya sudah 100% konsisten berderet ke bawah.</p>";
echo "<p><a href='admin/index.php'>→ Buka CMS Admin</a></p>";
?>
