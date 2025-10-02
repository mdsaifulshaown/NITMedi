<?php
session_start();
require_once "../db/config.php";

// Check if Admin logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// ===============================
// Fetch Latest Consultations
// ===============================
try {
    $latest_consults = $pdo->query("SELECT * FROM consultations ORDER BY consultation_id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching consultations: " . $e->getMessage());
}

// ===============================
// Helper: Get patient name by type
// ===============================
function getPatientName($type, $id, $pdo) {
    if($type === "Student"){
        $stmt = $pdo->prepare("SELECT name FROM students WHERE student_id=?");
    } elseif($type === "Faculty"){
        $stmt = $pdo->prepare("SELECT name FROM faculty WHERE faculty_id=?");
    } elseif($type === "Staff"){
        $stmt = $pdo->prepare("SELECT name FROM staff WHERE staff_id=?");
    } else return "Unknown";
    
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['name'] : "Unknown";
}

// ===============================
// Helper: Get total medicine cost for consultation
// ===============================
function getTotalMedicineCost($consultation_id, $pdo){
    $stmt = $pdo->prepare("SELECT SUM(total_price) as total FROM prescription WHERE consultation_id=?");
    $stmt->execute([$consultation_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['total'] : 0;
}

// Get statistics for the dashboard
$total_consultations = count($latest_consults);
$total_cost = 0;
$patient_type_counts = ['Student' => 0, 'Faculty' => 0, 'Staff' => 0];

foreach ($latest_consults as $consult) {
    $cost = getTotalMedicineCost($consult['consultation_id'], $pdo);
    $total_cost += $cost;
    if (isset($patient_type_counts[$consult['patient_type']])) {
        $patient_type_counts[$consult['patient_type']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latest Consultations - NITMedi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .stat-card.total:before { background: var(--primary); }
        .stat-card.students:before { background: #3498db; }
        .stat-card.faculty:before { background: #9b59b6; }
        .stat-card.staff:before { background: #e67e22; }
        .stat-card.cost:before { background: var(--success); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stat-card.total .stat-value { color: var(--primary); }
        .stat-card.students .stat-value { color: #3498db; }
        .stat-card.faculty .stat-value { color: #9b59b6; }
        .stat-card.staff .stat-value { color: #e67e22; }
        .stat-card.cost .stat-value { color: var(--success); }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .consultation-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 15px;
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }

        .consultation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .consultation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .consultation-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .consultation-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .consultation-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .detail-item {
            margin-bottom: 8px;
        }

        .detail-label {
            font-weight: 500;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .detail-value {
            font-weight: 500;
            color: var(--dark);
            margin-top: 3px;
        }

        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .toggle-btn {
            padding: 10px 20px;
            background: var(--light-gray);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .toggle-btn.active {
            background: var(--primary);
            color: white;
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
            
            .stats-container {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .consultation-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .consultation-meta {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .consultation-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<header>
    <h2><i class="fas fa-file-medical"></i> Latest Consultations</h2>
    <nav>
        <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
        <a href="manage_faculty.php"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
        <a href="manage_staff.php"><i class="fas fa-users"></i> Staff</a>
        <a href="patient_search.php"><i class="fas fa-search"></i> Search</a>
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
                <i class="fas fa-file-medical"></i>
            </div>
            <div class="stat-value"><?= $total_consultations ?></div>
            <div class="stat-label">Recent Consultations</div>
        </div>
        
        <div class="stat-card students">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-value"><?= $patient_type_counts['Student'] ?></div>
            <div class="stat-label">Students</div>
        </div>
        
        <div class="stat-card faculty">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-value"><?= $patient_type_counts['Faculty'] ?></div>
            <div class="stat-label">Faculty</div>
        </div>
        
        <div class="stat-card staff">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?= $patient_type_counts['Staff'] ?></div>
            <div class="stat-label">Staff</div>
        </div>
        
        <div class="stat-card cost">
            <div class="stat-icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-value">₹<?= number_format($total_cost, 0) ?></div>
            <div class="stat-label">Total Cost</div>
        </div>
    </div>

    <!-- View Toggle -->
    <div class="view-toggle">
        <button class="toggle-btn active" id="tableViewBtn">
            <i class="fas fa-table"></i> Table View
        </button>
        <button class="toggle-btn" id="cardViewBtn">
            <i class="fas fa-th-large"></i> Card View
        </button>
    </div>

    <!-- Table View -->
    <div class="card" id="tableView">
        <h2 class="page-title"><i class="fas fa-table"></i> Recent Consultations</h2>
        
        <?php if (empty($latest_consults)): ?>
            <div style="text-align: center; padding: 40px; color: var(--gray);">
                <i class="fas fa-file-medical-alt" style="font-size: 3rem; margin-bottom: 15px; color: var(--light-gray);"></i>
                <h4>No Consultations Found</h4>
                <p>No recent consultations available.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>Patient Type</th>
                            <th>Consultant ID</th>
                            <th>Disease</th>
                            <th>Date</th>
                            <th>Medicine Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest_consults as $c): 
                            $cost = getTotalMedicineCost($c['consultation_id'], $pdo);
                        ?>
                            <tr>
                                <td><strong>#<?= $c['consultation_id'] ?></strong></td>
                                <td><?= htmlspecialchars(getPatientName($c['patient_type'], $c['patient_id'], $pdo)) ?></td>
                                <td>
                                    <span class="patient-type-badge <?= strtolower($c['patient_type']) ?>-badge">
                                        <i class="fas fa-<?= 
                                            $c['patient_type'] === 'Student' ? 'user-graduate' : 
                                            ($c['patient_type'] === 'Faculty' ? 'chalkboard-teacher' : 'users')
                                        ?>"></i>
                                        <?= $c['patient_type'] ?>
                                    </span>
                                </td>
                                <td><?= $c['consultant_id'] ?></td>
                                <td><?= htmlspecialchars($c['disease_name']) ?></td>
                                <td><?= $c['consultation_date'] ?></td>
                                <td>
                                    <span class="cost-badge <?= $cost > 5000 ? 'high' : 'normal' ?>">
                                        <i class="fas fa-rupee-sign"></i> <?= number_format($cost, 2) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Card View -->
    <div class="card" id="cardView" style="display: none;">
        <h2 class="page-title"><i class="fas fa-th-large"></i> Recent Consultations</h2>
        
        <?php if (empty($latest_consults)): ?>
            <div style="text-align: center; padding: 40px; color: var(--gray);">
                <i class="fas fa-file-medical-alt" style="font-size: 3rem; margin-bottom: 15px; color: var(--light-gray);"></i>
                <h4>No Consultations Found</h4>
                <p>No recent consultations available.</p>
            </div>
        <?php else: ?>
            <div class="consultations-grid">
                <?php foreach ($latest_consults as $c): 
                    $cost = getTotalMedicineCost($c['consultation_id'], $pdo);
                    $patient_name = htmlspecialchars(getPatientName($c['patient_type'], $c['patient_id'], $pdo));
                ?>
                    <div class="consultation-card">
                        <div class="consultation-header">
                            <div class="consultation-title">
                                Consultation #<?= $c['consultation_id'] ?>
                            </div>
                            <div class="consultation-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <?= $c['consultation_date'] ?>
                                </div>
                                <div class="meta-item">
                                    <span class="patient-type-badge <?= strtolower($c['patient_type']) ?>-badge">
                                        <?= $c['patient_type'] ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="consultation-details">
                            <div class="detail-item">
                                <div class="detail-label">Patient</div>
                                <div class="detail-value"><?= $patient_name ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Consultant ID</div>
                                <div class="detail-value"><?= $c['consultant_id'] ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Disease</div>
                                <div class="detail-value"><?= htmlspecialchars($c['disease_name']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Medicine Cost</div>
                                <div class="detail-value">
                                    <span class="cost-badge <?= $cost > 5000 ? 'high' : 'normal' ?>">
                                        <i class="fas fa-rupee-sign"></i> <?= number_format($cost, 2) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// View toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const tableViewBtn = document.getElementById('tableViewBtn');
    const cardViewBtn = document.getElementById('cardViewBtn');
    const tableView = document.getElementById('tableView');
    const cardView = document.getElementById('cardView');
    
    tableViewBtn.addEventListener('click', function() {
        tableView.style.display = 'block';
        cardView.style.display = 'none';
        tableViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
    });
    
    cardViewBtn.addEventListener('click', function() {
        tableView.style.display = 'none';
        cardView.style.display = 'block';
        cardViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
    });
    
    // Add animation to table rows
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
        row.style.animation = 'fadeIn 0.5s ease forwards';
        row.style.opacity = '0';
    });
    
    // Add animation to consultation cards
    const consultationCards = document.querySelectorAll('.consultation-card');
    consultationCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>
</body>
</html>
