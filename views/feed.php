<?php
/**
 * View: Feed de Caronas (Lite & Fast)
 */

require_once 'config/db.php';

$currentUserId = $_SESSION['user_id'] ?? 0;

try {
    $limit = 10;
    $sql = "SELECT r.*, u.name as driver_name, u.photo_url, u.reputation, c.model as car_model, u.phone as driver_phone
            FROM rides r
            JOIN users u ON r.driver_id = u.id
            LEFT JOIN cars c ON c.user_id = u.id
            WHERE r.departure_time >= NOW() AND r.seats_available > 0 AND r.status != 'canceled'
            ORDER BY r.departure_time ASC
            LIMIT $limit";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $myBookings = [];
    if ($currentUserId) {
        $stmtBookings = $pdo->prepare("SELECT ride_id FROM bookings WHERE passenger_id = ?");
        $stmtBookings->execute([$currentUserId]);
        $myBookings = $stmtBookings->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (PDOException $e) {
    echo "<div class='p-4 bg-red-50 text-red-600 rounded-2xl'>Erro: " . $e->getMessage() . "</div>";
    $rides = [];
    $myBookings = [];
}
?>

<div id="view-container" class="max-w-lg mx-auto">

    <!-- Notificação Flutuante -->
    <div id="new-rides-notification" class="fixed top-24 left-1/2 -translate-x-1/2 z-[100] hidden">
        <button onclick="location.reload()"
            class="bg-primary text-white rounded-full px-6 py-3 font-bold shadow-2xl flex items-center gap-2 animate-bounce hover:scale-105 active:scale-95 transition-all">
            <i class="bi bi-arrow-clockwise text-lg"></i>
            <span>Ver novas caronas</span>
        </button>
    </div>

    <!-- Viagem Ativa -->
    <div id="active-ride-container" class="mb-4"></div>

    <!-- Smart Search Bar -->
    <div class="sticky top-20 z-40 bg-gray-50/95 backdrop-blur-md py-4 mb-8">
        <div class="flex gap-2">
            <div class="relative flex-grow">
                <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="search-query" oninput="debounceSearch()"
                    class="w-full pl-12 pr-4 py-4 rounded-[2rem] bg-white border border-gray-100 shadow-sm focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium"
                    placeholder="Para onde você vai?">
            </div>
            <div class="relative w-32 shrink-0">
                <i class="bi bi-clock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="time" id="search-time" onchange="performSearch()"
                    class="w-full pl-10 pr-2 py-4 rounded-3xl bg-white border border-gray-100 shadow-sm focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
            </div>
        </div>
    </div>

    <!-- Título -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Próximos Horários</h2>
        <span
            class="text-[10px] font-bold text-primary uppercase tracking-widest bg-primary/5 px-3 py-1.5 rounded-full">Atualizado
            agora</span>
    </div>

    <!-- Lista de Cards -->
    <div id="rides-list" class="space-y-4">
        <?php if (empty($rides)): ?>
            <div
                class="flex flex-col items-center justify-center py-20 text-center bg-white/60 rounded-[3rem] border-2 border-dashed border-gray-200 shadow-sm">
                <div class="bg-gray-100 rounded-full w-24 h-24 flex items-center justify-center mb-6 opacity-30">
                    <i class="bi bi-car-front text-6xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-extrabold text-gray-700 mb-2">Pista Livre</h3>
                <p class="text-gray-400 text-sm max-w-[200px]">Nenhuma carona encontrada para agora.</p>
            </div>
        <?php else: ?>
            <?php
            $maxRideId = 0;
            foreach ($rides as $ride):
                if ($ride['id'] > $maxRideId)
                    $maxRideId = $ride['id'];
                $isDriver = ($ride['driver_id'] == $currentUserId);
                $isBooked = in_array($ride['id'], $myBookings);
                $time = date('H:i', strtotime($ride['departure_time']));
                $day = date('d/m', strtotime($ride['departure_time']));
                $avatar = $ride['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($ride['driver_name']) . "&background=random";
                ?>

                <div
                    class="bg-white rounded-[2.5rem] p-6 shadow-[0_4px_25px_rgba(0,0,0,0.02)] border border-gray-50 flex flex-col hover:shadow-xl hover:shadow-gray-200/40 transition-all active:scale-[0.98]">
                    <!-- Topo -->
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <img src="<?= htmlspecialchars($avatar) ?>" alt="D"
                                class="w-12 h-12 rounded-full border-2 border-white shadow-sm object-cover">
                            <div class="flex flex-col">
                                <span
                                    class="text-gray-900 font-bold text-sm leading-tight"><?= htmlspecialchars($ride['driver_name']) ?></span>
                                <div class="flex items-center text-warning gap-1">
                                    <i class="bi bi-star-fill text-[9px]"></i>
                                    <span class="text-[10px] font-bold text-gray-400"><?= $ride['reputation'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span
                                class="block text-[10px] font-bold text-gray-300 uppercase tracking-widest mb-1"><?= $day ?></span>
                            <span class="text-2xl font-black text-primary tracking-tighter"><?= $time ?></span>
                        </div>
                    </div>

                    <!-- Rota -->
                    <div class="relative pl-6 mb-6 space-y-4">
                        <div class="absolute left-1 top-2 bottom-2 w-0.5 border-l-2 border-dashed border-gray-200"></div>
                        <div class="flex items-center gap-4 relative">
                            <div class="absolute -left-6 w-3 h-3 rounded-full border-2 border-primary bg-white"></div>
                            <span
                                class="text-gray-500 font-medium text-sm truncate"><?= htmlspecialchars($ride['origin_text']) ?></span>
                        </div>
                        <div class="flex items-center gap-4 relative">
                            <div class="absolute -left-6 w-3 h-3 rounded-full bg-primary border-4 border-primary text-white">
                            </div>
                            <span
                                class="text-gray-900 font-extrabold text-sm truncate"><?= htmlspecialchars($ride['destination_text']) ?></span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-5 border-t border-gray-50">
                        <div class="flex gap-4">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-bold text-gray-300 uppercase">Vagas</span>
                                <span class="text-sm font-black text-gray-600"><?= $ride['seats_available'] ?></span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-bold text-gray-300 uppercase">Valor</span>
                                <span class="text-sm font-black text-primary">R$
                                    <?= number_format($ride['price'], 2, ',', '.') ?></span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <?php if ($isDriver): ?>
                                <span class="text-[10px] font-bold text-primary-active bg-primary/5 px-4 py-2 rounded-2xl">Sua
                                    Carona</span>
                            <?php elseif ($isBooked): ?>
                                <a href="https://wa.me/<?= preg_replace('/\D/', '', $ride['driver_phone']) ?>" target="_blank"
                                    class="bg-green-500 text-white px-5 py-2.5 rounded-2xl font-bold text-xs shadow-lg shadow-green-200 flex items-center gap-2">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                            <?php else: ?>
                                <button
                                    onclick="reservarCarona(<?= $ride['id'] ?>, '<?= $ride['price'] ?>', '<?= addslashes($ride['origin_text']) ?>', '<?= addslashes($ride['destination_text']) ?>')"
                                    class="bg-gray-900 text-white px-8 py-3 rounded-2xl font-bold text-sm shadow-xl shadow-gray-400 hover:bg-black transition-all">
                                    Reservar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Load More -->
    <div id="load-more-container" class="mt-8 mb-12 flex justify-center <?= count($rides) < 10 ? 'hidden' : '' ?>">
        <button id="load-more-btn" onclick="loadMoreRides()"
            class="bg-white border border-gray-100 text-gray-500 rounded-3xl px-8 py-4 font-bold shadow-sm hover:shadow-md transition-all flex items-center gap-2">
            <span>Mais caronas</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>

</div>

<script>
    let lastRideId = <?= (int) ($maxRideId ?? 0) ?>;
    let offset = 10;
    let searchTimeout;

    // Busca Integrada
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 500);
    }

    async function performSearch() {
        const query = $('#search-query').val();
        const time = $('#search-time').val();
        const container = $('#rides-list');

        if (query === '' && time === '') { location.reload(); return; }

        showSkeleton(container);
        $('#load-more-container').addClass('hidden');

        try {
            const response = await fetch(`api/search_rides.php?query=${encodeURIComponent(query)}&time=${encodeURIComponent(time)}`);
            const result = await response.json();
            if (result.success) {
                if (result.count > 0) {
                    container.html(result.html);
                } else {
                    container.html('<div class="py-20 text-center text-gray-400 italic">Nenhum resultado encontrado...</div>');
                }
            }
        } catch (e) { console.error(e); }
    }

    function showSkeleton(container) {
        let html = '';
        for (let i = 0; i < 3; i++) html += '<div class="h-48 bg-gray-100 rounded-[2.5rem] animate-pulse mb-4"></div>';
        container.html(html);
    }

    async function checkActiveRide() {
        try {
            const r = await fetch('api/get_active_ride.php');
            const res = await r.json();
            if (res.success && res.data) {
                const ride = res.data;
                const avatar = ride.driver_avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(ride.driver_name)}&background=random`;
                const price = parseFloat(ride.price).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                const isPaid = (ride.payment_status === 'paid');

                $('#active-ride-container').html(`
                    <div class="bg-primary rounded-[2.5rem] p-6 text-white shadow-2xl shadow-primary/30 relative overflow-hidden mb-8">
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-4">
                                <span class="text-[10px] font-bold uppercase bg-white/20 px-3 py-1 rounded-full">Próxima Viagem</span>
                                ${isPaid ? '<span class="text-[10px] font-bold uppercase bg-green-400 px-3 py-1 rounded-full">PAGO ✅</span>' : ''}
                            </div>
                            <h3 class="text-2xl font-black mb-1">Para ${ride.destination_text}</h3>
                            <p class="text-blue-50 text-sm mb-6">Com ${ride.driver_name}</p>
                            
                            ${isPaid ? `
                                <div class="bg-white/20 p-4 rounded-2xl flex items-center justify-center gap-3 font-bold">
                                    <i class="bi bi-check-circle"></i> Contribuição Confirmada
                                </div>
                            ` : `
                                <button onclick="copyToClipboard('${ride.driver_pix}')" class="w-full bg-white text-primary py-4 rounded-2xl font-black text-lg shadow-xl active:scale-95 transition-all text-center">
                                    Pagar R$ ${price}
                                </button>
                            `}
                        </div>
                    </div>
                `);
            }
        } catch (e) { }
    }

    async function reservarCarona(id, price, origin, dest) {
        const result = await Swal.fire({
            title: 'Reservar Carona?',
            html: `De <b>${origin}</b> para <b>${dest}</b>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            customClass: { confirmButton: 'bg-gray-900 text-white px-8 py-3 rounded-2xl font-bold', cancelButton: 'bg-gray-100 text-gray-500 px-8 py-3 rounded-2xl font-bold ml-2' },
            buttonsStyling: false
        });

        if (result.isConfirmed) {
            $.ajax({
                url: 'api/book_ride.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ ride_id: id }),
                success: function (res) {
                    if (res.success) {
                        Swal.fire({ title: 'Sucesso!', text: res.message, icon: 'success' }).then(() => location.reload());
                    } else {
                        Swal.fire({ text: res.message, icon: 'error' });
                    }
                }
            });
        }
    }

    async function copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        Swal.fire({ text: 'Pix copiado!', icon: 'success', toast: true, position: 'top', timer: 1500, showConfirmButton: false });
    }

    document.addEventListener('DOMContentLoaded', () => {
        checkActiveRide();
    });
</script>