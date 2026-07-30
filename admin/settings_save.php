<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

$pdo = getDB();
$u = current_user();
$action = $_POST['action'] ?? '';

if ($action === 'profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($fullName === '' || $email === '') {
        header('Location: settings.php?error=' . urlencode('Name and email cannot be empty.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare('UPDATE users SET full_name = :name, email = :email WHERE id = :id');
        $stmt->execute(['name' => $fullName, 'email' => $email, 'id' => $u['id']]);

        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['email'] = $email;

        header('Location: settings.php?saved=1');
    } catch (PDOException $e) {
        header('Location: settings.php?error=' . urlencode('That email is already in use by another account.'));
    }
    exit;
}

if ($action === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->execute(['id' => $u['id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        header('Location: settings.php?error=' . urlencode('Your current password is incorrect.'));
        exit;
    }
    if (strlen($new) < 6) {
        header('Location: settings.php?error=' . urlencode('New password must be at least 6 characters.'));
        exit;
    }
    if ($new !== $confirm) {
        header('Location: settings.php?error=' . urlencode('New password and confirmation do not match.'));
        exit;
    }

    $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
    $stmt->execute(['hash' => password_hash($new, PASSWORD_BCRYPT), 'id' => $u['id']]);

    header('Location: settings.php?saved=1');
    exit;
}

header('Location: settings.php');
exit;