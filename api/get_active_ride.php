<?php
/**
 * API: Buscar Viagem Ativa do Passageiro
 * Critério: Reserva confirmada para hoje (entre -2h e +4h do horário de saída)
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // 1. Buscar se o usuário tem uma reserva confirmada para hoje
    // Intervalo: departure_time entre (NOW - 4h) e (NOW + 2h)
    $stmt = $pdo->prepare("
        SELECT 
            r.id as ride_id,
            r.destination_text,
            r.departure_time,
            r.price,
            u.name as driver_name,
            u.photo_url as driver_avatar,
            u.pix_key as driver_pix,
            u.phone as driver_phone,
            b.payment_status
        FROM bookings b
        JOIN rides r ON b.ride_id = r.id
        JOIN users u ON r.driver_id = u.id
        WHERE b.passenger_id = ? 
          AND b.status = 'confirmed'
          AND r.status = 'scheduled'
          AND r.departure_time >= DATE_SUB(NOW(), INTERVAL 4 HOUR)
          AND r.departure_time <= DATE_ADD(NOW(), INTERVAL 2 HOUR)
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $activeRide = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($activeRide) {
        echo json_encode([
            'success' => true,
            'data' => $activeRide
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Nenhuma viagem ativa no momento.'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>