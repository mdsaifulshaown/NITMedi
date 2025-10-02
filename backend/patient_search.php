<?php
session_start();
require_once "../db/config.php";

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$search_results = [];
$search_performed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $search_performed = true;
    $patient_type = $_POST['patient_type'];
    $search_value = trim($_POST['search_value']);

    if ($patient_type === 'Student') {
        // Search student by student_id
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(SUM(c.total_price), 0) as total_cost,
                   COUNT(c.consultation_id) as consultation_count
            FROM students s
            LEFT JOIN consultations c ON s.student_id = c.patient_id AND c.patient_type = 'Student'
            WHERE s.student_id = ?
            GROUP BY s.id
        ");
        $stmt->execute([$search_value]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    elseif ($patient_type === 'Faculty') {
        // Search faculty by email
        $stmt = $pdo->prepare("
            SELECT f.*, 
                   COALESCE(SUM(c.total_price), 0) as total_cost,
                   COUNT(c.consultation_id) as consultation_count
            FROM faculty f
            LEFT JOIN consultations c ON f.faculty_id = c.patient_id AND c.patient_type = 'Faculty'
            WHERE f.email = ?
            GROUP BY f.id
        ");
        $stmt->execute([$search_value]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($patient_type === 'Staff') {
        // Search staff by email
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(SUM(c.total_price), 0) as total_cost,
                   COUNT(c.consultation_id) as consultation_count
            FROM staff s
            LEFT JOIN consultations c ON s.staff_id = c.patient_id AND c.patient_type = 'Staff'
            WHERE s.email = ?
            GROUP BY s.id
        ");
        $stmt->execute([$search_value]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Get consultation history for a specific patient
$consultation_history = [];
if (isset($_GET['patient_id']) && isset($_GET['patient_type'])) {
    $patient_id = $_GET['patient_id'];
    $patient_type = $_GET['patient_type'];
    
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as consultant_name
        FROM consultations c
        LEFT JOIN users u ON c.consultant_id = u.user_id
        WHERE c.patient_id = ? AND c.patient_type = ?
        ORDER BY c.consultation_date DESC, c.consultation_time DESC
    ");
    $stmt->execute([$patient_id, $patient_type]);
    $consultation_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Search - NITMedi</title>
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

        .msg-error {
            background: rgba(247, 37, 133, 0.15);
            color: #721c24;
            padding: 15px;
            border-radius: var(--border-radius);
            margin: 15px 0;
            border-left: 4px solid var(--danger);
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

        .patient-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
            transition: var(--transition);
            animation: fadeIn 0.5s ease;
        }

        .patient-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .patient-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .patient-type {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 500;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .info-value {
            font-weight: 500;
            color: var(--dark);
            margin-top: 3px;
        }

        .back-btn {
            background: var(--gray);
            color: white;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
            color: white;
        }

        .search-highlight {
            background: rgba(67, 97, 238, 0.1);
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: 500;
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
            
            .patient-info-grid {
                grid-template-columns: 1fr;
            }
            
            .patient-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        @media (max-width: 480px) {
            .stat-value {
                font-size: 1.5rem;
            }
            
            .patient-name {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
<header>
    <h2><i class="fas fa-search"></i> Patient Search</h2>
    <nav>
        <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
        <a href="manage_faculty.php"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
        <a href="manage_staff.php"><i class="fas fa-users"></i> Staff</a>
        <a href="high_cost_patients.php"><i class="fas fa-exclamation-triangle"></i> High Cost Patients</a>
    </nav>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</header>

<div class="container">
    <a href="admin.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    
    <!-- Search Form -->
    <div class="card">
        <h2 class="page-title"><i class="fas fa-search"></i> Search Patient</h2>
        <form method="POST" class="form-grid">
            <div class="form-group">
                <label for="patient_type"><i class="fas fa-user-tag"></i> Patient Type</label>
                <select name="patient_type" id="patient_type" required>
                    <option value="">Select Patient Type</option>
                    <option value="Student" <?= isset($_POST['patient_type']) && $_POST['patient_type'] == 'Student' ? 'selected' : '' ?>>Student</option>
                    <option value="Faculty" <?= isset($_POST['patient_type']) && $_POST['patient_type'] == 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                    <option value="Staff" <?= isset($_POST['patient_type']) && $_POST['patient_type'] == 'Staff' ? 'selected' : '' ?>>Staff</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="search_value" id="search_label">
                    <i class="fas fa-id-card"></i> 
                    <?= isset($_POST['patient_type']) ? 
                        ($_POST['patient_type'] == 'Student' ? 'Student ID' : 'Email Address') : 
                        'Search Value' ?>
                </label>
                <input type="text" name="search_value" id="search_value" 
                       placeholder="<?= isset($_POST['patient_type']) && $_POST['patient_type'] == 'Student' ? 'Enter Student ID' : 'Enter Email Address' ?>" 
                       value="<?= htmlspecialchars($_POST['search_value'] ?? '') ?>" required>
            </div>
            
            <div class="form-group" style="align-self: end;">
                <button type="submit" name="search" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search Patient
                </button>
            </div>
        </form>
    </div>

    <?php if ($search_performed): ?>
        <div class="card">
            <h2 class="page-title"><i class="fas fa-list"></i> Search Results</h2>
            
            <?php if (empty($search_results)): ?>
                <div class="msg-error">
                    <i class="fas fa-exclamation-circle"></i> No patient found with the provided details.
                </div>
            <?php else: ?>
                <?php foreach ($search_results as $patient): ?>
                    <div class="patient-card">
                        <div class="patient-header">
                            <div class="patient-name"><?= htmlspecialchars($patient['name']) ?></div>
                            <div class="patient-type"><?= htmlspecialchars($_POST['patient_type']) ?></div>
                        </div>
                        
                        <div class="patient-info-grid">
                            <div class="info-item">
                                <div class="info-label">ID</div>
                                <div class="info-value"><?= htmlspecialchars($patient['student_id'] ?? $patient['faculty_id'] ?? $patient['staff_id']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?= htmlspecialchars($patient['email']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?= htmlspecialchars($patient['phone']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><?= $_POST['patient_type'] == 'Student' ? 'Department' : 'Position' ?></div>
                                <div class="info-value"><?= htmlspecialchars($patient['department'] ?? $patient['position']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Total Consultations</div>
                                <div class="info-value"><?= $patient['consultation_count'] ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Total Medicine Cost</div>
                                <div class="info-value">
                                    <span class="cost-badge <?= $patient['total_cost'] > 15000 ? 'high' : 'normal' ?>">
                                        <i class="fas fa-rupee-sign"></i> <?= number_format($patient['total_cost'], 2) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <a href="patient_search.php?patient_id=<?= $patient['student_id'] ?? $patient['faculty_id'] ?? $patient['staff_id'] ?>&patient_type=<?= $_POST['patient_type'] ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-file-medical"></i> View Consultation History
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($consultation_history)): ?>
        <div class="card">
            <div class="patient-header">
                <h2 class="page-title">
                    <i class="fas fa-file-medical-alt"></i> Consultation History
                </h2>
                <div class="patient-type">
                    <?= htmlspecialchars($_GET['patient_type']) ?>: <?= htmlspecialchars($_GET['patient_id']) ?>
                </div>
            </div>
            
            <?php if (empty($consultation_history)): ?>
                <div class="msg-error">
                    <i class="fas fa-exclamation-circle"></i> No consultation history found.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Consultant</th>
                                <th>Disease</th>
                                <th>Symptoms</th>
                                <th>Medicine Cost</th>
                                <th>Triage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultation_history as $consultation): ?>
                                <tr>
                                    <td><?= htmlspecialchars($consultation['consultation_date']) ?></td>
                                    <td><?= htmlspecialchars($consultation['consultation_time']) ?></td>
                                    <td><?= htmlspecialchars($consultation['consultant_name']) ?></td>
                                    <td><?= htmlspecialchars($consultation['disease_name']) ?></td>
                                    <td><?= htmlspecialchars($consultation['symptoms']) ?></td>
                                    <td>
                                        <span class="cost-badge <?= $consultation['total_price'] > 15000 ? 'high' : 'normal' ?>">
                                            <i class="fas fa-rupee-sign"></i> <?= number_format($consultation['total_price'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cost-badge <?= $consultation['triage_priority'] == 'High' ? 'high' : 'normal' ?>">
                                            <?= htmlspecialchars($consultation['triage_priority']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Dynamic label change based on patient type
document.getElementById('patient_type').addEventListener('change', function() {
    const patientType = this.value;
    const searchLabel = document.getElementById('search_label');
    const searchInput = document.getElementById('search_value');
    
    if (patientType === 'Student') {
        searchLabel.innerHTML = '<i class="fas fa-id-card"></i> Student ID';
        searchInput.placeholder = 'Enter Student ID';
    } else if (patientType === 'Faculty' || patientType === 'Staff') {
        searchLabel.innerHTML = '<i class="fas fa-envelope"></i> Email Address';
        searchInput.placeholder = 'Enter Email Address';
    } else {
        searchLabel.innerHTML = '<i class="fas fa-search"></i> Search Value';
        searchInput.placeholder = 'Enter Search Value';
    }
});

// Add animation to search results
document.addEventListener('DOMContentLoaded', function() {
    const patientCards = document.querySelectorAll('.patient-card');
    patientCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>
</body>
</html>
