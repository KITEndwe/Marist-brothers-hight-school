<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('admin');

$role = 'admin';
$active = 'attendance';
$u = current_user();
$pdo = getDB();
$today = date('Y-m-d');
$saved = isset($_GET['saved']);

// ---- Teacher attendance: today's status + overall rate ---------------------
$stmt = $pdo->prepare(
    "SELECT t.id AS teacher_id, us.full_name, us.avatar_initials, us.profile_photo, t.staff_no, t.department,
            ta_today.status AS today_status,
            (SELECT ROUND(SUM(status='present')/COUNT(*)*100,1) FROM teacher_attendance WHERE teacher_id = t.id) AS rate
     FROM teachers t
     JOIN users us ON us.id = t.user_id
     LEFT JOIN teacher_attendance ta_today ON ta_today.teacher_id = t.id AND ta_today.attendance_date = :today
     ORDER BY us.full_name"
);
$stmt->execute(['today' => $today]);
$teacherRows = $stmt->fetchAll();

// ---- Learner attendance per grade -------------------------------------------
$stmt = $pdo->query(
    "SELECT c.grade_level, ROUND(AVG(s.attendance_pct),1) AS avg_attendance, COUNT(s.id) AS student_count
     FROM students s JOIN classes c ON c.id = s.class_id
     GROUP BY c.grade_level ORDER BY c.grade_level"
);
$gradeAttendance = $stmt->fetchAll();

// ---- Learner attendance per class (drill-down) ------------------------------
$stmt = $pdo->query(
    "SELECT c.name AS class_name, c.grade_level, ROUND(AVG(s.attendance_pct),1) AS avg_attendance, COUNT(s.id) AS student_count
     FROM students s JOIN classes c ON c.id = s.class_id
     GROUP BY c.id ORDER BY c.grade_level, c.name"
);
$classAttendance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance · MBN Portal</title>
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
        <h1>Attendance</h1>
        <div class="meta">Teaching staff attendance &amp; learner attendance by grade · <?= date('l, j F Y') ?></div>
      </div>
    </div>

    <?php if ($saved): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Attendance recorded for today.</div>
    <?php endif; ?>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>Teacher Attendance — Today</h3></div>
          <form action="attendance_save.php" method="POST">
          <table class="data-table">
            <thead><tr><th>Teacher</th><th>Department</th><th>Overall Rate</th><th>Today's Status</th></tr></thead>
            <tbody>
              <?php foreach ($teacherRows as $t): ?>
              <tr>
                <td style="display:flex; align-items:center; gap:8px;">
                  <?= avatar_html($t['profile_photo'], $t['avatar_initials'], '') ?>
                  <style>.data-table .avatar{width:26px;height:26px;font-size:10px;}</style>
                  <?= h($t['full_name']) ?>
                </td>
                <td><?= h($t['department']) ?></td>
                <td><?= $t['rate'] !== null ? $t['rate'].'%' : '—' ?></td>
                <td>
                  <select name="status[<?= (int)$t['teacher_id'] ?>]" style="padding:6px 8px; border-radius:6px; border:1.5px solid var(--line); font-size:12.5px; background:var(--white); color:var(--text);">
                    <option value="present" <?= $t['today_status']==='present'?'selected':'' ?>>Present</option>
                    <option value="late" <?= $t['today_status']==='late'?'selected':'' ?>>Late</option>
                    <option value="absent" <?= $t['today_status']==='absent'?'selected':'' ?>>Absent</option>
                  </select>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$teacherRows): ?>
              <tr><td colspan="4" style="color:var(--slate)">No teachers on record yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
          <?php if ($teacherRows): ?>
          <button type="submit" class="btn-small" style="margin-top:14px;">Save Today's Attendance</button>
          <?php endif; ?>
          </form>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Learner Attendance — By Grade</h3></div>
          <?php foreach ($gradeAttendance as $g): ?>
          <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
              <span>Grade <?= (int)$g['grade_level'] ?> <span style="color:var(--slate)">(<?= (int)$g['student_count'] ?> learners)</span></span>
              <span><b><?= $g['avg_attendance'] ?>%</b></span>
            </div>
            <div class="progress-bar"><div style="width:<?= min($g['avg_attendance'],100) ?>%"></div></div>
          </div>
          <?php endforeach; ?>
          <?php if (!$gradeAttendance): ?>
            <p style="color:var(--slate); font-size:13px;">No student attendance data yet.</p>
          <?php endif; ?>
        </div>

        <div class="panel">
          <div class="panel-head"><h3>By Class</h3></div>
          <table class="data-table">
            <thead><tr><th>Class</th><th>Learners</th><th>Attendance</th></tr></thead>
            <tbody>
              <?php foreach ($classAttendance as $c): ?>
              <tr>
                <td><b><?= h($c['class_name']) ?></b></td>
                <td><?= (int)$c['student_count'] ?></td>
                <td><?= $c['avg_attendance'] ?>%</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
