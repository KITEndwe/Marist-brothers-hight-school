<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$role = 'student';
$active = 'timetable';
$u = current_user();
$pdo = getDB();

$stmt = $pdo->prepare('SELECT s.*, c.name AS class_name FROM students s JOIN classes c ON c.id = s.class_id WHERE s.user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$student = $stmt->fetch();

if (!$student) {
    die('No student record linked to this account yet. Please contact the school administrator.');
}

$stmt = $pdo->prepare(
    "SELECT t.day_of_week, t.start_time, t.room, sub.name AS subject_name
     FROM timetable t JOIN subjects sub ON sub.id = t.subject_id
     WHERE t.class_id = :cid
     ORDER BY t.day_of_week, t.start_time"
);
$stmt->execute(['cid' => $student['class_id']]);
$rows = $stmt->fetchAll();

$byDay = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []]; // Monday - Friday
foreach ($rows as $r) {
    if (isset($byDay[$r['day_of_week']])) {
        $byDay[$r['day_of_week']][] = $r;
    }
}
$todayDow = (int)date('N');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Timetable · MBN Portal</title>
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
        <h1>Timetable</h1>
        <div class="meta">Grade <?= h($student['class_name']) ?> · Weekly schedule</div>
      </div>
    </div>

    <div class="week-grid">
      <?php foreach ($byDay as $dow => $classes): ?>
      <div class="panel" style="<?= $dow === $todayDow ? 'border-color:var(--gold); box-shadow:0 0 0 2px rgba(185,139,51,.15);' : '' ?>">
        <div class="panel-head">
          <h3><?= h(day_name($dow)) ?></h3>
          <?php if ($dow === $todayDow): ?><span class="badge badge-paid">Today</span><?php endif; ?>
        </div>
        <ul class="timeline">
          <?php foreach ($classes as $c): ?>
          <li>
            <time><?= date('H:i', strtotime($c['start_time'])) ?></time>
            <div><b><?= h($c['subject_name']) ?></b><br><span class="cat"><?= h($c['room']) ?></span></div>
          </li>
          <?php endforeach; ?>
          <?php if (!$classes): ?>
            <li style="color:var(--slate); font-size:12.5px;">No classes.</li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>