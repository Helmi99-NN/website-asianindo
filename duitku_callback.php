<?php
/**
 * Duitku Payment Gateway Webhook / Callback Handler
 * CV Asianindo E-Commerce
 * 
 * Menerima notifikasi status transaksi otomatis secara real-time dari server Duitku.
 * Dokumentasi Callback: https://docs.duitku.com
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/duitku_config.php';

$pdo = getDB();

// Tangkap POST data dari Duitku
$merchantCode = $_POST['merchantCode'] ?? '';
$amount = $_POST['amount'] ?? '';
$merchantOrderId = $_POST['merchantOrderId'] ?? '';
$productDetails = $_POST['productDetails'] ?? '';
$additionalParam = $_POST['additionalParam'] ?? '';
$paymentMethod = $_POST['paymentCode'] ?? '';
$resultCode = $_POST['resultCode'] ?? '';
$merchantUserId = $_POST['merchantUserId'] ?? '';
$reference = $_POST['reference'] ?? '';
$signature = $_POST['signature'] ?? '';
$publisherOrderId = $_POST['publisherOrderId'] ?? '';

// Logging callback untuk audit dan debug
$logDir = __DIR__ . '/data';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/duitku_callbacks.log';
$logEntry = date('Y-m-d H:i:s') . " | Order: {$merchantOrderId} | Amount: {$amount} | ResultCode: {$resultCode} | Ref: {$reference} | IP: " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

// 1. Validasi Keberadaan Data Utama
if (empty($merchantCode) || empty($amount) || empty($merchantOrderId) || empty($signature)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
    exit;
}

// 2. Validasi Signature Keamanan Duitku
// Formula Callback Signature: MD5(merchantCode + amount + merchantOrderId + apiKey)
$apiKey = DUITKU_API_KEY;
$calculatedSignature = md5($merchantCode . $amount . $merchantOrderId . $apiKey);

if ($calculatedSignature !== $signature) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Signature tidak valid (Bad Signature)']);
    exit;
}

// 3. Proses Status Transaksi
// ResultCode '00' menandakan transaksi BERHASIL DIBAYAR (SUCCESS)
if ($resultCode === '00') {
    try {
        $pdo->beginTransaction();

        // Update status Order menjadi 'payment_verified'
        $stmtOrder = $pdo->prepare("
            UPDATE orders SET 
                status = 'payment_verified',
                updated_at = NOW()
            WHERE order_number = ?
        ");
        $stmtOrder->execute([$merchantOrderId]);

        // Update status Payments menjadi 'verified'
        $stmtPay = $pdo->prepare("
            UPDATE payments p 
            JOIN orders o ON p.order_id = o.id 
            SET p.status = 'verified', 
                p.verified_at = NOW(),
                p.duitku_reference = ?,
                p.admin_notes = ?
            WHERE o.order_number = ?
        ");
        $adminNotes = 'Pembayaran terverifikasi otomatis via Duitku Gateway (' . $paymentMethod . ') | Ref: ' . $reference;
        $stmtPay->execute([$reference, $adminNotes, $merchantOrderId]);

        // Update shipment status menjadi 'preparing' jika ada
        $stmtShip = $pdo->prepare("
            UPDATE shipments s 
            JOIN orders o ON s.order_id = o.id 
            SET s.status = 'preparing',
                s.updated_at = NOW()
            WHERE o.order_number = ?
        ");
        $stmtShip->execute([$merchantOrderId]);

        $pdo->commit();

        // Response 'OK' atau JSON status success ke Duitku
        http_response_code(200);
        header('Content-Type: text/plain');
        echo "OK";
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
} else {
    // Jika transaksi gagal / expired / dibatalkan di sisi Duitku
    http_response_code(200);
    header('Content-Type: text/plain');
    echo "OK"; // Tetap kembalikan HTTP 200 agar Duitku tidak retry berulang
    exit;
}
