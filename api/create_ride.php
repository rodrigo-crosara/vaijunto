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
    // Definir a query SQL que será usada em ambas as lógicas
    $sql = "INSERT INTO rides (driver_id, origin_text, destination_text, waypoints, departure_time, seats_total, seats_available, price, tags, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";

    $ridesCreated = [];
    $repeat_days = $input['repeat_days'] ?? [];
    $repeat_time = $input['repeat_time'] ?? '';

    // Lógica Recorrente
    if (!empty($repeat_days) && !empty($repeat_time)) {
        // Criar para as próximas 2 semanas (14 dias)
        $today = new DateTime();
        $limit = new DateTime('+14 days');

        while ($today <= $limit) {
            // PHP: w (0 for Sunday, 6 for Saturday). Frontend: 1=Seg, 5=Sex. 
            // Ajuste: date('N') return 1 (Mon) through 7 (Sun)
            $currentDayOfWeek = $today->format('N'); // 1 = Seg, 5 = Sex

            // Check if current day is selected (values 1-5 sent from frontend)
            // Frontend sends string values "1", "2"...
            if (in_array($currentDayOfWeek, $repeat_days)) {
                // Montar datetime
                $dbDate = $today->format('Y-m-d') . ' ' . $repeat_time . ':00';

                // Validação extra: não criar no passado se for hoje e já passou a hora
                if (strtotime($dbDate) > time()) {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $driver_id,
                        $origin,
                        $destination,
                        $waypointsJson,
                        $dbDate,
                        $seats,
                        $seats,
                        $price,
                        $tagsJson
                    ]);
                    $ridesCreated[] = $pdo->lastInsertId();
                }
            }
            $today->modify('+1 day');
        }

        if (empty($ridesCreated)) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma data futura válida encontrada para os dias selecionados.']);
            exit;
        }

        // Retorna o ID do primeiro para o link
        $rideId = $ridesCreated[0];

    } else {
        // Lógica Única (Normal)
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $driver_id,
            $origin,
            $destination,
            $waypointsJson,
            $departure_time,
            $seats,
            $seats,
            $price,
            $tagsJson
        ]);
        $rideId = $pdo->lastInsertId();
    }

    // Registrar ação no log de segurança para rate limiting
    try {
        $pdo->prepare("INSERT INTO access_logs (ip_address, action_type, user_id) VALUES (?, 'create_ride', ?)")->execute([$ip, $userId]);
    } catch (PDOException $e) { /* Silencioso */
    }

    // 🚀 Notificar passageiros frequentes (Fãs)
    try {
        $stmtFans = $pdo->prepare("
            SELECT DISTINCT b.passenger_id 
            FROM bookings b
            JOIN rides r ON b.ride_id = r.id
            WHERE r.driver_id = ? 
              AND r.status = 'completed'
              AND r.departure_time > DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        $stmtFans->execute([$driver_id]);
        $fans = $stmtFans->fetchAll(PDO::FETCH_COLUMN);

        if ($fans) {
            require_once '../helpers/notification.php';
            $driverName = $_SESSION['user_name'] ?: 'Um motorista';
            $link = "index.php?ride_id=" . $rideId;

            foreach ($fans as $fanId) {
                // Evita notificar o próprio motorista
                if ($fanId == $driver_id)
                    continue;

                createNotification(
                    $pdo,
                    $fanId,
                    'system',
                    "🚗 $driverName postou uma nova carona para " . $destination . "!",
                    $link
                );
            }
        }
    } catch (Exception $e) {
        // Não falha a criação se a notificação der erro
    }

    echo json_encode(['success' => true, 'message' => 'Carona criada com waypoints!', 'ride_id' => $rideId]);

} catch (PDOException $e) {
    // Log do erro real no servidor se necessário
    // error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar carona no banco.', 'debug' => $e->getMessage()]);
}
