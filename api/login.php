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

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'O telefone é obrigatório']);
    exit;
}

// Limpeza básica do número (apenas números para o banco)
$cleanPhone = preg_replace('/\D/', '', $phone);

try {
    // 1. Verificar se o usuário já existe
    $stmt = $pdo->prepare("SELECT id, name, photo_url, is_driver, is_admin FROM users WHERE phone = ? LIMIT 1");
    $stmt->execute([$cleanPhone]);
    $user = $stmt->fetch();

    if ($user) {
        // Encontrado: Login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'] ?? '';
        $_SESSION['user_photo'] = $user['photo_url'] ?? '';
        $_SESSION['is_driver'] = (int) $user['is_driver'];
        $_SESSION['is_admin'] = (int) $user['is_admin'];
        echo json_encode(['success' => true, 'type' => 'login']);
    } else {
        // Não encontrado: Criar Usuário (Cadastro Implícito)
        $stmt = $pdo->prepare("INSERT INTO users (phone, is_driver, is_admin, created_at) VALUES (?, 0, 0, NOW())");
        $stmt->execute([$cleanPhone]);

        $newUserId = $pdo->lastInsertId();
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = '';
        $_SESSION['user_photo'] = '';
        $_SESSION['is_driver'] = 0;
        $_SESSION['is_admin'] = 0;

        echo json_encode(['success' => true, 'type' => 'register']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
