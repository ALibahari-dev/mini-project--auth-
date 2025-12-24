<?php
declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User
{
    private PDO $pdo;
    private string $jwt_secret = 'd8f3b9a5c1e6f7d9b2c3e4f6a1b2c3d4e5f6g7h8i9j0k1l2'; 
    private int $jwt_exp = 3600; // 1 ساعت

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }
    }

    // ---------------- Register ----------------
    public function register(array $data): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['status' => false, 'message' => 'Invalid request'];
        }

        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return ['status' => false, 'message' => 'All fields are required'];
        }

        // جلوگیری از ثبت ایمیل تکراری
        $check = $this->pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute(['email' => $data['email']]);

        if ($check->fetch()) {
            return ['status' => false, 'message' => 'Email already exists'];
        }

        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        return [
            'status' => true,
            'user_id' => $this->pdo->lastInsertId()
        ];
    }

    // ---------------- Login ----------------
    public function login(array $data, bool $useJWT = true): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['status' => false, 'message' => 'Invalid request'];
        }

        if (empty($data['email']) || empty($data['password'])) {
            return ['status' => false, 'message' => 'Email and password required'];
        }

        $sql = "SELECT id, username, password FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            return ['status' => false, 'message' => 'Invalid credentials'];
        }

        if ($useJWT) {
            // ---------------- JWT ----------------
            $payload = [
                'iss' => "yourdomain.com",
                'iat' => time(),
                'exp' => time() + $this->jwt_exp,
                'user_id' => $user['id'],
                'username' => $user['username']
            ];

            $token = JWT::encode($payload, $this->jwt_secret, 'HS256');

            return [
                'status' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $data['email']
                ]
            ];
        } else {
            // ---------------- Session ----------------
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $data['email']
            ];

            return [
                'status' => true,
                'message' => 'Logged in via session',
                'user' => $_SESSION['user']
            ];
        }
    }

    // ---------------- JWT Verification ----------------
    public function verifyJWT(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwt_secret, 'HS256'));
            return ['status' => true, 'data' => (array)$decoded];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Invalid or expired token'];
        }
    }

    // ---------------- Logout Session ----------------
    public function logout()
    {
        session_destroy();
        return ['status' => true, 'message' => 'Logged out'];
    }
}
