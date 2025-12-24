<?php
$_SERVER['REQUEST_METHOD'] = 'POST'; // اضافه کن برای تست CLI

require 'db.php';
require 'User.php';

$user = new User($pdo);

$result = $user->register([
    'username' => 'ali',
    'email'    => 'ali@test1.com',
    'password' => '12345678'
]);

print_r($result);
