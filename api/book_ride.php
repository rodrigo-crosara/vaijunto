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

    // 1. Buscar detalhes da carona (para validações e dados de retorno)
    $stmtRide = $pdo->prepare("
        SELECT r.*, u.phone as driver_phone, u.pix_key, u.name as driver_name, c.plate as car_plate, c.model as car_model
        FROM rides r
        JOIN users u ON r.driver_id = u.id
        LEFT JOIN cars c ON c.user_id = u.id
        WHERE r.id = ? 
        LIMIT 1
    ");
    $stmtRide->execute([$rideId]);
    $ride = $stmtRide->fetch(PDO::FETCH_ASSOC);

    if (!$ride) {
        throw new Exception("Carona não encontrada.");
    }

    // 2. Bloqueios de Lógica
    if ($ride['driver_id'] == $passengerId) {
        throw new Exception("Você não pode reservar sua própria carona.");
    }

    if ($ride['status'] === 'canceled') {
        throw new Exception("Esta carona foi cancelada.");
    }

    if (strtotime($ride['departure_time']) < time()) {
        throw new Exception("Esta carona já partiu.");
    }

    // Verificar duplicidade (exclui canceladas para permitir re-reserva)
    $stmtCheck = $pdo->prepare("SELECT id FROM bookings WHERE ride_id = ? AND passenger_id = ? AND status != 'canceled'");
    $stmtCheck->execute([$rideId, $passengerId]);
    if ($stmtCheck->fetch()) {
        throw new Exception("Você já reservou um lugar nesta carona.");
    }

    // 3. RESERVA ATÔMICA: Tenta decrementar SOMENTE se tiver vaga (> 0)
    $stmtDecrement = $pdo->prepare("UPDATE rides SET seats_available = seats_available - 1 WHERE id = ? AND seats_available > 0");
    $stmtDecrement->execute([$rideId]);

    if ($stmtDecrement->rowCount() === 0) {
        // Nenhuma linha afetada = sem vagas
        throw new Exception("Vagas esgotadas! Alguém reservou antes de você.");
    }

    // 4. Vaga garantida atomicamente — inserir booking
    $meetingPoint = trim($input['meetingPoint'] ?? '');
    $stmtBook = $pdo->prepare("INSERT INTO bookings (ride_id, passenger_id, meeting_point, status, created_at) VALUES (?, ?, ?, 'confirmed', NOW())");
    $stmtBook->execute([$rideId, $passengerId, $meetingPoint]);

    $pdo->commit();

    // 5. Notificar o Motorista
    $passengerName = $_SESSION['user_name'] ?? 'Alguém';
    createNotification($pdo, $ride['driver_id'], 'booking', "🎉 Nova reserva de {$passengerName}!", 'index.php?page=my_rides');

    // 6. Retorno de Sucesso com Dados Revelados
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

