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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// === UPLOAD PAYMENT PROOF ===
if ($action === 'upload_proof') {
    $order_number = trim($_POST['order_number'] ?? '');
    
    if (empty($order_number)) {
        echo json_encode(['success' => false, 'message' => 'Nomor pesanan wajib diisi.']);
        exit;
    }

    if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Silakan pilih file gambar bukti transfer yang valid.']);
        exit;
    }

    $file = $_FILES['proof_image'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $file_info = pathinfo($file['name']);
    $ext = strtolower($file_info['extension'] ?? '');

    if (!in_array($ext, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.']);
        exit;
    }

    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB.']);
        exit;
    }

    $upload_dir = __DIR__ . '/images/payments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $new_filename = 'proof_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $order_number) . '_' . time() . '.' . $ext;
    $target_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $db_path = 'images/payments/' . $new_filename;

        try {
            // Find order
            $stmt = $pdo->prepare("SELECT id, total FROM orders WHERE order_number = ? LIMIT 1");
            $stmt->execute([$order_number]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                $order_id = $order['id'];

                // Check payment record
                $stmt_pay = $pdo->prepare("SELECT id FROM payments WHERE order_id = ? LIMIT 1");
                $stmt_pay->execute([$order_id]);
                $pay = $stmt_pay->fetch(PDO::FETCH_ASSOC);

                if ($pay) {
                    $stmt_update = $pdo->prepare("UPDATE payments SET proof_image = ?, status = 'uploaded' WHERE id = ?");
                    $stmt_update->execute([$db_path, $pay['id']]);
                } else {
                    $stmt_ins = $pdo->prepare("
                        INSERT INTO payments (order_id, bank_name, account_number, account_name, amount, proof_image, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'uploaded', NOW())
                    ");
                    $stmt_ins->execute([
                        $order_id,
                        defined('COMPANY_BANK_NAME') ? COMPANY_BANK_NAME : 'Bank BCA',
                        defined('COMPANY_BANK_ACCOUNT') ? COMPANY_BANK_ACCOUNT : '6670747997',
                        defined('COMPANY_BANK_HOLDER') ? COMPANY_BANK_HOLDER : 'Iman Anjani Buchory',
                        $order['total'],
                        $db_path
                    ]);
                }

                $stmt_ord = $pdo->prepare("UPDATE orders SET status = 'payment_uploaded', updated_at = NOW() WHERE id = ?");
                $stmt_ord->execute([$order_id]);

                echo json_encode(['success' => true, 'message' => 'Bukti transfer berhasil diunggah', 'image_url' => $db_path]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
            }
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan bukti: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengunggah file ke server.']);
    }
    exit;
}

// === GET PAYMENT INFO ===
if ($action === 'get_payment_info') {
    $order_number = trim($_GET['order_number'] ?? '');
    if (empty($order_number)) {
        echo json_encode(['success' => false, 'message' => 'Nomor pesanan diperlukan.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT o.id, o.order_number, o.customer_id, o.shipping_name, o.shipping_phone, o.shipping_address,
                   o.shipping_city, o.shipping_province, o.shipping_postal_code, o.shipping_cost, o.subtotal,
                   o.status as order_status, o.total as total_amount, o.notes, o.created_at,
                   p.amount as bill_amount, p.status as payment_status, p.proof_image, p.bank_name, p.account_number, p.account_name,
                   p.payment_gateway, p.payment_method_code, p.payment_fee, p.duitku_reference, p.duitku_va_number, p.duitku_payment_url, p.duitku_qr_string,
                   s.expedition
            FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id
            LEFT JOIN shipments s ON o.id = s.order_id
            WHERE o.order_number = ?
            LIMIT 1
        ");
        $stmt->execute([$order_number]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Fetch order items
            $stmt_items = $pdo->prepare("SELECT product_name, quantity, price, weight_grams FROM order_items WHERE order_id = ?");
            $stmt_items->execute([$data['id']]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            // Fetch customer name/phone if shipping_name is missing
            $customer_name = $data['shipping_name'];
            $customer_phone = $data['shipping_phone'];
            if ((empty($customer_name) || empty($customer_phone)) && !empty($data['customer_id'])) {
                $stmt_c = $pdo->prepare("SELECT name, phone FROM customers WHERE id = ?");
                $stmt_c->execute([$data['customer_id']]);
                $c = $stmt_c->fetch(PDO::FETCH_ASSOC);
                if ($c) {
                    if (empty($customer_name)) $customer_name = $c['name'];
                    if (empty($customer_phone)) $customer_phone = $c['phone'];
                }
            }

            $bank_info = [
                'bank_name' => $data['bank_name'] ?: (defined('COMPANY_BANK_NAME') ? COMPANY_BANK_NAME : 'Bank BCA'),
                'account_number' => $data['account_number'] ?: (defined('COMPANY_BANK_ACCOUNT') ? COMPANY_BANK_ACCOUNT : '6670747997'),
                'account_name' => $data['account_name'] ?: (defined('COMPANY_BANK_HOLDER') ? COMPANY_BANK_HOLDER : 'Iman Anjani Buchory'),
                'whatsapp' => defined('COMPANY_WA_NUMBER') ? COMPANY_WA_NUMBER : '6285335850517'
            ];

            $billAmount = !empty($data['bill_amount']) ? (int)$data['bill_amount'] : (int)$data['total_amount'];

            echo json_encode([
                'success' => true,
                'order' => [
                    'order_number' => $order_number,
                    'customer_name' => $customer_name,
                    'shipping_name' => $customer_name,
                    'shipping_phone' => $customer_phone,
                    'shipping_address' => $data['shipping_address'] ?? '',
                    'shipping_city' => $data['shipping_city'] ?? '',
                    'shipping_province' => $data['shipping_province'] ?? '',
                    'shipping_postal_code' => $data['shipping_postal_code'] ?? '',
                    'shipping_cost' => (int)($data['shipping_cost'] ?? 0),
                    'expedition' => $data['expedition'] ?: 'Indah Cargo (Cargo Logistik Mesin)',
                    'items' => $items,
                    'total_amount' => (int)$data['total_amount'],
                    'bill_amount' => $billAmount,
                    'order_status' => $data['order_status'],
                    'notes' => $data['notes'] ?? '',
                    'created_at' => $data['created_at'],
                    'payment_gateway' => $data['payment_gateway'] ?? 'manual_transfer',
                    'payment_method_code' => $data['payment_method_code'] ?? 'BC',
                    'payment_fee' => (int)($data['payment_fee'] ?? 0),
                    'duitku_reference' => $data['duitku_reference'] ?? null,
                    'duitku_va_number' => $data['duitku_va_number'] ?? null,
                    'duitku_payment_url' => $data['duitku_payment_url'] ?? null,
                    'duitku_qr_string' => $data['duitku_qr_string'] ?? null,
                    'bank_name' => $data['bank_name'] ?? 'Bank BCA',
                    'account_number' => $data['account_number'] ?? '6670747997',
                    'account_name' => $data['account_name'] ?? 'Iman Anjani Buchory',
                    'payment_status' => $data['payment_status'] ?? 'pending',
                    'proof_image' => $data['proof_image'] ?? null
                ],
                'bank' => $bank_info
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
