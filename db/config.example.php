<?php

$host = "127.0.0.1";       // Use 127.0.0.1 instead of localhost
$port = 3307;               // XAMPP MySQL port
$dbname = "medical_center"; 
$username = "root";         
$password = "";             

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful!";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
