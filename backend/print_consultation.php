<?php
session_start();
require_once "../db/config.php";
require_once "../includes/functions.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ===============================
// Get Consultation ID
// ===============================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid consultation ID.");
}

$consultation_id = $_GET['id'];

// ===============================
// Fetch Consultation Details
// ===============================
$stmt = $pdo->prepare("SELECT * FROM consultations WHERE consultation_id = ?");
$stmt->execute([$consultation_id]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$consultation) {
    die("Consultation not found.");
}

// ===============================
// Fetch Patient Details
// ===============================
$patient = null;
switch ($consultation['patient_type']) {
    case 'Student':
        $patient = getStudentById($consultation['patient_id']);
        break;
    case 'Faculty':
        $patient = getFacultyById($consultation['patient_id']);
        break;
    case 'Staff':
        $patient = getStaffById($consultation['patient_id']);
        break;
}

// ===============================
// Fetch Consultant Details
// ===============================
$stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
$stmt->execute([$consultation['consultant_id']]);
$consultant = $stmt->fetch(PDO::FETCH_ASSOC);

// ===============================
// Fetch Prescribed Medicines
// ===============================
$stmt = $pdo->prepare("
    SELECT p.*, m.name AS medicine_name 
    FROM prescription p
    JOIN medicines m ON p.medicine_id = m.medicine_id
    WHERE p.consultation_id = ?
");
$stmt->execute([$consultation_id]);
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// Calculate Total Amount
// ===============================
$total_amount = 0;
foreach ($medicines as $med) {
    $total_amount += $med['total_price'];
}

// Base64 encoded logo for printing
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
    <title>Consultation Report - NITMedi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --border: #bdc3c7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        /* Header Styles */
        .header {
            background: var(--primary);
            color: white;
            padding: 25px 30px;
            text-align: center;
            position: relative;
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
        }

        .logo {
            height: 70px;
            width: auto;
            border-radius: 6px;
            object-fit: contain;
        }

        .hospital-info h1 {
            font-size: 2rem;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .hospital-info .tagline {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .report-title {
            background: var(--secondary);
            padding: 12px;
            margin-top: 15px;
            border-radius: 6px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        /* Content Styles */
        .content {
            padding: 30px;
        }

        .section {
            margin-bottom: 25px;
            border: 1px solid var(--light);
            border-radius: 8px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .section-header {
            background: var(--light);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
        }

        .section-header h3 {
            color: var(--primary);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header h3 i {
            color: var(--secondary);
        }

        .section-content {
            padding: 20px;
        }

        /* Grid Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid var(--secondary);
        }

        .info-label {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .info-value {
            color: var(--dark);
            font-size: 1rem;
            font-weight: 500;
        }

        /* Table Styles */
        .medicine-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            font-size: 0.9rem;
        }

        .medicine-table th {
            background: var(--primary);
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .medicine-table td {
            padding: 10px;
            border-bottom: 1px solid var(--light);
            font-size: 0.85rem;
        }

        .medicine-table tr:last-child td {
            border-bottom: none;
        }

        .total-row {
            background: var(--light) !important;
            font-weight: 700;
        }

        .total-row td {
            border-top: 2px solid var(--border);
            font-size: 0.9rem;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }

        .priority-critical { background: #e74c3c; color: white; }
        .priority-high { background: #f39c12; color: white; }
        .priority-medium { background: #3498db; color: white; }
        .priority-low { background: #27ae60; color: white; }

        .referral-yes { background: #e74c3c; color: white; }
        .referral-no { background: #27ae60; color: white; }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            justify-content: center;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #219653;
        }

        /* Footer */
        .footer {
            background: var(--primary);
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }

        .footer-item h4 {
            margin-bottom: 8px;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .signature-area {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            width: 150px;
            height: 1px;
            background: var(--dark);
            margin: 30px auto 8px;
        }

        /* Print Styles - A4 Size Optimization */
        @media print {
            @page {
                size: A4;
                margin: 0.5cm;
            }

            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 12pt;
                line-height: 1.4;
            }
            
            .container {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            
            .action-buttons,
            .btn {
                display: none !important;
            }
            
            .header {
                padding: 15px 20px !important;
                page-break-after: avoid;
            }
            
            .logo {
                height: 60px !important;
            }
            
            .hospital-info h1 {
                font-size: 1.6rem !important;
            }
            
            .report-title {
                font-size: 1.1rem !important;
                padding: 10px !important;
            }
            
            .content {
                padding: 20px !important;
            }
            
            .section {
                margin-bottom: 15px !important;
                border: 1px solid #ccc !important;
                page-break-inside: avoid;
            }
            
            .section-header {
                padding: 12px 15px !important;
            }
            
            .section-header h3 {
                font-size: 1.1rem !important;
            }
            
            .section-content {
                padding: 15px !important;
            }
            
            .grid-2,
            .grid-3 {
                gap: 10px !important;
            }
            
            .info-card {
                padding: 10px !important;
                margin-bottom: 8px !important;
            }
            
            .medicine-table {
                font-size: 10pt !important;
            }
            
            .medicine-table th,
            .medicine-table td {
                padding: 8px 6px !important;
            }
            
            .footer {
                padding: 15px !important;
                margin-top: 20px !important;
                page-break-before: avoid;
            }
            
            .footer-content {
                font-size: 10pt !important;
            }
            
            /* Ensure logo prints */
            .logo {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Hide background gradients in print */
            body, .header, .report-title {
                background: white !important;
                color: black !important;
            }
            
            .header {
                background: #2c3e50 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .report-title {
                background: #3498db !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }
            
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .logo-container {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Ensure logo visibility */
        .logo-fallback {
            display: none;
        }

        .logo:not([src]) + .logo-fallback {
            display: block;
            width: 70px;
            height: 70px;
            background: var(--secondary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <?php if ($logo_data): ?>
                    <img src="<?= $logo_data ?>" alt="NITM Logo" class="logo">
                <?php else: ?>
                    <div class="logo-fallback">NITM LOGO</div>
                <?php endif; ?>
                <div class="hospital-info">
                    <h1>NIT MEDICAL CENTER</h1>
                    <div class="tagline">Quality Healthcare for NIT Community</div>
                </div>
            </div>
            <div class="report-title">
                <i class="fas fa-file-medical-alt"></i>
                MEDICAL CONSULTATION REPORT
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Consultation Info -->
            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-info-circle"></i> Consultation Information</h3>
                </div>
                <div class="section-content">
                    <div class="grid-3">
                        <div class="info-card">
                            <div class="info-label">Consultation ID</div>
                            <div class="info-value">#<?= htmlspecialchars($consultation_id) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Consultation Date</div>
                            <div class="info-value"><?= htmlspecialchars($consultation['consultation_date']) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Consultation Time</div>
                            <div class="info-value"><?= htmlspecialchars($consultation['consultation_time']) ?></div>
                        </div>
                    </div>
                    
                    <div class="grid-2" style="margin-top: 15px;">
                        <div class="info-card">
                            <div class="info-label">Disease Diagnosed</div>
                            <div class="info-value"><?= htmlspecialchars($consultation['disease_name']) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Triage Priority</div>
                            <div class="info-value">
                                <span class="status-badge priority-<?= strtolower($consultation['triage_priority']) ?>">
                                    <?= htmlspecialchars($consultation['triage_priority']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="info-card" style="margin-top: 15px;">
                        <div class="info-label">Symptoms Observed</div>
                        <div class="info-value"><?= htmlspecialchars($consultation['symptoms']) ?></div>
                    </div>

                    <?php if (!empty($consultation['comments'])): ?>
                    <div class="info-card" style="margin-top: 15px;">
                        <div class="info-label">Doctor's Comments</div>
                        <div class="info-value"><?= htmlspecialchars($consultation['comments']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Patient & Consultant Info -->
            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-user-injured"></i> Patient & Consultant Details</h3>
                </div>
                <div class="section-content">
                    <div class="grid-2">
                        <div>
                            <h4 style="color: var(--primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; font-size: 1rem;">
                                <i class="fas fa-user"></i> Patient Information
                            </h4>
                            <?php if ($patient): ?>
                                <div class="info-card">
                                    <div class="info-label">Patient Type</div>
                                    <div class="info-value"><?= htmlspecialchars($consultation['patient_type']) ?></div>
                                </div>
                                <div class="info-card" style="margin-top: 10px;">
                                    <div class="info-label">Full Name</div>
                                    <div class="info-value"><?= htmlspecialchars($patient['name'] ?? 'N/A') ?></div>
                                </div>
                                <div class="info-card" style="margin-top: 10px;">
                                    <div class="info-label">ID Number</div>
                                    <div class="info-value"><?= htmlspecialchars($patient['student_id'] ?? $patient['faculty_id'] ?? $patient['staff_id'] ?? 'N/A') ?></div>
                                </div>
                            <?php else: ?>
                                <div class="info-card">
                                    <div class="info-value" style="color: var(--accent);">Patient details not found</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h4 style="color: var(--primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; font-size: 1rem;">
                                <i class="fas fa-user-md"></i> Consultant Information
                            </h4>
                            <div class="info-card">
                                <div class="info-label">Consultant Name</div>
                                <div class="info-value"><?= htmlspecialchars($consultant['name'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-card" style="margin-top: 10px;">
                                <div class="info-label">Referral Status</div>
                                <div class="info-value">
                                    <span class="status-badge referral-<?= strtolower($consultation['referral_status']) ?>">
                                        <?= htmlspecialchars($consultation['referral_status']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($consultation['referral_status'] === 'Yes'): ?>
                                <div class="info-card" style="margin-top: 10px;">
                                    <div class="info-label">Referral Place</div>
                                    <div class="info-value"><?= htmlspecialchars($consultation['referral_place']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medicine Prescription -->
            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-pills"></i> Medicine Prescription</h3>
                </div>
                <div class="section-content">
                    <?php if ($medicines): ?>
                        <table class="medicine-table">
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Quantity</th>
                                    <th>Unit Price (₹)</th>
                                    <th>Total Price (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($medicines as $med): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($med['medicine_name']) ?></td>
                                        <td><?= htmlspecialchars($med['quantity']) ?></td>
                                        <td>₹<?= number_format($med['unit_price'], 2) ?></td>
                                        <td>₹<?= number_format($med['total_price'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="3" style="text-align: right;"><strong>Grand Total:</strong></td>
                                    <td><strong>₹<?= number_format($total_amount, 2) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="info-card" style="text-align: center;">
                            <div class="info-value">No medicines prescribed for this consultation.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="dashboard.php" class="btn btn-success">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <!-- Signature Area -->
            <div class="signature-area">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="info-label">Consultant's Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="info-label">Patient's Signature</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <div class="footer-item">
                    <h4><i class="fas fa-map-marker-alt"></i> Address</h4>
                    <p>NIT Medical Center<br>National Institute of Technology</p>
                </div>
                <div class="footer-item">
                    <h4><i class="fas fa-phone"></i> Contact</h4>
                    <p>Emergency: +91-XXX-XXXX<br>Email: medical@nit.ac.in</p>
                </div>
            </div>
            <p style="opacity: 0.8; margin-top: 15px; font-size: 0.8rem;">
                <i class="fas fa-shield-alt"></i> This is an electronically generated report.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'true') {
                setTimeout(() => {
                    window.print();
                }, 500);
            }
        });
    </script>
</body>
</html>
