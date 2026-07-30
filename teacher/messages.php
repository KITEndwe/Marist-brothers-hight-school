<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('teacher');

$role = 'teacher';
$active = 'messages';
$u = current_user();
$pdo = getDB();
$sent = isset($_GET['sent']);

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

// Mark any unread messages as read the moment the inbox is opened.
$pdo->prepare('UPDATE messages SET read_at = NOW() WHERE recipient_id = :uid AND read_at IS NULL')
    ->execute(['uid' => $u['id']]);

// ---- Inbox: messages received --------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT m.*, us.full_name AS sender_name, us.avatar_initials, us.profile_photo
     FROM messages m JOIN users us ON us.id = m.sender_id
     WHERE m.recipient_id = :uid ORDER BY m.sent_at DESC"
);
$stmt->execute(['uid' => $u['id']]);
$inbox = $stmt->fetchAll();

// ---- Students this teacher can message (their own classes only) ----------------
$stmt = $pdo->prepare(
    "SELECT DISTINCT us.id AS user_id, us.full_name, c.name AS class_name
     FROM students s
     JOIN users us ON us.id = s.user_id
     JOIN classes c ON c.id = s.class_id
     JOIN subjects sub ON sub.class_id = s.class_id AND sub.teacher_id = :tid
     ORDER BY us.full_name"
);
$stmt->execute(['tid' => $teacher['id']]);
$myStudents = $stmt->fetchAll();
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
        <div class="meta">Direct messages with your students</div>
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
          <div class="panel-head"><h3>Send a Message</h3></div>
          <form action="message_save.php" method="POST">
            <div class="field">
              <label for="recipient_id">To</label>
              <select id="recipient_id" name="recipient_id" required style="width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); color:var(--text);">
                <option value="">Select a student…</option>
                <?php foreach ($myStudents as $s): ?>
                  <option value="<?= (int)$s['user_id'] ?>"><?= h($s['full_name']) ?> — <?= h($s['class_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="subject">Subject</label>
              <input type="text" id="subject" name="subject" placeholder="e.g. About your assignment">
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