<?php
// 1. Valores Padrão (Fallback para a página inicial)
$pageTitle = "Carona Online | Suas caronas facilitadas";
$pageDesc = "Encontre e ofereça caronas de forma rápida e segura. Junte-se à nossa comunidade!";

// URL da imagem padrão
$ogImage = "https://carona.online/assets/media/app/icon-512.png";

// 2. Se a URL tiver o ID de uma carona (ex: /1 ou index.php?ride_id=1)
if (isset($_GET['ride_id']) && is_numeric($_GET['ride_id'])) {
    try {
        $stmtMeta = $pdo->prepare("SELECT origin_text, destination_text, departure_time, price, waypoints FROM rides WHERE id = ?");
        $stmtMeta->execute([$_GET['ride_id']]);
        $rideMeta = $stmtMeta->fetch(PDO::FETCH_ASSOC);

        if ($rideMeta) {
            $origemMeta = htmlspecialchars($rideMeta['origin_text']);
            $destinoMeta = htmlspecialchars($rideMeta['destination_text']);
            $horaMeta = date('H:i', strtotime($rideMeta['departure_time']));
            $precoMeta = number_format($rideMeta['price'], 2, ',', '.');

            $wpArrMeta = json_decode($rideMeta['waypoints'] ?? '[]', true);
            if (!is_array($wpArrMeta))
                $wpArrMeta = [];
            $rotaMeta = empty($wpArrMeta) ? '' : ' | Via: ' . htmlspecialchars(implode(', ', $wpArrMeta));

            // 3. Substitui pelos valores Dinâmicos da Carona
            $pageTitle = "🚗 Vaga: $origemMeta ➝ $destinoMeta";
            $pageDesc = "⏰ $horaMeta • 💰 R$ $precoMeta $rotaMeta. Toque para garantir a sua vaga!";
        }
    } catch (Exception $e) {
        // Se der erro de banco, segue com o título padrão
    }
}

// Captura a URL atual limpa
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$currentUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>
        <?= $pageTitle ?>
    </title>
    <meta name="title" content="<?= $pageTitle ?>">
    <meta name="description" content="<?= $pageDesc ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $currentUrl ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $pageDesc ?>">
    <meta property="og:image" content="<?= $ogImage ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $currentUrl ?>">
    <meta property="twitter:title" content="<?= $pageTitle ?>">
    <meta property="twitter:description" content="<?= $pageDesc ?>">
    <meta property="twitter:image" content="<?= $ogImage ?>">

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#009EF7">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="assets/media/app/icon-192.png">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Configuração de Cores -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#009EF7',
                        secondary: '#F3F6F9',
                        light: '#F8FAFC',
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            -webkit-tap-highlight-color: transparent;
        }

        /* Suporte para notch de iPhones */
        .pb-safe {
            padding-bottom: env(safe-area-inset-bottom, 1.5rem);
        }

        /* Animações Modernas */
        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 0.95;
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.02);
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 4s infinite ease-in-out;
        }
    </style>

    <!-- Core Scripts (Loaded in Head) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="h-full antialiased text-gray-800">