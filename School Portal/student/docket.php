<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$u = current_user();
$pdo = getDB();

$stmt = $pdo->prepare(
    'SELECT s.*, c.name AS class_name FROM students s JOIN classes c ON c.id = s.class_id WHERE s.user_id = :uid'
);
$stmt->execute(['uid' => $u['id']]);
$student = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM fees WHERE student_id = :sid ORDER BY id DESC LIMIT 1');
$stmt->execute(['sid' => $student['id']]);
$fee = $stmt->fetch() ?: ['amount_billed' => 0, 'amount_paid' => 0, 'term' => 'N/A'];
$balance = $fee['amount_billed'] - $fee['amount_paid'];

// Hard gate: never render a docket while a balance is outstanding.
if ($balance > 0) {
    header('Location: fees.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT sub.name FROM subjects sub WHERE sub.class_id = :cid ORDER BY sub.name'
);
$stmt->execute(['cid' => $student['class_id']]);
$subjects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exam Docket · <?= h($u['full_name']) ?></title>
<style>
  body{ font-family: 'Work Sans', sans-serif; background:#fff; color:#16273F; padding:40px; }
  .docket{ max-width:640px; margin:0 auto; border:2px solid #16273F; border-radius:10px; padding:32px; }
  .docket h1{ font-family: Georgia, serif; font-size:22px; text-align:center; margin:0 0 4px; }
  .docket .sub{ text-align:center; color:#5B6472; font-size:13px; margin-bottom:22px; }
  table{ width:100%; border-collapse:collapse; margin-top:16px; }
  td{ padding:8px 4px; border-bottom:1px solid #DED5C1; font-size:14px; }
  td.label{ color:#5B6472; width:40%; }
  .stamp{ margin-top:28px; text-align:center; font-size:12px; color:#4C7A64; font-weight:700; border:2px solid #4C7A64; display:inline-block; padding:6px 18px; border-radius:6px; }
  .center{ text-align:center; }
  .print-btn{ display:block; margin:20px auto 0; padding:10px 20px; background:#16273F; color:#fff; border:0; border-radius:8px; cursor:pointer; font-size:13px; }
  @media print { .print-btn{ display:none; } }
</style>
</head>
<body>
  <div class="docket">
    <h1>Marist Brothers and Nyanga High School</h1>
    <div class="sub">Term Examination Admission Docket</div>

    <table>
      <tr><td class="label">Student Name</td><td><?= h($u['full_name']) ?></td></tr>
      <tr><td class="label">Student Number</td><td><?= h($student['admission_no']) ?></td></tr>
      <tr><td class="label">Class</td><td><?= h($student['class_name']) ?></td></tr>
      <tr><td class="label">Roll No.</td><td><?= h($student['roll_no']) ?></td></tr>
      <tr><td class="label">Term</td><td><?= h($fee['term']) ?></td></tr>
      <tr><td class="label">Fee Status</td><td>Fully Paid ✔</td></tr>
      <tr><td class="label">Subjects Registered</td><td><?= h(implode(', ', array_column($subjects, 'name'))) ?></td></tr>
    </table>

    <div class="center" style="margin-top:24px;">
      <span class="stamp">✔ CLEARED TO SIT TERM EXAMS</span>
    </div>

    <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
  </div>
</body>
</html>
