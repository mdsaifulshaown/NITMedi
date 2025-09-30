<?php
session_start();
require __DIR__ . '/../db/config.example.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id=? AND role='Consultant'");
    $stmt->execute([$id]);
}

header("Location: manage_consultants.php");
exit;
?>
