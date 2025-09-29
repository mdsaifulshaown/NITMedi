<?php
session_start();
require_once "db/config.php"; 

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Add Medicine
if (isset($_POST['add_medicine'])) {
    $name  = trim($_POST['name']);
    $stock = intval($_POST['stock']);
    $price = floatval($_POST['price']);
    $expiry = $_POST['expiry_date'];

    $stmt = $pdo->prepare("INSERT INTO medicines (name, stock, price, expiry_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $stock, $price, $expiry]);

    header("Location: manage_medicines.php?msg=added");
    exit;
}

// Delete Medicine
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM medicines WHERE medicine_id = ?");
    $stmt->execute([$id]);

    header("Location: manage_medicines.php?msg=deleted");
    exit;
}

// Edit Medicine
if (isset($_POST['edit_medicine'])) {
    $id    = intval($_POST['id']);
    $name  = trim($_POST['name']);
    $stock = intval($_POST['stock']);
    $price = floatval($_POST['price']);
    $expiry = $_POST['expiry_date'];

    $stmt = $pdo->prepare("UPDATE medicines SET name=?, stock=?, price=?, expiry_date=? WHERE medicine_id=?");
    $stmt->execute([$name, $stock, $price, $expiry, $id]);

    header("Location: manage_medicines.php?msg=updated");
    exit;
}

// Fetch all medicines
$medicines = $pdo->query("SELECT * FROM medicines ORDER BY medicine_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Medicines - NITMedi</title>
    <style>
        /* ============ Global Reset ============ */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

/* ============ Body ============ */
body {
    background: #f4f6f9;
    color: #333;
    line-height: 1.6;
}

/* ============ Header ============ */
header {
    background: #007BFF;
    color: #fff;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
header h2 {
    font-size: 22px;
}
header nav {
    display: flex;
    gap: 15px;
}
header nav a {
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    padding: 6px 10px;
    border-radius: 5px;
    transition: 0.3s;
}
header nav a:hover {
    background: rgba(255,255,255,0.2);
}
.logout-btn {
    background: #dc3545;
    color: #fff;
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 5px;
    transition: 0.3s;
}
.logout-btn:hover {
    background: #b02a37;
}

/* ============ Container ============ */
.container {
    width: 80%;
    margin: 25px auto;
    background: #fff;
    padding: 20px 25px;
    border-radius: 10px;
    box-shadow: 0px 4px 8px rgba(0,0,0,0.08);
}
.container h2 {
    margin-bottom: 15px;
    color: #007BFF;
}

/* ============ Form ============ */
form {
    margin-bottom: 30px;
}
form input, form button {
    display: block;
    width: 100%;
    margin: 10px 0;
    padding: 10px;
    font-size: 15px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
form input:focus {
    border-color: #007BFF;
    outline: none;
}
form button {
    background: #28a745;
    color: white;
    border: none;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}
form button:hover {
    background: #218838;
}

/* ============ Table ============ */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 25px;
}
table th, table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
}
table th {
    background: #007BFF;
    color: white;
}
table tr:nth-child(even) {
    background: #f9f9f9;
}
table tr:hover {
    background: #f1f1f1;
}

/* ============ Action Buttons ============ */
table a {
    padding: 5px 10px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}
table a[href*="edit"] {
    background: #ffc107;
    color: #000;
}
table a[href*="edit"]:hover {
    background: #e0a800;
}
table a[href*="delete"] {
    background: #dc3545;
    color: #fff;
}
table a[href*="delete"]:hover {
    background: #b02a37;
}

    </style>
</head>
<body>
<header>
    <h2>Manage Medicines</h2>
    <nav>
        <a href="admin.php">Dashboard</a>
        <a href="manage_students.php">Students</a>
        <a href="manage_faculty.php">Faculty</a>
        <a href="manage_staff.php">Staff</a>
        <a href="manage_medicines.php">Medicines</a>
    </nav>
    <a href="php/logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
    <h2>Add New Medicine</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Medicine Name" required>
        <input type="number" name="stock" placeholder="Stock" required>
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <input type="date" name="expiry_date" required>
        <button type="submit" name="add_medicine">Add Medicine</button>
    </form>

    <h2>Medicines List</h2>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Stock</th><th>Price</th><th>Expiry</th><th>Actions</th>
        </tr>
        <?php foreach ($medicines as $m): ?>
            <tr>
                <td><?= $m['medicine_id'] ?></td>
                <td><?= htmlspecialchars($m['name']) ?></td>
                <td><?= $m['stock'] ?></td>
                <td><?= $m['price'] ?></td>
                <td><?= $m['expiry_date'] ?></td>
                <td>
                    <a href="manage_medicines.php?edit=<?= $m['medicine_id'] ?>">Edit</a>
                    <a href="manage_medicines.php?delete=<?= $m['medicine_id'] ?>" onclick="return confirm('Delete this medicine?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (isset($_GET['edit'])): 
        $edit_id = intval($_GET['edit']);
        $edit_m = $pdo->query("SELECT * FROM medicines WHERE medicine_id=$edit_id")->fetch(PDO::FETCH_ASSOC);
    ?>
        <h2>Edit Medicine</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_m['medicine_id'] ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($edit_m['name']) ?>" required>
            <input type="number" name="stock" value="<?= $edit_m['stock'] ?>" required>
            <input type="number" step="0.01" name="price" value="<?= $edit_m['price'] ?>" required>
            <input type="date" name="expiry_date" value="<?= $edit_m['expiry_date'] ?>" required>
            <button type="submit" name="edit_medicine">Update Medicine</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>







