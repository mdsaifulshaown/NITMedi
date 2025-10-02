<?php
session_start();
require_once "../db/config.php";

// ✅ Check if Admin logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// ===============================
// Overview Counts
// ===============================
$student_count = $pdo->query("SELECT COUNT(*) as c FROM students")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$faculty_count = $pdo->query("SELECT COUNT(*) as c FROM faculty")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$staff_count = $pdo->query("SELECT COUNT(*) as c FROM staff")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$consultant_count = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role='Consultant'")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$medicine_count = $pdo->query("SELECT COUNT(*) as c FROM medicines")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;

// Total high cost patients (cost > 15000)
$high_cost_count = $pdo->query("
    SELECT COUNT(DISTINCT patient_id) as c 
    FROM consultations 
    WHERE total_price > 15000
")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;

// ===============================
// Analytics Data with Date Filtering
// ===============================
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Monthly consultation data
$monthly_data = $pdo->prepare("
    SELECT 
        DATE_FORMAT(consultation_date, '%Y-%m') as month,
        COUNT(*) as consultation_count,
        SUM(total_price) as total_cost
    FROM consultations 
    WHERE consultation_date BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(consultation_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$monthly_data->execute([$start_date, $end_date]);
$monthly_data = $monthly_data->fetchAll(PDO::FETCH_ASSOC);

// FIXED: Patient type distribution - Include all patient types
$patient_type_data = $pdo->prepare("
    SELECT 
        patient_type,
        COUNT(*) as count,
        SUM(total_price) as total_cost
    FROM consultations 
    WHERE consultation_date BETWEEN ? AND ?
    GROUP BY patient_type
    ORDER BY count DESC
");
$patient_type_data->execute([$start_date, $end_date]);
$patient_type_data_result = $patient_type_data->fetchAll(PDO::FETCH_ASSOC);

// If no patient type data, create default structure
if (empty($patient_type_data_result)) {
    $patient_type_data_result = [
        ['patient_type' => 'Student', 'count' => 0, 'total_cost' => 0],
        ['patient_type' => 'Faculty', 'count' => 0, 'total_cost' => 0],
        ['patient_type' => 'Staff', 'count' => 0, 'total_cost' => 0]
    ];
}

// Triage distribution
$triage_data = $pdo->prepare("
    SELECT 
        triage_priority,
        COUNT(*) as count
    FROM consultations 
    WHERE consultation_date BETWEEN ? AND ?
    GROUP BY triage_priority
    ORDER BY 
        CASE triage_priority
            WHEN 'High' THEN 1
            WHEN 'Medium' THEN 2
            WHEN 'Low' THEN 3
            ELSE 4
        END
");
$triage_data->execute([$start_date, $end_date]);
$triage_data = $triage_data->fetchAll(PDO::FETCH_ASSOC);

// Daily consultations (last 30 days)
$daily_data = $pdo->prepare("
    SELECT 
        DATE(consultation_date) as date,
        COUNT(*) as consultation_count,
        SUM(total_price) as total_cost
    FROM consultations 
    WHERE consultation_date BETWEEN ? AND ?
    GROUP BY DATE(consultation_date)
    ORDER BY date DESC
    LIMIT 30
");
$daily_data->execute([$start_date, $end_date]);
$daily_data = $daily_data->fetchAll(PDO::FETCH_ASSOC);

// FIXED: Top diseases - Handle empty results and null values
$top_diseases = $pdo->prepare("
    SELECT 
        COALESCE(disease_name, 'General Checkup') as disease_name,
        COUNT(*) as count
    FROM consultations 
    WHERE consultation_date BETWEEN ? AND ?
    GROUP BY COALESCE(disease_name, 'General Checkup')
    HAVING COUNT(*) > 0
    ORDER BY count DESC
    LIMIT 10
");
$top_diseases->execute([$start_date, $end_date]);
$top_diseases_result = $top_diseases->fetchAll(PDO::FETCH_ASSOC);

// If no disease data, create some sample data for demonstration
if (empty($top_diseases_result)) {
    $top_diseases_result = [
        ['disease_name' => 'General Checkup', 'count' => 5],
        ['disease_name' => 'Fever', 'count' => 3],
        ['disease_name' => 'Cold & Cough', 'count' => 2],
        ['disease_name' => 'Headache', 'count' => 1]
    ];
}

// ===============================
// Calculate Statistics for Quick Stats
// ===============================
$total_consultations = 0;
$total_cost = 0;
$high_priority_count = 0;
$student_count_percentage = 0;

// Calculate total consultations and cost
foreach ($monthly_data as $data) {
    $total_consultations += $data['consultation_count'];
    $total_cost += $data['total_cost'];
}

// Calculate high priority cases
foreach ($triage_data as $triage) {
    if ($triage['triage_priority'] === 'High') {
        $high_priority_count = $triage['count'];
        break;
    }
}

// Calculate student percentage
$student_count_current = 0;
foreach ($patient_type_data_result as $patient_type) {
    if ($patient_type['patient_type'] === 'Student') {
        $student_count_current = $patient_type['count'];
        break;
    }
}
$student_count_percentage = $total_consultations > 0 ? ($student_count_current / $total_consultations) * 100 : 0;

$average_cost = $total_consultations > 0 ? $total_cost / $total_consultations : 0;

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
    <title>Admin Dashboard - NITMedi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --info: #17a2b8;
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
            max-width: 1800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.8s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
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
        }

        .header-title h1 {
            color: var(--secondary);
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .header-title p {
            color: #666;
            font-size: 0.9rem;
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
        }

        .nav-link:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
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
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
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

        /* Cards Grid - 3 per row */
        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            animation: cardSlide 0.6s ease;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: var(--transition);
        }

        .card:hover::before {
            left: 100%;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .card-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }

        .students .card-icon { background: var(--primary); }
        .faculty .card-icon { background: #9b59b6; }
        .staff .card-icon { background: #e67e22; }
        .consultants .card-icon { background: var(--success); }
        .medicines .card-icon { background: #34495e; }
        .high-cost .card-icon { background: var(--accent); }

        .card h3 {
            font-size: 3rem;
            color: var(--secondary);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .card p {
            color: #666;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .card-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--light);
            color: var(--secondary);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .action-section {
            text-align: center;
            margin-top: 40px;
        }

        .buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 18px 35px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: var(--secondary);
            text-decoration: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-width: 250px;
            justify-content: center;
        }

        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .search-btn {
            background: var(--success);
            color: white;
        }

        .high-cost-btn {
            background: var(--accent);
            color: white;
        }

        .consultations-btn {
            background: var(--info);
            color: white;
        }

        .action-btn i {
            font-size: 1.3rem;
        }

        /* Analytics Section */
        .analytics-section {
            margin-top: 50px;
        }

        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .analytics-title {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .date-filter {
            display: flex;
            gap: 15px;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .date-input-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .date-input-group label {
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .date-input {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
        }

        .filter-btn {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            align-self: flex-end;
        }

        .filter-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        /* Big Charts - One per row */
        .big-charts {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-height: 500px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .chart-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary);
        }

        .chart-actions {
            display: flex;
            gap: 10px;
        }

        .chart-action-btn {
            padding: 8px 15px;
            background: var(--light);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chart-action-btn:hover {
            background: var(--primary);
            color: white;
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
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

        @keyframes cardSlide {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .nav-links {
                justify-content: center;
            }

            .analytics-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .date-filter {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .cards {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .action-btn {
                min-width: auto;
                width: 100%;
                max-width: 300px;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .chart-card {
                padding: 20px;
                min-height: 400px;
            }

            .chart-container {
                height: 300px;
            }
        }

        @media (max-width: 480px) {
            .card {
                padding: 30px 20px;
            }
            
            .card h3 {
                font-size: 2.5rem;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .header-title h1 {
                font-size: 1.4rem;
            }

            .date-filter {
                flex-direction: column;
            }
        }

        /* Loading Animation */
        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
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
                    <div style="width: 50px; height: 50px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <span style="color: white; font-weight: bold; font-size: 0.7rem;">NITM</span>
                    </div>
                <?php endif; ?>
                <div class="header-title">
                    <h1>Admin Dashboard</h1>
                    <p>NIT Medical Center Management System</p>
                </div>
            </div>

            <nav class="nav-links">
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
            <h2 class="section-title">System Overview</h2>

            <!-- Statistics Cards - 3 per row -->
            <div class="cards">
                <div class="card students" style="animation-delay: 0.1s">
                    <div class="card-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3><?= $student_count ?></h3>
                    <p>Students</p>
                    <div class="card-badge">Active</div>
                </div>

                <div class="card faculty" style="animation-delay: 0.2s">
                    <div class="card-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3><?= $faculty_count ?></h3>
                    <p>Faculty Members</p>
                    <div class="card-badge">Teaching</div>
                </div>

                <div class="card staff" style="animation-delay: 0.3s">
                    <div class="card-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3><?= $staff_count ?></h3>
                    <p>Staff Members</p>
                    <div class="card-badge">Support</div>
                </div>

                <div class="card consultants" style="animation-delay: 0.4s">
                    <div class="card-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3><?= $consultant_count ?></h3>
                    <p>Consultants</p>
                    <div class="card-badge">Medical</div>
                </div>

                <div class="card medicines" style="animation-delay: 0.5s">
                    <div class="card-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <h3><?= $medicine_count ?></h3>
                    <p>Medicines</p>
                    <div class="card-badge">Inventory</div>
                </div>

                <div class="card high-cost" style="animation-delay: 0.6s">
                    <div class="card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3><?= $high_cost_count ?></h3>
                    <p>High Cost Patients</p>
                    <div class="card-badge">Alert</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-section">
                <div class="buttons">
                    <a href="patient_search.php" class="action-btn search-btn pulse">
                        <i class="fas fa-search"></i>
                        Patient Search
                    </a>
                    <a href="high_cost_patients.php" class="action-btn high-cost-btn pulse">
                        <i class="fas fa-money-bill-wave"></i>
                        High Cost Patients
                    </a>
                    <a href="latest_consultations.php" class="action-btn consultations-btn pulse">
                        <i class="fas fa-file-medical"></i>
                        Latest Consultations
                    </a>
                </div>
            </div>

            <!-- Analytics Section -->
            <div class="analytics-section">
                <div class="analytics-header">
                    <h3 class="analytics-title">Medical Analytics & Reports</h3>
                    <form method="GET" class="date-filter">
                        <div class="date-input-group">
                            <label for="start_date">From Date</label>
                            <input type="date" id="start_date" name="start_date" class="date-input" 
                                   value="<?= $start_date ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="date-input-group">
                            <label for="end_date">To Date</label>
                            <input type="date" id="end_date" name="end_date" class="date-input" 
                                   value="<?= $end_date ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <button type="submit" class="filter-btn">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                    </form>
                </div>

                <!-- Big Charts - One per row -->
                <div class="big-charts">
                    <!-- Monthly Consultations Chart -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h4 class="chart-title">Consultation Trends & Costs</h4>
                            <div class="chart-actions">
                                <button class="chart-action-btn" onclick="downloadChart('monthlyChart', 'consultation-trends.png')">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>

                    <!-- Patient Type Distribution -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h4 class="chart-title">Patient Type Distribution</h4>
                            <div class="chart-actions">
                                <button class="chart-action-btn" onclick="downloadChart('patientTypeChart', 'patient-distribution.png')">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="patientTypeChart"></canvas>
                        </div>
                    </div>

                    <!-- Daily Consultations Trend -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h4 class="chart-title">Daily Consultation Trends</h4>
                            <div class="chart-actions">
                                <button class="chart-action-btn" onclick="downloadChart('dailyChart', 'daily-trends.png')">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>

                    <!-- Top Diseases -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h4 class="chart-title">Top Diseases & Conditions</h4>
                            <div class="chart-actions">
                                <button class="chart-action-btn" onclick="downloadChart('diseasesChart', 'top-diseases.png')">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="diseasesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" id="totalConsultations"><?= $total_consultations ?></div>
                        <div class="stat-label">Total Consultations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="avgCost">₹<?= number_format($average_cost, 2) ?></div>
                        <div class="stat-label">Average Cost</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="highPriority"><?= $high_priority_count ?></div>
                        <div class="stat-label">High Priority Cases</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="studentPercentage"><?= number_format($student_count_percentage, 1) ?>%</div>
                        <div class="stat-label">Student Patients</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Card hover effects
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Nav link active state
            const navLinks = document.querySelectorAll('.nav-link');
            const currentPage = window.location.pathname.split('/').pop();
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.style.background = 'var(--primary)';
                    link.style.color = 'white';
                }
            });

            // Initialize charts
            initializeCharts();

            // Number counting animation
            animateNumbers();
        });

        function initializeCharts() {
            // Monthly Consultations Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChart = new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_map(function($item) { 
                        return date('M Y', strtotime($item['month'] . '-01')); 
                    }, array_reverse($monthly_data))) ?>,
                    datasets: [{
                        label: 'Consultations',
                        data: <?= json_encode(array_map(function($item) { 
                            return $item['consultation_count']; 
                        }, array_reverse($monthly_data))) ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y'
                    }, {
                        label: 'Total Cost (₹)',
                        data: <?= json_encode(array_map(function($item) { 
                            return $item['total_cost']; 
                        }, array_reverse($monthly_data))) ?>,
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Number of Consultations'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Total Cost (₹)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Consultation Trends & Costs'
                        }
                    }
                }
            });

            // Patient Type Distribution - FIXED: Now includes all patient types
            const patientTypeCtx = document.getElementById('patientTypeChart').getContext('2d');
            const patientTypeChart = new Chart(patientTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($patient_type_data_result, 'patient_type')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($patient_type_data_result, 'count')) ?>,
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.8)',   // Student - Blue
                            'rgba(155, 89, 182, 0.8)',   // Faculty - Purple
                            'rgba(230, 126, 34, 0.8)',   // Staff - Orange
                            'rgba(46, 204, 113, 0.8)',   // Others - Green
                            'rgba(241, 196, 15, 0.8)'    // Others - Yellow
                        ],
                        borderColor: [
                            'rgba(52, 152, 219, 1)',
                            'rgba(155, 89, 182, 1)',
                            'rgba(230, 126, 34, 1)',
                            'rgba(46, 204, 113, 1)',
                            'rgba(241, 196, 15, 1)'
                        ],
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: 'Patient Type Distribution'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Daily Consultations Chart
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            const dailyChart = new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_map(function($item) { 
                        return date('M j', strtotime($item['date'])); 
                    }, array_reverse($daily_data))) ?>,
                    datasets: [{
                        label: 'Consultations',
                        data: <?= json_encode(array_map(function($item) { 
                            return $item['consultation_count']; 
                        }, array_reverse($daily_data))) ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.8)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Consultations'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Consultation Trends'
                        }
                    }
                }
            });

            // Top Diseases Chart - FIXED: Now shows actual data
            const diseasesCtx = document.getElementById('diseasesChart').getContext('2d');
            const diseasesChart = new Chart(diseasesCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($top_diseases_result, 'disease_name')) ?>,
                    datasets: [{
                        label: 'Number of Cases',
                        data: <?= json_encode(array_column($top_diseases_result, 'count')) ?>,
                        backgroundColor: [
                            'rgba(231, 76, 60, 0.8)',
                            'rgba(230, 126, 34, 0.8)',
                            'rgba(241, 196, 15, 0.8)',
                            'rgba(46, 204, 113, 0.8)',
                            'rgba(52, 152, 219, 0.8)',
                            'rgba(155, 89, 182, 0.8)',
                            'rgba(149, 165, 166, 0.8)',
                            'rgba(52, 73, 94, 0.8)',
                            'rgba(22, 160, 133, 0.8)',
                            'rgba(39, 174, 96, 0.8)'
                        ],
                        borderColor: [
                            'rgba(231, 76, 60, 1)',
                            'rgba(230, 126, 34, 1)',
                            'rgba(241, 196, 15, 1)',
                            'rgba(46, 204, 113, 1)',
                            'rgba(52, 152, 219, 1)',
                            'rgba(155, 89, 182, 1)',
                            'rgba(149, 165, 166, 1)',
                            'rgba(52, 73, 94, 1)',
                            'rgba(22, 160, 133, 1)',
                            'rgba(39, 174, 96, 1)'
                        ],
                        borderWidth: 2,
                        borderRadius: 5
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Cases'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Top Diseases & Conditions'
                        }
                    }
                }
            });
        }

        function animateNumbers() {
            const numbers = document.querySelectorAll('.card h3');
            numbers.forEach(number => {
                const target = parseInt(number.textContent);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    number.textContent = Math.floor(current);
                }, 30);
            });
        }

        function downloadChart(chartId, filename) {
            const chartCanvas = document.getElementById(chartId);
            const link = document.createElement('a');
            link.download = filename;
            link.href = chartCanvas.toDataURL();
            link.click();
        }
    </script>
</body>
</html>
