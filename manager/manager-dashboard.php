<?php
// manager-dashboard.php
require_once __DIR__ . '/../includes/auth.php';
requireRole('manager');

$pdo = Database::getInstance()->getConnection();

// ===================== GET COUNTS FOR DASHBOARD =====================
$customerCount = 0;
$supplierCount = 0;
$pendingSupplierCount = 0;
$productCount = 0;
$orderCount = 0;

try { $customerCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(); } catch (Exception $e) {}
try { $supplierCount = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status = 'active'")->fetchColumn(); } catch (Exception $e) {}
try { $pendingSupplierCount = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status = 'pending'")->fetchColumn(); } catch (Exception $e) {}
try { $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); } catch (Exception $e) {}

try {
    if (Database::getInstance()->getDriver() === 'sqlite') {
        $orderCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE strftime('%m', order_date) = strftime('%m', 'now')")->fetchColumn();
    } else {
        $orderCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE MONTH(order_date) = MONTH(CURRENT_DATE())")->fetchColumn();
    }
} catch (Exception $e) {
    $orderCount = 0;
}

// ===================== GET PENDING SUPPLIER REGISTRATIONS =====================
try {
    $pendingSuppliers = $pdo->query("
        SELECT s.*,
               s.created_at as registered_date,
               s.name as company_name,
               s.contact as contact_person,
               s.email,
               s.phone,
               s.materials
        FROM suppliers s
        WHERE s.status = 'pending'
        ORDER BY s.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {
    $pendingSuppliers = [];
}

// ===================== GET ACTIVE SUPPLIERS =====================
try {
    $activeSuppliers = $pdo->query("
        SELECT s.*,
               s.created_at as registered_date,
               s.updated_at as approved_date,
               s.name as company_name,
               s.contact as contact_person,
               s.email,
               s.phone,
               s.materials
        FROM suppliers s
        WHERE s.status = 'active'
        ORDER BY s.name ASC
    ")->fetchAll();
} catch (Exception $e) {
    $activeSuppliers = [];
}

// ===================== GET RECENT ORDERS =====================
try {
    $orders = $pdo->query("
        SELECT o.*, c.name as customer_name
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        ORDER BY o.order_date DESC
        LIMIT 5
    ")->fetchAll();
} catch (Exception $e) {
    $orders = [];
}

// ===================== GET EMAIL LOG =====================
try {
    $emailLog = $pdo->query("
        SELECT * FROM email_log
        ORDER BY sent_at DESC
        LIMIT 50
    ")->fetchAll();
} catch (Exception $e) {
    $emailLog = [];
}

// ===================== HANDLE APPROVE/REJECT ACTIONS =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $supplierId = $_POST['supplier_id'] ?? 0;
    $action = $_POST['action'];
    $reason = $_POST['reason'] ?? '';

    if ($action === 'approve') {
        $stmt = $pdo->prepare("
            UPDATE suppliers 
            SET status = 'active', 
                updated_at = NOW(),
                approved_at = NOW(),
                approved_by = ? 
            WHERE supplier_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $supplierId]);

        $supplier = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
        $supplier->execute([$supplierId]);
        $supplierData = $supplier->fetch();

        $subject = "✅ Supplier Registration Approved - Pearl Land Commodities";
        $body = "Dear " . ($supplierData['contact_person'] ?? 'Supplier') . ",\n\n";
        $body .= "We are pleased to inform you that your supplier registration with Pearl Land Commodities has been APPROVED! 🎉\n\n";
        $body .= "Company: " . ($supplierData['company_name'] ?? 'N/A') . "\n";
        $body .= "Username: " . ($supplierData['username'] ?? 'N/A') . "\n";
        $body .= "Email: " . ($supplierData['email'] ?? 'N/A') . "\n\n";
        $body .= "You can now log in to your supplier account.\n\n";
        $body .= "Best regards,\nPearl Land Commodities Team";

        try {
            $emailStmt = $pdo->prepare("
                INSERT INTO email_log (recipient, subject, body, type, sent_at)
                VALUES (?, ?, ?, 'supplier_approval', NOW())
            ");
            $emailStmt->execute([$supplierData['email'], $subject, $body]);
        } catch (Exception $e) {}

        $_SESSION['success'] = "✅ Supplier approved successfully! Email notification sent.";

    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("
            UPDATE suppliers 
            SET status = 'rejected', 
                updated_at = NOW(),
                rejection_reason = ? 
            WHERE supplier_id = ?
        ");
        $stmt->execute([$reason, $supplierId]);

        $supplier = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
        $supplier->execute([$supplierId]);
        $supplierData = $supplier->fetch();

        $subject = "❌ Supplier Registration Update - Pearl Land Commodities";
        $body = "Dear " . ($supplierData['contact_person'] ?? 'Supplier') . ",\n\n";
        $body .= "Thank you for your interest in becoming a supplier.\n\n";
        $body .= "We regret to inform you that your registration has been REJECTED.\n\n";
        $body .= "Reason: " . ($reason ?: "Your application did not meet our current requirements.") . "\n\n";
        $body .= "Best regards,\nPearl Land Commodities Team";

        try {
            $emailStmt = $pdo->prepare("
                INSERT INTO email_log (recipient, subject, body, type, sent_at)
                VALUES (?, ?, ?, 'supplier_rejection', NOW())
            ");
            $emailStmt->execute([$supplierData['email'], $subject, $body]);
        } catch (Exception $e) {}

        $_SESSION['success'] = "❌ Supplier rejected. Email notification sent.";
    }

    header("Location: manager-dashboard.php");
    exit;
}

// ===================== AJAX: GET REPORT DATA =====================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_report_data') {
    header('Content-Type: application/json');
    
    $reportType = $_GET['type'] ?? 'sales';
    $fromDate = $_GET['from'] ?? date('Y-m-01');
    $toDate = $_GET['to'] ?? date('Y-m-d');
    
    $response = ['success' => true, 'data' => []];
    
    try {
        switch ($reportType) {
            case 'sales':
                // Sales Report
                $salesData = $pdo->prepare("
                    SELECT 
                        o.order_code,
                        o.order_date,
                        c.name as customer_name,
                        o.total_amount,
                        o.status,
                        o.payment_method
                    FROM orders o
                    LEFT JOIN customers c ON o.customer_id = c.customer_id
                    WHERE o.order_date BETWEEN ? AND ?
                    ORDER BY o.order_date DESC
                ");
                $salesData->execute([$fromDate, $toDate . ' 23:59:59']);
                $ordersList = $salesData->fetchAll();
                
                $totalRevenue = $pdo->prepare("
                    SELECT COALESCE(SUM(total_amount), 0) as total 
                    FROM orders 
                    WHERE order_date BETWEEN ? AND ?
                ");
                $totalRevenue->execute([$fromDate, $toDate . ' 23:59:59']);
                $revenue = $totalRevenue->fetchColumn();
                
                $pendingOrders = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM orders 
                    WHERE order_date BETWEEN ? AND ? AND status = 'pending'
                ");
                $pendingOrders->execute([$fromDate, $toDate . ' 23:59:59']);
                $pending = $pendingOrders->fetchColumn();
                
                $deliveredOrders = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM orders 
                    WHERE order_date BETWEEN ? AND ? AND status = 'delivered'
                ");
                $deliveredOrders->execute([$fromDate, $toDate . ' 23:59:59']);
                $delivered = $deliveredOrders->fetchColumn();
                
                $response['data'] = [
                    'orders' => $ordersList,
                    'summary' => [
                        'total_orders' => count($ordersList),
                        'total_revenue' => $revenue,
                        'pending_orders' => $pending,
                        'delivered_orders' => $delivered
                    ]
                ];
                break;
                
            case 'supplier':
                // Supplier Report
                $supplierData = $pdo->prepare("
                    SELECT 
                        s.supplier_code,
                        s.name as company_name,
                        s.contact as contact_person,
                        s.email,
                        s.phone,
                        s.materials,
                        s.status,
                        s.created_at as registered_date,
                        s.approved_at,
                        (SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = s.supplier_id) as order_count,
                        (SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders WHERE supplier_id = s.supplier_id) as total_cost
                    FROM suppliers s
                    WHERE s.created_at BETWEEN ? AND ?
                    ORDER BY s.created_at DESC
                ");
                $supplierData->execute([$fromDate, $toDate . ' 23:59:59']);
                $suppliers = $supplierData->fetchAll();
                
                $activeCount = $pdo->prepare("
                    SELECT COUNT(*) FROM suppliers 
                    WHERE created_at BETWEEN ? AND ? AND status = 'active'
                ");
                $activeCount->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $pendingCount = $pdo->prepare("
                    SELECT COUNT(*) FROM suppliers 
                    WHERE created_at BETWEEN ? AND ? AND status = 'pending'
                ");
                $pendingCount->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $response['data'] = [
                    'suppliers' => $suppliers,
                    'summary' => [
                        'total' => count($suppliers),
                        'active' => $activeCount->fetchColumn(),
                        'pending' => $pendingCount->fetchColumn()
                    ]
                ];
                break;
                
            case 'payment':
                // Payment Report
                $paymentData = $pdo->prepare("
                    SELECT 
                        po.po_code,
                        po.supplier_id,
                        s.name as supplier_name,
                        po.total_amount,
                        po.payment_terms,
                        po.payment_status,
                        po.created_at,
                        po.delivery_date
                    FROM purchase_orders po
                    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
                    WHERE po.created_at BETWEEN ? AND ?
                    ORDER BY po.created_at DESC
                ");
                $paymentData->execute([$fromDate, $toDate . ' 23:59:59']);
                $payments = $paymentData->fetchAll();
                
                $paidTotal = $pdo->prepare("
                    SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders 
                    WHERE created_at BETWEEN ? AND ? AND payment_status = 'Paid'
                ");
                $paidTotal->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $pendingTotal = $pdo->prepare("
                    SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders 
                    WHERE created_at BETWEEN ? AND ? AND payment_status = 'Pending'
                ");
                $pendingTotal->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $overdueTotal = $pdo->prepare("
                    SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders 
                    WHERE created_at BETWEEN ? AND ? AND payment_status = 'Overdue'
                ");
                $overdueTotal->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $response['data'] = [
                    'payments' => $payments,
                    'summary' => [
                        'paid' => $paidTotal->fetchColumn(),
                        'pending' => $pendingTotal->fetchColumn(),
                        'overdue' => $overdueTotal->fetchColumn()
                    ]
                ];
                break;
                
            case 'summary':
                // Summary Report
                $totalOrders = $pdo->prepare("
                    SELECT COUNT(*) FROM orders WHERE order_date BETWEEN ? AND ?
                ");
                $totalOrders->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $totalRevenue = $pdo->prepare("
                    SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_date BETWEEN ? AND ?
                ");
                $totalRevenue->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $totalSuppliers = $pdo->prepare("
                    SELECT COUNT(*) FROM suppliers WHERE created_at BETWEEN ? AND ?
                ");
                $totalSuppliers->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $totalProducts = $pdo->prepare("
                    SELECT COUNT(*) FROM products WHERE created_at BETWEEN ? AND ?
                ");
                $totalProducts->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $lowStock = $pdo->prepare("
                    SELECT COUNT(*) FROM products WHERE stock_quantity < min_stock
                ");
                $lowStock->execute();
                
                $totalSamples = $pdo->prepare("
                    SELECT COUNT(*) FROM sample_requests WHERE created_at BETWEEN ? AND ?
                ");
                $totalSamples->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $qcPassed = $pdo->prepare("
                    SELECT COUNT(*) FROM qc_results WHERE test_date BETWEEN ? AND ? AND result = 'Pass'
                ");
                $qcPassed->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $qcRejected = $pdo->prepare("
                    SELECT COUNT(*) FROM qc_results WHERE test_date BETWEEN ? AND ? AND result = 'Reject'
                ");
                $qcRejected->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $pendingPOs = $pdo->prepare("
                    SELECT COUNT(*) FROM purchase_orders WHERE created_at BETWEEN ? AND ? AND status = 'Pending Approval'
                ");
                $pendingPOs->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $approvedPOs = $pdo->prepare("
                    SELECT COUNT(*) FROM purchase_orders WHERE created_at BETWEEN ? AND ? AND status = 'Approved'
                ");
                $approvedPOs->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $response['data'] = [
                    'summary' => [
                        'total_orders' => $totalOrders->fetchColumn(),
                        'total_revenue' => $totalRevenue->fetchColumn(),
                        'total_suppliers' => $totalSuppliers->fetchColumn(),
                        'total_products' => $totalProducts->fetchColumn(),
                        'low_stock' => $lowStock->fetchColumn(),
                        'total_samples' => $totalSamples->fetchColumn(),
                        'qc_passed' => $qcPassed->fetchColumn(),
                        'qc_rejected' => $qcRejected->fetchColumn(),
                        'pending_pos' => $pendingPOs->fetchColumn(),
                        'approved_pos' => $approvedPOs->fetchColumn()
                    ]
                ];
                break;
                
            case 'product':
                // Product Report
                $productData = $pdo->prepare("
                    SELECT 
                        p.product_code,
                        p.product_name,
                        p.category,
                        p.price_per_kg,
                        p.stock_quantity as stock,
                        p.min_stock,
                        CASE 
                            WHEN p.stock_quantity < p.min_stock THEN 'Low'
                            ELSE 'Normal'
                        END as stock_status,
                        (p.stock_quantity * p.price_per_kg) as stock_value
                    FROM products p
                    WHERE p.created_at BETWEEN ? AND ?
                    ORDER BY p.product_name ASC
                ");
                $productData->execute([$fromDate, $toDate . ' 23:59:59']);
                $products = $productData->fetchAll();
                
                $totalStockValue = $pdo->prepare("
                    SELECT COALESCE(SUM(stock_quantity * price_per_kg), 0) FROM products 
                    WHERE created_at BETWEEN ? AND ?
                ");
                $totalStockValue->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $lowStockCount = $pdo->prepare("
                    SELECT COUNT(*) FROM products 
                    WHERE created_at BETWEEN ? AND ? AND stock_quantity < min_stock
                ");
                $lowStockCount->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $response['data'] = [
                    'products' => $products,
                    'summary' => [
                        'total_products' => count($products),
                        'total_stock_value' => $totalStockValue->fetchColumn(),
                        'low_stock_count' => $lowStockCount->fetchColumn()
                    ]
                ];
                break;
                
            case 'sample':
                // Sample Report
                $sampleData = $pdo->prepare("
                    SELECT 
                        sr.sample_code,
                        sr.supplier_id,
                        s.name as supplier_name,
                        sr.material_name,
                        sr.quantity,
                        sr.request_date,
                        sr.status,
                        qc.result as qc_result,
                        qc.test_date as qc_date,
                        qc.remarks,
                        qc.price as qc_price
                    FROM sample_requests sr
                    LEFT JOIN suppliers s ON sr.supplier_id = s.supplier_id
                    LEFT JOIN qc_results qc ON sr.sample_id = qc.sample_id
                    WHERE sr.request_date BETWEEN ? AND ?
                    ORDER BY sr.request_date DESC
                ");
                $sampleData->execute([$fromDate, $toDate . ' 23:59:59']);
                $samples = $sampleData->fetchAll();
                
                $totalSamples = $pdo->prepare("
                    SELECT COUNT(*) FROM sample_requests WHERE request_date BETWEEN ? AND ?
                ");
                $totalSamples->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $qcPassed = $pdo->prepare("
                    SELECT COUNT(*) FROM qc_results qc
                    JOIN sample_requests sr ON qc.sample_id = sr.sample_id
                    WHERE sr.request_date BETWEEN ? AND ? AND qc.result = 'Pass'
                ");
                $qcPassed->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $qcRejected = $pdo->prepare("
                    SELECT COUNT(*) FROM qc_results qc
                    JOIN sample_requests sr ON qc.sample_id = sr.sample_id
                    WHERE sr.request_date BETWEEN ? AND ? AND qc.result = 'Reject'
                ");
                $qcRejected->execute([$fromDate, $toDate . ' 23:59:59']);
                
                $response['data'] = [
                    'samples' => $samples,
                    'summary' => [
                        'total_samples' => $totalSamples->fetchColumn(),
                        'qc_passed' => $qcPassed->fetchColumn(),
                        'qc_rejected' => $qcRejected->fetchColumn()
                    ]
                ];
                break;
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// ===================== AJAX: GET SUPPLIER DATA FOR TABLES =====================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_suppliers') {
    header('Content-Type: application/json');
    
    $pending = $pdo->query("
        SELECT s.*, s.created_at as registered_date
        FROM suppliers s 
        WHERE s.status = 'pending' 
        ORDER BY s.created_at DESC
    ")->fetchAll();
    
    $active = $pdo->query("
        SELECT s.*, s.created_at as registered_date, s.updated_at as approved_date
        FROM suppliers s 
        WHERE s.status = 'active' 
        ORDER BY s.name ASC
    ")->fetchAll();
    
    echo json_encode([
        'success' => true,
        'pending' => $pending,
        'active' => $active
    ]);
    exit;
}

// ===================== AJAX: GET EMAIL LOG =====================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_email_log') {
    header('Content-Type: application/json');
    try {
        $logs = $pdo->query("
            SELECT * FROM email_log
            ORDER BY sent_at DESC
            LIMIT 50
        ")->fetchAll();
    } catch (Exception $e) {
        $logs = [];
    }
    echo json_encode(['success' => true, 'data' => $logs]);
    exit;
}

// ===================== AJAX: CLEAR EMAIL LOG =====================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clear_email_log') {
    header('Content-Type: application/json');
    try {
        $pdo->query("TRUNCATE TABLE email_log");
    } catch (Exception $e) {}
    echo json_encode(['success' => true, 'message' => 'Email log cleared']);
    exit;
}

// ===================== GET SUCCESS/ERROR MESSAGES =====================
$successMessage = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard</title>
    <style>
        body { font-family: sans-serif; margin: 30px; background: #f4f6f9; }
        .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-val { font-size: 28px; font-weight: bold; color: #2e7d32; }
    </style>
</head>
<body>
    <h1>🌿 Manager Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Manager'); ?>!</p>

    <div class="grid">
        <div class="card">
            <h3>Customers</h3>
            <div class="stat-val"><?php echo $customerCount; ?></div>
        </div>
        <div class="card">
            <h3>Active Suppliers</h3>
            <div class="stat-val"><?php echo $supplierCount; ?></div>
        </div>
        <div class="card">
            <h3>Pending Suppliers</h3>
            <div class="stat-val"><?php echo $pendingSupplierCount; ?></div>
        </div>
        <div class="card">
            <h3>Products</h3>
            <div class="stat-val"><?php echo $productCount; ?></div>
        </div>
    </div>
</body>
</html>
