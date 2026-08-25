<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Fatal: ' . $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database/db.php';
$pdo = getDB();

function getInput() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// === CHECK SESSION ===
if ($action === 'check_session') {
    if (!empty($_SESSION['customer_id'])) {
        echo json_encode(['logged_in' => true, 'customer' => [
            'id' => (int)$_SESSION['customer_id'],
            'name' => $_SESSION['customer_name'] ?? '',
            'email' => $_SESSION['customer_email'] ?? ''
        ]]);
    } else {
        echo json_encode(['logged_in' => false, 'customer' => null]);
    }
    exit;
}

// === REGISTER ===
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Use POST']); exit;
    }
    $d = getInput();
    $name = trim($d['name'] ?? '');
    $email = trim($d['email'] ?? '');
    $phone = trim($d['phone'] ?? '');
    $password = $d['password'] ?? '';
    $address = trim($d['address'] ?? '');
    $city = trim($d['city'] ?? '');
    $province = trim($d['province'] ?? '');
    $postal_code = trim($d['postal_code'] ?? $d['zip'] ?? '');

    if (!$name || !$email || !$phone || !$password) {
        echo json_encode(['success' => false, 'error' => 'Nama, email, telepon, dan password wajib diisi!']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Format email tidak valid!']); exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password minimal 6 karakter!']); exit;
    }

    try {
        $chk = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email sudah terdaftar!']); exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins = $pdo->prepare("INSERT INTO customers (name, email, phone, password_hash, address, city, province, postal_code, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())");
        $ins->execute([$name, $email, $phone, $hash, $address, $city, $province, $postal_code]);
        $cid = (int)$pdo->lastInsertId();

        $_SESSION['customer_id'] = $cid;
        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_email'] = $email;

        echo json_encode(['success' => true, 'message' => 'Registrasi berhasil!', 'customer' => [
            'id' => $cid, 'name' => $name, 'email' => $email, 'phone' => $phone
        ]]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Gagal registrasi: ' . $e->getMessage()]);
    }
    exit;
}

// === LOGIN ===
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Use POST']); exit;
    }
    $d = getInput();
    $email = trim($d['email'] ?? '');
    $password = $d['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'error' => 'Email dan password wajib diisi!']); exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $c = $stmt->fetch();
        $stored = $c['password_hash'] ?? $c['password'] ?? '';

        if (!$c || !$stored || !password_verify($password, $stored)) {
            echo json_encode(['success' => false, 'error' => 'Email atau password salah!']); exit;
        }

        $_SESSION['customer_id'] = (int)$c['id'];
        $_SESSION['customer_name'] = $c['name'];
        $_SESSION['customer_email'] = $c['email'];

        echo json_encode(['success' => true, 'message' => 'Login berhasil!', 'customer' => [
            'id' => (int)$c['id'], 'name' => $c['name'], 'email' => $c['email'], 'phone' => $c['phone'] ?? ''
        ]]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Gagal login: ' . $e->getMessage()]);
    }
    exit;
}

// === GOOGLE AUTH ===
if ($action === 'google_auth') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Use POST']); exit;
    }
    $d = getInput();
    $credential = $d['credential'] ?? '';
    $email = trim($d['email'] ?? '');
    $name = trim($d['name'] ?? '');

    if (!empty($credential)) {
        $parts = explode('.', $credential);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-','_'],['+','/'], $parts[1])), true);
            if ($payload && !empty($payload['email'])) {
                $email = trim($payload['email']);
                $name = trim($payload['name'] ?? explode('@', $email)[0]);
            }
        }
    }

    if (!$email) {
        echo json_encode(['success' => false, 'error' => 'Data akun Google tidak valid!']); exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $c = $stmt->fetch();

        if ($c) {
            $_SESSION['customer_id'] = (int)$c['id'];
            $_SESSION['customer_name'] = $c['name'];
            $_SESSION['customer_email'] = $c['email'];
            echo json_encode(['success' => true, 'is_new' => false, 'message' => 'Login Google berhasil!', 'customer' => [
                'id' => (int)$c['id'], 'name' => $c['name'], 'email' => $c['email']
            ]]);
        } else {
            $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
            $phone = trim($d['phone'] ?? '-');
            $ins = $pdo->prepare("INSERT INTO customers (name, email, phone, password_hash, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())");
            $ins->execute([$name, $email, $phone, $hash]);
            $cid = (int)$pdo->lastInsertId();

            $_SESSION['customer_id'] = $cid;
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;

            echo json_encode(['success' => true, 'is_new' => true, 'message' => 'Pendaftaran Google berhasil!', 'customer' => [
                'id' => $cid, 'name' => $name, 'email' => $email
            ]]);
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Gagal Google auth: ' . $e->getMessage()]);
    }
    exit;
}

// === LOGOUT ===
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logout berhasil!']);
    exit;
}

// === GET PROFILE ===
if ($action === 'get_profile') {
    if (empty($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'error' => 'Silakan login terlebih dahulu.']); exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, address, city, province, postal_code, created_at FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $c = $stmt->fetch();
        if ($c) { $c['id'] = (int)$c['id']; }
        echo json_encode(['success' => true, 'customer' => $c ?: null]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// === UPDATE PROFILE ===
if ($action === 'update_profile') {
    if (empty($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'error' => 'Silakan login terlebih dahulu.']); exit;
    }
    $d = getInput();
    try {
        $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, address=?, city=?, province=?, postal_code=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([
            trim($d['name'] ?? ''), trim($d['phone'] ?? ''), trim($d['address'] ?? ''),
            trim($d['city'] ?? ''), trim($d['province'] ?? ''), trim($d['postal_code'] ?? ''),
            $_SESSION['customer_id']
        ]);
        $_SESSION['customer_name'] = trim($d['name'] ?? '');
        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui!']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// === CHANGE PASSWORD ===
if ($action === 'change_password') {
    if (empty($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'error' => 'Silakan login terlebih dahulu.']); exit;
    }
    $d = getInput();
    $cur = $d['current_password'] ?? '';
    $new = $d['new_password'] ?? '';
    if (!$cur || !$new || strlen($new) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password tidak valid!']); exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT password_hash FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $c = $stmt->fetch();
        if (!$c || !password_verify($cur, $c['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'Password lama salah!']); exit;
        }
        $pdo->prepare("UPDATE customers SET password_hash=?, updated_at=NOW() WHERE id=?")->execute([
            password_hash($new, PASSWORD_BCRYPT), $_SESSION['customer_id']
        ]);
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah!']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action: ' . htmlspecialchars($action)]);
