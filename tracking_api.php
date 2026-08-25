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

$action = $_GET['action'] ?? '';

if ($action === 'get_tracking') {
    $order_number = trim($_GET['order_number'] ?? '');
    if (empty($order_number)) {
        echo json_encode(['success' => false, 'message' => 'Nomor pesanan diperlukan.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT 
                o.id, o.order_number, o.status, o.subtotal, o.shipping_cost, o.total as total_amount, o.created_at as order_date,
                s.tracking_number, s.expedition as expedition_name, s.estimated_arrival, s.status as shipment_status, s.notes as shipment_notes,
                c.name as customer_name, c.phone, c.address, c.city, c.province
            FROM orders o 
            LEFT JOIN shipments s ON o.id = s.order_id 
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.order_number = ?
            LIMIT 1
        ");
        $stmt->execute([$order_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Get items
            $stmt_items = $pdo->prepare("
                SELECT product_id, product_name as name, product_image as image_url, price, quantity, weight_grams
                FROM order_items
                WHERE order_id = ?
            ");
            $stmt_items->execute([$order['id']]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            $timeline = [
                'order_created' => [
                    'title' => 'Pesanan Dibuat',
                    'completed' => true,
                    'date' => $order['order_date']
                ],
                'payment_verified' => [
                    'title' => 'Pembayaran Diterima',
                    'completed' => in_array($order['status'], ['payment_verified', 'processing', 'shipped', 'delivered', 'completed']),
                    'date' => null
                ],
                'processing' => [
                    'title' => 'Mesin Sedang Dipersiapkan',
                    'completed' => in_array($order['status'], ['processing', 'shipped', 'delivered', 'completed']),
                    'date' => null
                ],
                'shipped' => [
                    'title' => 'Diserahkan ke Ekspedisi',
                    'completed' => in_array($order['status'], ['shipped', 'delivered', 'completed']),
                    'date' => null
                ],
                'delivered' => [
                    'title' => 'Pesanan Telah Sampai di Tujuan',
                    'completed' => in_array($order['status'], ['delivered', 'completed']),
                    'date' => null
                ]
            ];

            echo json_encode([
                'success' => true,
                'order' => [
                    'order_number' => $order['order_number'],
                    'status' => $order['status'],
                    'total_amount' => (int)$order['total_amount'],
                    'shipping_cost' => (int)$order['shipping_cost']
                ],
                'shipping' => [
                    'tracking_number' => $order['tracking_number'] ?? '',
                    'expedition_name' => $order['expedition_name'] ?? 'Indah Kargo',
                    'estimated_arrival' => $order['estimated_arrival'] ?? '',
                    'notes' => $order['shipment_notes'] ?? '',
                    'status' => $order['shipment_status'] ?? 'preparing'
                ],
                'customer' => [
                    'name' => $order['customer_name'] ?? '',
                    'phone' => $order['phone'] ?? '',
                    'address' => trim(($order['address'] ?? '') . ', ' . ($order['city'] ?? '') . ', ' . ($order['province'] ?? ''), ', ')
                ],
                'items' => $items,
                'timeline' => $timeline
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
