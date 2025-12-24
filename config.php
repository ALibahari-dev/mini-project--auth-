<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// دیتابیس
$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// JWT config
define('JWT_SECRET', $_ENV['JWT_SECRET']);
define('JWT_EXPIRE', (int)$_ENV['JWT_EXPIRE'])
define('JWT_REFRESH_EXPIRE', (int)$_ENV['JWT_REFRESH_EXPIRE']);
