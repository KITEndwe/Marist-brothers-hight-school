<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('admin');

$role = 'admin';
$active = 'students';
$u = current_user();
$pdo = getDB();

$search   = trim($_GET['q'] ?? '');
$classId  = (int)($_GET['class_id'] ?? 0);

$stmt = $pdo->query('SELECT id, name, grade_level FROM classes ORDER BY grade_level, name');
$classes = $stmt->fetchAll();

$sql = "SELECT s.id, s.admission_no, s.roll_no, s.attendance_pct, us.full_name, us.email, us.avatar_initials, us.profile_photo,
               c.name AS class_name, c.id AS class_id,
               (SELECT ROUND(AVG(g.score)/25,1) FROM grades g WHERE g.student_id = s.id) AS gpa,
               (SELECT SUM(amount_billed) FROM fees f WHERE f.student_id = s.id) AS billed,
               (SELECT SUM(amount_paid) FROM fees f WHERE f.student_id = s.id) AS paid
        FROM students s
        JOIN users us ON us.id = s.user_id
        JOIN classes c ON c.id = s.class_id
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (us.full_name LIKE :q OR s.admission_no LIKE :q OR us.email LIKE :q)";
    $params['q'] = "%$search%";
}
if ($classId) {
    $sql .= " AND c.id = :cid";
    $params['cid'] = $classId;
}
$sql .= " ORDER BY us.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$totalCount = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students · MBN Portal</title>
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
        <h1>Students</h1>
        <div class="meta"><?= $totalCount ?> learners enrolled · showing <?= count($students) ?> result<?= count($students) === 1 ? '' : 's' ?></div>
      </div>
      <a href="add-student.php" class="term-pill">➕ Add Student</a>
    </div>

    <div class="panel">
      <form method="GET" class="form-inline">
        <div class="field" style="flex:2;">
          <label>Search</label>
          <input type="text" name="q" value="<?= h($search) ?>" placeholder="Name, admission no. or email...">
        </div>
        <div class="field">
          <label>Class</label>
          <select name="class_id" style="width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); color:var(--text);">
            <option value="0">All classes</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $classId === (int)$c['id'] ? 'selected' : '' ?>>Grade <?= (int)$c['grade_level'] ?> — <?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-small">Filter</button>
        <?php if ($search || $classId): ?><a href="students.php" class="btn-small" style="background:var(--parchment-2); color:var(--ink); text-decoration:none; display:inline-flex; align-items:center;">Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="panel">
      <table class="data-table">
        <thead><tr><th>Student</th><th>Admission No.</th><th>Class</th><th>GPA</th><th>Attendance</th><th>Fee Status</th></tr></thead>
        <tbody>
          <?php foreach ($students as $s):
              $billed = (float)($s['billed'] ?? 0);
              $paid = (float)($s['paid'] ?? 0);
              if ($billed == 0) { $feeLabel = '—'; $feeClass = ''; }
              elseif ($paid >= $billed) { $feeLabel = 'Paid'; $feeClass = 'badge-paid'; }
              elseif ($paid > 0) { $feeLabel = 'Partial'; $feeClass = 'badge-partial'; }
              else { $feeLabel = '$'.number_format($billed - $paid, 0).' due'; $feeClass = 'badge-due'; }
          ?>
          <tr>
            <td style="display:flex; align-items:center; gap:8px;">
              <?= avatar_html($s['profile_photo'], $s['avatar_initials'], '') ?>
              <style>.data-table .avatar{width:28px;height:28px;font-size:10.5px;}</style>
              <div>
                <div><b><?= h($s['full_name']) ?></b></div>
                <div style="font-size:11.5px; color:var(--slate);"><?= h($s['email']) ?></div>
              </div>
            </td>
            <td style="font-family:var(--font-data);"><?= h($s['admission_no']) ?></td>
            <td><?= h($s['class_name']) ?></td>
            <td><?= $s['gpa'] !== null ? $s['gpa'] : '—' ?></td>
            <td><?= number_format($s['attendance_pct'], 0) ?>%</td>
            <td><?php if ($feeClass): ?><span class="badge <?= $feeClass ?>"><?= $feeLabel ?></span><?php else: echo '—'; endif; ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$students): ?>
          <tr><td colspan="6" style="color:var(--slate)">No students match that search.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>