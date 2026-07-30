<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('teacher');

$role = 'teacher';
$active = 'classes';
$u = current_user();
$pdo = getDB();
$term = 'Term 2 2025/26';

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT sub.id AS subject_id, sub.name AS subject_name, c.id AS class_id, c.name AS class_name,
            (SELECT COUNT(*) FROM students st WHERE st.class_id = c.id) AS student_count,
            (SELECT ROUND(AVG(g.score),1) FROM grades g WHERE g.subject_id = sub.id AND g.term = :term) AS avg_score
     FROM subjects sub JOIN classes c ON c.id = sub.class_id
     WHERE sub.teacher_id = :tid ORDER BY c.name, sub.name"
);
$stmt->execute(['tid' => $teacher['id'], 'term' => $term]);
$myClasses = $stmt->fetchAll();

// Which class's roster to show (defaults to the first one)
$selectedClassId = (int)($_GET['class_id'] ?? ($myClasses[0]['class_id'] ?? 0));

$roster = [];
if ($selectedClassId) {
    $stmt = $pdo->prepare(
        "SELECT s.id AS student_id, s.roll_no, s.attendance_pct, us.full_name, us.avatar_initials, us.profile_photo,
                (SELECT ROUND(AVG(g.score),1) FROM grades g WHERE g.student_id = s.id AND g.term = :term) AS avg_score
         FROM students s JOIN users us ON us.id = s.user_id
         WHERE s.class_id = :cid ORDER BY us.full_name"
    );
    $stmt->execute(['term' => $term, 'cid' => $selectedClassId]);
    $roster = $stmt->fetchAll();
}

$classNames = array_unique(array_column($myClasses, 'class_name'));
$selectedClassName = '';
foreach ($myClasses as $c) {
    if ((int)$c['class_id'] === $selectedClassId) { $selectedClassName = $c['class_name']; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Classes · MBN Portal</title>
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
        <h1>My Classes</h1>
        <div class="meta"><?= count($classNames) ?> class<?= count($classNames) === 1 ? '' : 'es' ?> · <?= h($term) ?></div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Subjects &amp; Performance Overview</h3></div>
      <table class="data-table">
        <thead><tr><th>Class</th><th>Subject</th><th>Students</th><th>Avg Score</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($myClasses as $c): ?>
          <tr>
            <td><b><?= h($c['class_name']) ?></b></td>
            <td><?= h($c['subject_name']) ?></td>
            <td><?= (int)$c['student_count'] ?></td>
            <td><?= $c['avg_score'] !== null ? $c['avg_score'].'%' : '—' ?></td>
            <td><a href="classes.php?class_id=<?= (int)$c['class_id'] ?>" style="color:var(--maroon); font-weight:600; font-size:12.5px;">View Roster →</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$myClasses): ?>
          <tr><td colspan="5" style="color:var(--slate)">You are not assigned to any subjects yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($selectedClassId): ?>
    <div class="panel">
      <div class="panel-head"><h3>Roster — <?= h($selectedClassName) ?></h3></div>
      <table class="data-table">
        <thead><tr><th>Student</th><th>Roll No.</th><th>Attendance</th><th>Avg Score (<?= h($term) ?>)</th></tr></thead>
        <tbody>
          <?php foreach ($roster as $s): ?>
          <tr>
            <td style="display:flex; align-items:center; gap:8px;">
              <?= avatar_html($s['profile_photo'], $s['avatar_initials'], '') ?>
              <style>.data-table .avatar{width:26px;height:26px;font-size:10px;}</style>
              <?= h($s['full_name']) ?>
            </td>
            <td><?= h($s['roll_no']) ?></td>
            <td><?= number_format($s['attendance_pct'], 0) ?>%</td>
            <td><?= $s['avg_score'] !== null ? $s['avg_score'].'%' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$roster): ?>
          <tr><td colspan="4" style="color:var(--slate)">No learners found for this class.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>