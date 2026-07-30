<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add-student.php');
    exit;
}

$pdo = getDB();

$fullName    = trim($_POST['full_name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$classId     = (int)($_POST['class_id'] ?? 0);
$rollNo      = trim($_POST['roll_no'] ?? '');
$admissionNo = trim($_POST['admission_no'] ?? '');
$password    = trim($_POST['password'] ?? '');

if ($fullName === '' || $email === '' || !$classId || $rollNo === '') {
    header('Location: add-student.php?error=' . urlencode('Please fill in the student\'s name, email, class and roll number.'));
    exit;
}

// Auto-generate an admission number if none was supplied: MBN-<year>-<sequence>
if ($admissionNo === '') {
    $year = date('Y');
    $count = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE admission_no LIKE 'MBN-{$year}-%'")->fetchColumn();
    $admissionNo = sprintf('MBN-%s-%03d', $year, $count + 1);
}

// Auto-generate a temporary password if none was supplied.
if ($password === '') {
    $password = 'Mbn' . random_int(1000, 9999) . '!';
}

// Build initials from the full name, e.g. "Tadiwa Nyathi" -> "TN"
$parts = preg_split('/\s+/', trim($fullName));
$initials = strtoupper((($parts[0][0] ?? '') . ($parts[count($parts) - 1][0] ?? '')));

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, avatar_initials)
         VALUES (:name, :email, :hash, "student", :initials)'
    );
    $stmt->execute([
        'name'     => $fullName,
        'email'    => $email,
        'hash'     => password_hash($password, PASSWORD_BCRYPT),
        'initials' => $initials,
    ]);
    $userId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'INSERT INTO students (user_id, admission_no, class_id, roll_no, attendance_pct)
         VALUES (:uid, :adm, :cid, :roll, 0.00)'
    );
    $stmt->execute([
        'uid'  => $userId,
        'adm'  => $admissionNo,
        'cid'  => $classId,
        'roll' => $rollNo,
    ]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    // 23000 = integrity constraint violation (duplicate email or admission number)
    if ($e->getCode() === '23000') {
        header('Location: add-student.php?error=' . urlencode('That email or admission number is already in use.'));
    } else {
        header('Location: add-student.php?error=' . urlencode('Could not add the student. Please try again.'));
    }
    exit;
}

header('Location: add-student.php?created=1&adm=' . urlencode($admissionNo) . '&pwd=' . urlencode($password));
exit;