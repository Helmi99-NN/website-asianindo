<?php
/**
 * Midtrans HTTP Notification / Webhook Receiver
 * CV Asianindo E-Commerce
 * 
 * Midtrans mengirimkan HTTP POST Notification ke URL ini secara otomatis
 * ketika status transaksi berubah (Pending -> Settlement / Expire / Cancel).
 */

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/midtrans_config.php';

header('Content-Type: application/json');

// 1. Tangani Test Ping / Probe dari Dashboard Midtrans (GET atau Empty POST)
if ($_SERVER['REQUEST_METHOD'] === 'GET' || empty($rawInput)) {
    http_response_code(200);
    echo json_encode(['status' => 'OK', 'message' => 'Midtrans webhook endpoint is active and ready']);
    exit;
}

$data = json_decode($rawInput, true);

// 2. Tangani Test Payload dari Dashboard Midtrans ("Test notification URL")
if (!$data || empty($data['order_id'])) {
    http_response_code(200);
    echo json_encode(['status' => 'OK', 'message' => 'Midtrans test probe acknowledged']);
    exit;
}

$orderId = $data['order_id'];
$statusCode = $data['status_code'] ?? '200';
$grossAmount = $data['gross_amount'] ?? '0';
$reqSignature = $data['signature_key'] ?? '';
$transactionStatus = $data['transaction_status'] ?? '';
$fraudStatus = $data['fraud_status'] ?? '';
$paymentType = $data['payment_type'] ?? '';
$transactionId = $data['transaction_id'] ?? '';
$pdfUrl = $data['pdf_url'] ?? '';

// Jika order_id merupakan transaksi test bawaan Midtrans dashboard
if (strpos(strtolower($orderId), 'test') !== false || empty($reqSignature)) {
    http_response_code(200);
    echo json_encode(['status' => 'OK', 'message' => 'Test notification acknowledged successfully']);
    exit;
}

// Format gross_amount ke format angka murni (tanpa desimal tak perlu)
$grossAmountFormatted = is_numeric($grossAmount) ? sprintf("%.2f", (float)$grossAmount) : $grossAmount;

// Validasi Signature Key SHA-512
// Formula Midtrans: SHA512(order_id + status_code + gross_amount + ServerKey)
$localSig1 = hash('sha512', $orderId . $statusCode . $grossAmount . MIDTRANS_SERVER_KEY);
$localSig2 = hash('sha512', $orderId . $statusCode . $grossAmountFormatted . MIDTRANS_SERVER_KEY);

if ($reqSignature !== $localSig1 && $reqSignature !== $localSig2) {
    // Jika signature tidak cocok tapi berawalan test/mock, tetap beri respon 200 agar pengetesan dashboard tidak error
    if (strpos(strtolower($orderId), 'sample') !== false || strpos(strtolower($orderId), 'mock') !== false) {
        http_response_code(200);
        echo json_encode(['status' => 'OK', 'message' => 'Sample test signature acknowledged']);
        exit;
    }
    http_response_code(200); // Selalu kirim 200 untuk mencegah retrying Midtrans
    echo json_encode(['status' => 'IGNORED', 'message' => 'Invalid signature key']);
    exit;
}

try {
    $pdo = getDB();

    // 1. Cari data order berdasarkan order_number
    $stmt = $pdo->prepare("SELECT id, status, total FROM orders WHERE order_number = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        // Jangan kembalikan 404 ke Midtrans (Midtrans rekomendasi return 200 untuk pesanan yang tidak dikenal)
        http_response_code(200);
        echo json_encode(['status' => 'OK', 'message' => 'Order not found in database or dummy test transaction']);
        exit;
    }

    $dbOrderId = $order['id'];
    $newOrderStatus = $order['status'];
    $newPaymentStatus = 'pending';

    // Ambil nomor VA jika ada (BCA, BNI, BRI, Permata)
    $vaNumber = null;
    if (!empty($data['va_numbers'][0]['va_number'])) {
        $vaNumber = $data['va_numbers'][0]['va_number'];
    } elseif (!empty($data['permata_va_number'])) {
        $vaNumber = $data['permata_va_number'];
    }

    // 2. Evaluasi status transaksi dari Midtrans
    if ($transactionStatus === 'capture') {
        if ($fraudStatus === 'challenge') {
            $newOrderStatus = 'pending_payment';
            $newPaymentStatus = 'challenge';
        } else if ($fraudStatus === 'accept') {
            $newOrderStatus = 'payment_verified';
            $newPaymentStatus = 'verified';
        }
    } else if ($transactionStatus === 'settlement') {
        $newOrderStatus = 'payment_verified';
        $newPaymentStatus = 'verified';
    } else if ($transactionStatus === 'pending') {
        $newOrderStatus = 'pending_payment';
        $newPaymentStatus = 'pending';
    } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
        $newOrderStatus = 'cancelled';
        $newPaymentStatus = 'cancelled';
    }

    // 3. Update database order & payments
    $stmtUpdateOrd = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmtUpdateOrd->execute([$newOrderStatus, $dbOrderId]);

    // Cek record payment
    $stmtPay = $pdo->prepare("SELECT id FROM payments WHERE order_id = ? LIMIT 1");
    $stmtPay->execute([$dbOrderId]);
    $pay = $stmtPay->fetch(PDO::FETCH_ASSOC);

    if ($pay) {
        $stmtUpdatePay = $pdo->prepare("
            UPDATE payments 
            SET status = ?, 
                midtrans_transaction_id = ?, 
                midtrans_payment_type = ?, 
                midtrans_pdf_url = ?,
                account_number = COALESCE(?, account_number),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmtUpdatePay->execute([
            $newPaymentStatus,
            $transactionId,
            $paymentType,
            $pdfUrl,
            $vaNumber,
            $pay['id']
        ]);
    } else {
        $stmtInsPay = $pdo->prepare("
            INSERT INTO payments (order_id, payment_gateway, amount, status, midtrans_transaction_id, midtrans_payment_type, midtrans_pdf_url, account_number, created_at)
            VALUES (?, 'midtrans', ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtInsPay->execute([
            $dbOrderId,
            (int)$grossAmount,
            $newPaymentStatus,
            $transactionId,
            $paymentType,
            $pdfUrl,
            $vaNumber
        ]);
    }

    http_response_code(200);
    echo json_encode(['status' => 'OK', 'message' => 'Notification processed successfully']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'ERROR', 'message' => 'Database error: ' . $e->getMessage()]);
}
