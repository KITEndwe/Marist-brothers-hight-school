<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$role = 'admin';
$active = 'dashboard';
$u = current_user();
$pdo = getDB();
$term = 'Term 2 2025/26';

$totalStudents = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalTeachers = (int)$pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
$departments   = (int)$pdo->query('SELECT COUNT(DISTINCT department) FROM teachers')->fetchColumn();

$feeTotals = $pdo->query('SELECT SUM(amount_billed) AS billed, SUM(amount_paid) AS paid FROM fees')->fetch();
$billed = (float)($feeTotals['billed'] ?? 0);
$paid   = (float)($feeTotals['paid'] ?? 0);
$collectionRate = $billed > 0 ? round(($paid / $billed) * 100) : 0;

$defaulters = (int)$pdo->query('SELECT COUNT(*) FROM fees WHERE amount_paid < amount_billed')->fetchColumn();

// ---- Collection rate per grade level ---------------------------------------
$stmt = $pdo->query(
    "SELECT c.grade_level, SUM(f.amount_billed) AS billed, SUM(f.amount_paid) AS paid
     FROM fees f
     JOIN students s ON s.id = f.student_id
     JOIN classes c ON c.id = s.class_id
     GROUP BY c.grade_level ORDER BY c.grade_level"
);
$byGrade = $stmt->fetchAll();

// ---- Recent students with GPA + fee status ---------------------------------
$stmt = $pdo->query(
    "SELECT us.full_name, us.avatar_initials, c.name AS class_name,
            (SELECT ROUND(AVG(g.score)/25,1) FROM grades g WHERE g.student_id = s.id) AS gpa,
            f.amount_billed, f.amount_paid
     FROM students s
     JOIN users us ON us.id = s.user_id
     JOIN classes c ON c.id = s.class_id
     LEFT JOIN fees f ON f.student_id = s.id
     ORDER BY s.id DESC LIMIT 6"
);
$recentStudents = $stmt->fetchAll();

$incompleteProfiles = (int)$pdo->query("SELECT COUNT(*) FROM teachers t JOIN users u ON u.id=t.user_id WHERE t.department = '' OR t.department IS NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard · MBN Portal</title>
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
        <h1>Admin Dashboard</h1>
        <div class="meta">System Overview · Academic Year 2025/26 · <?= h($term) ?></div>
      </div>
      <span class="term-pill">🟢 Live Mode</span>
    </div>

    <div class="banner">
      <h2>System Overview</h2>
      <p>MBN Portal Admin Console · <span class="accent">All systems operational</span> · Last sync: just now</p>
    </div>

    <div class="stat-grid">
      <div class="stat-card accent-gold">
        <div class="tag">🎓</div>
        <div class="label">Total Students</div>
        <div class="value"><?= number_format($totalStudents) ?></div>
        <div class="foot">Across all grades</div>
      </div>
      <div class="stat-card accent-ink">
        <div class="tag">🧑‍🏫</div>
        <div class="label">Teaching Staff</div>
        <div class="value"><?= $totalTeachers ?></div>
        <div class="foot" style="color:var(--slate)"><?= $departments ?> departments</div>
      </div>
      <div class="stat-card accent-sage">
        <div class="tag">💰</div>
        <div class="label">Fees Collected</div>
        <div class="value">$<?= number_format($paid,0) ?></div>
        <div class="foot" style="color:var(--slate)">of $<?= number_format($billed,0) ?> billed</div>
      </div>
      <div class="stat-card accent-maroon">
        <div class="tag">⚠️</div>
        <div class="label">Fee Defaulters</div>
        <div class="value"><?= $defaulters ?></div>
        <div class="foot" style="color:var(--maroon)">Action required</div>
      </div>
    </div>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head">
            <h3>Fee Collection Rate — Academic Year 2025/26</h3>
            <span class="badge badge-partial"><?= $collectionRate ?>% paid</span>
          </div>
          <?php foreach ($byGrade as $g):
              $pct = $g['billed'] > 0 ? round(($g['paid']/$g['billed'])*100) : 0; ?>
          <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
              <span>Grade <?= (int)$g['grade_level'] ?></span><span><b><?= $pct ?>%</b></span>
            </div>
            <div class="progress-bar"><div style="width:<?= $pct ?>%"></div></div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="panel">
          <div class="panel-head">
            <h3>Recent Students</h3>
            <a href="students.php">View All →</a>
          </div>
          <table class="data-table">
            <thead><tr><th>Student</th><th>Class</th><th>GPA</th><th>Fee Status</th></tr></thead>
            <tbody>
              <?php foreach ($recentStudents as $s):
                  $bill = (float)($s['amount_billed'] ?? 0);
                  $pd = (float)($s['amount_paid'] ?? 0);
                  if ($bill == 0) { $statusLabel='—'; $statusClass=''; }
                  elseif ($pd >= $bill) { $statusLabel='Paid'; $statusClass='badge-paid'; }
                  elseif ($pd > 0) { $statusLabel='Partial'; $statusClass='badge-partial'; }
                  else { $statusLabel='$'.number_format($bill-$pd,0); $statusClass='badge-due'; }
              ?>
              <tr>
                <td style="display:flex; align-items:center; gap:8px;">
                  <div class="avatar" style="width:26px;height:26px;font-size:10px;"><?= h($s['avatar_initials']) ?></div>
                  <?= h($s['full_name']) ?>
                </td>
                <td><?= h($s['class_name']) ?></td>
                <td><?= $s['gpa'] !== null ? $s['gpa'] : '—' ?></td>
                <td><?php if($statusClass): ?><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span><?php else: echo '—'; endif; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Quick Actions</h3></div>
          <div class="quick-actions">
            <a href="add-student.php"><span class="ic">➕</span>Add Student</a>
            <a href="teachers.php"><span class="ic">🧑‍🏫</span>Add Teacher</a>
            <a href="attendance.php"><span class="ic">✅</span>Take Attendance</a>
            <a href="fees.php"><span class="ic">💰</span>Fee Reminder</a>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head"><h3>System Alerts <span class="badge badge-due">3</span></h3></div>
          <ul class="alert-list">
            <li><?= $defaulters ?> students have unpaid fees</li>
            <li>Term 2 reports due in 12 days</li>
            <li><?= $incompleteProfiles ?> teacher profiles incomplete</li>
          </ul>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
