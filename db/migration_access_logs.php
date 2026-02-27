<?php
/**
 * Migration: Criar tabela access_logs para Rate Limiting
 */
require_once __DIR__ . '/../config/db.php';

try {
    echo "Verificando tabela access_logs...\n";

    $sql = "CREATE TABLE IF NOT EXISTS access_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        user_id INT NULL,
        action_type VARCHAR(50) NOT NULL COMMENT 'ex: login, create_ride',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_user (user_id),
        INDEX idx_action (action_type),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    echo "Sucesso: Tabela access_logs pronta para uso.\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
