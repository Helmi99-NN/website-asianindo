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
$pdo = getDB();

function getInput() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

function calculateShippingCost($weightGrams, $province) {
    $baseRate = 50000;
    $province = strtolower(trim($province));
    
    if (strpos($province, 'jawa timur') !== false) {
        $baseRate = 25000;
    } elseif (strpos($province, 'jawa tengah') !== false) {
        $baseRate = 35000;
    } elseif (strpos($province, 'jawa barat') !== false || strpos($province, 'jakarta') !== false) {
        $baseRate = 45000;
    } elseif (strpos($province, 'sumatera') !== false) {
        $baseRate = 80000;
    } elseif (strpos($province, 'kalimantan') !== false) {
        $baseRate = 95000;
    } elseif (strpos($province, 'sulawesi') !== false) {
        $baseRate = 100000;
    } elseif (strpos($province, 'papua') !== false) {
        $baseRate = 200000;
    }
    
    $kg = ceil($weightGrams / 1000);
    if ($kg <= 0) $kg = 1;
    return $baseRate * $kg;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$customer_id = $_SESSION['customer_id'] ?? null;

// === CALCULATE SHIPPING (Can be called before login or after) ===
if ($action === 'calculate_shipping') {
    $d = getInput();
    $weight_grams = (int)($d['weight_grams'] ?? 0);
    $province = trim($d['province'] ?? '');
    $cost = calculateShippingCost($weight_grams, $province);
    echo json_encode(['success' => true, 'shipping_cost' => $cost]);
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
    $notes = trim($d['notes'] ?? '');
    $is_from_cart = !empty($d['is_from_cart']);

    $subtotal = 0;
    $totalWeight = 0;
    foreach ($items as $item) {
        $subtotal += ((int)$item['price']) * ((int)$item['quantity']);
        $totalWeight += ((int)($item['weight_grams'] ?? 0)) * ((int)$item['quantity']);
    }

    $shipping_cost = calculateShippingCost($totalWeight, $shipping_province);
    $total = $subtotal + $shipping_cost;
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
            $shipping_city, $shipping_province, $shipping_postal_code, $notes, $subtotal,
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
                (int)($item['price'] ?? 0),
                (int)($item['quantity'] ?? 1),
                (int)($item['weight_grams'] ?? 0)
            ]);

            if ($is_from_cart) {
                $stmt_clear->execute([$customer_id, $item['product_id']]);
            }
        }

        // Insert initial payment record
        $stmt_pay = $pdo->prepare("
            INSERT INTO payments (order_id, bank_name, account_number, account_name, amount, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt_pay->execute([
            $order_id,
            defined('COMPANY_BANK_NAME') ? COMPANY_BANK_NAME : 'Bank BCA',
            defined('COMPANY_BANK_ACCOUNT') ? COMPANY_BANK_ACCOUNT : '6670747997',
            defined('COMPANY_BANK_HOLDER') ? COMPANY_BANK_HOLDER : 'Iman Anjani Buchory',
            $total
        ]);

        // Insert initial shipment record
        $stmt_ship = $pdo->prepare("
            INSERT INTO shipments (order_id, expedition, status, created_at)
            VALUES (?, 'Indah Kargo', 'preparing', NOW())
        ");
        $stmt_ship->execute([$order_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'order_number' => $order_number,
            'total' => $total,
            'shipping_cost' => $shipping_cost
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
