<?php
session_start();
require 'db/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role     = trim($_POST['role']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // User check
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // For plain text (NOT recommended in real projects)
        if ($password === $user['password']) {
            // Session set
            $_SESSION['user_id']   = $user['user_id'];  
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];

            // Redirect by role
            if ($role === "Consultant") {
                header("Location: dashboard.php");
            } elseif ($role === "Admin") {
                header("Location: admin.php");
            }
            exit;
        } else {
            $error = "❌ Invalid Email / Password!";
        }
    } else {
        $error = "❌ Invalid Email / Password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Medical Center</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <form method="POST" action="">
            <label>Role:</label>
            <select name="role" required>
                <option value="Consultant">Consultant</option>
                <option value="Admin">Admin</option>
            </select><br><br>

            <label>Email:</label>
            <input type="email" name="email" required><br><br>

            <label>Password:</label>
            <input type="password" name="password" required><br><br>

            <button type="submit">Login</button>
        </form>
        <?php if(isset($error)){ echo "<p style='color:red;'>$error</p>"; } ?>
    </div>
</body>
</html>
