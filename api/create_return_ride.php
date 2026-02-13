<?php
/**
 * API: Criar Viagem de Volta Baseada na Ida
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
$originalRideId = intval($input['originalRideId'] ?? 0);

if ($originalRideId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de carona inválido.']);
    exit;
}

try {
    // 1. Buscar os dados da carona original
    $stmt = $pdo->prepare("SELECT * FROM rides WHERE id = ? AND driver_id = ?");
    $stmt->execute([$originalRideId, $userId]);
    $original = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$original) {
        echo json_encode(['success' => false, 'message' => 'Carona original não encontrada ou sem permissão.']);
        exit;
    }

    // 2. Inverter Origem e Destino
    $newOriginText = $original['destination_text'];
    $newDestinationText = $original['origin_text'];
    $newOriginCoords = $original['destination_coords'];
    $newDestinationCoords = $original['origin_coords'];

    // 3. Ajustar Horário (+9 horas por padrão) ou usar o enviado
    $newDepartureTime = $input['newDepartureTime'] ?? date('Y-m-d H:i:s', strtotime($original['departure_time'] . ' + 9 hours'));

    // 4. Inserir nova carona
    $stmtInsert = $pdo->prepare("
        INSERT INTO rides (
            driver_id, origin_text, destination_text, origin_coords, destination_coords, 
            waypoints, departure_time, seats_total, seats_available, price, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())
    ");

    $stmtInsert->execute([
        $userId,
        $newOriginText,
        $newDestinationText,
        $newOriginCoords,
        $newDestinationCoords,
        $original['waypoints'], // Mantém os mesmos waypoints por enquanto (ideal seria inverter mas é complexo)
        $newDepartureTime,
        $original['seats_total'],
        $original['seats_total'], // Reseta vagas disponíveis
        $original['price']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Carona de volta criada com sucesso!',
        'new_ride_id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
}
?>