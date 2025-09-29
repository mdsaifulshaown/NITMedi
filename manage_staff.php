<?php
session_start();
require_once "db/config.php"; 

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Add Staff
if (isset($_POST['add_staff'])) {
    $staff_id = trim($_POST['staff_id']);
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $position = trim($_POST['position']);

    $stmt = $pdo->prepare("INSERT INTO staff (staff_id, name, email, phone, position) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$staff_id, $name, $email, $phone, $position]);
    header("Location: manage_staff.php?msg=added");
    exit;
}

// Delete Staff
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM staff WHERE id=?");
    $stmt->execute([$id]);
    header("Location: manage_staff.php?msg=deleted");
    exit;
}

// Edit Staff
if (isset($_POST['edit_staff'])) {
    $id       = intval($_POST['id']);
    $staff_id = trim($_POST['staff_id']);
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $position = trim($_POST['position']);

    $stmt = $pdo->prepare("UPDATE staff SET staff_id=?, name=?, email=?, phone=?, position=? WHERE id=?");
    $stmt->execute([$staff_id, $name, $email, $phone, $position, $id]);
    header("Location: manage_staff.php?msg=updated");
    exit;
}

// Fetch all staff
$staffs = $pdo->query("SELECT * FROM staff ORDER BY staff_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - NITMedi</title>
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
    <h2>Manage Staff</h2>
    <nav>
        <a href="admin.php">Dashboard</a>
        <a href="manage_students.php">Students</a>
        <a href="manage_faculty.php">Faculty</a>
        <a href="manage_consultants.php">Consultants</a>
        <a href="manage_medicines.php">Medicines</a>
    </nav>
    <a href="php/logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
    <h2>Add New Staff</h2>
    <form method="POST">
        <input type="text" name="staff_id" placeholder="Staff ID" required>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="text" name="position" placeholder="Position" required>
        <button type="submit" name="add_staff">Add Staff</button>
    </form>

    <h2>Staff List</h2>
    <table>
        <tr>
            <th>ID</th><th>Staff ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Position</th><th>Actions</th>
        </tr>
        <?php foreach ($staffs as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['staff_id']) ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= htmlspecialchars($s['phone']) ?></td>
                <td><?= htmlspecialchars($s['position']) ?></td>
                <td class="action-btns">
                    <a href="manage_staff.php?edit=<?= $s['id'] ?>" class="edit-btn">Edit</a>
                    <a href="manage_staff.php?delete=<?= $s['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure to delete?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (isset($_GET['edit'])): 
        $edit_id = intval($_GET['edit']);
        $edit_s = $pdo->query("SELECT * FROM staff WHERE id=$edit_id")->fetch(PDO::FETCH_ASSOC);
    ?>
        <h2>Edit Staff</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_s['id'] ?>">
            <input type="text" name="staff_id" value="<?= htmlspecialchars($edit_s['staff_id']) ?>" required>
            <input type="text" name="name" value="<?= htmlspecialchars($edit_s['name']) ?>" required>
            <input type="email" name="email" value="<?= htmlspecialchars($edit_s['email']) ?>" required>
            <input type="text" name="phone" value="<?= htmlspecialchars($edit_s['phone']) ?>" required>
            <input type="text" name="position" value="<?= htmlspecialchars($edit_s['position']) ?>" required>
            <button type="submit" name="edit_staff">Update Staff</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
