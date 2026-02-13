<?php
/**
 * View: Minhas Viagens (Reservas do Passageiro)
 */

require_once 'config/db.php';

$currentUserId = $_SESSION['user_id'] ?? 0;

if (!$currentUserId) {
    header("Location: index.php?page=home");
    exit;
}

try {
    // Buscar todas as reservas do passageiro
    $stmt = $pdo->prepare("
        SELECT 
            b.id as booking_id,
            b.status as booking_status,
            b.meeting_point,
            r.id as ride_id,
            r.origin_text,
            r.destination_text,
            r.departure_time,
            r.price,
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
?>

<div class="max-w-xl mx-auto pt-6 px-4 pb-20">
    <div class="flex items-center justify-between mb-8 px-2">
        <h1 class="text-2xl font-bold text-gray-900 leading-tight">Minhas Viagens</h1>
        <span class="badge badge-light-primary rounded-lg font-bold">Passageiro</span>
    </div>

    <?php if (empty($bookings)): ?>
        <div
            class="flex flex-col items-center justify-center py-12 text-center bg-white rounded-3xl border border-dashed border-gray-200">
            <div class="bg-gray-50 rounded-full p-6 mb-4">
                <i class="bi bi-ticket-perforated text-5xl text-gray-300"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">Você ainda não tem reservas.</h3>
            <p class="text-gray-400 text-sm mb-8 max-w-xs mx-auto">Encontre sua carona ideal e comece a viajar com a
                comunidade.</p>
            <a href="index.php?page=home"
                class="btn btn-primary rounded-2xl px-8 py-3 font-bold shadow-lg shadow-primary/20">
                Buscar Carona
            </a>
        </div>
    <?php else: ?>

        <div class="space-y-4">
            <?php foreach ($bookings as $b):
                $time = date('H:i', strtotime($b['departure_time']));
                $date = date('d/m/Y', strtotime($b['departure_time']));
                $avatar = $b['driver_avatar'] ?: "https://ui-avatars.com/api/?name=" . urlencode($b['driver_name']) . "&background=random";
                $isConfirmed = ($b['booking_status'] === 'confirmed');
                ?>
                <div class="card shadow-sm border border-gray-100 rounded-2xl overflow-hidden bg-white">
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">
                                    <?= $date ?> •
                                    <?= $time ?>
                                </span>
                                <h3 class="text-gray-900 font-bold text-lg mb-1 leading-tight">Para
                                    <?= htmlspecialchars($b['destination_text']) ?>
                                </h3>
                            </div>
                            <?php if ($isConfirmed): ?>
                                <span
                                    class="badge badge-light-success font-bold px-3 py-2 rounded-lg border border-success/10 lowercase flex items-center gap-1">
                                    <i class="bi bi-check-circle-fill"></i> confirmada
                                </span>
                            <?php else: ?>
                                <span
                                    class="badge badge-light-secondary font-bold px-3 py-2 rounded-lg border border-gray-100 lowercase">
                                    <?= $b['booking_status'] ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-3 bg-gray-50 rounded-2xl p-3 border border-gray-100 mb-4">
                            <div class="symbol symbol-40px symbol-circle border-2 border-white">
                                <img src="<?= htmlspecialchars($avatar) ?>" alt="Motorista">
                            </div>
                            <div class="flex flex-col flex-1">
                                <span class="text-gray-900 font-bold text-sm">
                                    <?= htmlspecialchars($b['driver_name']) ?>
                                </span>
                                <span class="text-[10px] text-gray-500 italic">
                                    <?= htmlspecialchars($b['car_model'] ?: 'Carro') ?> •
                                    <?= htmlspecialchars($b['car_plate'] ?: 'Placa não informada') ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-primary font-black text-sm">R$
                                    <?= number_format($b['price'], 2, ',', '.') ?>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mb-5 px-1">
                            <i class="bi bi-geo-alt-fill text-primary text-xs"></i>
                            <span class="text-xs text-gray-500">Embarque em: <b class="text-gray-700 font-bold">
                                    <?= htmlspecialchars($b['meeting_point'] ?: 'Origem') ?>
                                </b></span>
                        </div>

                        <?php if ($isConfirmed): ?>
                            <div class="flex gap-2">
                                <button onclick="mostrarPixParaMotorista('<?= addslashes($b['driver_pix'] ?: '') ?>')"
                                    class="btn btn-outline btn-outline-primary flex-1 rounded-xl font-bold py-3 flex items-center justify-center gap-2">
                                    <i class="bi bi-qr-code"></i> Ver Pix
                                </button>
                                <a href="https://wa.me/<?= preg_replace('/\D/', '', $b['driver_phone']) ?>" target="_blank"
                                    class="btn btn-success flex-1 rounded-xl font-bold py-3 flex items-center justify-center gap-2 shadow-lg shadow-success/20">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function mostrarPixParaMotorista(pix) {
        if (!pix) {
            Swal.fire({ text: 'O motorista ainda não cadastrou o Pix no sistema.', icon: 'info' });
            return;
        }
        Swal.fire({
            title: 'Pagar Motorista',
            html: `
            <div class="py-4">
                <div class="bg-blue-50 p-6 rounded-3xl mb-6 border-2 border-dashed border-blue-200">
                    <span class="text-xl font-bold text-primary break-all tracking-tight select-all">${pix}</span>
                </div>
                <button onclick="navigator.clipboard.writeText('${pix}'); Swal.fire({text:'Copiado!', timer:1000, showConfirmButton:false, toast:true, position:'top'})" class="btn btn-primary w-full rounded-2xl py-4 font-bold shadow-lg shadow-primary/20">
                    <i class="bi bi-copy mr-2"></i> Copiar Chave
                </button>
            </div>
        `,
            showConfirmButton: false,
            showCloseButton: true,
            customClass: { popup: 'rounded-[2.5rem]' }
        });
    }
</script>