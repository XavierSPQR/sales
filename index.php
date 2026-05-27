<?php
// index.php - Login Page
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'manager') {
        header('Location: manager-dashboard.php');
    } elseif ($_SESSION['role'] === 'stock_clerk') {
        header('Location: stock-dashboard.php');
    } elseif ($_SESSION['role'] === 'account_clerk') {
        header('Location: account-dashboard.php');
    }
    exit();
}

require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple password check (in real system, use password_verify)
    // For demo, we're using plain text check
    if ($username === 'manager' && $password === 'manager123') {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'manager';
        $_SESSION['role'] = 'manager';
        $_SESSION['full_name'] = 'Admin Manager';
        header('Location: manager-dashboard.php');
        exit();
    } elseif ($username === 'clerk' && $password === 'clerk123') {
        $_SESSION['user_id'] = 2;
        $_SESSION['username'] = 'clerk';
        $_SESSION['role'] = 'stock_clerk';
        $_SESSION['full_name'] = 'Stock Clerk';
        header('Location: stock-dashboard.php');
        exit();
    } elseif ($username === 'account' && $password === 'account123') {
        $_SESSION['user_id'] = 3;
        $_SESSION['username'] = 'account';
        $_SESSION['role'] = 'account_clerk';
        $_SESSION['full_name'] = 'Account Clerk';
        header('Location: account-dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌶️ Pearl Land Commodities - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #8B4513, #D2691E);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "🌶️🌶️🌶️";
            font-size: 200px;
            position: absolute;
            bottom: -50px;
            left: -50px;
            opacity: 0.1;
            transform: rotate(-10deg);
        }

        body::after {
            content: "🌿🌿🌿";
            font-size: 180px;
            position: absolute;
            top: -50px;
            right: -50px;
            opacity: 0.1;
            transform: rotate(15deg);
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            z-index: 10;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand h1 {
            color: #8B4513;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .brand p {
            color: #D2691E;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4A3729;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #E6D5B8;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #D2691E;
            box-shadow: 0 0 0 3px rgba(210,105,30,0.1);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #8B4513, #D2691E);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139,69,19,0.4);
        }

        .error {
            background: #FFE4E1;
            color: #CD5C5C;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        .demo-info {
            margin-top: 20px;
            padding: 15px;
            background: #FFF5E6;
            border-radius: 8px;
            font-size: 14px;
        }

        .demo-info p {
            color: #8B4513;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .demo-info ul {
            list-style: none;
        }

        .demo-info li {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px;
            background: white;
            border-radius: 5px;
        }

        .role {
            font-weight: 600;
            color: #D2691E;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #8B4513;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand">
            <h1>🌶️ Pearl Land Commodities</h1>
            <p>Premium Spice Manufacturer</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">🔐 Login</button>
        </form>

        <div class="demo-info">
            <p>📋 Demo Credentials</p>
            <ul>
                <li><span class="role">👑 Manager:</span> manager / manager123</li>
                <li><span class="role">📦 Stock Clerk:</span> clerk / clerk123</li>
                <li><span class="role">💰 Account Clerk:</span> account / account123</li>
            </ul>
        </div>

        <div class="footer">
            © 2026 Pearl Land Commodities. All rights reserved.
        </div>
    </div>
</body>
</html>