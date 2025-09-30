<?php
require_once __DIR__ . '/../db/config.example.php';

// ===============================
// Student Functions
// ===============================
function getStudentByRoll($roll_no) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM students WHERE roll_no = ?");
    $stmt->execute([$roll_no]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getStudentById($student_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ===============================
// Faculty Functions
// ===============================
function getFacultyByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM faculty WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getFacultyById($faculty_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM faculty WHERE faculty_id = ?");
    $stmt->execute([$faculty_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ===============================
// Staff Functions
// ===============================
function getStaffByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getStaffById($staff_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ===============================
// Medicine Functions
// ===============================
function getAllMedicines() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMedicinePrice($medicine_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT price FROM medicines WHERE id = ?");
    $stmt->execute([$medicine_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (float)$row['price'] : 0;
}

// ===============================
// Consultation Functions
// ===============================
function insertConsultation($data, $medicines = []) {
    global $pdo;

    // ===============================
    // Calculate total medicines cost
    // ===============================
    $medicines_total = 0;
    foreach ($medicines as $med) {
        $unit_price = isset($med['unit_price']) ? $med['unit_price'] : getMedicinePrice($med['medicine_id']);
        $qty = (int)$med['quantity'];
        $medicines_total += $unit_price * $qty;
        $med['unit_price'] = $unit_price;
        $med['total_price'] = $unit_price * $qty;
    }

    // ===============================
    // Insert Consultation
    // ===============================
    $stmt = $pdo->prepare("
        INSERT INTO consultations
        (patient_type, patient_id, consultant_id, disease_name, consultation_date, consultation_time, triage_priority, symptoms, medicines_total, referral_status, referral_place, referral_reason, comments)
        VALUES (:patient_type, :patient_id, :consultant_id, :disease_name, :consultation_date, :consultation_time, :triage_priority, :symptoms, :medicines_total, :referral_status, :referral_place, :referral_reason, :comments)
    ");

    $stmt->execute([
        ':patient_type' => $data['patient_type'] ?? '',
        ':patient_id' => $data['patient_id'] ?? '',
        ':consultant_id' => $data['consultant_id'] ?? null,
        ':disease_name' => $data['disease_name'] ?? '',
        ':consultation_date' => $data['consultation_date'] ?? date('Y-m-d'),
        ':consultation_time' => $data['consultation_time'] ?? date('H:i:s'),
        ':triage_priority' => $data['triage_priority'] ?? 'Normal',
        ':symptoms' => $data['symptoms'] ?? '',
        ':medicines_total' => $medicines_total,
        ':referral_status' => $data['referral_status'] ?? 'No',
        ':referral_place' => $data['referral_place'] ?? '',
        ':referral_reason' => $data['referral_reason'] ?? '',
        ':comments' => $data['comments'] ?? ''
    ]);

    $consultation_id = $pdo->lastInsertId();

    // ===============================
    // Insert Prescribed Medicines
    // ===============================
    if (!empty($medicines)) {
        $stmt2 = $pdo->prepare("
            INSERT INTO prescription (consultation_id, medicine_id, quantity, unit_price, total_price, created_at)
            VALUES (:consultation_id, :medicine_id, :quantity, :unit_price, :total_price, NOW())
        ");

        foreach ($medicines as $med) {
            $stmt2->execute([
                ':consultation_id' => $consultation_id,
                ':medicine_id' => $med['medicine_id'],
                ':quantity' => $med['quantity'],
                ':unit_price' => $med['unit_price'],
                ':total_price' => $med['total_price']
            ]);

            // Update stock
            $stmt3 = $pdo->prepare("UPDATE medicines SET stock = stock - ? WHERE id = ?");
            $stmt3->execute([$med['quantity'], $med['medicine_id']]);
        }
    }

    return $consultation_id;
}

// ===============================
// Previous Consultations
// ===============================
function getPreviousConsultations($patient_type, $patient_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM consultations
        WHERE patient_type = ? AND patient_id = ?
        ORDER BY consultation_date DESC, consultation_time DESC
    ");
    $stmt->execute([$patient_type, $patient_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===============================
// Total Price (all consultations)
// ===============================
function getTotalSpent($patient_type, $patient_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(total_price) as total FROM consultations WHERE patient_type = ? AND patient_id = ?");
    $stmt->execute([$patient_type, $patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && $row['total'] !== null ? (float)$row['total'] : 0;
}
?>