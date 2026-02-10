<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// اگر قبلاً لاگین شده، مستقیم بره داشبورد
if (isset($_SESSION['access_token'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ثبت نام</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f5f5f5;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }
        .box {
            background: white;
            padding: 24px;
            width: 320px;
            border-radius: 8px;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
        .success {
            color: green;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="box">
    <h3>ثبت نام</h3>

    <form id="registerForm">
        <input type="text" id="username" placeholder="نام کاربری (اختیاری)">
        <input type="email" id="email" placeholder="ایمیل" required>
        <input type="password" id="password" placeholder="رمز عبور" required>
        <button type="submit">ثبت نام</button>
    </form>

    <div id="message" class="error"></div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const username = document.getElementById('username').value;
    const email    = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const message  = document.getElementById('message');

    message.textContent = '';
    message.className = 'error';

    const res = await fetch('http://localhost:8000/api-register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, email, password })
    });

    const data = await res.json();

    if (!data.status) {
        message.textContent = data.message || 'خطا در ثبت نام';
        return;
    }

    // ارسال توکن‌ها به PHP برای ذخیره در session
    const save = await fetch('save-session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    if (save.ok) {
        message.textContent = 'ثبت نام موفق! در حال هدایت به داشبورد...';
        message.className = 'success';
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 1000);
    }
});
</script>

</body>
</html>
