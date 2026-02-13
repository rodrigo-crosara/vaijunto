<?php
/**
 * View: Navegação Inferior (App Style Native)
 */
$currentPage = $_GET['page'] ?? 'home';
?>

<div
    class="fixed bottom-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-t border-white/20 shadow-[0_-10px_40px_rgba(0,0,0,0.03)] pb-safe no-select">
    <div class="flex justify-around items-center h-20 max-w-lg mx-auto px-4">

        <!-- Home -->
        <a href="index.php?page=home"
            class="flex flex-col items-center justify-center w-16 gap-1 transition-all <?= ($currentPage == 'home') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
            <i class="bi bi-house<?= ($currentPage == 'home') ? '-fill' : '' ?> text-2xl"></i>
            <span class="text-[9px] font-bold uppercase tracking-wider">Início</span>
        </a>

        <!-- Notificações (Sino) -->
        <a href="index.php?page=notifications" id="nav-bell-link"
            class="flex flex-col items-center justify-center w-16 gap-1 transition-all relative <?= ($currentPage == 'notifications') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
            <div class="relative">
                <i class="bi bi-bell<?= ($currentPage == 'notifications') ? '-fill' : '' ?> text-2xl"></i>
                <span id="notif-badge"
                    class="hidden absolute -top-1 -right-1.5 w-4.5 h-4.5 bg-red-500 text-white text-[8px] font-black rounded-full flex items-center justify-center min-w-[18px] h-[18px] shadow-lg shadow-red-200 border-2 border-white"></span>
            </div>
            <span class="text-[9px] font-bold uppercase tracking-wider">Alertas</span>
        </a>

        <!-- Ação Central (Dinâmica) -->
        <?php if (!empty($_SESSION['is_driver']) && $_SESSION['is_driver'] == 1): ?>
            <!-- Dashboard Motorista -->
            <a href="index.php?page=my_rides"
                class="flex flex-col items-center justify-center w-16 gap-1 transition-all <?= ($currentPage == 'my_rides') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
                <i class="bi bi-calendar-check<?= ($currentPage == 'my_rides') ? '-fill' : '' ?> text-2xl"></i>
                <span class="text-[9px] font-bold uppercase tracking-wider">Painel</span>
            </a>

            <!-- Botão Flutuante Criar -->
            <div class="relative -top-10">
                <a href="index.php?page=offer"
                    class="flex items-center justify-center w-16 h-16 rounded-3xl bg-primary text-white shadow-2xl shadow-primary/40 hover:scale-110 active:scale-95 transition-all outline-none border-4 border-white">
                    <i class="bi bi-plus-lg text-3xl font-bold"></i>
                </a>
            </div>
        <?php else: ?>
            <!-- Minhas Reservas (Passageiro) -->
            <a href="index.php?page=my_bookings"
                class="flex flex-col items-center justify-center w-16 gap-1 transition-all <?= ($currentPage == 'my_bookings') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
                <i class="bi bi-ticket-perforated<?= ($currentPage == 'my_bookings') ? '-fill' : '' ?> text-2xl"></i>
                <span class="text-[9px] font-bold uppercase tracking-wider">Reservas</span>
            </a>
        <?php endif; ?>

        <!-- Perfil -->
        <a href="index.php?page=profile"
            class="flex flex-col items-center justify-center w-16 gap-1 transition-all <?= ($currentPage == 'profile') ? 'text-primary' : 'text-gray-400 hover:text-gray-600' ?>">
            <i class="bi bi-person-circle<?= ($currentPage == 'profile') ? '-fill' : '' ?> text-2xl"></i>
            <span class="text-[9px] font-bold uppercase tracking-wider">Perfil</span>
        </a>

    </div>
</div>