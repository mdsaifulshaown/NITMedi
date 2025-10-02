<?php
session_start();
require_once "../db/config.php"; 

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

// Reset all student costs
if (isset($_POST['reset_all_costs'])) {
    // Reset all consultation costs for students
    $stmt = $pdo->prepare("UPDATE consultations SET total_price = 0 WHERE patient_type = 'Student'");
    $stmt->execute();
    header("Location: manage_students.php?msg=all_costs_reset");
    exit;
}

// Reset individual student cost
if (isset($_POST['reset_student_cost'])) {
    $student_id = intval($_POST['student_id']);
    
    // Get student's student_id from id
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        // Reset consultation costs for this specific student
        $stmt = $pdo->prepare("UPDATE consultations SET total_price = 0 WHERE patient_id = ? AND patient_type = 'Student'");
        $stmt->execute([$student['student_id']]);
    }
    
    header("Location: manage_students.php?msg=student_cost_reset&id=" . $student_id);
    exit;
}

// Fetch all students with their total consultation costs
$students_stmt = $pdo->query("
    SELECT s.*, 
           COALESCE(SUM(c.total_price), 0) as total_cost
    FROM students s
    LEFT JOIN consultations c ON s.student_id = c.patient_id AND c.patient_type = 'Student'
    GROUP BY s.id
    ORDER BY s.id DESC
");
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - NITMedi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }

        nav {
            display: flex;
            gap: 15px;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .logout-btn {
            background: var(--danger);
            padding: 8px 16px;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .logout-btn:hover {
            background: #d90452;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(247, 37, 133, 0.3);
        }

        .container {
            padding: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-title {
            color: var(--dark);
            margin-bottom: 25px;
            font-weight: 600;
            position: relative;
            display: inline-block;
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            transition: var(--transition);
            animation: fadeIn 0.5s ease;
        }

        .card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-3px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
            font-size: 15px;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d90452;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(247, 37, 133, 0.3);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e76f11;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(248, 150, 30, 0.3);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #3aa8d0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 201, 240, 0.3);
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 14px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background: white;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        table th, table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        table th {
            background: var(--primary);
            color: white;
            font-weight: 500;
            position: sticky;
            top: 0;
        }

        table tr {
            transition: var(--transition);
        }

        table tr:hover {
            background: rgba(67, 97, 238, 0.05);
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .high-cost {
            color: var(--danger);
            font-weight: 600;
        }

        .normal-cost {
            color: var(--success);
            font-weight: 500;
        }

        .cost-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .cost-badge.high {
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }

        .cost-badge.normal {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .msg-success {
            background: rgba(76, 201, 240, 0.15);
            color: #155724;
            padding: 15px;
            border-radius: var(--border-radius);
            margin: 15px 0;
            border-left: 4px solid var(--success);
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .section-title {
            margin: 25px 0 15px;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.25rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
        }

        .close-btn:hover {
            color: var(--danger);
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
            }
            
            nav {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .container {
                padding: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .action-btns {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .modal-content {
                padding: 20px;
                width: 95%;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<header>
    <h2><i class="fas fa-user-graduate"></i> Manage Students</h2>
    <nav>
        <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_faculty.php"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
        <a href="manage_staff.php"><i class="fas fa-users"></i> Staff</a>
        <a href="manage_consultants.php"><i class="fas fa-user-md"></i> Consultants</a>
        <a href="manage_medicines.php"><i class="fas fa-pills"></i> Medicines</a>
    </nav>
    <a href="php/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</header>

<div class="container">
    <!-- Show success messages -->
    <?php if (isset($_GET['msg'])): ?>
        <?php
        $messages = [
            'added' => 'Student added successfully!',
            'updated' => 'Student updated successfully!',
            'deleted' => 'Student deleted successfully!',
            'all_costs_reset' => 'All student costs have been reset!',
            'student_cost_reset' => 'Student cost has been reset!',
            'costs_reset' => 'Costs reset successfully!'
        ];
        if (isset($messages[$_GET['msg']])): ?>
            <div class="msg-success">
                <i class="fas fa-check-circle"></i> <?= $messages[$_GET['msg']] ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-label">Total Students</div>
            <div class="stat-value"><?= count($students) ?></div>
            <i class="fas fa-user-graduate fa-2x" style="color: var(--primary);"></i>
        </div>
        <div class="stat-card">
            <div class="stat-label">High Cost Students</div>
            <div class="stat-value">
                <?= count(array_filter($students, function($s) { return ($s['total_cost'] ?? 0) > 15000; })) ?>
            </div>
            <i class="fas fa-exclamation-triangle fa-2x" style="color: var(--danger);"></i>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Costs</div>
            <div class="stat-value">₹<?= number_format(array_sum(array_column($students, 'total_cost')), 2) ?></div>
            <i class="fas fa-rupee-sign fa-2x" style="color: var(--success);"></i>
        </div>
    </div>

    <!-- Cost Management Section -->
    <div class="card">
        <h3 class="section-title"><i class="fas fa-calculator"></i> Cost Management</h3>
        <form method="POST" style="text-align: center; background: none; box-shadow: none; margin: 0; padding: 0;">
            <button type="submit" name="reset_all_costs" class="btn btn-warning" onclick="return confirm('Are you sure you want to reset ALL student costs? This will set all consultation costs to zero. This action cannot be undone.')">
                <i class="fas fa-undo"></i> Reset All Student Costs
            </button>
        </form>
        <p style="text-align: center; color: var(--gray); font-size: 14px; margin: 10px 0 0 0;">
            This will reset consultation costs for all students in the system.
        </p>
    </div>

    <!-- Add Student Form -->
    <div class="card">
        <h2 class="page-title"><i class="fas fa-plus-circle"></i> Add New Student</h2>
        <form method="POST" class="form-grid">
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input type="text" id="student_id" name="student_id" placeholder="Enter Student ID" required>
            </div>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter Full Name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter Email" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" placeholder="Enter Phone Number" required>
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department" placeholder="Enter Department" required>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" required>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <button type="submit" name="add_student" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add Student
                </button>
            </div>
        </form>
    </div>

    <!-- Students List -->
    <div class="card">
        <h2 class="page-title"><i class="fas fa-list"></i> Students List</h2>
        
        <div class="search-container">
            <div class="form-group search-input">
                <input type="text" id="searchInput" placeholder="Search students...">
            </div>
            <button class="btn btn-primary" id="searchBtn">
                <i class="fas fa-search"></i> Search
            </button>
        </div>
        
        <div class="table-container">
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>DOB</th>
                        <th>Total Cost</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?= $s['id'] ?></td>
                            <td><?= htmlspecialchars($s['student_id']) ?></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><?= htmlspecialchars($s['phone']) ?></td>
                            <td><?= htmlspecialchars($s['department']) ?></td>
                            <td><?= htmlspecialchars($s['dob']) ?></td>
                            <td>
                                <span class="cost-badge <?= ($s['total_cost'] ?? 0) > 15000 ? 'high' : 'normal' ?>">
                                    ₹<?= number_format($s['total_cost'] ?? 0, 2) ?>
                                </span>
                                <form method="POST" style="margin-top: 5px;">
                                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                    <button type="submit" name="reset_student_cost" class="btn btn-warning btn-sm" 
                                            onclick="return confirm('Are you sure you want to reset cost for student: <?= htmlspecialchars($s['name']) ?>? This will set all their consultation costs to zero.')">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </form>
                            </td>
                            <td class="action-btns">
                                <a href="manage_students.php?edit=<?= $s['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="manage_students.php?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure to delete student: <?= htmlspecialchars($s['name']) ?>?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (isset($_GET['edit'])): 
        $edit_id = intval($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_s = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
        <!-- Edit Student Modal -->
        <div class="modal" id="editModal" style="display: flex;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title"><i class="fas fa-edit"></i> Edit Student</h3>
                    <a href="manage_students.php" class="close-btn">&times;</a>
                </div>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="id" value="<?= $edit_s['id'] ?>">
                    <div class="form-group">
                        <label for="edit_student_id">Student ID</label>
                        <input type="text" id="edit_student_id" name="student_id" value="<?= htmlspecialchars($edit_s['student_id']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Full Name</label>
                        <input type="text" id="edit_name" name="name" value="<?= htmlspecialchars($edit_s['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" value="<?= htmlspecialchars($edit_s['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_phone">Phone Number</label>
                        <input type="text" id="edit_phone" name="phone" value="<?= htmlspecialchars($edit_s['phone']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_department">Department</label>
                        <input type="text" id="edit_department" name="department" value="<?= htmlspecialchars($edit_s['department']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_dob">Date of Birth</label>
                        <input type="date" id="edit_dob" name="dob" value="<?= htmlspecialchars($edit_s['dob']) ?>" required>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1; display: flex; gap: 10px;">
                        <button type="submit" name="edit_student" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Student
                        </button>
                        <a href="manage_students.php" class="btn btn-danger">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Additional confirmation for reset actions
document.addEventListener('DOMContentLoaded', function() {
    const resetAllBtn = document.querySelector('button[name="reset_all_costs"]');
    if (resetAllBtn) {
        resetAllBtn.addEventListener('click', function(e) {
            if (!confirm('⚠️ WARNING: This will reset costs for ALL students. All consultation costs will be set to zero. This action cannot be undone. Are you absolutely sure?')) {
                e.preventDefault();
            }
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const table = document.getElementById('studentsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    function performSearch() {
        const searchText = searchInput.value.toLowerCase();
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length; j++) {
                const cellText = cells[j].textContent.toLowerCase();
                if (cellText.includes(searchText)) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    }
    
    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    // Auto-show modal if edit parameter is present
    <?php if (isset($_GET['edit'])): ?>
        document.getElementById('editModal').style.display = 'flex';
    <?php endif; ?>
});
</script>
</body>
</html>
