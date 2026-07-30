<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: assignments.php');
    exit;
}

$pdo = getDB();
$u = current_user();

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

$subjectId = (int)($_POST['subject_id'] ?? 0);
$title     = trim($_POST['title'] ?? '');
$dueDate   = trim($_POST['due_date'] ?? '');

if (!$subjectId || $title === '' || $dueDate === '') {
    header('Location: assignments.php?error=' . urlencode('Please fill in subject, title and due date.'));
    exit;
}

$check = $pdo->prepare('SELECT id FROM subjects WHERE id = :sid AND teacher_id = :tid');
$check->execute(['sid' => $subjectId, 'tid' => $teacher['id']]);
if (!$check->fetch()) {
    header('Location: assignments.php?error=' . urlencode('That subject is not assigned to you.'));
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO assignments (subject_id, teacher_id, title, due_date) VALUES (:sid, :tid, :title, :due)'
);
$stmt->execute([
    'sid'   => $subjectId,
    'tid'   => $teacher['id'],
    'title' => $title,
    'due'   => $dueDate,
]);

header('Location: assignments.php?created=1');
exit;