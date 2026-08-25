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

                echo json_encode(['success' => true, 'message' => 'Bukti transfer berhasil diunggah']);
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
            SELECT o.id, o.order_number, o.status as order_status, o.total as total_amount, o.notes, o.created_at,
                   p.amount as bill_amount, p.status as payment_status, p.proof_image, p.bank_name, p.account_number, p.account_name
            FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id
            WHERE o.order_number = ?
            LIMIT 1
        ");
        $stmt->execute([$order_number]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
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
                    'total_amount' => (int)$data['total_amount'],
                    'bill_amount' => $billAmount,
                    'order_status' => $data['order_status'],
                    'notes' => $data['notes'] ?? '',
                    'created_at' => $data['created_at'],
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
