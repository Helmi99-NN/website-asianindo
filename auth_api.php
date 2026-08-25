<?php
/**
 * Customer Authentication & Profile API
 * CV Asianindo E-Commerce System
 * (asianindomachine.com)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once __DIR__ . '/database/db.php';
$pdo = getDB();

/**
 * Helper to ensure customer is logged in.
 * Checks session and returns customer_id or responds with 401 JSON and exits.
 * Can be reused by other API files.
 *
 * @return int Customer ID
 */
function requireCustomerLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['customer_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'logged_in' => false,
            'error' => 'Unauthorized. Silakan login terlebih dahulu.'
        ]);
        exit;
    }
    return (int)$_SESSION['customer_id'];
}

/**
 * Helper to parse request data from both JSON body and standard $_POST
 *
 * @return array
 */
function getRequestData() {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if (is_array($input)) {
        return array_merge($_POST, $input);
    }
    return $_POST;
}

$action = $_GET['action'] ?? '';

// ==========================================
// 1. REGISTER (POST)
// ==========================================
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Metode request tidak diizinkan. Gunakan POST.']);
        exit;
    }

    $data = getRequestData();
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';
    $address = trim($data['address'] ?? '');
    $city = trim($data['city'] ?? '');
    $province = trim($data['province'] ?? '');
    $postal_code = trim($data['postal_code'] ?? '');

    // Validation: Required fields
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nama, email, nomor telepon, dan password wajib diisi!']);
        exit;
    }

    // Validation: Email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Format email tidak valid!']);
        exit;
    }

    // Validation: Password length
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password minimal harus 6 karakter!']);
        exit;
    }

    try {
        // Check if email already registered
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Email sudah terdaftar. Silakan gunakan email lain atau login.']);
            exit;
        }

        // Hash password with bcrypt
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert into customers table (using password_hash from setup.sql)
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO customers (name, email, phone, password_hash, address, city, province, postal_code, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $insertStmt->execute([
                $name,
                $email,
                $phone,
                $hashedPassword,
                $address,
                $city,
                $province,
                $postal_code
            ]);
        } catch (PDOException $pe) {
            // Fallback in case column is named password
            $insertStmt = $pdo->prepare("
                INSERT INTO customers (name, email, phone, password, address, city, province, postal_code, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $insertStmt->execute([
                $name,
                $email,
                $phone,
                $hashedPassword,
                $address,
                $city,
                $province,
                $postal_code
            ]);
        }

        $customerId = (int)$pdo->lastInsertId();

        // Auto-login after register (set session)
        $_SESSION['customer_id'] = $customerId;
        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_email'] = $email;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registrasi berhasil!',
            'customer' => [
                'id' => $customerId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'province' => $province,
                'postal_code' => $postal_code
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan pada server saat registrasi: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 2. LOGIN (POST)
// ==========================================
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Metode request tidak diizinkan. Gunakan POST.']);
        exit;
    }

    $data = getRequestData();
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email dan password wajib diisi!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        $storedHash = $customer['password_hash'] ?? $customer['password'] ?? '';

        if (!$customer || empty($storedHash) || !password_verify($password, $storedHash)) {
            http_response_code(401);
            echo json_encode(['error' => 'Email atau password salah!']);
            exit;
        }

        // Set session
        $_SESSION['customer_id'] = (int)$customer['id'];
        $_SESSION['customer_name'] = $customer['name'];
        $_SESSION['customer_email'] = $customer['email'];

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil!',
            'customer' => [
                'id' => (int)$customer['id'],
                'name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'] ?? '',
                'address' => $customer['address'] ?? '',
                'city' => $customer['city'] ?? '',
                'province' => $customer['province'] ?? '',
                'postal_code' => $customer['postal_code'] ?? ''
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan pada server saat login: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 2B. GOOGLE AUTH (LOGIN / REGISTER) (POST)
// ==========================================
if ($action === 'google_auth') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Metode request tidak diizinkan. Gunakan POST.']);
        exit;
    }

    $data = getRequestData();
    $credential = $data['credential'] ?? '';
    $email = trim($data['email'] ?? '');
    $name = trim($data['name'] ?? '');

    // If Google JWT token was sent from Google Identity Services
    if (!empty($credential)) {
        $parts = explode('.', $credential);
        if (count($parts) === 3) {
            $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
            $payload = json_decode($payloadJson, true);
            if ($payload && !empty($payload['email'])) {
                $email = trim($payload['email']);
                $name = trim($payload['name'] ?? explode('@', $email)[0]);
            }
        }
    }

    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Data akun Google tidak valid!']);
        exit;
    }

    try {
        // Check if customer exists
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer) {
            // Existing customer -> Auto Login
            $_SESSION['customer_id'] = (int)$customer['id'];
            $_SESSION['customer_name'] = $customer['name'];
            $_SESSION['customer_email'] = $customer['email'];

            echo json_encode([
                'success' => true,
                'is_new' => false,
                'message' => 'Login dengan akun Google berhasil!',
                'customer' => [
                    'id' => (int)$customer['id'],
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'phone' => $customer['phone'] ?? '',
                    'address' => $customer['address'] ?? '',
                    'city' => $customer['city'] ?? '',
                    'province' => $customer['province'] ?? '',
                    'postal_code' => $customer['postal_code'] ?? ''
                ]
            ]);
            exit;
        } else {
            // New customer -> Auto Register
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);
            $phone = trim($data['phone'] ?? '-');

            try {
                $insertStmt = $pdo->prepare("
                    INSERT INTO customers (name, email, phone, password_hash, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                $insertStmt->execute([$name, $email, $phone, $hashedPassword]);
            } catch (PDOException $pe) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO customers (name, email, phone, password, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                $insertStmt->execute([$name, $email, $phone, $hashedPassword]);
            }

            $customerId = (int)$pdo->lastInsertId();

            $_SESSION['customer_id'] = $customerId;
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'is_new' => true,
                'message' => 'Pendaftaran akun dengan Google berhasil!',
                'customer' => [
                    'id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone
                ]
            ]);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan saat autentikasi Google: ' . $e->getMessage()]);
        exit;
    }
}

// ==========================================
// 3. LOGOUT (POST / GET)
// ==========================================
if ($action === 'logout') {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Logout berhasil!'
    ]);
    exit;
}

// ==========================================
// 4. CHECK SESSION (GET)
// ==========================================
if ($action === 'check_session') {
    if (!empty($_SESSION['customer_id'])) {
        echo json_encode([
            'logged_in' => true,
            'customer' => [
                'id' => (int)$_SESSION['customer_id'],
                'name' => $_SESSION['customer_name'] ?? '',
                'email' => $_SESSION['customer_email'] ?? ''
            ]
        ]);
    } else {
        echo json_encode([
            'logged_in' => false,
            'customer' => null
        ]);
    }
    exit;
}

// ==========================================
// 5. GET PROFILE (GET)
// ==========================================
if ($action === 'get_profile') {
    $customerId = requireCustomerLogin();

    try {
        $stmt = $pdo->prepare("
            SELECT id, name, email, phone, address, city, province, postal_code, created_at, updated_at
            FROM customers
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            http_response_code(404);
            echo json_encode(['error' => 'Data customer tidak ditemukan!']);
            exit;
        }

        $customer['id'] = (int)$customer['id'];
        echo json_encode([
            'success' => true,
            'customer' => $customer
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan saat mengambil profil: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 6. UPDATE PROFILE (POST)
// ==========================================
if ($action === 'update_profile') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Metode request tidak diizinkan. Gunakan POST.']);
        exit;
    }

    $customerId = requireCustomerLogin();
    $data = getRequestData();

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');
    $city = trim($data['city'] ?? '');
    $province = trim($data['province'] ?? '');
    $postal_code = trim($data['postal_code'] ?? '');

    if (empty($name) || empty($phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nama dan nomor telepon wajib diisi!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE customers
            SET name = ?, phone = ?, address = ?, city = ?, province = ?, postal_code = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $name,
            $phone,
            $address,
            $city,
            $province,
            $postal_code,
            $customerId
        ]);

        // Update session name if changed
        $_SESSION['customer_name'] = $name;

        echo json_encode([
            'success' => true,
            'message' => 'Profil berhasil diperbarui!',
            'customer' => [
                'id' => $customerId,
                'name' => $name,
                'email' => $_SESSION['customer_email'] ?? '',
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'province' => $province,
                'postal_code' => $postal_code
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan saat memperbarui profil: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 7. CHANGE PASSWORD (POST)
// ==========================================
if ($action === 'change_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Metode request tidak diizinkan. Gunakan POST.']);
        exit;
    }

    $customerId = requireCustomerLogin();
    $data = getRequestData();

    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['error' => 'Password saat ini dan password baru wajib diisi!']);
        exit;
    }

    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password baru minimal harus 6 karakter!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT password FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        if (!$customer || !password_verify($currentPassword, $customer['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Password saat ini tidak sesuai!']);
            exit;
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $pdo->prepare("UPDATE customers SET password = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$hashedNewPassword, $customerId]);

        echo json_encode([
            'success' => true,
            'message' => 'Password berhasil diubah!'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan saat mengubah password: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// DEFAULT / INVALID ACTION
// ==========================================
http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
exit;
