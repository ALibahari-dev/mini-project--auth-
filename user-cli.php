<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/User.php';

$user = new User($pdo);

/**
 * چاپ راهنما
 */
function help() {
    echo "Usage: php user-cli.php [command] [options]\n";
    echo "Commands:\n";
    echo "  register username email password           Register new user\n";
    echo "  login email password                       Login user and get JWT\n";
    echo "  reset-password email newpassword           Reset user password\n";
    echo "  activate email                             Activate user account\n";
    echo "  deactivate email                           Deactivate user account\n";
    echo "  list                                       List all users\n";
    echo "  help                                       Show this help\n";
    exit;
}

$args = $argv;
array_shift($args); // remove script name
$command = $args[0] ?? null;

if (!$command) help();

switch ($command) {

    case 'register':
        $username = $args[1] ?? null;
        $email    = $args[2] ?? null;
        $password = $args[3] ?? null;
        if (!$username || !$email || !$password) {
            echo "Usage: php user-cli.php register username email password\n";
            exit;
        }
        $result = $user->register([
            'username'=>$username,
            'email'=>$email,
            'password'=>$password
        ]);
        echo $result['status'] ? "✅ کاربر با موفقیت ثبت شد. ID: {$result['user_id']}\n" 
                               : "❌ خطا: {$result['message']}\n";
        break;

    case 'login':
        $email    = $args[1] ?? null;
        $password = $args[2] ?? null;
        if (!$email || !$password) {
            echo "Usage: php user-cli.php login email password\n";
            exit;
        }
        $result = $user->login([
            'email'=>$email,
            'password'=>$password
        ]);
        if ($result['status']) {
            echo "✅ ورود موفق\n";
            echo "Access Token: {$result['access_token']}\n";
            echo "Refresh Token: {$result['refresh_token']}\n";
        } else {
            echo "❌ خطا: {$result['message']}\n";
        }
        break;

    case 'reset-password':
        $email       = $args[1] ?? null;
        $newPassword = $args[2] ?? null;
        if (!$email || !$newPassword) {
            echo "Usage: php user-cli.php reset-password email newpassword\n";
            exit;
        }
        $stmt = $pdo->prepare("UPDATE users SET password=:password WHERE email=:email");
        $stmt->execute([
            'password'=>password_hash($newPassword,PASSWORD_BCRYPT),
            'email'=>$email
        ]);
        echo "✅ پسورد کاربر {$email} با موفقیت تغییر کرد\n";
        break;

    case 'activate':
        $email = $args[1] ?? null;
        if (!$email) {
            echo "Usage: php user-cli.php activate email\n";
            exit;
        }
        $pdo->prepare("UPDATE users SET status='active' WHERE email=:email")
            ->execute(['email'=>$email]);
        echo "✅ حساب کاربری {$email} فعال شد\n";
        break;

    case 'deactivate':
        $email = $args[1] ?? null;
        if (!$email) {
            echo "Usage: php user-cli.php deactivate email\n";
            exit;
        }
        $pdo->prepare("UPDATE users SET status='inactive' WHERE email=:email")
            ->execute(['email'=>$email]);
        echo "✅ حساب کاربری {$email} غیرفعال شد\n";
        break;

    case 'list':
        $stmt = $pdo->query("SELECT ID, username, email, role, status, last_login FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            echo "{$u['ID']} | {$u['username']} | {$u['email']} | {$u['role']} | {$u['status']} | Last login: {$u['last_login']}\n";
        }
        break;

    case 'help':
    default:
        help();
        break;
}
