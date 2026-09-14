<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$role = 'admin';
$active = 'add-student';
$u = current_user();
$pdo = getDB();

$errors = [];
$success = false;

// Pull classes for the dropdown
$classes = $pdo->query('SELECT id, name, grade_level FROM classes ORDER BY grade_level, name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $admission_no = trim($_POST['admission_no'] ?? '');
    $class_id     = (int)($_POST['class_id'] ?? 0);
    $roll_no      = trim($_POST['roll_no'] ?? '');

    // ---- Validation ----------------------------------------------------
    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($password === '' || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($admission_no === '') $errors[] = 'Admission number is required.';
    if ($class_id <= 0) $errors[] = 'Please select a class.';
    if ($roll_no === '') $errors[] = 'Roll number is required.';

    if (empty($errors)) {
        // Check for existing email / admission number before inserting
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'A user with that email already exists.';

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE admission_no = ?');
        $stmt->execute([$admission_no]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'That admission number is already in use.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Build avatar initials from the name, e.g. "Tendai Chikafu" -> "TC"
            $parts = preg_split('/\s+/', $full_name);
            $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));

            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, avatar_initials)
                 VALUES (?, ?, ?, \'student\', ?)'
            );
            $stmt->execute([$full_name, $email, $password_hash, $initials]);
            $user_id = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO students (user_id, admission_no, class_id, roll_no, attendance_pct)
                 VALUES (?, ?, ?, ?, 0.00)'
            );
            $stmt->execute([$user_id, $admission_no, $class_id, $roll_no]);

            $pdo->commit();
            $success = true;

            // Clear form fields after a successful insert
            $full_name = $email = $admission_no = $roll_no = '';
            $class_id = 0;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: could not add student. Please try again.';
        }
    }
}
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
        <div class="meta">Create a new student account and enrol them in a class</div>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="panel" style="border-left:4px solid var(--sage, #4caf7d);">
        <p style="color:var(--sage, #4caf7d); font-weight:600;">✅ Student added successfully.</p>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="panel" style="border-left:4px solid var(--maroon, #b3261e);">
        <?php foreach ($errors as $err): ?>
          <p style="color:var(--maroon, #b3261e); margin:4px 0;">⚠️ <?= h($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="add-student.php" autocomplete="off">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div>
            <label for="full_name" style="display:block; font-size:13px; margin-bottom:4px;">Full Name</label>
            <input type="text" id="full_name" name="full_name" required
                   value="<?= h($full_name ?? '') ?>"
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>

          <div>
            <label for="email" style="display:block; font-size:13px; margin-bottom:4px;">Email</label>
            <input type="email" id="email" name="email" required
                   value="<?= h($email ?? '') ?>"
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>

          <div>
            <label for="password" style="display:block; font-size:13px; margin-bottom:4px;">Password</label>
            <input type="password" id="password" name="password" required minlength="6"
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>

          <div>
            <label for="admission_no" style="display:block; font-size:13px; margin-bottom:4px;">Admission No.</label>
            <input type="text" id="admission_no" name="admission_no" required
                   placeholder="e.g. MBN-2026-045"
                   value="<?= h($admission_no ?? '') ?>"
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>

          <div>
            <label for="class_id" style="display:block; font-size:13px; margin-bottom:4px;">Class</label>
            <select id="class_id" name="class_id" required
                    style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
              <option value="">Select a class…</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (!empty($class_id) && (int)$class_id === (int)$c['id']) ? 'selected' : '' ?>>
                  <?= h($c['name']) ?> (Grade <?= (int)$c['grade_level'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="roll_no" style="display:block; font-size:13px; margin-bottom:4px;">Roll No.</label>
            <input type="text" id="roll_no" name="roll_no" required
                   placeholder="e.g. 2026-045"
                   value="<?= h($roll_no ?? '') ?>"
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>
        </div>

        <div style="margin-top:20px;">
          <button type="submit" style="padding:10px 20px; background:var(--gold,#c9a227); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
            Add Student
          </button>
        </div>
      </form>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>