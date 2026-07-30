<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_role('admin');

$role = 'admin';
$active = 'fees';
$u = current_user();
$pdo = getDB();
$saved = isset($_GET['saved']);
$error = $_GET['error'] ?? null;

$feeTotals = $pdo->query('SELECT SUM(amount_billed) AS billed, SUM(amount_paid) AS paid FROM fees')->fetch();
$billed = (float)($feeTotals['billed'] ?? 0);
$paid   = (float)($feeTotals['paid'] ?? 0);
$collectionRate = $billed > 0 ? round(($paid / $billed) * 100) : 0;
$defaulters = (int)$pdo->query('SELECT COUNT(*) FROM fees WHERE amount_paid < amount_billed')->fetchColumn();

$stmt = $pdo->query(
    "SELECT f.id AS fee_id, f.term, f.amount_billed, f.amount_paid, s.id AS student_id,
            us.full_name, us.avatar_initials, us.profile_photo, c.name AS class_name
     FROM fees f
     JOIN students s ON s.id = f.student_id
     JOIN users us ON us.id = s.user_id
     JOIN classes c ON c.id = s.class_id
     ORDER BY (f.amount_billed - f.amount_paid) DESC, us.full_name"
);
$feeRows = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT s.id, us.full_name, s.admission_no, c.name AS class_name
     FROM students s JOIN users us ON us.id = s.user_id JOIN classes c ON c.id = s.class_id
     ORDER BY us.full_name"
);
$allStudents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fees · MBN Portal</title>
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
        <h1>Fees</h1>
        <div class="meta">Fee ledger across all students · <?= $collectionRate ?>% collected overall</div>
      </div>
    </div>

    <?php if ($saved): ?>
      <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Payment recorded.</div>
    <?php elseif ($error): ?>
      <div class="form-error" style="margin-bottom:18px;"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="stat-grid">
      <div class="stat-card accent-sage">
        <div class="tag">💰</div>
        <div class="label">Total Collected</div>
        <div class="value">$<?= number_format($paid, 0) ?></div>
        <div class="foot" style="color:var(--slate)">of $<?= number_format($billed, 0) ?> billed</div>
      </div>
      <div class="stat-card accent-gold">
        <div class="tag">📊</div>
        <div class="label">Collection Rate</div>
        <div class="value"><?= $collectionRate ?>%</div>
        <div class="foot" style="color:var(--slate)">Across all students</div>
      </div>
      <div class="stat-card accent-maroon">
        <div class="tag">⚠️</div>
        <div class="label">Defaulters</div>
        <div class="value"><?= $defaulters ?></div>
        <div class="foot" style="color:var(--maroon)">Balance outstanding</div>
      </div>
    </div>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>All Fee Records</h3></div>
          <table class="data-table">
            <thead><tr><th>Student</th><th>Term</th><th>Billed</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($feeRows as $f):
                  $bal = $f['amount_billed'] - $f['amount_paid'];
                  if ($bal <= 0) { $st = 'badge-paid'; $lbl = 'Paid'; }
                  elseif ($f['amount_paid'] > 0) { $st = 'badge-partial'; $lbl = 'Partial'; }
                  else { $st = 'badge-due'; $lbl = 'Unpaid'; }
              ?>
              <tr>
                <td style="display:flex; align-items:center; gap:8px;">
                  <?= avatar_html($f['profile_photo'], $f['avatar_initials'], '') ?>
                  <style>.data-table .avatar{width:26px;height:26px;font-size:10px;}</style>
                  <div><b><?= h($f['full_name']) ?></b><div style="font-size:11px; color:var(--slate);"><?= h($f['class_name']) ?></div></div>
                </td>
                <td><?= h($f['term']) ?></td>
                <td>$<?= number_format($f['amount_billed'], 0) ?></td>
                <td>$<?= number_format($f['amount_paid'], 0) ?></td>
                <td>$<?= number_format(max($bal, 0), 0) ?></td>
                <td><span class="badge <?= $st ?>"><?= $lbl ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$feeRows): ?>
              <tr><td colspan="6" style="color:var(--slate)">No fee records yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Record a Payment</h3></div>
          <form action="fee_payment_save.php" method="POST">
            <div class="field">
              <label for="student_id">Student</label>
              <select id="student_id" name="student_id" required style="width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); color:var(--text);">
                <option value="">Select a student…</option>
                <?php foreach ($allStudents as $s): ?>
                  <option value="<?= (int)$s['id'] ?>"><?= h($s['full_name']) ?> — <?= h($s['class_name']) ?> (<?= h($s['admission_no']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="term">Term</label>
              <input type="text" id="term" name="term" placeholder="e.g. Term 2 2025/26" required>
            </div>
            <div class="form-grid-2">
              <div class="field">
                <label for="amount_billed">Amount Billed</label>
                <input type="number" step="0.01" id="amount_billed" name="amount_billed" placeholder="e.g. 2400" required>
              </div>
              <div class="field">
                <label for="amount_paid">Amount Paid Now</label>
                <input type="number" step="0.01" id="amount_paid" name="amount_paid" placeholder="e.g. 800" required>
              </div>
            </div>
            <p style="font-size:11.5px; color:var(--slate); margin:-6px 0 14px;">
              If a record already exists for this student + term, the payment is added to it; otherwise a new record is created.
            </p>
            <button type="submit" class="btn-small" style="width:100%;">Save Payment</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>