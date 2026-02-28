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
    // Validar status e vagas novamente
    if ($ride['seats_available'] < 1 || !in_array($ride['status'], ['scheduled', 'active'])) {
        throw new Exception("Carona lotada ou indisponível.");
    }

    if ($ride['driver_id'] == $passengerId) {
        throw new Exception("Você não pode reservar sua própria carona.");
    }

    if ($ride['status'] === 'canceled') {
        throw new Exception("Esta carona foi cancelada.");
    }

    // Grace Period de 30 minutos para permitir reservas de última hora
    if (strtotime($ride['departure_time']) < strtotime('-30 minutes')) {
        throw new Exception("Esta carona já partiu há mais de 30 minutos.");
    }

    // Verificar duplicidade (permite re-reserva se a anterior foi cancelada ou REJEITADA)
    $stmtCheck = $pdo->prepare("SELECT id FROM bookings WHERE ride_id = ? AND passenger_id = ? AND status NOT IN ('canceled', 'rejected')");
    $stmtCheck->execute([$rideId, $passengerId]);
    if ($stmtCheck->fetch()) {
        throw new Exception("Você já tem uma solicitação ativa nesta carona.");
    }

    // 3. RESERVA ATÔMICA: Tenta decrementar SOMENTE se tiver vaga (> 0)
    $stmtDecrement = $pdo->prepare("UPDATE rides SET seats_available = seats_available - 1 WHERE id = ? AND seats_available > 0");
    $stmtDecrement->execute([$rideId]);

    if ($stmtDecrement->rowCount() === 0) {
        // Nenhuma linha afetada = sem vagas
        throw new Exception("Vagas esgotadas! Alguém reservou antes de você.");
    }

    // 4. Vaga reservada provisoriamente (PENDING)
    $meetingPoint = trim($input['meetingPoint'] ?? '');
    $note = mb_substr(trim($input['note'] ?? ''), 0, 100);

    // Status 'pending' é o gatilho para a confirmação do motorista
    $stmtBook = $pdo->prepare("INSERT INTO bookings (ride_id, passenger_id, meeting_point, note, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmtBook->execute([$rideId, $passengerId, $meetingPoint, $note]);

    $pdo->commit();

    // 5. Notificar o Motorista
    $stmtMe = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmtMe->execute([$passengerId]);
    $passengerName = $stmtMe->fetchColumn() ?: 'Alguém';

    $notifMsg = "🔔 Solicitação de vaga: {$passengerName} quer entrar!";
    if ($note)
        $notifMsg .= " Obs: {$note}";

    createNotification($pdo, $ride['driver_id'], 'booking_request', $notifMsg, 'index.php?page=my_rides');

    // 6. Retorno de Sucesso (Flow 'Solicitação')
    echo json_encode([
        'success' => true,
        'message' => 'Solicitação enviada!',
        'driver_phone' => $ride['driver_phone'],
        // Não enviamos dados sensíveis (placa/pix) ainda
        'car_model' => $ride['car_model'] ?? 'Carro'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

