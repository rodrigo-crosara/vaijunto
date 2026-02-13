<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso Negado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    if ($action === 'ban_user') {
        $userId = $input['user_id'] ?? 0;
        if (!$userId)
            throw new Exception("ID de usuário inválido");

        // Soft delete: mudar nome e telefone
        $stmt = $pdo->prepare("UPDATE users SET phone = CONCAT(phone, '_BANNED'), name = CONCAT('[BANNED] ', COALESCE(name, 'User')) WHERE id = ?");
        $stmt->execute([$userId]);

        // Cancelar caronas futuras do usuário (como motorista)
        $stmtRides = $pdo->prepare("UPDATE rides SET status = 'canceled' WHERE driver_id = ? AND departure_time >= NOW()");
        $stmtRides->execute([$userId]);

        // Cancelar reservas futuras (como passageiro)
        $stmtBookings = $pdo->prepare("UPDATE bookings SET status = 'canceled' WHERE passenger_id = ? AND id IN (SELECT b.id FROM (SELECT b2.id FROM bookings b2 JOIN rides r ON b2.ride_id = r.id WHERE r.departure_time >= NOW()) as b)");
        // SQL simplificado para facilitar:
        $pdo->prepare("UPDATE bookings b JOIN rides r ON b.ride_id = r.id SET b.status = 'canceled' WHERE b.passenger_id = ? AND r.departure_time >= NOW()")->execute([$userId]);

        echo json_encode(['success' => true, 'message' => 'Usuário banido e viagens futuras canceladas']);

    } elseif ($action === 'delete_ride') {
        $rideId = $input['ride_id'] ?? 0;
        if (!$rideId)
            throw new Exception("ID da carona inválido");

        // Marcar como cancelada
        $stmt = $pdo->prepare("UPDATE rides SET status = 'canceled' WHERE id = ?");
        $stmt->execute([$rideId]);

        // Notificar passageiros (usando a lógica de notificações se existir, senão apenas cancela)
        // No esquema atual, cancelamos as reservas também
        $stmtBookings = $pdo->prepare("UPDATE bookings SET status = 'canceled' WHERE ride_id = ?");
        $stmtBookings->execute([$rideId]);

        echo json_encode(['success' => true, 'message' => 'Carona cancelada com sucesso']);

    } else {
        throw new Exception("Ação desconhecida");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
