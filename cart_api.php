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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;

if ($action == 'get_cart_count') {
    if (!$customer_id) {
        echo json_encode(['success' => true, 'count' => 0]);
        exit;
    }
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'count' => $row['count'] ? (int)$row['count'] : 0]);
    exit;
}

// Require login for other actions
$customer_id = requireCustomerLogin();

switch ($action) {
    case 'get_cart':
        $stmt = $conn->prepare("SELECT * FROM cart_items WHERE customer_id = ? ORDER BY added_at DESC");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        $subtotal = 0;
        $total_items = 0;
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
            $subtotal += $row['product_price'] * $row['quantity'];
            $total_items += $row['quantity'];
        }
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'subtotal' => $subtotal,
            'total_items' => $total_items
        ]);
        break;

    case 'add_to_cart':
        $product_id = $_POST['product_id'] ?? 0;
        $product_name = $_POST['product_name'] ?? '';
        $product_image = $_POST['product_image'] ?? '';
        $product_price = $_POST['product_price'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        $weight_grams = $_POST['weight_grams'] ?? 0;
        
        if (!$product_id) {
            echo json_encode(['success' => false, 'error' => 'Invalid product']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO cart_items (customer_id, product_id, product_name, product_image, product_price, quantity, weight_grams) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
        $stmt->bind_param("iisssii", $customer_id, $product_id, $product_name, $product_image, $product_price, $quantity, $weight_grams);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item added to cart']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;

    case 'update_qty':
        $product_id = $_POST['product_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;
        
        if ($quantity <= 0) {
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $customer_id, $product_id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE customer_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $customer_id, $product_id);
            $stmt->execute();
        }
        echo json_encode(['success' => true]);
        break;

    case 'remove_item':
        $product_id = $_POST['product_id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $customer_id, $product_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove item']);
        }
        break;

    case 'clear_cart':
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to clear cart']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>
