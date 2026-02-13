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

    <?php if (empty($myRides)): ?>
        <!-- Empty State (Manteve igual) -->
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

        <!-- 1. Próxima Viagem (Highlight) -->
        <?php
        $nextRide = null;
        foreach ($myRides as $ride) {
            if ($ride['status'] !== 'canceled' && strtotime($ride['departure_time']) >= time()) {
                $nextRide = $ride;
                break; // A query já ordena por time ASC, então a primeira válida é a próxima
            }
        }
        ?>

        <?php if ($nextRide):
            $time = date('H:i', strtotime($nextRide['departure_time']));
            $date = date('d/m', strtotime($nextRide['departure_time']));
            ?>
            <div class="mb-8">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1">Próxima Missão 🚀</h2>

                <div
                    class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-[2.5rem] p-6 text-white shadow-2xl shadow-blue-900/20 relative overflow-hidden">
                    <!-- Background Decoration -->
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="block text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Saída
                                    às</span>
                                <span class="text-5xl font-black tracking-tighter"><?= $time ?></span>
                                <span class="text-blue-200 font-bold ml-1"><?= $date ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Vagas</span>
                                <div class="flex items-center justify-end gap-1">
                                    <span class="text-2xl font-black"><?= $nextRide['seats_available'] ?></span>
                                    <span class="text-blue-300 text-sm">/ <?= $nextRide['seats_total'] ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mb-8">
                            <span class="font-bold text-lg truncate"><?= $nextRide['origin_text'] ?></span>
                            <i class="bi bi-arrow-right text-blue-300"></i>
                            <span class="font-bold text-lg truncate"><?= $nextRide['destination_text'] ?></span>
                        </div>

                        <!-- Ações Rápidas -->
                        <div class="grid grid-cols-2 gap-3 mb-6">
                            <button onclick='shareRide(<?= json_encode([
                                "id" => $nextRide['id'],
                                "origin" => $nextRide['origin_text'],
                                "destination" => $nextRide['destination_text'],
                                "departure_time" => $nextRide['departure_time'],
                                "price" => $nextRide['price'],
                                "waypoints" => "" // Simplificado para botão rápido
                            ]) ?>)'
                                class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all">
                                <i class="bi bi-whatsapp"></i> Divulgar
                            </button>
                            <button onclick="editarVagas(<?= $nextRide['id'] ?>, <?= $nextRide['seats_available'] ?>)"
                                class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all">
                                <i class="bi bi-pencil-square"></i> Editar
                            </button>
                        </div>

                        <!-- Checklist Express (Dentro do Card) -->
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-blue-200 uppercase">Checklist de Embarque</span>
                                <span
                                    class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded text-white"><?= count($nextRide['passengers']) ?>
                                    Confirmados</span>
                            </div>

                            <?php if (empty($nextRide['passengers'])): ?>
                                <div class="text-center py-2">
                                    <p class="text-blue-200 text-xs italic">Aguardando passageiros...</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($nextRide['passengers'] as $p):
                                        $isPaid = ($p['payment_status'] === 'paid');
                                        ?>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <img src="<?= $p['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($p['name']) ?>"
                                                    class="w-8 h-8 rounded-full border border-white/30">
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-sm font-bold leading-tight"><?= explode(' ', $p['name'])[0] ?></span>
                                                    <span class="text-[10px] text-blue-200 truncate max-w-[100px]"><i
                                                            class="bi bi-geo-alt-fill"></i> <?= $p['meeting_point'] ?></span>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <a href="https://wa.me/<?= preg_replace('/\D/', '', $p['phone']) ?>" target="_blank"
                                                    class="w-8 h-8 rounded-full bg-green-500/20 text-green-300 flex items-center justify-center hover:bg-green-500 hover:text-white transition-all">
                                                    <i class="bi bi-whatsapp text-sm"></i>
                                                </a>
                                                <button onclick="confirmarPagamento(<?= $p['booking_id'] ?>)"
                                                    class="w-8 h-8 rounded-full flex items-center justify-center transition-all <?= $isPaid ? 'bg-green-500 text-white' : 'bg-white/10 text-white/50 hover:bg-green-500 hover:text-white' ?>">
                                                    <i class="bi bi-currency-dollar text-sm"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2. Histórico / Outras Viagens -->
        <div>
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1">Histórico & Planejamento</h2>
            <div class="space-y-4 opacity-100">
                <?php foreach ($myRides as $ride):
                    if ($nextRide && $ride['id'] == $nextRide['id'])
                        continue; // Pula a que já exibimos
                    // Rest of the loop logic keeps the simplified card style for history
                    $time = date('H:i', strtotime($ride['departure_time']));
                    $date = date('d/m', strtotime($ride['departure_time']));
                    $isCanceled = ($ride['status'] === 'canceled');
                    ?>
                    <!-- Mini Card de Histórico -->
                    <div
                        class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center justify-between <?= $isCanceled ? 'opacity-50' : '' ?>">
                        <div class="flex items-center gap-4">
                            <div
                                class="bg-gray-50 h-10 w-10 rounded-xl flex items-center justify-center text-gray-400 font-bold text-xs flex-col">
                                <span><?= $date ?></span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
                                    <span><?= $ride['origin_text'] ?></span>
                                    <i class="bi bi-arrow-right text-gray-300 text-xs"></i>
                                    <span><?= $ride['destination_text'] ?></span>
                                </div>
                                <span class="text-xs text-gray-400"><?= count($ride['passengers']) ?> passageiros • R$
                                    <?= number_format($ride['price'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                        <?php if (!$isCanceled): ?>
                            <button class="text-gray-400 hover:text-primary" type="button" data-bs-toggle="collapse"
                                data-bs-target="#ride-history-<?= $ride['id'] ?>">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        <?php else: ?>
                            <span class="text-[10px] font-bold text-red-400 uppercase bg-red-50 px-2 py-1 rounded">Cancelada</span>
                        <?php endif; ?>
                    </div>

                    <!-- Detalhes do Histórico (Collapse) -->
                    <div class="collapse" id="ride-history-<?= $ride['id'] ?>">
                        <div class="bg-gray-50 rounded-xl p-4 mx-2 text-xs space-y-2 mb-4">
                            <!-- Detalhes básicos e lista de passageiros simplificada -->
                            <?php if (empty($ride['passengers'])): ?>
                                <p class="text-gray-400 italic">Sem passageiros.</p>
                            <?php else: ?>
                                <?php foreach ($ride['passengers'] as $p): ?>
                                    <div class="flex justify-between items-center bg-white p-2 rounded-lg border border-gray-100">
                                        <span><?= $p['name'] ?></span>
                                        <span
                                            class="<?= $p['payment_status'] == 'paid' ? 'text-green-500' : 'text-gray-400' ?> font-bold">
                                            <?= $p['payment_status'] == 'paid' ? 'Pago' : 'Pendente' ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="pt-2 flex justify-end">
                                <button onclick="confirmarCancelamento(<?= $ride['id'] ?>)"
                                    class="text-red-500 font-bold hover:underline">Cancelar Viagem</button>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
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