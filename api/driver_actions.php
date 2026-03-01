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
    (!in_array($action, ['remove_passenger', 'confirm_payment', 'confirm_booking', 'reject_booking', 'close_ride', 'finish_ride']) && $rideId <= 0)
) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

try {
    switch ($action) {
        case 'confirm_booking':
            $bookingId = intval($input['bookingId'] ?? 0);
            $stmt = $pdo->prepare("SELECT b.id, b.status, b.passenger_id, r.driver_id FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.id = ? AND r.driver_id = ?");
            $stmt->execute([$bookingId, $driverId]);
            $b = $stmt->fetch();

            if ($b) {
                if ($b['status'] !== 'pending') {
                    echo json_encode(['success' => false, 'message' => 'Esta reserva já foi processada ou cancelada.']);
                    exit;
                }
                $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$bookingId]);
                createNotification($pdo, $b['passenger_id'], 'confirmed', '✅ Sua solicitação de carona foi aceita pelo motorista!', 'index.php?page=my_bookings');
                echo json_encode(['success' => true, 'message' => 'Passageiro confirmado na carona!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reserva não encontrada.']);
            }
            break;

        case 'reject_booking':
            $bookingId = intval($input['bookingId'] ?? 0);
            $stmt = $pdo->prepare("SELECT b.id, b.status, b.ride_id, b.passenger_id, r.driver_id FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.id = ? AND r.driver_id = ?");
            $stmt->execute([$bookingId, $driverId]);
            $b = $stmt->fetch();

            if ($b) {
                if ($b['status'] === 'rejected' || $b['status'] === 'canceled') {
                    echo json_encode(['success' => false, 'message' => 'Esta reserva já está inativa.']);
                    exit;
                }

                $pdo->beginTransaction();
                // Devolve a vaga para a carona (pois ela foi reservada no ato do pedido)
                $pdo->prepare("UPDATE rides SET seats_available = seats_available + 1 WHERE id = ?")->execute([$b['ride_id']]);
                // Rejeita a solicitação
                $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$bookingId]);
                createNotification($pdo, $b['passenger_id'], 'cancel', '❌ O motorista não pôde aceitar sua solicitação.', 'index.php?page=home');
                $pdo->commit();

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
            // 1. Buscar dados da carona para validação temporal
            $stmtCheck = $pdo->prepare("SELECT departure_time FROM rides WHERE id = ? AND driver_id = ?");
            $stmtCheck->execute([$rideId, $driverId]);
            $rideData = $stmtCheck->fetch();

            if (!$rideData) {
                echo json_encode(['success' => false, 'message' => 'Carona não encontrada ou sem permissão.']);
                exit;
            }

            // Determinar se a carona é futura (com margem de 30 min)
            $isFuture = strtotime($rideData['departure_time']) >= (time() - 1800);

            // Validação de Segurança: Proibir cancelamento com pagamentos confirmados
            $stmtPaid = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE ride_id = ? AND payment_status = 'paid' AND status != 'canceled'");
            $stmtPaid->execute([$rideId]);
            if ($stmtPaid->fetchColumn() > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Não é possível cancelar uma carona que já possui pagamentos confirmados. Você deve resolver os estornos com os passageiros primeiro.'
                ]);
                exit;
            }

            // 2. Marcar como cancelada no banco
            $stmt = $pdo->prepare("UPDATE rides SET status = 'canceled' WHERE id = ? AND driver_id = ?");
            $stmt->execute([$rideId, $driverId]);

            if ($stmt->rowCount() > 0) {
                // 3. Notificar passageiros APENAS se a viagem for futura
                if ($isFuture) {
                    $driverName = $_SESSION['user_name'] ?? 'O motorista';
                    $stmtPass = $pdo->prepare("SELECT id, passenger_id, status FROM bookings WHERE ride_id = ? AND status IN ('confirmed', 'pending')");
                    $stmtPass->execute([$rideId]);
                    $bookingsToCancel = $stmtPass->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($bookingsToCancel as $b) {
                        $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$b['id']]);
                        $msg = ($b['status'] === 'confirmed')
                            ? "🚨 URGENTE: Viagem cancelada por {$driverName}."
                            : "❌ Viagem cancelada: O motorista cancelou a carona antes de aceitar seu pedido.";
                        createNotification($pdo, $b['passenger_id'], 'cancel', $msg, 'index.php?page=home');
                    }
                    $msgFinal = 'Carona cancelada e passageiros notificados.';
                } else {
                    $msgFinal = 'Carona inativada do histórico (sem notificações).';
                }

                echo json_encode(['success' => true, 'message' => $msgFinal]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao processar cancelamento.']);
            }
            break;

        case 'update_seats':
            $newSeats = intval($input['newSeats'] ?? -1);
            if ($newSeats < 0) {
                echo json_encode(['success' => false, 'message' => 'Quantidade de vagas inválida.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE rides SET seats_available = ? WHERE id = ? AND driver_id = ? AND departure_time >= NOW()");
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
                SELECT b.ride_id, b.status, b.passenger_id
                FROM bookings b
                JOIN rides r ON b.ride_id = r.id
                WHERE b.id = ? AND r.driver_id = ?
            ");
            $stmt->execute([$bookingId, $driverId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                throw new Exception("Reserva não encontrada ou sem permissão.");
            }

            // Só devolve vaga se a reserva não estiver já em estado de "vaga devolvida"
            if ($booking['status'] === 'rejected' || $booking['status'] === 'canceled') {
                throw new Exception("Esta reserva já foi cancelada ou removida.");
            }

            // 2. Marcar como rejeitada
            $stmtUpdate = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
            $stmtUpdate->execute([$bookingId]);

            // 3. Devolver a vaga apenas UMA vez
            $stmtInc = $pdo->prepare("UPDATE rides SET seats_available = seats_available + 1 WHERE id = ?");
            $stmtInc->execute([$booking['ride_id']]);

            // 4. Notificar o passageiro removido
            $driverName = $_SESSION['user_name'] ?? 'O motorista';
            createNotification($pdo, $booking['passenger_id'], 'cancel', "😔 {$driverName} removeu você da carona.", 'index.php?page=my_bookings');

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Passageiro removido e vaga liberada.']);
            break;

        case 'no_show_booking':
            $bookingId = intval($input['bookingId'] ?? 0);
            if ($bookingId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de reserva inválido.']);
                exit;
            }

            // Verificar se a carona pertence ao motorista e se o horário já permite no-show
            $stmt = $pdo->prepare("
                SELECT b.id, b.passenger_id, r.departure_time 
                FROM bookings b
                JOIN rides r ON b.ride_id = r.id
                WHERE b.id = ? AND r.driver_id = ?
            ");
            $stmt->execute([$bookingId, $driverId]);
            $row = $stmt->fetch();

            if ($row) {
                // Opcional: Só permitir no-show após o horário da partida? 
                // Por enquanto deixaremos livre para o motorista decidir.

                $stmtUpdate = $pdo->prepare("UPDATE bookings SET status = 'no_show' WHERE id = ?");
                $stmtUpdate->execute([$bookingId]);

                // Notificar o passageiro
                createNotification($pdo, $row['passenger_id'], 'system', "⚠️ O motorista marcou que você não compareceu ao embarque.", 'index.php?page=my_bookings');

                echo json_encode(['success' => true, 'message' => 'Passageiro marcado como No-Show.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reserva não encontrada ou sem permissão.']);
            }
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

        case 'undo_payment':
            $bookingId = intval($input['bookingId'] ?? 0);
            if ($bookingId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de reserva inválido.']);
                exit;
            }

            // Verificar se a carona pertence ao motorista
            $stmt = $pdo->prepare("
                SELECT b.id, b.passenger_id FROM bookings b
                JOIN rides r ON b.ride_id = r.id
                WHERE b.id = ? AND r.driver_id = ?
            ");
            $stmt->execute([$bookingId, $driverId]);

            $bookingRow = $stmt->fetch();
            if ($bookingRow) {
                $stmtUpdate = $pdo->prepare("UPDATE bookings SET payment_status = 'pending' WHERE id = ?");
                $stmtUpdate->execute([$bookingId]);

                // Notificar o passageiro (Estorno ou Correção)
                createNotification($pdo, $bookingRow['passenger_id'], 'payment', '⚠️ Pagamento marcado como pendente pelo motorista. Resolva se necessário.', 'index.php?page=my_bookings');

                echo json_encode(['success' => true, 'message' => 'Status de pagamento revertido para pendente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reserva não encontrada ou sem permissão.']);
            }
            break;

        case 'finish_ride':
            $stmt = $pdo->prepare("UPDATE rides SET status = 'finished' WHERE id = ? AND driver_id = ?");
            $stmt->execute([$rideId, $driverId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Viagem finalizada com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao finalizar viagem ou carona não encontrada.']);
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
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
}
?>