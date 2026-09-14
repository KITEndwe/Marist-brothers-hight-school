<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('teacher');

$role = 'teacher';
$active = 'past-papers';
$u = current_user();
$pdo = getDB();
$uploaded = isset($_GET['uploaded']);
$error = $_GET['error'] ?? null;

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT sub.id, sub.name, c.name AS class_name
     FROM subjects sub JOIN classes c ON c.id = sub.class_id
     WHERE sub.teacher_id = :tid ORDER BY c.name, sub.name"
);
$stmt->execute(['tid' => $teacher['id']]);
$mySubjects = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT pp.*, sub.name AS subject_name, c.name AS class_name
     FROM past_papers pp
     JOIN subjects sub ON sub.id = pp.subject_id
     JOIN classes c ON c.id = sub.class_id
     WHERE pp.teacher_id = :tid ORDER BY pp.uploaded_at DESC"
);
$stmt->execute(['tid' => $teacher['id']]);
$myPapers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Past Papers · MBN Portal</title>
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
        <h1>Past Papers</h1>
        <div class="meta">Upload past exam papers for your subjects — students see these instantly</div>
      </div>
    </div>

    <?php if ($uploaded): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Past paper uploaded.</div>
    <?php elseif ($error): ?>
      <div class="form-error" style="margin-bottom:18px;"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>My Uploads</h3></div>
          <table class="data-table">
            <thead><tr><th>Title</th><th>Subject / Class</th><th>Term</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($myPapers as $p): ?>
              <tr>
                <td><b><?= h($p['title']) ?></b></td>
                <td><?= h($p['subject_name']) ?> · <?= h($p['class_name']) ?></td>
                <td><?= h($p['term'] ?? '—') ?></td>
                <td><a href="../uploads/<?= h($p['file_path']) ?>" target="_blank" style="color:var(--maroon); font-weight:600; font-size:13px;">View →</a></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$myPapers): ?>
              <tr><td colspan="4" style="color:var(--slate)">You haven't uploaded any past papers yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Upload New Past Paper</h3></div>
          <form action="past_paper_save.php" method="POST" enctype="multipart/form-data">
            <div class="field">
              <label for="subject_id">Subject</label>
              <select id="subject_id" name="subject_id" required style="width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); color:var(--text);">
                <?php foreach ($mySubjects as $s): ?>
                  <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?> — <?= h($s['class_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="title">Title</label>
              <input type="text" id="title" name="title" placeholder="e.g. Term 2 Mid-Term Test" required>
            </div>
            <div class="field">
              <label for="term">Term</label>
              <input type="text" id="term" name="term" placeholder="e.g. Term 2 2025/26">
            </div>
            <div class="field">
              <label for="paper">File (PDF, DOC, DOCX)</label>
              <input type="file" id="paper" name="paper" accept=".pdf,.doc,.docx" required>
            </div>
            <button type="submit" class="btn-small" style="width:100%;">Upload</button>
            <p style="font-size:11.5px; color:var(--slate); margin-top:8px;">Max 10MB</p>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
