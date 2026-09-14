<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('admin');

$role = 'admin';
$active = 'teachers';
$u = current_user();
$pdo = getDB();

$stmt = $pdo->query(
    "SELECT t.id, t.staff_no, t.department, us.full_name, us.email, us.avatar_initials, us.profile_photo,
            (SELECT COUNT(*) FROM subjects sub WHERE sub.teacher_id = t.id) AS subject_count,
            (SELECT ROUND(SUM(status='present')/COUNT(*)*100,1) FROM teacher_attendance WHERE teacher_id = t.id) AS attendance_rate
     FROM teachers t JOIN users us ON us.id = t.user_id
     ORDER BY us.full_name"
);
$teachers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teachers · MBN Portal</title>
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
        <h1>Teachers</h1>
        <div class="meta"><?= count($teachers) ?> teaching staff on record</div>
      </div>
      <a href="attendance.php" class="term-pill">✅ Take Attendance</a>
    </div>

    <div class="panel">
      <table class="data-table">
        <thead><tr><th>Teacher</th><th>Staff No.</th><th>Department</th><th>Subjects</th><th>Attendance Rate</th></tr></thead>
        <tbody>
          <?php foreach ($teachers as $t): ?>
          <tr>
            <td style="display:flex; align-items:center; gap:8px;">
              <?= avatar_html($t['profile_photo'], $t['avatar_initials'], '') ?>
              <style>.data-table .avatar{width:28px;height:28px;font-size:10.5px;}</style>
              <div>
                <div><b><?= h($t['full_name']) ?></b></div>
                <div style="font-size:11.5px; color:var(--slate);"><?= h($t['email']) ?></div>
              </div>
            </td>
            <td style="font-family:var(--font-data);"><?= h($t['staff_no']) ?></td>
            <td><?= h($t['department']) ?></td>
            <td><?= (int)$t['subject_count'] ?></td>
            <td><?= $t['attendance_rate'] !== null ? $t['attendance_rate'].'%' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$teachers): ?>
          <tr><td colspan="5" style="color:var(--slate)">No teachers on record yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
