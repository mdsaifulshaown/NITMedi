<?php
session_start();
require_once "../db/config.php"; // PDO connection

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
    <title>Manage Consultants - NITMedi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
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

        .consultant-table {
            width: 100%;
            border-collapse: collapse;
        }

        .consultant-table th {
            background: var(--primary);
            color: white;
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .consultant-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #e0e0e0;
            transition: var(--transition);
        }

        .consultant-table tr:hover td {
            background: rgba(52, 152, 219, 0.05);
            transform: scale(1.01);
        }

        .consultant-table tr:last-child td {
            border-bottom: none;
        }

        .action-btns {
            display: flex;
            gap: 10px;
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

        /* Status Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
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

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--accent);
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
            
            .consultant-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-btns {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 20px;
            }
            
            .table-header {
                padding: 15px 20px;
            }
            
            .consultant-table th,
            .consultant-table td {
                padding: 12px 15px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
        }

        /* Password Toggle */
        .password-toggle {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
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
                    <h1>Manage Consultants</h1>
                    <p>NIT Medical Center - Consultant Management</p>
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
                <a href="manage_medicines.php" class="nav-link">
                    <i class="fas fa-pills"></i> Medicines
                </a>
            </nav>

            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h2 class="section-title">Consultant Management</h2>

            <!-- Success Messages -->
            <?php if (isset($_GET['msg'])): ?>
                <?php
                $messages = [
                    'added' => 'Consultant added successfully!',
                    'updated' => 'Consultant updated successfully!',
                    'deleted' => 'Consultant deleted successfully!'
                ];
                if (isset($messages[$_GET['msg']])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $messages[$_GET['msg']] ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Add Consultant Form -->
            <div class="form-container">
                <h3 class="form-title">
                    <i class="fas fa-user-plus"></i> Add New Consultant
                </h3>
                <form method="POST" id="addConsultantForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <div class="input-group">
                                <input type="text" name="name" id="name" placeholder="Enter full name" required>
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <div class="input-group">
                                <input type="email" name="email" id="email" placeholder="Enter email address" required>
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Phone Number
                            </label>
                            <div class="input-group">
                                <input type="text" name="phone" id="phone" placeholder="Enter phone number" required>
                                <i class="fas fa-phone input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" placeholder="Enter password" required>
                                <i class="fas fa-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="add_consultant" class="btn">
                        <i class="fas fa-user-plus"></i> Add Consultant
                    </button>
                </form>
            </div>

            <!-- Consultants List -->
            <div class="table-container">
                <div class="table-header">
                    <h2>
                        <i class="fas fa-users"></i> Consultants List
                        <span style="font-size: 0.9rem; opacity: 0.8; margin-left: 10px;">
                            (Total: <?= count($consultants) ?>)
                        </span>
                    </h2>
                </div>
                
                <table class="consultant-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultants as $c): ?>
                            <tr>
                                <td><strong>#<?= $c['consultant_id'] ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                            <?= strtoupper(substr($c['name'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><?= htmlspecialchars($c['phone']) ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="manage_consultants.php?edit=<?= $c['consultant_id'] ?>" class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="manage_consultants.php?delete=<?= $c['consultant_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($c['name']) ?>?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit Consultant Form -->
            <?php if (isset($_GET['edit'])): 
                $edit_id = intval($_GET['edit']);
                $stmt = $pdo->prepare("SELECT * FROM consultants WHERE consultant_id = ?");
                $stmt->execute([$edit_id]);
                $edit_c = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="form-container" style="margin-top: 30px; animation: slideUp 0.6s ease;">
                    <h3 class="form-title">
                        <i class="fas fa-user-edit"></i> Edit Consultant
                    </h3>
                    <form method="POST" id="editConsultantForm">
                        <input type="hidden" name="id" value="<?= $edit_c['consultant_id'] ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="edit_name">Full Name</label>
                                <div class="input-group">
                                    <input type="text" name="name" id="edit_name" value="<?= htmlspecialchars($edit_c['name']) ?>" required>
                                    <i class="fas fa-user input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_email">Email Address</label>
                                <div class="input-group">
                                    <input type="email" name="email" id="edit_email" value="<?= htmlspecialchars($edit_c['email']) ?>" required>
                                    <i class="fas fa-envelope input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_phone">Phone Number</label>
                                <div class="input-group">
                                    <input type="text" name="phone" id="edit_phone" value="<?= htmlspecialchars($edit_c['phone']) ?>" required>
                                    <i class="fas fa-phone input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_password">New Password (Leave blank to keep current)</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="edit_password" placeholder="Enter new password">
                                    <i class="fas fa-lock input-icon"></i>
                                    <button type="button" class="password-toggle" onclick="togglePassword('edit_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="edit_consultant" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Consultant
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Password toggle functionality
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = event.currentTarget;
            const icon = toggle.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

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
            const editForm = document.querySelector('[id^="editConsultantForm"]');
            if (editForm) {
                editForm.scrollIntoView({ behavior: 'smooth' });
            }
        });
    </script>
</body>
</html>
