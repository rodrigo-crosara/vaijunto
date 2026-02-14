<?php
/**
 * DB Reset: Retorna o sistema ao estado original (limpa banco e fotos).
 * ACESSO: db/reset.php?token=123456&confirm=yes
 */
require_once '../config/db.php';

echo "<body style='font-family:sans-serif; background:#f4f7f6; padding:40px; color:#2c3e50;'>";
echo "<div style='max-width:600px; margin:0 auto; background:white; padding:30px; border-radius:30px; shadow:0 10px 30px rgba(0,0,0,0.05);'>";

// 1. Segurança: Dupla checagem
$token = $_GET['token'] ?? '';
$confirm = $_GET['confirm'] ?? '';

if ($token !== '123456' || $confirm !== 'yes') {
    echo "<h1 style='color:#e74c3c;'>🛑 MODO DE SEGURANÇA</h1>";
    echo "<p>Para zerar o sistema completamente, você precisa usar os parâmetros corretos na URL:</p>";
    echo "<code style='background:#fdf0f0; padding:10px; display:block; border-radius:10px; color:#c0392b;'>?token=123456&confirm=yes</code>";
    echo "<br><p><b>Atenção:</b> Esta ação excluirá todos os usuários, fotos e histórico.</p>";
    exit;
}

try {
    echo "<h1>🧹 Resetando Sistema...</h1><hr style='border:1px solid #eee; margin-bottom:20px;'>";

    // 2. Limpar Tabelas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables = ['notifications', 'ratings', 'bookings', 'rides', 'cars', 'access_logs', 'users'];

    foreach ($tables as $t) {
        try {
            $pdo->exec("TRUNCATE TABLE $t");
            echo "<div style='margin-bottom:8px;'>✅ Tabela <b style='color:#27ae60;'>$t</b> zerada.</div>";
        } catch (PDOException $e) {
            echo "<div style='margin-bottom:8px;'>❌ Erro em $t: " . $e->getMessage() . "</div>";
        }
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<br>";

    // 3. Limpar Arquivos Físicos (Imagens de Upload)
    $folders = [
        '../assets/media/uploads/users/',
        '../assets/media/uploads/cars/'
    ];

    foreach ($folders as $dir) {
        if (!is_dir($dir)) {
            echo "<div style='margin-bottom:8px; color:#95a5a6;'>ℹ️ Pasta $dir não encontrada.</div>";
            continue;
        }

        $files = glob($dir . '*');
        $deletedCount = 0;
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitkeep' && basename($file) !== 'index.html') {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }
        echo "<div style='margin-bottom:8px;'>✅ Pasta <b style='color:#27ae60;'>$dir</b> limpa. ($deletedCount arquivos removidos)</div>";
    }

    echo "<hr style='border:1px solid #eee; margin-top:20px;'>";
    echo "<h2 style='color:#2ecc71;'>✨ Operação Concluída!</h2>";
    echo "<p>O sistema agora está em seu estado original.</p>";
    echo "<a href='../index.php' style='display:inline-block; margin-top:10px; padding:12px 25px; background:#009EF7; color:white; text-decoration:none; border-radius:12px; font-weight:bold;'>Voltar para a Home</a>";

} catch (PDOException $e) {
    echo "<h2 style='color:#e74c3c;'>❌ Falha Crítica</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "</div></body>";
