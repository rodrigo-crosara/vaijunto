<?php
/**
 * API: Recuperar dados da última carona criada pelo motorista
 * Objetivo: Preenchimento automático (Smart Reply)
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nǜo autenticado.']);
    exit;
}

$driverId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT origin_text as origin, destination_text as destination, waypoints, price, seats_total as seats, tags
        FROM rides 
        WHERE driver_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$driverId]);
    $lastRide = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastRide) {
        // Extrair details das tags
        $tags = json_decode($lastRide['tags'] ?? '{}', true);
        $lastRide['details'] = $tags['details'] ?? '';
        unset($lastRide['tags']); // Limpa tags raw

        echo json_encode(['success' => true, 'data' => $lastRide]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma carona anterior encontrada.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar histórico.']);
}
