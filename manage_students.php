<?php
session_start();
require_once "db/config.php"; 

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Add Student
if (isset($_POST['add_student'])) {
    $student_id = trim($_POST['student_id']);
    $name       = trim($_POST['name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $dob        = $_POST['dob'];

    $stmt = $pdo->prepare("INSERT INTO students (student_id, name, email, phone, department, dob) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$student_id, $name, $email, $phone, $department, $dob]);
    header("Location: manage_students.php?msg=added");
    exit;
}

// Delete Student
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_students.php?msg=deleted");
    exit;
}

// Edit Student
if (isset($_POST['edit_student'])) {
    $id         = intval($_POST['id']);
    $student_id = trim($_POST['student_id']);
    $name       = trim($_POST['name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $dob        = $_POST['dob'];

    $stmt = $pdo->prepare("UPDATE students SET student_id=?, name=?, email=?, phone=?, department=?, dob=? WHERE id=?");
    $stmt->execute([$student_id, $name, $email, $phone, $department, $dob, $id]);
    header("Location: manage_students.php?msg=updated");
    exit;
}

// Fetch all students
$students_stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students - NITMedi</title>
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
    <h2>Manage Students</h2>
    <nav>
        <a href="admin.php">Dashboard</a>
        <a href="manage_faculty.php">Faculty</a>
        <a href="manage_staff.php">Staff</a>
        <a href="manage_consultants.php">Consultants</a>
        <a href="manage_medicines.php">Medicines</a>
    </nav>
    <a href="php/logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
    <h2>Add New Student</h2>
    <form method="POST">
        <input type="text" name="student_id" placeholder="Student ID" required>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="text" name="department" placeholder="Department" required>
        <input type="date" name="dob" required>
        <button type="submit" name="add_student">Add Student</button>
    </form>

    <h2>Students List</h2>
    <table>
        <tr>
            <th>ID</th><th>Student ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Department</th><th>DOB</th><th>Actions</th>
        </tr>
        <?php foreach ($students as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['student_id']) ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= htmlspecialchars($s['phone']) ?></td>
                <td><?= htmlspecialchars($s['department']) ?></td>
                <td><?= htmlspecialchars($s['dob']) ?></td>
                <td class="action-btns">
                    <a href="manage_students.php?edit=<?= $s['id'] ?>" class="edit-btn">Edit</a>
                    <a href="manage_students.php?delete=<?= $s['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure to delete?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (isset($_GET['edit'])): 
        $edit_id = intval($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_s = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
        <h2>Edit Student</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_s['id'] ?>">
            <input type="text" name="student_id" value="<?= htmlspecialchars($edit_s['student_id']) ?>" required>
            <input type="text" name="name" value="<?= htmlspecialchars($edit_s['name']) ?>" required>
            <input type="email" name="email" value="<?= htmlspecialchars($edit_s['email']) ?>" required>
            <input type="text" name="phone" value="<?= htmlspecialchars($edit_s['phone']) ?>" required>
            <input type="text" name="department" value="<?= htmlspecialchars($edit_s['department']) ?>" required>
            <input type="date" name="dob" value="<?= htmlspecialchars($edit_s['dob']) ?>" required>
            <button type="submit" name="edit_student">Update Student</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
