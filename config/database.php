<?php
// config/database.php - Central Database Configuration
// Uses the Database singleton class for all connections.

require_once __DIR__ . '/../classes/Database.php';

// Get PDO connection from the singleton class
// This variable is kept for backward compatibility with existing code
$pdo = Database::getInstance()->getConnection();
?>
