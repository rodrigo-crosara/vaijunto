<?php
/**
 * API: Excluir Conta do Usuário (com verificação de pendências)
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
$confirmation = trim($input['confirmation'] ?? '');

if ($confirmation !== 'DELETAR') {
    echo json_encode(['success' => false, 'message' => 'Confirmação inválida.']);
    exit;
}

try {
    // 1. Verificar viagens futuras como MOTORISTA
    $stmtDriver = $pdo->prepare("
        SELECT COUNT(*) FROM rides 
        WHERE driver_id = ? AND departure_time >= NOW() AND status != 'canceled'
    ");
    $stmtDriver->execute([$userId]);
    $pendingAsDriver = (int) $stmtDriver->fetchColumn();

    // 2. Verificar reservas futuras como PASSAGEIRO
    $stmtPassenger = $pdo->prepare("
        SELECT COUNT(*) FROM bookings b
        JOIN rides r ON b.ride_id = r.id
        WHERE b.passenger_id = ? AND r.departure_time >= NOW() AND b.status = 'confirmed' AND r.status != 'canceled'
    ");
    $stmtPassenger->execute([$userId]);
    $pendingAsPassenger = (int) $stmtPassenger->fetchColumn();

    if ($pendingAsDriver > 0 || $pendingAsPassenger > 0) {
        $msg = 'Você não pode excluir a conta com viagens pendentes. ';
        if ($pendingAsDriver > 0)
            $msg .= "Você tem {$pendingAsDriver} carona(s) futura(s) como motorista. ";
        if ($pendingAsPassenger > 0)
            $msg .= "Você tem {$pendingAsPassenger} reserva(s) futura(s) como passageiro. ";
        $msg .= 'Conclua ou cancele-as primeiro.';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    // 3. Sem pendências: limpar arquivos físicos antes do DELETE
    $stmtUserPhoto = $pdo->prepare("SELECT photo_url FROM users WHERE id = ?");
    $stmtUserPhoto->execute([$userId]);
    $userPhoto = $stmtUserPhoto->fetchColumn();

    $stmtCarPhoto = $pdo->prepare("SELECT photo_url FROM cars WHERE user_id = ?");
    $stmtCarPhoto->execute([$userId]);
    $carPhoto = $stmtCarPhoto->fetchColumn();

    // Apagar arquivos físicos do servidor
    if ($userPhoto && file_exists(__DIR__ . '/../' . $userPhoto)) {
        @unlink(__DIR__ . '/../' . $userPhoto);
    }
    if ($carPhoto && file_exists(__DIR__ . '/../' . $carPhoto)) {
        @unlink(__DIR__ . '/../' . $carPhoto);
    }

    // 4. Excluir conta (CASCADE cuida do resto no banco)
    $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmtDelete->execute([$userId]);

    // 5. Destruir sessão
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Conta excluída permanentemente.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir conta.', 'debug' => $e->getMessage()]);
}
