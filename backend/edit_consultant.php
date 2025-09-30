<?php
session_start();
require __DIR__ . '/../db/config.example.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role='Consultant'");
$stmt->execute([$id]);
$consultant = $stmt->fetch();

if (!$consultant) {
    die("Consultant not found!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if ($password) {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, password=? WHERE user_id=?");
        $stmt->execute([$name, $email, $phone, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=? WHERE user_id=?");
        $stmt->execute([$name, $email, $phone, $id]);
    }

    header("Location: manage_consultants.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Consultant</title>
</head>
<body>
    <h2>Edit Consultant</h2>
    <form method="POST">
        <label>Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($consultant['name']) ?>" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($consultant['email']) ?>" required><br><br>

        <label>Phone:</label><br>
        <input type="text" name="phone" value="<?= htmlspecialchars($consultant['phone']) ?>"><br><br>

        <label>Password (leave blank to keep unchanged):</label><br>
        <input type="password" name="password"><br><br>

        <button type="submit">Update</button>
    </form>
</body>
</html>
