<?php
// manager-dashboard.php
require_once 'includes/auth.php';
requireRole('manager');

// Get counts for dashboard
global $pdo;

$customerCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$supplierCount = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$orderCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE MONTH(order_date) = MONTH(CURRENT_DATE())")->fetchColumn();

// Get recent orders
$orders = $pdo->query("
    SELECT o.*, c.name as customer_name 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.customer_id 
    ORDER BY o.order_date DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👑 Manager Dashboard - Pearl Land</title>
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

        /* Sidebar */
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
            transition: all 0.3s;
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

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-bottom: 4px solid #8B4513;
        }

        .stat-card h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #8B4513;
        }

        /* Quick Access */
        .quick-access {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .quick-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .quick-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .quick-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .quick-card h3 {
            color: #8B4513;
            margin-bottom: 10px;
        }

        .quick-card a {
            display: inline-block;
            padding: 5px 15px;
            background: #8B4513;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        /* Recent Orders Table */
        .recent-orders {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .recent-orders h2 {
            color: #333;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            background: #8B4513;
            color: white;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status.pending { background: #f39c12; color: white; }
        .status.processing { background: #3498db; color: white; }
        .status.delivered { background: #27ae60; color: white; }
        .status.cancelled { background: #e74c3c; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>🌶️ Pearl Land</h2>
            <div class="sidebar-menu">
                <a href="manager-dashboard.php" class="active">🏠 Dashboard</a>
                <a href="customers/index.php">👥 Customers</a>
                <a href="suppliers/index.php">🚚 Suppliers</a>
                <a href="products/index.php">📦 Products</a>
                <a href="orders/index.php">📝 Orders</a>
                <a href="reports/index.php">📊 Reports</a>
            </div>
            <div class="user-info">
                <div>👑 <?php echo $_SESSION['full_name']; ?></div>
                <div style="font-size: 12px;">Manager</div>
                <a href="logout.php" style="color: white; text-decoration: none; display: block; margin-top: 10px;">🚪 Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Manager Dashboard</h1>
                <div class="role-badge">👑 Manager Access</div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Customers</h3>
                    <div class="stat-number"><?php echo $customerCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Suppliers</h3>
                    <div class="stat-number"><?php echo $supplierCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Products</h3>
                    <div class="stat-number"><?php echo $productCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Orders (MTD)</h3>
                    <div class="stat-number"><?php echo $orderCount; ?></div>
                </div>
            </div>

            <div class="quick-access">
                <div class="quick-card">
                    <div class="quick-icon">👥</div>
                    <h3>Customers</h3>
                    <a href="customers/index.php">Manage →</a>
                </div>
                <div class="quick-card">
                    <div class="quick-icon">🚚</div>
                    <h3>Suppliers</h3>
                    <a href="suppliers/index.php">Manage →</a>
                </div>
                <div class="quick-card">
                    <div class="quick-icon">📦</div>
                    <h3>Products</h3>
                    <a href="products/index.php">Manage →</a>
                </div>
                <div class="quick-card">
                    <div class="quick-icon">📝</div>
                    <h3>Orders</h3>
                    <a href="orders/index.php">Manage →</a>
                </div>
            </div>

            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_code']; ?></td>
                            <td><?php echo $order['customer_name'] ?? 'N/A'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                            <td>Rs. <?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                            <td><span class="status <?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>