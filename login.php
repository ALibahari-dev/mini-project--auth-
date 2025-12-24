<?php
require __DIR__ . '/config.php';   // ← باید اول باشد
require __DIR__ . '/User.php';

$_SERVER['REQUEST_METHOD'] = 'POST';

$user = new User($pdo);

$result = $user->login([
    'email'    => 'ali@test2.com',
    'password' => '12345678'
]);

print_r($result);
