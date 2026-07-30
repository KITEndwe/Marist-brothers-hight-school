<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('teacher');

$role = 'teacher';
$active = 'grades';
$u = current_user();
$pdo = getDB();
$term = 'Term 2 2025/26';
$saved = isset($_GET['saved']);
$error = $_GET['error'] ?? null;

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$teacher = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT sub.id, sub.name, c.name AS class_name, c.id AS class_id
     FROM subjects sub JOIN classes c ON c.id = sub.class_id
     WHERE sub.teacher_id = :tid ORDER BY c.name, sub.name"
);
$stmt->execute(['tid' => $teacher['id']]);
$mySubjects = $stmt->fetchAll();

$selectedSubjectId = (int)($_GET['subject_id'] ?? ($mySubjects[0]['id'] ?? 0));

$gradeRows = [];
if ($selectedSubjectId) {
    $stmt = $pdo->prepare(
        "SELECT s.id AS student_id, us.full_name, us.avatar_initials, us.profile_photo,
                g.id AS grade_id, g.score, g.letter_grade
         FROM students s
         JOIN users us ON us.id = s.user_id
         JOIN subjects sub ON sub.id = :subid
         LEFT JOIN grades g ON g.student_id = s.id AND g.subject_id = :subid2 AND g.term = :term
         WHERE s.class_id = sub.class_id
         ORDER BY us.full_name"
    );
    $stmt->execute(['subid' => $selectedSubjectId, 'subid2' => $selectedSubjectId, 'term' => $term]);
    $gradeRows = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Grades · MBN Portal</title>
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
        <h1>Grades</h1>
        <div class="meta"><?= h($term) ?> · enter or update a score and it's saved instantly</div>
      </div>
    </div>

    <?php if ($saved): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Grade saved.</div>
    <?php elseif ($error): ?>
      <div class="form-error" style="margin-bottom:18px;"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="GET" class="form-inline">
        <div class="field">
          <label>Subject</label>
          <select name="subject_id" onchange="this.form.submit()" style="width:260px; padding:10px 12px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); color:var(--text);">
            <?php foreach ($mySubjects as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= $selectedSubjectId === (int)$s['id'] ? 'selected' : '' ?>><?= h($s['name']) ?> — <?= h($s['class_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Gradebook — <?= h($term) ?></h3></div>
      <table class="data-table">
        <thead><tr><th>Student</th><th>Current Score</th><th>Grade</th><th>Update Score</th></tr></thead>
        <tbody>
          <?php foreach ($gradeRows as $g): ?>
          <tr>
            <td style="display:flex; align-items:center; gap:8px;">
              <?= avatar_html($g['profile_photo'], $g['avatar_initials'], '') ?>
              <style>.data-table .avatar{width:26px;height:26px;font-size:10px;}</style>
              <?= h($g['full_name']) ?>
            </td>
            <td><?= $g['score'] !== null ? number_format($g['score'],0).'%' : '—' ?></td>
            <td><?= $g['letter_grade'] ? '<span class="badge '.grade_color($g['letter_grade']).'">'.h($g['letter_grade']).'</span>' : '—' ?></td>
            <td>
              <form action="grade_save.php" method="POST" style="display:flex; gap:8px;">
                <input type="hidden" name="student_id" value="<?= (int)$g['student_id'] ?>">
                <input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
                <input type="number" name="score" min="0" max="100" placeholder="0–100" value="<?= $g['score'] !== null ? (int)$g['score'] : '' ?>" style="width:90px; padding:8px 10px; border-radius:6px; border:1.5px solid var(--line); background:var(--white); color:var(--text);" required>
                <button type="submit" class="btn-small">Save</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$gradeRows): ?>
          <tr><td colspan="4" style="color:var(--slate)">No learners found for this subject.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>