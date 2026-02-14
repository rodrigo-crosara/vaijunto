<?php
/**
 * DB Seeder: Popular banco de dados com dados fictícios para teste.
 * ACESSO: db/seed.php?token=123456
 */
require_once '../config/db.php';

$startTime = microtime(true);

// 1. Segurança: Token de acesso
$token = $_GET['token'] ?? '';
if ($token !== '123456') {
    header('HTTP/1.0 403 Forbidden');
    die('<h1 style="color:red; font-family:sans-serif;">🚫 Acesso Negado</h1><p>Token inválido.</p>');
}

// 2. Confirmação (prevenir execução acidental mesmo com token)
if (!isset($_GET['force']) || $_GET['force'] !== 'true') {
    echo '<div style="font-family:sans-serif; text-align:center; padding: 50px;">';
    echo '<h1 style="color:#e74c3c;">⚠️ AVISO CRÍTICO</h1>';
    echo '<p style="font-size:1.2rem;">Isso irá <b>APAGAR TODOS OS DADOS</b> do banco (Usuários, Caronas, Reservas, etc).</p>';
    echo '<a href="?token=123456&force=true" style="display:inline-block; padding:15px 30px; background:#e74c3c; color:white; text-decoration:none; border-radius:10px; font-weight:bold;">SIM, TENHO CERTEZA. LIMPAR E SEMEAR.</a>';
    echo '</div>';
    exit;
}

try {
    // 3. Limpar Banco
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables = ['users', 'cars', 'rides', 'bookings', 'ratings', 'notifications', 'access_logs'];
    foreach ($tables as $t) {
        $pdo->exec("TRUNCATE TABLE $t");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<pre style='background:#f4f4f4; padding:20px; border-radius:10px; font-family:monospace;'>";
    echo "🧹 Banco de dados limpo com sucesso.\n";

    // 4. Geradores de Dados
    $firstNames = ['Ana', 'Bruno', 'Carlos', 'Daniela', 'Eduardo', 'Fernanda', 'Gabriel', 'Heloísa', 'Ítalo', 'Juliana', 'Kléber', 'Lívia', 'Marcelo', 'Natália', 'Otávio', 'Patrícia', 'Ricardo', 'Sílvia', 'Tiago', 'Ursula', 'Vitor', 'Wanessa', 'Xavier', 'Yara', 'Zeca'];
    $lastNames = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves', 'Pereira', 'Lima', 'Gomes', 'Costa', 'Ribeiro', 'Martins', 'Carvalho', 'Almeida', 'Lopes', 'Soares', 'Fernandes', 'Vieira', 'Barbosa'];
    $locais = ['Asa Norte', 'Asa Sul', 'Lago Norte', 'Lago Sul', 'Águas Claras', 'Taguatinga', 'Guará', 'Sudoeste', 'Octogonal', 'Cruzeiro', 'Sobradinho', 'Samambaia'];
    $carrosModels = ['VW Gol', 'Hyundai HB20', 'Chevrolet Onix', 'Fiat Argo', 'Toyota Corolla', 'Honda Civic', 'Jeep Compass', 'Ford Ka', 'Renault Kwid', 'Fiat Uno'];
    $cores = ['Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul'];

    // 5. Criar 50 Usuários
    $userIds = [];
    $driverIds = [];

    // O primeiro sempre será nosso Admin pra não perder acesso
    $pdo->prepare("INSERT INTO users (id, name, phone, is_driver, is_admin, reputation, created_at) VALUES (1, 'Admin VaiJunto', '61999999999', 1, 1, 5.0, NOW())")->execute();
    $userIds[] = 1;
    $driverIds[] = 1;
    // Adicionar carro pro admin
    $pdo->prepare("INSERT INTO cars (user_id, model, color, plate) VALUES (1, 'Tesla Model 3', 'Branco', 'ADM-1234')")->execute();

    for ($i = 2; $i <= 50; $i++) {
        $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
        $phone = '619' . rand(10000000, 99999999);
        $isDriver = ($i <= 20) ? 1 : 0; // Primeiros 20 são motoristas
        $photo = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=random&color=fff&size=200";

        $stmt = $pdo->prepare("INSERT INTO users (name, phone, is_driver, is_admin, photo_url, reputation, created_at) VALUES (?, ?, ?, 0, ?, ?, NOW())");
        $stmt->execute([$name, $phone, $isDriver, $photo, (rand(40, 50) / 10)]);
        $uid = $pdo->lastInsertId();
        $userIds[] = $uid;

        if ($isDriver) {
            $driverIds[] = $uid;
            $pdo->prepare("INSERT INTO cars (user_id, model, color, plate) VALUES (?, ?, ?, ?)")
                ->execute([
                    $uid,
                    $carrosModels[array_rand($carrosModels)],
                    $cores[array_rand($cores)],
                    strtoupper(substr(md5(rand()), 0, 3)) . '-' . rand(1000, 9999)
                ]);
        }
    }
    echo "👤 50 Usuários criados (20 motoristas com carros).\n";

    // 6. Criar 100 Caronas
    $rideIds = [];
    $pastRideIds = [];

    for ($i = 0; $i < 100; $i++) {
        $driverId = $driverIds[array_rand($driverIds)];
        $origin = $locais[array_rand($locais)];
        $dest = $locais[array_rand($locais)];
        while ($origin === $dest)
            $dest = $locais[array_rand($locais)];

        $isPast = ($i < 50); // 50 passadas, 50 futuras
        if ($isPast) {
            $time = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days'));
            $status = 'completed';
        } else {
            $time = date('Y-m-d H:i:s', strtotime('+' . rand(1, 14) . ' days ' . rand(0, 23) . ' hours'));
            $status = 'active';
        }

        $price = rand(5, 25) . '.00';
        $seats = rand(2, 4);

        $stmt = $pdo->prepare("INSERT INTO rides (driver_id, origin_text, destination_text, departure_time, price, seats_total, seats_available, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$driverId, $origin, $dest, $time, $price, $seats, $seats, $status]);
        $rid = $pdo->lastInsertId();
        $rideIds[] = $rid;
        if ($isPast)
            $pastRideIds[] = $rid;
    }
    echo "🚗 100 Caronas criadas (50 futuras, 50 passadas).\n";

    // 7. Criar 200 Reservas Aleatórias
    $reservasCriadas = 0;
    for ($i = 0; $i < 200; $i++) {
        $rideId = $rideIds[array_rand($rideIds)];
        $passengerId = $userIds[array_rand($userIds)];

        // Verificar se passageiro não é o motorista
        $stmtCheck = $pdo->prepare("SELECT driver_id, seats_available, status FROM rides WHERE id = ?");
        $stmtCheck->execute([$rideId]);
        $ride = $stmtCheck->fetch();

        if ($ride && $ride['driver_id'] != $passengerId && $ride['seats_available'] > 0) {
            $status = ($ride['status'] === 'completed') ? 'confirmed' : (rand(0, 5) > 0 ? 'confirmed' : 'pending');

            $pdo->prepare("INSERT INTO bookings (ride_id, passenger_id, status, created_at) VALUES (?, ?, ?, NOW())")
                ->execute([$rideId, $passengerId, $status]);

            // Se confirmado, abate vaga
            if ($status === 'confirmed') {
                $pdo->prepare("UPDATE rides SET seats_available = seats_available - 1 WHERE id = ?")->execute([$rideId]);
            }
            $reservasCriadas++;
        }
    }
    echo "🎫 {$reservasCriadas} Reservas aleatórias processadas.\n";

    // 8. Avaliações para caronas passadas
    $avaliacoes = 0;
    foreach ($pastRideIds as $rid) {
        $stmtB = $pdo->prepare("SELECT passenger_id, driver_id FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.ride_id = ? AND b.status = 'confirmed'");
        $stmtB->execute([$rid]);
        $passengers = $stmtB->fetchAll();

        foreach ($passengers as $p) {
            if (rand(0, 1)) { // 50% chance de avaliar
                $score = rand(4, 5);
                $comments = ['Excelente motorista!', 'Viagem tranquila.', 'Muito pontual.', 'Gostei bastante.', 'Recomendo!'];

                // Passageiro avalia motorista
                $pdo->prepare("INSERT INTO ratings (booking_id, rater_id, rated_id, score, comment, created_at) VALUES ((SELECT id FROM bookings WHERE ride_id = ? AND passenger_id = ? LIMIT 1), ?, ?, ?, ?, NOW())")
                    ->execute([$rid, $p['passenger_id'], $p['passenger_id'], $p['driver_id'], $score, $comments[array_rand($comments)]]);
                $avaliacoes++;
            }
        }
    }
    echo "⭐ {$avaliacoes} Avaliações geradas.\n";

    $endTime = microtime(true);
    $totalTime = round($endTime - $startTime, 4);
    echo "\n✨ <b>Mágica concluída!</b> App populado e pronto para testes.";
    echo "\n⏱️ Tempo de execução: {$totalTime}s";
    echo "\n\n<a href='../index.php' style='color:#009EF7; font-weight:bold; text-decoration:none;'>🚀 IR PARA O APP</a>";
    echo "</pre>";

} catch (PDOException $e) {
    echo "❌ <b>ERRO NO BANCO:</b> " . $e->getMessage();
}
