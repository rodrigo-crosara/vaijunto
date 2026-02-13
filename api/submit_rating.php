<?php
/**
 * API: Submeter Avaliação Pós-Viagem
 * Recebe: booking_id, score (1-5), comment (opcional)
 * Calcula e atualiza reputation do usuário avaliado.
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$reviewerId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$bookingId = intval($input['booking_id'] ?? 0);
$score = intval($input['score'] ?? 0);
$comment = trim($input['comment'] ?? '');

// Validações básicas
if ($bookingId <= 0 || $score < 1 || $score > 5) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos. Score deve ser entre 1 e 5.']);
    exit;
}

try {
    // 1. Buscar a booking e descobrir quem avaliar
    $stmt = $pdo->prepare("
        SELECT b.id, b.passenger_id, b.status, r.driver_id, r.id as ride_id, r.departure_time
        FROM bookings b
        JOIN rides r ON b.ride_id = r.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Reserva não encontrada.']);
        exit;
    }

    // 2. Verificar se o usuário logado participou desta viagem
    $isPassenger = ($booking['passenger_id'] == $reviewerId);
    $isDriver = ($booking['driver_id'] == $reviewerId);

    if (!$isPassenger && !$isDriver) {
        echo json_encode(['success' => false, 'message' => 'Você não participou dessa viagem.']);
        exit;
    }

    // 3. Verificar se a viagem já aconteceu
    if (strtotime($booking['departure_time']) > time()) {
        echo json_encode(['success' => false, 'message' => 'A viagem ainda não aconteceu.']);
        exit;
    }

    // 4. Determinar quem será avaliado
    // Passageiro avalia o Motorista; Motorista avalia o Passageiro
    $ratedUserId = $isPassenger ? $booking['driver_id'] : $booking['passenger_id'];

    // 5. Verificar duplicata
    $stmtCheck = $pdo->prepare("SELECT id FROM ratings WHERE booking_id = ? AND reviewer_id = ?");
    $stmtCheck->execute([$bookingId, $reviewerId]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Você já avaliou esta viagem.']);
        exit;
    }

    // 6. Inserir avaliação
    $pdo->beginTransaction();

    $stmtInsert = $pdo->prepare("
        INSERT INTO ratings (booking_id, reviewer_id, rated_user_id, ride_id, score, comment, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmtInsert->execute([$bookingId, $reviewerId, $ratedUserId, $booking['ride_id'], $score, $comment ?: null]);

    // 7. Recalcular a reputação média do usuário avaliado
    $stmtAvg = $pdo->prepare("SELECT ROUND(AVG(score), 1) as avg_score FROM ratings WHERE rated_user_id = ?");
    $stmtAvg->execute([$ratedUserId]);
    $newReputation = $stmtAvg->fetchColumn() ?: 5.0;

    // 8. Atualizar reputation na tabela users
    $stmtUpdate = $pdo->prepare("UPDATE users SET reputation = ? WHERE id = ?");
    $stmtUpdate->execute([$newReputation, $ratedUserId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Avaliação enviada! Obrigado.',
        'new_reputation' => $newReputation
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar avaliação.', 'debug' => $e->getMessage()]);
}
