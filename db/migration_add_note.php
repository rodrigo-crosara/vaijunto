<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN note VARCHAR(100) NULL COMMENT 'Ponto de encontro ou obs do passageiro'");
    echo "Migration successful: 'note' column added to 'bookings'.\n";
} catch (PDOException $e) {
    echo "Migration failed (might already exist): " . $e->getMessage() . "\n";
}
?>