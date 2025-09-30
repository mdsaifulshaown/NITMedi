<?php

require_once __DIR__ . '/../db/config.example.php';

require_once __DIR__ . '/../includes/functions.php';

$patient = null;
$consultations = [];
$message = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $patient_type = $_POST['patient_type'] ?? null;

    if ($patient_type === "Student" && !empty($_POST['student_id'])) {
        $patient = getStudentById($_POST['student_id']); // from functions.php
        if ($patient) {
            $consultations = getPreviousConsultations("Student", $patient['student_id']);
        } else {
            $message = "No student found with ID: " . htmlspecialchars($_POST['student_id']);
        }
    }

    if ($patient_type === "Faculty" && !empty($_POST['faculty_id'])) {
        $patient = getFacultyById($_POST['faculty_id']);
        if ($patient) {
            $consultations = getPreviousConsultations("Faculty", $patient['faculty_id']);
        } else {
            $message = "No faculty found with ID: " . htmlspecialchars($_POST['faculty_id']);
        }
    }

    if ($patient_type === "Staff" && !empty($_POST['staff_id'])) {
        $patient = getStaffById($_POST['staff_id']);
        if ($patient) {
            $consultations = getPreviousConsultations("Staff", $patient['staff_id']);
        } else {
            $message = "No staff found with ID: " . htmlspecialchars($_POST['staff_id']);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Medical Center</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .search-box { padding: 15px; border: 1px solid #ddd; margin-bottom: 20px; }
        .results, .consultations { margin-top: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .message { color: red; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Medical Center Dashboard</h2>

    <div class="search-box">
        <form method="POST" action="">
            <label>Patient Type:</label>
            <select name="patient_type" required>
                <option value="">--Select--</option>
                <option value="Student" <?= (($_POST['patient_type'] ?? '') === 'Student') ? 'selected' : '' ?>>Student</option>
                <option value="Faculty" <?= (($_POST['patient_type'] ?? '') === 'Faculty') ? 'selected' : '' ?>>Faculty</option>
                <option value="Staff" <?= (($_POST['patient_type'] ?? '') === 'Staff') ? 'selected' : '' ?>>Staff</option>
            </select><br><br>

            <div id="student-box" style="display:none;">
                <label>Student ID:</label>
                <input type="text" name="student_id" placeholder="e.g. B24CS041">
            </div>

            <div id="faculty-box" style="display:none;">
                <label>Faculty ID:</label>
                <input type="text" name="faculty_id" placeholder="e.g. 01">
            </div>

            <div id="staff-box" style="display:none;">
                <label>Staff ID:</label>
                <input type="text" name="staff_id" placeholder="e.g. 01">
            </div>

            <br>
            <button type="submit">Search</button>
        </form>
        <?php if ($message): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>
    </div>

    <?php if ($patient): ?>
        <div class="results">
            <h3>Patient Details</h3>
            <table>
                <tr>
                    <?php foreach ($patient as $key => $value): ?>
                        <th><?= htmlspecialchars($key) ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <?php foreach ($patient as $value): ?>
                        <td><?= htmlspecialchars($value) ?></td>
                    <?php endforeach; ?>
                </tr>
            </table>
        </div>

        <div class="consultations">
            <h3>Previous Consultations</h3>
            <?php if (!empty($consultations)): ?>
                <table>
                    <tr>
                        <?php foreach ($consultations[0] as $key => $val): ?>
                            <th><?= htmlspecialchars($key) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php foreach ($consultations as $row): ?>
                        <tr>
                            <?php foreach ($row as $val): ?>
                                <td><?= htmlspecialchars($val) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php
    // ✅ Calculate total price
                    $totalPrice = 0;
                    foreach ($consultations as $row) {
                    if (isset($row['total_price'])) {   // assumes you have a 'price' column in consultations table
                        $totalPrice += (float)$row['total_price'];
                    }
                    }
    ?>
            <p><strong>Total Price of All Consultations:</strong> ₹<?= number_format($totalPrice, 2) ?></p>
            <?php else: ?>
                <p>No consultations found for this patient.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script>
        // Show ID input based on role
        function toggleBoxes() {
            let type = document.querySelector("select[name='patient_type']").value;
            document.getElementById("student-box").style.display = (type === "Student") ? "block" : "none";
            document.getElementById("faculty-box").style.display = (type === "Faculty") ? "block" : "none";
            document.getElementById("staff-box").style.display = (type === "Staff") ? "block" : "none";
        }
        document.querySelector("select[name='patient_type']").addEventListener("change", toggleBoxes);
        window.onload = toggleBoxes;
    </script>
</body>
</html>
