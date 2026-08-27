<?php
/**
 * Duitku Payment Gateway API Handler & Helper
 * CV Asianindo E-Commerce
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/duitku_config.php';

$pdo = getDB();

function getApiInput() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

/**
 * Buat transaksi Duitku (Inquiry v2)
 * @param array $orderData Data order dari database
 * @param string $channelCode Kode metode pembayaran Duitku (misal: 'BC', 'M2', 'NQ', 'VC')
 * @param array $customerData Data customer (nama, email, phone)
 * @return array Hasil respons Duitku
 */
function createDuitkuTransaction($orderData, $channelCode, $customerData = []) {
    global $pdo;

    $merchantCode = DUITKU_MERCHANT_CODE;
    $apiKey = DUITKU_API_KEY;
    $merchantOrderId = $orderData['order_number'];
    
    // Tagihan yang harus dibayar saat ini (bisa full atau DP awal)
    $paymentAmount = (int)($orderData['bill_amount'] ?? $orderData['amount'] ?? $orderData['total']);
    
    // Hitung biaya layanan Duitku (dibebankan ke pembeli)
    $paymentFee = calculateDuitkuFee($channelCode, $paymentAmount);
    $totalCharge = $paymentAmount + $paymentFee;

    $productDetails = 'Pesanan Mesin Asianindo #' . $merchantOrderId;
    $email = $customerData['email'] ?? $orderData['customer_email'] ?? 'pembeli@asianindomachine.com';
    $phoneNumber = $customerData['phone'] ?? $orderData['shipping_phone'] ?? '081234567890';
    $customerVaName = substr($customerData['name'] ?? $orderData['shipping_name'] ?? 'Pelanggan Asianindo', 0, 30);
    $callbackUrl = DUITKU_CALLBACK_URL;
    $returnUrl = DUITKU_RETURN_URL . '?order_number=' . urlencode($merchantOrderId);
    $expiryPeriod = DUITKU_EXPIRY_PERIOD; // 1440 menit (24 jam)

    // Formula Signature Request Duitku: MD5(merchantCode + merchantOrderId + paymentAmount + apiKey)
    $signature = md5($merchantCode . $merchantOrderId . $totalCharge . $apiKey);

    $payload = [
        'merchantCode'     => $merchantCode,
        'paymentAmount'    => $totalCharge,
        'paymentMethod'    => $channelCode,
        'merchantOrderId'  => $merchantOrderId,
        'productDetails'   => $productDetails,
        'additionalParam'  => json_encode(['order_id' => $orderData['id'] ?? 0, 'fee' => $paymentFee]),
        'merchantUserInfo' => $customerVaName,
        'customerVaName'   => $customerVaName,
        'email'            => $email,
        'phoneNumber'      => $phoneNumber,
        'itemDetails'      => [
            [
                'name'     => substr($productDetails, 0, 50),
                'price'    => $paymentAmount,
                'quantity' => 1
            ],
            [
                'name'     => 'Biaya Layanan Pembayaran (Duitku)',
                'price'    => $paymentFee,
                'quantity' => 1
            ]
        ],
        'callbackUrl'      => $callbackUrl,
        'returnUrl'        => $returnUrl,
        'signature'        => $signature,
        'expiryPeriod'     => $expiryPeriod
    ];

    $inquiryUrl = DUITKU_BASE_URL . '/api/merchant/v2/inquiry';

    $ch = curl_init($inquiryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($payload))
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'error'   => 'Koneksi Duitku Gateway gagal: ' . $curlError
        ];
    }

    $resData = json_decode($response, true);

    if ($httpCode === 200 && is_array($resData) && ($resData['statusCode'] ?? '') === '00') {
        $reference = $resData['reference'] ?? '';
        $paymentUrl = $resData['paymentUrl'] ?? '';
        $vaNumber = $resData['vaNumber'] ?? '';
        $qrString = $resData['qrString'] ?? '';

        // Update database payments record
        try {
            $channels = getDuitkuPaymentChannels();
            $channelName = $channels[$channelCode]['name'] ?? $channelCode;

            // Ensure columns exist in payments table
            ensureDuitkuColumnsExist();

            $orderId = (int)($orderData['id'] ?? 0);
            if ($orderId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE payments SET 
                        bank_name = ?, 
                        account_number = ?, 
                        account_name = ?, 
                        amount = ?, 
                        payment_gateway = 'duitku',
                        payment_method_code = ?,
                        payment_fee = ?,
                        duitku_reference = ?,
                        duitku_va_number = ?,
                        duitku_payment_url = ?,
                        duitku_qr_string = ?
                    WHERE order_id = ?
                ");
                $stmt->execute([
                    $channelName,
                    $vaNumber ?: ($reference ?: '-'),
                    'Duitku Gateway (' . $channelName . ')',
                    $totalCharge,
                    $channelCode,
                    $paymentFee,
                    $reference,
                    $vaNumber,
                    $paymentUrl,
                    $qrString,
                    $orderId
                ]);
            }
        } catch (Throwable $dbErr) {
            // Log but don't fail transaction response
        }

        return [
            'success'      => true,
            'status_code'  => '00',
            'order_number' => $merchantOrderId,
            'channel_code' => $channelCode,
            'channel_name' => $channelName ?? $channelCode,
            'reference'    => $reference,
            'payment_url'  => $paymentUrl,
            'va_number'    => $vaNumber,
            'qr_string'    => $qrString,
            'amount'       => $paymentAmount,
            'fee'          => $paymentFee,
            'total_charge' => $totalCharge
        ];
    } else {
        $msg = $resData['statusMessage'] ?? ($resData['message'] ?? 'Gagal membuat transaksi di Duitku');
        return [
            'success'     => false,
            'error'       => $msg,
            'raw_response'=> $resData
        ];
    }
}

/**
 * Cek status transaksi langsung ke Duitku Gateway
 */
function checkDuitkuStatus($merchantOrderId) {
    global $pdo;

    $merchantCode = DUITKU_MERCHANT_CODE;
    $apiKey = DUITKU_API_KEY;
    $signature = md5($merchantCode . $merchantOrderId . $apiKey);

    $payload = [
        'merchantCode'    => $merchantCode,
        'merchantOrderId' => $merchantOrderId,
        'signature'       => $signature
    ];

    $url = DUITKU_BASE_URL . '/api/merchant/transactionStatus';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);

    $resData = json_decode($response, true);
    if (is_array($resData)) {
        $statusCode = $resData['statusCode'] ?? '';
        // '00' = SUCCESS
        if ($statusCode === '00') {
            // Update order and payments to verified
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = 'payment_verified', updated_at = NOW() WHERE order_number = ?");
                $stmt->execute([$merchantOrderId]);

                $stmt2 = $pdo->prepare("
                    UPDATE payments p 
                    JOIN orders o ON p.order_id = o.id 
                    SET p.status = 'verified', p.verified_at = NOW() 
                    WHERE o.order_number = ?
                ");
                $stmt2->execute([$merchantOrderId]);
            } catch (Throwable $e) {}
        }
        return [
            'success'     => true,
            'is_paid'     => ($statusCode === '00'),
            'status_code' => $statusCode,
            'message'     => $resData['statusMessage'] ?? '',
            'data'        => $resData
        ];
    }

    return ['success' => false, 'error' => 'Gagal memeriksa status ke Duitku'];
}

/**
 * Helper untuk memastikan kolom-kolom Duitku tersedia di database
 */
function ensureDuitkuColumnsExist() {
    global $pdo;
    static $checked = false;
    if ($checked) return;

    try {
        $cols = [
            "ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_gateway VARCHAR(30) DEFAULT 'manual_transfer'",
            "ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_method_code VARCHAR(30) NULL",
            "ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_fee BIGINT DEFAULT 0",
            "ALTER TABLE payments ADD COLUMN IF NOT EXISTS duitku_reference VARCHAR(100) NULL",
            "ALTER TABLE payments ADD COLUMN IF NOT EXISTS duitku_va_number VARCHAR(100) NULL",
            "ALTER TABLE payments ADD COLUMN IF NOT EXISTS duitku_payment_url TEXT NULL",
            "ALTER TABLE payments ADD COLUMN IF NOT EXISTS duitku_qr_string TEXT NULL"
        ];
        foreach ($cols as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // Fallback untuk MySQL versi lama yang tidak support IF NOT EXISTS di ADD COLUMN
            }
        }
        $checked = true;
    } catch (Throwable $e) {}
}

// ======================== API ROUTING ========================
$action = $_GET['action'] ?? '';

if ($action === 'get_channels') {
    $amount = (int)($_GET['amount'] ?? 0);
    $allChannels = getDuitkuPaymentChannels();
    $result = [];

    foreach ($allChannels as $code => $ch) {
        $calculatedFee = calculateDuitkuFee($code, $amount);
        $totalWithFee = $amount + $calculatedFee;
        
        $feeLabel = $ch['fee_label'];
        if ($ch['fee_type'] === 'percent' && $amount > 0) {
            $feeLabel = $ch['fee_label'] . ' (' . formatRupiah($calculatedFee) . ')';
        } elseif ($ch['fee_type'] === 'combo' && $amount > 0) {
            $feeLabel = $ch['fee_label'] . ' (' . formatRupiah($calculatedFee) . ')';
        }

        $result[] = array_merge($ch, [
            'calculated_fee'       => $calculatedFee,
            'calculated_fee_formatted' => formatRupiah($calculatedFee),
            'fee_label_display'    => $feeLabel,
            'total_with_fee'       => $totalWithFee,
            'total_with_fee_formatted' => formatRupiah($totalWithFee)
        ]);
    }

    echo json_encode([
        'success'     => true,
        'environment' => DUITKU_ENVIRONMENT,
        'amount'      => $amount,
        'channels'    => $result
    ]);
    exit;
}

if ($action === 'calculate_fee') {
    $channelCode = trim($_GET['channel'] ?? '');
    $amount = (int)($_GET['amount'] ?? 0);
    $fee = calculateDuitkuFee($channelCode, $amount);
    $total = $amount + $fee;

    echo json_encode([
        'success'     => true,
        'channel'     => $channelCode,
        'amount'      => $amount,
        'fee'         => $fee,
        'fee_formatted' => formatRupiah($fee),
        'total'       => $total,
        'total_formatted' => formatRupiah($total)
    ]);
    exit;
}

if ($action === 'check_status') {
    $orderNumber = trim($_GET['order_number'] ?? '');
    if (!$orderNumber) {
        echo json_encode(['success' => false, 'error' => 'Nomor pesanan wajib disertakan']);
        exit;
    }

    $statusRes = checkDuitkuStatus($orderNumber);
    echo json_encode($statusRes);
    exit;
}

if ($action === 'request_payment') {
    $input = getApiInput();
    $orderNumber = trim($input['order_number'] ?? '');
    $channelCode = trim($input['payment_method'] ?? 'BC');

    if (!$orderNumber) {
        echo json_encode(['success' => false, 'error' => 'Nomor pesanan wajib disertakan']);
        exit;
    }

    // Ambil order dari database
    $stmt = $pdo->prepare("
        SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
               p.amount AS bill_amount
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        LEFT JOIN payments p ON p.order_id = o.id
        WHERE o.order_number = ?
    ");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
        exit;
    }

    $customerData = [
        'name'  => $order['shipping_name'] ?: $order['customer_name'],
        'email' => $order['customer_email'],
        'phone' => $order['shipping_phone'] ?: $order['customer_phone']
    ];

    $res = createDuitkuTransaction($order, $channelCode, $customerData);
    echo json_encode($res);
    exit;
}

// Fallback response
echo json_encode([
    'success' => false,
    'error'   => 'Action tidak valid'
]);
