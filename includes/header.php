<!DOCTYPE html>
<html lang="pt-br" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>VaiJunto - Caronas Sem Atrito</title>

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
    </style>

    <!-- Core Scripts (Loaded in Head) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="h-full antialiased text-gray-800">