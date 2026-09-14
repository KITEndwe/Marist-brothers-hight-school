<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$role = 'student';
$active = 'dashboard';
$u = current_user();
$pdo = getDB();
$term = 'Term 2 2025/26';

// ---- Student + class info -------------------------------------------------
$stmt = $pdo->prepare(
    'SELECT s.*, c.name AS class_name, c.id AS class_id
     FROM students s JOIN classes c ON c.id = s.class_id
     WHERE s.user_id = :uid'
);
$stmt->execute(['uid' => $u['id']]);
$student = $stmt->fetch();

if (!$student) {
    die('No student record linked to this account yet. Please contact the school administrator.');
}

// ---- Grades for the current term ------------------------------------------
$stmt = $pdo->prepare(
    'SELECT sub.name AS subject_name, g.score, g.letter_grade
     FROM grades g JOIN subjects sub ON sub.id = g.subject_id
     WHERE g.student_id = :sid AND g.term = :term
     ORDER BY g.score DESC'
);
$stmt->execute(['sid' => $student['id'], 'term' => $term]);
$grades = $stmt->fetchAll();

$avgScore = $grades ? round(array_sum(array_column($grades, 'score')) / count($grades), 1) : 0;
$gpa = round($avgScore / 25, 1); // simple 100pt -> 4.0 scale approximation
if ($gpa > 4) $gpa = 4.0;

// ---- Class rank (by average score across the class) ------------------------
$stmt = $pdo->prepare(
    'SELECT g.student_id, AVG(g.score) AS avg_score
     FROM grades g JOIN students st ON st.id = g.student_id
     WHERE st.class_id = :cid AND g.term = :term
     GROUP BY g.student_id ORDER BY avg_score DESC'
);
$stmt->execute(['cid' => $student['class_id'], 'term' => $term]);
$classRanking = $stmt->fetchAll();
$rank = 1; $classSize = count($classRanking) ?: 1;
foreach ($classRanking as $i => $row) {
    if ((int)$row['student_id'] === (int)$student['id']) { $rank = $i + 1; break; }
}

// ---- Fees -------------------------------------------------------------------
$stmt = $pdo->prepare('SELECT * FROM fees WHERE student_id = :sid ORDER BY id DESC LIMIT 1');
$stmt->execute(['sid' => $student['id']]);
$fee = $stmt->fetch() ?: ['amount_billed' => 0, 'amount_paid' => 0, 'term' => $term];
$balanceDue = $fee['amount_billed'] - $fee['amount_paid'];
$paidPct = $fee['amount_billed'] > 0 ? round(($fee['amount_paid'] / $fee['amount_billed']) * 100) : 100;

// ---- Today's timetable -------------------------------------------------------
$today = (int)date('N');
$stmt = $pdo->prepare(
    'SELECT t.start_time, t.room, sub.name AS subject_name
     FROM timetable t JOIN subjects sub ON sub.id = t.subject_id
     WHERE t.class_id = :cid AND t.day_of_week = :dow
     ORDER BY t.start_time'
);
$stmt->execute(['cid' => $student['class_id'], 'dow' => $today]);
$todayClasses = $stmt->fetchAll();

// ---- Announcements ------------------------------------------------------------
$stmt = $pdo->query(
    "SELECT * FROM announcements WHERE audience IN ('all','students') ORDER BY posted_date DESC LIMIT 4"
);
$announcements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard · MBN Portal</title>
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
        <h1>Student Dashboard</h1>
        <div class="meta"><?= date('l, j F Y') ?> · <?= h($term) ?></div>
      </div>
      <span class="term-pill">🗓 <?= h($term) ?></span>
    </div>

    <div class="banner">
      <h2>Good <?= (int)date('H') < 12 ? 'morning' : ((int)date('H') < 17 ? 'afternoon' : 'evening') ?>, <?= h(explode(' ', $u['full_name'])[0]) ?> 👋</h2>
      <p>Grade <span class="accent"><?= h($student['class_name']) ?></span> · Roll No. <?= h($student['roll_no']) ?> · Attendance: <?= h($student['attendance_pct']) ?>% · Current GPA: <span class="accent"><?= $gpa ?> / 4.0</span></p>
    </div>

    <div class="stat-grid">
      <div class="stat-card accent-gold">
        <div class="tag">🏆</div>
        <div class="label">Class Rank</div>
        <div class="value">#<?= $rank ?> / <?= $classSize ?></div>
        <div class="foot">✔ Active Enrollment</div>
      </div>
      <div class="stat-card accent-ink">
        <div class="tag">📘</div>
        <div class="label">Subjects Enrolled</div>
        <div class="value"><?= count($grades) ?: 6 ?></div>
        <div class="foot" style="color:var(--slate)">This term</div>
      </div>
      <div class="stat-card accent-sage">
        <div class="tag">✅</div>
        <div class="label">Attendance</div>
        <div class="value"><?= h($student['attendance_pct']) ?>%</div>
        <div class="foot">This term</div>
      </div>
      <div class="stat-card accent-maroon">
        <div class="tag">💳</div>
        <div class="label">Balance Due</div>
        <div class="value">$<?= number_format($balanceDue, 0) ?></div>
        <div class="foot" style="color:<?= $balanceDue > 0 ? 'var(--maroon)' : 'var(--sage)' ?>"><?= $balanceDue > 0 ? 'Payment pending' : 'All cleared ✓' ?></div>
      </div>
    </div>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head">
            <h3>Report Card — <?= h($term) ?></h3>
            <a href="grades.php">View All →</a>
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
              <?php if (!$grades): ?>
              <tr><td colspan="3" style="color:var(--slate)">No grades recorded for this term yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="panel">
          <div class="panel-head"><h3>Today's Classes — <?= date('l') ?></h3></div>
          <ul class="timeline">
            <?php foreach ($todayClasses as $c): ?>
            <li>
              <time><?= date('H:i', strtotime($c['start_time'])) ?></time>
              <div><b><?= h($c['subject_name']) ?></b> <span class="cat"><?= h($c['room']) ?></span></div>
            </li>
            <?php endforeach; ?>
            <?php if (!$todayClasses): ?>
              <li style="color:var(--slate)">No classes scheduled for today.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Announcements</h3></div>
          <ul class="timeline">
            <?php foreach ($announcements as $a): ?>
            <li>
              <time><?= date('M j', strtotime($a['posted_date'])) ?></time>
              <div><b><?= h($a['title']) ?></b><br><span class="cat"><?= h($a['category']) ?></span></div>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="panel">
          <div class="panel-head"><h3>Fee Status</h3></div>
          <div style="display:flex; justify-content:space-between; align-items:baseline;">
            <span class="value" style="font-family:var(--font-data); font-size:22px;"><?= $paidPct ?>%</span>
            <span class="badge <?= $paidPct >= 100 ? 'badge-paid' : ($paidPct > 0 ? 'badge-partial' : 'badge-due') ?>"><?= $paidPct >= 100 ? 'Paid' : 'In progress' ?></span>
          </div>
          <div class="progress-bar"><div style="width:<?= min($paidPct,100) ?>%"></div></div>
          <p style="font-size:12.5px; color:var(--slate); margin-top:10px;">
            <?= h($fee['term']) ?>: $<?= number_format($fee['amount_billed'],0) ?> · Paid: $<?= number_format($fee['amount_paid'],0) ?>
          </p>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
