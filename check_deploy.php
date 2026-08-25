<?php
/**
 * Verifikasi apakah file auth_api.php sudah versi terbaru di server
 */
header('Content-Type: application/json');

$authFile = __DIR__ . '/auth_api.php';
$dbFile = __DIR__ . '/database/db.php';

$result = [
    'auth_api_exists' => file_exists($authFile),
    'auth_api_size' => file_exists($authFile) ? filesize($authFile) : 0,
    'auth_api_md5' => file_exists($authFile) ? md5_file($authFile) : '',
    'auth_api_has_error_handler' => file_exists($authFile) ? (strpos(file_get_contents($authFile), 'set_error_handler') !== false) : false,
    'auth_api_has_getDB' => file_exists($authFile) ? (strpos(file_get_contents($authFile), '$pdo = getDB()') !== false) : false,
    'auth_api_first_100_chars' => file_exists($authFile) ? substr(file_get_contents($authFile), 0, 200) : '',
    'db_exists' => file_exists($dbFile),
    'db_size' => file_exists($dbFile) ? filesize($dbFile) : 0,
    'db_has_password' => file_exists($dbFile) ? (strpos(file_get_contents($dbFile), 'Web_asianindo21') !== false) : false,
    'db_md5' => file_exists($dbFile) ? md5_file($dbFile) : '',
];

echo json_encode($result, JSON_PRETTY_PRINT);
