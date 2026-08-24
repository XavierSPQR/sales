<?php
// setup_database.php
// Runs pearl_land_db.sql against your configured MySQL database.
// Usage (browser): http://localhost/sales_1/setup_database.php?run=1
// IMPORTANT: This file is intended for local/dev only.

require_once __DIR__ . '/db_config.php';

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

if (empty($_GET['run'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "This script will create/initialize the database using database/pearl_land_db.sql.\n\n";
    echo "Run it by visiting: setup_database.php?run=1\n";
    exit;
}

$sqlFile = __DIR__ . '/database/pearl_land_db.sql';
if (!is_file($sqlFile)) {
    fail('SQL file not found: ' . $sqlFile);
}

$sql = file_get_contents($sqlFile);
if ($sql === false || trim($sql) === '') {
    fail('Could not read SQL file or file is empty.');
}

$mysqli = null;
try {
    // db_config.php should define: $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME
    if (!isset($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME)) {
        fail('db_config.php must define $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME');
    }

    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
    if ($mysqli->connect_errno) {
        fail('MySQL connection failed: ' . $mysqli->connect_error);
    }

    // Ensure mysqli uses utf8mb4
    $mysqli->set_charset('utf8mb4');

    // Execute whole script. pearl_land_db.sql includes DROP/CREATE DATABASE and USE.
    // mysqli->multi_query handles multiple statements.
    if (!$mysqli->multi_query($sql)) {
        fail('SQL execution failed (multi_query): ' . $mysqli->error);
    }

    // Drain results
    do {
        if ($result = $mysqli->store_result()) {
            // Free result set
            $result->free();
        }

        // If there is another result set, we continue.
        $more = $mysqli->more_results();
        if ($more) {
            $mysqli->next_result();
        }
    } while ($more);

    header('Content-Type: text/plain; charset=utf-8');
    echo "Database setup completed successfully using pearl_land_db.sql\n";
    exit;

} catch (Throwable $e) {
    fail('Unexpected error: ' . $e->getMessage());
} finally {
    if ($mysqli instanceof mysqli) {
        $mysqli->close();
    }
}

