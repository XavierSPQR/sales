<?php
// account-dashboard.php
require_once 'includes/auth.php';
requireRole('account_clerk');

global $pdo;

$totalOutstanding = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'pending'
")->fetchColumn();

$pendingCount = $pdo->query("
    SELECT COUNT(*) FROM payments WHERE status = 'pending'
")->fetchColumn();

$recentPayments = $pdo->query("
    SELECT p.*, o.order_code 
    FROM payments p 
    LEFT JOIN orders o ON p.order_id = o.order_id 
    ORDER BY p.payment_date DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💰 Account Dashboard - Pearl Land</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f5f5;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: #8B4513;
            color: white;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #D2691E;
        }

        .sidebar-menu {
            margin-top: 30px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #D2691E;
        }

        .user-info {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
        }

        .role-badge {
            background: #8B4513;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #8B4513;
        }

        .payments-table {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #8B4513;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .status-paid {
            background: #27ae60;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .status-pending {
            background: #f39c12;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>🌶️ Pearl Land</h2>
            <div class="sidebar-menu">
                <a href="account-dashboard.php" class="active">🏠 Dashboard</a>
                <a href="customers/index.php">👥 Customers</a>
                <a href="reports/index.php">📊 Reports</a>
            </div>
            <div class="user-info">
                <div>💰 <?php echo $_SESSION['full_name']; ?></div>
                <div>Account Clerk</div>
                <a href="logout.php" style="color: white; text-decoration: none; display: block; margin-top: 10px;">🚪 Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Account Clerk Dashboard</h1>
                <div class="role-badge">💰 Finance Access</div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Outstanding</h3>
                    <div class="stat-number">Rs. <?php echo number_format($totalOutstanding, 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending Payments</h3>
                    <div class="stat-number"><?php echo $pendingCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>This Month</h3>
                    <div class="stat-number">Rs. 0</div>
                </div>
            </div>

            <div class="payments-table">
                <h2 style="margin-bottom: 20px;">📋 Recent Payments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPayments as $payment): ?>
                        <tr>
                            <td>#PAY-<?php echo str_pad($payment['payment_id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo $payment['order_code'] ?? 'N/A'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                            <td>Rs. <?php echo number_format($payment['amount'] ?? 0, 2); ?></td>
                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                            <td>
                                <span class="status-<?php echo $payment['status']; ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>