<?php
session_start();
require_once "../db/config.php"; 

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

// Check for expired medicines
$current_date = date('Y-m-d');
$expired_count = 0;
$low_stock_count = 0;

foreach ($medicines as $medicine) {
    if ($medicine['expiry_date'] < $current_date) {
        $expired_count++;
    }
    if ($medicine['stock'] < 10) {
        $low_stock_count++;
    }
}

// Base64 encoded logo
$logo_path = "assets/NITM logo.png";
$logo_data = null;
if (file_exists($logo_path)) {
    $logo_type = pathinfo($logo_path, PATHINFO_EXTENSION);
    $logo_content = file_get_contents($logo_path);
    $logo_data = 'data:image/' . $logo_type . ';base64,' . base64_encode($logo_content);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Medicines - NITMedi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gradient);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.8s ease;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            height: 50px;
            width: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header-title h1 {
            color: var(--secondary);
            font-size: 1.8rem;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .header-title p {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-link {
            color: var(--secondary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.2);
        }

        .nav-link:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .logout-btn {
            background: var(--accent);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        /* Main Content */
        .main-content {
            animation: fadeIn 1s ease;
        }

        .section-title {
            color: white;
            font-size: 2rem;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid var(--warning);
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.6s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-total .stat-icon { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-expired .stat-icon { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-low-stock .stat-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Form Styles */
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.6s ease;
        }

        .form-title {
            color: var(--secondary);
            font-size: 1.5rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-title i {
            color: var(--primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.95rem;
        }

        .input-group {
            position: relative;
        }

        input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .btn {
            background: var(--primary);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .btn:hover {
            background: #2980b9;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .btn-success {
            background: var(--success);
        }

        .btn-success:hover {
            background: #219653;
        }

        /* Table Styles */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.8s ease;
        }

        .table-header {
            background: var(--secondary);
            color: white;
            padding: 20px 30px;
        }

        .table-header h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
        }

        .table-header h2 i {
            color: var(--primary);
        }

        .medicines-table {
            width: 100%;
            border-collapse: collapse;
        }

        .medicines-table th {
            background: var(--primary);
            color: white;
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .medicines-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #e0e0e0;
            transition: var(--transition);
        }

        .medicines-table tr:hover td {
            background: rgba(52, 152, 219, 0.05);
            transform: scale(1.01);
        }

        .medicines-table tr:last-child td {
            border-bottom: none;
        }

        .stock-cell, .price-cell, .expiry-cell {
            text-align: center;
            font-weight: 600;
        }

        .low-stock {
            color: var(--warning);
        }

        .out-of-stock {
            color: var(--danger);
        }

        .expired {
            color: var(--danger);
            background: rgba(231, 76, 60, 0.1);
        }

        .action-btns {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .edit-btn {
            background: var(--primary);
            color: white;
        }

        .edit-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .delete-btn {
            background: var(--accent);
            color: white;
        }

        .delete-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header-left {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-links {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-link {
                width: 200px;
                justify-content: center;
            }
            
            .medicines-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-btns {
                flex-direction: column;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 20px;
            }
            
            .table-header {
                padding: 15px 20px;
            }
            
            .medicines-table th,
            .medicines-table td {
                padding: 12px 15px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <?php if ($logo_data): ?>
                    <img src="<?= $logo_data ?>" alt="NITM Logo" class="logo">
                <?php else: ?>
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #3498db, #2980b9); border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                        <span style="color: white; font-weight: bold; font-size: 0.7rem;">NITM</span>
                    </div>
                <?php endif; ?>
                <div class="header-title">
                    <h1>Manage Medicines</h1>
                    <p>NIT Medical Center - Medicine Inventory</p>
                </div>
            </div>

            <nav class="nav-links">
                <a href="admin.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="manage_students.php" class="nav-link">
                    <i class="fas fa-user-graduate"></i> Students
                </a>
                <a href="manage_faculty.php" class="nav-link">
                    <i class="fas fa-chalkboard-teacher"></i> Faculty
                </a>
                <a href="manage_staff.php" class="nav-link">
                    <i class="fas fa-user-tie"></i> Staff
                </a>
                <a href="manage_consultants.php" class="nav-link">
                    <i class="fas fa-user-md"></i> Consultants
                </a>
            </nav>

            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h2 class="section-title">Medicine Inventory Management</h2>

            <!-- Success Messages -->
            <?php if (isset($_GET['msg'])): ?>
                <?php
                $messages = [
                    'added' => 'Medicine added successfully!',
                    'updated' => 'Medicine updated successfully!',
                    'deleted' => 'Medicine deleted successfully!'
                ];
                if (isset($messages[$_GET['msg']])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $messages[$_GET['msg']] ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Warning Messages -->
            <?php if ($expired_count > 0): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Warning: <?= $expired_count ?> medicine(s) have expired!
                </div>
            <?php endif; ?>

            <?php if ($low_stock_count > 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle"></i>
                    Alert: <?= $low_stock_count ?> medicine(s) are running low on stock!
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card stat-total" style="animation-delay: 0.1s">
                    <div class="stat-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div class="stat-number"><?= count($medicines) ?></div>
                    <div class="stat-label">Total Medicines</div>
                </div>

                <div class="stat-card stat-expired" style="animation-delay: 0.2s">
                    <div class="stat-icon">
                        <i class="fas fa-skull-crossbones"></i>
                    </div>
                    <div class="stat-number"><?= $expired_count ?></div>
                    <div class="stat-label">Expired Medicines</div>
                </div>

                <div class="stat-card stat-low-stock" style="animation-delay: 0.3s">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-number"><?= $low_stock_count ?></div>
                    <div class="stat-label">Low Stock Alert</div>
                </div>
            </div>

            <!-- Add Medicine Form -->
            <div class="form-container">
                <h3 class="form-title">
                    <i class="fas fa-plus-circle"></i> Add New Medicine
                </h3>
                <form method="POST" id="addMedicineForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-pills"></i> Medicine Name
                            </label>
                            <div class="input-group">
                                <input type="text" name="name" id="name" placeholder="Enter medicine name" required>
                                <i class="fas fa-pills input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="stock">
                                <i class="fas fa-boxes"></i> Stock Quantity
                            </label>
                            <div class="input-group">
                                <input type="number" name="stock" id="stock" placeholder="Enter stock quantity" required min="0">
                                <i class="fas fa-boxes input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="price">
                                <i class="fas fa-rupee-sign"></i> Price per Unit
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="price" id="price" placeholder="Enter price" required min="0">
                                <i class="fas fa-rupee-sign input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="expiry_date">
                                <i class="fas fa-calendar-times"></i> Expiry Date
                            </label>
                            <div class="input-group">
                                <input type="date" name="expiry_date" id="expiry_date" required>
                                <i class="fas fa-calendar-times input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="add_medicine" class="btn">
                        <i class="fas fa-plus-circle"></i> Add Medicine
                    </button>
                </form>
            </div>

            <!-- Medicines List -->
            <div class="table-container">
                <div class="table-header">
                    <h2>
                        <i class="fas fa-list-alt"></i> Medicines Inventory
                        <span style="font-size: 0.9rem; opacity: 0.8; margin-left: 10px;">
                            (Total: <?= count($medicines) ?> medicines)
                        </span>
                    </h2>
                </div>
                
                <table class="medicines-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Medicine Name</th>
                            <th>Stock</th>
                            <th>Price (₹)</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $m): 
                            $is_expired = $m['expiry_date'] < $current_date;
                            $is_low_stock = $m['stock'] < 10;
                            $is_out_of_stock = $m['stock'] == 0;
                            
                            $stock_class = '';
                            if ($is_out_of_stock) {
                                $stock_class = 'out-of-stock';
                            } elseif ($is_low_stock) {
                                $stock_class = 'low-stock';
                            }
                        ?>
                            <tr class="<?= $is_expired ? 'expired' : '' ?>">
                                <td><strong>#<?= $m['medicine_id'] ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3498db, #2980b9); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                            <i class="fas fa-pills"></i>
                                        </div>
                                        <?= htmlspecialchars($m['name']) ?>
                                    </div>
                                </td>
                                <td class="stock-cell <?= $stock_class ?>">
                                    <?= $m['stock'] ?>
                                    <?php if ($is_low_stock): ?>
                                        <br><small style="color: var(--warning);">Low Stock!</small>
                                    <?php elseif ($is_out_of_stock): ?>
                                        <br><small style="color: var(--danger);">Out of Stock!</small>
                                    <?php endif; ?>
                                </td>
                                <td class="price-cell">₹<?= number_format($m['price'], 2) ?></td>
                                <td class="expiry-cell <?= $is_expired ? 'expired' : '' ?>">
                                    <?= $m['expiry_date'] ?>
                                    <?php if ($is_expired): ?>
                                        <br><small style="color: var(--danger);">Expired!</small>
                                    <?php endif; ?>
                                </td>
                                <td class="status-cell">
                                    <?php if ($is_expired): ?>
                                        <span style="color: var(--danger); font-weight: 600;">
                                            <i class="fas fa-skull-crossbones"></i> Expired
                                        </span>
                                    <?php elseif ($is_out_of_stock): ?>
                                        <span style="color: var(--danger); font-weight: 600;">
                                            <i class="fas fa-times-circle"></i> Out of Stock
                                        </span>
                                    <?php elseif ($is_low_stock): ?>
                                        <span style="color: var(--warning); font-weight: 600;">
                                            <i class="fas fa-exclamation-triangle"></i> Low Stock
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--success); font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> Available
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="manage_medicines.php?edit=<?= $m['medicine_id'] ?>" class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="manage_medicines.php?delete=<?= $m['medicine_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($m['name']) ?>?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit Medicine Form -->
            <?php if (isset($_GET['edit'])): 
                $edit_id = intval($_GET['edit']);
                $stmt = $pdo->prepare("SELECT * FROM medicines WHERE medicine_id = ?");
                $stmt->execute([$edit_id]);
                $edit_m = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="form-container" style="margin-top: 30px; animation: slideUp 0.6s ease;">
                    <h3 class="form-title">
                        <i class="fas fa-edit"></i> Edit Medicine
                    </h3>
                    <form method="POST" id="editMedicineForm">
                        <input type="hidden" name="id" value="<?= $edit_m['medicine_id'] ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="edit_name">Medicine Name</label>
                                <div class="input-group">
                                    <input type="text" name="name" id="edit_name" value="<?= htmlspecialchars($edit_m['name']) ?>" required>
                                    <i class="fas fa-pills input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_stock">Stock Quantity</label>
                                <div class="input-group">
                                    <input type="number" name="stock" id="edit_stock" value="<?= $edit_m['stock'] ?>" required min="0">
                                    <i class="fas fa-boxes input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_price">Price per Unit</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="price" id="edit_price" value="<?= $edit_m['price'] ?>" required min="0">
                                    <i class="fas fa-rupee-sign input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_expiry_date">Expiry Date</label>
                                <div class="input-group">
                                    <input type="date" name="expiry_date" id="edit_expiry_date" value="<?= $edit_m['expiry_date'] ?>" required>
                                    <i class="fas fa-calendar-times input-icon"></i>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="edit_medicine" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Medicine
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Form validation and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to inputs
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });

            // Auto-hide success messages after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            // Smooth scroll to edit form
            const editForm = document.querySelector('[id^="editMedicineForm"]');
            if (editForm) {
                editForm.scrollIntoView({ behavior: 'smooth' });
            }

            // Set minimum date for expiry date to today
            const expiryInputs = document.querySelectorAll('input[type="date"]');
            const today = new Date().toISOString().split('T')[0];
            expiryInputs.forEach(input => {
                input.min = today;
            });
        });
    </script>
</body>
</html>
