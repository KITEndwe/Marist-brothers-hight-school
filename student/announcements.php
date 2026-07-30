<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$role = 'student';
$active = 'announcements';
$u = current_user();
$pdo = getDB();

$stmt = $pdo->query(
    "SELECT a.*, us.full_name AS author FROM announcements a
     LEFT JOIN users us ON us.id = a.posted_by
     WHERE a.audience IN ('all','students')
     ORDER BY a.posted_date DESC, a.id DESC"
);
$announcements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcements · MBN Portal</title>
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
        <h1>Announcements</h1>
        <div class="meta">Notices from the admin office and your teachers</div>
      </div>
    </div>

    <div class="panel">
      <ul class="timeline">
        <?php foreach ($announcements as $a): ?>
        <li>
          <time><?= date('M j', strtotime($a['posted_date'])) ?></time>
          <div>
            <b><?= h($a['title']) ?></b>
            <span class="cat"><?= h($a['category']) ?><?= $a['author'] ? ' · '.h($a['author']) : '' ?></span>
            <?php if ($a['body']): ?><p style="font-size:12.5px; color:var(--slate); margin:4px 0 0;"><?= h($a['body']) ?></p><?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (!$announcements): ?>
          <li style="color:var(--slate)">No announcements yet.</li>
        <?php endif; ?>
      </ul>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
