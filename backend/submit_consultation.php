<?php
session_start();
require_once "../db/config.php";
require_once "../includes/functions.php";

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
        // DEBUG: Check what's in POST data
        // ===============================
        echo "<pre>POST data: ";
        print_r($_POST);
        echo "</pre>";

        // ===============================
        // Step 2: Calculate total medicines cost
        // ===============================
        $total_price = 0;
        $medicines_data = [];

        if (!empty($_POST['medicines']) && is_array($_POST['medicines'])) {
            echo "<pre>Medicines found in POST: ";
            print_r($_POST['medicines']);
            echo "</pre>";
            
            foreach ($_POST['medicines'] as $index => $med) {
                echo "<pre>Processing medicine $index: ";
                print_r($med);
                echo "</pre>";
                
                if (!empty($med['medicine_id']) && !empty($med['quantity'])) {
                    $medicine_id = $med['medicine_id'];
                    $quantity = (int)$med['quantity'];
                    
                    echo "Medicine ID: $medicine_id, Quantity: $quantity<br>";

                    // Fetch medicine price from DB
                    $stmtPrice = $pdo->prepare("SELECT price, stock, name FROM medicines WHERE medicine_id = ?");
                    $stmtPrice->execute([$medicine_id]);
                    $row = $stmtPrice->fetch(PDO::FETCH_ASSOC);
                    
                    echo "<pre>Medicine DB result: ";
                    print_r($row);
                    echo "</pre>";
                    
                    if (!$row) {
                        throw new Exception("Medicine not found with ID: $medicine_id");
                    }
                    
                    $unit_price = (float)$row['price'];
                    echo "Unit Price: $unit_price<br>";

                    $med_total_price = $unit_price * $quantity;
                    echo "Medicine Total: $med_total_price<br>";

                    // Add to total
                    $total_price += $med_total_price;
                    echo "Running Total: $total_price<br>";

                    // Save medicine info for prescription insert
                    $medicines_data[] = [
                        'medicine_id' => $medicine_id,
                        'quantity' => $quantity,
                        'unit_price' => $unit_price,
                        'total_price' => $med_total_price
                    ];
                } else {
                    echo "Medicine ID or Quantity empty for index $index<br>";
                }
            }
        } else {
            echo "No medicines array found in POST data<br>";
        }

        echo "FINAL TOTAL PRICE: $total_price<br>";

        // ===============================
        // Step 3: Insert Consultation with total_price
        // ===============================
        $stmt = $pdo->prepare("
            INSERT INTO consultations 
            (patient_type, patient_id, consultant_id, disease_name, consultation_date, consultation_time, triage_priority, symptoms, total_price, referral_status, referral_place, referral_reason, comments)
            VALUES (:patient_type, :patient_id, :consultant_id, :disease_name, :consultation_date, :consultation_time, :triage_priority, :symptoms, :total_price, :referral_status, :referral_place, :referral_reason, :comments)
        ");

        $insertData = [
            'patient_type' => $_POST['patient_type'],
            'patient_id' => $_POST['patient_id'],
            'consultant_id' => $_POST['consultant_id'],
            'disease_name' => $_POST['disease_name'],
            'consultation_date' => $_POST['consultation_date'],
            'consultation_time' => $_POST['consultation_time'],
            'triage_priority' => $_POST['triage_priority'],
            'symptoms' => $_POST['symptoms'] ?? '',
            'total_price' => $total_price,
            'referral_status' => $_POST['referral_status'] ?? 'No',
            'referral_place' => $_POST['referral_place'] ?? '',
            'referral_reason' => $_POST['referral_reason'] ?? '',
            'comments' => $_POST['comments'] ?? ''
        ];

        echo "<pre>Inserting consultation data: ";
        print_r($insertData);
        echo "</pre>";

        $stmt->execute($insertData);

        $consultation_id = $pdo->lastInsertId();
        echo "Consultation ID: $consultation_id<br>";

        // ===============================
        // Step 4: Insert Prescribed Medicines & Update Stock
        // ===============================
        if (!empty($medicines_data)) {
            echo "<pre>Inserting medicines data: ";
            print_r($medicines_data);
            echo "</pre>";
            
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
                $stmt3 = $pdo->prepare("UPDATE medicines SET stock = stock - ? WHERE medicine_id = ?");
                $stmt3->execute([$med['quantity'], $med['medicine_id']]);
                echo "Updated stock for medicine ID: {$med['medicine_id']}<br>";
            }
        } else {
            echo "No medicines data to insert<br>";
        }

        // ===============================
        // Step 5: Redirect to print consultation page
        // ===============================
        echo "<script>alert('Consultation saved with total price: $total_price'); window.location.href = 'print_consultation.php?id=$consultation_id';</script>";
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
