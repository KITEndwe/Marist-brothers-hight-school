<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: grades.php');
    exit;
}

$pdo = getDB();
$u = current_user();
$term = 'Term 2 2025/26';

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

$studentId = (int)($_POST['student_id'] ?? 0);
$subjectId = (int)($_POST['subject_id'] ?? 0);
$score     = (float)($_POST['score'] ?? -1);

if (!$studentId || !$subjectId || $score < 0 || $score > 100) {
    header('Location: grades.php?subject_id=' . $subjectId . '&error=' . urlencode('Please enter a score between 0 and 100.'));
    exit;
}

// Confirm this subject actually belongs to this teacher.
$check = $pdo->prepare('SELECT id FROM subjects WHERE id = :sid AND teacher_id = :tid');
$check->execute(['sid' => $subjectId, 'tid' => $teacher['id']]);
if (!$check->fetch()) {
    header('Location: grades.php?error=' . urlencode('That subject is not assigned to you.'));
    exit;
}

$letter = score_to_letter($score);

$stmt = $pdo->prepare('SELECT id FROM grades WHERE student_id = :sid AND subject_id = :subid AND term = :term');
$stmt->execute(['sid' => $studentId, 'subid' => $subjectId, 'term' => $term]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $pdo->prepare('UPDATE grades SET score = :score, letter_grade = :letter WHERE id = :id');
    $stmt->execute(['score' => $score, 'letter' => $letter, 'id' => $existing['id']]);
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO grades (student_id, subject_id, term, score, letter_grade)
         VALUES (:sid, :subid, :term, :score, :letter)'
    );
    $stmt->execute(['sid' => $studentId, 'subid' => $subjectId, 'term' => $term, 'score' => $score, 'letter' => $letter]);
}

header('Location: grades.php?subject_id=' . $subjectId . '&saved=1');
exit;