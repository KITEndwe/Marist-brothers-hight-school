<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('admin');

$role = 'admin';
$active = 'settings';
$u = current_user();
$pdo = getDB();
$saved = isset($_GET['saved']);
$error = $_GET['error'] ?? null;

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $u['id']]);
$account = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings · MBN Portal</title>
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
        <h1>Settings</h1>
        <div class="meta">Your admin account details</div>
      </div>
    </div>

    <?php if ($saved): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Changes saved.</div>
    <?php elseif ($error): ?>
      <div class="form-error" style="margin-bottom:18px;"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>Profile Photo</h3></div>
          <div style="display:flex; align-items:center; gap:18px;">
            <?= avatar_html($account['profile_photo'], $account['avatar_initials'], 'lg') ?>
            <style>.avatar.lg{width:72px;height:72px;font-size:22px;}</style>
            <form action="photo_upload.php" method="POST" enctype="multipart/form-data" style="flex:1;">
              <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" required style="margin-bottom:10px;">
              <button type="submit" class="btn-small">Upload New Photo</button>
            </form>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head"><h3>Account Details</h3></div>
          <form action="settings_save.php" method="POST">
            <input type="hidden" name="action" value="profile">
            <div class="field">
              <label for="full_name">Full Name</label>
              <input type="text" id="full_name" name="full_name" value="<?= h($account['full_name']) ?>" required>
            </div>
            <div class="field">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" value="<?= h($account['email']) ?>" required>
            </div>
            <button type="submit" class="btn-small">Save Changes</button>
          </form>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Change Password</h3></div>
          <form action="settings_save.php" method="POST">
            <input type="hidden" name="action" value="password">
            <div class="field">
              <label for="current_password">Current Password</label>
              <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="field">
              <label for="new_password">New Password</label>
              <input type="password" id="new_password" name="new_password" minlength="6" required>
            </div>
            <div class="field">
              <label for="confirm_password">Confirm New Password</label>
              <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
            </div>
            <button type="submit" class="btn-small" style="width:100%;">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>