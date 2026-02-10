<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        if(session_status() === PHP_SESSION_NONE) session_start();
    }

    // ثبت نام کاربر
    public function register(array $data): array {
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$username || !$email || !$password) {
            return ['status'=>false,'message'=>'تمام فیلدها الزامی است'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status'=>false,'message'=>'ایمیل نامعتبر است'];
        }

        if (strlen($password) < 8) {
            return ['status'=>false,'message'=>'پسورد حداقل ۸ کاراکتر باشد'];
        }

        // چک ایمیل و یوزرنیم
        $check = $this->pdo->prepare("SELECT id FROM users WHERE email=:email OR username=:username LIMIT 1");
        $check->execute(['email'=>$email, 'username'=>$username]);
        if ($check->fetch()) return ['status'=>false,'message'=>'ایمیل یا یوزرنیم قبلاً ثبت شده'];

        $stmt = $this->pdo->prepare("INSERT INTO users (username,email,password) VALUES (:username,:email,:password)");
        $stmt->execute([
            'username'=>$username,
            'email'=>$email,
            'password'=>password_hash($password, PASSWORD_BCRYPT)
        ]);

        return ['status'=>true,'user_id'=>$this->pdo->lastInsertId()];
    }

    // لاگین کاربر
    public function login(array $data): array {
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$password) return ['status'=>false,'message'=>'ایمیل و پسورد الزامی است'];

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email=:email LIMIT 1");
        $stmt->execute(['email'=>$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return ['status'=>false,'message'=>'ایمیل یا پسورد اشتباه است'];

        if ($user['status'] !== 'active') {
            return ['status'=>false,'message'=>'حساب کاربری شما فعال نیست یا مسدود شده است'];
        }

        if (!password_verify($password, $user['password'])) {
            // افزایش تعداد تلاش ناموفق
            $this->pdo->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE ID = ?")
                      ->execute([$user['ID']]);
            return ['status'=>false,'message'=>'ایمیل یا پسورد اشتباه است'];
        }

        // موفقیت آمیز: ریست login_attempts و ثبت last_login
        $this->pdo->prepare("UPDATE users SET login_attempts=0, last_login=NOW() WHERE ID=?")
                  ->execute([$user['ID']]);

        // ایجاد JWT Access Token
        $payload = [
            'iss'=>'yourdomain.com',
            'iat'=>time(),
            'exp'=>time()+JWT_EXPIRE,
            'user_id'=>$user['ID'],
            'role'=>$user['role']
        ];
        $accessToken = JWT::encode($payload, JWT_SECRET, 'HS256');

        // ایجاد Refresh Token
        $refreshToken = bin2hex(random_bytes(64));
        $refreshExpires = date('Y-m-d H:i:s', time()+JWT_REFRESH_EXPIRE);

        // ذخیره refresh token در دیتابیس
        $this->pdo->prepare("UPDATE users SET refresh_token=:token, refresh_token_expires=:exp WHERE ID=:id")
                  ->execute([
                      'token'=>$refreshToken,
                      'exp'=>$refreshExpires,
                      'id'=>$user['ID']
                  ]);

        return [
            'status'=>true,
            'access_token'=>$accessToken,
            'refresh_token'=>$refreshToken,
            'user'=>[
                'id'=>$user['ID'],
                'username'=>$user['username'],
                'email'=>$user['email'],
                'role'=>$user['role']
            ]
        ];
    }

    // تایید JWT
    public function verifyJWT(string $token): array {
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET,'HS256'));
            return ['status'=>true,'data'=>(array)$decoded];
        } catch (\Exception $e) {
            return ['status'=>false,'message'=>'توکن نامعتبر یا منقضی شده است'];
        }
    }

    // استفاده از Refresh Token برای دریافت Access Token جدید
    public function refreshToken(string $refreshToken): array {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE refresh_token=:token LIMIT 1");
        $stmt->execute(['token'=>$refreshToken]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return ['status'=>false,'message'=>'Refresh Token نامعتبر است'];
        if (strtotime($user['refresh_token_expires']) < time()) {
            return ['status'=>false,'message'=>'Refresh Token منقضی شده است'];
        }

        // ایجاد Access Token جدید
        $payload = [
            'iss'=>'yourdomain.com',
            'iat'=>time(),
            'exp'=>time()+JWT_EXPIRE,
            'user_id'=>$user['ID'],
            'role'=>$user['role']
        ];
        $accessToken = JWT::encode($payload, JWT_SECRET, 'HS256');

        return [
            'status'=>true,
            'access_token'=>$accessToken
        ];
    }
}
