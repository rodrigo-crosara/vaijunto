<?php
/**
 * Teste de Fumaça (Health Check)
 * Verifica se as APIs principais estão respondendo corretamente.
 */

function checkUrl($name, $url, $method = 'GET', $params = [])
{
    $ch = curl_init();
    $fullUrl = $url;
    if ($method === 'GET' && !empty($params)) {
        $fullUrl .= '?' . http_build_query($params);
    }

    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $isJson = false;
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $isJson = true;
    }

    $success = ($httpCode === 200 && ($isJson ? ($data['success'] ?? true) : true));

    echo "<div style='margin-bottom: 10px; padding: 15px; border-radius: 12px; font-family: sans-serif; display: flex; align-items: center; justify-content: space-between; border: 1px solid " . ($success ? "#d4edda" : "#f8d7da") . "; background: " . ($success ? "#f8fff9" : "#fff5f5") . ";'>";
    echo "<div>";
    echo "<b style='color:#333;'>$name</b><br>";
    echo "<small style='color:#888;'>$url</small>";
    echo "</div>";
    echo "<div>";
    echo "<span style='padding: 5px 12px; border-radius: 8px; font-weight: 800; font-size: 0.8rem; background: " . ($success ? "#2ecc71" : "#e74c3c") . "; color: white;'>" . ($success ? "PASS" : "FAIL") . "</span>";
    echo " <span style='font-weight:bold; color:#555;'>HTTP $httpCode</span>";
    echo "</div>";
    echo "</div>";

    if (!$success) {
        echo "<pre style='background:#000; color:#0f0; padding:10px; font-size:11px; margin-top:5px;'>Response: " . htmlspecialchars($response) . "</pre>";
    }
}

// Tentar detectar a URL base automaticamente
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$basePath = str_replace('/tests/health_check.php', '', $_SERVER['REQUEST_URI']);
$baseUrl = "$protocol://$host$basePath";

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>VaiJunto - Health Check</title>
    <style>
        body {
            background: #f8fafc;
            padding: 40px;
        }

        .container {
            max-width: 700px;
            mx-auto;
            background: white;
            padding: 30px;
            border-radius: 30px;
            shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.05);
        }

        h1 {
            font-weight: 900;
            margin-bottom: 30px;
            color: #1e293b;
        }
    </style>
</head>

<body>
    <div class="container" style="margin: 0 auto;">
        <h1>🔍 Teste de Fumaça</h1>

        <?php
        // 1. Verificar Feed de Caronas
        checkUrl("API: Feed de Caronas", "$baseUrl/api/get_rides.php");

        // 2. Verificar Busca (Parâmetros)
        checkUrl("API: Busca de Caronas", "$baseUrl/api/search_rides.php", "GET", ['origin' => 'Asa']);

        // 3. Verificar Login (Deve falhar no honeypot ou faltar dados conforme esperado, mas o teste é ver se o endpoint responde)
        checkUrl("API: Endpoint de Login", "$baseUrl/api/login.php", "POST", ['phone' => '61999999999']);

        // 4. Verificar Manifesto PWA
        checkUrl("PWA: Manifesto JSON", "$baseUrl/manifest.json");

        // 5. Verificar Service Worker
        checkUrl("PWA: Service Worker JS", "$baseUrl/sw.js");
        ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="health_check.php"
                style="color:#009EF7; text-decoration:none; font-weight:800; font-size:0.9rem; text-transform:uppercase;">🔄
                Rodar Novamente</a>
        </div>
    </div>
</body>

</html>