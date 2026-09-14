<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: attendance.php');
    exit;
}

$pdo = getDB();
$today = date('Y-m-d');
$classId = (int)($_POST['class_id'] ?? 0);
$statuses = $_POST['status'] ?? [];

$insertStmt = $pdo->prepare(
    'INSERT INTO attendance (student_id, class_date, status)
     VALUES (:sid, :date, :status)
     ON DUPLICATE KEY UPDATE status = VALUES(status)'
);
$recalcStmt = $pdo->prepare(
    "UPDATE students SET attendance_pct =
        (SELECT ROUND(SUM(status='present')/COUNT(*)*100,2) FROM attendance WHERE student_id = :sid)
     WHERE id = :sid"
);

foreach ($statuses as $studentId => $status) {
    if (!in_array($status, ['present', 'late', 'absent'], true)) {
        continue;
    }
    $studentId = (int)$studentId;
    $insertStmt->execute(['sid' => $studentId, 'date' => $today, 'status' => $status]);
    $recalcStmt->execute(['sid' => $studentId]);
}

header('Location: attendance.php?class_id=' . $classId . '&saved=1');
exit;
