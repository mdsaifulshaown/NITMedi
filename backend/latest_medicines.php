<?php
session_start();
require_once __DIR__ . '/../db/config.example.php';

// Check if Admin logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// ===============================
// Fetch Latest Consultations
// ===============================
try {
    $latest_consults = $pdo->query("SELECT * FROM consultations ORDER BY consultation_id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching consultations: " . $e->getMessage());
}

// ===============================
// Helper to get patient name
// ===============================
function getPatientName($type, $id, $pdo){
    if($type === "Student"){
        $stmt = $pdo->prepare("SELECT name FROM students WHERE student_id=?");
    } elseif($type === "Faculty"){
        $stmt = $pdo->prepare("SELECT name FROM faculty WHERE faculty_id=?");
    } elseif($type === "Staff"){
        $stmt = $pdo->prepare("SELECT name FROM staff WHERE staff_id=?");
    } else {
        return "Unknown";
    }
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['name'] : "Unknown";
}

// ===============================
// Helper to get total medicine cost
// ===============================
function getTotalMedicineCost($consultation_id, $pdo){
    $stmt = $pdo->prepare("SELECT SUM(total_price) as total FROM prescription WHERE consultation_id=?");
    $stmt->execute([$consultation_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['total'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Latest Consultations - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f5f6fa; margin:0; padding:0; }
        header { background:#2c3e50; color:#fff; padding:15px; display:flex; justify-content:space-between; align-items:center; }
        header h2 { margin:0; }
        nav a { color:#fff; margin:0 10px; text-decoration:none; }
        nav a:hover { text-decoration:underline; }
        .container { padding:20px; }
        table { width:100%; border-collapse:collapse; background:#fff; border-radius:6px; overflow:hidden; }
        table th, table td { padding:12px; border:1px solid #ddd; text-align:left; }
        table th { background:#34495e; color:#fff; }
    </style>
</head>
<body>
<header>
    <h2>Latest Consultations</h2>
    <nav>
        <a href="admin_dashboard.php" style="color:#fff;">Back to Dashboard</a>
    </nav>
</header>

<div class="container">
    <table>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Type</th>
            <th>Consultant ID</th>
            <th>Disease</th>
            <th>Date</th>
            <th>Total Cost</th>
        </tr>
        <?php foreach ($latest_consults as $c): ?>
            <tr>
                <td><?= $c['consultation_id'] ?></td>
                <td><?= htmlspecialchars(getPatientName($c['patient_type'], $c['patient_id'], $pdo)) ?></td>
                <td><?= $c['patient_type'] ?></td>
                <td><?= $c['consultant_id'] ?></td>
                <td><?= htmlspecialchars($c['disease_name']) ?></td>
                <td><?= $c['consultation_date'] ?></td>
                <td><?= getTotalMedicineCost($c['consultation_id'], $pdo) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
