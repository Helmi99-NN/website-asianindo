<?php
session_start();
header('Content-Type: application/json');
require_once 'database/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'upload_proof') {
    $order_number = $_POST['order_number'] ?? '';
    
    if (empty($order_number)) {
        echo json_encode(['success' => false, 'message' => 'Nomor pesanan wajib diisi.']);
        exit;
    }

    if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Silakan pilih file gambar yang valid.']);
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

    $new_filename = 'proof_' . $order_number . '_' . time() . '.' . $ext;
    $target_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $db_path = 'images/payments/' . $new_filename;

        // Check if payment record exists
        $stmt = $conn->prepare("SELECT id FROM payments WHERE order_number = ?");
        $stmt->bind_param("s", $order_number);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE payments SET proof_image = ?, status = 'uploaded', updated_at = NOW() WHERE order_number = ?");
            $stmt->bind_param("ss", $db_path, $order_number);
            $stmt->execute();
        } else {
            // If we don't have order_id, let's get it first
            $stmt = $conn->prepare("SELECT id, total_amount FROM orders WHERE order_number = ?");
            $stmt->bind_param("s", $order_number);
            $stmt->execute();
            $order_res = $stmt->get_result();
            if ($order_res->num_rows > 0) {
                $order_data = $order_res->fetch_assoc();
                $order_id = $order_data['id'];
                $amount = $order_data['total_amount'];
                
                $stmt = $conn->prepare("INSERT INTO payments (order_id, order_number, amount, payment_method, proof_image, status, created_at) VALUES (?, ?, ?, 'transfer', ?, 'uploaded', NOW())");
                $stmt->bind_param("isds", $order_id, $order_number, $amount, $db_path);
                $stmt->execute();
            }
        }

        $stmt = $conn->prepare("UPDATE orders SET status = 'payment_uploaded' WHERE order_number = ?");
        $stmt->bind_param("s", $order_number);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Bukti transfer berhasil diunggah']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengunggah file.']);
    }
} elseif ($action === 'get_payment_info') {
    $order_number = $_GET['order_number'] ?? '';
    if (empty($order_number)) {
        echo json_encode(['success' => false, 'message' => 'Nomor pesanan diperlukan.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT o.id, o.status as order_status, o.total_amount, o.created_at, p.status as payment_status, p.proof_image FROM orders o LEFT JOIN payments p ON o.id = p.order_id WHERE o.order_number = ?");
    $stmt->bind_param("s", $order_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        $bank_info = [
            'bank_name' => 'BCA',
            'account_number' => '6670747997',
            'account_name' => 'Iman Anjani Buchory'
        ];
        
        echo json_encode([
            'success' => true,
            'order' => [
                'order_number' => $order_number,
                'total_amount' => (float)$data['total_amount'],
                'order_status' => $data['order_status'],
                'created_at' => $data['created_at'],
                'payment_status' => $data['payment_status'] ?? 'pending',
                'proof_image' => $data['proof_image'] ?? null
            ],
            'bank' => $bank_info
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
