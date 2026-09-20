<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$role = 'admin';
$active = 'settings';
$u = current_user();
$pdo = getDB();

$errors = [];
$success = '';

// Re-fetch the fresh row for this admin (in case current_user() is a cached/session copy)
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$u['id']]);
$account = $stmt->fetch();

// ---- Handle: update profile (name / email) ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $account['id']]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'That email is already in use by another account.';
    }

    if (empty($errors)) {
        $parts = preg_split('/\s+/', $full_name);
        $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));

        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, avatar_initials = ? WHERE id = ?');
        $stmt->execute([$full_name, $email, $initials, $account['id']]);

        $success = 'Profile updated.';
        $account['full_name'] = $full_name;
        $account['email'] = $email;
        $account['avatar_initials'] = $initials;
    }
}

// ---- Handle: change password --------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $account['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    }
    if (strlen($new) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    if ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $newHash = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$newHash, $account['id']]);
        $success = 'Password changed successfully.';
    }
}
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
        <div class="meta">Manage your admin account</div>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="panel" style="border-left:4px solid var(--sage, #4caf7d);">
        <p style="color:var(--sage, #4caf7d); font-weight:600;">✅ <?= h($success) ?></p>
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
      <div class="panel-head">
        <h3>Profile</h3>
      </div>
      <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px;">
        <div class="avatar" style="width:52px; height:52px; font-size:18px;"><?= h($account['avatar_initials'] ?? '') ?></div>
        <div>
          <div style="font-weight:600;"><?= h($account['full_name']) ?></div>
          <div style="font-size:13px; color:var(--slate);">Admin · Account created <?= date('d M Y', strtotime($account['created_at'])) ?></div>
        </div>
      </div>

      <form method="post" action="settings.php">
        <input type="hidden" name="action" value="update_profile">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div>
            <label for="full_name" style="display:block; font-size:13px; margin-bottom:4px;">Full Name</label>
            <input type="text" id="full_name" name="full_name" required
                   value="<?= h($account['full_name']) ?>"
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>
          <div>
            <label for="email" style="display:block; font-size:13px; margin-bottom:4px;">Email</label>
            <input type="email" id="email" name="email" required
                   value="<?= h($account['email']) ?>"
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>
        </div>
        <div style="margin-top:16px;">
          <button type="submit" style="padding:9px 18px; background:var(--gold,#c9a227); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
            Save Profile
          </button>
        </div>
      </form>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Change Password</h3></div>
      <form method="post" action="settings.php">
        <input type="hidden" name="action" value="change_password">
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
          <div>
            <label for="current_password" style="display:block; font-size:13px; margin-bottom:4px;">Current Password</label>
            <input type="password" id="current_password" name="current_password" required
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>
          <div>
            <label for="new_password" style="display:block; font-size:13px; margin-bottom:4px;">New Password</label>
            <input type="password" id="new_password" name="new_password" required minlength="6"
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>
          <div>
            <label for="confirm_password" style="display:block; font-size:13px; margin-bottom:4px;">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                   style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          </div>
        </div>
        <div style="margin-top:16px;">
          <button type="submit" style="padding:9px 18px; background:var(--ink,#1e2a44); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
            Update Password
          </button>
        </div>
      </form>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Appearance</h3></div>
      <p style="color:var(--slate); font-size:14px; margin-bottom:12px;">
        Toggle light / dark mode using the 🌓 button in the corner — your preference is saved in this browser.
      </p>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>