<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: attendance.php');
    exit;
}

$pdo = getDB();
$today = date('Y-m-d');
$statuses = $_POST['status'] ?? [];

$stmt = $pdo->prepare(
    'INSERT INTO teacher_attendance (teacher_id, attendance_date, status)
     VALUES (:tid, :date, :status)
     ON DUPLICATE KEY UPDATE status = VALUES(status)'
);

foreach ($statuses as $teacherId => $status) {
    if (!in_array($status, ['present', 'late', 'absent'], true)) {
        continue;
    }
    $stmt->execute([
        'tid'    => (int)$teacherId,
        'date'   => $today,
        'status' => $status,
    ]);
}

header('Location: attendance.php?saved=1');
exit;
