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
        SELECT id, origin_text, destination_text, departure_time, seats_total, seats_available, price, status, waypoints, tags
        FROM rides
        WHERE driver_id = ?
        ORDER BY departure_time ASC
    ");
    $stmtRides->execute([$currentUserId]);
    $myRides = $stmtRides->fetchAll(PDO::FETCH_ASSOC);

    // 2. Para cada carona, buscar os passageiros
    foreach ($myRides as &$ride) {
        $stmtPassengers = $pdo->prepare("
            SELECT b.id as booking_id, u.name, u.photo_url, u.phone, b.meeting_point, b.note, b.status as booking_status, b.payment_status
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
                            <div class="font-bold text-lg truncate">
                                <?= $nextRide['origin_text'] ?>
                            </div>
                            <i class="bi bi-arrow-right text-blue-300"></i>
                            <div class="font-bold text-lg truncate">
                                <?= $nextRide['destination_text'] ?>
                            </div>
                        </div>

                        <!-- Botão FECHAR VAGAS -->
                        <?php if ($nextRide['seats_available'] > 0): ?>
                            <button onclick="fecharVagas(<?= $nextRide['id'] ?>)"
                                class="w-full mb-4 bg-red-500/80 hover:bg-red-500 backdrop-blur-md text-white py-3 rounded-2xl font-bold text-sm shadow-lg hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                                <i class="bi bi-slash-circle"></i> 🚫 Lotou Externamente / Fechar Vagas
                            </button>
                        <?php endif; ?>

                        <!-- Ações Rápidas -->
                        <div class="flex gap-2 mb-6">
                                    <button onclick='compartilharRide(<?= $nextRide['id'] ?>, "<?= addslashes($nextRide['origin_text']) ?>", "<?= addslashes($nextRide['destination_text']) ?>", "<?= $time ?>", "", "<?= number_format($nextRide['price'], 2, ',', '.') ?>")'
                                        class="flex-1 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all">
                                        <i class="bi bi-whatsapp"></i> Divulgar <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                                    </button>
                                    <button onclick='copiarOferta(<?= $nextRide['id'] ?>, "<?= addslashes($nextRide['origin_text']) ?>", "<?= addslashes($nextRide['destination_text']) ?>", "<?= $time ?>", "", "<?= number_format($nextRide['price'], 2, ',', '.') ?>")'
                                        class="w-14 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white py-3 rounded-2xl font-bold text-lg flex items-center justify-center gap-2 transition-all"
                                        title="Copiar Texto">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                    <button onclick="editarVagas(<?= $nextRide['id'] ?>, <?= $nextRide['seats_available'] ?>)"
                                        class="flex-1 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </button>
                        </div>

                        <?php if ($nextRide['seats_available'] == 0): ?>
                            <!-- Badge Relativo -->
                            <?php 
                            $diff = strtotime($nextRide['departure_time']) - time();
                            $days = floor($diff / (60 * 60 * 24));
                            if ($days > 0) {
                                echo '<div class="absolute top-4 right-4 bg-white/20 backdrop-blur-md px-3 py-1 rounded-lg text-[10px] font-bold text-white border border-white/20">Daqui a ' . $days . ' dias</div>';
                            }
                            ?>
                            
                            <button onclick="copiarLotado(<?= $nextRide['id'] ?>, '<?= $time ?>', '<?= addslashes($nextRide['destination_text']) ?>')"
                                class="w-full mb-6 bg-red-500 text-white py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-red-500/20 hover:scale-[1.02] transition-all">
                                <i class="bi bi-slash-circle"></i> Copiar Aviso de "LOTADO"
                            </button>
                        <?php endif; ?>

                        <!-- Checklist Express (Dentro do Card) -->
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-blue-200 uppercase">Passageiros</span>
                                <span
                                    class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded text-white"><?= count($nextRide['passengers']) ?>
                                    Confirmados</span>
                            </div>

                            <div id="passenger-list-container">
                                <?php if (empty($nextRide['passengers'])): ?>
                                    <div class="text-center py-2">
                                        <p class="text-blue-200 text-xs italic">Aguardando passageiros...</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($nextRide['passengers'] as $p):
                                            $isPaid = ($p['payment_status'] === 'paid');
                                            $pPhone = preg_replace('/\D/', '', $p['phone']);
                                            ?>
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <img src="<?= $p['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($p['name']) ?>"
                                                        class="w-8 h-8 rounded-full border border-white/30">
                                                    <div class="flex flex-col">
                                                        <span
                                                            class="text-sm font-bold leading-tight"><?= explode(' ', $p['name'])[0] ?></span>
                                                        <span class="text-[10px] text-blue-200 truncate max-w-[100px]">
                                                            <i class="bi bi-geo-alt-fill"></i> <?= $p['meeting_point'] ?>
                                                        </span>
                                                        <?php if (!empty($p['note'])): ?>
                                                            <span class="text-[10px] text-white/70 truncate max-w-[120px] mt-0.5 italic">
                                                                📍 <?= htmlspecialchars($p['note']) ?>
                                                            </span>
                                                        <?php endif; ?>
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
                                                <?php elseif ($p['booking_status'] === 'pending'): ?>
                                                    <div class="flex gap-1">
                                                        <button onclick="responderSolicitacao(<?= $p['booking_id'] ?>, 'confirm')"
                                                            class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center shadow-lg hover:scale-110 transition-transform" title="Aceitar">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                        <button onclick="responderSolicitacao(<?= $p['booking_id'] ?>, 'reject')"
                                                            class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center shadow-lg hover:scale-110 transition-transform" title="Recusar">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
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
                                <div>
                                <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
                                    <div><?= $ride['origin_text'] ?></div>
                                    <i class="bi bi-arrow-right text-gray-300 text-xs"></i>
                                    <div><?= $ride['destination_text'] ?></div>
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
                                    
                                    <button onclick='repetirViagem(<?= json_encode($ride) ?>)' 
                                            class="text-primary font-bold hover:underline flex items-center gap-1">
                                        <i class="bi bi-arrow-repeat"></i> Repetir
                                    </button>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
    const currentRideId = <?= $nextRide ? $nextRide['id'] : 0 ?>;

    function repetirViagem(ride) {
        // Salvar dados no localStorage para o offer.php pegar
        // Estrutura compatível com o fillWithLastRide
        const data = {
            origin: ride.origin_text,
            destination: ride.destination_text,
            price: ride.price,
            seats: ride.seats_total,
            details: ride.tags ? JSON.parse(ride.tags).details : '',
            waypoints: ride.waypoints
        };
        
        // Vamos usar a mesma lógica do "checkLastRide" mas forçando os dados
        // Uma forma é salvar isso no sessionStorage e o offer.php ler
        sessionStorage.setItem('repeat_ride_data', JSON.stringify(data));
        window.location.href = 'index.php?page=offer&mode=repeat';
    }

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

    function compartilharRide(rideId, origem, destino, hora, rota, valor) {
        const link = `${window.location.origin}${window.location.pathname}?ride_id=${rideId}`;
        const texto = getRideText(origem, destino, hora, rota, valor, link);
        const url = `https://wa.me/?text=${encodeURIComponent(texto)}`;
        window.open(url, '_blank');
    }

    // Polling Automático de Passageiros
    if (currentRideId > 0) {
        setInterval(() => {
            $.get('api/get_passengers_html.php?ride_id=' + currentRideId, function(data) {
                if (data.trim() !== "") {
                    $('#passenger-list-container').html(data);
                }
            });
        }, 5000);
    }


    function fecharVagas(rideId) {
        Swal.fire({
            title: 'Lotou? Fechar vagas?',
            text: "Sua carona sairá do feed imediatamente.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, fechar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api/close_ride.php', { rideId: rideId }, function(res) {
                    // Nota: api/close_ride.php espera JSON raw, mas $.post envia form-urlencoded. 
                    // Se o backend espera raw input, melhor usar fetch ou ajustar backend. 
                    // Pelo código anterior backend usa `json_decode(file_get_contents('php://input'))`.
                    // Vamos usar fetch para garantir compatibilidade com o backend existente.
                });
                
                // Correção para usar fetch e garantir envio correto
                fetch('api/close_ride.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({rideId: rideId})
                })
                .then(r => r.json())
                .then(data => {
                     Swal.fire({
                        title: 'Vagas Encerradas!',
                        html: '<p class="mb-3 text-sm text-gray-600">Copie o aviso abaixo e cole no grupo:</p>' +
                              '<textarea id="msg-encerrado" class="form-control mb-3 text-center font-bold" rows="2" readonly>❌ Carona ENCERRADA/LOTADA. Obrigado!</textarea>',
                        showCancelButton: true,
                        confirmButtonText: '📝 Copiar e ir pro Zap <i class="bi bi-box-arrow-up-right text-[10px]"></i>',
                        confirmButtonColor: '#25D366',
                        cancelButtonText: 'Concluir',
                        reverseButtons: true
                    }).then((res2) => {
                        if (res2.isConfirmed) {
                            var copyText = document.getElementById("msg-encerrado");
                            copyText.select();
                            document.execCommand("copy");
                            window.open('https://wa.me/', '_blank');
                        }
                        location.reload();
                    });
                });
            }
        });
    }

    async function responderSolicitacao(bookingId, action) {
        // action: 'confirm' ou 'reject'
        Swal.fire({ title: 'Processando...', didOpen: () => Swal.showLoading() });
        try {
            const res = await fetch('api/driver_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action + '_booking', bookingId: bookingId })
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                Swal.fire('Erro', data.message, 'error');
            }
        } catch (e) {
            Swal.fire('Erro', 'Falha na conexão', 'error');
        }
    }

    async function copiarLotado(id, hora, destino) {
        const texto = `❌ Carona das ${hora} para ${destino} -> ENCERRADA/LOTADA. Obrigado!`;
        try {
            await navigator.clipboard.writeText(texto);
            Swal.fire({
                toast: true,
                position: 'top',
                icon: 'success',
                title: 'Aviso de LOTADO copiado!',
                showConfirmButton: false,
                timer: 2000
            });
        } catch (err) {
            Swal.fire('Erro', 'Não foi possível copiar.', 'error');
        }
    }

    async function driverAction(data) {
        try {
            const response = await fetch('api/driver_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                Swal.fire({ text: result.message, icon: 'success', timer: 1500, showConfirmButton: false });
                if (data.action !== 'confirm_payment') {
                    setTimeout(() => location.reload(), 1500);
                }
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
</script>