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

        // Notificar passageiros
        $stmtBookings = $pdo->prepare("UPDATE bookings SET status = 'canceled' WHERE ride_id = ?");
        $stmtBookings->execute([$rideId]);

        echo json_encode(['success' => true, 'message' => 'Carona cancelada com sucesso']);

    } elseif ($action === 'delete_user_permanent') {
        $userId = $input['user_id'] ?? 0;
        if (!$userId)
            throw new Exception("ID de usuário inválido");

        // Segurança: Não permitir deletar a si mesmo (o próprio admin logado)
        if ($userId == $_SESSION['user_id']) {
            throw new Exception("Você não pode excluir sua própria conta pelo painel administrativo.");
        }

        // 1. Buscar fotos para apagar arquivos físicos
        $stmtPhotos = $pdo->prepare("
            SELECT u.photo_url as user_photo, c.photo_url as car_photo 
            FROM users u 
            LEFT JOIN cars c ON c.user_id = u.id 
            WHERE u.id = ?
        ");
        $stmtPhotos->execute([$userId]);
        $photos = $stmtPhotos->fetch();

        if ($photos) {
            if ($photos['user_photo'] && file_exists(__DIR__ . '/../' . $photos['user_photo'])) {
                @unlink(__DIR__ . '/../' . $photos['user_photo']);
            }
            if ($photos['car_photo'] && file_exists(__DIR__ . '/../' . $photos['car_photo'])) {
                @unlink(__DIR__ . '/../' . $photos['car_photo']);
            }
        }

        // 2. Excluir do banco (CASCADE deve estar habilitado)
        $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmtDelete->execute([$userId]);

        echo json_encode(['success' => true, 'message' => 'Usuário e todos os seus dados foram apagados permanentemente.']);

    } elseif ($action === 'notify_user') {
        $userId = $input['user_id'] ?? 0;
        $message = trim($input['message'] ?? '');
        $type = $input['type'] ?? 'system';

        if (!$userId || empty($message))
            throw new Exception("Dados insuficientes para notificação.");

        require_once '../helpers/notification.php';
        if (createNotification($pdo, $userId, $type, $message, 'index.php?page=notifications')) {
            echo json_encode(['success' => true, 'message' => 'Notificação enviada com sucesso!']);
        } else {
            throw new Exception("Falha ao criar notificação no banco.");
        }

    } else {
        throw new Exception("Ação desconhecida");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
