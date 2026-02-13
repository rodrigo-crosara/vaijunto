<?php
/**
 * API: Verificar se existem novas caronas desde o último ID visto
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$lastId = intval($_GET['last_id'] ?? 0);

if ($lastId <= 0) {
    echo json_encode(['success' => true, 'new_count' => 0]);
    exit;
}

try {
    // Contar caronas agendadas com ID maior que o último visto e que não sejam do próprio usuário
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM rides 
        WHERE id > ? 
          AND status = 'scheduled' 
          AND driver_id != ?
          AND departure_time >= NOW()
    ");
    $stmt->execute([$lastId, $_SESSION['user_id']]);
    $count = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'new_count' => (int) $count
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>