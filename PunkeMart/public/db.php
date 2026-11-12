<?php
// Database connection. Update username/password to match your local MySQL setup.
$host = 'localhost';
$dbname = 'punkemart_inventory_and_orders';
$username = 'root';
$password = '';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    exit('Database connection failed. Check includes/db.php and make sure MySQL is running.');
}
?>
