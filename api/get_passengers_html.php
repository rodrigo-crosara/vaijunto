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
    $stmt = $pdo->prepare("
        SELECT b.id as booking_id, u.name, u.photo_url, u.phone, b.meeting_point, b.note, b.status as booking_status, b.payment_status
        FROM bookings b
        JOIN users u ON b.passenger_id = u.id
        WHERE b.ride_id = ? AND b.status NOT IN ('rejected', 'canceled')
        ORDER BY b.created_at ASC
    ");
    $stmt->execute([$rideId]);
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($passengers)) {
        echo '<div class="text-center py-2"><p class="text-blue-200 text-xs italic">Aguardando passageiros...</p></div>';
        exit;
    }

    echo '<div class="space-y-4">'; // Aumentado o espaçamento entre passageiros
    foreach ($passengers as $p) {
        $isPaid = ($p['payment_status'] === 'paid');
        $pPhone = preg_replace('/\D/', '', $p['phone']);
        $pPhone = ltrim($pPhone, '0'); // Remove zero à esquerda do DDD
        if (strlen($pPhone) === 11 || strlen($pPhone) === 10)
            $pPhone = '55' . $pPhone;
        ?>
        <div class="flex flex-col gap-2"> <!-- Container vertical para suportar a nota -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="<?= $p['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($p['name']) ?>"
                        class="w-8 h-8 rounded-full border border-white/30">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold leading-tight">
                            <?= explode(' ', $p['name'])[0] ?>
                        </span>
                        <span class="text-[10px] text-blue-200 truncate max-w-[120px]">
                            <i class="bi bi-geo-alt-fill"></i>
                            <?= htmlspecialchars($p['meeting_point']) ?>
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <?php if ($p['booking_status'] === 'confirmed'): ?>
                        <a href="https://wa.me/<?= $pPhone ?>" target="_blank"
                            class="w-8 h-8 rounded-full bg-green-500/20 text-green-300 flex items-center justify-center hover:bg-green-500 hover:text-white transition-all">
                            <i class="bi bi-whatsapp text-sm"></i>
                        </a>
                        <?php if ($isPaid): ?>
                            <button onclick="desfazerPagamento(<?= $p['booking_id'] ?>)"
                                class="badge bg-green-500 text-[9px] font-bold py-1.5 px-2 rounded-lg border-0 cursor-pointer hover:bg-green-600 transition-colors">
                                PAGO ✅
                            </button>
                        <?php else: ?>
                            <button onclick="confirmarPagamento(<?= $p['booking_id'] ?>)"
                                class="btn btn-xs btn-light-success text-[9px] font-bold py-1 px-2 rounded-lg flex items-center gap-1">
                                💰 <span class="hidden sm:inline">Confirmar</span>
                            </button>
                        <?php endif; ?>
                    <?php elseif ($p['booking_status'] === 'pending'): ?>
                        <div class="flex gap-1">
                            <button onclick="responderSolicitacao(<?= $p['booking_id'] ?>, 'confirm')"
                                class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center shadow-lg hover:scale-110 transition-transform"
                                title="Aceitar">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button onclick="responderSolicitacao(<?= $p['booking_id'] ?>, 'reject')"
                                class="w-8 h-8 rounded-full bg-red-50 text-red-500 flex items-center justify-center border border-red-500/20 hover:bg-red-500 hover:text-white transition-all"
                                title="Recusar">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Nota do Passageiro (Avisos de bagagem, camisa, etc) -->
            <?php if (!empty($p['note'])): ?>
                <div class="px-2">
                    <div
                        class="bg-yellow-400/20 text-yellow-100 text-[9px] font-bold px-2 py-1 rounded-lg flex items-center gap-1.5 w-fit border border-yellow-400/20">
                        <i class="bi bi-chat-left-text-fill text-[8px]"></i>
                        <?= htmlspecialchars($p['note']) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div> <!-- Fim do flex-col gap-2 -->
        <?php
    }
    echo '</div>';

} catch (PDOException $e) {
    // Silencioso no polling
}
