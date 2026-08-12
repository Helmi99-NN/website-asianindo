<?php
/**
 * sync_products.php
 * 
 * Sinkronisasi semua produk dari default_products.js ke data/products.json
 * dan membersihkansemua tag HTML / kode random menjadi poin-poin bersih (•).
 * 
 * PENTING: Jalankan sekali saja melalui browser, lalu hapus file ini.
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔄 Sinkronisasi Produk & Pembersihan Deskripsi</h2>";

function cleanDescHtml($text) {
    if (!$text) return '';
    if (strpos($text, '<li>') !== false) {
        $temp = preg_replace('/<ul[^>]*>/i', '', $text);
        $temp = preg_replace('/<\/ul>/i', '', $temp);
        $parts = explode('</li>', $temp);
        $items = [];
        foreach ($parts as $part) {
            $item = trim(strip_tags($part));
            if ($item !== '') {
                $items[] = (strpos($item, '•') === 0) ? $item : '• ' . $item;
            }
        }
        return implode("\n", $items);
    }
    if (strpos($text, '·') !== false) {
        $parts = explode('·', $text);
        $items = [];
        foreach ($parts as $part) {
            $item = trim($part);
            if ($item !== '') {
                $items[] = (strpos($item, '•') === 0) ? $item : '• ' . $item;
            }
        }
        return implode("\n", $items);
    }
    if (strpos($text, '<br') !== false) {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    }
    return trim(strip_tags($text));
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

// 3. Build index of CMS products by ID
$cmsIndex = [];
foreach ($cmsProducts as $i => $p) {
    if (isset($p['id'])) {
        $cmsIndex[$p['id']] = $i;
    }
}

// 4. Merge & Clean descriptions
$added = 0;
$updated = 0;

foreach ($defaultProducts as $defProduct) {
    $id = $defProduct['id'] ?? null;
    if (!$id) continue;
    
    // Clean description
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
        // Force clean desc in CMS if it contains raw HTML code
        if (isset($existing['desc'])) {
            $cleanedDesc = cleanDescHtml($existing['desc']);
            if ($cleanedDesc !== $existing['desc']) {
                $existing['desc'] = $cleanedDesc;
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

// Also clean any CMS products that weren't in defaultProducts
foreach ($cmsProducts as &$p) {
    if (isset($p['desc'])) {
        $p['desc'] = cleanDescHtml($p['desc']);
    }
}

echo "<p>➕ <strong>$added</strong> produk baru ditambahkan ke CMS</p>";
echo "<p>🔧 <strong>$updated</strong> produk di-update & dibersihkan dari kode HTML</p>";
echo "<p>📊 Total produk sekarang: <strong>" . count($cmsProducts) . "</strong></p>";

// 5. Write back to data/products.json and data/products.js
$dir = dirname($dataJsonFile);
if (!is_dir($dir)) mkdir($dir, 0777, true);

$jsonOutput = json_encode($cmsProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($dataJsonFile, $jsonOutput);
file_put_contents($dataJsFile, "window.CATALOG_PRODUCTS = " . $jsonOutput . ";");

echo "<hr>";
echo "<h3 style='color: green'>✅ Sinkronisasi & Pembersihan Kode Berhasil!</h3>";
echo "<p>Semua kode HTML (seperti <code>&lt;ul&gt;</code>, <code>&lt;li&gt;</code>) telah dibersihkan menjadi teks poin-poin rapi (<code>•</code>).</p>";
echo "<p>Sekarang di CMS maupun Website tampilan deskripsi sudah bersih tanpa kode-kode random.</p>";
echo "<p><a href='admin/index.php'>→ Buka CMS Admin</a></p>";
?>
