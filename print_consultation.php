<?php
session_start();
require_once "db/config.php";
require_once "includes/functions.php";

// ✅ Check login
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Consultation Details</title>
    <style>
        body { font-family: Arial, sans-serif; margin:20px; background:#f9f9f9; }
        .box { background:#fff; padding:20px; margin-bottom:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        h2,h3 { margin-top:0; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid #ccc; padding:8px; text-align:left; }
        th { background:#f2f2f2; }
        .print-btn { padding:10px 20px; background:#4CAF50; color:#fff; border:none; border-radius:5px; cursor:pointer; margin-bottom:20px; }
        .print-btn:hover { background:#45a049; }
    </style>
    <script>
        function printPage() {
            window.print();
        }
    </script>
</head>
<body>
    <h2>Consultation Details</h2>
    <button class="print-btn" onclick="printPage()">Print</button>

    <div class="box">
        <h3>Patient Details</h3>
        <?php if ($patient): ?>
            <?php foreach($patient as $k=>$v){ echo "<p><b>".ucfirst($k).":</b> ".htmlspecialchars($v)."</p>"; } ?>
        <?php else: ?>
            <p>Patient details not found.</p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h3>Consultation Info</h3>
        <p><b>Disease:</b> <?= htmlspecialchars($consultation['disease_name']) ?></p>
        <p><b>Symptoms:</b> <?= htmlspecialchars($consultation['symptoms']) ?></p>
        <p><b>Comments:</b> <?= htmlspecialchars($consultation['comments']) ?></p>
        <p><b>Date:</b> <?= htmlspecialchars($consultation['consultation_date']) ?></p>
        <p><b>Time:</b> <?= htmlspecialchars($consultation['consultation_time']) ?></p>
        <p><b>Triage / Priority:</b> <?= htmlspecialchars($consultation['triage_priority']) ?></p>
        <p><b>Referral Status:</b> <?= htmlspecialchars($consultation['referral_status']) ?></p>
        <?php if ($consultation['referral_status'] === 'Yes'): ?>
            <p><b>Referral Place:</b> <?= htmlspecialchars($consultation['referral_place']) ?></p>
            <p><b>Referral Reason:</b> <?= htmlspecialchars($consultation['referral_reason']) ?></p>
        <?php endif; ?>
        <p><b>Medicine Total Cost:</b> ₹<?= htmlspecialchars(number_format($consultation['total_price'],2)) ?></p>
    </div>

    <div class="box">
        <h3>Prescribed Medicines</h3>
        <?php if ($medicines): ?>
            <table>
                <tr>
                    <th>Medicine</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Price</th>
                </tr>
                <?php foreach($medicines as $med): ?>
                    <tr>
                        <td><?= htmlspecialchars($med['medicine_name']) ?></td>
                        <td><?= htmlspecialchars($med['quantity']) ?></td>
                        <td>₹<?= htmlspecialchars(number_format($med['unit_price'],2)) ?></td>
                        <td>₹<?= htmlspecialchars(number_format($med['total_price'],2)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="3" style="text-align:right;">Total Amount</th>
                    <th>₹<?= htmlspecialchars(number_format($total_amount,2)) ?></th>
                </tr>
            </table>
        <?php else: ?>
            <p>No medicines prescribed.</p>
        <?php endif; ?>
    </div>
</body>
</html>
