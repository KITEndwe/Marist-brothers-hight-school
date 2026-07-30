<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: fees.php');
    exit;
}

$pdo = getDB();

$studentId    = (int)($_POST['student_id'] ?? 0);
$term         = trim($_POST['term'] ?? '');
$amountBilled = (float)($_POST['amount_billed'] ?? 0);
$amountPaid   = (float)($_POST['amount_paid'] ?? 0);

if (!$studentId || $term === '' || $amountBilled <= 0 || $amountPaid < 0) {
    header('Location: fees.php?error=' . urlencode('Please choose a student and enter valid amounts.'));
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM fees WHERE student_id = :sid AND term = :term LIMIT 1');
$stmt->execute(['sid' => $studentId, 'term' => $term]);
$existing = $stmt->fetch();

if ($existing) {
    // Never let a payment push amount_paid above amount_billed.
    $stmt = $pdo->prepare(
        'UPDATE fees SET amount_billed = :billed,
                         amount_paid = LEAST(:billed2, amount_paid + :paid)
         WHERE id = :id'
    );
    $stmt->execute([
        'billed'  => $amountBilled,
        'billed2' => $amountBilled,
        'paid'    => $amountPaid,
        'id'      => $existing['id'],
    ]);
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO fees (student_id, term, amount_billed, amount_paid)
         VALUES (:sid, :term, :billed, LEAST(:billed2, :paid))'
    );
    $stmt->execute([
        'sid'     => $studentId,
        'term'    => $term,
        'billed'  => $amountBilled,
        'billed2' => $amountBilled,
        'paid'    => $amountPaid,
    ]);
}

header('Location: fees.php?saved=1');
exit;