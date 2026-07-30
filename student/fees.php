<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$role = 'student';
$active = 'fees';
$u = current_user();
$pdo = getDB();

$stmt = $pdo->prepare('SELECT * FROM students WHERE user_id = :uid');
$stmt->execute(['uid' => $u['id']]);
$student = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM fees WHERE student_id = :sid ORDER BY id DESC');
$stmt->execute(['sid' => $student['id']]);
$feeRows = $stmt->fetchAll();

$latest = $feeRows[0] ?? ['amount_billed' => 0, 'amount_paid' => 0, 'term' => 'N/A'];
$balance = $latest['amount_billed'] - $latest['amount_paid'];
$fullyPaid = $balance <= 0;
$paidPct = $latest['amount_billed'] > 0 ? round(($latest['amount_paid'] / $latest['amount_billed']) * 100) : 100;
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
        <div class="meta">Your billing history and exam docket eligibility</div>
      </div>
    </div>

    <div class="panel-grid">
      <div>
        <div class="panel">
          <div class="panel-head"><h3>Billing History</h3></div>
          <table class="data-table">
            <thead><tr><th>Term</th><th>Billed</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($feeRows as $f):
                  $bal = $f['amount_billed'] - $f['amount_paid'];
                  $status = $bal <= 0 ? 'Paid' : ($f['amount_paid'] > 0 ? 'Partial' : 'Due');
                  $cls = $bal <= 0 ? 'badge-paid' : ($f['amount_paid'] > 0 ? 'badge-partial' : 'badge-due');
              ?>
              <tr>
                <td><?= h($f['term']) ?></td>
                <td>$<?= number_format($f['amount_billed'],0) ?></td>
                <td>$<?= number_format($f['amount_paid'],0) ?></td>
                <td>$<?= number_format($bal,0) ?></td>
                <td><span class="badge <?= $cls ?>"><?= $status ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$feeRows): ?>
              <tr><td colspan="5" style="color:var(--slate)">No fee records yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div class="panel">
          <div class="panel-head"><h3>Fee Status — <?= h($latest['term']) ?></h3></div>
          <div style="display:flex; justify-content:space-between; align-items:baseline;">
            <span class="value" style="font-family:var(--font-data); font-size:22px;"><?= $paidPct ?>%</span>
            <span class="badge <?= $fullyPaid ? 'badge-paid' : 'badge-due' ?>"><?= $fullyPaid ? 'Fully Paid' : 'Balance Due' ?></span>
          </div>
          <div class="progress-bar"><div style="width:<?= min($paidPct,100) ?>%"></div></div>
          <p style="font-size:12.5px; color:var(--slate); margin-top:10px;">
            Balance: <b>$<?= number_format($balance,0) ?></b>
          </p>
        </div>

        <div class="panel">
          <div class="panel-head"><h3>Exam Docket</h3></div>
          <?php if ($fullyPaid): ?>
            <p style="font-size:13.5px; color:var(--sage); margin-bottom:14px;">✔ Fees fully settled — you're cleared to sit tests this term.</p>
            <a href="docket.php" target="_blank" class="btn-small" style="display:inline-block; text-decoration:none;">Download Exam Docket →</a>
          <?php else: ?>
            <p style="font-size:13.5px; color:var(--maroon); margin-bottom:6px;">⚠ No exam docket issued.</p>
            <p style="font-size:12.5px; color:var(--slate);">
              An outstanding balance of <b>$<?= number_format($balance,0) ?></b> is on your account for
              <?= h($latest['term']) ?>. Fees must be fully settled before an exam docket can be issued.
              Please contact the finance office to clear your balance.
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>
