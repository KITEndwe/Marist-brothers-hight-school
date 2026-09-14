<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('student');

$role = 'student';
$active = 'profile';
$u = current_user();
$pdo = getDB();

$stmt = $pdo->prepare(
    'SELECT s.*, c.name AS class_name, c.grade_level, us.email, us.profile_photo, us.avatar_initials
     FROM students s
     JOIN classes c ON c.id = s.class_id
     JOIN users us ON us.id = s.user_id
     WHERE s.user_id = :uid'
);
$stmt->execute(['uid' => $u['id']]);
$student = $stmt->fetch();

$uploaded = isset($_GET['uploaded']);
$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile · MBN Portal</title>
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
        <h1>My Profile</h1>
        <div class="meta">Manage your photo and view your student record</div>
      </div>
    </div>

    <?php if ($uploaded): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Photo updated successfully.</div>
    <?php elseif ($error): ?>
      <div class="form-error" style="margin-bottom:18px;"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="panel-grid">
      <div>
        <div class="panel" style="text-align:center;">
          <?= avatar_html($student['profile_photo'], $student['avatar_initials'], 'lg') ?>
          <style>.avatar.lg{width:110px;height:110px;font-size:36px;margin:0 auto 16px;}</style>
          <h2 style="font-family:var(--font-display); margin:10px 0 4px;"><?= h($u['full_name']) ?></h2>
          <p style="color:var(--slate); font-size:13.5px; margin-bottom:20px;">Grade <?= h($student['class_name']) ?> · Student</p>

          <form action="photo_upload.php" method="POST" enctype="multipart/form-data" style="text-align:left;">
            <div class="field">
              <label for="photo">Upload new profile photo</label>
              <input type="file" id="photo" name="photo" accept="image/png, image/jpeg, image/webp" required>
            </div>
            <button type="submit" class="btn-small" style="width:100%;">Upload Photo</button>
            <p style="font-size:11.5px; color:var(--slate); margin-top:8px;">JPG, PNG or WEBP · max 3MB</p>
          </form>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Student Record</h3></div>
          <table class="data-table">
            <tbody>
              <tr><td style="color:var(--slate); width:40%;">Student Number</td><td><b style="font-family:var(--font-data);"><?= h($student['admission_no']) ?></b></td></tr>
              <tr><td style="color:var(--slate);">Full Name</td><td><?= h($u['full_name']) ?></td></tr>
              <tr><td style="color:var(--slate);">Email</td><td><?= h($student['email']) ?></td></tr>
              <tr><td style="color:var(--slate);">Class</td><td><?= h($student['class_name']) ?> (Grade <?= (int)$student['grade_level'] ?>)</td></tr>
              <tr><td style="color:var(--slate);">Roll Number</td><td><?= h($student['roll_no']) ?></td></tr>
              <tr><td style="color:var(--slate);">Attendance</td><td><?= h($student['attendance_pct']) ?>%</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
