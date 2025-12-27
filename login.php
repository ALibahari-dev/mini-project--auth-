<?php
session_start();

// اگر لاگین شده، مستقیم بره داشبورد
if (isset($_SESSION['access_token'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
    </style>
</head>
<body>

<div class="box">
    <h3>ورود</h3>

    <form id="loginForm">
        <input type="email" id="email" placeholder="ایمیل" required>
        <input type="password" id="password" placeholder="رمز عبور" required>
        <button type="submit">ورود</button>
    </form>

    <div id="message" class="error"></div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const email    = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const message  = document.getElementById('message');

    message.textContent = '';

    const res = await fetch('http://localhost:8000/api-login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email, password })
    });

    const data = await res.json();

    if (!data.status) {
        message.textContent = data.message || 'خطا در ورود';
        return;
    }

    // ارسال توکن‌ها به PHP برای ذخیره در session
    const save = await fetch('save-session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    if (save.ok) {
        window.location.href = 'dashboard.php';
    }
});
</script>

</body>
</html>
