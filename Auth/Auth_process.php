<?php
// auth/auth_process.php
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: login.php?error=empty_fields");
    exit;
}

$pdo = getDBConnection();

// Get account info from active email.
$stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

// Verify user and password with hashing
if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
    // Elakkan serangan Session Fixation
    session_regenerate_id(true);

    // stor indentity to session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    header("Location: ../views/dashboard.php");
    exit;
} else {
    // error message (elak account enumeration)
    header("Location: login.php?error=invalid_credentials");
    exit;
}