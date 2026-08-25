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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$customer_id = $_SESSION['customer_id'] ?? null;

// === GET CART COUNT (PUBLIC / GUEST SUPPORT) ===
if ($action === 'get_cart_count') {
    if (!$customer_id) {
        echo json_encode(['success' => true, 'count' => 0]);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart_items WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $row = $stmt->fetch();
        echo json_encode(['success' => true, 'count' => (int)($row['count'] ?? 0)]);
    } catch (Throwable $e) {
        echo json_encode(['success' => true, 'count' => 0]);
    }
    exit;
}

// Require login for other cart operations
if (!$customer_id) {
    echo json_encode(['success' => false, 'error' => 'Silakan login terlebih dahulu.']);
    exit;
}

// === GET CART ITEMS ===
if ($action === 'get_cart') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE customer_id = ? ORDER BY added_at DESC");
        $stmt->execute([$customer_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subtotal = 0;
        $total_items = 0;
        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['customer_id'] = (int)$item['customer_id'];
            $item['product_price'] = (int)$item['product_price'];
            $item['quantity'] = (int)$item['quantity'];
            $item['weight_grams'] = (int)($item['weight_grams'] ?? 0);
            $subtotal += $item['product_price'] * $item['quantity'];
            $total_items += $item['quantity'];
        }

        echo json_encode([
            'success' => true,
            'items' => $items,
            'subtotal' => $subtotal,
            'total_items' => $total_items
        ]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Gagal mengambil data keranjang: ' . $e->getMessage()]);
    }
    exit;
}

// === ADD TO CART ===
if ($action === 'add_to_cart') {
    $d = getInput();
    $product_id = trim($d['product_id'] ?? '');
    $product_name = trim($d['product_name'] ?? '');
    $product_image = trim($d['product_image'] ?? '');
    $product_price = (int)($d['product_price'] ?? 0);
    $quantity = max(1, (int)($d['quantity'] ?? 1));
    $weight_grams = (int)($d['weight_grams'] ?? 0);

    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'ID Produk tidak valid']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (customer_id, product_id, product_name, product_image, product_price, quantity, weight_grams, added_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                quantity = quantity + VALUES(quantity),
                product_price = VALUES(product_price),
                product_name = VALUES(product_name),
                product_image = VALUES(product_image)
        ");
        $stmt->execute([$customer_id, $product_id, $product_name, $product_image, $product_price, $quantity, $weight_grams]);

        echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan ke keranjang']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Gagal menambahkan ke keranjang: ' . $e->getMessage()]);
    }
    exit;
}

// === UPDATE QTY ===
if ($action === 'update_qty') {
    $d = getInput();
    $product_id = trim($d['product_id'] ?? '');
    $quantity = (int)($d['quantity'] ?? 0);

    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'ID Produk tidak valid']);
        exit;
    }

    try {
        if ($quantity <= 0) {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?");
            $stmt->execute([$customer_id, $product_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE customer_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $customer_id, $product_id]);
        }
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Gagal mengubah jumlah: ' . $e->getMessage()]);
    }
    exit;
}

// === REMOVE ITEM ===
if ($action === 'remove_item') {
    $d = getInput();
    $product_id = trim($d['product_id'] ?? '');

    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'ID Produk tidak valid']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?");
        $stmt->execute([$customer_id, $product_id]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus produk: ' . $e->getMessage()]);
    }
    exit;
}

// === CLEAR CART ===
if ($action === 'clear_cart') {
    try {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Gagal mengosongkan keranjang: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action: ' . htmlspecialchars($action)]);
