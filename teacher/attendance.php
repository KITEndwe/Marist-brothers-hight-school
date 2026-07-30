<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('teacher');

$role = 'teacher';
$active = 'attendance';
$u = current_user();
$pdo = getDB();
$today = date('Y-m-d');
$saved = isset($_GET['saved']);

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

// ---- Classes this teacher is the class teacher for, or teaches a subject in
$stmt = $pdo->prepare(
    "SELECT DISTINCT c.id, c.name FROM classes c
     LEFT JOIN subjects sub ON sub.class_id = c.id AND sub.teacher_id = :tid
     WHERE c.class_teacher_id = :uid OR sub.id IS NOT NULL
     ORDER BY c.name"
);
$stmt->execute(['tid' => $teacher['id'], 'uid' => $u['id']]);
$myClasses = $stmt->fetchAll();

$selectedClassId = (int)($_GET['class_id'] ?? ($myClasses[0]['id'] ?? 0));

$students = [];
if ($selectedClassId) {
    $stmt = $pdo->prepare(
        "SELECT s.id AS student_id, us.full_name, us.avatar_initials, us.profile_photo, s.roll_no,
                att.status AS today_status
         FROM students s
         JOIN users us ON us.id = s.user_id
         LEFT JOIN attendance att ON att.student_id = s.id AND att.class_date = :today
         WHERE s.class_id = :cid
         ORDER BY us.full_name"
    );
    $stmt->execute(['today' => $today, 'cid' => $selectedClassId]);
    $students = $stmt->fetchAll();
}
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
        <div class="meta"><?= date('l, j F Y') ?></div>
      </div>
    </div>

    <?php if ($saved): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Attendance saved for today.</div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head">
        <h3>Select Class</h3>
      </div>
      <form method="GET" class="form-inline" style="margin-bottom:4px;">
        <div class="field">
          <select name="class_id" onchange="this.form.submit()" style="width:220px; padding:10px 12px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); color:var(--text);">
            <?php foreach ($myClasses as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $selectedClassId === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Mark Today's Attendance</h3></div>
      <form action="attendance_save.php" method="POST">
        <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
        <table class="data-table">
          <thead><tr><th>Student</th><th>Roll No.</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
              <td style="display:flex; align-items:center; gap:8px;">
                <?= avatar_html($s['profile_photo'], $s['avatar_initials'], '') ?>
                <style>.data-table .avatar{width:26px;height:26px;font-size:10px;}</style>
                <?= h($s['full_name']) ?>
              </td>
              <td><?= h($s['roll_no']) ?></td>
              <td>
                <select name="status[<?= (int)$s['student_id'] ?>]" style="padding:6px 8px; border-radius:6px; border:1.5px solid var(--line); font-size:12.5px; background:var(--white); color:var(--text);">
                  <option value="present" <?= $s['today_status']==='present'?'selected':'' ?>>Present</option>
                  <option value="late" <?= $s['today_status']==='late'?'selected':'' ?>>Late</option>
                  <option value="absent" <?= $s['today_status']==='absent'?'selected':'' ?>>Absent</option>
                </select>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$students): ?>
            <tr><td colspan="3" style="color:var(--slate)">No learners found for this class.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <?php if ($students): ?>
        <button type="submit" class="btn-small" style="margin-top:14px;">Save Attendance</button>
        <?php endif; ?>
      </form>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
