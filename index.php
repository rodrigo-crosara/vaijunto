<?php
/**
 * Main Controller: VaiJunto App (Lite Edition)
 */
session_start();
require_once 'config/db.php';

// Redirecionamento para Login se não autenticado
$page = $_GET['page'] ?? 'home';
if (!isset($_SESSION['user_id']) && $page !== 'login') {
    header('Location: index.php?page=login');
    exit;
}

// Se já está logado e tenta ir pro login, manda pra home
if (isset($_SESSION['user_id']) && $page === 'login') {
    header('Location: index.php?page=home');
    exit;
}

include 'includes/header.php';

// Wrapper principal do app com padding para a barra inferior
echo '<main id="app-content" class="min-h-screen pb-32 pt-6 px-4 max-w-lg mx-auto overflow-x-hidden">';

switch ($page) {
    case 'home':
        include 'views/feed.php';
        break;
    case 'login':
        include 'views/login.php';
        break;
    case 'profile':
        include 'views/profile.php';
        break;
    case 'offer':
        include 'views/offer.php';
        break;
    case 'my_rides':
        include 'views/my_rides.php';
        break;
    case 'my_bookings':
        include 'views/my_bookings.php';
        break;
    default:
        include 'views/feed.php';
        break;
}

echo '</main>';

// Só exibe a navegação se não estiver no login
if ($page !== 'login') {
    include 'includes/nav.php';
}

include 'includes/footer.php';
?>