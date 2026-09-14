<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'student';

if (!$email || !$password) {
    header('Location: login.php?error=invalid');
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND role = :role AND is_active = 1 LIMIT 1');
$stmt->execute(['email' => $email, 'role' => $role]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: login.php?error=invalid');
    exit;
}

// Store a lean session — never store the password hash in session.
$_SESSION['user'] = [
    'id'        => $user['id'],
    'full_name' => $user['full_name'],
    'email'     => $user['email'],
    'role'      => $user['role'],
    'initials'  => $user['avatar_initials'],
    'photo'     => $user['profile_photo'],
];

header('Location: ' . $user['role'] . '/dashboard.php');
exit;
