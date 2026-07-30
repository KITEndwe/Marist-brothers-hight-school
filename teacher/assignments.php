<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('teacher');

$role = 'teacher';
$active = 'assignments';
$u = current_user();
$pdo = getDB();
$created = isset($_GET['created']);
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
    "SELECT a.id, a.title, a.due_date, sub.name AS subject_name, c.name AS class_name,
            (SELECT COUNT(*) FROM students st WHERE st.class_id = c.id) AS total_students,
            (SELECT COUNT(*) FROM assignment_submissions asub WHERE asub.assignment_id = a.id AND asub.status = 'submitted') AS pending_review,
            (SELECT COUNT(*) FROM assignment_submissions asub WHERE asub.assignment_id = a.id AND asub.status = 'graded') AS graded_count
     FROM assignments a
     JOIN subjects sub ON sub.id = a.subject_id
     JOIN classes c ON c.id = sub.class_id
     WHERE a.teacher_id = :tid
     ORDER BY a.due_date DESC"
);
$stmt->execute(['tid' => $teacher['id']]);
$myAssignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assignments · MBN Portal</title>
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
        <h1>Assignments</h1>
        <div class="meta"><?= count($myAssignments) ?> assignment<?= count($myAssignments) === 1 ? '' : 's' ?> set across your subjects</div>
      </div>
    </div>

    <?php if ($created): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Assignment created.</div>
    <?php elseif ($error): ?>
      <div class="form-error" style="margin-bottom:18px;"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>My Assignments</h3></div>
          <table class="data-table">
            <thead><tr><th>Title</th><th>Subject / Class</th><th>Due</th><th>Submissions</th></tr></thead>
            <tbody>
              <?php foreach ($myAssignments as $a): ?>
              <tr>
                <td><b><?= h($a['title']) ?></b></td>
                <td><?= h($a['subject_name']) ?> · <?= h($a['class_name']) ?></td>
                <td><?= date('M j, Y', strtotime($a['due_date'])) ?></td>
                <td>
                  <span class="badge badge-partial"><?= (int)$a['pending_review'] ?> to review</span>
                  <span class="badge badge-paid"><?= (int)$a['graded_count'] ?> graded</span>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$myAssignments): ?>
              <tr><td colspan="4" style="color:var(--slate)">You haven't set any assignments yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Set New Assignment</h3></div>
          <form action="assignment_save.php" method="POST">
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
              <input type="text" id="title" name="title" placeholder="e.g. Homework 4 — Trigonometry" required>
            </div>
            <div class="field">
              <label for="due_date">Due Date</label>
              <input type="date" id="due_date" name="due_date" required>
            </div>
            <button type="submit" class="btn-small" style="width:100%;">Create Assignment</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>