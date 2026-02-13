<?php
/**
 * Helper: Criar notificação no banco de dados
 * Reutilizável via require_once em qualquer API.
 */

function createNotification($pdo, $userId, $type, $message, $link = null)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link_url, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$userId, $type, $message, $link]);
        return true;
    } catch (PDOException $e) {
        // Falhar silenciosamente - notificação não deve quebrar o fluxo principal
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}
