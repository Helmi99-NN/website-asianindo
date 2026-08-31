<?php
/**
 * Midtrans Payment Gateway Integration API
 * CV Asianindo E-Commerce
 */

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/midtrans_config.php';

// Auto-migrate database table columns for Midtrans
function ensureMidtransColumnsExist($pdo) {
    try {
        $columns = [
            'payment_gateway' => "ALTER TABLE payments ADD COLUMN payment_gateway VARCHAR(50) DEFAULT 'manual_transfer' AFTER order_id",
            'payment_method_code' => "ALTER TABLE payments ADD COLUMN payment_method_code VARCHAR(50) DEFAULT 'BC' AFTER payment_gateway",
            'payment_fee' => "ALTER TABLE payments ADD COLUMN payment_fee INT DEFAULT 0 AFTER payment_method_code",
            'midtrans_snap_token' => "ALTER TABLE payments ADD COLUMN midtrans_snap_token VARCHAR(255) NULL AFTER payment_fee",
            'midtrans_transaction_id' => "ALTER TABLE payments ADD COLUMN midtrans_transaction_id VARCHAR(255) NULL AFTER midtrans_snap_token",
            'midtrans_payment_type' => "ALTER TABLE payments ADD COLUMN midtrans_payment_type VARCHAR(100) NULL AFTER midtrans_transaction_id",
            'midtrans_pdf_url' => "ALTER TABLE payments ADD COLUMN midtrans_pdf_url TEXT NULL AFTER midtrans_payment_type"
        ];

        $stmt = $pdo->query("SHOW COLUMNS FROM payments");
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($columns as $col => $alterSql) {
            if (!in_array($col, $existing)) {
                $pdo->exec($alterSql);
            }
        }
    } catch (Throwable $e) {
        // Silently skip if table or column already modified
    }
}

/**
 * Membuat Transaction Request ke Midtrans Snap API (/snap/v1/transactions)
 */
function createMidtransSnapToken($orderData, $paymentMethodCode, $customerData) {
    $orderNumber = $orderData['order_number'];
    $baseAmount = (int)$orderData['total']; // Total (subtotal + shipping_cost)
    $paymentFee = calculateMidtransFee($paymentMethodCode, $baseAmount);
    $grossAmount = $baseAmount + $paymentFee;

    $channels = getMidtransPaymentChannels();
    $channelInfo = $channels[$paymentMethodCode] ?? null;
    $midtransType = $channelInfo['midtrans_type'] ?? null;

    $enabledPayments = [];
    if ($midtransType === 'bca_va') $enabledPayments = ['bca_va'];
    elseif ($midtransType === 'echannel') $enabledPayments = ['echannel'];
    elseif ($midtransType === 'bri_va') $enabledPayments = ['bri_va'];
    elseif ($midtransType === 'bni_va') $enabledPayments = ['bni_va'];
    elseif ($midtransType === 'permata_va') $enabledPayments = ['permata_va'];
    elseif ($midtransType === 'cimb_va') $enabledPayments = ['cimb_va'];
    elseif ($midtransType === 'qris') $enabledPayments = ['gopay', 'qris', 'shopeepay'];
    elseif ($midtransType === 'credit_card') $enabledPayments = ['credit_card'];
    elseif ($midtransType === 'gopay') $enabledPayments = ['gopay'];
    elseif ($midtransType === 'shopeepay') $enabledPayments = ['shopeepay'];

    // Construct item details for Snap transaction
    $items = [];
    if (!empty($orderData['items'])) {
        foreach ($orderData['items'] as $it) {
            $items[] = [
                'id' => 'PRD-' . ($it['product_id'] ?? rand(100, 999)),
                'price' => (int)$it['price'],
                'quantity' => (int)$it['quantity'],
                'name' => mb_strimwidth($it['product_name'], 0, 45, '...')
            ];
        }
    } else {
        $items[] = [
            'id' => 'PRD-MAIN',
            'price' => (int)($orderData['subtotal'] ?? $baseAmount),
            'quantity' => 1,
            'name' => 'Produk Mesin CV Asianindo'
        ];
    }

    if (!empty($orderData['shipping_cost']) && (int)$orderData['shipping_cost'] > 0) {
        $items[] = [
            'id' => 'SHIP-COST',
            'price' => (int)$orderData['shipping_cost'],
            'quantity' => 1,
            'name' => 'Ongkos Kirim (' . ($orderData['expedition'] ?? 'Cargo') . ')'
        ];
    }

    if ($paymentFee > 0) {
        $items[] = [
            'id' => 'FEE-PLATFORM',
            'price' => (int)$paymentFee,
            'quantity' => 1,
            'name' => 'Biaya Layanan Payment Gateway'
        ];
    }

    $payload = [
        'transaction_details' => [
            'order_id' => $orderNumber,
            'gross_amount' => $grossAmount
        ],
        'item_details' => $items,
        'customer_details' => [
            'first_name' => $customerData['name'] ?? 'Pelanggan',
            'email' => $customerData['email'] ?? 'pembeli@asianindomachine.com',
            'phone' => $customerData['phone'] ?? '081234567890'
        ],
        'callbacks' => [
            'finish' => MIDTRANS_FINISH_URL . '?order_number=' . $orderNumber
        ]
    ];

    if (!empty($enabledPayments)) {
        $payload['enabled_payments'] = $enabledPayments;
    }

    $serverKey = MIDTRANS_SERVER_KEY;
    $authHeader = 'Basic ' . base64_encode($serverKey . ':');

    $ch = curl_init(MIDTRANS_SNAP_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: ' . $authHeader
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'statusCode' => '99',
            'statusMessage' => 'cURL Error: ' . $error
        ];
    }

    $resData = json_decode($response, true);

    if ($httpCode === 201 || $httpCode === 200) {
        return [
            'success' => true,
            'snap_token' => $resData['token'] ?? null,
            'redirect_url' => $resData['redirect_url'] ?? null,
            'raw' => $resData
        ];
    } else {
        return [
            'success' => false,
            'statusCode' => (string)$httpCode,
            'statusMessage' => $resData['error_messages'][0] ?? ($resData['status_message'] ?? 'Gagal membuat Snap Token Midtrans')
        ];
    }
}

/**
 * Cek Status Transaksi ke Midtrans Core API (/v2/{order_id}/status)
 */
function checkMidtransStatus($orderNumber) {
    $serverKey = MIDTRANS_SERVER_KEY;
    $authHeader = 'Basic ' . base64_encode($serverKey . ':');
    $url = MIDTRANS_CORE_URL . '/' . urlencode($orderNumber) . '/status';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: ' . $authHeader
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response, true) ?? [];
    $status = $resData['transaction_status'] ?? '';

    $isPaid = false;
    if (in_array($status, ['settlement', 'capture'])) {
        if ($status === 'capture') {
            $fraud = $resData['fraud_status'] ?? '';
            $isPaid = ($fraud === 'accept');
        } else {
            $isPaid = true;
        }
    }

    if ($isPaid) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = ? LIMIT 1");
            $stmt->execute([$orderNumber]);
            $ord = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ord) {
                $orderId = $ord['id'];
                $pdo->prepare("UPDATE orders SET status = 'payment_verified', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
                $pdo->prepare("UPDATE payments SET status = 'verified', midtrans_transaction_id = ?, midtrans_payment_type = ? WHERE order_id = ?")->execute([
                    $resData['transaction_id'] ?? '',
                    $resData['payment_type'] ?? '',
                    $orderId
                ]);
            }
        } catch (Throwable $e) {}
    }

    return [
        'success' => ($httpCode === 200),
        'is_paid' => $isPaid,
        'transaction_status' => $status,
        'data' => $resData
    ];
}

// REST API Handler jika dipanggil secara direct GET / POST
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'midtrans_api.php') {
    header('Content-Type: application/json');
    $pdo = getDB();
    ensureMidtransColumnsExist($pdo);

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'get_channels') {
        $amount = (int)($_GET['amount'] ?? $_POST['amount'] ?? 0);
        $channels = getMidtransPaymentChannels();
        $res = [];

        foreach ($channels as $code => $ch) {
            $fee = calculateMidtransFee($code, $amount);
            $res[] = array_merge($ch, [
                'fee_calculated' => $fee,
                'total_bill' => $amount + $fee
            ]);
        }

        echo json_encode(['success' => true, 'channels' => $res]);
        exit;
    }

    if ($action === 'calculate_fee') {
        $code = $_GET['channel_code'] ?? $_POST['channel_code'] ?? 'BC';
        $amount = (int)($_GET['amount'] ?? $_POST['amount'] ?? 0);
        $fee = calculateMidtransFee($code, $amount);

        echo json_encode([
            'success' => true,
            'channel_code' => $code,
            'base_amount' => $amount,
            'fee' => $fee,
            'total_bill' => $amount + $fee
        ]);
        exit;
    }

    if ($action === 'check_status') {
        $orderNumber = trim($_GET['order_number'] ?? $_POST['order_number'] ?? '');
        if (empty($orderNumber)) {
            echo json_encode(['success' => false, 'message' => 'Nomor pesanan wajib diisi']);
            exit;
        }

        $res = checkMidtransStatus($orderNumber);
        echo json_encode($res);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    exit;
}
