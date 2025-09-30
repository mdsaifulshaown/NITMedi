<?php
session_start();
require_once __DIR__ . '/../db/config.example.php';

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Add Faculty
if (isset($_POST['add_faculty'])) {
    $faculty_id = trim($_POST['faculty_id']);
    $name       = trim($_POST['name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $department = trim($_POST['department']);

    $stmt = $pdo->prepare("INSERT INTO faculty (faculty_id, name, email, phone, department) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$faculty_id, $name, $email, $phone, $department]);
    header("Location: manage_faculty.php?msg=added");
    exit;
}

// Delete Faculty
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM faculty WHERE id=?");
    $stmt->execute([$id]);
    header("Location: manage_faculty.php?msg=deleted");
    exit;
}

// Edit Faculty
if (isset($_POST['edit_faculty'])) {
    $id         = intval($_POST['id']);
    $faculty_id = trim($_POST['faculty_id']);
    $name       = trim($_POST['name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $department = trim($_POST['department']);

    $stmt = $pdo->prepare("UPDATE faculty SET faculty_id=?, name=?, email=?, phone=?, department=? WHERE id=?");
    $stmt->execute([$faculty_id, $name, $email, $phone, $department, $id]);
    header("Location: manage_faculty.php?msg=updated");
    exit;
}

// Fetch all faculty
$faculties = $pdo->query("SELECT * FROM faculty ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Faculty - NITMedi</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f5f6fa; }
        header { background:#2c3e50; color:#fff; padding:15px; display:flex; justify-content:space-between; align-items:center; }
        header h2 { margin:0; }
        nav a { color:#fff; margin:0 10px; text-decoration:none; }
        nav a:hover { text-decoration:underline; }
        .logout-btn { background:#e74c3c; padding:6px 12px; border-radius:4px; text-decoration:none; color:#fff; }
        .container { padding:20px; }
        h2 { color:#2c3e50; }
        form { background:#fff; padding:15px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); margin-bottom:20px; }
        input, button { padding:8px; margin:5px 0; width:100%; }
        table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff; border-radius:6px; overflow:hidden; }
        table th, table td { padding:12px; border:1px solid #ddd; text-align:left; }
        table th { background:#34495e; color:#fff; }
        .action-btns a { margin-right:10px; text-decoration:none; padding:5px 10px; border-radius:4px; }
        .edit-btn { background:#3498db; color:#fff; }
        .delete-btn { background:#e74c3c; color:#fff; }
    </style>
</head>
<body>
<header>
    <h2>Manage Faculty</h2>
    <nav>
        <a href="admin.php">Dashboard</a>
        <a href="manage_students.php">Students</a>
        <a href="manage_staff.php">Staff</a>
        <a href="manage_consultants.php">Consultants</a>
        <a href="manage_medicines.php">Medicines</a>
    </nav>
    <a href="logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
    <h2>Add New Faculty</h2>
    <form method="POST">
        <input type="text" name="faculty_id" placeholder="Faculty ID" required>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="text" name="department" placeholder="Department" required>
        <button type="submit" name="add_faculty">Add Faculty</button>
    </form>

    <h2>Faculty List</h2>
    <table>
        <tr>
            <th>ID</th><th>Faculty ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Department</th><th>Actions</th>
        </tr>
        <?php foreach ($faculties as $f): ?>
            <tr>
                <td><?= $f['id'] ?></td>
                <td><?= htmlspecialchars($f['faculty_id']) ?></td>
                <td><?= htmlspecialchars($f['name']) ?></td>
                <td><?= htmlspecialchars($f['email']) ?></td>
                <td><?= htmlspecialchars($f['phone']) ?></td>
                <td><?= htmlspecialchars($f['department']) ?></td>
                <td class="action-btns">
                    <a href="manage_faculty.php?edit=<?= $f['id'] ?>" class="edit-btn">Edit</a>
                    <a href="manage_faculty.php?delete=<?= $f['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure to delete?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (isset($_GET['edit'])):
        $edit_id = intval($_GET['edit']);
        $edit_f = $pdo->query("SELECT * FROM faculty WHERE id=$edit_id")->fetch(PDO::FETCH_ASSOC);
    ?>
        <h2>Edit Faculty</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_f['id'] ?>">
            <input type="text" name="faculty_id" value="<?= htmlspecialchars($edit_f['faculty_id']) ?>" required>
            <input type="text" name="name" value="<?= htmlspecialchars($edit_f['name']) ?>" required>
            <input type="email" name="email" value="<?= htmlspecialchars($edit_f['email']) ?>" required>
            <input type="text" name="phone" value="<?= htmlspecialchars($edit_f['phone']) ?>" required>
            <input type="text" name="department" value="<?= htmlspecialchars($edit_f['department']) ?>" required>
            <button type="submit" name="edit_faculty">Update Faculty</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
