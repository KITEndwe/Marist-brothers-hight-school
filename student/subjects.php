<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$role = 'student';
$active = 'subjects';
$u = current_user();
$pdo = getDB();
$term = 'Term 2 2025/26';

$stmt = $pdo->prepare('SELECT s.*, c.name AS class_name FROM students s JOIN classes c ON c.id = s.class_id WHERE s.user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$student = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT sub.name AS subject_name, us.full_name AS teacher_name, us.avatar_initials, us.profile_photo,
            (SELECT ROUND(AVG(g.score),1) FROM grades g WHERE g.subject_id = sub.id AND g.student_id = :sid AND g.term = :term) AS my_score
     FROM subjects sub
     JOIN teachers t ON t.id = sub.teacher_id
     JOIN users us ON us.id = t.user_id
     WHERE sub.class_id = :cid
     ORDER BY sub.name'
);
$stmt->execute(['sid' => $student['id'], 'cid' => $student['class_id'], 'term' => $term]);
$subjects = $stmt->fetchAll();
require_once __DIR__ . '/../includes/upload.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Subjects · MBN Portal</title>
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
        <h1>My Subjects</h1>
        <div class="meta">Grade <?= h($student['class_name']) ?> · <?= h($term) ?></div>
      </div>
    </div>

    <div class="panel">
      <table class="data-table">
        <thead><tr><th>Subject</th><th>Teacher</th><th>My Average</th></tr></thead>
        <tbody>
          <?php foreach ($subjects as $s): ?>
          <tr>
            <td><b><?= h($s['subject_name']) ?></b></td>
            <td style="display:flex; align-items:center; gap:8px;">
              <?= avatar_html($s['profile_photo'], $s['avatar_initials'], '') ?>
              <style>.data-table .avatar{width:26px;height:26px;font-size:10px;}</style>
              <?= h($s['teacher_name']) ?>
            </td>
            <td><?= $s['my_score'] !== null ? $s['my_score'] . '%' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$subjects): ?>
          <tr><td colspan="3" style="color:var(--slate)">No subjects assigned yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
