<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$role = 'student';
$active = 'grades';
$u = current_user();
$pdo = getDB();

$stmt = $pdo->prepare('SELECT s.*, c.name AS class_name FROM students s JOIN classes c ON c.id = s.class_id WHERE s.user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$student = $stmt->fetch();

if (!$student) {
    die('No student record linked to this account yet. Please contact the school administrator.');
}

// ---- All grades this student has, across every term recorded -------------------
$stmt = $pdo->prepare(
    "SELECT g.term, g.score, g.letter_grade, sub.name AS subject_name
     FROM grades g JOIN subjects sub ON sub.id = g.subject_id
     WHERE g.student_id = :sid
     ORDER BY g.term DESC, g.score DESC"
);
$stmt->execute(['sid' => $student['id']]);
$allGrades = $stmt->fetchAll();

// Group by term so multiple terms (once they exist) render as separate report cards.
$byTerm = [];
foreach ($allGrades as $g) {
    $byTerm[$g['term']][] = $g;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Grades · MBN Portal</title>
<script>(function(){try{var t=localStorage.getItem('mbn-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<button id="theme-toggle-btn" class="theme-fab" title="Toggle light / dark theme" aria-label="Toggle theme">🌓</button>

<div class="app">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main">
    <div class="topbar">
      <div>
        <h1>My Grades</h1>
        <div class="meta">Grade <?= h($student['class_name']) ?> · Full report card history</div>
      </div>
    </div>

    <?php foreach ($byTerm as $term => $grades):
        $avg = round(array_sum(array_column($grades, 'score')) / count($grades), 1);
        $gpa = min(round($avg / 25, 1), 4.0);
    ?>
    <div class="panel">
      <div class="panel-head">
        <h3><?= h($term) ?></h3>
        <span class="badge badge-paid">Avg <?= $avg ?>% · GPA <?= $gpa ?></span>
      </div>
      <table class="data-table">
        <thead><tr><th>Subject</th><th>Score</th><th>Grade</th></tr></thead>
        <tbody>
          <?php foreach ($grades as $g): ?>
          <tr>
            <td><?= h($g['subject_name']) ?></td>
            <td><?= number_format($g['score'], 0) ?>%</td>
            <td><span class="badge <?= grade_color($g['letter_grade']) ?>"><?= h($g['letter_grade']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endforeach; ?>

    <?php if (!$byTerm): ?>
    <div class="panel">
      <p style="color:var(--slate); font-size:14px;">No grades have been recorded for you yet — check back once your teachers start entering scores.</p>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>