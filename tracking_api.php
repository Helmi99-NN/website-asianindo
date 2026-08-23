<?php
session_start();
header('Content-Type: application/json');
require_once 'database/db.php';

$action = $_GET['action'] ?? '';

if ($action === 'get_tracking') {
    $order_number = $_GET['order_number'] ?? '';
    if (empty($order_number)) {
        echo json_encode(['success' => false, 'message' => 'Nomor pesanan diperlukan.']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT 
            o.id, o.order_number, o.status, o.total_amount, o.shipping_cost, o.created_at as order_date,
            s.tracking_number, s.expedition_name, s.estimated_arrival, s.status as shipment_status, s.notes as shipment_notes, s.shipped_at,
            c.name as customer_name, c.phone, c.address, c.city, c.province
        FROM orders o 
        LEFT JOIN shipments s ON o.id = s.order_id 
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.order_number = ?
    ");
    $stmt->bind_param("s", $order_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // Get items
        $stmt_items = $conn->prepare("
            SELECT oi.quantity, oi.price, p.name, p.image_url 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt_items->bind_param("i", $order['id']);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }

        $timeline = [
            'order_created' => [
                'title' => 'Pesanan Dibuat',
                'completed' => true,
                'date' => $order['order_date']
            ],
            'payment_verified' => [
                'title' => 'Pembayaran Diterima',
                'completed' => in_array($order['status'], ['processing', 'shipped', 'delivered', 'completed']),
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
                'date' => $order['shipped_at']
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
                'total_amount' => $order['total_amount'],
                'shipping_cost' => $order['shipping_cost']
            ],
            'shipping' => [
                'tracking_number' => $order['tracking_number'],
                'expedition_name' => $order['expedition_name'],
                'estimated_arrival' => $order['estimated_arrival'],
                'notes' => $order['shipment_notes'],
                'status' => $order['shipment_status']
            ],
            'customer' => [
                'name' => $order['customer_name'],
                'phone' => $order['phone'],
                'address' => $order['address'] . ', ' . $order['city'] . ', ' . $order['province']
            ],
            'items' => $items,
            'timeline' => $timeline
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
