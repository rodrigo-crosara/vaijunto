<?php
/**
 * Migration: Corrigir Cascata de Exclusão para Proteger Reputação
 * Objetivo: Impedir que o expurgo de caronas velhas apague as avaliações dos usuários.
 */
require_once __DIR__ . '/../config/db.php';

try {
    echo "Iniciando correção de integridade das avaliações...\n";

    // 1. Tornar colunas aceitáveis para NULL na tabela ratings
    $pdo->exec("ALTER TABLE ratings MODIFY booking_id INT NULL");
    $pdo->exec("ALTER TABLE ratings MODIFY ride_id INT NULL");

    // 2. Descobrir nomes das constraints de Foreign Key (podem variar por ambiente)
    // Vamos tentar remover as que são baseadas nas FKs de bookings e rides

    // Como não sabemos o nome exato (ex: ratings_ibfk_1), vamos usar um bloco de script 
    // para buscar no information_schema e dropar dinamicamente.

    $stmt = $pdo->prepare("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'ratings' 
          AND COLUMN_NAME IN ('booking_id', 'ride_id')
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->execute();
    $fks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($fks as $fkName) {
        echo "Removendo FK: {$fkName}...\n";
        $pdo->exec("ALTER TABLE ratings DROP FOREIGN KEY {$fkName}");
    }

    // 3. Recriar as FKs com ON DELETE SET NULL
    echo "Recriando chaves estrangeiras com proteção SET NULL...\n";
    $pdo->exec("ALTER TABLE ratings ADD CONSTRAINT fk_ratings_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL");
    $pdo->exec("ALTER TABLE ratings ADD CONSTRAINT fk_ratings_ride FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE SET NULL");

    echo "Sucesso! Avaliações protegidas contra o expurgo de caronas.\n";

} catch (Exception $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}
