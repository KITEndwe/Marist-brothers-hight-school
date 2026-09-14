<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('teacher');

$role = 'teacher';
$active = 'announcements';
$u = current_user();
$pdo = getDB();
$posted = isset($_GET['posted']);

$stmt = $pdo->query(
    "SELECT a.*, us.full_name AS author FROM announcements a
     LEFT JOIN users us ON us.id = a.posted_by
     WHERE a.audience IN ('all','students','teachers')
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
        <div class="meta">Post notices to your students, or view what's been shared school-wide</div>
      </div>
    </div>

    <?php if ($posted): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Announcement posted.</div>
    <?php endif; ?>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>All Announcements</h3></div>
          <ul class="timeline">
            <?php foreach ($announcements as $a): ?>
            <li>
              <time><?= date('M j', strtotime($a['posted_date'])) ?></time>
              <div>
                <b><?= h($a['title']) ?></b>
                <span class="cat"><?= h($a['category']) ?> · <?= h(ucfirst($a['audience'])) ?><?= $a['author'] ? ' · by '.h($a['author']) : '' ?></span>
                <?php if ($a['body']): ?><p style="font-size:12.5px; color:var(--slate); margin:4px 0 0;"><?= h($a['body']) ?></p><?php endif; ?>
              </div>
            </li>
            <?php endforeach; ?>
            <?php if (!$announcements): ?>
              <li style="color:var(--slate)">No announcements yet.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Post New Announcement</h3></div>
          <form action="announcement_save.php" method="POST">
            <div class="field">
              <label for="title">Title</label>
              <input type="text" id="title" name="title" required>
            </div>
            <div class="field">
              <label for="category">Category</label>
              <input type="text" id="category" name="category" placeholder="Academic, Homework, Reminder..." required>
            </div>
            <div class="field">
              <label for="audience">Audience</label>
              <select id="audience" name="audience" style="width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); color:var(--text);">
                <option value="students">My students</option>
                <option value="all">Everyone</option>
              </select>
            </div>
            <div class="field">
              <label for="body">Message</label>
              <textarea id="body" name="body" rows="4" style="width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:8px; font-family:var(--font-body); background:var(--white); color:var(--text);"></textarea>
            </div>
            <button type="submit" class="btn-small" style="width:100%;">Post Announcement</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
