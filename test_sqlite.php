<?php
// test_sqlite.php - Test SQLite Database Connection
// Uses the Database singleton class.
// NOTE: To test SQLite, change the driver in classes/Database.php to 'sqlite'.

require_once __DIR__ . '/classes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $driver = $db->getDriver();

    echo "<h1>✅ Database Connected via Singleton Class!</h1>";
    echo "<p><strong>Driver:</strong> " . strtoupper($driver) . "</p>";

    if ($driver === 'sqlite') {
        $result = $pdo->query("SELECT sqlite_version() as version");
        $row = $result->fetch();
        echo "<p><strong>SQLite Version:</strong> " . $row['version'] . "</p>";
        echo "<p><strong>Database File:</strong> " . __DIR__ . "/sales_system.db</p>";
    } else {
        $result = $pdo->query("SELECT VERSION() as version");
        $row = $result->fetch();
        echo "<p><strong>MySQL Version:</strong> " . $row['version'] . "</p>";
    }

    echo "<p style='color: green;'>✓ Ready to use!</p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ Connection Failed</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
