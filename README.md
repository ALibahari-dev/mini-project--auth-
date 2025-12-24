<?php
/**
 * User Authentication API - JWT & Session
 *
 * این سند شامل کلاس User و تمام متدهای Register, Login, JWT verification و Session logout می‌باشد.
 * با این کلاس می‌توانید یک API امن برای ثبت‌نام، لاگین، و مدیریت کاربر بسازید.
 *
 * پیش‌نیاز:
 * - PHP 8+
 * - Composer: firebase/php-jwt
 * - PDO اتصال به دیتابیس MySQL
 *
 * قابلیت‌ها:
 * - register(array \$data) -> ثبت‌نام امن با hash کردن پسورد
 * - login(array \$data, bool \$useJWT=true) -> لاگین با JWT یا Session
 * - verifyJWT(string \$token) -> اعتبارسنجی توکن JWT
 * - logout() -> پایان session
 *
 * خروجی‌ها بصورت آرایه هستند و قابل encode کردن به JSON برای API Headless
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User
{
    private PDO $pdo;
    private string $jwt_secret = 'YOUR_SECRET_KEY_123';
    private int $jwt_exp = 3600;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }
    }

    /**
     * Register user
     * @param array $data ['username'=>..., 'email'=>..., 'password'=>...]
     * @return array ['status'=>bool, 'message'=>string, 'user_id'=>int]
     */
    public function register(array $data): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['status' => false, 'message' => 'Invalid request'];
        }

        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return ['status' => false, 'message' => 'All fields are required'];
        }

        // Check duplicate email
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

    /**
     * Login user
     * @param array $data ['email'=>..., 'password'=>...]
     * @param bool $useJWT - true for JWT, false for Session
     * @return array
     */
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
            $payload = [
                'iss' => 'yourdomain.com',
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

    /**
     * Verify JWT token
     * @param string $token
     * @return array
     */
    public function verifyJWT(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwt_secret, 'HS256'));
            return ['status' => true, 'data' => (array)$decoded];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Invalid or expired token'];
        }
    }

    /**
     * Logout user (Session)
     * @return array
     */
    public function logout(): array
    {
        session_destroy();
        return ['status' => true, 'message' => 'Logged out'];
    }
}
