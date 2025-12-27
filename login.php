<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/User.php';

/**
 * Headers
 */
header('Content-Type: application/json; charset=utf-8');

/**
 * فقط POST مجازه
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => false,
        'message' => 'Method Not Allowed'
    ]);
    exit;
}

/**
 * گرفتن ورودی JSON
 */
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['email']) || empty($input['password'])) {
    http_response_code(422);
    echo json_encode([
        'status'  => false,
        'message' => 'Email and password are required'
    ]);
    exit;
}

$user = new User($pdo);

/**
 * Login
 */
$result = $user->login([
    'email'    => $input['email'],
    'password' => $input['password']
]);

/**
 * خروجی استاندارد API
 */
http_response_code($result['status'] ? 200 : 401);
echo json_encode($result);
