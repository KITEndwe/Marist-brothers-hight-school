<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$role = 'admin';
$active = 'reports';
$u = current_user();
$pdo = getDB();

// ---- Term selector (default to the most recent term in grades) -----------
$terms = $pdo->query('SELECT DISTINCT term FROM grades ORDER BY term DESC')->fetchAll(PDO::FETCH_COLUMN);
$term = trim($_GET['term'] ?? '');
if ($term === '' && !empty($terms)) {
    $term = $terms[0];
}

$classId = (int)($_GET['class_id'] ?? 0);
$classes = $pdo->query('SELECT id, name, grade_level FROM classes ORDER BY grade_level, name')->fetchAll();

// ---- School-wide overview: one row per class for the selected term -------
$overview = [];
if ($term !== '') {
    $stmt = $pdo->prepare(
        "SELECT c.id, c.name, c.grade_level,
                (SELECT ROUND(AVG(g.score),1)
                   FROM grades g JOIN subjects sub ON sub.id = g.subject_id
                   WHERE sub.class_id = c.id AND g.term = ?) AS avg_score,
                (SELECT ROUND(AVG(s.attendance_pct),1) FROM students s WHERE s.class_id = c.id) AS avg_attendance,
                (SELECT SUM(f.amount_billed) FROM fees f JOIN students s ON s.id = f.student_id
                   WHERE s.class_id = c.id AND f.term = ?) AS billed,
                (SELECT SUM(f.amount_paid) FROM fees f JOIN students s ON s.id = f.student_id
                   WHERE s.class_id = c.id AND f.term = ?) AS paid
         FROM classes c
         ORDER BY c.grade_level, c.name"
    );
    $stmt->execute([$term, $term, $term]);
    $overview = $stmt->fetchAll();
}

// ---- Detailed grade sheet for one class + term ----------------------------
$subjects = [];
$students = [];
$gradesMap = [];
$distribution = [];

if ($classId > 0 && $term !== '') {
    $stmt = $pdo->prepare('SELECT id, name FROM subjects WHERE class_id = ? ORDER BY name');
    $stmt->execute([$classId]);
    $subjects = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT s.id, us.full_name FROM students s
         JOIN users us ON us.id = s.user_id
         WHERE s.class_id = ? ORDER BY us.full_name'
    );
    $stmt->execute([$classId]);
    $students = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT g.student_id, g.subject_id, g.score, g.letter_grade
         FROM grades g JOIN subjects sub ON sub.id = g.subject_id
         WHERE sub.class_id = ? AND g.term = ?'
    );
    $stmt->execute([$classId, $term]);
    foreach ($stmt->fetchAll() as $row) {
        $gradesMap[$row['student_id']][$row['subject_id']] = $row;
        $distribution[$row['letter_grade']] = ($distribution[$row['letter_grade']] ?? 0) + 1;
    }
    ksort($distribution);
}

$selectedClassName = '';
foreach ($classes as $c) {
    if ((int)$c['id'] === $classId) { $selectedClassName = $c['name']; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports · MBN Portal</title>
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
        <h1>Reports</h1>
        <div class="meta"><?= $term !== '' ? h($term) : 'No graded terms yet' ?></div>
      </div>
    </div>

    <?php if (empty($terms)): ?>
      <div class="panel">
        <p style="color:var(--slate); font-size:14px;">No grade records exist yet, so there's nothing to report on. Once grades are entered, this page will summarize performance by class and term.</p>
      </div>
    <?php else: ?>

    <div class="panel">
      <form method="get" action="reports.php" style="display:flex; gap:12px; flex-wrap:wrap;">
        <select name="term" onchange="this.form.submit()" style="padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          <?php foreach ($terms as $t): ?>
            <option value="<?= h($t) ?>" <?= $term === $t ? 'selected' : '' ?>><?= h($t) ?></option>
          <?php endforeach; ?>
        </select>

        <select name="class_id" onchange="this.form.submit()" style="padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          <option value="0">School-wide overview</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $classId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= h($c['name']) ?> (Grade <?= (int)$c['grade_level'] ?>) — full report
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <?php if ($classId === 0): ?>
      <!-- ============ SCHOOL-WIDE OVERVIEW ============ -->
      <div class="panel">
        <div class="panel-head"><h3>Performance by Class — <?= h($term) ?></h3></div>
        <table class="data-table">
          <thead>
            <tr><th>Class</th><th>Avg. Score</th><th>Avg. Attendance</th><th>Fee Collection</th></tr>
          </thead>
          <tbody>
            <?php foreach ($overview as $row):
                $billed = (float)($row['billed'] ?? 0);
                $paid   = (float)($row['paid'] ?? 0);
                $rate   = $billed > 0 ? round(($paid / $billed) * 100) : null;
            ?>
            <tr>
              <td><?= h($row['name']) ?> <span style="color:var(--slate); font-size:12px;">(Gr. <?= (int)$row['grade_level'] ?>)</span></td>
              <td><?= $row['avg_score'] !== null ? $row['avg_score'] . '%' : '—' ?></td>
              <td><?= $row['avg_attendance'] !== null ? $row['avg_attendance'] . '%' : '—' ?></td>
              <td><?= $rate !== null ? $rate . '%' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <?php else: ?>
      <!-- ============ DETAILED CLASS REPORT ============ -->
      <div class="panel">
        <div class="panel-head">
          <h3><?= h($selectedClassName) ?> — Grade Sheet · <?= h($term) ?></h3>
        </div>

        <?php if (empty($subjects) || empty($students)): ?>
          <p style="color:var(--slate); font-size:14px;">No subjects or students found for this class.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Student</th>
                <?php foreach ($subjects as $sub): ?>
                  <th><?= h($sub['name']) ?></th>
                <?php endforeach; ?>
                <th>Average</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $st):
                  $scores = [];
              ?>
              <tr>
                <td><?= h($st['full_name']) ?></td>
                <?php foreach ($subjects as $sub):
                    $g = $gradesMap[$st['id']][$sub['id']] ?? null;
                    if ($g !== null) $scores[] = (float)$g['score'];
                ?>
                  <td><?= $g !== null ? number_format((float)$g['score'], 0) . ' (' . h($g['letter_grade']) . ')' : '—' ?></td>
                <?php endforeach; ?>
                <td><b><?= !empty($scores) ? number_format(array_sum($scores) / count($scores), 1) : '—' ?></b></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($distribution)): ?>
      <div class="panel">
        <div class="panel-head"><h3>Grade Distribution</h3></div>
        <div style="display:flex; gap:20px; flex-wrap:wrap;">
          <?php foreach ($distribution as $letter => $count): ?>
            <div style="text-align:center;">
              <div style="font-size:22px; font-weight:700;"><?= (int)$count ?></div>
              <div style="font-size:13px; color:var(--slate);"><?= h($letter) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    <?php endif; ?>

    <?php endif; ?>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>