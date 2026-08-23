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
    
    if ($event === 'visitor') $data['visitors']++;
    elseif ($event === 'wa_click') $data['wa_clicks']++;
    elseif ($event === 'message') $data['messages']++;
    elseif ($event === 'product_view' && $productId) {
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
        $where[] = '(o.order_number LIKE ? OR c.name LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $db->prepare("
        SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at, 
               c.name as customer_name, c.email as customer_email,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
               p.status as payment_status, s.status as shipment_status
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
        'total_orders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'pending_payment' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_payment'")->fetchColumn(),
        'pending_verifications' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'payment_uploaded'")->fetchColumn(),
        'active_shipments' => $db->query("SELECT COUNT(*) FROM shipments WHERE status IN ('preparing', 'shipped', 'in_transit')")->fetchColumn(),
        'total_sales' => $db->query("SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled', 'pending_payment', 'payment_uploaded')")->fetchColumn() ?? 0
    ];
    echo json_encode($stats);
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

function getAccountSize() {
    $dir = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] ? dirname($_SERVER['DOCUMENT_ROOT']) : realpath(__DIR__ . '/../');
    
    // Try shell_exec (fastest for Hostinger/Linux)
    if (function_exists('shell_exec')) {
        $output = @shell_exec('du -sb ' . escapeshellarg($dir) . ' 2>/dev/null');
        if ($output) {
            $parts = preg_split('/\s+/', trim($output));
            if (is_numeric($parts[0])) {
                return (float)$parts[0];
            }
        }
    }
    
    // Fallback to PHP recursion
    $size = 0;
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    } catch (Exception $e) {}
    return $size;
}

if ($action === 'get_storage') {
    $settings = getJson('settings', []);
    // Default to 100GB which is Hostinger Premium default, user can change in settings
    $total_gb = isset($settings['storage_quota_gb']) ? (float)$settings['storage_quota_gb'] : 100.0;
    $total_bytes = $total_gb * 1073741824;
    
    $used_bytes = getAccountSize();
    $free_bytes = max(0, $total_bytes - $used_bytes);
    
    echo json_encode([
        'total_gb' => $total_gb,
        'free_gb' => round($free_bytes / 1073741824, 2),
        'used_gb' => round($used_bytes / 1073741824, 3),
        'used_mb' => round($used_bytes / 1048576, 2),
        'percent_used' => $total_bytes > 0 ? round(($used_bytes / $total_bytes) * 100, 1) : 0
    ]);
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
        
        // Fallback for non-images or if GD fails
        $filename = uniqid('media_') . '.' . $ext;
        if (move_uploaded_file($tmp, $UPLOAD_DIR . $filename)) {
            echo json_encode(['success' => true, 'path' => 'images/' . $filename]);
            exit;
        }
    }
    http_response_code(400);
    echo json_encode(['error' => 'Upload failed']);
    exit;
}

// Legacy Product Functions (preserving for compatibility)
function readData() {
    global $DATA_JSON;
    if (!file_exists($DATA_JSON)) {
        $DEFAULT_JSON = __DIR__ . '/../default_products.json';
        if (file_exists($DEFAULT_JSON)) {
            return json_decode(file_get_contents($DEFAULT_JSON), true) ?? [];
        }
        return [];
    }
    return json_decode(file_get_contents($DATA_JSON), true) ?? [];
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

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
