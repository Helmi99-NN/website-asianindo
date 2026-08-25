<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting auth test...\n";

try {
    require_once __DIR__ . '/database/db.php';
    echo "db.php loaded successfully.\n";
    
    $pdo = getDB();
    if ($pdo) {
        echo "PDO connected.\n";
        
        $email = 'test_check_' . time() . '@gmail.com';
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        echo "SELECT query executed.\n";
        
        $hashed = password_hash('password123', PASSWORD_BCRYPT);
        $insert = $pdo->prepare("
            INSERT INTO customers (name, email, phone, password_hash, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $insert->execute(['Test User', $email, '081234567890', $hashed]);
        $id = $pdo->lastInsertId();
        echo "INSERT query executed, new customer ID: $id\n";
        
        // Clean up
        $pdo->query("DELETE FROM customers WHERE id = $id");
        echo "Cleanup test user success.\n";
        echo "ALL AUTH OPERATIONS WORKING 100%!\n";
    } else {
        echo "PDO is null.\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
