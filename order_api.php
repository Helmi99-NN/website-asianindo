<?php
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
require_once __DIR__ . '/midtrans_config.php';
require_once __DIR__ . '/midtrans_api.php';
$pdo = getDB();

function getInput() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

/**
 * Mendapatkan daftar pilihan ekspedisi kargo & tarif otomatis
 * Menggunakan Biteship API dengan fallback cerdas ke matriks tarif kargo resmi
 */
function getShippingOptions($weightGrams, $province, $city = '', $postalCode = '') {
    $weightGrams = max(1000, (int)$weightGrams);
    $kg = max(1, ceil($weightGrams / 1000));
    $provinceClean = strtolower(trim($province));
    $apiKey = defined('BITESHIP_API_KEY') ? BITESHIP_API_KEY : '';
    $originPostal = defined('ORIGIN_POSTAL_CODE') ? ORIGIN_POSTAL_CODE : 65111;

    $options = [];

    // 1. Coba panggil Biteship API jika ada API Key & Kode Pos tujuan
    if (!empty($apiKey) && !empty($postalCode) && is_numeric($postalCode) && strlen($postalCode) === 5) {
        try {
            $payload = json_encode([
                'origin_postal_code' => (int)$originPostal,
                'destination_postal_code' => (int)$postalCode,
                'couriers' => 'indah_cargo,jne,sicepat,sentral,jnt',
                'items' => [[
                    'name' => 'Mesin Industri Asianindo',
                    'value' => 10000000,
                    'weight' => $weightGrams,
                    'quantity' => 1
                ]]
            ]);

            $ch = curl_init('https://api.biteship.com/v1/rates/couriers');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $resData = json_decode($response, true);
                if (!empty($resData['pricing']) && is_array($resData['pricing'])) {
                    foreach ($resData['pricing'] as $rate) {
                        $courierName = $rate['courier_name'] ?? $rate['company'] ?? 'Kargo Ekspedisi';
                        $serviceName = $rate['courier_service_name'] ?? $rate['service_type'] ?? 'Cargo Logistik';
                        $price = (int)($rate['price'] ?? 0);
                        $etd = $rate['duration'] ?? $rate['shipment_duration_range'] ?? '2-4 hari';

                        if ($price > 0) {
                            $options[] = [
                                'courier_code' => strtolower($rate['courier_code'] ?? $rate['company'] ?? 'cargo'),
                                'courier_name' => ucwords(str_replace('_', ' ', $courierName)),
                                'service_name' => $serviceName,
                                'price' => $price,
                                'etd' => $etd . (strpos($etd, 'hari') === false ? ' hari' : ''),
                                'description' => 'Tarif resmi real-time Biteship'
                            ];
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // Lanjut ke fallback matriks kargo jika API timeout / saldo habis
        }
    }

    // 2. Fallback Matriks Ekspedisi Kargo Resmi (Indah Cargo, JTR, SiCepat Gokil, Sentral Cargo)
    if (empty($options)) {
        // Base rate per kg dari Malang ke berbagai wilayah
        $baseIndah = 25000;
        $etd = '2-4 hari';

        if (strpos($provinceClean, 'jawa timur') !== false) {
            $baseIndah = 22000; $etd = '1-2 hari';
        } elseif (strpos($provinceClean, 'jawa tengah') !== false || strpos($provinceClean, 'yogyakarta') !== false) {
            $baseIndah = 30000; $etd = '2-3 hari';
        } elseif (strpos($provinceClean, 'jawa barat') !== false || strpos($provinceClean, 'jakarta') !== false || strpos($provinceClean, 'banten') !== false) {
            $baseIndah = 38000; $etd = '2-4 hari';
        } elseif (strpos($provinceClean, 'bali') !== false || strpos($provinceClean, 'nusa tenggara') !== false) {
            $baseIndah = 45000; $etd = '3-5 hari';
        } elseif (strpos($provinceClean, 'sumatera') !== false || strpos($provinceClean, 'lampung') !== false || strpos($provinceClean, 'riau') !== false) {
            $baseIndah = 75000; $etd = '4-7 hari';
        } elseif (strpos($provinceClean, 'kalimantan') !== false) {
            $baseIndah = 85000; $etd = '4-8 hari';
        } elseif (strpos($provinceClean, 'sulawesi') !== false) {
            $baseIndah = 90000; $etd = '5-9 hari';
        } elseif (strpos($provinceClean, 'papua') !== false || strpos($provinceClean, 'maluku') !== false) {
            $baseIndah = 180000; $etd = '7-14 hari';
        } else {
            $baseIndah = 45000; $etd = '3-6 hari';
        }

        $options = [
            [
                'courier_code' => 'indah_cargo',
                'courier_name' => 'Indah Cargo',
                'service_name' => 'Cargo Logistik Mesin (Darat/Laut)',
                'price' => (int)($baseIndah * $kg),
                'etd' => $etd,
                'is_recommended' => true,
                'description' => 'Pilihan Utama Pengiriman Mesin Berat Asianindo'
            ],
            [
                'courier_code' => 'jne_jtr',
                'courier_name' => 'JNE Trucking (JTR)',
                'service_name' => 'Layanan Kargo Truk',
                'price' => (int)(($baseIndah + 4000) * $kg),
                'etd' => $etd,
                'is_recommended' => false,
                'description' => 'Layanan Kargo Darat JNE'
            ],
            [
                'courier_code' => 'sicepat_gokil',
                'courier_name' => 'SiCepat Cargo (GOKIL)',
                'service_name' => 'Cargo Kilat Terpercaya',
                'price' => (int)(($baseIndah + 2000) * $kg),
                'etd' => $etd,
                'is_recommended' => false,
                'description' => 'Kargo Ekonomis SiCepat'
            ],
            [
                'courier_code' => 'sentral_cargo',
                'courier_name' => 'Sentral Cargo',
                'service_name' => 'Spesialis Logistik Mesin',
                'price' => (int)(($baseIndah + 3000) * $kg),
                'etd' => $etd,
                'is_recommended' => false,
                'description' => 'Ekspedisi Kargo Antar Pulau'
            ]
        ];
    }

    return $options;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$customer_id = $_SESSION['customer_id'] ?? null;

// === GET SHIPPING OPTIONS (PUBLIC / CHECKOUT) ===
if ($action === 'get_shipping_options' || $action === 'calculate_shipping') {
    $d = getInput();
    $weight_grams = (int)($d['weight_grams'] ?? 25000);
    $province = trim($d['province'] ?? '');
    $city = trim($d['city'] ?? '');
    $postal_code = trim($d['postal_code'] ?? $d['shipping_postal_code'] ?? '');

    $options = getShippingOptions($weight_grams, $province, $city, $postal_code);
    $firstCost = !empty($options[0]['price']) ? $options[0]['price'] : 50000;

    echo json_encode([
        'success' => true,
        'shipping_cost' => $firstCost,
        'options' => $options
    ]);
    exit;
}

if (!$customer_id) {
    echo json_encode(['success' => false, 'error' => 'Silakan login terlebih dahulu.']);
    exit;
}

// === CREATE ORDER ===
if ($action === 'create_order') {
    $d = getInput();
    $items = $d['items'] ?? [];
    if (is_string($items)) {
        $items = json_decode($items, true) ?: [];
    }

    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'Daftar produk tidak boleh kosong']);
        exit;
    }

    $shipping_name = trim($d['shipping_name'] ?? $_SESSION['customer_name'] ?? '');
    $shipping_phone = trim($d['shipping_phone'] ?? '');
    $shipping_address = trim($d['shipping_address'] ?? '');
    $shipping_city = trim($d['shipping_city'] ?? '');
    $shipping_province = trim($d['shipping_province'] ?? '');
    $shipping_postal_code = trim($d['shipping_postal_code'] ?? '');
    $selected_expedition = trim($d['expedition'] ?? 'Indah Cargo (Cargo Logistik Mesin)');
    $notes = trim($d['notes'] ?? '');
    $is_from_cart = !empty($d['is_from_cart']);

    $subtotal = 0;
    $totalWeight = 0;
    foreach ($items as $item) {
        $subtotal += ((int)($item['price'] ?? $item['product_price'] ?? 0)) * ((int)($item['quantity'] ?? 1));
        $totalWeight += ((int)($item['weight_grams'] ?? 25000)) * ((int)($item['quantity'] ?? 1));
    }

    $shipping_cost = (int)($d['shipping_cost'] ?? 0);
    if ($shipping_cost <= 0) {
        $calcOptions = getShippingOptions($totalWeight, $shipping_province, $shipping_city, $shipping_postal_code);
        $shipping_cost = !empty($calcOptions[0]['price']) ? $calcOptions[0]['price'] : 50000;
    }

    $total = $subtotal + $shipping_cost;
    $payment_scheme = trim($d['payment_scheme'] ?? 'dp_50');
    $payment_method_code = trim($d['payment_method_code'] ?? $d['payment_method'] ?? 'BC');
    $payment_gateway = ($payment_method_code === 'MANUAL_BCA' || $d['payment_gateway'] === 'manual_transfer') ? 'manual_transfer' : 'duitku';

    $initial_amount = $total;
    $scheme_text = '';

    if ($payment_scheme === 'dp_50') {
        $dp1 = (int)round($total * 0.5);
        $dp2 = $total - $dp1;
        $initial_amount = $dp1;
        $scheme_text = "Skema Pembayaran: DP 50% + Pelunasan 50%\n• DP Awal (50%): Rp " . number_format($dp1, 0, ',', '.') . " (Mulai Fabrikasi)\n• Pelunasan (50%): Rp " . number_format($dp2, 0, ',', '.') . " (Saat Siap Kirim)";
    } elseif ($payment_scheme === 'dp_3_stage') {
        $dp1 = (int)round($total * 0.3);
        $dp2 = (int)round($total * 0.4);
        $dp3 = $total - $dp1 - $dp2;
        $initial_amount = $dp1;
        $scheme_text = "Skema Pembayaran: 3 Tahap (30% - 40% - 30%)\n• DP 1 (30%): Rp " . number_format($dp1, 0, ',', '.') . " (Mulai Fabrikasi)\n• DP 2 (40%): Rp " . number_format($dp2, 0, ',', '.') . " (Progres ~50%)\n• Pelunasan (30%): Rp " . number_format($dp3, 0, ',', '.') . " (Saat Siap Kirim)";
    } else {
        $scheme_text = "Skema Pembayaran: Penuh 100% Lunas (Rp " . number_format($total, 0, ',', '.') . ")";
    }

    // Hitung Biaya Layanan Midtrans (jika bukan manual)
    $payment_fee = 0;
    if ($payment_gateway === 'midtrans') {
        $payment_fee = calculateMidtransFee($payment_method_code, $initial_amount);
    }
    $total_bill_amount = $initial_amount + $payment_fee;

    $final_notes = trim($scheme_text . ($notes ? "\n\nCatatan Khusus:\n" . $notes : ''));
    $order_number = generateOrderNumber();
    $status = 'pending_payment';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO orders (
                order_number, customer_id, shipping_name, shipping_phone, shipping_address,
                shipping_city, shipping_province, shipping_postal_code, notes, subtotal,
                shipping_cost, total, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $order_number, $customer_id, $shipping_name, $shipping_phone, $shipping_address,
            $shipping_city, $shipping_province, $shipping_postal_code, $final_notes, $subtotal,
            $shipping_cost, $total, $status
        ]);
        $order_id = (int)$pdo->lastInsertId();

        $stmt_item = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, product_image, price, quantity, weight_grams)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_clear = $pdo->prepare("DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?");

        foreach ($items as $item) {
            $stmt_item->execute([
                $order_id,
                $item['product_id'] ?? '',
                $item['product_name'] ?? '',
                $item['product_image'] ?? '',
                (int)($item['price'] ?? $item['product_price'] ?? 0),
                (int)($item['quantity'] ?? 1),
                (int)($item['weight_grams'] ?? 0)
            ]);

            if ($is_from_cart) {
                $stmt_clear->execute([$customer_id, $item['product_id']]);
            }
        }

        // Pastikan kolom Midtrans tersedia
        ensureMidtransColumnsExist($pdo);

        // Get Channel info
        $channels = getMidtransPaymentChannels();
        $selectedChannelInfo = $channels[$payment_method_code] ?? null;
        $channelName = $selectedChannelInfo['name'] ?? ($payment_gateway === 'midtrans' ? 'Midtrans Payment Gateway' : 'Bank BCA');

        // Jika metode pembayaran via Midtrans, panggil API Snap Token Midtrans
        $midtransResponse = null;
        $snapToken = null;
        $redirectUrl = null;

        if ($payment_gateway === 'midtrans') {
            $orderPayload = [
                'id' => $order_id,
                'order_number' => $order_number,
                'bill_amount' => $initial_amount,
                'total' => $total,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping_cost,
                'expedition' => $selected_expedition,
                'items' => $items,
                'shipping_name' => $shipping_name,
                'shipping_phone' => $shipping_phone
            ];
            $customerPayload = [
                'name' => $shipping_name ?: ($_SESSION['customer_name'] ?? 'Pelanggan'),
                'email' => $_SESSION['customer_email'] ?? 'pembeli@asianindomachine.com',
                'phone' => $shipping_phone ?: '081234567890'
            ];
            $midtransResponse = createMidtransSnapToken($orderPayload, $payment_method_code, $customerPayload);
            if ($midtransResponse && !empty($midtransResponse['snap_token'])) {
                $snapToken = $midtransResponse['snap_token'];
                $redirectUrl = $midtransResponse['redirect_url'] ?? null;
            }
        }

        // Insert initial payment record
        $stmt_pay = $pdo->prepare("
            INSERT INTO payments (
                order_id, bank_name, account_number, account_name, amount, 
                payment_gateway, payment_method_code, payment_fee, midtrans_snap_token, status, created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt_pay->execute([
            $order_id,
            $channelName,
            ($payment_gateway === 'manual_transfer') ? (defined('COMPANY_BANK_ACCOUNT') ? COMPANY_BANK_ACCOUNT : '6670747997') : '-',
            ($payment_gateway === 'manual_transfer') ? (defined('COMPANY_BANK_HOLDER') ? COMPANY_BANK_HOLDER : 'Iman Anjani Buchory') : 'Midtrans Gateway (' . $channelName . ')',
            $total_bill_amount,
            $payment_gateway,
            $payment_method_code,
            $payment_fee,
            $snapToken
        ]);

        // Insert initial shipment record
        $stmt_ship = $pdo->prepare("
            INSERT INTO shipments (order_id, expedition, status, created_at)
            VALUES (?, ?, 'preparing', NOW())
        ");
        $stmt_ship->execute([$order_id, $selected_expedition]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'order_number' => $order_number,
            'total' => $total,
            'initial_amount' => $initial_amount,
            'payment_fee' => $payment_fee,
            'total_bill_amount' => $total_bill_amount,
            'payment_scheme' => $payment_scheme,
            'payment_gateway' => $payment_gateway,
            'payment_method_code' => $payment_method_code,
            'shipping_cost' => $shipping_cost,
            'expedition' => $selected_expedition,
            'snap_token' => $snapToken,
            'redirect_url' => $redirectUrl,
            'midtrans' => $midtransResponse
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Gagal membuat pesanan: ' . $e->getMessage()]);
    }
    exit;
}

// === GET ORDERS ===
if ($action === 'get_orders') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$customer_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// === GET ORDER DETAIL ===
if ($action === 'get_order_detail') {
    $identifier = trim($_GET['id'] ?? $_GET['order_number'] ?? '');
    try {
        if (is_numeric($identifier) && strpos($identifier, 'ASN-') === false) {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? AND id = ? LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? AND order_number = ? LIMIT 1");
        }
        $stmt->execute([$customer_id, $identifier]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
            exit;
        }

        $order_id = $order['id'];

        $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt_items->execute([$order_id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $stmt_pay = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? LIMIT 1");
        $stmt_pay->execute([$order_id]);
        $payment = $stmt_pay->fetch(PDO::FETCH_ASSOC);

        $stmt_ship = $pdo->prepare("SELECT * FROM shipments WHERE order_id = ? LIMIT 1");
        $stmt_ship->execute([$order_id]);
        $shipment = $stmt_ship->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items,
            'payment' => $payment,
            'shipment' => $shipment
        ]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// === CANCEL ORDER ===
if ($action === 'cancel_order') {
    $d = getInput();
    $order_number = trim($d['order_number'] ?? '');

    try {
        $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE customer_id = ? AND order_number = ? LIMIT 1");
        $stmt->execute([$customer_id, $order_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
            exit;
        }

        if ($order['status'] !== 'pending_payment') {
            echo json_encode(['success' => false, 'error' => 'Pesanan tidak dapat dibatalkan pada tahap ini']);
            exit;
        }

        $stmt_update = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $stmt_update->execute([$order['id']]);

        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibatalkan']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action: ' . htmlspecialchars($action)]);
