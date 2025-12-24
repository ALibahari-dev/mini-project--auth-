<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register(array $data): array {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['status'=>false,'message'=>'Invalid request'];
        }

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$username || !$email || !$password) {
            return ['status'=>false,'message'=>'All fields are required'];
        }

        $check = $this->pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute(['email'=>$email]);
        if ($check->fetch()) return ['status'=>false,'message'=>'Email already exists'];

        $stmt = $this->pdo->prepare("INSERT INTO users (username,email,password) VALUES (:username,:email,:password)");
        $stmt->execute([
            'username'=>$username,
            'email'=>$email,
            'password'=>password_hash($password,PASSWORD_DEFAULT)
        ]);

        return ['status'=>true,'user_id'=>$this->pdo->lastInsertId()];
    }

    public function login(array $data): array {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['status'=>false,'message'=>'Invalid request'];
        }

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$password) return ['status'=>false,'message'=>'Email and password required'];

        $stmt = $this->pdo->prepare("SELECT id,username,password FROM users WHERE email=:email LIMIT 1");
        $stmt->execute(['email'=>$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password,$user['password'])) {
            return ['status'=>false,'message'=>'Invalid credentials'];
        }

        // JWT Access Token
        $payload = [
            'iss'=>'yourdomain.com',
            'iat'=>time(),
            'exp'=>time()+JWT_EXPIRE,
            'user_id'=>$user['id']
        ];
        $accessToken = JWT::encode($payload, JWT_SECRET, 'HS256');

        // Refresh Token
        $refreshPayload = [
            'user_id'=>$user['id'],
            'iat'=>time(),
            'exp'=>time()+JWT_REFRESH_EXPIRE
        ];
        $refreshToken = JWT::encode($refreshPayload, JWT_SECRET, 'HS256');

        // می‌توانید refresh token را در دیتابیس ذخیره کنید برای invalid کردن آن در صورت لزوم

        return [
            'status'=>true,
            'access_token'=>$accessToken,
            'refresh_token'=>$refreshToken,
            'user'=>[
                'id'=>$user['id'],
                'username'=>$user['username'],
                'email'=>$email
            ]
        ];
    }

    public function verifyJWT(string $token): array {
        try {
            $decoded = JWT::decode($token,new Key(JWT_SECRET,'HS256'));
            return ['status'=>true,'data'=>(array)$decoded];
        } catch (\Exception $e) {
            return ['status'=>false,'message'=>'Invalid or expired token'];
        }
    }
}
