<?php
/**
 * sync_products.php
 * 
 * Sinkronisasi semua produk dari default_products.js ke data/products.json
 * Produk yang sudah ada di data/products.json akan di-merge (data CMS menang),
 * produk yang belum ada akan ditambahkan.
 * 
 * PENTING: Jalankan sekali saja melalui browser, lalu hapus file ini.
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔄 Sinkronisasi Produk</h2>";

$defaultJsFile = __DIR__ . '/default_products.js';
$dataJsonFile = __DIR__ . '/data/products.json';
$dataJsFile = __DIR__ . '/data/products.js';

// 1. Parse default_products.js
if (!file_exists($defaultJsFile)) {
    echo "<p style='color:red'>❌ default_products.js tidak ditemukan!</p>";
    exit;
}

$jsContent = file_get_contents($defaultJsFile);
// Extract JSON array from: window.CATALOG_PRODUCTS = [...];
$startPos = strpos($jsContent, '[');
$endPos = strrpos($jsContent, ']');
if ($startPos === false || $endPos === false) {
    echo "<p style='color:red'>❌ Gagal parsing default_products.js!</p>";
    exit;
}
$jsonStr = substr($jsContent, $startPos, $endPos - $startPos + 1);
$defaultProducts = json_decode($jsonStr, true);

if (!is_array($defaultProducts)) {
    echo "<p style='color:red'>❌ Gagal decode JSON dari default_products.js! Error: " . json_last_error_msg() . "</p>";
    exit;
}

echo "<p>📦 Ditemukan <strong>" . count($defaultProducts) . "</strong> produk di default_products.js</p>";

// 2. Load existing data/products.json (CMS data)
$cmsProducts = [];
if (file_exists($dataJsonFile)) {
    $cmsProducts = json_decode(file_get_contents($dataJsonFile), true) ?? [];
}
echo "<p>📋 Ditemukan <strong>" . count($cmsProducts) . "</strong> produk di data/products.json (CMS)</p>";

// 3. Build index of CMS products by ID for fast lookup
$cmsIndex = [];
foreach ($cmsProducts as $i => $p) {
    if (isset($p['id'])) {
        $cmsIndex[$p['id']] = $i;
    }
}

// 4. Merge: for each default product, if it exists in CMS, merge (CMS wins for non-empty fields).
//    If it doesn't exist in CMS, add it.
$added = 0;
$updated = 0;

foreach ($defaultProducts as $defProduct) {
    $id = $defProduct['id'] ?? null;
    if (!$id) continue;
    
    if (isset($cmsIndex[$id])) {
        // Product exists in CMS - merge missing fields from default
        $cmsIdx = $cmsIndex[$id];
        $existing = $cmsProducts[$cmsIdx];
        
        $changed = false;
        foreach ($defProduct as $key => $val) {
            // Only fill in fields that are missing or empty in CMS
            if (!isset($existing[$key]) || $existing[$key] === '' || $existing[$key] === null) {
                $existing[$key] = $val;
                $changed = true;
            }
        }
        
        if ($changed) {
            $cmsProducts[$cmsIdx] = $existing;
            $updated++;
        }
    } else {
        // Product doesn't exist in CMS - add it
        $cmsProducts[] = $defProduct;
        $added++;
    }
}

echo "<p>➕ <strong>$added</strong> produk baru ditambahkan ke CMS</p>";
echo "<p>🔧 <strong>$updated</strong> produk yang ada di-update (field kosong diisi)</p>";
echo "<p>📊 Total produk sekarang: <strong>" . count($cmsProducts) . "</strong></p>";

// 5. Write back to data/products.json and data/products.js
$dir = dirname($dataJsonFile);
if (!is_dir($dir)) mkdir($dir, 0777, true);

$jsonOutput = json_encode($cmsProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($dataJsonFile, $jsonOutput);
file_put_contents($dataJsFile, "window.CATALOG_PRODUCTS = " . $jsonOutput . ";");

echo "<hr>";
echo "<h3 style='color: green'>✅ Sinkronisasi selesai!</h3>";
echo "<p>Semua produk dari <code>default_products.js</code> sekarang ada di CMS (<code>data/products.json</code>).</p>";
echo "<p>Deskripsi produk sekarang terdeteksi di CMS dan bisa diedit langsung.</p>";
echo "<p><strong>⚠️ PENTING:</strong> Hapus file <code>sync_products.php</code> ini setelah selesai demi keamanan.</p>";
echo "<p><a href='admin/index.php'>→ Buka CMS Admin</a></p>";
?>
