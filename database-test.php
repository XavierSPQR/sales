<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'pearl_land_db';

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>✅ Database Connected Successfully!</h1>";
echo "Connected to: <strong>" . $database . "</strong> database";

mysqli_close($conn);
?>