<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$pdo = getDB();
$u = current_user();

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

$recipientId = (int)($_POST['recipient_id'] ?? 0);
$subject     = trim($_POST['subject'] ?? '');
$body        = trim($_POST['body'] ?? '');

if (!$recipientId || $body === '') {
    header('Location: messages.php');
    exit;
}

// Only allow messaging a student who is actually in one of this teacher's classes.
$check = $pdo->prepare(
    "SELECT 1 FROM students s
     JOIN subjects sub ON sub.class_id = s.class_id AND sub.teacher_id = :tid
     WHERE s.user_id = :rid LIMIT 1"
);
$check->execute(['tid' => $teacher['id'], 'rid' => $recipientId]);
if (!$check->fetch()) {
    header('Location: messages.php');
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (:sender, :recipient, :subject, :body)'
);
$stmt->execute([
    'sender'    => $u['id'],
    'recipient' => $recipientId,
    'subject'   => $subject ?: null,
    'body'      => $body,
]);

header('Location: messages.php?sent=1');
exit;