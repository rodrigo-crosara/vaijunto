<?php
/**
 * API: Ações do Motorista (Cancelar Carona, Editar Vagas, Remover Passageiro)
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
$action = $input['action'] ?? '';
$rideId = intval($input['rideId'] ?? 0);

// Validação: remove_passenger e confirm_payment não precisam de rideId, usam bookingId
if (
    !$action ||
    ($action !== 'remove_passenger' && $action !== 'confirm_payment' && $rideId <= 0)
) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

try {
    switch ($action) {
        case 'cancel_ride':
            // Verificar se a carona é do motorista
            $stmt = $pdo->prepare("UPDATE rides SET status = 'canceled' WHERE id = ? AND driver_id = ?");
            $stmt->execute([$rideId, $driverId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Carona cancelada com sucesso.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Carona não encontrada ou você não tem permissão.']);
            }
            break;

        case 'update_seats':
            $newSeats = intval($input['newSeats'] ?? -1);
            if ($newSeats < 0) {
                echo json_encode(['success' => false, 'message' => 'Quantidade de vagas inválida.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE rides SET seats_available = ? WHERE id = ? AND driver_id = ?");
            $stmt->execute([$newSeats, $rideId, $driverId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Vagas atualizadas.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar vagas ou sem permissão.']);
            }
            break;

        case 'remove_passenger':
            $bookingId = intval($input['bookingId'] ?? 0);
            if ($bookingId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de reserva inválido.']);
                exit;
            }

            $pdo->beginTransaction();

            // 1. Buscar a reserva e verificar se a carona pertence ao motorista
            $stmt = $pdo->prepare("
                SELECT b.ride_id, b.status 
                FROM bookings b
                JOIN rides r ON b.ride_id = r.id
                WHERE b.id = ? AND r.driver_id = ?
            ");
            $stmt->execute([$bookingId, $driverId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                throw new Exception("Reserva não encontrada ou sem permissão.");
            }

            if ($booking['status'] === 'rejected') {
                throw new Exception("Esta reserva já foi removida.");
            }

            // 2. Marcar como rejeitada
            $stmtUpdate = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
            $stmtUpdate->execute([$bookingId]);

            // 3. Incrementar vagas na carona
            $stmtInc = $pdo->prepare("UPDATE rides SET seats_available = seats_available + 1 WHERE id = ?");
            $stmtInc->execute([$booking['ride_id']]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Passageiro removido e vaga liberada.']);
            break;

        case 'confirm_payment':
            $bookingId = intval($input['bookingId'] ?? 0);
            if ($bookingId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de reserva inválido.']);
                exit;
            }

            // Verificar se a carona pertence ao motorista
            $stmt = $pdo->prepare("
                SELECT b.id FROM bookings b
                JOIN rides r ON b.ride_id = r.id
                WHERE b.id = ? AND r.driver_id = ?
            ");
            $stmt->execute([$bookingId, $driverId]);

            if ($stmt->fetch()) {
                $stmtUpdate = $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE id = ?");
                $stmtUpdate->execute([$bookingId]);
                echo json_encode(['success' => true, 'message' => 'Pagamento confirmado!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reserva não encontrada ou sem permissão.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
            break;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>