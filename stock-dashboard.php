<?php
// stock-dashboard.php
require_once 'includes/auth.php';
requireRole('stock_clerk');

global $pdo;

$productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE current_stock <= reorder_level")->fetchColumn();

// Get low stock products
$lowStock = $pdo->query("SELECT * FROM products WHERE current_stock <= reorder_level ORDER BY current_stock ASC LIMIT 5")->fetchAll();

// Get all products
$products = $pdo->query("SELECT * FROM products ORDER BY current_stock ASC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📦 Stock Dashboard - Pearl Land</title>
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

        .alert-box {
            background: #fff3cd;
            border-left: 4px solid #e67e22;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ffeeba;
        }

        .stock-table {
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

        .stock-low {
            color: #e67e22;
            font-weight: bold;
        }

        .stock-critical {
            color: #e74c3c;
            font-weight: bold;
        }

        .btn-reorder {
            background: #8B4513;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>🌶️ Pearl Land</h2>
            <div class="sidebar-menu">
                <a href="stock-dashboard.php" class="active">🏠 Dashboard</a>
                <a href="products/index.php">📦 Products</a>
                <a href="suppliers/index.php">🚚 Suppliers</a>
                <a href="orders/index.php">📝 Orders</a>
            </div>
            <div class="user-info">
                <div>📦 <?php echo $_SESSION['full_name']; ?></div>
                <div>Stock Clerk</div>
                <a href="logout.php" style="color: white; text-decoration: none; display: block; margin-top: 10px;">🚪 Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Stock Clerk Dashboard</h1>
                <div class="role-badge">📦 Stock Management</div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="stat-number"><?php echo $productCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Low Stock Items</h3>
                    <div class="stat-number"><?php echo $lowStockCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Categories</h3>
                    <div class="stat-number">5</div>
                </div>
            </div>

            <?php if ($lowStockCount > 0): ?>
            <div class="alert-box">
                <h3 style="color: #e67e22; margin-bottom: 10px;">⚠️ Low Stock Alerts</h3>
                <?php foreach ($lowStock as $item): ?>
                <div class="alert-item">
                    <span><strong><?php echo $item['name']; ?></strong> - <?php echo $item['current_stock']; ?> kg remaining</span>
                    <span class="stock-<?php echo $item['current_stock'] == 0 ? 'critical' : 'low'; ?>">
                        <?php echo $item['current_stock'] == 0 ? 'Out of Stock' : 'Reorder now'; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="stock-table">
                <h2 style="margin-bottom: 20px;">📊 Current Stock Levels</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['product_code']; ?></td>
                            <td><?php echo $product['name']; ?></td>
                            <td class="<?php 
                                echo $product['current_stock'] <= $product['reorder_level'] ? 'stock-low' : ''; 
                            ?>"><?php echo $product['current_stock']; ?> kg</td>
                            <td><?php echo $product['reorder_level']; ?> kg</td>
                            <td>
                                <?php if ($product['current_stock'] == 0): ?>
                                    <span class="stock-critical">Out of Stock</span>
                                <?php elseif ($product['current_stock'] <= $product['reorder_level']): ?>
                                    <span class="stock-low">Low Stock</span>
                                <?php else: ?>
                                    <span style="color: #27ae60;">Good</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['current_stock'] <= $product['reorder_level']): ?>
                                    <button class="btn-reorder" onclick="alert('Reorder placed for <?php echo $product['name']; ?>')">
                                        Reorder
                                    </button>
                                <?php endif; ?>
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