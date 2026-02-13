<?php
/**
 * API: Criar Nova Carona (Atualizada com Waypoints e Tags)
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// 1. Segurança: Verificar Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

// 2. Receber Dados (JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

// 3. Sanitização e Validação
$origin = trim($input['origin'] ?? '');
$destination = trim($input['destination'] ?? '');
$waypointsInput = trim($input['waypoints'] ?? ''); // Plain tex, comma separated
$detailsInput = trim($input['details'] ?? ''); // Novo campo de observações

$departure_time = $input['departure_time'] ?? '';
$seats = intval($input['seats'] ?? 0);
$price = floatval($input['price'] ?? 0);
$driver_id = $_SESSION['user_id'];

if (empty($origin) || empty($destination) || empty($departure_time) || $seats <= 0) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

// 4. Processamento de Dados Complexos (JSON)

// Waypoints: Explode string por vírgula e salva como JSON Array
$waypointsJson = null;
if (!empty($waypointsInput)) {
    $waypointsArray = array_map('trim', explode(',', $waypointsInput));
    $waypointsJson = json_encode($waypointsArray);
}

// Tags/Details: Salvar observações no campo JSON `tags`
$tagsJson = null;
if (!empty($detailsInput)) {
    // Cria um objeto JSON simples. Pode ser expandido futuramente.
    $tagsJson = json_encode(['details' => $detailsInput]);
}

// 5. Inserção no Banco
try {
    // Nota: Usamos as colunas existentes `waypoints` e `tags` (esta última para guardar as new 'observations')
    $sql = "INSERT INTO rides (driver_id, origin_text, destination_text, waypoints, departure_time, seats_total, seats_available, price, tags, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $driver_id,
        $origin,
        $destination,
        $waypointsJson, // Salva o JSON dos waypoints ou NULL
        $departure_time,
        $seats,
        $seats, // Inicialmente disponíveis = total
        $price,
        $tagsJson // Salva JSON com { "details": "..." } ou NULL
    ]);

    $rideId = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'message' => 'Carona criada com waypoints!', 'ride_id' => $rideId]);

} catch (PDOException $e) {
    // Log do erro real no servidor se necessário
    // error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar carona no banco.', 'debug' => $e->getMessage()]);
}
