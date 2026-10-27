<?php
session_start();
header('Content-Type: application/json');

$DATA_JSON = __DIR__ . '/../data/products.json';
$DATA_JS = __DIR__ . '/../data/products.js';
$UPLOAD_DIR = __DIR__ . '/../images/';
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'asianindo123';

$action = $_GET['action'] ?? '';

// CORS for tracking if needed
header("Access-Control-Allow-Origin: *");

// === PUBLIC ROUTES ===

if ($action === 'login') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
        $_SESSION['is_admin'] = true;
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Username atau Password salah!']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'check_session') {
    echo json_encode(['is_admin' => isset($_SESSION['is_admin'])]);
    exit;
}

// PUBLIC READ - allows frontend pages to load CMS content without login
if ($action === 'get_public') {
    $module = $_GET['module'] ?? '';
    $allowed = ['settings', 'homepage', 'about', 'contact', 'articles'];
    if (in_array($module, $allowed)) {
        $path = __DIR__ . '/../data/' . $module . '.json';
        if (file_exists($path)) {
            echo file_get_contents($path);
        } else {
            echo json_encode($module === 'articles' ? [] : new stdClass());
        }
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Invalid module']);
    exit;
}

if ($action === 'heal_catalog') {
    $products = readData();
    echo json_encode(['success' => true, 'count' => count($products), 'message' => 'Katalog produk berhasil diperiksa & diselaraskan']);
    exit;
}

if ($action === 'get_product_views') {
    header('Content-Type: application/json');
    $analyticsFile = __DIR__ . '/../data/analytics.json';
    $views = [];
    if (file_exists($analyticsFile)) {
        $parsed = json_decode(file_get_contents($analyticsFile), true);
        if (isset($parsed['product_views']) && is_array($parsed['product_views'])) {
            $views = $parsed['product_views'];
        }
    }
    echo json_encode(['success' => true, 'product_views' => $views]);
    exit;
}

if ($action === 'track_event') {
    // Only accept POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        exit;
    }
    
    // Parse JSON input since fetch might send JSON
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true) ?? [];
    
    $event = $input['event'] ?? $_POST['event'] ?? ''; 
    $productId = $input['product_id'] ?? $_POST['product_id'] ?? '';
    
    $analyticsFile = __DIR__ . '/../data/analytics.json';
    $data = ['visitors' => 0, 'wa_clicks' => 0, 'messages' => 0, 'product_views' => []];
    
    if (file_exists($analyticsFile)) {
        $parsed = json_decode(file_get_contents($analyticsFile), true);
        if (is_array($parsed)) $data = array_merge($data, $parsed);
    }
    
    if (!isset($data['history'])) $data['history'] = [];
    $today = date('Y-m-d');
    if (!isset($data['history'][$today])) {
        $data['history'][$today] = ['visitors' => 0, 'wa_clicks' => 0];
    }
    
    if ($event === 'visitor') {
        $data['visitors']++;
        $data['history'][$today]['visitors']++;
    } elseif ($event === 'wa_click') {
        $data['wa_clicks']++;
        $data['history'][$today]['wa_clicks']++;
    } elseif ($event === 'message') {
        $data['messages']++;
    } elseif ($event === 'product_view' && $productId) {
        if (!isset($data['product_views'][$productId])) {
            $data['product_views'][$productId] = 0;
        }
        $data['product_views'][$productId]++;
    }
    
    if (!is_dir(dirname($analyticsFile))) mkdir(dirname($analyticsFile), 0777, true);
    file_put_contents($analyticsFile, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'track_article_view') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true) ?? [];
    $articleId = $input['id'] ?? '';
    
    if ($articleId) {
        $path = __DIR__ . '/../data/articles.json';
        if (file_exists($path)) {
            // Read and increment
            $articles = json_decode(file_get_contents($path), true) ?? [];
            foreach ($articles as &$a) {
                if (isset($a['id']) && $a['id'] === $articleId) {
                    $a['views'] = ($a['views'] ?? 0) + 1;
                    break;
                }
            }
            file_put_contents($path, json_encode($articles, JSON_PRETTY_PRINT));
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// === PROTECTED ROUTES BELOW ===
if (!isset($_SESSION['is_admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../database/db.php';

if ($action === 'get_admin_orders') {
    $db = getDB();
    $status = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';

    $where = [];
    $params = [];

    if ($status !== 'all') {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }

    if (!empty($search)) {
        $where[] = '(o.order_number LIKE ? OR c.name LIKE ? OR o.shipping_name LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $db->prepare("
        SELECT o.id, o.order_number, o.status, o.total, o.total as total_amount, o.created_at, 
               c.name as customer_name, c.email as customer_email,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
               p.status as payment_status, p.bank_name, p.payment_gateway, p.payment_method_code, 
               p.payment_fee, p.amount as bill_amount, p.midtrans_transaction_id, p.midtrans_snap_token,
               s.status as shipment_status
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        LEFT JOIN payments p ON o.id = p.order_id
        LEFT JOIN shipments s ON o.id = s.order_id
        $whereClause
        ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'get_admin_order_detail') {
    $db = getDB();
    $order_id = $_GET['order_id'] ?? null;
    $order_number = $_GET['order_number'] ?? null;
    
    if (!$order_id && !$order_number) {
        http_response_code(400); echo json_encode(['error' => 'Missing order ID']); exit;
    }

    $where = $order_id ? "o.id = ?" : "o.order_number = ?";
    $param = $order_id ? $order_id : $order_number;

    $stmt = $db->prepare("
        SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE $where
    ");
    $stmt->execute([$param]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404); echo json_encode(['error' => 'Order not found']); exit;
    }

    $stmtItems = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmtItems->execute([$order['id']]);
    $order['items'] = $stmtItems->fetchAll();

    $stmtPay = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    $stmtPay->execute([$order['id']]);
    $order['payment'] = $stmtPay->fetch();

    $stmtShip = $db->prepare("SELECT * FROM shipments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    $stmtShip->execute([$order['id']]);
    $order['shipment'] = $stmtShip->fetch();

    echo json_encode($order);
    exit;
}

if ($action === 'verify_payment') {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? null;
    $status = $input['status'] ?? null;
    $admin_notes = $input['admin_notes'] ?? '';

    if (!$order_id || !in_array($status, ['verified', 'rejected'])) {
        http_response_code(400); echo json_encode(['error' => 'Invalid data']); exit;
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("UPDATE payments SET status = ?, admin_notes = ?, verified_at = NOW() WHERE order_id = ?");
        $stmt->execute([$status, $admin_notes, $order_id]);

        $order_status = $status === 'verified' ? 'payment_verified' : 'pending_payment';
        $stmtOrd = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmtOrd->execute([$order_status, $order_id]);

        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_shipment') {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? null;
    $tracking_number = $input['tracking_number'] ?? '';
    $expedition = $input['expedition'] ?? 'Indah Kargo';
    $status = $input['status'] ?? 'preparing';
    $estimated_arrival = $input['estimated_arrival'] ?? null;
    $notes = $input['notes'] ?? '';

    if (!$order_id) {
        http_response_code(400); echo json_encode(['error' => 'Missing order ID']); exit;
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT id FROM shipments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $update = $db->prepare("UPDATE shipments SET tracking_number=?, expedition=?, status=?, estimated_arrival=?, notes=?, updated_at=NOW() WHERE order_id=?");
            $update->execute([$tracking_number, $expedition, $status, $estimated_arrival, $notes, $order_id]);
        } else {
            $insert = $db->prepare("INSERT INTO shipments (order_id, tracking_number, expedition, status, estimated_arrival, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$order_id, $tracking_number, $expedition, $status, $estimated_arrival, $notes]);
        }

        if (in_array($status, ['shipped', 'delivered'])) {
            $updOrder = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $updOrder->execute([$status, $order_id]);
        }

        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_order_status') {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? null;
    $status = $input['status'] ?? null;

    if (!$order_id || !$status) {
        http_response_code(400); echo json_encode(['error' => 'Invalid data']); exit;
    }

    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $order_id])) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500); echo json_encode(['error' => 'Failed to update order status']);
    }
    exit;
}

if ($action === 'get_ecommerce_stats') {
    $db = getDB();
    $stats = [
        'total_orders' => (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'pending_payment' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_payment'")->fetchColumn(),
        'pending_verifications' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'payment_uploaded'")->fetchColumn(),
        'active_shipments' => (int)$db->query("SELECT COUNT(*) FROM shipments WHERE status IN ('preparing', 'shipped', 'in_transit')")->fetchColumn(),
        'total_sales' => (int)($db->query("SELECT SUM(total) FROM orders WHERE status NOT IN ('cancelled', 'pending_payment', 'payment_uploaded')")->fetchColumn() ?? 0),
        'total_customers' => (int)$db->query("SELECT COUNT(*) FROM customers")->fetchColumn()
    ];
    echo json_encode($stats);
    exit;
}

if ($action === 'get_admin_customers') {
    $db = getDB();
    $search = trim($_GET['search'] ?? '');
    
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = "(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.city LIKE ? OR c.province LIKE ?)";
        $term = "%$search%";
        $params = [$term, $term, $term, $term, $term];
    }
    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "
        SELECT c.id, c.name, c.email, c.phone, c.address, c.city, c.province, c.postal_code, c.created_at,
               COUNT(o.id) as order_count,
               COALESCE(SUM(CASE WHEN o.status NOT IN ('cancelled', 'pending_payment') THEN o.total ELSE 0 END), 0) as total_spent,
               MAX(o.created_at) as last_order_date
        FROM customers c
        LEFT JOIN orders o ON c.id = o.customer_id
        $whereClause
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'get_admin_customer_detail') {
    $db = getDB();
    $customer_id = (int)($_GET['customer_id'] ?? 0);
    if (!$customer_id) {
        http_response_code(400); echo json_encode(['error' => 'Missing customer ID']); exit;
    }
    
    $stmt = $db->prepare("
        SELECT id, name, email, phone, address, city, province, postal_code, created_at
        FROM customers
        WHERE id = ?
    ");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    if (!$customer) {
        http_response_code(404); echo json_encode(['error' => 'Customer not found']); exit;
    }
    
    $stmtOrders = $db->prepare("
        SELECT o.id, o.order_number, o.status, o.total, o.created_at,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        WHERE o.customer_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmtOrders->execute([$customer_id]);
    $customer['orders'] = $stmtOrders->fetchAll();
    
    echo json_encode($customer);
    exit;
}

if ($action === 'delete_customer') {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $customer_id = (int)($input['customer_id'] ?? 0);
    if (!$customer_id) {
        http_response_code(400); echo json_encode(['error' => 'ID Pelanggan tidak valid']); exit;
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $orderCount = (int)$stmt->fetchColumn();
    if ($orderCount > 0) {
        http_response_code(400); echo json_encode(['error' => 'Pelanggan memiliki pesanan aktif dan tidak dapat dihapus.']); exit;
    }
    
    $del = $db->prepare("DELETE FROM customers WHERE id = ?");
    $del->execute([$customer_id]);
    echo json_encode(['success' => true]);
    exit;
}

// Helper functions for general JSON
function getJson($filename, $default = []) {
    $path = __DIR__ . '/../data/' . $filename . '.json';
    if (file_exists($path)) {
        $content = file_get_contents($path);
        return json_decode($content, true) ?? $default;
    }
    return $default;
}

function saveJson($filename, $data) {
    $path = __DIR__ . '/../data/' . $filename . '.json';
    $jsPath = __DIR__ . '/../data/' . $filename . '.js';
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($path, $json);
    file_put_contents($jsPath, "window." . strtoupper($filename) . "_DATA = " . $json . ";");
}

// Data modules generic endpoints
$allowed_modules = ['settings', 'articles', 'homepage', 'about', 'contact'];

if ($action === 'get_module') {
    $module = $_GET['module'] ?? '';
    if (in_array($module, $allowed_modules)) {
        echo json_encode(getJson($module, $module === 'articles' ? [] : new stdClass()));
        exit;
    }
}

if ($action === 'save_module') {
    $module = $_GET['module'] ?? '';
    if (in_array($module, $allowed_modules)) {
        $jsonPayload = file_get_contents('php://input');
        $data = json_decode($jsonPayload, true);
        if ($data !== null) {
            saveJson($module, $data);
            echo json_encode(['success' => true]);
            exit;
        }
    }
    http_response_code(400);
    echo json_encode(['error' => 'Invalid module or data']);
    exit;
}

if ($action === 'get_analytics') {
    $analytics = getJson('analytics', ['visitors' => 0, 'wa_clicks' => 0, 'messages' => 0, 'product_views' => []]);
    echo json_encode($analytics);
    exit;
}



// Media Library Endpoints
if ($action === 'get_media') {
    if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0777, true);
    $files = array_diff(scandir($UPLOAD_DIR), ['.', '..']);
    $media = [];
    foreach ($files as $file) {
        $path = $UPLOAD_DIR . $file;
        if (is_file($path)) {
            $media[] = [
                'name' => $file,
                'path' => 'images/' . $file,
                'size' => filesize($path),
                'mtime' => filemtime($path)
            ];
        }
    }
    usort($media, function($a, $b) { return $b['mtime'] <=> $a['mtime']; });
    echo json_encode($media);
    exit;
}

if ($action === 'delete_media') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true) ?? [];
    $filename = $input['filename'] ?? $_POST['filename'] ?? '';
    if ($filename && strpos($filename, '..') === false) {
        $path = $UPLOAD_DIR . basename($filename);
        if (file_exists($path) && is_file($path)) {
            unlink($path);
            echo json_encode(['success' => true]);
            exit;
        }
    }
    http_response_code(400);
    echo json_encode(['error' => 'File not found or invalid']);
    exit;
}

if ($action === 'upload_media') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0777, true);
        
        $tmp = $_FILES['file']['tmp_name'];
        $origName = $_FILES['file']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        
        // Image processing (GD required)
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
        
        if ($isImage && function_exists('imagecreatefromjpeg')) {
            $filename = uniqid('media_') . '.webp';
            $destPath = $UPLOAD_DIR . $filename;
            
            if ($ext === 'jpg' || $ext === 'jpeg') $img = @imagecreatefromjpeg($tmp);
            elseif ($ext === 'png') $img = @imagecreatefrompng($tmp);
            elseif ($ext === 'webp') $img = @imagecreatefromwebp($tmp);
            else $img = false;
            
            if ($img !== false) {
                $width = imagesx($img);
                $height = imagesy($img);
                $maxWidth = 1200;
                
                if ($width > $maxWidth) {
                    $newWidth = $maxWidth;
                    $newHeight = floor($height * ($maxWidth / $width));
                    $newImg = imagecreatetruecolor($newWidth, $newHeight);
                    
                    // Handle transparency for PNG
                    if ($ext === 'png' || $ext === 'webp') {
                        imagealphablending($newImg, false);
                        imagesavealpha($newImg, true);
                        $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
                        imagefilledrectangle($newImg, 0, 0, $newWidth, $newHeight, $transparent);
                    }
                    
                    imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($img);
                    $img = $newImg;
                }
                
                if (function_exists('imagewebp')) {
                    imagewebp($img, $destPath, 80);
                } else {
                    $filename = uniqid('media_') . '.jpg';
                    $destPath = $UPLOAD_DIR . $filename;
                    imagejpeg($img, $destPath, 80);
                }
                imagedestroy($img);
                
                echo json_encode(['success' => true, 'path' => 'images/' . $filename]);
                exit;
            }
        }
        
        // Video file upload handling (store in videos/ directory with public permissions)
        if (in_array($ext, ['mp4', 'webm', 'mov'])) {
            $videoDir = __DIR__ . '/../videos/';
            if (!is_dir($videoDir)) mkdir($videoDir, 0777, true);
            $filename = uniqid('video_') . '.' . $ext;
            if (move_uploaded_file($tmp, $videoDir . $filename)) {
                @chmod($videoDir . $filename, 0644);
                echo json_encode(['success' => true, 'path' => 'videos/' . $filename]);
                exit;
            }
        }

        // Fallback for non-images or if GD fails
        $filename = uniqid('media_') . '.' . $ext;
        if (move_uploaded_file($tmp, $UPLOAD_DIR . $filename)) {
            @chmod($UPLOAD_DIR . $filename, 0644);
            echo json_encode(['success' => true, 'path' => 'images/' . $filename]);
            exit;
        }
    }
    http_response_code(400);
    echo json_encode(['error' => 'Upload failed']);
    exit;
}

// Product Data Functions with Auto-Healing
function healProductImages(&$products) {
    $healed = false;
    foreach ($products as &$p) {
        if (!isset($p['id'])) continue;
        $id = (string)$p['id'];
        if (isset($p['images']) && is_array($p['images'])) {
            foreach ($p['images'] as $idx => $img) {
                if (is_string($img)) {
                    $isBrokenMedia = (strpos($img, 'media_6ac') !== false);
                    $fileMissing = (strpos($img, 'images/media_') === 0 && !file_exists(__DIR__ . '/../' . $img));
                    if ($isBrokenMedia || $fileMissing) {
                        $p['images'][$idx] = 'images/products/' . $id . '_' . $idx . '.webp';
                        $healed = true;
                    }
                }
            }
        } elseif (!isset($p['images']) || empty($p['images'])) {
            $p['images'] = ['images/products/' . $id . '_0.webp'];
            $healed = true;
        }

        // Auto-heal broken video paths
        $isVacuum = (stripos(($p['name'] ?? '') . ' ' . ($p['subCategory'] ?? ''), 'vacuum') !== false);
        if (isset($p['video']) && is_string($p['video'])) {
            $v = trim($p['video']);
                    $fileExists = ($v !== '' && file_exists(__DIR__ . '/../' . $v));
        $isBrokenVid = ($v !== '' && (!$fileExists || strpos($v, 'media_6ac') !== false || strpos($v, 'video_6ac') !== false));
            if ($isBrokenVid && $isVacuum) {
                $p['video'] = 'videos/promo_vacuum_frying.mp4';
                $healed = true;
            } elseif ($isBrokenVid && !file_exists(__DIR__ . '/../' . $v)) {
                $p['video'] = '';
                $healed = true;
            }
        } elseif ($isVacuum && empty($p['video'])) {
            $p['video'] = 'videos/promo_vacuum_frying.mp4';
            $healed = true;
        }

        // Auto-heal promo_image: default to leftmost cover photo if empty
        if ((!isset($p['promo_image']) || trim($p['promo_image']) === '') && !empty($p['images']) && is_array($p['images'])) {
            $p['promo_image'] = $p['images'][0];
            $healed = true;
        }

        // Auto-heal capacity: fix truncated capacity values (e.g. '1' -> '1,5 kg', '3' -> '3,5 kg')
        if (isset($p['capacity'])) {
            $capTrim = trim($p['capacity']);
            $pName = $p['name'] ?? '';
            if ($capTrim === '1' && (stripos($pName, '1,5') !== false || stripos($pName, '1.5') !== false)) {
                $p['capacity'] = '1,5 kg';
                $p['capacitySize'] = 'small';
                $healed = true;
            } elseif ($capTrim === '3' && (stripos($pName, '3,5') !== false || stripos($pName, '3.5') !== false)) {
                $p['capacity'] = '3,5 kg';
                $p['capacitySize'] = 'small';
                $healed = true;
            }
        }
    }
    return $healed;
}

function readData() {
    global $DATA_JSON;
    $products = [];
    if (!file_exists($DATA_JSON)) {
        $DEFAULT_JSON = __DIR__ . '/../default_products.json';
        if (file_exists($DEFAULT_JSON)) {
            $products = json_decode(file_get_contents($DEFAULT_JSON), true) ?? [];
        }
    } else {
        $products = json_decode(file_get_contents($DATA_JSON), true) ?? [];
    }

    if (!empty($products) && healProductImages($products)) {
        writeData($products);
    }

    return $products;
}

function writeData($data) {
    global $DATA_JSON, $DATA_JS;
    $dir = dirname($DATA_JSON);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $jsonString = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($DATA_JSON, $jsonString);
    file_put_contents($DATA_JS, "window.CATALOG_PRODUCTS = " . $jsonString . ";");
}

if ($action === 'get_products') {
    echo json_encode(readData());
    exit;
}

if ($action === 'save_product') {
    $id = $_POST['id'] ?? uniqid();
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $subCategory = $_POST['subCategory'] ?? '';
    $capacitySize = $_POST['capacitySize'] ?? 'medium';
    $capacity = $_POST['capacity'] ?? '';
    $price = (int)($_POST['price'] ?? 0);
    $priceRange = $_POST['priceRange'] ?? '';
    $description = $_POST['description'] ?? '';
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';
    $slug = $_POST['slug'] ?? '';

    
    // Parse images array from frontend
    $images = [];
    if (isset($_POST['images'])) {
        $imagesData = json_decode($_POST['images'], true);
        if (is_array($imagesData)) {
            $images = $imagesData;
        }
    }
    
    $videoPath = $_POST['existing_video'] ?? '';
    
    $features = [];
    if (isset($_POST['features']) && is_array($_POST['features'])) {
        foreach ($_POST['features'] as $feat) {
            if (trim($feat) !== '') $features[] = trim($feat);
        }
    }
    
    $specs = [];
    if (isset($_POST['specs_keys']) && is_array($_POST['specs_keys'])) {
        foreach ($_POST['specs_keys'] as $i => $key) {
            $val = $_POST['specs_vals'][$i] ?? '';
            if (trim($key) !== '' && trim($val) !== '') {
                $specs[trim($key)] = trim($val);
            }
        }
    }

    // Handle video upload (legacy, but keeping it)
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['video']['size'] > 15 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Ukuran video maksimal 15MB!']);
            exit;
        }
        $ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('vid_') . '.' . $ext;
        if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0777, true);
        move_uploaded_file($_FILES['video']['tmp_name'], $UPLOAD_DIR . $filename);
        $videoPath = 'images/' . $filename;
    }

    $product = [
        'id' => $id,
        'name' => $name,
        'slug' => $slug ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name))),
        'meta_title' => $meta_title,
        'meta_description' => $meta_description,
        'category' => $category,
        'subCategory' => $subCategory,
        'capacitySize' => $capacitySize,
        'capacity' => $capacity,
        'price' => $price,
        'priceRange' => $priceRange,
        'images' => $images,
        'video' => $videoPath,
        'rating' => 5,
        'reviews' => rand(5, 50),
        'sold' => rand(10, 100),
        'desc' => $description,
        'features' => $features,
        'specs' => $specs
    ];

    $products = readData();
    $found = false;
    foreach ($products as $i => $p) {
        if ($p['id'] === $id) {
            // Merge: keep existing fields, overwrite with new non-empty values
            $merged = $p;
            foreach ($product as $key => $val) {
                // Only overwrite if the new value is not empty/default,
                // or if the key is explicitly being set (like video, images)
                if ($key === 'images' || $key === 'video') {
                    // Always take the new value for media fields
                    $merged[$key] = $val;
                } elseif ($key === 'price' && $val > 0) {
                    $merged[$key] = $val;
                    // Recalculate priceDisplay when price changes
                    $merged['priceDisplay'] = 'Rp ' . number_format($val, 0, ',', '.');
                } elseif (is_string($val) && $val !== '') {
                    $merged[$key] = $val;
                } elseif (is_array($val) && !empty($val)) {
                    $merged[$key] = $val;
                }
                // Skip overwriting with empty values to preserve original data
            }
            // Ensure desc is synced (admin sends as 'description' field)
            if ($description !== '') {
                $merged['desc'] = $description;
            }
            $products[$i] = $merged;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        // New product: generate priceDisplay and waMsg
        $product['priceDisplay'] = 'Rp ' . number_format($price, 0, ',', '.');
        $product['waMsg'] = 'Halo, saya tertarik dengan ' . $name;
        $products[] = $product;
    }

    writeData($products);
    echo json_encode(['success' => true, 'product' => $found ? $products[$i] : $product]);
    exit;
}

if ($action === 'delete_product') {
    $id = $_POST['id'] ?? '';
    $products = readData();
    $products = array_values(array_filter($products, function($p) use ($id) {
        return $p['id'] !== $id;
    }));
    writeData($products);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'bulk_update_products') {
    $input = json_decode(file_get_contents('php://input'), true);
    $updates = $input['products'] ?? (is_array($input) ? $input : []);

    if (!is_array($updates) || empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tidak ada data produk yang dikirim']);
        exit;
    }

    $products = readData();
    $indexMap = [];
    foreach ($products as $idx => $p) {
        if (isset($p['id'])) {
            $indexMap[(string)$p['id']] = $idx;
        }
    }

    $updatedCount = 0;
    foreach ($updates as $item) {
        $id = isset($item['id']) ? (string)$item['id'] : '';
        if ($id === '' || !isset($indexMap[$id])) {
            continue;
        }

        $idx = $indexMap[$id];
        $current = $products[$idx];

        // Update fields if provided
        if (isset($item['name']) && trim($item['name']) !== '') {
            $current['name'] = trim($item['name']);
            $current['waMsg'] = 'Halo, saya tertarik dengan ' . $current['name'];
        }

        if (isset($item['price']) && is_numeric($item['price'])) {
            $current['price'] = (int)$item['price'];
            $current['priceDisplay'] = 'Rp ' . number_format($current['price'], 0, ',', '.');
        }

        if (isset($item['category']) && trim($item['category']) !== '') {
            $current['category'] = trim($item['category']);
        }

        if (isset($item['subCategory'])) {
            $current['subCategory'] = trim($item['subCategory']);
        }

        if (isset($item['capacity'])) {
            $current['capacity'] = trim($item['capacity']);
        }

        if (isset($item['capacitySize']) && trim($item['capacitySize']) !== '') {
            $current['capacitySize'] = trim($item['capacitySize']);
        }

        if (isset($item['priceRange'])) {
            $current['priceRange'] = trim($item['priceRange']);
        }

        if (isset($item['desc'])) {
            $current['desc'] = trim($item['desc']);
        } elseif (isset($item['description'])) {
            $current['desc'] = trim($item['description']);
        }

        $products[$idx] = $current;
        $updatedCount++;
    }

    writeData($products);
    echo json_encode([
        'success' => true,
        'updated_count' => $updatedCount,
        'message' => "Berhasil memperbarui $updatedCount produk secara massal!"
    ]);
    exit;
}

if ($action === 'bulk_update_media') {
    $input = json_decode(file_get_contents('php://input'), true);
    $updates = $input['updates'] ?? (is_array($input) ? $input : []);

    if (!is_array($updates) || empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tidak ada data produk yang dikirim']);
        exit;
    }

    $products = readData();
    $indexMap = [];
    foreach ($products as $idx => $p) {
        if (isset($p['id'])) {
            $indexMap[(string)$p['id']] = $idx;
        }
    }

    $updatedCount = 0;
    foreach ($updates as $item) {
        $id = isset($item['id']) ? (string)$item['id'] : '';
        if ($id === '' || !isset($indexMap[$id])) {
            continue;
        }

        $idx = $indexMap[$id];
        $current = $products[$idx];

        if (isset($item['images']) && is_array($item['images'])) {
            $cleanImages = [];
            foreach ($item['images'] as $img) {
                if (is_string($img) && trim($img) !== '') {
                    $cleanImages[] = trim($img);
                }
            }
            $current['images'] = $cleanImages;
        }

        if (array_key_exists('promo_image', $item)) {
            $current['promo_image'] = trim($item['promo_image'] ?? '');
        }

        if (array_key_exists('video', $item)) {
            $current['video'] = trim($item['video'] ?? '');
        }

        $products[$idx] = $current;
        $updatedCount++;
    }

    writeData($products);
    echo json_encode([
        'success' => true,
        'updated_count' => $updatedCount,
        'message' => "$updatedCount / " . count($updates) . " berhasil"
    ]);
    exit;
}

// ==========================================
// INVOICE & QUOTATION GENERATOR API
// ==========================================
$INVOICES_JSON = __DIR__ . '/../data/invoices.json';

function readInvoices() {
    global $INVOICES_JSON;
    if (!file_exists($INVOICES_JSON)) return [];
    $data = json_decode(file_get_contents($INVOICES_JSON), true);
    return is_array($data) ? $data : [];
}

function writeInvoices($invoices) {
    global $INVOICES_JSON;
    $dir = dirname($INVOICES_JSON);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($INVOICES_JSON, json_encode($invoices, JSON_PRETTY_PRINT));
}

if ($action === 'get_invoices') {
    echo json_encode(readInvoices());
    exit;
}

if ($action === 'save_invoice') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Data invoice tidak valid']);
        exit;
    }

    $invoices = readInvoices();
    $id = !empty($input['id']) ? $input['id'] : ('INV-' . date('Ymd-His') . '-' . rand(100, 999));
    $input['id'] = $id;
    $input['updated_at'] = date('Y-m-d H:i:s');
    if (empty($input['created_at'])) {
        $input['created_at'] = date('Y-m-d H:i:s');
    }

    $found = false;
    foreach ($invoices as $idx => $inv) {
        if ($inv['id'] === $id) {
            $invoices[$idx] = $input;
            $found = true;
            break;
        }
    }
    if (!$found) {
        array_unshift($invoices, $input);
    }

    writeInvoices($invoices);
    echo json_encode(['success' => true, 'id' => $id, 'invoice' => $input]);
    exit;
}

if ($action === 'delete_invoice') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? ($_POST['id'] ?? '');
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID Invoice tidak ditemukan']);
        exit;
    }

    $invoices = readInvoices();
    $invoices = array_values(array_filter($invoices, function($inv) use ($id) {
        return $inv['id'] !== $id;
    }));
    writeInvoices($invoices);
    echo json_encode(['success' => true]);
    exit;
}

// ==========================================
// KNOWLEDGE BASE & SMART Q&A ASSISTANT API
// ==========================================
$WA_KNOWLEDGE_JSON = __DIR__ . '/../data/whatsapp_knowledge.json';

function readWhatsAppKnowledge() {
    global $WA_KNOWLEDGE_JSON;
    if (!file_exists($WA_KNOWLEDGE_JSON)) return [];
    $data = json_decode(file_get_contents($WA_KNOWLEDGE_JSON), true);
    if (!is_array($data)) return [];
    foreach ($data as &$item) {
        if (empty($item['machine_category'])) $item['machine_category'] = $item['topic'] ?? ($item['machine_name'] ?? 'Umum');
        if (empty($item['topic'])) $item['topic'] = $item['machine_category'] ?? 'Umum';
        if (empty($item['answer'])) $item['answer'] = $item['content'] ?? '';
        if (empty($item['content'])) $item['content'] = $item['answer'] ?? '';
        if (empty($item['tags'])) $item['tags'] = [];
        elseif (is_string($item['tags'])) $item['tags'] = array_values(array_filter(array_map('trim', explode(',', $item['tags']))));
    }
    return $data;
}

function writeWhatsAppKnowledge($data) {
    global $WA_KNOWLEDGE_JSON;
    $dir = dirname($WA_KNOWLEDGE_JSON);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($WA_KNOWLEDGE_JSON, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($action === 'get_whatsapp_knowledge') {
    echo json_encode(['success' => true, 'data' => readWhatsAppKnowledge()]);
    exit;
}

if ($action === 'save_whatsapp_knowledge') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        $input = $_POST;
    }

    $content = trim($input['answer'] ?? ($input['content'] ?? ''));
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Konten jawaban/spesifikasi teknis tidak boleh kosong']);
        exit;
    }

    $list = readWhatsAppKnowledge();
    $id = !empty($input['id']) ? $input['id'] : ('wa_' . time() . '_' . rand(100, 999));
    $topic = trim($input['machine_category'] ?? ($input['topic'] ?? 'Umum'));
    $title = trim($input['title'] ?? 'Konsultasi Teknis');
    $question = trim($input['question'] ?? '');
    $sender = trim($input['sender'] ?? 'Owner CV Asianindo');
    $date = !empty($input['date']) ? $input['date'] : date('Y-m-d');
    
    $tagsRaw = $input['tags'] ?? '';
    if (is_array($tagsRaw)) {
        $tags = array_values(array_filter(array_map('trim', $tagsRaw)));
    } else {
        $tags = array_values(array_filter(array_map('trim', explode(',', strtolower($tagsRaw . ',' . $topic)))));
    }

    $entry = [
        'id' => $id,
        'topic' => $topic,
        'machine_category' => $topic,
        'machine_name' => 'Mesin ' . $topic . ' Asianindo',
        'title' => $title,
        'question' => $question,
        'content' => $content,
        'answer' => $content,
        'date' => $date,
        'sender' => $sender,
        'tags' => array_values(array_unique(array_filter($tags)))
    ];

    $found = false;
    foreach ($list as $idx => $item) {
        if ($item['id'] === $id) {
            $list[$idx] = array_merge($item, $entry);
            $found = true;
            break;
        }
    }
    if (!$found) {
        array_unshift($list, $entry);
    }

    writeWhatsAppKnowledge($list);
    echo json_encode(['success' => true, 'id' => $id, 'data' => $entry]);
    exit;
}

if ($action === 'delete_whatsapp_knowledge') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? ($_POST['id'] ?? '');
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID tidak ditemukan']);
        exit;
    }

    $list = readWhatsAppKnowledge();
    $list = array_values(array_filter($list, function($item) use ($id) {
        return $item['id'] !== $id;
    }));
    writeWhatsAppKnowledge($list);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'import_whatsapp_chat') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $rawText = trim($_POST['raw_chat'] ?? ($_POST['raw_text'] ?? ($input['raw_chat'] ?? ($input['raw_text'] ?? ''))));
    $defaultTopic = trim($_POST['topic'] ?? ($input['topic'] ?? 'Konsultasi Teknis'));

    if (empty($rawText)) {
        http_response_code(400);
        echo json_encode(['error' => 'Teks chat WhatsApp kosong']);
        exit;
    }

    $lines = explode("\n", $rawText);
    $parsedMessages = [];
    $currentMsg = null;

    $regex1 = '/^\[?(\d{1,2}[\/\.-]\d{1,2}[\/\.-]\d{2,4})[,\s]+(\d{1,2}[:\.]\d{1,2}(?:[:\.]\d{1,2})?)\]?\s*(?:-\s*)?([^:]+):\s*(.*)$/i';

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;

        if (preg_match($regex1, $trimmed, $matches)) {
            if ($currentMsg) {
                $parsedMessages[] = $currentMsg;
            }
            $currentMsg = [
                'date' => $matches[1],
                'time' => $matches[2],
                'sender' => trim($matches[3]),
                'text' => trim($matches[4])
            ];
        } else {
            if ($currentMsg) {
                $currentMsg['text'] .= "\n" . $trimmed;
            } else {
                $currentMsg = [
                    'date' => date('d/m/Y'),
                    'time' => date('H:i'),
                    'sender' => 'Catatan Teknis',
                    'text' => $trimmed
                ];
            }
        }
    }
    if ($currentMsg) {
        $parsedMessages[] = $currentMsg;
    }

    $knownMachines = [
        'spray dryer', 'vacuum frying', 'retort', 'pirolisis', 'evaporator',
        'spinner', 'pasteurisasi', 'uht', 'mixer', 'pasta', 'roasting',
        'kopi', 'madu', 'vco', 'keripik', 'penepung', 'diskmill', 'pengering', 'oven'
    ];

    $list = readWhatsAppKnowledge();
    $addedCount = 0;

    $chunkSize = 2;
    for ($i = 0; $i < count($parsedMessages); $i += $chunkSize) {
        $chunk = array_slice($parsedMessages, $i, $chunkSize);
        $fullText = implode("\n", array_map(function($m) {
            return '[' . $m['date'] . ' ' . $m['time'] . '] ' . $m['sender'] . ': ' . $m['text'];
        }, $chunk));

        $lowerText = strtolower($fullText);
        $detectedMachine = 'Umum';
        foreach ($knownMachines as $m) {
            if (strpos($lowerText, $m) !== false) {
                $detectedMachine = ucwords($m);
                break;
            }
        }

        $entryTitle = mb_substr($chunk[0]['text'], 0, 80);
        if (mb_strlen($chunk[0]['text']) > 80) $entryTitle .= '...';

        $tags = [$detectedMachine];
        if (strpos($lowerText, 'suhu') !== false || strpos($lowerText, 'derajat') !== false) $tags[] = 'suhu';
        if (strpos($lowerText, 'kapasitas') !== false || strpos($lowerText, 'liter') !== false || strpos($lowerText, 'kg') !== false) $tags[] = 'kapasitas';
        if (strpos($lowerText, 'watt') !== false || strpos($lowerText, 'listrik') !== false || strpos($lowerText, 'daya') !== false) $tags[] = 'daya';
        if (strpos($lowerText, 'harga') !== false) $tags[] = 'harga';

        $entry = [
            'id' => 'wa_' . time() . '_' . rand(100, 999),
            'topic' => $detectedMachine !== 'Umum' ? $detectedMachine : $defaultTopic,
            'machine_name' => 'Mesin ' . ($detectedMachine !== 'Umum' ? $detectedMachine : $defaultTopic) . ' Asianindo',
            'title' => $entryTitle,
            'content' => implode("\n\n", array_map(function($m) {
                return $m['sender'] . ': ' . $m['text'];
            }, $chunk)),
            'raw_chat' => $fullText,
            'date' => date('Y-m-d'),
            'sender' => $chunk[count($chunk)-1]['sender'] ?? 'Owner CV Asianindo',
            'tags' => array_values(array_unique(array_filter($tags)))
        ];

        array_unshift($list, $entry);
        $addedCount++;
    }

    writeWhatsAppKnowledge($list);
    echo json_encode([
        'success' => true,
        'added_count' => $addedCount,
        'total_parsed_messages' => count($parsedMessages),
        'message' => "Berhasil mengekstrak dan menyimpan $addedCount entri percakapan WhatsApp ke database!"
    ]);
    exit;
}

if ($action === 'search_knowledge') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $query = trim($_POST['query'] ?? ($input['query'] ?? ($_GET['q'] ?? ($_GET['query'] ?? ''))));
    
    $includeProducts = true;
    if (isset($_POST['include_products'])) {
        $includeProducts = ($_POST['include_products'] === '1' || $_POST['include_products'] === 'true');
    } elseif (isset($input['include_products'])) {
        $includeProducts = (bool)$input['include_products'];
    }

    $includeWhatsapp = true;
    if (isset($_POST['include_whatsapp'])) {
        $includeWhatsapp = ($_POST['include_whatsapp'] === '1' || $_POST['include_whatsapp'] === 'true');
    } elseif (isset($input['include_whatsapp'])) {
        $includeWhatsapp = (bool)$input['include_whatsapp'];
    }

    $sources = [];
    if ($includeProducts) $sources[] = 'products';
    if ($includeWhatsapp) $sources[] = 'whatsapp';
    $sources[] = 'articles';

    if (empty($query)) {
        http_response_code(400);
        echo json_encode(['error' => 'Pertanyaan atau kata kunci pencarian tidak boleh kosong']);
        exit;
    }

    $lowerQuery = strtolower($query);

    $stopwords = [
        'berapa', 'apa', 'apakah', 'bagaimana', 'mengapa', 'kenapa', 'siapa', 'dimana',
        'mesin', 'dari', 'yang', 'dan', 'atau', 'pada', 'untuk', 'di', 'ke', 'adalah',
        'ini', 'itu', 'bisa', 'tolong', 'cek', 'info', 'spesifikasi', 'spek', 'kak',
        'pak', 'gan', 'mas', 'min', 'ya', 'sih', 'dong', 'kah', 'ada'
    ];

    $rawWords = preg_split('/[\s,\.\?!;:\(\)\-_]+/', $lowerQuery);
    $keywords = [];
    foreach ($rawWords as $w) {
        $w = trim($w);
        if (strlen($w) > 1 && !in_array($w, $stopwords)) {
            $keywords[] = $w;
        }
    }

    $machineEntities = [
        'spray dryer' => ['spray dryer', 'spray dry', 'pengering semprot'],
        'vacuum frying' => ['vacuum frying', 'vacum frying', 'keripik buah', 'penggoreng hampa'],
        'retort' => ['retort', 'sterilisasi', 'sterilisator', 'autoclave', 'kaleng', 'pouch'],
        'pirolisis' => ['pirolisis', 'pyrolysis', 'asap cair', 'batok kelapa', 'biochar'],
        'evaporator' => ['evaporator', 'evaporasi', 'pengental madu', 'vco'],
        'spinner' => ['spinner', 'peniris minyak', 'sentrifugal'],
        'uht' => ['uht', 'sterilisasi uht', 'ultra high temperature'],
        'pasteurisasi' => ['pasteurisasi', 'pasteurizer', 'susu pasteurisasi'],
        'diskmill' => ['diskmill', 'penepung', 'gilingan tepung'],
        'roasting' => ['roasting', 'sangrai', 'roaster kopi'],
        'mixer' => ['mixer', 'pencampur', 'ribbon mixer'],
        'pelet' => ['pelet', 'pellet', 'pakan ternak'],
        'cup sealer' => ['cup sealer', 'sealer cup', 'penutup cup']
    ];

    $detectedMachine = null;
    foreach ($machineEntities as $key => $synonyms) {
        foreach ($synonyms as $syn) {
            if (strpos($lowerQuery, $syn) !== false) {
                $detectedMachine = $key;
                break 2;
            }
        }
    }

    $intentAttributes = [
        'suhu' => ['suhu', 'panas', 'temperatur', 'derajat', '°c', 'celcius', 'c', 'inlet', 'outlet', 'heater', 'pemanas', 'thermal', 'steam'],
        'daya' => ['daya', 'listrik', 'watt', 'volt', 'hp', 'phase', 'kw', 'kwh', 'ampere'],
        'kapasitas' => ['kapasitas', 'liter', 'ltr', 'kg', 'batch', 'volume', 'jam', 'ukuran'],
        'material' => ['material', 'bahan', 'stainless', 'plat', 'sus 304', 'sus 316', 'food grade'],
        'harga' => ['harga', 'biaya', 'tarif', 'rp', 'juta', 'ribu', 'nilai'],
        'cara_kerja' => ['cara kerja', 'prinsip', 'metode', 'proses', 'fungsi', 'tata cara'],
        'garansi' => ['garansi', 'servis', 'service', 'sparepart', 'rusak', 'jaminan']
    ];

    $detectedIntents = [];
    foreach ($intentAttributes as $intent => $terms) {
        foreach ($terms as $term) {
            if (strpos($lowerQuery, $term) !== false) {
                $detectedIntents[] = $intent;
                break;
            }
        }
    }

    // 1. SEARCH PRODUCTS
    $matchedProducts = [];
    if (in_array('products', $sources)) {
        $products = readData();
        foreach ($products as $p) {
            $score = 0;
            $pNameLower = strtolower($p['name'] ?? '');
            $pDescLower = strtolower($p['desc'] ?? '');
            $pCatLower = strtolower($p['category'] ?? '');
            $pSubLower = strtolower($p['subCategory'] ?? '');

            if ($detectedMachine) {
                foreach ($machineEntities[$detectedMachine] as $syn) {
                    if (strpos($pNameLower, $syn) !== false || strpos($pSubLower, $syn) !== false) {
                        $score += 50;
                        break;
                    }
                }
            }

            foreach ($keywords as $kw) {
                if (strpos($pNameLower, $kw) !== false) $score += 20;
                if (strpos($pDescLower, $kw) !== false) $score += 6;
                if (strpos($pCatLower, $kw) !== false) $score += 10;
            }

            foreach ($detectedIntents as $intent) {
                foreach ($intentAttributes[$intent] as $term) {
                    if (strpos($pDescLower, $term) !== false) {
                        $score += 8;
                    }
                }
            }

            if ($score > 15) {
                $descLines = preg_split('/[\r\n•]+/', $p['desc'] ?? '');
                $matchedSnippets = [];
                foreach ($descLines as $line) {
                    $trimmedLine = trim($line);
                    if (empty($trimmedLine)) continue;
                    $lineLower = strtolower($trimmedLine);

                    $hasMatch = false;
                    foreach ($keywords as $kw) {
                        if (strpos($lineLower, $kw) !== false) { $hasMatch = true; break; }
                    }
                    if (!$hasMatch) {
                        foreach ($detectedIntents as $intent) {
                            foreach ($intentAttributes[$intent] as $term) {
                                if (strpos($lineLower, $term) !== false) { $hasMatch = true; break 2; }
                            }
                        }
                    }
                    if ($hasMatch) {
                        $matchedSnippets[] = $trimmedLine;
                    }
                }

                $matchedProducts[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'category' => $p['category'] ?? '',
                    'subCategory' => $p['subCategory'] ?? '',
                    'capacity' => $p['capacity'] ?? '',
                    'price' => $p['price'] ?? 0,
                    'priceDisplay' => $p['priceDisplay'] ?? '',
                    'image' => !empty($p['images'][0]) ? $p['images'][0] : '',
                    'score' => $score,
                    'snippets' => array_slice($matchedSnippets, 0, 4), 'matched_snippets' => array_slice($matchedSnippets, 0, 4)
                ];
            }
        }

        usort($matchedProducts, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        $matchedProducts = array_slice($matchedProducts, 0, 6);
    }

    // 2. SEARCH WHATSAPP KNOWLEDGE
    $matchedWhatsApp = [];
    if (in_array('whatsapp', $sources)) {
        $waKnowledge = readWhatsAppKnowledge();
        foreach ($waKnowledge as $wa) {
            $score = 0;
            $waTopicLower = strtolower($wa['topic'] ?? '');
            $waMachineLower = strtolower($wa['machine_name'] ?? '');
            $waTitleLower = strtolower($wa['title'] ?? '');
            $waContentLower = strtolower($wa['content'] ?? '');
            $waRawLower = strtolower($wa['raw_chat'] ?? '');
            $waTags = array_map('strtolower', $wa['tags'] ?? []);

            if ($detectedMachine) {
                foreach ($machineEntities[$detectedMachine] as $syn) {
                    if (strpos($waTopicLower, $syn) !== false || strpos($waMachineLower, $syn) !== false || in_array($syn, $waTags)) {
                        $score += 55;
                        break;
                    }
                }
            }

            foreach ($keywords as $kw) {
                if (strpos($waTitleLower, $kw) !== false) $score += 25;
                if (strpos($waContentLower, $kw) !== false) $score += 15;
                if (strpos($waRawLower, $kw) !== false) $score += 10;
                if (in_array($kw, $waTags)) $score += 20;
            }

            foreach ($detectedIntents as $intent) {
                foreach ($intentAttributes[$intent] as $term) {
                    if (strpos($waContentLower, $term) !== false || in_array($term, $waTags)) {
                        $score += 12;
                    }
                }
            }

            if ($score > 15) {
                $matchedWhatsApp[] = [
                    'id' => $wa['id'],
                    'topic' => $wa['topic'],
                    'machine_name' => $wa['machine_name'],
                    'title' => $wa['title'],
                    'content' => $wa['content'],
                    'answer' => $wa['answer'] ?? $wa['content'],
                    'question' => $wa['question'] ?? '',
                    'machine_category' => $wa['machine_category'] ?? $wa['topic'],
                    'tags' => $wa['tags'] ?? [],
                    'raw_chat' => $wa['raw_chat'] ?? '',
                    'date' => $wa['date'] ?? '',
                    'sender' => $wa['sender'] ?? 'Owner CV Asianindo',
                    'score' => $score
                ];
            }
        }

        usort($matchedWhatsApp, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        $matchedWhatsApp = array_slice($matchedWhatsApp, 0, 5);
    }

    // 3. SEARCH ARTICLES
    $matchedArticles = [];
    if (in_array('articles', $sources)) {
        $artPath = __DIR__ . '/../data/articles.json';
        if (file_exists($artPath)) {
            $articles = json_decode(file_get_contents($artPath), true) ?? [];
            foreach ($articles as $art) {
                $score = 0;
                $titleLower = strtolower($art['title'] ?? '');
                $contentLower = strtolower($art['content'] ?? '');

                foreach ($keywords as $kw) {
                    if (strpos($titleLower, $kw) !== false) $score += 20;
                    if (strpos($contentLower, $kw) !== false) $score += 5;
                }
                if ($score > 15) {
                    $matchedArticles[] = [
                        'id' => $art['id'] ?? '',
                        'title' => $art['title'] ?? '',
                        'excerpt' => $art['excerpt'] ?? mb_substr(strip_tags($art['content'] ?? ''), 0, 150) . '...',
                        'score' => $score
                    ];
                }
            }
            usort($matchedArticles, function($a, $b) { return $b['score'] - $a['score']; });
            $matchedArticles = array_slice($matchedArticles, 0, 3);
        }
    }

    // 4. SYNTHESIZE DIRECT ANSWER
    $directAnswer = null;
    $highlights = [];
    if (!empty($matchedWhatsApp)) {
        $topWa = $matchedWhatsApp[0];
        $highlights[] = "📌 **Catatan Teknis Owner WhatsApp (" . $topWa['topic'] . ")**:\n" . $topWa['content'];
    }
    if (!empty($matchedProducts)) {
        $topProd = $matchedProducts[0];
        $prodInfo = "🏭 **Spesifikasi Website (" . $topProd['name'] . ")**:\n";
        if (!empty($topProd['snippets'])) {
            foreach ($topProd['snippets'] as $s) {
                $prodInfo .= "• " . $s . "\n";
            }
        }
        if (!empty($topProd['priceDisplay'])) {
            $prodInfo .= "• Harga Estimasi: " . $topProd['priceDisplay'] . "\n";
        }
        $highlights[] = trim($prodInfo);
    }

    if (!empty($highlights)) {
        $directAnswer = implode("\n\n", $highlights);
    } else {
        $directAnswer = "Belum ditemukan informasi spesifik untuk pertanyaan: \"$query\". Silakan coba kata kunci lain atau masukkan potongan chat WhatsApp baru di tab 'Kelola Chat WhatsApp'.";
    }

    $keyHighlights = [];
    if (!empty($detectedMachine)) {
        $keyHighlights[] = "Mesin: " . ucwords($detectedMachine);
    }
    if (!empty($detectedIntents)) {
        foreach ($detectedIntents as $di) {
            $keyHighlights[] = "Parameter: " . ucwords(str_replace('_', ' ', $di));
        }
    }

    $directAnswerObj = [
        'headline' => !empty($detectedMachine) ? ("Spesifikasi " . ucwords($detectedMachine) . " Asianindo") : ("Hasil Pengetahuan: " . $query),
        'summary' => $directAnswer,
        'highlights' => $keyHighlights
    ];

    echo json_encode([
        'success' => true,
        'query' => $query,
        'detected_machine' => $detectedMachine ? ucwords($detectedMachine) : null,
        'detected_intents' => $detectedIntents,
        'direct_answer' => $directAnswerObj,
        'products_found' => $matchedProducts,
        'whatsapp_found' => $matchedWhatsApp,
        'articles_found' => $matchedArticles,
        'results_products' => $matchedProducts,
        'results_whatsapp' => $matchedWhatsApp,
        'results_articles' => $matchedArticles,
        'total_found' => count($matchedProducts) + count($matchedWhatsApp) + count($matchedArticles)
    ]);
    exit;
}


http_response_code(400);
echo json_encode(['error' => 'Invalid action']);

