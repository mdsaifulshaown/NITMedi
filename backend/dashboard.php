<?php
session_start();
require_once "../db/config.php";
require_once "../includes/functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Consultant') {
    header("Location: index.php");
    exit;
}

// Set Indian timezone
date_default_timezone_set('Asia/Kolkata');

$patient = null;
$consultations = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_type = $_POST['patient_type'] ?? '';

    if ($patient_type === "Student" && !empty($_POST['student_id'])) {
        $patient = getStudentById($_POST['student_id']);
        if ($patient) {
            $consultations = getPreviousConsultations("Student", $patient['student_id']);
        }
    } elseif (($patient_type === "Faculty" || $patient_type === "Staff") && !empty($_POST['email'])) {
        if ($patient_type === "Faculty") {
            $patient = getFacultyByEmail($_POST['email']);
        } else {
            $patient = getStaffByEmail($_POST['email']);
        }
        if ($patient) {
            $consultations = getPreviousConsultations($patient_type, $patient['faculty_id'] ?? $patient['staff_id']);
        }
    }
}

// Get medicines for dropdown
$medicines = getAllMedicines();

// Calculate total cost for previous consultations
$total_previous_cost = 0;
foreach ($consultations as $consultation) {
    $total_previous_cost += floatval($consultation['total_price'] ?? 0);
}

// Get current Indian date and time
$current_date = date('Y-m-d');
$current_time = date('H:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultant Dashboard - NITMedi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
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
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.5s ease;
        }

        .header h1 {
            color: var(--secondary);
            font-size: 1.8rem;
        }

        .header h1 i {
            color: var(--primary);
            margin-right: 10px;
        }

        .logout-btn {
            background: var(--danger);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
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

        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            animation: fadeIn 0.6s ease;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .card h3 {
            color: var(--secondary);
            margin-bottom: 20px;
            font-size: 1.4rem;
            border-bottom: 2px solid var(--light);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h3 i {
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn {
            background: var(--primary);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
        }

        .btn-success:hover {
            background: #219653;
        }

        .btn-danger {
            background: var(--danger);
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-warning {
            background: var(--warning);
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .patient-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            background: var(--light);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .info-label {
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--dark);
            font-size: 1.1rem;
            margin-top: 5px;
        }

        .cost-badge {
            background: var(--warning);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .high-cost-badge {
            background: var(--danger);
        }

        .high-cost-text {
            color: var(--danger);
            font-weight: bold;
        }

        .med-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            align-items: end;
        }

        .med-row select, .med-row input {
            flex: 1;
        }

        .remove-med {
            background: var(--danger);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .remove-med:hover {
            background: #c0392b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: var(--secondary);
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .consultation-table td {
            vertical-align: top;
        }

        .cost-cell {
            font-weight: 600;
        }

        .cost-cell.high-cost {
            color: var(--danger);
            font-weight: bold;
        }

        .priority-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .priority-critical { background: #e74c3c; }
        .priority-high { background: #f39c12; }
        .priority-medium { background: #3498db; }
        .priority-low { background: #27ae60; }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #bdc3c7;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }

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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .med-row {
                flex-direction: column;
            }
            
            .patient-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-md"></i> Consultant Dashboard</h1>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="card">
            <h3><i class="fas fa-search"></i> Search Patient</h3>
            <form method="POST" id="searchForm">
                <div class="form-group">
                    <label for="patient_type">Patient Type</label>
                    <select name="patient_type" id="patient_type" onchange="toggleInput()" required>
                        <option value="">-- Select Patient Type --</option>
                        <option value="Student" <?= ($_POST['patient_type'] ?? '') === 'Student' ? 'selected' : '' ?>>Student</option>
                        <option value="Faculty" <?= ($_POST['patient_type'] ?? '') === 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                        <option value="Staff" <?= ($_POST['patient_type'] ?? '') === 'Staff' ? 'selected' : '' ?>>Staff</option>
                    </select>
                </div>

                <div id="student_box" style="display:none;">
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" name="student_id" id="student_id" 
                               value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>" 
                               placeholder="Enter Student ID">
                    </div>
                </div>

                <div id="email_box" style="display:none;">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               placeholder="Enter Email Address">
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> Search Patient
                </button>
            </form>
        </div>

        <?php if ($patient): ?>
            <div class="grid-2">
                <div class="card">
                    <h3><i class="fas fa-user-injured"></i> Patient Details</h3>
                    <div class="patient-info">
                        <?php foreach($patient as $k=>$v): 
                            if(!is_array($v) && !in_array($k, ['id', 'password'])): ?>
                                <div class="info-item">
                                    <div class="info-label"><?= ucwords(str_replace('_', ' ', $k)) ?></div>
                                    <div class="info-value"><?= htmlspecialchars($v) ?></div>
                                </div>
                            <?php endif;
                        endforeach; ?>
                    </div>
                    
                    <?php if ($total_previous_cost > 0): ?>
                        <div style="margin-top: 20px; text-align: center;">
                            <div class="cost-badge <?= $total_previous_cost > 15000 ? 'high-cost-badge' : '' ?>">
                                <i class="fas fa-money-bill-wave"></i>
                                Total Previous Cost: ₹<?= number_format($total_previous_cost, 2) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3><i class="fas fa-history"></i> Consultation History</h3>
                    <?php if ($consultations): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="consultation-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Disease</th>
                                        <th>Cost</th>
                                        <th>Priority</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($consultations as $c): ?>
                                        <tr>
                                            <td>
                                                <div><strong><?= htmlspecialchars($c['consultation_date']) ?></strong></div>
                                                <small style="color: #666;"><?= htmlspecialchars($c['consultation_time']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($c['disease_name']) ?></td>
                                            <td class="<?= ($c['total_price'] ?? 0) > 15000 ? 'high-cost-text' : 'cost-cell' ?>">
                                                ₹<?= number_format($c['total_price'] ?? 0, 2) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $priority_class = '';
                                                $priority_text = $c['triage_priority'] ?? 'Low';
                                                switch(strtolower($priority_text)) {
                                                    case 'critical': $priority_class = 'priority-critical'; break;
                                                    case 'high': $priority_class = 'priority-high'; break;
                                                    case 'medium': $priority_class = 'priority-medium'; break;
                                                    default: $priority_class = 'priority-low';
                                                }
                                                ?>
                                                <span class="priority-badge <?= $priority_class ?>">
                                                    <?= htmlspecialchars($priority_text) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-file-medical"></i>
                            <p>No previous consultations found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-file-medical-alt"></i> New Consultation</h3>
                <form method="POST" action="submit_consultation.php" id="consultationForm">
                    <input type="hidden" name="patient_type" value="<?= htmlspecialchars($_POST['patient_type']) ?>">
                    <input type="hidden" name="patient_id" value="<?= ($patient['student_id'] ?? $patient['faculty_id'] ?? $patient['staff_id']) ?>">
                    <input type="hidden" name="consultant_id" value="<?= $_SESSION['user_id'] ?>">

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="symptoms">Symptoms</label>
                            <textarea name="symptoms" id="symptoms" rows="4" required placeholder="Describe patient symptoms..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="disease_name">Disease Diagnosis</label>
                            <input type="text" name="disease_name" id="disease_name" required placeholder="Enter disease name">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="consultation_date">Consultation Date</label>
                            <input type="date" name="consultation_date" id="consultation_date" value="<?= $current_date ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="consultation_time">Consultation Time</label>
                            <input type="time" name="consultation_time" id="consultation_time" value="<?= $current_time ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="triage_priority">Triage Priority</label>
                        <select name="triage_priority" id="triage_priority" required>
                            <option value="">-- Select Priority Level --</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>

                    <h4 style="margin: 25px 0 15px 0; color: var(--secondary);">
                        <i class="fas fa-pills"></i> Prescribed Medicines
                    </h4>

                    <div id="medicines-container">
                        <div class="med-row">
                            <select name="medicines[0][medicine_id]" required onchange="calculateTotal()">
                                <option value="">-- Select Medicine --</option>
                                <?php foreach($medicines as $med): ?>
                                    <option value="<?= $med['medicine_id'] ?>" data-price="<?= $med['price'] ?>">
                                        <?= htmlspecialchars($med['name']) ?> (₹<?= number_format($med['price'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="medicines[0][quantity]" placeholder="Quantity" min="1" value="1" required oninput="calculateTotal()">
                            <button type="button" class="remove-med" onclick="this.parentElement.remove(); calculateTotal()">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success" onclick="addMedicine()">
                        <i class="fas fa-plus"></i> Add More Medicines
                    </button>

                    <div style="margin: 20px 0; padding: 15px; background: var(--light); border-radius: 8px;">
                        <strong>Estimated Total Cost: </strong>
                        <span id="totalCost" style="font-weight: bold; color: var(--primary);">₹0.00</span>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="referral_status">Referral Required</label>
                            <select name="referral_status" id="referral_status">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="referral_place">Referral Place (if any)</label>
                            <input type="text" name="referral_place" id="referral_place" placeholder="Hospital or clinic name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="referral_reason">Referral Reason</label>
                        <input type="text" name="referral_reason" id="referral_reason" placeholder="Reason for referral">
                    </div>

                    <div class="form-group">
                        <label for="comments">Additional Comments</label>
                        <textarea name="comments" id="comments" rows="3" placeholder="Any additional notes..."></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">
                        <i class="fas fa-save"></i> Save Consultation
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let medCount = 1;
        
        function toggleInput() {
            const type = document.getElementById("patient_type").value;
            document.getElementById("student_box").style.display = (type === "Student") ? "block" : "none";
            document.getElementById("email_box").style.display = (type === "Faculty" || type === "Staff") ? "block" : "none";
        }

        function addMedicine() {
            const container = document.getElementById('medicines-container');
            const div = document.createElement('div');
            div.className = 'med-row';
            div.innerHTML = `
                <select name="medicines[${medCount}][medicine_id]" required onchange="calculateTotal()">
                    <option value="">-- Select Medicine --</option>
                    <?php foreach($medicines as $med): ?>
                        <option value="<?= $med['medicine_id'] ?>" data-price="<?= $med['price'] ?>">
                            <?= htmlspecialchars($med['name']) ?> (₹<?= number_format($med['price'], 2) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="medicines[${medCount}][quantity]" placeholder="Quantity" min="1" value="1" required oninput="calculateTotal()">
                <button type="button" class="remove-med" onclick="this.parentElement.remove(); calculateTotal()">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(div);
            medCount++;
        }

        function calculateTotal() {
            let total = 0;
            const medicineRows = document.querySelectorAll('.med-row');
            
            medicineRows.forEach(row => {
                const select = row.querySelector('select');
                const quantityInput = row.querySelector('input[type="number"]');
                
                if (select && select.value && quantityInput && quantityInput.value) {
                    const price = parseFloat(select.selectedOptions[0].getAttribute('data-price'));
                    const quantity = parseInt(quantityInput.value);
                    total += price * quantity;
                }
            });
            
            document.getElementById('totalCost').textContent = '₹' + total.toFixed(2);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleInput();
            calculateTotal();
            
            // Add event listeners to existing medicine inputs
            document.querySelectorAll('select[name^="medicines"], input[name^="medicines"]').forEach(element => {
                element.addEventListener('change', calculateTotal);
                element.addEventListener('input', calculateTotal);
            });
        });
    </script>
</body>
</html>
