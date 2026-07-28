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

// === PROTECTED ROUTES BELOW ===
if (!isset($_SESSION['is_admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
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

function getDirSize($dir) {
    $size = 0;
    if (!is_dir($dir)) return 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        $size += $file->getSize();
    }
    return $size;
}

if ($action === 'get_storage') {
    $settings = getJson('settings', []);
    $total_gb = isset($settings['storage_quota_gb']) ? (float)$settings['storage_quota_gb'] : 2.0;
    $total_bytes = $total_gb * 1073741824;
    
    $web_root = realpath(__DIR__ . '/../');
    $used_bytes = getDirSize($web_root);
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

// Upload Media Endpoint
if ($action === 'upload_media') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('media_') . '.' . $ext;
        if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0777, true);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $UPLOAD_DIR . $filename)) {
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
        'slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name))),
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
        'description' => $description,
        'features' => $features,
        'specs' => $specs
    ];

    $products = readData();
    $found = false;
    foreach ($products as $i => $p) {
        if ($p['id'] === $id) {
            $products[$i] = $product;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $products[] = $product;
    }

    writeData($products);
    echo json_encode(['success' => true, 'product' => $product]);
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
