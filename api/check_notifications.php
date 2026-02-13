<?php
/**
 * API: Verificar notificações não-lidas (para polling)
 * Retorna contagem e as notificações mais recentes não lidas.
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Contar não lidas
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtCount->execute([$userId]);
    $count = (int) $stmtCount->fetchColumn();

    // Buscar as 5 mais recentes não lidas (para toasts)
    $stmtRecent = $pdo->prepare("
        SELECT id, type, message, link_url, created_at 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmtRecent->execute([$userId]);
    $notifications = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'count' => $count,
        'notifications' => $notifications
    ]);

} catch (PDOException $e) {
    echo json_encode(['count' => 0, 'notifications' => []]);
}
