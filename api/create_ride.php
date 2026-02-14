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

$userId = $_SESSION['user_id'];
$ip = $_SERVER['REMOTE_ADDR'];

// 1.1 Rate Limiting (User based) - Máximo 3 caronas por hora
try {
    $stmtLimit = $pdo->prepare("SELECT COUNT(*) FROM access_logs WHERE user_id = ? AND action_type = 'create_ride' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmtLimit->execute([$userId]);
    if ($stmtLimit->fetchColumn() >= 3) {
        echo json_encode(['success' => false, 'message' => 'Calma! Você já criou muitas caronas recentemente. Aguarde um pouco.']);
        exit;
    }
} catch (PDOException $e) { /* Silencioso */
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

// Sanitização de preço: brasileiro usa vírgula (R$ 10,50), MySQL quer ponto (10.50)
$priceInput = $input['price'] ?? '0';
$price = floatval(str_replace(',', '.', str_replace('.', '', $priceInput)));

$driver_id = $_SESSION['user_id'];

if (empty($origin) || empty($destination) || empty($departure_time) || $seats <= 0) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

// Validação: Data no passado
if (strtotime($departure_time) < time()) {
    echo json_encode(['success' => false, 'message' => 'A data e hora de saída devem ser futuras.']);
    exit;
}

// Validação: Preço negativo
if ($price < 0) {
    echo json_encode(['success' => false, 'message' => 'O preço não pode ser negativo.']);
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

    // Registrar ação no log de segurança para rate limiting
    try {
        $pdo->prepare("INSERT INTO access_logs (ip_address, action_type, user_id) VALUES (?, 'create_ride', ?)")->execute([$ip, $userId]);
    } catch (PDOException $e) { /* Silencioso */
    }

    echo json_encode(['success' => true, 'message' => 'Carona criada com waypoints!', 'ride_id' => $rideId]);

} catch (PDOException $e) {
    // Log do erro real no servidor se necessário
    // error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar carona no banco.', 'debug' => $e->getMessage()]);
}
