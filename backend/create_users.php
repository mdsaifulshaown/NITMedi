<?php
// create_users.php — Run once, then DELETE this file for security.
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


try {
    $host = $_ENV['DB_HOST'];
    $db   = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connect error: " . $e->getMessage());
}

// Change these credentials if you want different ones
$users = [
    [
        'name' => $_ENV['ADMIN_NAME'],
        'email' => $_ENV['ADMIN_EMAIL'],
        'password_plain' => $_ENV['ADMIN_PASSWORD'],
        'role' => $_ENV['ADMIN_ROLE'] // must match role check in your login code
    ],
    [
        'name' => $_ENV['CONSULTANT_NAME'],
        'email' => $_ENV['CONSULTANT_EMAIL'],
        'password_plain' => $_ENV['CONSULTANT_PASSWORD'],
        'role' => $_ENV['CONSULTANT_ROLE']
    ]
];

// Check if users table exists (optional)
try {
    $pdo->query("SELECT 1 FROM users LIMIT 1");
} catch (Exception $e) {
    die("Table `users` not found — create the table first. Error: " . $e->getMessage());
}

$inserted = [];
foreach ($users as $u) {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$u['email']]);
    if ($stmt->fetch()) {
        $inserted[] = $u['email'] . " (already exists)";
        continue;
    }

    $hashed = password_hash($u['password_plain'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$u['name'], $u['email'], $hashed, $u['role']]);
    $inserted[] = $u['email'] . " (inserted)";
}

// Output result
echo "<h3>Result:</h3><ul>";
foreach ($inserted as $r) {
    echo "<li>" . htmlspecialchars($r) . "</li>";
}
echo "</ul>";
echo "<p><strong>Now you can login with:</strong></p>";
echo "<ul>
        <li>Admin → admin@example.com / admin123</li>
        <li>Consultant → consultant@example.com / consult123</li>
      </ul>";
echo "<p><em>Important:</em> After verifying login, DELETE this file (create_users.php) for security.</p>";
