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

    // 3. Ajustar Horário
    $originalDate = date('Y-m-d', strtotime($original['departure_time']));
    $reqTime = $input['repeat_time'] ?? '';

    if (!empty($reqTime)) {
        // Se enviou o horário (ex: "18:00"), usa a mesma data da original
        $newDepartureTime = $originalDate . ' ' . $reqTime . ':00';
    } else {
        // Fallback: +9 horas ou o que foi enviado completo
        $newDepartureTime = $input['newDepartureTime'] ?? date('Y-m-d H:i:s', strtotime($original['departure_time'] . ' + 9 hours'));
    }

    // Validação e Ajuste Temporal Inteligente
    $isPast = (strtotime($newDepartureTime) < time());
    if ($isPast) {
        // Se a volta calculada caiu no passado (ex: repetindo carona de ontem),
        // agenda automaticamente para daqui a 2 horas.
        $newDepartureTime = date('Y-m-d H:i:s', strtotime('+2 hours'));
    }

    // 4. Inserir nova carona (Sincronizado com create_ride.php)
    $stmtInsert = $pdo->prepare("
        INSERT INTO rides (
            driver_id, origin_text, destination_text, waypoints, departure_time, 
            seats_total, seats_available, price, tags, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())
    ");

    $stmtInsert->execute([
        $userId,
        $newOriginText,
        $newDestinationText,
        $original['waypoints'],
        $newDepartureTime,
        $original['seats_total'],
        $original['seats_total'], // Restaura as vagas (limpa os fantasmas da ida)
        $original['price'],
        $original['tags']         // Copia observações/detalhes
    ]);

    $msg = $isPast
        ? 'Carona de volta agendada para hoje (+2h), pois a data original já passou.'
        : 'Carona de volta criada com sucesso!';

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'new_ride_id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao criar viagem de volta.']);
}
?>