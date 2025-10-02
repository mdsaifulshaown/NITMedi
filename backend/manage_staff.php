<?php
session_start();
require_once "../db/config.php"; 

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

// Reset all staff costs
if (isset($_POST['reset_all_costs'])) {
    $stmt = $pdo->prepare("UPDATE consultations SET total_price = 0 WHERE patient_type = 'Staff'");
    $stmt->execute();
    header("Location: manage_staff.php?msg=all_costs_reset");
    exit;
}

// Reset individual staff cost
if (isset($_POST['reset_staff_cost'])) {
    $staff_id = intval($_POST['staff_id']);
    
    $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff) {
        $stmt = $pdo->prepare("UPDATE consultations SET total_price = 0 WHERE patient_id = ? AND patient_type = 'Staff'");
        $stmt->execute([$staff['staff_id']]);
    }
    
    header("Location: manage_staff.php?msg=staff_cost_reset&id=" . $staff_id);
    exit;
}

// Fetch all staff with their total consultation costs
$staffs_stmt = $pdo->query("
    SELECT s.*, 
           COALESCE(SUM(c.total_price), 0) as total_cost
    FROM staff s
    LEFT JOIN consultations c ON s.staff_id = c.patient_id AND c.patient_type = 'Staff'
    GROUP BY s.id
    ORDER BY s.id DESC
");
$staffs = $staffs_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - NITMedi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: var(--secondary);
            line-height: 1.6;
        }

        /* Header Styles */
        header {
            background: var(--secondary);
            color: white;
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .header-content h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        nav {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: var(--transition);
            font-weight: 500;
            position: relative;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        nav a.active {
            background: var(--primary);
        }

        .logout-btn {
            background: var(--accent);
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            animation: fadeIn 0.8s ease;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
            position: relative;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: var(--transition);
            border: 1px solid #eef2f7;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eef2f7;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--secondary);
        }

        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--accent);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #219653;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        table th {
            background: var(--secondary);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        table td {
            padding: 1rem;
            border-bottom: 1px solid #eef2f7;
        }

        table tr {
            transition: var(--transition);
        }

        table tr:hover {
            background: #f8f9fa;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        /* Cost indicators */
        .cost-high {
            color: var(--accent);
            font-weight: 700;
            background: #ffeaea;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            display: inline-block;
        }

        .cost-normal {
            color: var(--success);
            font-weight: 600;
        }

        /* Action buttons */
        .action-btns {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Messages */
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: slideDown 0.5s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.primary {
            background: var(--primary);
        }

        .stat-icon.warning {
            background: var(--warning);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .stat-info p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            nav {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .action-btns {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            table th, table td {
                padding: 0.75rem 0.5rem;
            }
        }

        @media (max-width: 480px) {
            header {
                padding: 1rem;
            }
            
            .card {
                padding: 1rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            nav a {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
        }

        /* Edit Form Styling */
        .edit-form-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .edit-form {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideDown 0.3s ease;
        }

        /* Loading States */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #bdc3c7;
        }
    </style>
</head>
<body>
<header>
    <div class="header-content">
        <div class="logo-section">
            <div class="logo">NM</div>
            <h2>NITMedi Admin</h2>
        </div>
        <nav>
            <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
            <a href="manage_faculty.php"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
            <a href="manage_staff.php" class="active"><i class="fas fa-users"></i> Staff</a>
            <a href="manage_consultants.php"><i class="fas fa-user-md"></i> Consultants</a>
            <a href="manage_medicines.php"><i class="fas fa-pills"></i> Medicines</a>
        </nav>
        <a href="php/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Staff Management</h1>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="toggleAddForm()">
                <i class="fas fa-plus"></i> Add New Staff
            </button>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?= count($staffs) ?></h3>
                <p>Total Staff Members</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <h3>₹<?= number_format(array_sum(array_column($staffs, 'total_cost')), 2) ?></h3>
                <p>Total Staff Costs</p>
            </div>
        </div>
    </div>

    <!-- Success Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <?php
        $messages = [
            'added' => 'Staff added successfully!',
            'updated' => 'Staff updated successfully!',
            'deleted' => 'Staff deleted successfully!',
            'all_costs_reset' => 'All staff costs have been reset!',
            'staff_cost_reset' => 'Staff cost has been reset!'
        ];
        if (isset($messages[$_GET['msg']])): ?>
            <div class="message message-success">
                <i class="fas fa-check-circle"></i>
                <?= $messages[$_GET['msg']] ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Cost Management Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calculator"></i> Cost Management</h3>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <form method="POST" style="display: inline-block;">
                <button type="submit" name="reset_all_costs" class="btn btn-warning" 
                        onclick="return confirm('⚠️ WARNING: This will reset costs for ALL staff. All consultation costs will be set to zero. This action cannot be undone. Are you absolutely sure?')">
                    <i class="fas fa-sync-alt"></i> Reset All Staff Costs
                </button>
            </form>
            <p style="color: #666; margin-top: 1rem; font-size: 0.9rem;">
                This will reset consultation costs for all staff in the system.
            </p>
        </div>
    </div>

    <!-- Add Staff Form (Initially Hidden) -->
    <div class="card" id="addStaffForm" style="display: none;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-plus"></i> Add New Staff Member</h3>
            <button class="btn btn-danger btn-sm" onclick="toggleAddForm()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="staff_id"><i class="fas fa-id-card"></i> Staff ID</label>
                    <input type="text" name="staff_id" placeholder="Enter Staff ID" required>
                </div>
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" placeholder="Enter Full Name" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="Enter Email" required>
                </div>
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="text" name="phone" placeholder="Enter Phone Number" required>
                </div>
                <div class="form-group">
                    <label for="position"><i class="fas fa-briefcase"></i> Position</label>
                    <input type="text" name="position" placeholder="Enter Position" required>
                </div>
            </div>
            <button type="submit" name="add_staff" class="btn btn-success">
                <i class="fas fa-save"></i> Add Staff Member
            </button>
        </form>
    </div>

    <!-- Staff List Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Staff Members</h3>
            <div class="card-actions">
                <span class="btn btn-primary btn-sm">Total: <?= count($staffs) ?></span>
            </div>
        </div>
        
        <?php if (empty($staffs)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No Staff Members Found</h3>
                <p>Get started by adding your first staff member.</p>
                <button class="btn btn-primary" onclick="toggleAddForm()">
                    <i class="fas fa-plus"></i> Add Staff Member
                </button>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Position</th>
                            <th>Total Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffs as $s): ?>
                            <tr>
                                <td><?= $s['id'] ?></td>
                                <td><strong><?= htmlspecialchars($s['staff_id']) ?></strong></td>
                                <td><?= htmlspecialchars($s['name']) ?></td>
                                <td><?= htmlspecialchars($s['email']) ?></td>
                                <td><?= htmlspecialchars($s['phone']) ?></td>
                                <td><?= htmlspecialchars($s['position']) ?></td>
                                <td>
                                    <span class="<?= ($s['total_cost'] ?? 0) > 15000 ? 'cost-high' : 'cost-normal' ?>">
                                        ₹<?= number_format($s['total_cost'] ?? 0, 2) ?>
                                    </span>
                                    <form method="POST" style="margin-top: 5px;">
                                        <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                                        <button type="submit" name="reset_staff_cost" class="btn btn-warning btn-sm" 
                                                onclick="return confirm('Are you sure you want to reset cost for staff: <?= htmlspecialchars($s['name']) ?>? This will set all their consultation costs to zero.')">
                                            <i class="fas fa-sync-alt"></i> Reset
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="manage_staff.php?edit=<?= $s['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="manage_staff.php?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure to delete staff: <?= htmlspecialchars($s['name']) ?>?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['edit'])): 
        $edit_id = intval($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_s = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
        <div class="edit-form-container">
            <div class="edit-form">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit"></i> Edit Staff Member</h3>
                    <a href="manage_staff.php" class="btn btn-danger btn-sm">
                        <i class="fas fa-times"></i> Close
                    </a>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $edit_s['id'] ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="staff_id"><i class="fas fa-id-card"></i> Staff ID</label>
                            <input type="text" name="staff_id" value="<?= htmlspecialchars($edit_s['staff_id']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($edit_s['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($edit_s['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($edit_s['phone']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="position"><i class="fas fa-briefcase"></i> Position</label>
                            <input type="text" name="position" value="<?= htmlspecialchars($edit_s['position']) ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="edit_staff" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Staff Member
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Toggle Add Staff Form
function toggleAddForm() {
    const form = document.getElementById('addStaffForm');
    if (form.style.display === 'none') {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth' });
    } else {
        form.style.display = 'none';
    }
}

// Add loading states to buttons
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<div class="loading"></div> Processing...';
                submitBtn.disabled = true;
                
                // Re-enable after 5 seconds if still on page (form submission failed)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    });
    
    // Add animation to table rows
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.1}s`;
        row.style.animation = 'fadeIn 0.5s ease forwards';
        row.style.opacity = '0';
    });
});

// Auto-hide success messages after 5 seconds
setTimeout(() => {
    const messages = document.querySelectorAll('.message');
    messages.forEach(msg => {
        msg.style.transition = 'all 0.5s ease';
        msg.style.opacity = '0';
        msg.style.height = '0';
        msg.style.padding = '0';
        msg.style.margin = '0';
        setTimeout(() => msg.remove(), 500);
    });
}, 5000);
</script>
</body>
</html>
