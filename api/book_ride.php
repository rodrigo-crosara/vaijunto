<?php
/**
 * API: Confirmar Reserva de Carona
 */
session_start();
require_once '../config/db.php';
require_once '../helpers/notification.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$passengerId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$rideId = intval($input['rideId'] ?? 0);

if ($rideId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID da carona inválido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Buscar detalhes da carona e verificar condições (FOR UPDATE para lock)
    $stmtRide = $pdo->prepare("
        SELECT r.*, u.phone as driver_phone, u.pix_key, c.plate as car_plate, c.model as car_model
        FROM rides r
        JOIN users u ON r.driver_id = u.id
        LEFT JOIN cars c ON c.user_id = u.id
        WHERE r.id = ? 
        LIMIT 1 
        FOR UPDATE
    ");
    $stmtRide->execute([$rideId]);
    $ride = $stmtRide->fetch(PDO::FETCH_ASSOC);

    if (!$ride) {
        throw new Exception("Carona não encontrada.");
    }

    // Validações
    if ($ride['driver_id'] == $passengerId) {
        throw new Exception("Você não pode reservar sua própria carona.");
    }

    if ($ride['seats_available'] <= 0) {
        throw new Exception("Esta carona está lotada.");
    }

    // Verificar duplicidade
    $stmtCheck = $pdo->prepare("SELECT id FROM bookings WHERE ride_id = ? AND passenger_id = ?");
    $stmtCheck->execute([$rideId, $passengerId]);
    if ($stmtCheck->fetch()) {
        throw new Exception("Você já reservou um lugar nesta carona.");
    }

    // 2. Criar Booking
    $meetingPoint = trim($input['meetingPoint'] ?? '');
    $stmtBook = $pdo->prepare("INSERT INTO bookings (ride_id, passenger_id, meeting_point, status, created_at) VALUES (?, ?, ?, 'confirmed', NOW())");
    $stmtBook->execute([$rideId, $passengerId, $meetingPoint]);

    // 3. Atualizar Vagas
    $stmtUpdate = $pdo->prepare("UPDATE rides SET seats_available = seats_available - 1 WHERE id = ?");
    $stmtUpdate->execute([$rideId]);

    $pdo->commit();

    // 4. Notificar o Motorista
    $passengerName = $_SESSION['user_name'] ?? 'Alguém';
    createNotification($pdo, $ride['driver_id'], 'booking', "🎉 Nova reserva de {$passengerName}!", 'index.php?page=my_rides');

    // 5. Retorno de Sucesso com Dados Revelados
    echo json_encode([
        'success' => true,
        'message' => 'Vaga garantida!',
        'driver_phone' => $ride['driver_phone'],
        'pix_key' => $ride['pix_key'] ?? '',
        'car_plate' => $ride['car_plate'] ?? 'Placa não inf.',
        'car_model' => $ride['car_model'] ?? 'Carro'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
