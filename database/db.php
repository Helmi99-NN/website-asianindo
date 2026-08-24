<?php
/**
 * Database Connection & Core Helpers for CV Asianindo E-Commerce
 * Uses PDO MySQL with utf8mb4 charset
 */

// Cek jika ada config.php terpisah agar tidak tertimpa saat update repository
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Konfigurasi Database (Sesuaikan dengan kredensial cPanel / Hostinger)
$dbHost = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1');
$dbName = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'u255210891_web_asianindo');
$dbUser = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'u255210891_web_asianindo');
$dbPass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');

// Konfigurasi Rekening Pembayaran Resmi CV Asianindo
if (!defined('COMPANY_BANK_NAME')) define('COMPANY_BANK_NAME', 'Bank BCA');
if (!defined('COMPANY_BANK_ACCOUNT')) define('COMPANY_BANK_ACCOUNT', '6670747997');
if (!defined('COMPANY_BANK_HOLDER')) define('COMPANY_BANK_HOLDER', 'Iman Anjani Buchory');
if (!defined('COMPANY_WA_NUMBER')) define('COMPANY_WA_NUMBER', '6285335850517');

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 5,
];

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    try {
        // Coba alternatif host (localhost jika 127.0.0.1 gagal, atau sebaliknya)
        $altHost = ($dbHost === '127.0.0.1') ? 'localhost' : '127.0.0.1';
        $pdo = new PDO("mysql:host={$altHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, $options);
    } catch (PDOException $e2) {
        if (!defined('CLI_MODE')) {
            http_response_code(200); // Kembalikan 200 dengan status error agar terbaca jelas di JSON frontend
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'status' => 'error',
                'error' => 'Koneksi database gagal: ' . $e2->getMessage()
            ]);
            exit;
        }
    }
}

/**
 * Singleton / Getter untuk PDO Instance
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Generate nomor pesanan unik format ASN-YYYYMMDD-XXXX
 */
function generateOrderNumber($db = null) {
    if (!$db) $db = getDB();
    $prefix = 'ASN-' . date('Ymd') . '-';
    $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
    return $prefix . $random;
}

/**
 * Format nominal Rupiah
 */
function formatRupiah($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Helper validasi session customer login
 */
function requireCustomerLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['customer_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Silakan login terlebih dahulu untuk melanjutkan transaksi.',
            'require_login' => true
        ]);
        exit;
    }
    return (int)$_SESSION['customer_id'];
}

/**
 * Estimasi Ongkir Kargo (Indah Kargo / Cargo Logistik Mesin)
 * Berdasarkan berat total (kg) dan provinsi tujuan
 */
function calculateShippingCost($weightGrams, $province) {
    $weightKg = max(1, ceil($weightGrams / 1000));
    $province = strtolower(trim($province));

    // Default rate per kg & minimum cost
    $pricePerKg = 4000;
    $minCost = 50000; // Minimum 50rb untuk kargo mesin

    if (strpos($province, 'jawa timur') !== false || strpos($province, 'jatim') !== false) {
        $pricePerKg = 2500;
        $minCost = 35000;
    } elseif (strpos($province, 'jawa') !== false || strpos($province, 'jakarta') !== false || strpos($province, 'banten') !== false || strpos($province, 'yogyakarta') !== false) {
        $pricePerKg = 3500;
        $minCost = 45000;
    } elseif (strpos($province, 'bali') !== false || strpos($province, 'nusa tenggara') !== false || strpos($province, 'ntb') !== false || strpos($province, 'ntt') !== false) {
        $pricePerKg = 5000;
        $minCost = 75000;
    } elseif (strpos($province, 'sumatera') !== false || strpos($province, 'lampung') !== false || strpos($province, 'riau') !== false || strpos($province, 'aceh') !== false) {
        $pricePerKg = 6000;
        $minCost = 85000;
    } elseif (strpos($province, 'kalimantan') !== false) {
        $pricePerKg = 7500;
        $minCost = 100000;
    } elseif (strpos($province, 'sulawesi') !== false) {
        $pricePerKg = 8000;
        $minCost = 110000;
    } elseif (strpos($province, 'maluku') !== false || strpos($province, 'papua') !== false) {
        $pricePerKg = 12000;
        $minCost = 150000;
    }

    $calculated = $weightKg * $pricePerKg;
    $finalCost = max($minCost, $calculated);

    return [
        'weight_kg' => $weightKg,
        'price_per_kg' => $pricePerKg,
        'cost' => $finalCost,
        'cost_display' => formatRupiah($finalCost),
        'service_name' => 'Indah Kargo / Cargo Logistik Mesin'
    ];
}
