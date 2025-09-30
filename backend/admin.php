<?php
session_start();
require_once __DIR__ . '/../db/config.example.php';

// ✅ Check if Admin logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// ===============================
// Overview Counts
// ===============================
$student_count = $pdo->query("SELECT COUNT(*) as c FROM students")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$faculty_count = $pdo->query("SELECT COUNT(*) as c FROM faculty")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$staff_count = $pdo->query("SELECT COUNT(*) as c FROM staff")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$consultant_count = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role='Consultant'")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$medicine_count = $pdo->query("SELECT COUNT(*) as c FROM medicines")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - NITMedi</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f5f6fa; }
        header { background:#2c3e50; color:#fff; padding:15px; display:flex; justify-content:space-between; align-items:center; }
        header h2 { margin:0; }
        nav a { color:#fff; margin:0 10px; text-decoration:none; }
        nav a:hover { text-decoration:underline; }
        .logout-btn { background:#e74c3c; padding:6px 12px; border-radius:4px; text-decoration:none; color:#fff; }
        .container { padding:20px; }
        .cards { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:20px; margin-bottom:30px; }
        .card { background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); text-align:center; }
        .card h3 { margin:0; font-size:22px; color:#2c3e50; }
        .card p { margin:5px 0 0; font-size:16px; color:#555; }
        .buttons { margin-top:20px; display:flex; gap:20px; flex-wrap: wrap; }
        .buttons a { padding:12px 20px; background:#3498db; color:#fff; border-radius:6px; text-decoration:none; font-weight:bold; transition: 0.3s; }
        .buttons a:hover { background:#2980b9; }
    </style>
</head>
<body>
<header>
    <h2>Admin Dashboard</h2>
    <nav>
        <a href="manage_students.php">Students</a>
        <a href="manage_faculty.php">Faculty</a>
        <a href="manage_staff.php">Staff</a>
        <a href="manage_consultants.php">Consultants</a>
        <a href="manage_medicines.php">Medicines</a>
    </nav>
    <a href="logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
    <h2>Overview</h2>
    <div class="cards">
        <div class="card"><h3><?= $student_count ?></h3><p>Students</p></div>
        <div class="card"><h3><?= $faculty_count ?></h3><p>Faculty</p></div>
        <div class="card"><h3><?= $staff_count ?></h3><p>Staff</p></div>
        <div class="card"><h3><?= $consultant_count ?></h3><p>Consultants</p></div>
        <div class="card"><h3><?= $medicine_count ?></h3><p>Medicines</p></div>
    </div>

    <div class="buttons">
        <a href="latest_medicines.php">View Latest Medicines</a>
        <a href="latest_consultations.php">View Latest Consultations</a>
    </div>
</div>
</body>
</html>
