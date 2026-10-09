<?php
require_once __DIR__ . '/../midtrans_config.php';
require_once __DIR__ . '/../midtrans_api.php';

echo "=== CHECKING MIDTRANS STATUS & CONFIGURATION ===\n\n";

echo "1. Active Configuration:\n";
echo "   - Environment:   " . MIDTRANS_ENVIRONMENT . "\n";
echo "   - Server Key:    " . (MIDTRANS_SERVER_KEY ? substr(MIDTRANS_SERVER_KEY, 0, 10) . '...' : '(EMPTY)') . "\n";
echo "   - Client Key:    " . (MIDTRANS_CLIENT_KEY ? substr(MIDTRANS_CLIENT_KEY, 0, 10) . '...' : '(EMPTY)') . "\n";
echo "   - Snap URL:      " . MIDTRANS_SNAP_URL . "\n";
echo "   - Finish URL:    " . MIDTRANS_FINISH_URL . "\n";
echo "   - Webhook URL:   " . MIDTRANS_NOTIFICATION_URL . "\n\n";

echo "2. Testing Midtrans Snap API Token Generation:\n";
$testOrder = [
    'order_number' => 'ASN-' . date('Ymd') . '-TEST' . rand(100, 999),
    'total' => 100000,
    'subtotal' => 100000,
    'shipping_cost' => 0,
    'items' => [
        [
            'product_id' => 'TEST-1',
            'product_name' => 'Produk Test Asianindo',
            'price' => 100000,
            'quantity' => 1
        ]
    ]
];

$testCustomer = [
    'name' => 'Testing Pelanggan',
    'email' => 'customer@asianindomachine.com',
    'phone' => '08123456789'
];

$snapResult = createMidtransSnapToken($testOrder, 'BC', $testCustomer);
echo "   - Snap Token Response:\n";
print_r($snapResult);

echo "\n3. Testing Database Connection & Midtrans Columns:\n";
try {
    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbName = getenv('DB_NAME') ?: 'asianindo_db';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') ?: '';
    $testPdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);
    echo "   - DB Connection: SUCCESS\n";
    ensureMidtransColumnsExist($testPdo);
    $stmt = $testPdo->query("SHOW COLUMNS FROM payments LIKE 'midtrans_%'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   - Payments Columns: " . implode(', ', $cols) . "\n";
} catch (Throwable $e) {
    echo "   - DB Info: Database offline di lokal CLI (" . $e->getMessage() . ") - Database aktif pada runtime hosting/server.\n";
}

echo "\n4. Testing Webhook Callback Verification:\n";
$dummyOrderId = 'ASN-20261009-TEST';
$dummyGrossAmount = '104000.00';
$dummyStatusCode = '200';
$sig = hash('sha512', $dummyOrderId . $dummyStatusCode . $dummyGrossAmount . MIDTRANS_SERVER_KEY);
echo "   - Generated SHA512 Signature: " . substr($sig, 0, 20) . "...\n";
