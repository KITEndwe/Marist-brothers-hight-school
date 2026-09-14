<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$role = 'admin';
$active = 'fees';
$u = current_user();
$pdo = getDB();

$errors = [];
$success = '';

// ---- Handle: record a payment against an existing fee ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $feeId  = (int)($_POST['fee_id'] ?? 0);
    $amount = (float)($_POST['payment_amount'] ?? 0);

    if ($feeId <= 0 || $amount <= 0) {
        $errors[] = 'Enter a valid payment amount.';
    } else {
        $stmt = $pdo->prepare('SELECT amount_billed, amount_paid FROM fees WHERE id = ?');
        $stmt->execute([$feeId]);
        $fee = $stmt->fetch();

        if (!$fee) {
            $errors[] = 'Fee record not found.';
        } else {
            $newPaid = min((float)$fee['amount_billed'], (float)$fee['amount_paid'] + $amount);
            $stmt = $pdo->prepare('UPDATE fees SET amount_paid = ? WHERE id = ?');
            $stmt->execute([$newPaid, $feeId]);
            $success = 'Payment recorded.';
        }
    }
}

// ---- Handle: bill a new fee to a student --------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_fee') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $term      = trim($_POST['term'] ?? '');
    $billed    = (float)($_POST['amount_billed'] ?? 0);

    if ($studentId <= 0) $errors[] = 'Please select a student.';
    if ($term === '') $errors[] = 'Term is required.';
    if ($billed <= 0) $errors[] = 'Amount billed must be greater than zero.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM fees WHERE student_id = ? AND term = ?');
        $stmt->execute([$studentId, $term]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'This student already has a fee record for that term.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO fees (student_id, term, amount_billed, amount_paid) VALUES (?, ?, ?, 0.00)');
            $stmt->execute([$studentId, $term, $billed]);
            $success = 'Fee billed to student.';
        }
    }
}

// ---- Filters -------------------------------------------------------------
$termFilter   = trim($_GET['term'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$terms = $pdo->query('SELECT DISTINCT term FROM fees ORDER BY term DESC')->fetchAll(PDO::FETCH_COLUMN);

$where = [];
$params = [];
if ($termFilter !== '') { $where[] = 'f.term = ?'; $params[] = $termFilter; }

$havingSql = '';
if ($statusFilter === 'paid')    $havingSql = 'HAVING f.amount_paid >= f.amount_billed';
if ($statusFilter === 'partial') $havingSql = 'HAVING f.amount_paid > 0 AND f.amount_paid < f.amount_billed';
if ($statusFilter === 'unpaid')  $havingSql = 'HAVING f.amount_paid = 0';

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT f.id, f.term, f.amount_billed, f.amount_paid, s.id AS student_id,
               us.full_name, us.avatar_initials, c.name AS class_name
        FROM fees f
        JOIN students s ON s.id = f.student_id
        JOIN users us ON us.id = s.user_id
        JOIN classes c ON c.id = s.class_id
        $whereSql
        $havingSql
        ORDER BY f.term DESC, us.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fees = $stmt->fetchAll();

// ---- Summary stats (respecting the term filter only, for a clean overview) --
$summarySql = 'SELECT SUM(amount_billed) AS billed, SUM(amount_paid) AS paid, COUNT(*) AS cnt,
                      SUM(CASE WHEN amount_paid < amount_billed THEN 1 ELSE 0 END) AS defaulters
               FROM fees f' . ($termFilter !== '' ? ' WHERE f.term = ?' : '');
$stmt = $pdo->prepare($summarySql);
$stmt->execute($termFilter !== '' ? [$termFilter] : []);
$summary = $stmt->fetch();
$billed = (float)($summary['billed'] ?? 0);
$paid   = (float)($summary['paid'] ?? 0);
$collectionRate = $billed > 0 ? round(($paid / $billed) * 100) : 0;
$defaulters = (int)($summary['defaulters'] ?? 0);

// ---- Students without a fee record for the "bill a fee" form -------------
$allStudents = $pdo->query(
    "SELECT s.id, us.full_name, c.name AS class_name
     FROM students s
     JOIN users us ON us.id = s.user_id
     JOIN classes c ON c.id = s.class_id
     ORDER BY us.full_name"
)->fetchAll();
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
        <div class="meta">Fee collection · Academic Year 2025/26<?= $termFilter !== '' ? ' · ' . h($termFilter) : '' ?></div>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="panel" style="border-left:4px solid var(--sage, #4caf7d);">
        <p style="color:var(--sage, #4caf7d); font-weight:600;">✅ <?= h($success) ?></p>
      </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="panel" style="border-left:4px solid var(--maroon, #b3261e);">
        <?php foreach ($errors as $err): ?>
          <p style="color:var(--maroon, #b3261e); margin:4px 0;">⚠️ <?= h($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="stat-grid">
      <div class="stat-card accent-sage">
        <div class="tag">💰</div>
        <div class="label">Fees Collected</div>
        <div class="value">$<?= number_format($paid, 0) ?></div>
        <div class="foot" style="color:var(--slate)">of $<?= number_format($billed, 0) ?> billed</div>
      </div>
      <div class="stat-card accent-gold">
        <div class="tag">📊</div>
        <div class="label">Collection Rate</div>
        <div class="value"><?= $collectionRate ?>%</div>
        <div class="foot" style="color:var(--slate)"><?= (int)($summary['cnt'] ?? 0) ?> fee records</div>
      </div>
      <div class="stat-card accent-maroon">
        <div class="tag">⚠️</div>
        <div class="label">Fee Defaulters</div>
        <div class="value"><?= $defaulters ?></div>
        <div class="foot" style="color:var(--maroon)">Action required</div>
      </div>
    </div>

    <div class="panel">
      <form method="get" action="fees.php" style="display:flex; gap:12px; flex-wrap:wrap;">
        <select name="term" style="padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          <option value="">All terms</option>
          <?php foreach ($terms as $t): ?>
            <option value="<?= h($t) ?>" <?= $termFilter === $t ? 'selected' : '' ?>><?= h($t) ?></option>
          <?php endforeach; ?>
        </select>

        <select name="status" style="padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
          <option value="">Any status</option>
          <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
          <option value="partial" <?= $statusFilter === 'partial' ? 'selected' : '' ?>>Partial</option>
          <option value="unpaid" <?= $statusFilter === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
        </select>

        <button type="submit" style="padding:8px 16px; border:1px solid var(--border,#ccc); border-radius:6px; background:#fff; cursor:pointer;">Filter</button>
        <?php if ($termFilter !== '' || $statusFilter !== ''): ?>
          <a href="fees.php" style="padding:8px 16px; align-self:center; color:var(--slate);">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="panel">
      <table class="data-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Class</th>
            <th>Term</th>
            <th>Billed</th>
            <th>Paid</th>
            <th>Outstanding</th>
            <th>Status</th>
            <th>Record Payment</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($fees)): ?>
          <tr><td colspan="8" style="text-align:center; color:var(--slate); padding:24px;">No fee records found.</td></tr>
          <?php endif; ?>

          <?php foreach ($fees as $f):
              $bill = (float)$f['amount_billed'];
              $pd   = (float)$f['amount_paid'];
              $due  = max(0, $bill - $pd);
              if ($pd >= $bill) { $statusLabel = 'Paid'; $statusClass = 'badge-paid'; }
              elseif ($pd > 0) { $statusLabel = 'Partial'; $statusClass = 'badge-partial'; }
              else { $statusLabel = 'Unpaid'; $statusClass = 'badge-due'; }
          ?>
          <tr>
            <td style="display:flex; align-items:center; gap:8px;">
              <div class="avatar" style="width:26px;height:26px;font-size:10px;"><?= h($f['avatar_initials']) ?></div>
              <?= h($f['full_name']) ?>
            </td>
            <td><?= h($f['class_name']) ?></td>
            <td><?= h($f['term']) ?></td>
            <td>$<?= number_format($bill, 2) ?></td>
            <td>$<?= number_format($pd, 2) ?></td>
            <td><?= $due > 0 ? '$' . number_format($due, 2) : '—' ?></td>
            <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
            <td>
              <?php if ($due > 0): ?>
              <form method="post" action="fees.php" style="display:flex; gap:6px;">
                <input type="hidden" name="action" value="record_payment">
                <input type="hidden" name="fee_id" value="<?= (int)$f['id'] ?>">
                <input type="number" name="payment_amount" step="0.01" min="0.01" max="<?= $due ?>"
                       placeholder="Amount" required
                       style="width:90px; padding:6px; border:1px solid var(--border,#ccc); border-radius:6px;">
                <button type="submit" style="padding:6px 10px; background:var(--gold,#c9a227); color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:12px;">Pay</button>
              </form>
              <?php else: ?>
                <span style="color:var(--slate); font-size:13px;">Settled</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Bill a New Fee</h3></div>
      <form method="post" action="fees.php" style="display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:12px; align-items:end;">
        <input type="hidden" name="action" value="add_fee">
        <div>
          <label style="display:block; font-size:13px; margin-bottom:4px;">Student</label>
          <select name="student_id" required style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
            <option value="">Select a student…</option>
            <?php foreach ($allStudents as $st): ?>
              <option value="<?= (int)$st['id'] ?>"><?= h($st['full_name']) ?> — <?= h($st['class_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label style="display:block; font-size:13px; margin-bottom:4px;">Term</label>
          <input type="text" name="term" required placeholder="e.g. Term 3 2025/26"
                 style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
        </div>
        <div>
          <label style="display:block; font-size:13px; margin-bottom:4px;">Amount Billed</label>
          <input type="number" name="amount_billed" step="0.01" min="0.01" required
                 style="width:100%; padding:8px; border:1px solid var(--border,#ccc); border-radius:6px;">
        </div>
        <div>
          <button type="submit" style="padding:9px 18px; background:var(--gold,#c9a227); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Bill Fee</button>
        </div>
      </form>
    </div>
  </main>
</div>
<script src="../assets/js/theme.js"></script>
</body>
</html>