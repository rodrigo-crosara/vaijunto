<?php
/**
 * API: Motorista fecha carona externamente
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$driverId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$rideId = intval($input['rideId'] ?? 0);

if ($rideId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

try {
    // Verifica se pertence ao motorista
    $stmt = $pdo->prepare("UPDATE rides SET seats_available = 0 WHERE id = ? AND driver_id = ?");
    $stmt->execute([$rideId, $driverId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Carona não encontrada ou já fechada.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro DB: ' . $e->getMessage()]);
}
