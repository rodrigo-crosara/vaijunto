<?php
/**
 * View: Central de Notificações
 */
require_once 'config/db.php';

$currentUserId = $_SESSION['user_id'] ?? 0;

if (!$currentUserId) {
    header("Location: index.php?page=home");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, type, message, link_url, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$currentUserId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notifications = [];
}

// Ícones por tipo
$typeIcons = [
    'booking' => ['bi-ticket-perforated-fill', 'text-primary', 'bg-blue-50'],
    'cancel' => ['bi-x-circle-fill', 'text-red-500', 'bg-red-50'],
    'confirmed' => ['bi-check-circle-fill', 'text-green-500', 'bg-green-50'],
    'payment' => ['bi-cash-stack', 'text-emerald-500', 'bg-emerald-50'],
    'system' => ['bi-bell-fill', 'text-gray-500', 'bg-gray-100'],
];
?>

<div class="max-w-xl mx-auto pt-6 px-4 pb-20">
    <div class="flex items-center justify-between mb-6 px-1">
        <h1 class="text-2xl font-bold text-gray-900 leading-tight">Notificações</h1>
        <?php if (!empty($notifications)): ?>
            <button onclick="markAllRead()" class="text-xs font-bold text-primary hover:underline">Marcar tudo como
                lido</button>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div
            class="flex flex-col items-center justify-center py-16 text-center bg-white rounded-[2.5rem] border border-dashed border-gray-200 shadow-sm">
            <div class="bg-gray-50 rounded-full p-8 mb-6">
                <i class="bi bi-bell-slash text-5xl text-gray-200"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">Tudo tranquilo por aqui.</h3>
            <p class="text-gray-400 text-sm max-w-xs mx-auto">Nenhuma notificação no momento.</p>
        </div>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($notifications as $n):
                $icon = $typeIcons[$n['type']] ?? $typeIcons['system'];
                $isUnread = !$n['is_read'];
                $timeAgo = timeAgo($n['created_at']);
                ?>
                <a href="javascript:void(0)"
                    onclick="openNotification(<?= $n['id'] ?>, '<?= addslashes($n['link_url'] ?: '') ?>')"
                    class="flex items-start gap-4 p-4 rounded-2xl transition-all hover:shadow-md <?= $isUnread ? 'bg-blue-50/70 border border-blue-100' : 'bg-white border border-gray-50' ?>">
                    <div class="w-10 h-10 rounded-full <?= $icon[2] ?> flex items-center justify-center shrink-0 mt-0.5">
                        <i class="bi <?= $icon[0] ?> <?= $icon[1] ?> text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p
                            class="text-sm <?= $isUnread ? 'font-bold text-gray-900' : 'font-medium text-gray-600' ?> leading-snug">
                            <?= htmlspecialchars($n['message']) ?>
                        </p>
                        <span class="text-[10px] font-bold text-gray-300 uppercase tracking-wider mt-1 block">
                            <?= $timeAgo ?>
                        </span>
                    </div>
                    <?php if ($isUnread): ?>
                        <div class="w-2.5 h-2.5 rounded-full bg-primary shrink-0 mt-2"></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
function timeAgo($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0)
        return $diff->d . 'd atrás';
    if ($diff->h > 0)
        return $diff->h . 'h atrás';
    if ($diff->i > 0)
        return $diff->i . 'min atrás';
    return 'agora';
}
?>

<script>
    function openNotification(id, link) {
        fetch('api/mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: id })
        }).then(() => {
            if (link) {
                window.location.href = link;
            } else {
                // Remove visual de não-lido
                location.reload();
            }
        });
    }

    function markAllRead() {
        fetch('api/mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ target: 'all' })
        }).then(() => location.reload());
    }
</script>