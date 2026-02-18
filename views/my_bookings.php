<?php
/**
 * View: Minhas Viagens (Dashboard do Passageiro - Bilhete Digital)
 */

require_once 'config/db.php';

$currentUserId = $_SESSION['user_id'] ?? 0;

if (!$currentUserId) {
    header("Location: index.php?page=home");
    exit;
}

try {
    // Buscar todas as reservas do passageiro com dados completos
    $stmt = $pdo->prepare("
        SELECT 
            b.id as booking_id,
            b.status as booking_status,
            b.meeting_point,
            b.payment_status,
            r.id as ride_id,
            r.origin_text,
            r.destination_text,
            r.waypoints,
            r.departure_time,
            r.price,
            r.status as ride_status,
            u.name as driver_name,
            u.photo_url as driver_avatar,
            u.phone as driver_phone,
            u.pix_key as driver_pix,
            c.model as car_model,
            c.plate as car_plate
        FROM bookings b
        JOIN rides r ON b.ride_id = r.id
        JOIN users u ON r.driver_id = u.id
        LEFT JOIN cars c ON c.user_id = u.id
        WHERE b.passenger_id = ?
        ORDER BY r.departure_time DESC
    ");
    $stmt->execute([$currentUserId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro ao carregar reservas: " . $e->getMessage() . "</div>";
    $bookings = [];
}

// Separar: próxima viagem ativa vs histórico
$nextBooking = null;
$historyBookings = [];

foreach ($bookings as $b) {
    $isFuture = strtotime($b['departure_time']) >= time();
    $isActive = ($b['booking_status'] === 'confirmed' && $isFuture && $b['ride_status'] !== 'canceled');

    if (!$nextBooking && $isActive) {
        $nextBooking = $b;
    } else {
        $historyBookings[] = $b;
    }
}
?>

<div class="max-w-xl mx-auto pt-6 px-4 pb-20">
    <div class="flex items-center justify-between mb-8 px-1">
        <h1 class="text-2xl font-bold text-gray-900 leading-tight">Minhas Viagens</h1>
        <span
            class="text-[10px] font-bold text-primary bg-primary/10 px-3 py-1.5 rounded-full uppercase tracking-widest">Passageiro</span>
    </div>

    <?php if (empty($bookings)): ?>
        <!-- Empty State -->
        <div
            class="flex flex-col items-center justify-center py-16 text-center bg-white rounded-[2.5rem] border border-dashed border-gray-200 shadow-sm">
            <div class="bg-blue-50 rounded-full p-8 mb-6">
                <i class="bi bi-ticket-perforated text-5xl text-primary/40"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">Você ainda não tem reservas.</h3>
            <p class="text-gray-400 text-sm mb-8 max-w-xs mx-auto">Encontre sua carona ideal e comece a viajar com a
                comunidade.</p>
            <a href="index.php?page=home"
                class="bg-primary text-white px-8 py-3.5 rounded-2xl font-bold shadow-lg shadow-primary/20 hover:shadow-primary/40 transition-shadow">
                <i class="bi bi-search mr-2"></i> Buscar Carona
            </a>
        </div>

    <?php else: ?>

        <!-- ==================== -->
        <!-- SEÇÃO 1: HERO CARD   -->
        <!-- ==================== -->
        <?php if ($nextBooking):
            $nb = $nextBooking;
            $nbTime = date('H:i', strtotime($nb['departure_time']));
            $nbDate = date('d/m', strtotime($nb['departure_time']));
            $nbDayName = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][date('w', strtotime($nb['departure_time']))];
            $nbAvatar = $nb['driver_avatar'] ?: "https://ui-avatars.com/api/?name=" . urlencode($nb['driver_name']) . "&background=0D8FFD&color=fff&bold=true";
            $nbPhone = preg_replace('/\D/', '', $nb['driver_phone']);
            $isPaid = ($nb['payment_status'] === 'paid');
            ?>
            <div class="mb-8">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1">Seu Bilhete 🎫</h2>

                <!-- Bilhete Azul -->
                <div
                    class="bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-500 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-blue-900/30 relative">
                    <!-- Decorações de fundo -->
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                    <div class="absolute -left-8 bottom-10 w-32 h-32 bg-white/5 rounded-full blur-xl"></div>

                    <div class="relative z-10 p-6">

                        <!-- Topo: Motorista + Hora -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <img src="<?= htmlspecialchars($nbAvatar) ?>" alt="Motorista"
                                    class="w-14 h-14 rounded-full border-[3px] border-white/40 shadow-lg object-cover">
                                <div class="flex flex-col">
                                    <span
                                        class="text-white font-bold text-base leading-tight"><?= htmlspecialchars($nb['driver_name']) ?></span>
                                    <span class="text-blue-100 text-xs font-medium">
                                        <?= htmlspecialchars($nb['car_model'] ?: 'Carro') ?> • <span
                                            class="font-bold"><?= htmlspecialchars($nb['car_plate'] ?: '---') ?></span>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span
                                    class="block text-blue-200 text-[10px] font-bold uppercase tracking-wider"><?= $nbDayName ?>
                                    <?= $nbDate ?></span>
                                <span class="text-4xl font-black text-white tracking-tighter"><?= $nbTime ?></span>
                            </div>
                        </div>

                        <!-- Meio: Timeline Visual -->
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 mb-5 border border-white/10">
                            <div class="relative pl-6">
                                <div class="absolute left-2 top-4 bottom-6 w-0.5 border-l-2 border-dashed border-white/30">
                                </div>

                                <!-- Ponto de Embarque -->
                                <div class="flex items-start gap-3 relative mb-5">
                                    <div
                                        class="absolute -left-[14px] top-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white/50 shadow-lg shadow-green-400/40 z-10">
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-bold text-green-300 uppercase tracking-widest">Seu
                                            Embarque</span>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($nb['meeting_point'] ?: $nb['origin_text']) ?>"
                                            target="_blank"
                                            class="text-white font-bold text-sm hover:text-green-300 transition-colors underline underline-offset-4 decoration-white/20">
                                            <?= htmlspecialchars($nb['meeting_point'] ?: $nb['origin_text']) ?>
                                        </a>
                                    </div>
                                </div>

                                <?php
                                // Waypoints intermediários
                                $waypoints = json_decode($nb['waypoints'] ?? '[]', true);
                                if (!empty($waypoints)):
                                    foreach ($waypoints as $wp): ?>
                                        <div class="flex items-start gap-4 relative mb-4">
                                            <div class="absolute -left-[12px] top-1.5 w-2.5 h-2.5 bg-white/30 rounded-full z-10"></div>
                                            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($wp) ?>"
                                                target="_blank"
                                                class="text-blue-100 font-medium text-xs hover:text-white transition-colors">
                                                <?= htmlspecialchars($wp) ?>
                                            </a>
                                        </div>
                                    <?php endforeach;
                                endif; ?>

                                <!-- Destino Final -->
                                <div class="flex items-start gap-3 relative">
                                    <div
                                        class="absolute -left-[14px] top-1 w-4 h-4 bg-white rounded-full border-2 border-blue-300 shadow-lg z-10">
                                    </div>
                                    <div>
                                        <span
                                            class="block text-[10px] font-bold text-blue-200 uppercase tracking-widest">Destino
                                            Final</span>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($nb['destination_text']) ?>"
                                            target="_blank"
                                            class="text-white font-extrabold text-sm hover:text-blue-200 transition-colors underline underline-offset-4 decoration-white/20">
                                            <?= htmlspecialchars($nb['destination_text']) ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status de Pagamento -->
                        <?php if ($isPaid): ?>
                            <div
                                class="flex items-center justify-center gap-2 bg-green-400/20 border border-green-300/20 rounded-2xl py-3 mb-5">
                                <i class="bi bi-patch-check-fill text-green-300 text-2xl"></i>
                                <span class="text-green-100 font-black text-sm uppercase tracking-widest">Pagamento
                                    Confirmado</span>
                            </div>
                        <?php else: ?>
                            <button onclick="verPixMotorista('<?= addslashes($nb['driver_pix'] ?: '') ?>')"
                                class="w-full bg-white text-blue-600 font-black py-4 rounded-2xl text-sm shadow-lg hover:shadow-xl transition-shadow flex items-center justify-center gap-2 mb-5">
                                <i class="bi bi-qr-code text-lg"></i> Ver Chave Pix do Motorista
                            </button>
                        <?php endif; ?>

                        <!-- Rodapé: Ações -->
                        <div class="flex items-center justify-between">
                            <div class="flex gap-3">
                                <a href="https://wa.me/<?= $nbPhone ?>" target="_blank"
                                    class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center shadow-lg shadow-green-500/30 hover:scale-110 transition-transform">
                                    <i class="bi bi-whatsapp text-xl"></i>
                                </a>
                                <?php
                                $locMsg = urlencode("Olá! Estou compartilhando minha localização atual para facilitar o encontro. 👇");
                                ?>
                                <a href="https://wa.me/<?= $nbPhone ?>?text=<?= $locMsg ?>" target="_blank"
                                    class="w-12 h-12 rounded-full bg-blue-400 text-white flex items-center justify-center hover:bg-blue-500 transition-colors shadow-lg shadow-blue-400/30"
                                    title="Pedir/Enviar Localização">
                                    <i class="bi bi-geo-alt text-xl"></i>
                                </a>
                                <?php
                                $shareTxt = urlencode("Estou indo de {$nb['origin_text']} para {$nb['destination_text']} com {$nb['driver_name']}. Placa: {$nb['car_plate']}. Carro: {$nb['car_model']}. Acompanhe se eu chegar bem!");
                                ?>
                                <a href="https://wa.me/?text=<?= $shareTxt ?>" target="_blank"
                                    class="w-12 h-12 rounded-full bg-white/20 text-white flex items-center justify-center hover:bg-white/30 transition-colors backdrop-blur-md"
                                    title="🛡️ Enviar dados para Mãe/Amigo">
                                    <i class="bi bi-shield-check text-xl"></i>
                                </a>
                                <button
                                    onclick='copiarOferta(<?= $nb['ride_id'] ?>, "<?= addslashes($nb['origin_text']) ?>", "<?= addslashes($nb['destination_text']) ?>", "<?= $nbTime ?>", "<?= addslashes(implode(" > ", $waypoints)) ?>", "<?= number_format($nb['price'], 2, ',', '.') ?>")'
                                    class="w-12 h-12 rounded-full bg-cyan-400 text-white flex items-center justify-center shadow-lg shadow-cyan-400/30 hover:scale-110 transition-transform"
                                    title="Copiar Oferta">
                                    <i class="bi bi-clipboard text-xl"></i>
                                </button>
                            </div>

                            <button onclick="cancelarReserva(<?= $nb['booking_id'] ?>)"
                                class="text-xs font-bold text-white/50 hover:text-red-300 transition-colors flex items-center gap-1">
                                <i class="bi bi-x-circle text-sm"></i> Cancelar Reserva
                            </button>
                        </div>

                        <!-- Valor -->
                        <div class="mt-5 pt-4 border-t border-white/10 flex items-center justify-between">
                            <span class="text-blue-200 text-xs font-bold uppercase tracking-wider">Contribuição</span>
                            <span class="text-white font-black text-xl">R$
                                <?= number_format($nb['price'], 2, ',', '.') ?></span>
                        </div>

                    </div>
                </div>
            </div>
        <?php endif; ?>


        <!-- ======================== -->
        <!-- SEÇÃO 2: HISTÓRICO       -->
        <!-- ======================== -->
        <?php if (!empty($historyBookings)): ?>
            <div>
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1">Histórico</h2>
                <div class="space-y-3">
                    <?php foreach ($historyBookings as $b):
                        $time = date('H:i', strtotime($b['departure_time']));
                        $date = date('d/m', strtotime($b['departure_time']));
                        $avatar = $b['driver_avatar'] ?: "https://ui-avatars.com/api/?name=" . urlencode($b['driver_name']) . "&background=random";
                        $isCanceled = ($b['booking_status'] === 'canceled' || $b['ride_status'] === 'canceled');
                        $isFuture = strtotime($b['departure_time']) >= time();
                        $isActive = ($b['booking_status'] === 'confirmed' && $isFuture && $b['ride_status'] !== 'canceled');
                        $isPaid = ($b['payment_status'] === 'paid');
                        ?>
                        <div
                            class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-4 <?= $isCanceled ? 'opacity-40' : ($isFuture ? '' : 'opacity-70') ?>">
                            <img src="<?= htmlspecialchars($avatar) ?>" alt="D"
                                class="w-10 h-10 rounded-full border border-gray-100 object-cover shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($b['destination_text']) ?>"
                                        target="_blank"
                                        class="truncate hover:text-primary transition-colors"><?= htmlspecialchars($b['destination_text']) ?></a>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <span><?= $date ?> às <?= $time ?></span>
                                    <span>•</span>
                                    <span>R$ <?= number_format($b['price'], 2, ',', '.') ?></span>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <?php if ($isCanceled): ?>
                                    <span class="text-[10px] font-bold text-red-400 bg-red-50 px-2.5 py-1 rounded-full">Cancelada</span>
                                <?php elseif ($isActive): ?>
                                    <div class="flex gap-1.5">
                                        <a href="https://wa.me/<?= preg_replace('/\D/', '', $b['driver_phone']) ?>" target="_blank"
                                            class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                        <button onclick="cancelarReserva(<?= $b['booking_id'] ?>)"
                                            class="w-8 h-8 rounded-full bg-red-50 text-red-400 flex items-center justify-center text-sm hover:bg-red-100 transition-colors">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                <?php elseif ($isPaid): ?>
                                    <span class="text-[10px] font-bold text-green-500 bg-green-50 px-2.5 py-1 rounded-full">Pago
                                        ✓</span>
                                <?php else: ?>
                                    <span
                                        class="text-[10px] font-bold text-gray-400 bg-gray-50 px-2.5 py-1 rounded-full">Concluída</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
    function getRideText(origem, destino, hora, rota, valor, link) {
        return `🚗 *Vaga Disponível!*\n\n📍 *De:* ${origem}\n🏁 *Para:* ${destino}\n⏰ *Saída:* ${hora}\n🛣️ *Rota:* ${rota}\n💰 *Valor:* R$ ${valor}\n\n👉 *Garanta sua vaga:* ${link}`;
    }

    async function copiarOferta(origem, destino, hora, rota, valor, rideId) {
        const link = `${window.location.origin}${window.location.pathname}?ride_id=${rideId}`;
        const texto = getRideText(origem, destino, hora, rota, valor, link);
        try {
            await navigator.clipboard.writeText(texto);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Texto copiado com o link!',
                showConfirmButton: false,
                timer: 2000
            });
        } catch (err) {
            Swal.fire('Erro', 'Não foi possível copiar.', 'error');
        }
    }

    function verPixMotorista(pix) {
        if (!pix) {
            Swal.fire({ text: 'O motorista ainda não cadastrou o Pix no sistema.', icon: 'info' });
            return;
        }
        Swal.fire({
            title: 'Pagar Motorista',
            html: `
            <div class="py-4">
                <p class="text-gray-500 mb-4 font-medium text-sm">Copie a chave e faça o Pix pelo seu banco:</p>
                <div class="bg-blue-50 p-6 rounded-3xl mb-6 border-2 border-dashed border-blue-200">
                    <span class="text-xl font-bold text-primary break-all tracking-tight select-all">${pix}</span>
                </div>
                <button onclick="navigator.clipboard.writeText('${pix}'); Swal.fire({text:'Copiado!', timer:1000, showConfirmButton:false, toast:true, position:'top'})" class="bg-primary text-white w-full rounded-2xl py-4 font-bold shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                    <i class="bi bi-copy"></i> Copiar Chave
                </button>
            </div>
        `,
            showConfirmButton: false,
            showCloseButton: true,
            customClass: { popup: 'rounded-[2.5rem]' }
        });
    }

    function cancelarReserva(bookingId) {
        Swal.fire({
            title: 'Cancelar Reserva?',
            html: '<p class="text-gray-500 text-sm">Isso vai <b>liberar sua vaga</b> para outra pessoa imediatamente.<br>Você não poderá desfazer essa ação.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, cancelar',
            cancelButtonText: 'Manter reserva',
            customClass: {
                confirmButton: 'bg-red-500 text-white font-bold px-6 py-3 rounded-2xl shadow-lg hover:bg-red-600 transition-all',
                cancelButton: 'bg-gray-100 text-gray-500 font-bold px-6 py-3 rounded-2xl ml-2 hover:bg-gray-200'
            },
            buttonsStyling: false
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch('api/passenger_actions.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'cancel_booking', bookingId: bookingId })
                    });
                    const data = await res.json();
                    if (data.success) {
                        Swal.fire({
                            text: data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({ text: data.message, icon: 'error' });
                    }
                } catch (err) {
                    Swal.fire({ text: 'Erro na conexão.', icon: 'error' });
                }
            }
        });
    }
</script>