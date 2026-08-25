<?php
session_start();
$_SESSION['user_key'] = 'local_user';
header("Location: plataforma.php");
exit;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@WuTang CENTRAL | LOGIN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0a0a0c;
            --accent-primary: #ff0033;
            --accent-secondary: #cc0000;
            --text-main: #e8e8e8;
            --text-dim: #8a8a9a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, #050508 0%, #0a0a0f 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        .bg-animation {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        .bg-animation::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 40%, rgba(255,0,51,0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(204,0,0,0.06) 0%, transparent 50%);
            animation: floatBg 20s ease-in-out infinite;
        }
        @keyframes floatBg {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-5%, -5%); }
        }

        .grid-overlay {
            position: fixed;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(255,0,51,0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,0,51,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 1;
        }

        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }

        .login-card {
            background: rgba(15, 15, 20, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            border: 1px solid rgba(255, 0, 51, 0.2);
            padding: 48px 40px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-primary), transparent);
            animation: scan 3s linear infinite;
        }

        @keyframes scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(255,0,51,0.15), rgba(204,0,0,0.05));
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            border: 1px solid rgba(255,0,51,0.3);
        }

        .logo-icon i {
            font-size: 42px;
            color: var(--accent-primary);
            filter: drop-shadow(0 0 15px rgba(255,0,51,0.5));
        }

        .logo-area h1 {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
            letter-spacing: 2px;
        }

        .logo-area p {
            text-align: center;
            color: var(--text-dim);
            font-size: 12px;
            margin-top: 8px;
            letter-spacing: 1px;
        }

        .input-group {
            position: relative;
            margin-bottom: 28px;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 18px;
            z-index: 2;
        }

        .input-group input {
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 16px 20px 16px 52px;
            font-size: 14px;
            font-family: 'JetBrains Mono', monospace;
            color: #fff;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 20px rgba(255, 0, 51, 0.2);
        }

        .input-group input::placeholder {
            color: rgba(255,255,255,0.3);
            font-family: 'Inter', sans-serif;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, rgba(255,0,51,0.1), rgba(204,0,0,0.05));
            border: 1.5px solid var(--accent-primary);
            border-radius: 16px;
            padding: 16px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--accent-primary);
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            letter-spacing: 2px;
        }

        .btn-login:hover {
            background: var(--accent-primary);
            color: #000;
            box-shadow: 0 0 30px rgba(255, 0, 51, 0.4);
        }

        .error-msg {
            background: rgba(255, 77, 109, 0.1);
            border: 1px solid #ff4d6d;
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 24px;
            color: #ff4d6d;
            text-align: center;
            font-size: 13px;
        }

        .footer {
            text-align: center;
            margin-top: 32px;
            font-size: 11px;
            color: var(--text-dim);
        }

        .stats-badge {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .stats-badge span {
            font-size: 11px;
            color: var(--text-dim);
        }

        .stats-badge i {
            color: var(--accent-primary);
            margin-right: 5px;
        }
    </style>
</head>
<body>
<div class="bg-animation"></div>
<div class="grid-overlay"></div>

<div class="login-container">
    <div class="login-card">
        <div class="logo-icon">
            <i class="fas fa-dragon"></i>
        </div>
        <div class="logo-area">
            <h1>WU-TANG</h1>
            <p>SECURE ACCESS v3.0</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="text" name="key" placeholder="@WuTang-XXXX-XXXX-XXXX" autocomplete="off" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-right"></i> ACESSAR SISTEMA
            </button>
        </form>

        <div class="stats-badge">
            <span><i class="fas fa-shield-alt"></i> ENCRYPTED</span>
            <span><i class="fas fa-bolt"></i> HIGH SPEED</span>
            <span><i class="fas fa-chart-line"></i> REAL TIME</span>
        </div>

        <div class="footer">
            WU-TANG • v3.0 // ALL RIGHTS RESERVED
        </div>
    </div>
</div>
</body>
</html>