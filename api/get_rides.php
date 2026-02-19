<?php
/**
 * API: Buscar caronas paginadas
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$offset = intval($_GET['offset'] ?? 0);
$limit = 10;

try {
    $sql = "SELECT r.*, u.name as driver_name, u.photo_url, u.reputation, c.model as car_model, c.color as car_color, c.plate as car_plate, u.phone as driver_phone
            FROM rides r
            JOIN users u ON r.driver_id = u.id
            LEFT JOIN cars c ON c.user_id = u.id
            WHERE r.departure_time >= NOW() AND r.seats_available > 0 AND r.status != 'canceled'
            ORDER BY r.departure_time ASC
            LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca IDs das caronas que o usuário já reservou (otimizado: só para as retornadas)
    $myBookings = [];
    if ($rides) {
        $rideIds = array_column($rides, 'id');
        $placeholders = implode(',', array_fill(0, count($rideIds), '?'));
        $stmtBookings = $pdo->prepare("SELECT ride_id FROM bookings WHERE passenger_id = ? AND ride_id IN ($placeholders)");
        $stmtBookings->execute(array_merge([$currentUserId], $rideIds));
        $myBookings = $stmtBookings->fetchAll(PDO::FETCH_COLUMN);
    }

    $html = '';
    foreach ($rides as $ride) {
        $isDriver = ($ride['driver_id'] == $currentUserId);
        $isBooked = in_array($ride['id'], $myBookings);
        $time = date('H:i', strtotime($ride['departure_time']));
        $day = date('d/m', strtotime($ride['departure_time']));
        $avatar = $ride['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($ride['driver_name']) . "&background=random";

        ob_start();
        ?>
        <div class="card shadow-sm border-0 rounded-2xl overflow-hidden bg-white hover:shadow-md transition-shadow">
            <div class="card-body p-5">
                <!-- Topo: Motorista -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Motorista"
                            class="w-10 h-10 rounded-full border border-gray-100 shadow-sm">
                        <div class="flex flex-col">
                            <span class="text-gray-900 font-bold text-sm leading-tight">
                                <?= htmlspecialchars($ride['driver_name']) ?>
                            </span>
                            <div class="flex items-center text-warning gap-1">
                                <i class="bi bi-star-fill text-[8px]"></i>
                                <span class="text-[10px] font-semibold text-gray-500">
                                    <?= $ride['reputation'] ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">
                            <?= date('d/m \à\s H:i', strtotime($ride['departure_time'])) ?>
                        </span>
                        <span class="text-lg font-extrabold text-primary">
                            <!-- Hora já mostrada acima, aqui só destaque se quiser -->
                        </span>
                    </div>
                </div>

                <!-- Rota Compacta -->
                <div class="flex flex-col gap-3 mb-5 relative pl-2">
                    <div class="absolute left-[9px] top-2 bottom-3 w-[2px] border-l-2 border-dashed border-gray-200"></div>
                    <div class="flex items-start gap-4 relative z-10">
                        <div class="w-2.5 h-2.5 rounded-full border-2 border-primary bg-white mt-1"></div>
                        <span class="text-gray-700 font-medium text-sm line-clamp-1">
                            <?= htmlspecialchars($ride['origin_text']) ?>
                        </span>
                    </div>
                    <div class="flex items-start gap-4 relative z-10">
                        <div class="w-2.5 h-2.5 rounded-full bg-primary mt-1"></div>
                        <span class="text-gray-900 font-bold text-sm line-clamp-1">
                            <?= htmlspecialchars($ride['destination_text']) ?>
                        </span>
                    </div>
                </div>

                <!-- Rodapé: Vagas e Preço -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5 text-gray-500">
                            <i class="bi bi-people-fill text-sm"></i>
                            <span class="text-xs font-bold">
                                <?= $ride['seats_available'] ?> vagas
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 text-primary">
                            <i class="bi bi-cash-stack text-sm"></i>
                            <span class="text-xs font-extrabold">R$
                                <?= number_format($ride['price'], 2, ',', '.') ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($isDriver): ?>
                        <span class="badge badge-light-primary rounded-lg font-bold uppercase text-[9px] px-3 py-1.5">Sua
                            Carona</span>
                    <?php elseif ($isBooked): ?>
                        <div class="flex gap-2">
                            <a href="https://wa.me/<?= preg_replace('/\D/', '', $ride['driver_phone']) ?>" target="_blank"
                                class="btn btn-sm btn-light-success rounded-xl font-bold px-4">
                                WhatsApp
                            </a>
                            <span
                                class="badge badge-light-info rounded-lg font-bold uppercase text-[9px] flex items-center">Reservado</span>
                        </div>
                    <?php else: ?>
                        <button
                            onclick="reservarCarona(<?= $ride['id'] ?>, '<?= $ride['price'] ?>', '<?= addslashes($ride['origin_text']) ?>', '<?= addslashes($ride['destination_text']) ?>')"
                            class="btn btn-primary btn-sm rounded-xl px-6 font-bold shadow-lg shadow-primary/20 hover:scale-105 transition-transform">
                            Reservar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        $html .= ob_get_clean();
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($rides)
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>