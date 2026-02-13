<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso Negado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    if ($action === 'cleanup_files') {
        $uploadDirs = [
            '../assets/media/uploads/users/',
            '../assets/media/uploads/cars/'
        ];

        // 1. Coletar todas as URLs do banco
        $usedUrls = [];
        $stmtUsers = $pdo->query("SELECT photo_url FROM users WHERE photo_url IS NOT NULL AND photo_url != ''");
        while ($row = $stmtUsers->fetchColumn()) {
            $usedUrls[] = basename($row);
        }

        $stmtCars = $pdo->query("SELECT photo_url FROM cars WHERE photo_url IS NOT NULL AND photo_url != ''");
        while ($row = $stmtCars->fetchColumn()) {
            $usedUrls[] = basename($row);
        }

        $removedCount = 0;
        $savedBytes = 0;

        foreach ($uploadDirs as $dir) {
            if (!is_dir($dir))
                continue;

            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..')
                    continue;

                // Se o arquivo físico NÃO está na lista de usados do banco
                if (!in_array($file, $usedUrls)) {
                    $filePath = $dir . $file;
                    $savedBytes += filesize($filePath);
                    unlink($filePath);
                    $removedCount++;
                }
            }
        }

        $savedMb = round($savedBytes / (1024 * 1024), 2);
        echo json_encode([
            'success' => true,
            'message' => "Faxina concluída! {$removedCount} arquivos órfãos removidos. Espaço recuperado: {$savedMb} MB."
        ]);

    } elseif ($action === 'broadcast') {
        $message = trim($input['message'] ?? '');
        if (empty($message))
            throw new Exception("A mensagem não pode estar vazia.");

        // Inserir notificação para TODOS os usuários
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, link_url, created_at)
            SELECT id, 'system', :msg, 'index.php?page=home', NOW() FROM users
        ");
        $stmt->execute([':msg' => "📢 ALERTA GERAL: " . $message]);

        echo json_encode(['success' => true, 'message' => 'Mensagem enviada com sucesso para toda a base!']);

    } elseif ($action === 'purge_old') {
        // Apagar caronas com mais de 3 meses
        $stmt = $pdo->query("DELETE FROM rides WHERE departure_time < DATE_SUB(NOW(), INTERVAL 3 MONTH)");
        $count = $stmt->rowCount();

        echo json_encode(['success' => true, 'message' => "Expurgo concluído! {$count} caronas antigas (e suas dependências) foram removidas das base (Histórico anterior a 3 meses removido com sucesso)."]);

    } else {
        throw new Exception("Ação de manutenção desconhecida.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
