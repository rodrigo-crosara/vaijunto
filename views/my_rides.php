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

    // 1. Buscar caronas criadas pelo motorista (últimos 7 dias + futuras)
    $stmtRides = $pdo->prepare("
        SELECT id, origin_text, destination_text, departure_time, seats_total, seats_available, price, status, waypoints, tags
        FROM rides
        WHERE driver_id = ? AND departure_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
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
            WHERE b.ride_id = ? AND b.status NOT IN ('rejected', 'canceled')
            ORDER BY b.created_at ASC
        ");
        $stmtPassengers->execute([$ride['id']]);
        $ride['passengers'] = $stmtPassengers->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro ao carregar dados. Tente novamente.</div>";
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

        <?php
        // 1. Próxima Viagem (Highlight)
        $nextRide = null;
        $nowGrace = time() - 3600; // 1 hora de tolerância padrão para visualização
        foreach ($myRides as $ride) {
            // A Próxima Missão é a primeira que não foi cancelada nem finalizada
            if ($ride['status'] === 'scheduled' || $ride['status'] === 'active') {
                $nextRide = $ride;
                break;
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
                                <?= htmlspecialchars($nextRide['origin_text']) ?>
                            </div>
                            <i class="bi bi-arrow-right text-blue-300"></i>
                            <div class="font-bold text-lg truncate">
                                <?= htmlspecialchars($nextRide['destination_text']) ?>
                            </div>
                        </div>

                        <?php 
                            $wpArr = json_decode($nextRide['waypoints'] ?? '[]', true);
                            if(!is_array($wpArr)) $wpArr = [];
                            $rotaStr = empty($wpArr) ? 'Via padrão' : implode(' -> ', $wpArr);
                            $tagsArr = json_decode($nextRide['tags'] ?? '{}', true);
                            $detalhesStr = $tagsArr['details'] ?? '';
                        ?>

                        <!-- Ações Rápidas -->
                        <div class="flex flex-col sm:flex-row gap-3 w-full mb-6">
                            <div class="flex gap-2 w-full sm:w-auto sm:flex-1">
                                <button onclick='compartilharRide(<?= $nextRide['id'] ?>, "<?= htmlspecialchars(addslashes($nextRide['origin_text']), ENT_QUOTES, "UTF-8") ?>", "<?= htmlspecialchars(addslashes($nextRide['destination_text']), ENT_QUOTES, "UTF-8") ?>", "<?= $time ?>", "<?= htmlspecialchars(addslashes($rotaStr), ENT_QUOTES, "UTF-8") ?>", "<?= number_format($nextRide['price'], 2, ",", ".") ?>", "<?= $nextRide['seats_available'] ?>", "<?= htmlspecialchars(addslashes($detalhesStr), ENT_QUOTES, "UTF-8") ?>")'
                                    class="flex-1 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all">
                                    <i class="bi bi-whatsapp"></i> Divulgar <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                                </button>

                                <button onclick='copiarOferta(<?= $nextRide['id'] ?>, "<?= htmlspecialchars(addslashes($nextRide['origin_text']), ENT_QUOTES, "UTF-8") ?>", "<?= htmlspecialchars(addslashes($nextRide['destination_text']), ENT_QUOTES, "UTF-8") ?>", "<?= $time ?>", "<?= htmlspecialchars(addslashes($rotaStr), ENT_QUOTES, "UTF-8") ?>", "<?= number_format($nextRide['price'], 2, ",", ".") ?>", "<?= $nextRide['seats_available'] ?>", "<?= htmlspecialchars(addslashes($detalhesStr), ENT_QUOTES, "UTF-8") ?>")'
                                    class="w-14 shrink-0 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white py-3 rounded-2xl font-bold text-lg flex items-center justify-center transition-all"
                                    title="Copiar Texto">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>

                            <button onclick="editarVagas(<?= $nextRide['id'] ?>, <?= $nextRide['seats_available'] ?>)"
                                class="w-full sm:w-auto sm:flex-1 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all">
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
                            
                            <button onclick="copiarLotado(<?= $nextRide['id'] ?>, '<?= $time ?>', '<?= htmlspecialchars(addslashes($nextRide['destination_text']), ENT_QUOTES, 'UTF-8') ?>')"
                                class="w-full mb-6 bg-red-500 text-white py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-red-500/20 hover:scale-[1.02] transition-all">
                                🚫 Copiar Aviso de "LOTADO"
                            </button>
                        <?php endif; ?>

                        <!-- Checklist Express (Dentro do Card) -->
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-blue-200 uppercase">Passageiros</span>
                                <span
                                    class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded text-white"><?= count($nextRide['passengers']) ?>
                                    Inscritos</span>
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
                                            $pPhone = ltrim($pPhone, '0'); // Remove o zero à esquerda do DDD
                                            if (strlen($pPhone) === 11 || strlen($pPhone) === 10) $pPhone = '55' . $pPhone;
                                            ?>
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <img src="<?= $p['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($p['name']) ?>"
                                                        class="w-8 h-8 rounded-full border border-white/30">
                                                    <div class="flex flex-col">
                                                        <span
                                                            class="text-sm font-bold leading-tight"><?= explode(' ', $p['name'])[0] ?></span>
                                                        <span class="text-[10px] text-blue-200 truncate max-w-[100px]">
                                                            <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($p['meeting_point']) ?>
                                                        </span>
                                                        <?php if (!empty($p['note'])): ?>
                                                            <span class="text-[10px] text-white/70 truncate max-w-[120px] mt-0.5 italic">
                                                                📍 <?= htmlspecialchars($p['note']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php if ($p['booking_status'] === 'confirmed'): ?>
                                                    <div class="flex gap-2">
                                                        <a href="https://wa.me/<?= $pPhone ?>" target="_blank"
                                                            class="h-8 rounded-full bg-green-500/20 text-green-300 flex items-center justify-center px-3 hover:bg-green-500 hover:text-white transition-all text-[10px] font-bold gap-1">
                                                            <i class="bi bi-whatsapp"></i> <span class="hidden sm:inline">WhatsApp</span>
                                                        </a>
                                                        <?php if ($isPaid): ?>
                                                            <span class="badge bg-green-500 text-[9px] font-bold py-1.5 px-2 rounded-lg">PAGO ✅</span>
                                                        <?php else: ?>
                                                            <button onclick="confirmarPagamento(<?= $p['booking_id'] ?>)"
                                                                class="btn btn-xs btn-light-success text-[9px] font-bold py-1 px-2 rounded-lg flex items-center gap-1">
                                                                💰 <span class="hidden sm:inline">Confirmar</span> Pagamento
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
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

                            <?php if ($nextRide['seats_available'] > 0): ?>
                                <div class="mt-4">
                                    <button onclick="fecharVagas(<?= $nextRide['id'] ?>, '<?= htmlspecialchars(addslashes($nextRide['destination_text']), ENT_QUOTES, 'UTF-8') ?>', '<?= $time ?>')"
                                        class="w-full bg-red-500/20 hover:bg-red-500/40 border border-red-500/30 text-red-100 py-3 rounded-2xl font-bold text-sm transition-all flex items-center justify-center gap-2 mt-4">
                                        🚫 Lotou fora do app (Fechar Vagas)
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                            <div class="flex flex-col gap-2 mt-4">
                                <button onclick="finalizarViagem(<?= $nextRide['id'] ?>)" 
                                    class="w-full bg-green-500 text-white py-4 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-green-500/20 hover:scale-[1.02] transition-all">
                                    <i class="bi bi-flag-fill"></i> FINALIZAR VIAGEM
                                </button>
                                
                                <button onclick="confirmarCancelamento(<?= $nextRide['id'] ?>)" 
                                    class="text-[11px] font-bold text-blue-200 hover:text-red-300 transition-colors flex items-center justify-center gap-1.5 mx-auto py-2">
                                    <i class="bi bi-trash3"></i> Cancelar esta viagem
                                </button>
                            </div>

                            <button onclick="gerarVolta(<?= $nextRide['id'] ?>, '<?= htmlspecialchars(addslashes($nextRide['origin_text']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($nextRide['destination_text']), ENT_QUOTES, 'UTF-8') ?>')" 
                                class="mt-4 text-[11px] font-bold text-white bg-white/10 hover:bg-white/20 px-4 py-2 rounded-xl transition-all flex items-center justify-center gap-2 mx-auto border border-white/10 uppercase tracking-tighter">
                                <i class="bi bi-arrow-repeat"></i> Criar Viagem de Volta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2. Planejamento (Próximas Viagens) -->
        <?php 
        $futureRides = array_filter($myRides, function($r) use ($nextRide) {
            return (!$nextRide || $r['id'] != $nextRide['id']) && in_array($r['status'], ['scheduled', 'active']);
        });
        ?>

        <?php if (!empty($futureRides)): ?>
        <div class="mb-10">
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1">Sua Agenda (Semana)</h2>
            <div class="space-y-4">
                <?php foreach ($futureRides as $ride): 
                    $rideTime = strtotime($ride['departure_time']);
                    $time = date('H:i', $rideTime);
                    $date = date('d/m', $rideTime);
                    $isCanceled = ($ride['status'] === 'canceled');
                    $pendingCount = 0;
                    $confirmedCount = 0;
                    foreach($ride['passengers'] as $p) {
                        if($p['booking_status'] === 'pending') $pendingCount++;
                        if($p['booking_status'] === 'confirmed') $confirmedCount++;
                    }

                    $wpArr = json_decode($ride['waypoints'] ?? '[]', true);
                    if(!is_array($wpArr)) $wpArr = [];
                    $rotaStr = empty($wpArr) ? 'Via padrão' : implode(' -> ', $wpArr);
                    $tagsArr = json_decode($ride['tags'] ?? '{}', true);
                    $detalhesStr = $tagsArr['details'] ?? '';
                ?>
                    <!-- Card de Agenda -->
                    <div class="bg-white rounded-2xl p-4 border border-blue-100 shadow-sm flex items-center justify-between border-l-4 <?= $pendingCount > 0 ? 'border-l-red-500 animate-[pulse_2s_infinite]' : 'border-l-blue-400' ?>">
                        <div class="flex items-center gap-4">
                            <div class="<?= $pendingCount > 0 ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' ?> h-10 w-10 rounded-xl flex items-center justify-center font-bold text-xs flex-col">
                                <span><?= $date ?></span>
                                <span class="text-[8px] opacity-70"><?= $time ?></span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
                                    <div><?= htmlspecialchars($ride['origin_text']) ?></div>
                                    <i class="bi bi-arrow-right text-gray-300 text-xs"></i>
                                    <div><?= htmlspecialchars($ride['destination_text']) ?></div>
                                    <?php if ($pendingCount > 0): ?>
                                        <span class="flex h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-gray-400">
                                    <?= $confirmedCount ?> confirmados
                                    <?php if ($pendingCount > 0): ?>
                                        • <b class="text-red-500"><?= $pendingCount ?> pendentes</b>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($pendingCount > 0): ?>
                                 <button class="bg-red-500 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg shadow-red-200 animate-bounce" type="button" data-bs-toggle="collapse" data-bs-target="#ride-details-<?= $ride['id'] ?>">
                                    <i class="bi bi-bell-fill"></i> REVISAR
                                 </button>
                            <?php else: ?>
                                <button class="text-gray-400 hover:text-primary transition-all p-2" type="button" data-bs-toggle="collapse" data-bs-target="#ride-details-<?= $ride['id'] ?>">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Detalhes (Collapse) -->
                    <div class="collapse <?= $pendingCount > 0 ? 'show' : '' ?>" id="ride-details-<?= $ride['id'] ?>">
                        <div class="bg-gray-50 rounded-xl p-4 mx-2 text-xs space-y-3 mb-4">
                            <div class="flex gap-2">
                                <button onclick='compartilharRide(<?= $ride['id'] ?>, "<?= htmlspecialchars(addslashes($ride['origin_text']), ENT_QUOTES, 'UTF-8') ?>", "<?= htmlspecialchars(addslashes($ride['destination_text']), ENT_QUOTES, 'UTF-8') ?>", "<?= $time ?>", "<?= htmlspecialchars(addslashes($rotaStr), ENT_QUOTES, 'UTF-8') ?>", "<?= number_format($ride['price'], 2, ',', '.') ?>", "<?= $ride['seats_available'] ?>", "<?= htmlspecialchars(addslashes($detalhesStr), ENT_QUOTES, 'UTF-8') ?>")'
                                    class="flex-1 bg-white border border-blue-100 text-blue-600 py-2.5 rounded-xl font-bold flex items-center justify-center gap-2 shadow-sm">
                                    <i class="bi bi-whatsapp"></i> Divulgar
                                </button>
                                <button onclick="editarVagas(<?= $ride['id'] ?>, <?= $ride['seats_available'] ?>)"
                                    class="bg-white border border-blue-100 text-blue-600 px-4 rounded-xl font-bold flex items-center justify-center gap-2 shadow-sm">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button onclick="confirmarCancelamento(<?= $ride['id'] ?>)"
                                    class="bg-white border border-red-50 text-red-400 px-4 rounded-xl font-bold flex items-center justify-center gap-2 shadow-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <button onclick="gerarVolta(<?= $ride['id'] ?>, '<?= htmlspecialchars(addslashes($ride['origin_text']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($ride['destination_text']), ENT_QUOTES, 'UTF-8') ?>')"
                                    class="bg-blue-50 border border-blue-100 text-blue-600 px-3 rounded-xl font-bold flex items-center justify-center gap-1 shadow-sm"
                                    title="Gerar Volta">
                                    <i class="bi bi-arrow-repeat"></i> Volta
                                </button>
                            </div>

                            <hr class="border-gray-200/50">
                            
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Passageiros</h4>
                            <?php if (empty($ride['passengers'])): ?>
                                <p class="text-gray-400 italic">Sem passageiros.</p>
                            <?php else: ?>
                                <?php foreach ($ride['passengers'] as $p): 
                                    $pPhone = preg_replace('/\D/', '', $p['phone']);
                                    $pPhone = ltrim($pPhone, '0'); // Remove o zero à esquerda do DDD
                                    if (strlen($pPhone) === 11 || strlen($pPhone) === 10) $pPhone = '55' . $pPhone;
                                ?>
                                    <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <img src="<?= $p['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($p['name']) ?>" class="w-6 h-6 rounded-full">
                                            <span class="font-bold text-gray-700"><?= $p['name'] ?></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <?php if ($p['booking_status'] === 'pending'): ?>
                                                <button onclick="responderSolicitacao(<?= $p['booking_id'] ?>, 'confirm')" class="bg-green-500 text-white px-2 py-1 rounded-lg font-bold">Aceitar</button>
                                                <button onclick="responderSolicitacao(<?= $p['booking_id'] ?>, 'reject')" class="bg-red-50 text-red-500 px-2 py-1 rounded-lg border border-red-100">Recusar</button>
                                            <?php else: ?>
                                                <div class="flex items-center gap-1.5">
                                                    <a href="https://wa.me/<?= $pPhone ?>" target="_blank" class="w-8 h-8 rounded-full bg-green-50 text-green-500 flex items-center justify-center border border-green-100 hover:bg-green-500 hover:text-white transition-all" title="WhatsApp">
                                                        <i class="bi bi-whatsapp"></i>
                                                    </a>
                                                    <button onclick="marcarNoShow(<?= $p['booking_id'] ?>)" class="w-8 h-8 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center border border-orange-100 hover:bg-orange-500 hover:text-white transition-all" title="Não compareceu (No-Show)">
                                                        <i class="bi bi-person-x"></i>
                                                    </button>
                                                </div>
                                                <?php if($p['payment_status'] == 'paid'): ?>
                                                    <button onclick="desfazerPagamento(<?= $p['booking_id'] ?>)" 
                                                        class="text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg border border-green-100">
                                                        Pago ✓
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="confirmarPagamento(<?= $p['booking_id'] ?>)"
                                                        class="text-[10px] font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100 italic">
                                                        Pendente
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3. Histórico (Viagens Passadas ou Canceladas) -->
        <div>
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1">Histórico de Caronas</h2>
            <div class="space-y-4">
                <?php 
                $pastRides = array_filter($myRides, function($r) {
                    return $r['status'] === 'finished' || $r['status'] === 'canceled';
                });
                // Inverter para mostrar as mais recentes primeiro no histórico
                $pastRides = array_reverse($pastRides);
                ?>

                <?php if (empty($pastRides)): ?>
                    <p class="text-center text-gray-400 text-xs py-4">Nenhum histórico encontrado.</p>
                <?php else: ?>
                    <?php foreach ($pastRides as $ride): 
                        $time = date('H:i', strtotime($ride['departure_time']));
                        $date = date('d/m', strtotime($ride['departure_time']));
                        $isCanceled = ($ride['status'] === 'canceled');
                    ?>
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 shadow-sm flex items-center justify-between opacity-70 grayscale-[0.5]">
                            <div class="flex items-center gap-4">
                                <div class="bg-white h-10 w-10 rounded-xl flex items-center justify-center text-gray-500 font-bold text-xs flex-col border border-gray-100">
                                    <span><?= $date ?></span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 text-sm font-bold text-gray-600">
                                        <div><?= htmlspecialchars($ride['origin_text']) ?></div>
                                        <i class="bi bi-arrow-right text-gray-300 text-xs"></i>
                                        <div><?= htmlspecialchars($ride['destination_text']) ?></div>
                                    </div>
                                    <span class="text-[10px] text-gray-400">
                                        <?= count($ride['passengers']) ?> passageiros • R$ <?= number_format($ride['price'], 2, ',', '.') ?>
                                        <?php if ($isCanceled): ?> • <span class="text-red-400">Cancelada</span><?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <button class="text-gray-400 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#ride-history-<?= $ride['id'] ?>">
                                <i class="bi bi-clock-history"></i>
                            </button>
                        </div>

                        <!-- Collapse Histórico -->
                        <div class="collapse" id="ride-history-<?= $ride['id'] ?>">
                            <div class="bg-white rounded-xl p-4 mx-2 text-xs mb-4 border border-gray-100">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-bold text-gray-500">Passageiros:</span>
                                    <button onclick='repetirViagem(<?= htmlspecialchars(json_encode($ride), ENT_QUOTES, 'UTF-8') ?>)' class="text-primary font-bold flex items-center gap-1">
                                        <i class="bi bi-arrow-repeat"></i> Repetir Carona
                                    </button>
                                </div>
                                <div class="space-y-1">
                                    <?php foreach ($ride['passengers'] as $p): ?>
                                        <div class="flex justify-between items-center text-gray-500">
                                            <span><?= $p['name'] ?></span>
                                            <span class="font-bold"><?= $p['payment_status'] == 'paid' ? 'Pago' : 'Não Pago' ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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

    function getRideText(origem, destino, hora, rota, valor, vagas, detalhes, link) {
        let texto = `*${vagas} Vaga(s) para ${destino}* 🚘\n`;
        texto += `⏰ Saída: ${hora}\n\n`;

        // Constroi a rota em lista vertical
        let pontos = [origem];
        if (rota && rota.trim() !== '' && rota !== 'Via padrão' && rota !== '[]') {
            let rotaArray = rota.includes(' -> ') ? rota.split(' -> ') : rota.split(',');
            rotaArray.forEach(p => pontos.push(p.trim()));
        }
        pontos.push(destino);
        pontos = [...new Set(pontos)]; // Remove repetidos

        pontos.forEach(p => { if (p) texto += `🚘 ${p}\n`; });

        texto += `\n💰 R$ ${valor}\n`;
        if (detalhes && detalhes.trim() !== '') {
            texto += `⚠️ ${detalhes}\n`;
        }
        texto += `\n👉 *Reservar vaga:* ${link}`;
        return texto;
    }

    // CORRIGIDO: rideId agora é o primeiro parâmetro, igual no HTML
    async function copiarOferta(rideId, origem, destino, hora, rota, valor, vagas, detalhes) {
        const link = `${window.location.origin}/${rideId}`;
        const texto = getRideText(origem, destino, hora, rota, valor, vagas, detalhes, link);
        try {
            await navigator.clipboard.writeText(texto);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Texto copiado!',
                showConfirmButton: false,
                timer: 2000
            });
        } catch (err) {
            Swal.fire('Erro', 'Não foi possível copiar.', 'error');
        }
    }

    function compartilharRide(rideId, origem, destino, hora, rota, valor, vagas, detalhes) {
        const link = `${window.location.origin}/${rideId}`;
        const texto = getRideText(origem, destino, hora, rota, valor, vagas, detalhes, link);
        const url = `https://wa.me/?text=${encodeURIComponent(texto)}`;
        window.open(url, '_blank');
    }

    // Polling Automático de Passageiros
    if (currentRideId > 0) {
        setInterval(() => {
            if (document.hidden) return; // Economia de energia: Pausa se o app estiver em 2º plano
            $.get('api/get_passengers_html.php?ride_id=' + currentRideId, function(data) {
                if (data.trim() !== "") {
                    $('#passenger-list-container').html(data);
                }
            });
        }, 5000);
    }


    function fecharVagas(rideId, destino = '', hora = '') {
        Swal.fire({
            title: 'Fechar Vagas?',
            text: "Isso encerrará a carona e ninguém mais poderá reservar pelo app.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, lotou!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api/driver_actions.php', { action: 'close_ride', ride_id: rideId }, function(res) {
                    if(res.success) {

                        // Monta a mensagem para o WhatsApp
                        const textoZap = `❌ *Carona Lotada / Encerrada!*\n\nPessoal, a vaga para *${destino}* (Saída: ${hora}) já foi preenchida.\nObrigado!`;
                        const waLink = `https://wa.me/?text=${encodeURIComponent(textoZap)}`;

                        Swal.fire({
                            title: 'Vagas Fechadas!',
                            text: 'Deseja avisar no grupo do WhatsApp que a carona lotou?',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: '<i class="bi bi-whatsapp"></i> Avisar no Grupo',
                            cancelButtonText: 'Apenas fechar',
                            customClass: {
                                confirmButton: 'bg-green-500 text-white font-bold px-6 py-3 rounded-2xl shadow-lg hover:scale-105 transition-all',
                                cancelButton: 'bg-gray-100 text-gray-500 font-bold px-6 py-3 rounded-2xl ml-2'
                            },
                            buttonsStyling: false
                        }).then((resZap) => {
                            if (resZap.isConfirmed) {
                                window.open(waLink, '_blank');
                            }
                            location.reload();
                        });

                    } else {
                        Swal.fire('Erro', res.message, 'error');
                    }
                }, 'json');
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

    function desfazerPagamento(bookingId) {
        Swal.fire({
            title: 'Estornou o dinheiro?',
            text: 'O status de pagamento voltará para pendente. Use isto se você devolveu o PIX ao passageiro.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, marcar como pendente',
            cancelButtonText: 'Voltar',
            customClass: { confirmButton: 'bg-orange-500 text-white font-bold px-6 py-3 rounded-2xl shadow-lg', cancelButton: 'bg-gray-100 text-gray-500 font-bold px-6 py-3 rounded-2xl ml-2' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                driverAction({ action: 'undo_payment', bookingId: bookingId });
            }
        });
    }

    function finalizarViagem(rideId) {
        Swal.fire({
            title: 'Finalizar Viagem?',
            text: "A carona sairá do destaque e irá para o histórico. Certifique-se de que todos embarcaram e pagaram!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, Finalizar!',
            cancelButtonText: 'Ainda não',
            confirmButtonColor: '#10b981'
        }).then((result) => {
            if (result.isConfirmed) {
                driverAction({ action: 'finish_ride', rideId: rideId });
            }
        });
    }

    function marcarNoShow(bookingId) {
        Swal.fire({
            title: 'Passageiro não apareceu?',
            text: "O passageiro será marcado como 'Ausente' e não poderá avaliar você por esta carona.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, não apareceu',
            cancelButtonText: 'Cancelar',
            customClass: { 
                confirmButton: 'bg-orange-500 text-white font-bold px-6 py-3 rounded-2xl shadow-lg', 
                cancelButton: 'bg-gray-100 text-gray-500 font-bold px-6 py-3 rounded-2xl ml-2' 
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                driverAction({ action: 'no_show_booking', bookingId: bookingId });
            }
        });
    }

    async function gerarVolta(originalRideId, origem, destino) {
        const { value: time } = await Swal.fire({
            title: 'Horário de Volta',
            html: `De: <b>${destino}</b><br>Para: <b>${origem}</b>`,
            input: 'time',
            inputLabel: 'Que horas você volta?',
            inputValue: '18:00',
            showCancelButton: true,
            confirmButtonText: 'Criar Volta 🚀',
            cancelButtonText: 'Cancelar',
            customClass: { confirmButton: 'btn btn-primary px-6', cancelButton: 'btn btn-light ml-2' }
        });

        if (time) {
            Swal.fire({
                title: 'Processando...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch('api/create_return_ride.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        originalRideId, 
                        newDepartureTime: null, // O backend cuidará de manter o dia e mudar a hora
                        repeat_time: time // Passamos a hora desejada
                    })
                });
                
                // Nota: O backend create_return_ride.php original só aceita newDepartureTime full.
                // Mas podemos enviar o time e deixar o swal mais amigável.
                // Para simplificar, vamos enviar o horário formatado para o dia da carona original.
                
                const result = await response.json();
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Viagem de Volta Criada!',
                        text: result.message,
                        showCancelButton: true,
                        confirmButtonText: 'Sim, Ver Agora',
                        cancelButtonText: 'OK'
                    }).then((r) => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erro', result.message, 'error');
                }
            } catch (err) {
                Swal.fire('Erro', 'Falha na conexão.', 'error');
            }
        }
    }
</script>