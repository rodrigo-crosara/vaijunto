<?php
/**
 * API: Ações do Motorista (Cancelar Carona, Editar Vagas, Remover Passageiro)
 */
session_start();
require_once '../config/db.php';
require_once '../helpers/notification.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$driverId = $_SESSION['user_id'];
// Suporte a JSON ou POST tradicional
$rawInput = json_decode(file_get_contents('php://input'), true) ?: [];
$input = array_merge($_POST, $rawInput);

$action = $input['action'] ?? '';
$rideId = intval($input['rideId'] ?? $input['ride_id'] ?? 0);

// Validação: remove_passenger, confirm_payment, confirm_booking, reject_booking não precisam de rideId, usam bookingId
if (
    !$action ||
    (!in_array($action, ['remove_passenger', 'confirm_payment', 'confirm_booking', 'reject_booking', 'close_ride']) && $rideId <= 0)
) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

try {
    switch ($action) {
        case 'confirm_booking':
            $bookingId = intval($input['bookingId'] ?? 0);
            $stmt = $pdo->prepare("SELECT b.id, b.passenger_id, r.driver_id FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.id = ? AND r.driver_id = ?");
            $stmt->execute([$bookingId, $driverId]);
            $b = $stmt->fetch();

            if ($b) {
                $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$bookingId]);
                createNotification($pdo, $b['passenger_id'], 'confirmed', '✅ Sua solicitação de carona foi aceita pelo motorista!', 'index.php?page=my_bookings');
                echo json_encode(['success' => true, 'message' => 'Passageiro confirmado na carona!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reserva não encontrada.']);
            }
            break;

        case 'reject_booking':
            $bookingId = intval($input['bookingId'] ?? 0);
            $stmt = $pdo->prepare("SELECT b.id, b.ride_id, b.passenger_id, r.driver_id FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.id = ? AND r.driver_id = ?");
            $stmt->execute([$bookingId, $driverId]);
            $b = $stmt->fetch();

            if ($b) {
                // Devolve a vaga para a carona (pois ela foi reservada no ato do pedido)
                $pdo->prepare("UPDATE rides SET seats_available = seats_available + 1 WHERE id = ?")->execute([$b['ride_id']]);
                // Rejeita a solicitação
                $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$bookingId]);
                createNotification($pdo, $b['passenger_id'], 'cancel', '❌ O motorista não pôde aceitar sua solicitação.', 'index.php?page=home');
                echo json_encode(['success' => true, 'message' => 'Solicitação recusada. Vaga liberada.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reserva não encontrada.']);
            }
            break;
        case 'close_ride':
            // Fecha as vagas (seats_available = 0)
            $stmt = $pdo->prepare("UPDATE rides SET seats_available = 0 WHERE id = ? AND driver_id = ?");
            $stmt->execute([$rideId, $driverId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Carona não encontrada ou já encerrada.']);
            }
            break;

        case 'cancel_ride':
            // Verificar se a carona é do motorista
            $stmt = $pdo->prepare("UPDATE rides SET status = 'canceled' WHERE id = ? AND driver_id = ?");
            $stmt->execute([$rideId, $driverId]);

            if ($stmt->rowCount() > 0) {
                // Notificar TODOS os passageiros desta carona
                $driverName = $_SESSION['user_name'] ?? 'O motorista';
                $stmtPass = $pdo->prepare("SELECT passenger_id FROM bookings WHERE ride_id = ? AND status = 'confirmed'");
                $stmtPass->execute([$rideId]);
                $passengers = $stmtPass->fetchAll(PDO::FETCH_COLUMN);
                foreach ($passengers as $pid) {
                    createNotification($pdo, $pid, 'cancel', "🚨 URGENTE: Viagem cancelada por {$driverName}.", 'index.php?page=my_bookings');
                }
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

            // DEVOLVER VAGA
            // Buscar rideId da reserva
            $stmtRideId = $pdo->prepare("SELECT ride_id FROM bookings WHERE id = ?");
            $stmtRideId->execute([$bookingId]);
            $rideIdForReturn = $stmtRideId->fetchColumn();

            if ($rideIdForReturn) {
                $stmtInc = $pdo->prepare("UPDATE rides SET seats_available = seats_available + 1 WHERE id = ?");
                $stmtInc->execute([$rideIdForReturn]);
            }

            // 3. Incrementar vagas na carona
            $stmtInc = $pdo->prepare("UPDATE rides SET seats_available = seats_available + 1 WHERE id = ?");
            $stmtInc->execute([$booking['ride_id']]);

            // Notificar o passageiro removido
            $stmtPid = $pdo->prepare("SELECT passenger_id FROM bookings WHERE id = ?");
            $stmtPid->execute([$bookingId]);
            $removedPassengerId = $stmtPid->fetchColumn();
            $driverName = $_SESSION['user_name'] ?? 'O motorista';
            if ($removedPassengerId) {
                createNotification($pdo, $removedPassengerId, 'cancel', "😔 {$driverName} removeu você da carona.", 'index.php?page=my_bookings');
            }

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

            $bookingRow = $stmt->fetch();
            if ($bookingRow) {
                $stmtUpdate = $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE id = ?");
                $stmtUpdate->execute([$bookingId]);

                // Notificar o passageiro
                $stmtPid = $pdo->prepare("SELECT passenger_id FROM bookings WHERE id = ?");
                $stmtPid->execute([$bookingId]);
                $passengerId = $stmtPid->fetchColumn();
                if ($passengerId) {
                    createNotification($pdo, $passengerId, 'payment', '💰 Pagamento confirmado pelo motorista! ✅', 'index.php?page=my_bookings');
                }

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