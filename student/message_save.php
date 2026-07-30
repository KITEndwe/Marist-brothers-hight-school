<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$pdo = getDB();
$u = current_user();

$recipientId = (int)($_POST['recipient_id'] ?? 0);
$subject     = trim($_POST['subject'] ?? '');
$body        = trim($_POST['body'] ?? '');

if (!$recipientId || $body === '') {
    header('Location: messages.php');
    exit;
}

// Only allow messaging a teacher who actually teaches one of this student's subjects.
$check = $pdo->prepare(
    "SELECT 1 FROM subjects sub
     JOIN teachers t ON t.id = sub.teacher_id
     JOIN students s ON s.class_id = sub.class_id
     WHERE s.user_id = :uid AND t.user_id = :rid LIMIT 1"
);
$check->execute(['uid' => $u['id'], 'rid' => $recipientId]);
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