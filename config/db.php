<?php
/**
 * Database Connection Configuration
 * Uses PDO for secure and flexible database interaction.
 */

define('SECRET_KEY', 'vaijunto_app_token_secreto_2024');

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
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-03:00'"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In a production environment, don't leak connection details
    die("Database connection failed. Please try again later.");
}
