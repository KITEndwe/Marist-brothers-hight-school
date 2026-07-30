<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('teacher');

$u = current_user();
$pdo = getDB();

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['paper'])) {
    header('Location: past-papers.php');
    exit;
}

$subjectId = (int)($_POST['subject_id'] ?? 0);
$title     = trim($_POST['title'] ?? '');
$term      = trim($_POST['term'] ?? '') ?: null;

if (!$subjectId || $title === '') {
    header('Location: past-papers.php?error=' . urlencode('Please choose a subject and give the paper a title.'));
    exit;
}

// Confirm this subject actually belongs to this teacher.
$check = $pdo->prepare('SELECT id FROM subjects WHERE id = :sid AND teacher_id = :tid');
$check->execute(['sid' => $subjectId, 'tid' => $teacher['id']]);
if (!$check->fetch()) {
    header('Location: past-papers.php?error=' . urlencode('That subject is not assigned to you.'));
    exit;
}

$result = handle_upload($_FILES['paper'], 'past_papers', ['pdf', 'doc', 'docx'], 10);

if (!$result['ok']) {
    header('Location: past-papers.php?error=' . urlencode($result['error']));
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO past_papers (subject_id, teacher_id, title, term, file_path)
     VALUES (:sid, :tid, :title, :term, :path)'
);
$stmt->execute([
    'sid'   => $subjectId,
    'tid'   => $teacher['id'],
    'title' => $title,
    'term'  => $term,
    'path'  => $result['path'],
]);

header('Location: past-papers.php?uploaded=1');
exit;
