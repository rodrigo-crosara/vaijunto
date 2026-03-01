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
        $pdo->beginTransaction();

        // 1. Apagar access_logs antigos (mais de 1 mês)
        $pdo->query("DELETE FROM access_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");

        // 2. Apagar ratings de caronas com mais de 3 meses
        $pdo->query("DELETE rt FROM ratings rt JOIN rides r ON rt.ride_id = r.id WHERE r.departure_time < DATE_SUB(NOW(), INTERVAL 3 MONTH)");

        // 3. Apagar bookings de caronas com mais de 3 meses
        $pdo->query("DELETE b FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE r.departure_time < DATE_SUB(NOW(), INTERVAL 3 MONTH)");

        // 4. Agora sim apagar as caronas (sem dependências)
        $stmtRides = $pdo->query("DELETE FROM rides WHERE departure_time < DATE_SUB(NOW(), INTERVAL 3 MONTH)");
        $countRides = $stmtRides->rowCount();

        // 5. Apagar notificações velhas (mais de 3 meses)
        $stmtNotifOld = $pdo->query("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 MONTH)");

        // 6. Apagar notificações LIDAS com mais de 1 mês (limpeza agressiva)
        $stmtNotifRead = $pdo->query("DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");

        $totalNotifDeleted = $stmtNotifOld->rowCount() + $stmtNotifRead->rowCount();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Expurgo concluído! {$countRides} caronas e {$totalNotifDeleted} notificações antigas foram removidas (Histórico de 3 meses mantido)."
        ]);

    } else {
        throw new Exception("Ação de manutenção desconhecida.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
