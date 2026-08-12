<?php
// fix_spray_dryer.php
$DATA_JSON = __DIR__ . '/data/products.json';
$DATA_JS = __DIR__ . '/data/products.js';

if (file_exists($DATA_JSON)) {
    $products = json_decode(file_get_contents($DATA_JSON), true);
    $updated = false;
    foreach ($products as &$p) {
        if (isset($p['subCategory']) && $p['subCategory'] === 'Spray Dryer') {
            if (isset($p['desc']) && strpos($p['desc'], '·') !== false) {
                $items = array_filter(array_map('trim', explode('·', $p['desc'])));
                if (count($items) > 1) {
                    $html = '<ul class="list-disc pl-4 space-y-1">';
                    foreach ($items as $item) {
                        $html .= '<li>' . htmlspecialchars($item) . '</li>';
                    }
                    $html .= '</ul>';
                    $p['desc'] = $html;
                    $updated = true;
                    echo "Updated: " . htmlspecialchars($p['name']) . "<br>";
                }
            }
        }
    }
    
    if ($updated) {
        $jsonString = json_encode($products, JSON_PRETTY_PRINT);
        file_put_contents($DATA_JSON, $jsonString);
        file_put_contents($DATA_JS, "window.CATALOG_PRODUCTS = " . $jsonString . ";");
        echo "<b>Berhasil! Deskripsi Spray Dryer di data/products.json telah diperbarui!</b>";
    } else {
        echo "Tidak ada deskripsi yang perlu diperbarui atau produk Spray Dryer tidak ditemukan/sudah benar.";
    }
} else {
    echo "data/products.json tidak ditemukan! Script hanya bekerja jika data/products.json ada.";
}
?>
