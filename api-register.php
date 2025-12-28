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
 * Register
 */
$result = $user->register([
    'username' => $input['username'] ?? null,
    'email'    => $input['email'],
    'password' => $input['password']
]);

/**
 * خروجی استاندارد API
 */
if ($result['status'] === true) {
    http_response_code(201); // Created
} elseif ($result['status'] === false && isset($result['message']) && strpos($result['message'], 'exists') !== false) {
    http_response_code(409); // Conflict
} else {
    http_response_code(400);
}

echo json_encode($result);
