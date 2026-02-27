<?php
/**
 * Main Controller: VaiJunto App (Lite Edition)
 */
session_start();
require_once 'config/db.php';

// Sistema de Auto-Login (Lembrar-me)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['vj_remember'])) {
    list($cookie_user_id, $cookie_hash) = explode('|', $_COOKIE['vj_remember']);

    // Verifica se o cookie não foi falsificado
    if (hash_equals(hash_hmac('sha256', $cookie_user_id, SECRET_KEY), $cookie_hash)) {
        // Cookie válido! Busca os dados do usuário e recria a sessão
        $stmtAuth = $pdo->prepare("SELECT id, name, is_driver, is_admin FROM users WHERE id = ?");
        $stmtAuth->execute([$cookie_user_id]);
        $userAuth = $stmtAuth->fetch();

        if ($userAuth) {
            $_SESSION['user_id'] = $userAuth['id'];
            $_SESSION['user_name'] = $userAuth['name'];
            $_SESSION['is_driver'] = $userAuth['is_driver'];
            $_SESSION['is_admin'] = $userAuth['is_admin'];
        }
    } else {
        // Cookie inválido/fraudado: destrói o cookie
        setcookie('vj_remember', '', time() - 3600, '/');
    }
}

// Redirecionamento para Login se não autenticado (mas não faz header location para evitar loop)
$page = $_GET['page'] ?? 'home';

// Atalho para links diretos de carona (ex: compartilhado no WhatsApp)
if (isset($_GET['ride_id'])) {
    $page = 'home'; // O feed.php já tem lógica para destacar o ride_id_param
    $_GET['ride_id_param'] = $_GET['ride_id'];
}

// Header deve ser incluído sempre para carregar CSS e Fonts
include 'includes/header.php';

// Se não autenticado e tentando acessar qualquer pag que não seja login -> login
if (!isset($_SESSION['user_id'])) {
    // Carrega a view de login diretamente
    include 'views/login.php';
} else {
    // Middleware de Onboarding (Força cadastro completo)
    // Permite acessar profile, logout e script de update
    if (
        empty($_SESSION['user_name']) &&
        $page !== 'profile' &&
        $page !== 'logout'
    ) {

        // JavaScript redirect para evitar loop de header
        echo "<script>window.location.href = 'index.php?page=profile&msg=complete_registration';</script>";
        exit;
    }

    // Autenticado: Carrega a App Shell
    echo '<main id="app-content" class="min-h-screen pb-32 pt-2 px-4 max-w-lg mx-auto">';

    switch ($page) {
        case 'home':
            include 'views/feed.php';
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
        case 'notifications':
            include 'views/notifications.php';
            break;
        case 'admin':
            include 'views/admin.php';
            break;
        case 'help':
            include 'views/help.php';
            break;
        default:
            include 'views/feed.php';
            break;
    }

    echo '</main>';
    include 'includes/nav.php';
}

// Footer carrega scripts essenciais (jQuery, SweetAlert, Mask)
include 'includes/footer.php';
?>