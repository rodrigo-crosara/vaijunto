<?php
/**
 * View: Painel do Motorista (Minhas Caronas - Gestão Total)
 */

require_once 'config/db.php';

$currentUserId = $_SESSION['user_id'] ?? 0;

if (!$currentUserId) {
    header("Location: index.php?page=home");
    exit;
}

try {
    // 0. Buscar dados do motorista (chave pix)
    $stmtDriver = $pdo->prepare("SELECT pix_key FROM users WHERE id = ?");
    $stmtDriver->execute([$currentUserId]);
    $driverPix = $stmtDriver->fetchColumn();

    // 1. Buscar caronas criadas pelo motorista
    $stmtRides = $pdo->prepare("
        SELECT id, origin_text, destination_text, departure_time, seats_total, seats_available, price, status
        FROM rides
        WHERE driver_id = ?
        ORDER BY departure_time DESC
    ");
    $stmtRides->execute([$currentUserId]);
    $myRides = $stmtRides->fetchAll(PDO::FETCH_ASSOC);

    // 2. Para cada carona, buscar os passageiros
    foreach ($myRides as &$ride) {
        $stmtPassengers = $pdo->prepare("
            SELECT b.id as booking_id, u.name, u.photo_url, u.phone, b.meeting_point, b.status as booking_status, b.payment_status
            FROM bookings b
            JOIN users u ON b.passenger_id = u.id
            WHERE b.ride_id = ? AND b.status != 'rejected'
        ");
        $stmtPassengers->execute([$ride['id']]);
        $ride['passengers'] = $stmtPassengers->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro ao carregar dados: " . $e->getMessage() . "</div>";
    $myRides = [];
}
?>

<div class="max-w-xl mx-auto pt-6 px-4 pb-20">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-900 leading-tight">Painel do Motorista</h1>
        <span class="badge badge-light-primary rounded-lg font-bold">Driver Mode</span>
    </div>

    <!-- Empty State -->
    <?php if (empty($myRides)): ?>
        <div
            class="flex flex-col items-center justify-center py-12 text-center bg-white rounded-3xl border border-dashed border-gray-200 shadow-sm">
            <div class="bg-blue-50 rounded-full p-6 mb-4">
                <i class="bi bi-calendar2-plus text-5xl text-primary opacity-50"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">Você ainda não ofereceu nenhuma carona.</h3>
            <a href="index.php?page=offer"
                class="btn btn-primary rounded-2xl px-8 py-3 font-bold shadow-lg shadow-primary/20">
                <i class="bi bi-plus-lg mr-2"></i> Criar Carona
            </a>
        </div>
    <?php else: ?>

        <div class="space-y-6">
            <?php foreach ($myRides as $ride):
                $time = date('H:i', strtotime($ride['departure_time']));
                $date = date('d/m/Y', strtotime($ride['departure_time']));
                $isCanceled = ($ride['status'] === 'canceled');
                ?>
                <div
                    class="card shadow-sm border border-gray-100 rounded-3xl overflow-hidden bg-white <?= $isCanceled ? 'opacity-60 bg-gray-50' : '' ?>">
                    <div class="card-header bg-gray-50/50 p-5 flex justify-between items-center border-b border-gray-100">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-0.5">
                                <?= $date ?> às <?= $time ?>
                                <?php if ($isCanceled): ?>
                                    <span class="badge badge-light-danger ml-2 uppercase">CANCELADA</span>
                                <?php endif; ?>
                            </span>
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-gray-900 font-bold text-sm truncate max-w-[120px]"><?= $ride['origin_text'] ?></span>
                                <i class="bi bi-arrow-right text-gray-400 text-xs"></i>
                                <span
                                    class="text-gray-900 font-bold text-sm truncate max-w-[120px]"><?= $ride['destination_text'] ?></span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-[10px] font-bold text-gray-400 mb-1 uppercase">Vagas</span>
                            <div class="flex items-center gap-1">
                                <span class="text-primary font-bold"><?= $ride['seats_available'] ?></span>
                                <span class="text-gray-300">/</span>
                                <span class="text-gray-400 font-medium"><?= $ride['seats_total'] ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de Ações -->
                    <?php if (!$isCanceled): ?>
                        <div class="px-5 py-3 bg-white border-b border-gray-100 flex gap-2 overflow-x-auto">
                            <button onclick="editarVagas(<?= $ride['id'] ?>, <?= $ride['seats_available'] ?>)"
                                class="btn btn-sm btn-light-primary rounded-xl font-bold flex items-center gap-2 whitespace-nowrap">
                                <i class="bi bi-pencil-square"></i> Editar Vagas
                            </button>
                            <button onclick="confirmarCancelamento(<?= $ride['id'] ?>)"
                                class="btn btn-sm btn-light-danger rounded-xl font-bold flex items-center gap-2 whitespace-nowrap">
                                <i class="bi bi-x-circle"></i> Cancelar Viagem
                            </button>
                            <button onclick="confirmarVolta(<?= $ride['id'] ?>, '<?= addslashes($ride['origin_text']) ?>', '<?= addslashes($ride['destination_text']) ?>', '<?= $ride['departure_time'] ?>')"
                                class="btn btn-sm btn-light-info rounded-xl font-bold flex items-center gap-2 whitespace-nowrap">
                                <i class="bi bi-arrow-left-right"></i> Criar Volta
                            </button>
                            <button
                                class="btn btn-sm btn-light rounded-xl font-bold flex items-center gap-2 whitespace-nowrap ml-auto"
                                type="button" data-bs-toggle="collapse" data-bs-target="#ride-<?= $ride['id'] ?>">
                                <i class="bi bi-people"></i> Passageiros (<?= count($ride['passengers']) ?>)
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Lista de Passageiros (Collapse) -->
                    <div class="collapse <?= count($ride['passengers']) > 0 ? 'show' : '' ?>" id="ride-<?= $ride['id'] ?>">
                        <div class="card-body p-5 pt-0">
                            <?php if (empty($ride['passengers'])): ?>
                                <div class="bg-gray-50 rounded-2xl p-4 text-center border border-dashed border-gray-200 mt-4">
                                    <p class="text-gray-500 text-xs italic">Nenhum passageiro confirmado.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3 mt-4">
                                    <?php foreach ($ride['passengers'] as $passenger):
                                        $pAvatar = $passenger['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($passenger['name']) . "&background=random";
                                        $pPhone = preg_replace('/\D/', '', $passenger['phone']);
                                        ?>
                                        <div
                                            class="flex items-center justify-between bg-white border border-gray-100 rounded-2xl p-3 shadow-sm">
                                            <div class="flex items-center gap-3">
                                                <div class="symbol symbol-45px symbol-circle border-2 border-white shadow-sm">
                                                    <img src="<?= htmlspecialchars($pAvatar) ?>" alt="Passageiro">
                                                </div>
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-gray-900 font-bold text-sm mb-0.5"><?= htmlspecialchars($passenger['name']) ?></span>
                                                    <span
                                                        class="text-[10px] text-primary-active font-bold uppercase bg-primary/5 px-2 py-0.5 rounded-md">
                                                        <i class="bi bi-geo-alt-fill text-[9px]"></i>
                                                        <?= htmlspecialchars($passenger['meeting_point'] ?: 'Origem') ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex gap-1">
                                                <?php
                                                $isPaid = ($passenger['payment_status'] === 'paid');
                                                ?>
                                                <button onclick="confirmarPagamento(<?= $passenger['booking_id'] ?>)" 
                                                    class="btn btn-icon btn-sm <?= $isPaid ? 'btn-success' : 'btn-light-secondary' ?> rounded-lg"
                                                    title="<?= $isPaid ? 'Pago' : 'Marcar como Pago' ?>"
                                                    <?= $isPaid ? 'disabled' : '' ?>>
                                                    <i class="bi bi-check-circle<?= $isPaid ? '-fill' : '' ?>"></i>
                                                </button>
                                                <button onclick="mostrarPix('<?= addslashes($driverPix ?: '') ?>')"
                                                    class="btn btn-icon btn-sm btn-light-primary rounded-lg"
                                                    title="Cobrar (Mostrar Pix)">
                                                    <i class="bi bi-qr-code"></i>
                                                </button>
                                                <a href="https://wa.me/<?= $pPhone ?>" target="_blank"
                                                    class="btn btn-icon btn-sm btn-success rounded-lg">
                                                    <i class="bi bi-whatsapp"></i>
                                                </a>
                                                <?php if (!$isCanceled): ?>
                                                    <button onclick="removerPassageiro(<?= $passenger['booking_id'] ?>)"
                                                        class="btn btn-icon btn-sm btn-light-danger rounded-lg" title="Remover Passageiro">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<script>
    async function driverAction(data) {
        try {
            const response = await fetch('api/driver_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                Swal.fire({ text: result.message, icon: 'success', timer: 1500, showConfirmButton: false }).then(() => location.reload());
            } else {
                Swal.fire({ text: result.message, icon: 'error' });
            }
        } catch (err) {
            Swal.fire({ text: 'Erro na conexão.', icon: 'error' });
        }
    }

    function editarVagas(rideId, currentSeats) {
        Swal.fire({
            title: 'Editar Vagas Disponíveis',
            input: 'number',
            inputValue: currentSeats,
            inputLabel: 'Nova quantidade de vagas',
            showCancelButton: true,
            confirmButtonText: 'Salvar',
            customClass: { confirmButton: 'btn btn-primary px-6', cancelButton: 'btn btn-light ml-2' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                driverAction({ action: 'update_seats', rideId: rideId, newSeats: result.value });
            }
        });
    }

    function confirmarCancelamento(rideId) {
        Swal.fire({
            title: 'Cancelar Viagem?',
            text: 'Isso removerá a carona do feed público.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, cancelar',
            cancelButtonText: 'Voltar',
            confirmButtonColor: '#f1416c'
        }).then((result) => {
            if (result.isConfirmed) {
                driverAction({ action: 'cancel_ride', rideId: rideId });
            }
        });
    }

    function removerPassageiro(bookingId) {
        Swal.fire({
            title: 'Remover Passageiro?',
            text: 'A vaga será liberada no feed.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, remover',
            cancelButtonText: 'Não',
            customClass: { confirmButton: 'btn btn-danger px-6', cancelButton: 'btn btn-light ml-2' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                driverAction({ action: 'remove_passenger', bookingId: bookingId });
            }
        });
    }

    function mostrarPix(pix) {
        if (!pix) {
            Swal.fire({ text: 'Você ainda não cadastrou sua chave Pix no perfil.', icon: 'info' });
            return;
        }
        Swal.fire({
            title: 'Sua Chave Pix',
            html: `
                <div class="py-4">
                    <p class="text-gray-500 mb-4 font-medium">Peça para o passageiro escanear ou copiar:</p>
                    <div class="bg-gray-100 p-6 rounded-3xl mb-6 border-2 border-dashed border-gray-200">
                        <span class="text-xl font-bold text-gray-800 break-all tracking-tight select-all">${pix}</span>
                    </div>
                    <button onclick="navigator.clipboard.writeText('${pix}'); Swal.fire({text:'Copiado!', timer:1000, showConfirmButton:false, toast:true, position:'top'})" class="btn btn-primary w-full rounded-2xl py-4 font-bold shadow-lg shadow-primary/20">
                        <i class="bi bi-copy mr-2"></i> Copiar Chave
                    </button>
                    <p class="mt-4 text-[10px] text-gray-400 uppercase font-bold tracking-widest">VaiJunto • Segurança & Rapidez</p>
                </div>
            `,
            showConfirmButton: false,
            showCloseButton: true,
            customClass: { popup: 'rounded-[2.5rem]' }
        });
    }

    function confirmarPagamento(bookingId) {
        Swal.fire({
            title: 'Confirmar Recebimento?',
            text: 'Marcar que o passageiro já pagou a contribuição.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, recebido',
            cancelButtonText: 'Cancelar',
            customClass: { confirmButton: 'btn btn-success px-6', cancelButton: 'btn btn-light ml-2' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                driverAction({ action: 'confirm_payment', bookingId: bookingId });
            }
        });
    }

    function confirmarVolta(rideId, origin, destination, time) {
        // Calcular 9 horas depois
        const date = new Date(time);
        date.setHours(date.getHours() + 9);
        const suggestedTime = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        const suggestedDate = date.toLocaleDateString('pt-BR');

        Swal.fire({
            title: 'Criar Volta?',
            html: `Deseja criar a volta de <b>${destination}</b> para <b>${origin}</b>?<br><br>Sugestão: <b>${suggestedDate} às ${suggestedTime}</b>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, criar agora',
            cancelButtonText: 'Cancelar',
            customClass: { confirmButton: 'btn btn-primary px-6', cancelButton: 'btn btn-light ml-2' },
            buttonsStyling: false
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('api/create_return_ride.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ originalRideId: rideId })
                    });
                    const res = await response.json();
                    if (res.success) {
                        Swal.fire({ text: res.message, icon: 'success', timer: 1500, showConfirmButton: false }).then(() => location.reload());
                    } else {
                        Swal.fire({ text: res.message, icon: 'error' });
                    }
                } catch (e) {
                    Swal.fire({ text: 'Erro na conexão.', icon: 'error' });
                }
            }
        });
    }
</script>