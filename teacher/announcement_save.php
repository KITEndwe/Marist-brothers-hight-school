<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: announcements.php');
    exit;
}

$pdo = getDB();
$u = current_user();

$title    = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? 'Info');
// Teachers may only address students or everyone — never admin-only or teacher-only notices.
$audience = ($_POST['audience'] ?? 'students') === 'all' ? 'all' : 'students';
$body     = trim($_POST['body'] ?? '');

if ($title === '') {
    header('Location: announcements.php');
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO announcements (title, body, category, audience, posted_by, posted_date)
     VALUES (:title, :body, :category, :audience, :posted_by, CURDATE())'
);
$stmt->execute([
    'title'     => $title,
    'body'      => $body ?: null,
    'category'  => $category ?: 'Info',
    'audience'  => $audience,
    'posted_by' => $u['id'],
]);

header('Location: announcements.php?posted=1');
exit;
