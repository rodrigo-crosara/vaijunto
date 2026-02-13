<?php
/**
 * API: Busca Inteligente de Caronas
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$query = trim($_GET['query'] ?? '');
$timeFilter = trim($_GET['time'] ?? '');

try {
    $params = [];
    $sql = "SELECT r.*, u.name as driver_name, u.photo_url, u.reputation, c.model as car_model, u.phone as driver_phone
            FROM rides r
            JOIN users u ON r.driver_id = u.id
            LEFT JOIN cars c ON c.user_id = u.id
            WHERE r.status != 'canceled' 
              AND r.seats_available > 0 
              AND r.departure_time >= NOW()";

    if ($query !== '') {
        $sql .= " AND (LOWER(r.origin_text) LIKE LOWER(?) OR LOWER(r.destination_text) LIKE LOWER(?) OR LOWER(r.waypoints) LIKE LOWER(?))";
        $searchTerm = "%$query%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($timeFilter !== '') {
        // Ordenar caronas mais próximas do horário desejado (considerando o dia de hoje)
        $targetTime = date('Y-m-d') . ' ' . $timeFilter . ':00';
        $sql .= " ORDER BY ABS(TIMESTAMPDIFF(SECOND, r.departure_time, ?)) ASC";
        $params[] = $targetTime;
    } else {
        $sql .= " ORDER BY r.departure_time ASC";
    }

    $sql .= " LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar reservas do usuário para estas caronas
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

        // Verificar se passou por waypoints para exibir badge
        $passByMatch = null;
        if ($query !== '') {
            $waypoints = json_decode($ride['waypoints'], true) ?: [];
            foreach ($waypoints as $wp) {
                if (stripos($wp, $query) !== false) {
                    $passByMatch = $wp;
                    break;
                }
            }
        }

        ob_start();
        ?>
        <div class="bg-white rounded-[2.5rem] p-6 shadow-[0_4px_25px_rgba(0,0,0,0.02)] border border-gray-50 flex flex-col hover:shadow-xl hover:shadow-gray-200/40 transition-all active:scale-[0.98] mb-4">
            <!-- Topo -->
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="D"
                        class="w-12 h-12 rounded-full border-2 border-white shadow-sm object-cover">
                    <div class="flex flex-col">
                        <span class="text-gray-900 font-bold text-sm leading-tight"><?= htmlspecialchars($ride['driver_name']) ?></span>
                        <div class="flex items-center text-warning gap-1">
                            <i class="bi bi-star-fill text-[9px]"></i>
                            <span class="text-[10px] font-bold text-gray-400"><?= $ride['reputation'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <span class="block text-[10px] font-bold text-gray-300 uppercase tracking-widest mb-1"><?= $day ?></span>
                    <span class="text-2xl font-black text-primary tracking-tighter"><?= $time ?></span>
                </div>
            </div>

            <!-- Rota Detalhada -->
            <div class="relative pl-6 mb-6">
                <div class="absolute left-2.5 top-3 bottom-8 w-0.5 border-l-2 border-dashed border-gray-200"></div>

                <!-- Origem -->
                <div class="flex items-start gap-3 relative mb-4">
                    <i class="bi bi-circle text-primary text-xs bg-white relative z-10 mt-1"></i>
                    <div>
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Saída</span>
                        <span class="text-gray-900 font-bold text-sm leading-tight"><?= htmlspecialchars($ride['origin_text']) ?></span>
                    </div>
                </div>

                <!-- Waypoints -->
                <?php
                $waypoints = json_decode($ride['waypoints'] ?? '[]', true);
                if (!empty($waypoints)):
                    foreach ($waypoints as $point):
                        ?>
                        <div class="flex items-start gap-4 relative mb-4">
                            <i class="bi bi-dot text-gray-300 text-xl -ml-1.5 -mt-1 bg-white relative z-10"></i>
                            <span class="text-gray-500 font-medium text-xs"><?= htmlspecialchars($point) ?></span>
                        </div>
                    <?php
                    endforeach;
                endif;
                ?>

                <!-- Destino -->
                <div class="flex items-start gap-3 relative">
                    <i class="bi bi-geo-alt-fill text-primary text-xs bg-white relative z-10 mt-1"></i>
                    <div>
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Chegada</span>
                        <span class="text-gray-900 font-extrabold text-sm leading-tight"><?= htmlspecialchars($ride['destination_text']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Observações (Regras) -->
            <?php 
            // Recuperar details das tags se não tiver coluna dedicated (mas criamos details no input, o DB pode ter tags ou não, usamos details do input fetch?)
            // O endpoint search traz r.*, se details foi salvo em tags no create_ride, precisamos extrair.
            // No create_ride: $tagsJson = json_encode(['details' => $detailsInput]);
            $details = '';
            if (!empty($ride['tags'])) {
                $tags = json_decode($ride['tags'], true);
                $details = $tags['details'] ?? '';
            }
            if (!empty($details)): ?>
                <div class="bg-yellow-50 text-yellow-800 rounded-xl p-4 mb-5 flex gap-3 items-start text-xs font-medium">
                    <i class="bi bi-info-circle-fill text-yellow-500 text-sm shrink-0 mt-0.5"></i>
                    <span><?= nl2br(htmlspecialchars($details)) ?></span>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="flex items-center justify-between pt-5 border-t border-gray-50">
                <div class="flex gap-4">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-gray-300 uppercase">Vagas</span>
                        <span class="text-sm font-black text-gray-600"><?= $ride['seats_available'] ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-gray-300 uppercase">Valor</span>
                        <span class="text-sm font-black text-primary">R$ <?= number_format($ride['price'], 2, ',', '.') ?></span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <?php if ($isDriver): ?>
                        <button onclick='shareRide(<?= json_encode([
                            "id" => $ride['id'],
                            "origin" => $ride['origin_text'],
                            "destination" => $ride['destination_text'],
                            "departure_time" => $ride['departure_time'],
                            "price" => $ride['price'],
                            "waypoints" => $ride['waypoints']
                        ]) ?>)' 
                        class="bg-primary/10 text-primary px-5 py-2.5 rounded-2xl font-bold text-xs flex items-center gap-2 hover:bg-primary/20 transition-all">
                            <i class="bi bi-whatsapp text-lg"></i> Divulgar
                        </button>
                    <?php elseif ($isBooked): ?>
                        <a href="https://wa.me/<?= preg_replace('/\D/', '', $ride['driver_phone']) ?>" target="_blank"
                            class="bg-green-500 text-white px-5 py-2.5 rounded-2xl font-bold text-xs shadow-lg shadow-green-200 flex items-center gap-2">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    <?php else: ?>
                        <button
                            onclick='reservarCarona(<?= $ride['id'] ?>, "<?= $ride['price'] ?>", "<?= addslashes($ride['origin_text']) ?>", "<?= addslashes($ride['destination_text']) ?>", `<?= addslashes($ride['waypoints'] ?? "[]") ?>`)'
                            class="bg-gray-900 text-white px-8 py-3 rounded-2xl font-bold text-sm shadow-xl shadow-gray-400 hover:bg-black transition-all">
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