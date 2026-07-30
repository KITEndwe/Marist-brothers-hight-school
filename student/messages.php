<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('student');

$role = 'student';
$active = 'messages';
$u = current_user();
$pdo = getDB();
$sent = isset($_GET['sent']);

// Mark any unread messages as read the moment the inbox is opened.
$pdo->prepare('UPDATE messages SET read_at = NOW() WHERE recipient_id = :uid AND read_at IS NULL')
    ->execute(['uid' => $u['id']]);

$stmt = $pdo->prepare(
    "SELECT m.*, us.full_name AS sender_name, us.avatar_initials, us.profile_photo
     FROM messages m JOIN users us ON us.id = m.sender_id
     WHERE m.recipient_id = :uid ORDER BY m.sent_at DESC"
);
$stmt->execute(['uid' => $u['id']]);
$inbox = $stmt->fetchAll();

// Teachers this student can reply to: anyone who has ever messaged them, plus
// every teacher of their enrolled subjects.
$stmt = $pdo->prepare(
    "SELECT DISTINCT us.id AS user_id, us.full_name
     FROM subjects sub
     JOIN teachers t ON t.id = sub.teacher_id
     JOIN users us ON us.id = t.user_id
     JOIN students s ON s.class_id = sub.class_id
     WHERE s.user_id = :uid
     ORDER BY us.full_name"
);
$stmt->execute(['uid' => $u['id']]);
$myTeachers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages · MBN Portal</title>
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
        <h1>Messages</h1>
        <div class="meta">Messages from your teachers</div>
      </div>
    </div>

    <?php if ($sent): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Message sent.</div>
    <?php endif; ?>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>Inbox</h3></div>
          <ul class="row-list">
            <?php foreach ($inbox as $m): ?>
            <li style="align-items:flex-start;">
              <?= avatar_html($m['profile_photo'], $m['avatar_initials'], '') ?>
              <style>.row-list .avatar{width:32px;height:32px;font-size:12px; flex-shrink:0;}</style>
              <div style="flex:1;">
                <div class="name"><?= h($m['sender_name']) ?> <span class="sub" style="font-weight:400;"> · <?= date('M j, g:ia', strtotime($m['sent_at'])) ?></span></div>
                <?php if ($m['subject']): ?><div style="font-weight:600; font-size:13px; margin-top:2px;"><?= h($m['subject']) ?></div><?php endif; ?>
                <div class="sub" style="margin-top:3px;"><?= nl2br(h($m['body'])) ?></div>
              </div>
            </li>
            <?php endforeach; ?>
            <?php if (!$inbox): ?>
              <li style="color:var(--slate)">No messages yet.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Message a Teacher</h3></div>
          <form action="message_save.php" method="POST">
            <div class="field">
              <label for="recipient_id">To</label>
              <select id="recipient_id" name="recipient_id" required style="width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); color:var(--text);">
                <option value="">Select a teacher…</option>
                <?php foreach ($myTeachers as $t): ?>
                  <option value="<?= (int)$t['user_id'] ?>"><?= h($t['full_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="subject">Subject</label>
              <input type="text" id="subject" name="subject" placeholder="e.g. Question about homework">
            </div>
            <div class="field">
              <label for="body">Message</label>
              <textarea id="body" name="body" rows="5" required style="width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:8px; font-family:var(--font-body); background:var(--white); color:var(--text);"></textarea>
            </div>
            <button type="submit" class="btn-small" style="width:100%;">Send Message</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>