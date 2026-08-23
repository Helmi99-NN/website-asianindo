<?php
session_start();
header('Content-Type: application/json');
require_once 'database/db.php';

if (!function_exists('requireCustomerLogin')) {
    function requireCustomerLogin() {
        if (!isset($_SESSION['customer_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        }
        return $_SESSION['customer_id'];
    }
}

function calculateShippingCost($weightGrams, $province) {
    // Estimasi biaya kirim via Indah Kargo (Cargo Logistik Mesin)
    $baseRate = 50000;
    $province = strtolower(trim($province));
    
    // Simple logic for illustration
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
    if ($kg == 0) $kg = 1;
    return $baseRate * $kg;
}

function generateOrderNumber() {
    return 'ASN-' . date('YmdHis') . '-' . rand(1000, 9999);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$customer_id = requireCustomerLogin();

switch ($action) {
    case 'create_order':
        $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
        if (empty($items)) {
            echo json_encode(['success' => false, 'error' => 'Items are required']);
            exit;
        }

        $shipping_name = $_POST['shipping_name'] ?? '';
        $shipping_phone = $_POST['shipping_phone'] ?? '';
        $shipping_address = $_POST['shipping_address'] ?? '';
        $shipping_city = $_POST['shipping_city'] ?? '';
        $shipping_province = $_POST['shipping_province'] ?? '';
        $shipping_postal_code = $_POST['shipping_postal_code'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $is_from_cart = $_POST['is_from_cart'] ?? false;
        
        $subtotal = 0;
        $totalWeight = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
            $totalWeight += ($item['weight_grams'] ?? 0) * $item['quantity'];
        }
        
        $shipping_cost = calculateShippingCost($totalWeight, $shipping_province);
        $total = $subtotal + $shipping_cost;
        $order_number = generateOrderNumber();
        $status = 'pending_payment';

        $conn->begin_transaction();
        try {
            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (order_number, customer_id, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_province, shipping_postal_code, notes, subtotal, shipping_cost, total, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sisssssssiiis", $order_number, $customer_id, $shipping_name, $shipping_phone, $shipping_address, $shipping_city, $shipping_province, $shipping_postal_code, $notes, $subtotal, $shipping_cost, $total, $status);
            $stmt->execute();
            $order_id = $conn->insert_id;

            // Insert order items
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_image, price, quantity, weight_grams) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmt_item->bind_param("iissiii", $order_id, $item['product_id'], $item['product_name'], $item['product_image'], $item['price'], $item['quantity'], $item['weight_grams']);
                $stmt_item->execute();
                
                // Clear from cart if flag is set
                if ($is_from_cart) {
                    $stmt_clear = $conn->prepare("DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?");
                    $stmt_clear->bind_param("ii", $customer_id, $item['product_id']);
                    $stmt_clear->execute();
                }
            }

            // Insert payment record
            $bank_name = 'Bank BCA';
            $acc_num = '6670747997';
            $acc_name = 'Iman Anjani Buchory';
            $pay_status = 'pending';
            $stmt_pay = $conn->prepare("INSERT INTO payments (order_id, bank_name, account_number, account_name, amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt_pay->bind_param("isssis", $order_id, $bank_name, $acc_num, $acc_name, $total, $pay_status);
            $stmt_pay->execute();

            // Insert shipment record
            $expedition = 'Indah Kargo';
            $ship_status = 'preparing';
            $stmt_ship = $conn->prepare("INSERT INTO shipments (order_id, expedition, status, created_at) VALUES (?, ?, ?, NOW())");
            $stmt_ship->bind_param("iss", $order_id, $expedition, $ship_status);
            $stmt_ship->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'order_number' => $order_number, 'total' => $total, 'shipping_cost' => $shipping_cost]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_orders':
        $stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;

    case 'get_order_detail':
        $identifier = $_GET['id'] ?? $_GET['order_number'] ?? '';
        if (is_numeric($identifier) && !str_contains($identifier, 'ASN-')) {
            $stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? AND id = ?");
            $stmt->bind_param("ii", $customer_id, $identifier);
        } else {
            $stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? AND order_number = ?");
            $stmt->bind_param("is", $customer_id, $identifier);
        }
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }
        
        $order_id = $order['id'];
        
        $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $stmt_pay = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
        $stmt_pay->bind_param("i", $order_id);
        $stmt_pay->execute();
        $payment = $stmt_pay->get_result()->fetch_assoc();
        
        $stmt_ship = $conn->prepare("SELECT * FROM shipments WHERE order_id = ?");
        $stmt_ship->bind_param("i", $order_id);
        $stmt_ship->execute();
        $shipment = $stmt_ship->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'order' => $order, 
            'items' => $items, 
            'payment' => $payment, 
            'shipment' => $shipment
        ]);
        break;

    case 'calculate_shipping':
        $weight_grams = $_POST['weight_grams'] ?? 0;
        $province = $_POST['province'] ?? '';
        $cost = calculateShippingCost($weight_grams, $province);
        echo json_encode(['success' => true, 'shipping_cost' => $cost]);
        break;
        
    case 'cancel_order':
        $order_number = $_POST['order_number'] ?? '';
        
        $stmt = $conn->prepare("SELECT id, status FROM orders WHERE customer_id = ? AND order_number = ?");
        $stmt->bind_param("is", $customer_id, $order_number);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }
        
        if ($order['status'] !== 'pending_payment') {
            echo json_encode(['success' => false, 'error' => 'Order cannot be cancelled at this stage']);
            exit;
        }
        
        $status = 'cancelled';
        $stmt_update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $status, $order['id']);
        if ($stmt_update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Order cancelled']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to cancel order']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>
