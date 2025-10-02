<?php
session_start();
require_once "../db/config.php";

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$patient_type = $_GET['patient_type'] ?? 'All';
$high_cost_threshold = 15000;

// Build query based on patient type filter
$query = "";
$params = [];

if ($patient_type === 'All') {
    $query = "
        SELECT 'Student' as patient_type, s.student_id as patient_id, s.name, s.email, s.phone, 
               s.department, SUM(c.total_price) as total_cost, COUNT(c.consultation_id) as consultation_count
        FROM students s
        JOIN consultations c ON s.student_id = c.patient_id AND c.patient_type = 'Student'
        WHERE c.total_price > ?
        GROUP BY s.id
        HAVING total_cost > ?
        
        UNION ALL
        
        SELECT 'Faculty' as patient_type, f.faculty_id as patient_id, f.name, f.email, f.phone, 
               f.department, SUM(c.total_price) as total_cost, COUNT(c.consultation_id) as consultation_count
        FROM faculty f
        JOIN consultations c ON f.faculty_id = c.patient_id AND c.patient_type = 'Faculty'
        WHERE c.total_price > ?
        GROUP BY f.id
        HAVING total_cost > ?
        
        UNION ALL
        
        SELECT 'Staff' as patient_type, st.staff_id as patient_id, st.name, st.email, st.phone, 
               st.position as department, SUM(c.total_price) as total_cost, COUNT(c.consultation_id) as consultation_count
        FROM staff st
        JOIN consultations c ON st.staff_id = c.patient_id AND c.patient_type = 'Staff'
        WHERE c.total_price > ?
        GROUP BY st.id
        HAVING total_cost > ?
        
        ORDER BY total_cost DESC
    ";
    $params = array_fill(0, 6, $high_cost_threshold);
} else {
    $table = '';
    $id_field = '';
    
    if ($patient_type === 'Student') {
        $table = 'students';
        $id_field = 'student_id';
    } elseif ($patient_type === 'Faculty') {
        $table = 'faculty';
        $id_field = 'faculty_id';
    } elseif ($patient_type === 'Staff') {
        $table = 'staff';
        $id_field = 'staff_id';
    }
    
    $query = "
        SELECT '$patient_type' as patient_type, t.$id_field as patient_id, t.name, t.email, t.phone, 
               " . ($patient_type === 'Staff' ? 't.position as department' : 't.department') . ",
               SUM(c.total_price) as total_cost, COUNT(c.consultation_id) as consultation_count
        FROM $table t
        JOIN consultations c ON t.$id_field = c.patient_id AND c.patient_type = '$patient_type'
        WHERE c.total_price > ?
        GROUP BY t.id
        HAVING total_cost > ?
        ORDER BY total_cost DESC
    ";
    $params = [$high_cost_threshold, $high_cost_threshold];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$high_cost_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics for the dashboard
$total_high_cost = count($high_cost_patients);
$student_count = count(array_filter($high_cost_patients, function($p) { return $p['patient_type'] === 'Student'; }));
$faculty_count = count(array_filter($high_cost_patients, function($p) { return $p['patient_type'] === 'Faculty'; }));
$staff_count = count(array_filter($high_cost_patients, function($p) { return $p['patient_type'] === 'Staff'; }));
$highest_cost = $total_high_cost > 0 ? max(array_column($high_cost_patients, 'total_cost')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>High Cost Patients - NITMedi</title>
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
            background: var(--danger);
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

        .high-cost {
            color: var(--danger);
            font-weight: 600;
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

        .patient-type-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .student-badge {
            background: rgba(52, 152, 219, 0.15);
            color: #2980b9;
        }

        .faculty-badge {
            background: rgba(155, 89, 182, 0.15);
            color: #8e44ad;
        }

        .staff-badge {
            background: rgba(230, 126, 34, 0.15);
            color: #d35400;
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
            position: relative;
            overflow: hidden;
        }

        .stat-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .stat-card.total:before { background: var(--danger); }
        .stat-card.students:before { background: #3498db; }
        .stat-card.faculty:before { background: #9b59b6; }
        .stat-card.staff:before { background: #e67e22; }
        .stat-card.highest:before { background: var(--warning); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stat-card.total .stat-value { color: var(--danger); }
        .stat-card.students .stat-value { color: #3498db; }
        .stat-card.faculty .stat-value { color: #9b59b6; }
        .stat-card.staff .stat-value { color: #e67e22; }
        .stat-card.highest .stat-value { color: var(--warning); }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--light-gray);
        }

        .filter-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .section-title {
            margin: 25px 0 15px;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.25rem;
        }

        .cost-progress {
            height: 6px;
            background: var(--light-gray);
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }

        .cost-progress-bar {
            height: 100%;
            background: var(--danger);
            border-radius: 3px;
            transition: width 0.5s ease;
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
            
            .form-grid, .filter-form {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<header>
    <h2><i class="fas fa-exclamation-triangle"></i> High Cost Patients</h2>
    <nav>
        <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
        <a href="manage_faculty.php"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
        <a href="manage_staff.php"><i class="fas fa-users"></i> Staff</a>
        <a href="patient_search.php"><i class="fas fa-search"></i> Patient Search</a>
    </nav>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</header>

<div class="container">
    <a href="admin.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    
    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card total">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value"><?= $total_high_cost ?></div>
            <div class="stat-label">High Cost Patients</div>
        </div>
        
        <div class="stat-card students">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-value"><?= $student_count ?></div>
            <div class="stat-label">Students</div>
        </div>
        
        <div class="stat-card faculty">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-value"><?= $faculty_count ?></div>
            <div class="stat-label">Faculty</div>
        </div>
        
        <div class="stat-card staff">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?= $staff_count ?></div>
            <div class="stat-label">Staff</div>
        </div>
        
        <div class="stat-card highest">
            <div class="stat-icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-value">₹<?= number_format($highest_cost, 0) ?></div>
            <div class="stat-label">Highest Cost</div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <h3 class="section-title"><i class="fas fa-filter"></i> Filter Patients</h3>
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="patient_type"><i class="fas fa-user-tag"></i> Patient Type</label>
                <select name="patient_type" id="patient_type">
                    <option value="All" <?= $patient_type == 'All' ? 'selected' : '' ?>>All Patients</option>
                    <option value="Student" <?= $patient_type == 'Student' ? 'selected' : '' ?>>Students</option>
                    <option value="Faculty" <?= $patient_type == 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                    <option value="Staff" <?= $patient_type == 'Staff' ? 'selected' : '' ?>>Staff</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Results Card -->
    <div class="card">
        <h2 class="page-title">
            <i class="fas fa-list"></i> 
            High Cost Patients <?= $patient_type !== 'All' ? "($patient_type)" : '' ?>
            <span style="font-size: 0.8rem; color: var(--gray); font-weight: 400; margin-left: 10px;">
                (Threshold: ₹15,000+)
            </span>
        </h2>
        
        <?php if (empty($high_cost_patients)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h4>No High Cost Patients Found</h4>
                <p>No patients found with total medicine costs exceeding ₹15,000.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Patient Type</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department/Position</th>
                            <th>Consultations</th>
                            <th>Total Cost</th>
                            <th>Cost Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($high_cost_patients as $patient): 
                            $cost_percentage = min(100, ($patient['total_cost'] / 50000) * 100); // Scale to 50,000 for visualization
                        ?>
                            <tr>
                                <td>
                                    <span class="patient-type-badge <?= strtolower($patient['patient_type']) ?>-badge">
                                        <i class="fas fa-<?= 
                                            $patient['patient_type'] === 'Student' ? 'user-graduate' : 
                                            ($patient['patient_type'] === 'Faculty' ? 'chalkboard-teacher' : 'users')
                                        ?>"></i>
                                        <?= $patient['patient_type'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($patient['patient_id']) ?></td>
                                <td><strong><?= htmlspecialchars($patient['name']) ?></strong></td>
                                <td><?= htmlspecialchars($patient['email']) ?></td>
                                <td><?= htmlspecialchars($patient['phone']) ?></td>
                                <td><?= htmlspecialchars($patient['department']) ?></td>
                                <td style="text-align: center;">
                                    <span class="cost-badge" style="background: rgba(67, 97, 238, 0.1); color: var(--primary);">
                                        <?= $patient['consultation_count'] ?>
                                    </span>
                                </td>
                                <td class="high-cost">
                                    <strong>₹<?= number_format($patient['total_cost'], 2) ?></strong>
                                </td>
                                <td style="width: 120px;">
                                    <div class="cost-progress">
                                        <div class="cost-progress-bar" style="width: <?= $cost_percentage ?>%"></div>
                                    </div>
                                    <small style="color: var(--gray);"><?= number_format($cost_percentage, 1) ?>%</small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: rgba(247, 37, 133, 0.05); border-radius: var(--border-radius);">
                <p style="margin: 0; color: var(--danger); font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> 
                    Showing patients with total medicine costs exceeding ₹15,000. 
                    <?= $patient_type === 'All' ? 'Includes all patient types.' : "Filtered by $patient_type type." ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Add animation to table rows
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
        row.style.animation = 'fadeIn 0.5s ease forwards';
        row.style.opacity = '0';
    });
    
    // Animate progress bars
    const progressBars = document.querySelectorAll('.cost-progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 300);
    });
});
</script>
</body>
</html>