<?php
/**
 * API: Retorna APENAS o HTML da lista de passageiros de uma carona.
 * Usado para Polling no Painel do Motorista.
 */
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

$rideId = intval($_GET['ride_id'] ?? 0);
if ($rideId <= 0) {
    exit;
}

try {
    // Buscar passageiros desta carona
    $stmt = $pdo->prepare("
        SELECT b.id as booking_id, u.name, u.photo_url, u.phone, b.meeting_point, b.status as booking_status, b.payment_status
        FROM bookings b
        JOIN users u ON b.passenger_id = u.id
        WHERE b.ride_id = ? AND b.status != 'rejected'
        ORDER BY b.created_at ASC
    ");
    $stmt->execute([$rideId]);
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($passengers)) {
        echo '<div class="text-center py-2"><p class="text-blue-200 text-xs italic">Aguardando passageiros...</p></div>';
        exit;
    }

    echo '<div class="space-y-3">';
    foreach ($passengers as $p) {
        $isPaid = ($p['payment_status'] === 'paid');
        $pPhone = preg_replace('/\D/', '', $p['phone']);
        ?>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="<?= $p['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($p['name']) ?>"
                    class="w-8 h-8 rounded-full border border-white/30">
                <div class="flex flex-col">
                    <span class="text-sm font-bold leading-tight">
                        <?= explode(' ', $p['name'])[0] ?>
                    </span>
                    <span class="text-[10px] text-blue-200 truncate max-w-[100px]">
                        <i class="bi bi-geo-alt-fill"></i>
                        <?= htmlspecialchars($p['meeting_point']) ?>
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="https://wa.me/<?= $pPhone ?>" target="_blank"
                    class="w-8 h-8 rounded-full bg-green-500/20 text-green-300 flex items-center justify-center hover:bg-green-500 hover:text-white transition-all">
                    <i class="bi bi-whatsapp text-sm"></i>
                </a>
                <?php if ($isPaid): ?>
                    <span class="badge bg-green-500 text-[9px] font-bold py-1.5 px-2 rounded-lg">PAGO ✅</span>
                <?php else: ?>
                    <button onclick="confirmarPagamento(<?= $p['booking_id'] ?>)"
                        class="btn btn-xs btn-light-success text-[9px] font-bold py-1 px-2 rounded-lg flex items-center gap-1">
                        💰 Confirmar Pagamento
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    echo '</div>';

} catch (PDOException $e) {
    // Silencioso no polling
}
