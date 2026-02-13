<?php
/**
 * API: Atualizar Perfil e Carro
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

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

// 1. Atualizar User
$name = trim($input['name'] ?? '');
$bio = trim($input['bio'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'O nome é obrigatório.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Update User
    $pixKey = trim($input['pix_key'] ?? '');
    $isDriver = !empty($input['is_driver']) ? 1 : 0;

    $stmtUser = $pdo->prepare("UPDATE users SET name = ?, bio = ?, pix_key = ?, is_driver = ? WHERE id = ?");
    $stmtUser->execute([$name, $bio, $pixKey, $isDriver, $userId]);

    $_SESSION['is_driver'] = $isDriver;

    // 2. Atualizar Carro (Se for motorista)
    if ($isDriver) {
        $model = trim($input['car_model'] ?? '');
        $color = trim($input['car_color'] ?? '');
        $plate = trim($input['car_plate'] ?? '');

        // Verificar se usuário já tem carro
        $stmtCheck = $pdo->prepare("SELECT id FROM cars WHERE user_id = ?");
        $stmtCheck->execute([$userId]);
        $existingCar = $stmtCheck->fetchColumn();

        if ($existingCar) {
            // Update
            $stmtUpdateCar = $pdo->prepare("UPDATE cars SET model = ?, color = ?, plate = ? WHERE user_id = ?");
            $stmtUpdateCar->execute([$model, $color, $plate, $userId]);
        } else {
            // Insert
            $stmtInsertCar = $pdo->prepare("INSERT INTO cars (user_id, model, color, plate) VALUES (?, ?, ?, ?)");
            $stmtInsertCar->execute([$userId, $model, $color, $plate]);
        }
    } else {
        // Se desmarcou (opcional: poderia deletar o carro, mas por segurança vamos apenas manter ou ignorar)
        // Por enquanto, não faz nada. 
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Perfil salvo.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar perfil.', 'debug' => $e->getMessage()]);
}
