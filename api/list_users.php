<?php
declare(strict_types=1);

require_once __DIR__ . '/db_connect.php';

/**
 * Returns approved/active customers and wholesalers for the admin dashboard.
 * Username/password are intentionally NOT returned.
 */
endpoint_guard(function (): void {
    require_method(['GET']);

    $pdo = get_pdo();

    $customers = $pdo->query('
        SELECT c.customer_code, c.first_name, c.last_name, c.name, c.email, c.phone,
               c.address, c.city, c.postal_code, c.district, c.spice_preferences,
               c.account_status, u.status AS user_status
        FROM customers c
        JOIN users u ON u.user_id = c.user_id
        ORDER BY c.customer_code DESC
    ')->fetchAll(PDO::FETCH_ASSOC);

    $wholesalers = $pdo->query('
        SELECT w.wholesaler_code, w.first_name, w.last_name, w.company_name, w.email, w.phone,
               w.address, w.city, w.postal_code, w.district, w.business_type,
               w.account_status, u.status AS user_status
        FROM wholesalers w
        JOIN users u ON u.user_id = w.user_id
        ORDER BY w.wholesaler_code DESC
    ')->fetchAll(PDO::FETCH_ASSOC);

    respond(true, 'Customers and wholesalers fetched.', [
        'customers' => $customers,
        'wholesalers' => $wholesalers,
    ]);
});