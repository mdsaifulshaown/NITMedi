<?php
session_start();
require_once __DIR__ . '/../db/config.example.php';
require_once __DIR__ . '/../db/config.example.php';

// ===============================
// Step 0: Check if user is logged in and is a Consultant
// ===============================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Consultant') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ===============================
        // Step 1: Validate required fields
        // ===============================
        $requiredFields = [
            'patient_type',
            'patient_id',
            'consultant_id',
            'disease_name',
            'consultation_date',
            'consultation_time',
            'triage_priority'
        ];

        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // ===============================
        // Step 2: Calculate total medicines cost
        // ===============================
        $total_price = 0; // Updated field name
        $medicines_data = [];

        if (!empty($_POST['medicines']) && is_array($_POST['medicines'])) {
            foreach ($_POST['medicines'] as $med) {
                if (!empty($med['medicine_id']) && !empty($med['quantity'])) {
                    $medicine_id = $med['medicine_id'];
                    $quantity = (int)$med['quantity'];

                    // Fetch medicine price from DB
                    $stmtPrice = $pdo->prepare("SELECT price FROM medicines WHERE id = ?");
                    $stmtPrice->execute([$medicine_id]);
                    $row = $stmtPrice->fetch(PDO::FETCH_ASSOC);
                    $unit_price = $row ? (float)$row['price'] : 0;

                    $med_total_price = $unit_price * $quantity;

                    // Add to total
                    $total_price += $med_total_price;

                    // Save medicine info for prescription insert
                    $medicines_data[] = [
                        'medicine_id' => $medicine_id,
                        'quantity' => $quantity,
                        'unit_price' => $unit_price,
                        'total_price' => $med_total_price
                    ];
                }
            }
        }

        // ===============================
        // Step 3: Insert Consultation with total_price
        // ===============================
        $stmt = $pdo->prepare("
            INSERT INTO consultations 
            (patient_type, patient_id, consultant_id, disease_name, consultation_date, consultation_time, triage_priority, symptoms, total_price, referral_status, referral_place, referral_reason, comments)
            VALUES (:patient_type, :patient_id, :consultant_id, :disease_name, :consultation_date, :consultation_time, :triage_priority, :symptoms, :total_price, :referral_status, :referral_place, :referral_reason, :comments)
        ");

        $stmt->execute([
            'patient_type' => $_POST['patient_type'],
            'patient_id' => $_POST['patient_id'],
            'consultant_id' => $_POST['consultant_id'],
            'disease_name' => $_POST['disease_name'],
            'consultation_date' => $_POST['consultation_date'],
            'consultation_time' => $_POST['consultation_time'],
            'triage_priority' => $_POST['triage_priority'],
            'symptoms' => $_POST['symptoms'] ?? '',
            'total_price' => $total_price, // Updated
            'referral_status' => $_POST['referral_status'] ?? 'No',
            'referral_place' => $_POST['referral_place'] ?? '',
            'referral_reason' => $_POST['referral_reason'] ?? '',
            'comments' => $_POST['comments'] ?? ''
        ]);

        $consultation_id = $pdo->lastInsertId();

        // ===============================
        // Step 4: Insert Prescribed Medicines & Update Stock
        // ===============================
        if (!empty($medicines_data)) {
            $stmt2 = $pdo->prepare("
                INSERT INTO prescription (consultation_id, medicine_id, quantity, unit_price, total_price, created_at)
                VALUES (:consultation_id, :medicine_id, :quantity, :unit_price, :total_price, NOW())
            ");

            foreach ($medicines_data as $med) {
                $stmt2->execute([
                    'consultation_id' => $consultation_id,
                    'medicine_id' => $med['medicine_id'],
                    'quantity' => $med['quantity'],
                    'unit_price' => $med['unit_price'],
                    'total_price' => $med['total_price']
                ]);

                // Update stock
                $stmt3 = $pdo->prepare("UPDATE medicines SET stock = stock - ? WHERE id = ?");
                $stmt3->execute([$med['quantity'], $med['medicine_id']]);
            }
        }

        // ===============================
        // Step 5: Redirect to print consultation page
        // ===============================
        header("Location: print_consultation.php?id=$consultation_id");
        exit;

    } catch (Exception $e) {
        // Show user-friendly error message
        echo "<div style='padding:20px; background:#fdd; color:#900; border-radius:8px;'>";
        echo "<h3>Error Saving Consultation</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='dashboard.php'>Go Back</a>";
        echo "</div>";
        exit;
    }
} else {
    // If accessed directly, redirect to dashboard
    header("Location: dashboard.php");
    exit;
}
?>
