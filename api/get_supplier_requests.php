<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['GET']);
    require_roles(['manager', 'admin']);
    $status = $_GET['status'] ?? 'Pending';
    $stmt = get_pdo()->prepare('SELECT request_id, request_code, company_name, contact_person, email, phone, address, city, materials, business_type, status, rejection_reason, submitted_at, reviewed_at, reviewed_by FROM supplier_registration_requests WHERE status = ? ORDER BY submitted_at DESC');
    $stmt->execute([$status]);
    respond(true, 'Supplier requests loaded', ['requests' => $stmt->fetchAll()]);
});
?>
