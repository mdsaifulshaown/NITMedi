<?php
session_start();
require 'db/config.php';

// Initialize variables
$error = '';
$logo_data = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role     = trim($_POST['role']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($role) || empty($email) || empty($password)) {
        $error = "❌ Please fill in all fields!";
    } else {
        // User check
        try {
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
                        header("Location: backend/dashboard.php");
                    } elseif ($role === "Admin") {
                        header("Location: backend/admin.php");
                    }
                    exit;
                } else {
                    $error = "❌ Invalid Email / Password!";
                }
            } else {
                $error = "❌ Invalid Email / Password!";
            }
        } catch (PDOException $e) {
            $error = "❌ Database error. Please try again.";
        }
    }
}

// Base64 encoded logo - with proper error handling
$logo_path = "assets/NITM logo.png";
if (file_exists($logo_path) && is_readable($logo_path)) {
    $logo_type = pathinfo($logo_path, PATHINFO_EXTENSION);
    $logo_content = file_get_contents($logo_path);
    if ($logo_content !== false) {
        $logo_data = 'data:image/' . $logo_type . ';base64,' . base64_encode($logo_content);
    }
}

// Alternative logo paths if primary doesn't exist
if (!$logo_data) {
    $alternative_paths = [
        "NITM logo.png",
        "assets/logo.png", 
        "images/NITM logo.png",
        "../assets/NITM logo.png"
    ];
    
    foreach ($alternative_paths as $alt_path) {
        if (file_exists($alt_path) && is_readable($alt_path)) {
            $logo_type = pathinfo($alt_path, PATHINFO_EXTENSION);
            $logo_content = file_get_contents($alt_path);
            if ($logo_content !== false) {
                $logo_data = 'data:image/' . $logo_type . ';base64,' . base64_encode($logo_content);
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NITMedi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --accent: #e74c3c;
            --success: #27ae60;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-reverse: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 15px 40px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
            --transition-slow: all 0.5s ease;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            min-height: 600px;
            animation: slideUp 0.8s ease;
            position: relative;
        }

        /* Left Side - Welcome Section */
        .welcome-section {
            flex: 1;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: var(--transition-slow);
        }

        .welcome-section:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: float 20s infinite linear;
        }

        .logo-container {
            position: relative;
            margin-bottom: 30px;
            z-index: 2;
        }

        .logo {
            height: 100px;
            width: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: bounce 2s infinite;
            transition: var(--transition);
        }

        .logo:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .logo-placeholder {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: bounce 2s infinite;
            transition: var(--transition);
        }

        .logo-placeholder:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            animation: fadeIn 1s ease;
            position: relative;
            z-index: 2;
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            animation: fadeIn 1.2s ease;
            position: relative;
            z-index: 2;
        }

        .features {
            text-align: left;
            margin-top: 30px;
            animation: fadeIn 1.4s ease;
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 300px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 1rem;
            padding: 10px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .feature-item i {
            color: var(--success);
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        /* Right Side - Login Form */
        .login-section {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
            position: relative;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-title {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 10px;
            font-weight: 700;
            position: relative;
            display: inline-block;
        }

        .login-title::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 2px;
        }

        .login-subtitle {
            color: #666;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        select, input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
        }

        select:focus, input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .login-btn {
            width: 100%;
            background: var(--primary);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition-slow);
        }

        .login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #ffeaa7;
            color: #e74c3c;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            border-left: 4px solid #e74c3c;
            animation: shake 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--light);
            color: var(--secondary);
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
            transition: var(--transition);
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.9rem;
        }

        /* Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            100% {
                transform: translate(-50px, -50px) rotate(360deg);
            }
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-5px);
            }
            75% {
                transform: translateX(5px);
            }
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(52, 152, 219, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(52, 152, 219, 0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                min-height: auto;
            }
            
            .welcome-section, .login-section {
                padding: 30px 25px;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .logo, .logo-placeholder {
                height: 80px;
                width: 80px;
            }
        }

        /* Loading Animation */
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Floating elements */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: floatAround 15s infinite linear;
        }

        @keyframes floatAround {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            33% {
                transform: translate(30px, -50px) rotate(120deg);
            }
            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
            100% {
                transform: translate(0, 0) rotate(360deg);
            }
        }

        /* Enhanced focus states */
        .form-group:focus-within label {
            color: var(--primary);
        }

        /* Pulse animation for important elements */
        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Welcome Section -->
        <div class="welcome-section">
            <div class="floating-elements">
                <div class="floating-element" style="width: 80px; height: 80px; top: 10%; left: 10%;"></div>
                <div class="floating-element" style="width: 40px; height: 40px; top: 70%; left: 80%;"></div>
                <div class="floating-element" style="width: 60px; height: 60px; top: 40%; left: 85%;"></div>
            </div>
            
            <div class="logo-container">
                <?php if (!empty($logo_data)): ?>
                    <img src="<?= $logo_data ?>" alt="NITM Logo" class="logo">
                <?php else: ?>
                    <div class="logo-placeholder">
                        <span style="color: var(--secondary); font-weight: bold; font-size: 0.7rem; text-align: center;">NITM<br>MEDI</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <h1 class="welcome-title">Welcome to NITMedi</h1>
            <p class="welcome-subtitle">Your trusted healthcare partner</p>
            
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure & Private</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-bolt"></i>
                    <span>Fast & Reliable</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-users"></i>
                    <span>Professional Care</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-heartbeat"></i>
                    <span>24/7 Support</span>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-section">
            <div class="login-header">
                <h2 class="login-title">Sign In</h2>
                <p class="login-subtitle">Access your medical portal</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="role">
                        <i class="fas fa-user-tag"></i> Role
                        <span class="role-badge" id="roleBadge">Consultant</span>
                    </label>
                    <select name="role" id="role" required onchange="updateRoleBadge()">
                        <option value="Consultant">Consultant</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" name="email" id="email" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" required placeholder="Enter your password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn pulse" id="loginBtn">
                    <span id="btnText">Sign In</span>
                    <div class="loading" id="loading"></div>
                </button>
            </form>
            
            <div class="form-footer">
                <p>Secure login with industry-standard encryption</p>
            </div>
        </div>
    </div>

    <script>
        function updateRoleBadge() {
            const role = document.getElementById('role').value;
            const badge = document.getElementById('roleBadge');
            badge.textContent = role;
            
            // Add animation effect
            badge.style.transform = 'scale(1.2)';
            setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, 300);
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
            
            // Add focus to password field after toggle
            passwordInput.focus();
        }

        // Form submission animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loading = document.getElementById('loading');
            
            btn.disabled = true;
            btnText.style.display = 'none';
            loading.style.display = 'block';
            btn.style.background = '#95a5a6';
            btn.classList.remove('pulse');
        });

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, select');
            inputs.forEach(input => {
                // Add focus effects
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-5px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
                
                // Add input validation styling
                input.addEventListener('input', function() {
                    if (this.value) {
                        this.style.borderColor = '#27ae60';
                    } else {
                        this.style.borderColor = '#e0e0e0';
                    }
                });
            });
            
            // Add floating elements dynamically
            const floatingContainer = document.querySelector('.floating-elements');
            for (let i = 0; i < 5; i++) {
                const element = document.createElement('div');
                element.classList.add('floating-element');
                const size = Math.random() * 60 + 20;
                element.style.width = `${size}px`;
                element.style.height = `${size}px`;
                element.style.top = `${Math.random() * 100}%`;
                element.style.left = `${Math.random() * 100}%`;
                element.style.animationDuration = `${Math.random() * 10 + 10}s`;
                element.style.animationDelay = `${Math.random() * 5}s`;
                floatingContainer.appendChild(element);
            }
        });
    </script>
</body>
</html>
