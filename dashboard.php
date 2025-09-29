<?php
session_start();
require_once "db/config.php";
require_once "includes/functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Consultant') {
    header("Location: index.php");
    exit;
}

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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Consultant Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin:20px; background:#f9f9f9; }
        h2,h3,h4 { margin-top:0; }
        .box { background:#fff; padding:20px; margin-bottom:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        label { display:block; margin-top:10px; font-weight:600; }
        input, select, textarea, button { width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; }
        button { width:auto; cursor:pointer; background:#4CAF50; color:white; border:none; transition:0.3s; }
        button:hover { background:#45a049; }
        table { width:100%; border-collapse:collapse; margin-top:15px; }
        th, td { border:1px solid #ccc; padding:8px; text-align:left; }
        th { background:#f2f2f2; }
        .med-row { display:flex; gap:10px; margin-top:5px; }
        .med-row select, .med-row input { flex:1; }
    </style>
    <script>
        function toggleInput() {
            const type = document.getElementById("patient_type").value;
            document.getElementById("student_box").style.display = (type === "Student") ? "block" : "none";
            document.getElementById("email_box").style.display = (type === "Faculty" || type === "Staff") ? "block" : "none";
        }

        let medCount = 1;
        function addMedicine() {
            const container = document.getElementById('medicines-container');
            const div = document.createElement('div');
            div.className = 'med-row';
            div.innerHTML = `
                <select name="medicines[${medCount}][medicine_id]">
                    <?php foreach(getAllMedicines() as $med){ 
                        echo "<option value='{$med['id']}'>" . htmlspecialchars($med['name']) . " ({$med['price']})</option>"; 
                    } ?>
                </select>
                <input type="number" name="medicines[${medCount}][quantity]" placeholder="Quantity" min="1" required>
            `;
            container.appendChild(div);
            medCount++;
        }
    </script>
</head>
<body>
    <h2>Welcome, Consultant</h2>
    <a href="logout.php" style="display:inline-block;margin-bottom:20px;color:#fff;background:#f44336;padding:8px 12px;border-radius:4px;text-decoration:none;">Logout</a>

    <div class="box">
        <h3>Search Patient</h3>
        <form method="POST">
            <label>Patient Type</label>
            <select name="patient_type" id="patient_type" onchange="toggleInput()" required>
                <option value="">-- Select Type --</option>
                <option value="Student">Student</option>
                <option value="Faculty">Faculty</option>
                <option value="Staff">Staff</option>
            </select>

            <div id="student_box" style="display:none;">
                <label>Student ID</label>
                <input type="text" name="student_id">
            </div>
            <div id="email_box" style="display:none;">
                <label>Email ID</label>
                <input type="email" name="email">
            </div>
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if ($patient): ?>
        <div class="box">
            <h3>Patient Details</h3>
            <?php foreach($patient as $k=>$v){ echo "<p><b>".ucfirst($k).":</b> ".htmlspecialchars($v)."</p>"; } ?>
        </div>

        <div class="box">
            <h3>Previous Consultations</h3>
            <?php if ($consultations): ?>
                <table>
                    <tr>
                        <th>Date</th><th>Time</th><th>Symptoms</th><th>Disease</th>
                        <th>Medicine Cost</th><th>Comments</th><th>Triage Priority</th>
                    </tr>
                    <?php foreach($consultations as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['consultation_date']) ?></td>
                            <td><?= htmlspecialchars($c['consultation_time']) ?></td>
                            <td><?= htmlspecialchars($c['symptoms']) ?></td>
                            <td><?= htmlspecialchars($c['disease_name']) ?></td>
                            <td>₹<?= htmlspecialchars($c['total_price']) ?></td>
                            <td><?= htmlspecialchars($c['comments']) ?></td>
                            <td><?= htmlspecialchars($c['triage_priority']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No previous consultations.</p>
            <?php endif; ?>
        </div>

        <div class="box">
            <h3>New Consultation</h3>
            <form method="POST" action="submit_consultation.php">
                <input type="hidden" name="patient_type" value="<?= $_POST['patient_type'] ?>">
                <input type="hidden" name="patient_id" value="<?= ($patient['student_id'] ?? $patient['faculty_id'] ?? $patient['staff_id']) ?>">
                <input type="hidden" name="consultant_id" value="<?= $_SESSION['user_id'] ?>">

                <label>Symptoms</label>
                <textarea name="symptoms" required></textarea>

                <label>Disease</label>
                <input type="text" name="disease_name" required>

                <label>Date</label>
                <input type="date" name="consultation_date" required>
                <label>Time</label>
                <input type="time" name="consultation_time" required>

                <label>Triage Priority</label>
                <select name="triage_priority" required>
                    <option value="">-- Select Priority --</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>

                <h4>Prescribed Medicines</h4>
                <div id="medicines-container">
                    <div class="med-row">
                        <select name="medicines[0][medicine_id]">
                            <?php foreach(getAllMedicines() as $med){ 
                                echo "<option value='{$med['id']}'>" . htmlspecialchars($med['name']) . " ({$med['price']})</option>"; 
                            } ?>
                        </select>
                        <input type="number" name="medicines[0][quantity]" placeholder="Quantity" min="1" required>
                    </div>
                </div>
                <button type="button" onclick="addMedicine()">+ Add More Medicines</button>

                <label>Referral Status</label>
                <select name="referral_status">
                    <option value="No">No</option>
                    <option value="Yes">Yes</option>
                </select>
                <label>Referral Place</label>
                <input type="text" name="referral_place">
                <label>Referral Reason</label>
                <input type="text" name="referral_reason">

                <label>Comments</label>
                <textarea name="comments"></textarea>

                <button type="submit">Save Consultation</button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
