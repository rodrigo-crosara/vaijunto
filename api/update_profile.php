<?php
/**
 * API: Atualizar Perfil e Carro (com Telefone + Foto do Carro)
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Recebe dados via POST (padrão form-urlencoded ou multipart)
if (empty($_POST)) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

if (empty($input)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado enviado.']);
    exit;
}

// 1. Atualizar User
$name = trim($input['name'] ?? '');
$bio = trim($input['bio'] ?? '');
$newPhone = trim($input['phone'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'O nome é obrigatório.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Buscar telefone atual
    $stmtCurrent = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmtCurrent->execute([$userId]);
    $currentPhone = $stmtCurrent->fetchColumn();

    $phoneChanged = false;

    // Validação de telefone (se mudou)
    if (!empty($newPhone) && $newPhone !== $currentPhone) {
        // Verificar se o número já existe em outra conta
        $stmtCheckPhone = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
        $stmtCheckPhone->execute([$newPhone, $userId]);
        if ($stmtCheckPhone->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Este número de WhatsApp já está cadastrado em outra conta.']);
            exit;
        }
        $phoneChanged = true;
    }

    // PIN Update Logic
    $pin = $input['pin'] ?? '';
    $currentPin = $input['current_pin'] ?? '';

    // Se o usuário digitou um novo PIN (4 dígitos)
    if (!empty($pin)) {
        if (preg_match('/^\d{4}$/', $pin)) {
            // Busca o hash atual no banco
            $stmt = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            // Verifica se o PIN atual bate (ou se é a primeira vez/null, permite sem)
            if ($user['pin_hash'] && !password_verify($currentPin, $user['pin_hash'])) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'PIN atual incorreto.']);
                exit;
            }

            $pinHash = password_hash($pin, PASSWORD_DEFAULT);
            $stmtPin = $pdo->prepare("UPDATE users SET pin_hash = ? WHERE id = ?");
            $stmtPin->execute([$pinHash, $userId]);
        } else {
            // Rollback if PIN is invalid format but was attempted
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'O PIN deve ter 4 dígitos numéricos.']);
            exit;
        }
    }

    // Update User
    $pixKey = trim($input['pix_key'] ?? '');
    $isDriver = !empty($input['is_driver']) ? 1 : 0;

    if ($phoneChanged) {
        $stmtUser = $pdo->prepare("UPDATE users SET name = ?, bio = ?, phone = ?, pix_key = ?, is_driver = ? WHERE id = ?");
        $stmtUser->execute([$name, $bio, $newPhone, $pixKey, $isDriver, $userId]);
        $_SESSION['user_phone'] = $newPhone;
    } else {
        $stmtUser = $pdo->prepare("UPDATE users SET name = ?, bio = ?, pix_key = ?, is_driver = ? WHERE id = ?");
        $stmtUser->execute([$name, $bio, $pixKey, $isDriver, $userId]);
    }

    $_SESSION['is_driver'] = $isDriver;
    $_SESSION['user_name'] = $name;

    // 2. Atualizar Carro (Se for motorista)
    if ($isDriver) {
        $model = trim($input['car_model'] ?? '');
        $color = trim($input['car_color'] ?? '');
        $plate = trim($input['car_plate'] ?? '');

        if (!empty($model) && !empty($plate)) {
            // Verificar se usuário já tem carro
            $stmtCheck = $pdo->prepare("SELECT id FROM cars WHERE user_id = ?");
            $stmtCheck->execute([$userId]);
            $existingCar = $stmtCheck->fetchColumn();

            if ($existingCar) {
                $stmtUpdateCar = $pdo->prepare("UPDATE cars SET model = ?, color = ?, plate = ? WHERE user_id = ?");
                $stmtUpdateCar->execute([$model, $color, $plate, $userId]);
            } else {
                $stmtInsertCar = $pdo->prepare("INSERT INTO cars (user_id, model, color, plate) VALUES (?, ?, ?, ?)");
                $stmtInsertCar->execute([$userId, $model, $color, $plate]);
            }
        }
    }

    // 3. Upload da Foto do Carro (se enviada via multipart)
    if (isset($_FILES['car_photo']) && $_FILES['car_photo']['error'] === UPLOAD_ERR_OK) {
        $carFile = $_FILES['car_photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (in_array($carFile['type'], $allowed) && $carFile['size'] <= 5 * 1024 * 1024) {
            $ext = pathinfo($carFile['name'], PATHINFO_EXTENSION);
            $carFileName = "car_{$userId}_" . time() . ".{$ext}";
            $uploadDir = realpath(__DIR__ . '/../assets/media/uploads/cars') ?: __DIR__ . '/../assets/media/uploads/cars';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $carPath = $uploadDir . DIRECTORY_SEPARATOR . $carFileName;
            if (move_uploaded_file($carFile['tmp_name'], $carPath)) {
                $carPhotoUrl = "assets/media/uploads/cars/{$carFileName}";
                $stmtCarPhoto = $pdo->prepare("UPDATE cars SET photo_url = ? WHERE user_id = ?");
                $stmtCarPhoto->execute([$carPhotoUrl, $userId]);
            }
        }
    }

    $pdo->commit();

    $response = ['success' => true, 'message' => 'Perfil salvo.'];
    if ($phoneChanged) {
        $response['phone_changed'] = true;
        $response['new_phone'] = $newPhone;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar perfil.', 'debug' => $e->getMessage()]);
}
