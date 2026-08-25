<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$configPath1 = __DIR__ . '/database/config.php';
$configPath2 = __DIR__ . '/config.php';
$configPath3 = __DIR__ . '/db_config.php';

$debug = [
    'config_in_database_exists' => file_exists($configPath1),
    'config_in_root_exists' => file_exists($configPath2),
    'db_config_exists' => file_exists($configPath3),
];

define('CLI_MODE', true);
require_once __DIR__ . '/database/db.php';

$debug['db_host'] = $dbHost ?? 'unset';
$debug['db_name'] = $dbName ?? 'unset';
$debug['db_user'] = $dbUser ?? 'unset';
$debug['is_password_empty'] = empty($dbPass);
$debug['password_length'] = strlen($dbPass ?? '');
$debug['database_dir_files'] = is_dir(__DIR__ . '/database') ? scandir(__DIR__ . '/database') : [];

try {
    $pdo = getDB();
    if ($pdo) {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode([
            'status' => 'success',
            'message' => 'Koneksi database MySQL berhasil 100%!',
            'debug' => $debug,
            'tables' => $tables
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'PDO object is null',
            'debug' => $debug
        ], JSON_PRETTY_PRINT);
    }
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
}
