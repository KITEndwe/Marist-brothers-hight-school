<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$role = 'student';
$active = 'resources';
$u = current_user();
$pdo = getDB();

$stmt = $pdo->prepare('SELECT * FROM students WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$student = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT pp.*, sub.name AS subject_name, us.full_name AS teacher_name
     FROM past_papers pp
     JOIN subjects sub ON sub.id = pp.subject_id
     JOIN teachers t ON t.id = pp.teacher_id
     JOIN users us ON us.id = t.user_id
     WHERE sub.class_id = :cid
     ORDER BY pp.uploaded_at DESC"
);
$stmt->execute(['cid' => $student['class_id']]);
$papers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Past Papers · MBN Portal</title>
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
        <h1>Past Papers</h1>
        <div class="meta">Uploaded by your subject teachers</div>
      </div>
    </div>

    <div class="panel">
      <table class="data-table">
        <thead><tr><th>Title</th><th>Subject</th><th>Uploaded By</th><th>Term</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($papers as $p): ?>
          <tr>
            <td><b><?= h($p['title']) ?></b></td>
            <td><?= h($p['subject_name']) ?></td>
            <td><?= h($p['teacher_name']) ?></td>
            <td><?= h($p['term'] ?? '—') ?></td>
            <td><a href="../uploads/<?= h($p['file_path']) ?>" target="_blank" style="color:var(--maroon); font-weight:600; font-size:13px;">Download →</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$papers): ?>
          <tr><td colspan="5" style="color:var(--slate)">No past papers have been uploaded for your class yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
