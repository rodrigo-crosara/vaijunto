<?php
/**
 * View: Navegação Inferior (App Style Native)
 */
$currentPage = $_GET['page'] ?? 'home';

// Busca notificações não lidas para o badge inicial
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotif->execute([$_SESSION['user_id']]);
    $unreadCount = $stmtNotif->fetchColumn();
}
?>

<div
    class="fixed bottom-0 left-0 right-0 z-[100] bg-white/94 backdrop-blur-md border-t border-gray-100 shadow-[0_-10px_40px_rgba(0,0,0,0.05)] pb-safe no-select">
    <div class="flex justify-between items-center h-20 max-w-lg mx-auto px-2">

        <!-- Home -->
        <a href="index.php?page=home"
            class="flex-1 flex flex-col items-center justify-center gap-1 transition-all <?= ($currentPage == 'home') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
            <i class="bi bi-house<?= ($currentPage == 'home') ? '-fill' : '' ?> text-2xl"></i>
            <span class="text-[8px] font-black uppercase tracking-wider">Início</span>
        </a>

        <!-- Notificações (Sino) -->
        <a href="index.php?page=notifications" id="nav-bell-link"
            class="flex-1 flex flex-col items-center justify-center gap-1 transition-all relative <?= ($currentPage == 'notifications') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
            <div class="relative">
                <i class="bi bi-bell<?= ($currentPage == 'notifications') ? '-fill' : '' ?> text-2xl"></i>
                <span id="notif-badge"
                    class="<?= ($unreadCount > 0) ? 'flex' : 'hidden' ?> absolute -top-1 -right-1.5 w-4.5 h-4.5 bg-red-500 text-white text-[8px] font-black rounded-full flex items-center justify-center min-w-[17px] h-[17px] shadow-lg shadow-red-200 border-2 border-white">
                    <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                </span>
            </div>
            <span class="text-[8px] font-black uppercase tracking-wider">Alertas</span>
        </a>

        <!-- Ação Central (Dinâmica) -->
        <?php if (!empty($_SESSION['is_driver'])): ?>
            <!-- Botão Flutuante Criar (Centralizado para motoristas) -->
            <div class="relative -top-3 px-2">
                <a href="index.php?page=offer"
                    class="flex items-center justify-center w-14 h-14 rounded-3xl bg-primary text-white shadow-2xl shadow-primary/40 hover:scale-110 active:scale-95 transition-all outline-none border-4 border-white">
                    <i class="bi bi-plus-lg text-2xl font-black"></i>
                </a>
            </div>

            <!-- Dashboard Motorista -->
            <a href="index.php?page=my_rides"
                class="flex-1 flex flex-col items-center justify-center gap-1 transition-all <?= ($currentPage == 'my_rides') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
                <i class="bi bi-calendar-check<?= ($currentPage == 'my_rides') ? '-fill' : '' ?> text-2xl"></i>
                <span class="text-[8px] font-black uppercase tracking-wider">Painel</span>
            </a>
        <?php else: ?>
            <!-- Minhas Reservas (Passageiro) -->
            <a href="index.php?page=my_bookings"
                class="flex-1 flex flex-col items-center justify-center gap-1 transition-all <?= ($currentPage == 'my_bookings') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
                <i class="bi bi-ticket-perforated<?= ($currentPage == 'my_bookings') ? '-fill' : '' ?> text-2xl"></i>
                <span class="text-[8px] font-black uppercase tracking-wider">Reservas</span>
            </a>
        <?php endif; ?>

        <!-- Perfil -->
        <a href="index.php?page=profile"
            class="flex-1 flex flex-col items-center justify-center gap-1 transition-all <?= ($currentPage == 'profile') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
            <i class="bi bi-person-circle<?= ($currentPage == 'profile') ? '-fill' : '' ?> text-2xl"></i>
            <span class="text-[8px] font-black uppercase tracking-wider">Perfil</span>
        </a>

    </div>
</div>