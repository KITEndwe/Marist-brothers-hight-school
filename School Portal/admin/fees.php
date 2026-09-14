<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$role = 'admin';
$active = 'fees';
$u = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= ucfirst(str_replace('-', ' ', 'fees')) ?> · MBN Portal</title>
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
        <h1><?= ucfirst(str_replace('-', ' ', 'fees')) ?></h1>
        <div class="meta">This section is a placeholder — wire it up to the matching table in mbn_portal.sql.</div>
      </div>
    </div>
    <div class="panel">
      <p style="color:var(--slate); font-size:14px;">
        The <b>admin/fees.php</b> page is scaffolded and protected by the same login/session
        guard as the dashboard, but its content hasn't been built yet. Extend it the same way
        <code>dashboard.php</code> queries the database.
      </p>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
