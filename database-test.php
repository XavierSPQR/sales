<?php
// database-test.php - Test Database Connection
// Uses the Database singleton class.

require_once __DIR__ . '/classes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $driver = $db->getDriver();

    echo "<h1>✅ Database Connected Successfully!</h1>";
    echo "<p>Connected using: <strong>" . strtoupper($driver) . "</strong> driver</p>";

    if ($driver === 'mysql') {
        $version = $pdo->query("SELECT VERSION() as version")->fetch();
        echo "<p><strong>MySQL Version:</strong> " . $version['version'] . "</p>";
    } else {
        $version = $pdo->query("SELECT sqlite_version() as version")->fetch();
        echo "<p><strong>SQLite Version:</strong> " . $version['version'] . "</p>";
    }

    echo "<p style='color: green;'>✓ Database class is working correctly!</p>";

} catch (Exception $e) {
    echo "<h1>❌ Connection Failed</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>