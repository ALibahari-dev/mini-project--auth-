<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['access_token'])) {
    http_response_code(400);
    exit;
}

$_SESSION['access_token']  = $data['access_token'];
$_SESSION['refresh_token'] = $data['refresh_token'];
$_SESSION['user']          = $data['user'];

http_response_code(200);
