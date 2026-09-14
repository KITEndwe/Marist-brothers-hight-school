<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('student');

$u = current_user();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $result = handle_upload($_FILES['photo'], 'avatars', ['jpg', 'jpeg', 'png', 'webp'], 3);

    if ($result['ok']) {
        $stmt = $pdo->prepare('UPDATE users SET profile_photo = :path WHERE id = :id');
        $stmt->execute(['path' => $result['path'], 'id' => $u['id']]);
        $_SESSION['user']['photo'] = $result['path'];
        header('Location: profile.php?uploaded=1');
        exit;
    }
    header('Location: profile.php?error=' . urlencode($result['error']));
    exit;
}

header('Location: profile.php');
exit;
