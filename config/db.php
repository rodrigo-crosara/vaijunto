<?php
/**
 * Database Connection Configuration
 * Uses PDO for secure and flexible database interaction.
 */

date_default_timezone_set('America/Sao_Paulo');

$host = 'localhost';
$db = 'vaijunto_db';
$user = 'root'; // Default XAMPP user
$pass = '';     // Default XAMPP password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (\PDOException $e) {
    // In a production environment, don't leak connection details
    // throw new \PDOException($e->getMessage(), (int)$e->getCode());
    die("Database connection failed: " . $e->getMessage());
}
