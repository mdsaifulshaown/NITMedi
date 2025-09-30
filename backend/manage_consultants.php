<?php
session_start();
require_once __DIR__ . '/../db/config.example.php'; // PDO connection

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Add Consultant
if (isset($_POST['add_consultant'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO consultants (name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $password]);
    header("Location: manage_consultants.php?msg=added");
    exit;
}

// Delete Consultant
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM consultants WHERE consultant_id = ?");
    $stmt->execute([$id]);
    header("Location: manage_consultants.php?msg=deleted");
    exit;
}

// Edit Consultant
if (isset($_POST['edit_consultant'])) {
    $id    = intval($_POST['id']);
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE consultants SET name=?, email=?, phone=?, password=? WHERE consultant_id=?");
        $stmt->execute([$name, $email, $phone, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE consultants SET name=?, email=?, phone=? WHERE consultant_id=?");
        $stmt->execute([$name, $email, $phone, $id]);
    }
    header("Location: manage_consultants.php?msg=updated");
    exit;
}

// Fetch all consultants
$consultants = $pdo->query("SELECT * FROM consultants ORDER BY consultant_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Consultants - NITMedi</title>
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
    <h2>Manage Consultants</h2>
    <nav>
        <a href="admin.php">Dashboard</a>
        <a href="manage_students.php">Students</a>
        <a href="manage_faculty.php">Faculty</a>
        <a href="manage_staff.php">Staff</a>
        <a href="manage_medicines.php">Medicines</a>
    </nav>
    <a href="logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
    <h2>Add New Consultant</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="add_consultant">Add Consultant</button>
    </form>

    <h2>Consultants List</h2>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Actions</th>
        </tr>
        <?php foreach ($consultants as $c): ?>
            <tr>
                <td><?= $c['consultant_id'] ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['phone']) ?></td>
                <td class="action-btns">
                    <a href="manage_consultants.php?edit=<?= $c['consultant_id'] ?>" class="edit-btn">Edit</a>
                    <a href="manage_consultants.php?delete=<?= $c['consultant_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure to delete?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (isset($_GET['edit'])): 
        $edit_id = intval($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM consultants WHERE consultant_id = ?");
        $stmt->execute([$edit_id]);
        $edit_c = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
        <h2>Edit Consultant</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_c['consultant_id'] ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($edit_c['name']) ?>" required>
            <input type="email" name="email" value="<?= htmlspecialchars($edit_c['email']) ?>" required>
            <input type="text" name="phone" value="<?= htmlspecialchars($edit_c['phone']) ?>" required>
            <input type="password" name="password" placeholder="Leave blank to keep old password">
            <button type="submit" name="edit_consultant">Update Consultant</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
