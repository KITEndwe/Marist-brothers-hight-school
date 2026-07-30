<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$role = 'admin';
$active = 'reports';
$u = current_user();
$pdo = getDB();
$term = 'Term 2 2025/26';

// ---- Class performance summary ----------------------------------------------
$stmt = $pdo->prepare(
    "SELECT c.name AS class_name, c.grade_level, COUNT(DISTINCT s.id) AS student_count,
            ROUND(AVG(g.score),1) AS avg_score,
            ROUND(AVG(s.attendance_pct),1) AS avg_attendance
     FROM classes c
     LEFT JOIN students s ON s.class_id = c.id
     LEFT JOIN grades g ON g.student_id = s.id AND g.term = :term
     GROUP BY c.id ORDER BY c.grade_level, c.name"
);
$stmt->execute(['term' => $term]);
$classPerformance = $stmt->fetchAll();

// ---- Fee collection by grade --------------------------------------------------
$stmt = $pdo->query(
    "SELECT c.grade_level, SUM(f.amount_billed) AS billed, SUM(f.amount_paid) AS paid
     FROM fees f JOIN students s ON s.id = f.student_id JOIN classes c ON c.id = s.class_id
     GROUP BY c.grade_level ORDER BY c.grade_level"
);
$feeByGrade = $stmt->fetchAll();

// ---- Attendance summary --------------------------------------------------------
$stmt = $pdo->query(
    "SELECT c.grade_level, ROUND(AVG(s.attendance_pct),1) AS avg_attendance
     FROM students s JOIN classes c ON c.id = s.class_id
     GROUP BY c.grade_level ORDER BY c.grade_level"
);
$attendanceByGrade = $stmt->fetchAll();

// ---- Top / bottom performing students ------------------------------------------
$stmt = $pdo->prepare(
    "SELECT us.full_name, c.name AS class_name, ROUND(AVG(g.score),1) AS avg_score
     FROM grades g
     JOIN students s ON s.id = g.student_id
     JOIN users us ON us.id = s.user_id
     JOIN classes c ON c.id = s.class_id
     WHERE g.term = :term
     GROUP BY s.id ORDER BY avg_score DESC LIMIT 5"
);
$stmt->execute(['term' => $term]);
$topStudents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports · MBN Portal</title>
<script>(function(){try{var t=localStorage.getItem('mbn-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
<link rel="stylesheet" href="../assets/css/style.css">
<style>@media print{ .sidebar, .theme-fab, .no-print{ display:none !important; } .main{ padding:0; } }</style>
</head>
<body>
<button id="theme-toggle-btn" class="theme-fab" title="Toggle light / dark theme" aria-label="Toggle theme">🌓</button>

<div class="app">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main">
    <div class="topbar">
      <div>
        <h1>Reports</h1>
        <div class="meta">Academic Year 2025/26 · <?= h($term) ?></div>
      </div>
      <button class="term-pill no-print" onclick="window.print()" style="cursor:pointer; border:none;">🖨 Print Report</button>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Class Performance Summary</h3></div>
      <table class="data-table">
        <thead><tr><th>Class</th><th>Learners</th><th>Avg. Score</th><th>Avg. Attendance</th></tr></thead>
        <tbody>
          <?php foreach ($classPerformance as $c): ?>
          <tr>
            <td><b>Grade <?= (int)$c['grade_level'] ?> — <?= h($c['class_name']) ?></b></td>
            <td><?= (int)$c['student_count'] ?></td>
            <td><?= $c['avg_score'] !== null ? $c['avg_score'].'%' : '—' ?></td>
            <td><?= $c['avg_attendance'] !== null ? $c['avg_attendance'].'%' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>Fee Collection by Grade</h3></div>
          <?php foreach ($feeByGrade as $g):
              $pct = $g['billed'] > 0 ? round(($g['paid']/$g['billed'])*100) : 0; ?>
          <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
              <span>Grade <?= (int)$g['grade_level'] ?></span><span><b><?= $pct ?>%</b> ($<?= number_format($g['paid'],0) ?> / $<?= number_format($g['billed'],0) ?>)</span>
            </div>
            <div class="progress-bar"><div style="width:<?= $pct ?>%"></div></div>
          </div>
          <?php endforeach; ?>
          <?php if (!$feeByGrade): ?><p style="color:var(--slate); font-size:13px;">No fee data yet.</p><?php endif; ?>
        </div>

        <div class="panel">
          <div class="panel-head"><h3>Attendance by Grade</h3></div>
          <?php foreach ($attendanceByGrade as $a): ?>
          <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
              <span>Grade <?= (int)$a['grade_level'] ?></span><span><b><?= $a['avg_attendance'] ?>%</b></span>
            </div>
            <div class="progress-bar"><div style="width:<?= min($a['avg_attendance'],100) ?>%"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Top 5 Performers — <?= h($term) ?></h3></div>
          <ul class="row-list">
            <?php foreach ($topStudents as $i => $t): ?>
            <li>
              <div class="avatar" style="width:30px;height:30px;font-size:11px;">#<?= $i + 1 ?></div>
              <div style="flex:1;">
                <div class="name"><?= h($t['full_name']) ?></div>
                <div class="sub"><?= h($t['class_name']) ?></div>
              </div>
              <span class="badge grade-a"><?= $t['avg_score'] ?>%</span>
            </li>
            <?php endforeach; ?>
            <?php if (!$topStudents): ?>
              <li style="color:var(--slate)">No grades recorded for this term yet.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>