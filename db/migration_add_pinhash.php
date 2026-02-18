<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Checking for pin_hash column...\n";
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'pin_hash'");
    $stmt->execute();

    if (!$stmt->fetch()) {
        echo "Column not found. Adding pin_hash...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN pin_hash VARCHAR(255) NULL");
        echo "Success: pin_hash added.\n";
    } else {
        echo "Info: pin_hash column already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
