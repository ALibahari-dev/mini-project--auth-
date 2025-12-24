<?php
require 'vendor/autoload.php';  // اضافه کن این خط

require 'db.php';
require 'User.php';

$_SERVER['REQUEST_METHOD'] = 'POST';

$user = new User($pdo);

$result = $user->login([
    'email' => 'ali@test1.com',
    'password' => '12345678'
], true);

print_r($result);
