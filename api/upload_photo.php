<?php
/**
 * API: Upload de Foto de Perfil
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$userId = $_SESSION['user_id'];

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload.']);
    exit;
}

$file = $_FILES['photo'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo inválido. Apenas JPG, PNG ou WEBP são permitidos.']);
    exit;
}

// Limite de 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'A imagem deve ter no máximo 5MB.']);
    exit;
}

// Diretório de Upload
$uploadDir = '../assets/media/uploads/users/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Gerar nome único
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = 'user_' . $userId . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . $fileName;
$publicPath = 'assets/media/uploads/users/' . $fileName; // Caminho para salvar no banco

// Mover arquivo
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    try {
        // Atualizar Banco
        $stmt = $pdo->prepare("UPDATE users SET photo_url = ? WHERE id = ?");
        $stmt->execute([$publicPath, $userId]);

        // Atualizar Sessão Imediatamente
        $_SESSION['user_photo'] = $publicPath;

        echo json_encode([
            'success' => true,
            'message' => 'Foto atualizada com sucesso!',
            'photo_url' => $publicPath
        ]);
    } catch (PDOException $e) {
        unlink($targetPath); // Remove arquivo se falhar DB
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar o arquivo.']);
}
