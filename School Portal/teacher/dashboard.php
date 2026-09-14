<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('teacher');

$role = 'teacher';
$active = 'dashboard';
$u = current_user();
$pdo = getDB();
$term = 'Term 2 2025/26';

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

if (!$teacher) {
    die('No teacher record linked to this account yet. Please contact the school administrator.');
}

// ---- Classes / subjects this teacher runs, with live avg score ------------
$stmt = $pdo->prepare(
    "SELECT sub.id AS subject_id, sub.name AS subject_name, c.name AS class_name, c.id as class_id,
            (SELECT COUNT(*) FROM students st WHERE st.class_id = c.id) AS student_count,
            (SELECT ROUND(AVG(g.score),1) FROM grades g WHERE g.subject_id = sub.id AND g.term = :term) AS avg_score
     FROM subjects sub JOIN classes c ON c.id = sub.class_id
     WHERE sub.teacher_id = :tid
     ORDER BY c.name"
);
$stmt->execute(['tid' => $teacher['id'], 'term' => $term]);
$myClasses = $stmt->fetchAll();

$totalStudents = array_sum(array_column($myClasses, 'student_count'));
$avgAll = $myClasses ? round(array_sum(array_filter(array_column($myClasses, 'avg_score'))) / max(1, count(array_filter(array_column($myClasses, 'avg_score')))), 1) : 0;

// ---- Assignments + pending submissions -------------------------------------
$stmt = $pdo->prepare(
    "SELECT a.id, a.title, a.due_date, sub.name AS subject_name, c.name AS class_name,
            asub.id AS sub_id, asub.status, asub.is_late, st.id AS student_id, us.full_name, us.avatar_initials
     FROM assignments a
     JOIN subjects sub ON sub.id = a.subject_id
     JOIN classes c ON c.id = sub.class_id
     LEFT JOIN assignment_submissions asub ON asub.assignment_id = a.id
     LEFT JOIN students st ON st.id = asub.student_id
     LEFT JOIN users us ON us.id = st.user_id
     WHERE a.teacher_id = :tid AND (asub.status = 'submitted' OR asub.status IS NULL)
     ORDER BY a.due_date DESC LIMIT 6"
);
$stmt->execute(['tid' => $teacher['id']]);
$pendingReviews = $stmt->fetchAll();

$assignmentCountStmt = $pdo->prepare('SELECT COUNT(*) FROM assignments WHERE teacher_id = :tid');
$assignmentCountStmt->execute(['tid' => $teacher['id']]);
$assignmentCount = (int)$assignmentCountStmt->fetchColumn();

// ---- Today's schedule --------------------------------------------------------
$today = (int)date('N');
$stmt = $pdo->prepare(
    "SELECT t.start_time, t.room, sub.name AS subject_name, c.name AS class_name
     FROM timetable t
     JOIN subjects sub ON sub.id = t.subject_id
     JOIN classes c ON c.id = t.class_id
     WHERE sub.teacher_id = :tid AND t.day_of_week = :dow
     ORDER BY t.start_time"
);
$stmt->execute(['tid' => $teacher['id'], 'dow' => $today]);
$todaySchedule = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher Dashboard · MBN Portal</title>
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
        <h1>Teacher Dashboard</h1>
        <div class="meta"><?= h($u['full_name']) ?> · <?= h($teacher['department']) ?> · <?= h($term) ?></div>
      </div>
      <span class="term-pill">🗓 <?= h($term) ?></span>
    </div>

    <div class="banner">
      <h2>Welcome back, <?= h($u['full_name']) ?> 👋</h2>
      <p><span class="accent"><?= count($todaySchedule) ?> classes today</span> · <?= h(implode(', ', array_unique(array_column($myClasses,'class_name')))) ?> · <span class="accent"><?= count($pendingReviews) ?> pending submissions</span></p>
    </div>

    <div class="stat-grid">
      <div class="stat-card accent-gold">
        <div class="tag">🎓</div>
        <div class="label">My Students</div>
        <div class="value"><?= $totalStudents ?></div>
        <div class="foot" style="color:var(--slate)"><?= count($myClasses) ?> active classes</div>
      </div>
      <div class="stat-card accent-ink">
        <div class="tag">📝</div>
        <div class="label">Assignments</div>
        <div class="value"><?= $assignmentCount ?></div>
        <div class="foot"><?= count($pendingReviews) ?> pending review</div>
      </div>
      <div class="stat-card accent-sage">
        <div class="tag">⭐</div>
        <div class="label">Avg. Class Score</div>
        <div class="value"><?= $avgAll ?>%</div>
        <div class="foot" style="color:var(--slate)">This term</div>
      </div>
      <div class="stat-card accent-maroon">
        <div class="tag">🗓</div>
        <div class="label">Today's Classes</div>
        <div class="value"><?= count($todaySchedule) ?></div>
        <div class="foot" style="color:var(--slate)"><?= $todaySchedule ? 'Next: ' . date('H:i', strtotime($todaySchedule[0]['start_time'])) : 'None today' ?></div>
      </div>
    </div>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>My Classes — Performance Overview</h3></div>
          <table class="data-table">
            <thead><tr><th>Class</th><th>Subject</th><th>Students</th><th>Avg Score</th></tr></thead>
            <tbody>
              <?php foreach ($myClasses as $c): ?>
              <tr>
                <td><b><?= h($c['class_name']) ?></b></td>
                <td><?= h($c['subject_name']) ?></td>
                <td><?= (int)$c['student_count'] ?></td>
                <td><?= $c['avg_score'] !== null ? $c['avg_score'] . '%' : '—' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="panel">
          <div class="panel-head"><h3>Pending Reviews <span class="badge badge-partial"><?= count($pendingReviews) ?> new</span></h3></div>
          <ul class="row-list">
            <?php foreach ($pendingReviews as $p): ?>
            <li>
              <div class="avatar" style="width:30px;height:30px;font-size:11px;"><?= h($p['avatar_initials'] ?? '??') ?></div>
              <div style="flex:1;">
                <div class="name"><?= h($p['full_name'] ?? 'Unassigned') ?></div>
                <div class="sub"><?= h($p['title']) ?> · Class <?= h($p['class_name']) ?></div>
              </div>
              <span class="badge <?= $p['is_late'] ? 'badge-late' : 'badge-ontime' ?>"><?= $p['is_late'] ? 'Late' : 'On time' ?></span>
            </li>
            <?php endforeach; ?>
            <?php if (!$pendingReviews): ?>
              <li style="color:var(--slate)">Nothing to review right now.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Today's Schedule</h3></div>
          <ul class="timeline">
            <?php foreach ($todaySchedule as $s): ?>
            <li>
              <time><?= date('H:i', strtotime($s['start_time'])) ?></time>
              <div><b><?= h($s['class_name']) ?></b> — <?= h($s['subject_name']) ?><br><span class="cat"><?= h($s['room']) ?></span></div>
            </li>
            <?php endforeach; ?>
            <?php if (!$todaySchedule): ?>
              <li style="color:var(--slate)">No classes scheduled for today.</li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="panel">
          <div class="panel-head"><h3>Quick Grade Entry</h3></div>
          <form class="form-inline" action="grade_save.php" method="POST">
            <div class="field">
              <label>Student</label>
              <input type="text" name="student_search" placeholder="Search student...">
            </div>
            <div class="field">
              <label>Score (0–100)</label>
              <input type="number" name="score" min="0" max="100" placeholder="e.g. 88">
            </div>
            <button type="submit" class="btn-small">Submit</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
