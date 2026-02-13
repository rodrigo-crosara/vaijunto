<?php
/**
 * API: Ações do Passageiro (Cancelar Reserva)
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    exit;
}

$action = $input['action'];

switch ($action) {
    case 'cancel_booking':
        $bookingId = intval($input['bookingId'] ?? 0);

        if ($bookingId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID da reserva inválido.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Verificar se a reserva pertence ao usuário logado e está ativa
            $stmt = $pdo->prepare("SELECT id, ride_id, status FROM bookings WHERE id = ? AND passenger_id = ?");
            $stmt->execute([$bookingId, $userId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Reserva não encontrada ou não pertence a você.']);
                exit;
            }

            if ($booking['status'] === 'canceled') {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Esta reserva já foi cancelada.']);
                exit;
            }

            // 2. Cancelar a reserva
            $stmtCancel = $pdo->prepare("UPDATE bookings SET status = 'canceled' WHERE id = ?");
            $stmtCancel->execute([$bookingId]);

            // 3. Devolver a vaga ao ride
            $stmtSeats = $pdo->prepare("UPDATE rides SET seats_available = seats_available + 1 WHERE id = ?");
            $stmtSeats->execute([$booking['ride_id']]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Reserva cancelada. A vaga foi liberada.']);

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erro ao cancelar reserva.', 'debug' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
        break;
}
