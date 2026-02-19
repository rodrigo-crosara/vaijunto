<?php
/**
 * API de Login Zero Atrito
 * Cadastro implícito baseado no número de telefone.
 */

session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$phone = $_POST['phone'] ?? '';
$honeypot = $_POST['website_check'] ?? '';

// 1. Honeypot check
if (!empty($honeypot)) {
    // Silent kill for bots
    exit;
}

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'O telefone é obrigatório']);
    exit;
}

// 2. Rate Limiting (IP based)
$ip = $_SERVER['REMOTE_ADDR'];
try {
    $stmtLimit = $pdo->prepare("SELECT COUNT(*) FROM access_logs WHERE ip_address = ? AND action_type = 'login' AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmtLimit->execute([$ip]);
    if ($stmtLimit->fetchColumn() > 5) {
        echo json_encode(['success' => false, 'message' => 'Muitas tentativas. Tente novamente em 15 minutos.']);
        exit;
    }

    // Registrar tentativa
    $pdo->prepare("INSERT INTO access_logs (ip_address, action_type) VALUES (?, 'login')")->execute([$ip]);
} catch (PDOException $e) {
    // Silently continue if logs fail
}

// Limpeza básica do número (apenas números para o banco)
$cleanPhone = preg_replace('/\D/', '', $phone);
$pin = $_POST['pin'] ?? '';

if (strlen($pin) !== 4 || !ctype_digit($pin)) {
    echo json_encode(['success' => false, 'message' => 'O PIN deve ter 4 dígitos numéricos.']);
    exit;
}

try {
    // 1. Verificar se o usuário já existe
    $stmt = $pdo->prepare("SELECT id, name, photo_url, is_driver, is_admin, pin_hash FROM users WHERE phone = ? LIMIT 1");
    $stmt->execute([$cleanPhone]);
    $user = $stmt->fetch();

    if ($user) {
        // Encontrado: Verificar PIN
        if (!empty($user['pin_hash'])) {
            // Conta protegida: verificar hash
            if (!password_verify($pin, $user['pin_hash'])) {
                echo json_encode(['success' => false, 'message' => 'PIN incorreto.']);
                exit;
            }
        } else {
            // Migração: Usuário antigo sem PIN -> Definir este como o PIN permanente
            $newHash = password_hash($pin, PASSWORD_DEFAULT);
            $stmtUp = $pdo->prepare("UPDATE users SET pin_hash = ? WHERE id = ?");
            $stmtUp->execute([$newHash, $user['id']]);
        }

        // Login com sucesso
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'] ?? '';
        $_SESSION['user_photo'] = $user['photo_url'] ?? '';
        $_SESSION['is_driver'] = (int) $user['is_driver'];
        $_SESSION['is_admin'] = (int) $user['is_admin'];
        echo json_encode(['success' => true, 'type' => 'login']);

    } else {
        // Não encontrado: Criar Usuário (Cadastro Implícito com PIN)
        $pinHash = password_hash($pin, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (phone, pin_hash, is_driver, is_admin, created_at) VALUES (?, ?, 0, 0, NOW())");
        $stmt->execute([$cleanPhone, $pinHash]);

        $newUserId = $pdo->lastInsertId();
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = '';
        $_SESSION['user_photo'] = '';
        $_SESSION['is_driver'] = 0;
        $_SESSION['is_admin'] = 0;

        require_once '../helpers/notification.php';
        createNotification(
            $pdo,
            $newUserId,
            'system',
            "Dica: Perfis com foto são aceitos 3x mais rápido! Adicione a sua agora em 'Meu Perfil'.",
            "index.php?page=profile"
        );

        echo json_encode(['success' => true, 'type' => 'register']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
