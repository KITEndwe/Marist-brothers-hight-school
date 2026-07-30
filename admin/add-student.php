<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$role = 'admin';
$active = 'add-student';
$u = current_user();
$pdo = getDB();
$error = $_GET['error'] ?? null;
$created = isset($_GET['created']);

$stmt = $pdo->query('SELECT id, name, grade_level FROM classes ORDER BY grade_level, name');
$classes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Student · MBN Portal</title>
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
        <h1>Add Student</h1>
        <div class="meta">Creates a login account and enrolment record in one step.</div>
      </div>
    </div>

    <?php if ($created): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">
        ✔ Student added. Admission No: <b><?= h($_GET['adm'] ?? '') ?></b> · Temporary password: <b><?= h($_GET['pwd'] ?? '') ?></b> — share this with the student/parent, it will not be shown again.
      </div>
    <?php elseif ($error): ?>
      <div class="form-error" style="margin-bottom:18px;"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="panel" style="max-width:640px;">
      <div class="panel-head"><h3>Student Details</h3></div>
      <form action="add_student_save.php" method="POST">
        <div class="form-grid-2">
          <div class="field">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" placeholder="e.g. Tadiwa Nyathi" required>
          </div>
          <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="student@student.mbn.ac.zw" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="field">
            <label for="class_id">Class</label>
            <select id="class_id" name="class_id" required style="width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); color:var(--text);">
              <option value="">Select a class…</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= (int)$c['id'] ?>">Grade <?= (int)$c['grade_level'] ?> — <?= h($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="roll_no">Roll Number</label>
            <input type="text" id="roll_no" name="roll_no" placeholder="e.g. 2026-045" required>
          </div>
        </div>

        <div class="field">
          <label for="admission_no">Admission Number <span style="text-transform:none; font-weight:400;">(leave blank to auto-generate)</span></label>
          <input type="text" id="admission_no" name="admission_no" placeholder="e.g. MBN-2026-045">
        </div>

        <div class="field">
          <label for="password">Temporary Password <span style="text-transform:none; font-weight:400;">(leave blank to auto-generate)</span></label>
          <input type="text" id="password" name="password" placeholder="Leave blank to auto-generate">
        </div>

        <button type="submit" class="btn-small" style="width:100%;">➕ Add Student</button>
      </form>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>