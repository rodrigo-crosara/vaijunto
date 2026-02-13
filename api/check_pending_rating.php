<?php
/**
 * API: Verificar se existem viagens pendentes de avaliação
 * Retorna a primeira booking concluída que o usuário ainda não avaliou.
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'pending' => false]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Buscar bookings concluídas (viagem no passado, status confirmed) que o usuário NÃO avaliou ainda
    $stmt = $pdo->prepare("
        SELECT 
            b.id as booking_id,
            b.passenger_id,
            r.id as ride_id,
            r.driver_id,
            r.origin_text,
            r.destination_text,
            r.departure_time,
            CASE 
                WHEN b.passenger_id = ? THEN u_driver.name
                ELSE u_passenger.name
            END as other_user_name,
            CASE 
                WHEN b.passenger_id = ? THEN u_driver.photo_url
                ELSE u_passenger.photo_url
            END as other_user_photo,
            CASE 
                WHEN b.passenger_id = ? THEN 'driver'
                ELSE 'passenger'
            END as rated_role
        FROM bookings b
        JOIN rides r ON b.ride_id = r.id
        JOIN users u_driver ON r.driver_id = u_driver.id
        JOIN users u_passenger ON b.passenger_id = u_passenger.id
        WHERE (b.passenger_id = ? OR r.driver_id = ?)
          AND b.status = 'confirmed'
          AND r.status != 'canceled'
          AND r.departure_time < DATE_SUB(NOW(), INTERVAL 1 HOUR)
          AND NOT EXISTS (
              SELECT 1 FROM ratings rt 
              WHERE rt.booking_id = b.id AND rt.reviewer_id = ?
          )
        ORDER BY r.departure_time DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    $pending = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pending) {
        $avatar = $pending['other_user_photo'] ?: "https://ui-avatars.com/api/?name=" . urlencode($pending['other_user_name']) . "&background=0D8FFD&color=fff";
        echo json_encode([
            'success' => true,
            'pending' => true,
            'booking_id' => $pending['booking_id'],
            'other_user_name' => $pending['other_user_name'],
            'other_user_photo' => $avatar,
            'rated_role' => $pending['rated_role'],
            'origin' => $pending['origin_text'],
            'destination' => $pending['destination_text'],
            'date' => date('d/m', strtotime($pending['departure_time']))
        ]);
    } else {
        echo json_encode(['success' => true, 'pending' => false]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'pending' => false, 'debug' => $e->getMessage()]);
}
