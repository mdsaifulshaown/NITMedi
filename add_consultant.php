<?php
session_start();
require 'db/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']); // simple text for now

    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'Consultant')");
    $stmt->execute([$name, $email, $phone, $password]);

    header("Location: manage_consultants.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Consultant</title>
</head>
<body>
    <h2>Add New Consultant</h2>
    <form method="POST">
        <label>Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Phone:</label><br>
        <input type="text" name="phone"><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Save</button>
    </form>
</body>
</html>
