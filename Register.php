<?php
$_SERVER['REQUEST_METHOD'] = 'POST'; // شبیه‌سازی POST برای CLI

require __DIR__ . '/config.php';
require __DIR__ . '/User.php';

$user = new User($pdo);

$result = $user->register([
    'username' => 'ali',
    'email'    => 'ali@test1.com',
    'password' => '12345678'
]);

print_r($result);
