<?php
session_start();
header('Content-Type: application/json');

$DATA_JSON = __DIR__ . '/../data/products.json';
$DATA_JS = __DIR__ . '/../data/products.js';
$UPLOAD_DIR = __DIR__ . '/../images/';
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'asianindo123'; // Default password

$action = $_GET['action'] ?? '';

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

// PROTECTED ROUTES BELOW
if (!isset($_SESSION['is_admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

function readData() {
    global $DATA_JSON;
    if (!file_exists($DATA_JSON)) {
        // Fallback to default tracked by git
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
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
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
    $imagePath = $_POST['existing_image'] ?? '';
    $videoPath = $_POST['existing_video'] ?? '';
    
    // Process Features
    $features = [];
    if (isset($_POST['features']) && is_array($_POST['features'])) {
        foreach ($_POST['features'] as $feat) {
            if (trim($feat) !== '') $features[] = trim($feat);
        }
    }
    
    // Process Specs
    $specs = [];
    if (isset($_POST['specs_keys']) && is_array($_POST['specs_keys'])) {
        foreach ($_POST['specs_keys'] as $i => $key) {
            $val = $_POST['specs_vals'][$i] ?? '';
            if (trim($key) !== '' && trim($val) !== '') {
                $specs[trim($key)] = trim($val);
            }
        }
    }

    // Handle Image Upload (Max 2MB)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Ukuran gambar maksimal 2MB!']);
            exit;
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $UPLOAD_DIR . $filename);
        $imagePath = 'images/' . $filename;
    }

    // Handle Video Upload (Max 15MB)
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['video']['size'] > 15 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Ukuran video maksimal 15MB!']);
            exit;
        }
        $ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('vid_') . '.' . $ext;
        move_uploaded_file($_FILES['video']['tmp_name'], $UPLOAD_DIR . $filename);
        $videoPath = 'images/' . $filename; // Saving in images folder for now
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
        'image' => $imagePath,
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
