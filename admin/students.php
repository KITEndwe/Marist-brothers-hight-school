<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$role = 'admin';
$active = 'students';
$u = current_user();
$pdo = getDB();

// ---- Handle delete ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student_id'])) {
    $delId = (int)$_POST['delete_student_id'];
    // Deleting the user cascades to the students row (FK ON DELETE CASCADE)
    $stmt = $pdo->prepare('SELECT user_id FROM students WHERE id = ?');
    $stmt->execute([$delId]);
    $userId = $stmt->fetchColumn();
    if ($userId) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    }
    header('Location: students.php');
    exit;
}

// ---- Filters ------------------------------------------------------------
$search   = trim($_GET['q'] ?? '');
$classId  = (int)($_GET['class_id'] ?? 0);

$classes = $pdo->query('SELECT id, name, grade_level FROM classes ORDER BY grade_level, name')->fetchAll();

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(us.full_name LIKE ? OR s.admission_no LIKE ? OR us.email LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}
if ($classId > 0) {
    $where[] = 's.class_id = ?';
    $params[] = $classId;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT s.id, us.full_name, us.email, us.avatar_initials, s.admission_no, s.roll_no,
               s.attendance_pct, c.name AS class_name, c.grade_level,
               (SELECT ROUND(AVG(g.score)/25,1) FROM grades g WHERE g.student_id = s.id) AS gpa,
               f.amount_billed, f.amount_paid
        FROM students s
        JOIN users us ON us.id = s.user_id
        JOIN classes c ON c.id = s.class_id
        LEFT JOIN fees f ON f.student_id = s.id
        $whereSql
        ORDER BY us.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$totalStudents = count($students);
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
        <div class="meta"><?= $totalStudents ?> student<?= $totalStudents === 1 ? '' : 's' ?> · Academic Year 2025/26</div>
      </div>
      <a href="add-student.php" style="padding:8px 16px; background:var(--gold,#c9a227); color:#fff; border-radius:6px; text-decoration:none; font-weight:600;">➕ Add Student</a>
    </div>

    <div class="panel">
      <form method="get" action="students.php" style="display:flex; gap:12px; flex-wrap:wrap;">
        <input type="text" name="q" placeholder="Search by name, admission no. or email…"
               value="<?= h($search) ?>"
               style="flex:1; min-width:220px; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">

        <select name="class_id" style="padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          <option value="0">All classes</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $classId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= h($c['name']) ?> (Grade <?= (int)$c['grade_level'] ?>)
            </option>
          <?php endforeach; ?>
        </select>

        <button type="submit" style="padding:8px 16px; border:1px solid var(--border,#ccc); border-radius:6px; background:#fff; cursor:pointer;">Filter</button>
        <?php if ($search !== '' || $classId > 0): ?>
          <a href="students.php" style="padding:8px 16px; align-self:center; color:var(--slate);">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="panel">
      <table class="data-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Admission No.</th>
            <th>Class</th>
            <th>Roll No.</th>
            <th>Attendance</th>
            <th>GPA</th>
            <th>Fee Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
          <tr><td colspan="8" style="text-align:center; color:var(--slate); padding:24px;">No students found.</td></tr>
          <?php endif; ?>

          <?php foreach ($students as $s):
              $bill = (float)($s['amount_billed'] ?? 0);
              $pd   = (float)($s['amount_paid'] ?? 0);
              if ($bill == 0) { $statusLabel = '—'; $statusClass = ''; }
              elseif ($pd >= $bill) { $statusLabel = 'Paid'; $statusClass = 'badge-paid'; }
              elseif ($pd > 0) { $statusLabel = 'Partial'; $statusClass = 'badge-partial'; }
              else { $statusLabel = '$' . number_format($bill - $pd, 0); $statusClass = 'badge-due'; }
          ?>
          <tr>
            <td style="display:flex; align-items:center; gap:8px;">
              <div class="avatar" style="width:26px;height:26px;font-size:10px;"><?= h($s['avatar_initials']) ?></div>
              <div>
                <div><?= h($s['full_name']) ?></div>
                <div style="font-size:12px; color:var(--slate);"><?= h($s['email']) ?></div>
              </div>
            </td>
            <td><?= h($s['admission_no']) ?></td>
            <td><?= h($s['class_name']) ?> <span style="color:var(--slate); font-size:12px;">(Gr. <?= (int)$s['grade_level'] ?>)</span></td>
            <td><?= h($s['roll_no']) ?></td>
            <td><?= number_format((float)$s['attendance_pct'], 1) ?>%</td>
            <td><?= $s['gpa'] !== null ? $s['gpa'] : '—' ?></td>
            <td><?php if ($statusClass): ?><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span><?php else: echo '—'; endif; ?></td>
            <td>
              <form method="post" action="students.php" onsubmit="return confirm('Remove <?= h(addslashes($s['full_name'])) ?> from the system? This cannot be undone.');" style="margin:0;">
                <input type="hidden" name="delete_student_id" value="<?= (int)$s['id'] ?>">
                <button type="submit" style="background:none; border:none; color:var(--maroon,#b3261e); cursor:pointer; font-size:13px;">Remove</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>