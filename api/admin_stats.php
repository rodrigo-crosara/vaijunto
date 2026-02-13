<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso Negado']);
    exit;
}

try {
    // 1. KPIs
    // Total de Usuários
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Caronas Hoje (partindo de agora em diante)
    $ridesToday = $pdo->query("SELECT COUNT(*) FROM rides WHERE departure_time >= NOW() AND status != 'canceled'")->fetchColumn();

    // Total de Reservas
    $totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status != 'rejected' AND status != 'canceled'")->fetchColumn();

    // Receita Estimada (Soma de prices das reservas confirmadas/não canceladas)
    $revenue = $pdo->query("
        SELECT SUM(r.price) 
        FROM bookings b 
        JOIN rides r ON b.ride_id = r.id 
        WHERE b.status = 'confirmed' AND r.status != 'canceled'
    ")->fetchColumn() ?: 0;

    // 2. Recentes: Últimos 5 usuários cadastrados
    $recentUsers = $pdo->query("
        SELECT id, name, phone, photo_url, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Caronas Recentes: Últimas 5 caronas criadas
    $recentRides = $pdo->query("
        SELECT r.id, r.origin_text, r.destination_text, r.departure_time, r.status, u.name as driver_name 
        FROM rides r 
        JOIN users u ON r.driver_id = u.id 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => $totalUsers,
            'rides_today' => $ridesToday,
            'total_bookings' => $totalBookings,
            'revenue' => (float) $revenue
        ],
        'recent_users' => $recentUsers,
        'recent_rides' => $recentRides
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
