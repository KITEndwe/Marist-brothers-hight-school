<?php
require_once __DIR__ . '/includes/auth.php';

// Already logged in? send them straight to their portal.
if (current_user()) {
    header('Location: ' . current_user()['role'] . '/dashboard.php');
    exit;
}

$error = $_GET['error'] ?? null;
$errorMessages = [
    'invalid'      => 'That email and password combination was not found. Please try again.',
    'wrong_portal' => 'Please sign in using the tab that matches your account type.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In · Marist Brothers and Nyanga High School Portal</title>
<script>(function(){try{var t=localStorage.getItem('mbn-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<button id="theme-toggle-btn" class="theme-fab" title="Toggle light / dark theme" aria-label="Toggle theme">🌓</button>


<div class="auth-page">

  <!-- Left hero panel -->
  <div class="auth-hero">
    <div>
      <a href="index.php" style="color: rgba(246,242,233,.7); font-size:12.5px; font-weight:600; margin-bottom:18px; display:inline-block;">← Back to website</a>
      <div class="brand-row">
        <div class="seal lg">MBN</div>
        <div class="brand-name">Marist Brothers and<br>Nyanga High School
          <small>School Management Portal</small>
        </div>
      </div>
    </div>

    <div>
      <div class="hero-stats">
        <div class="hero-stat"><b>1,240</b><span>Students</span></div>
        <div class="hero-stat"><b>86</b><span>Teachers</span></div>
        <div class="hero-stat"><b>32</b><span>Classes</span></div>
      </div>
      <p class="hero-quote">"Ut vitam habeant" — educating the whole person, in faith and discipline, since our founding.</p>
      <ul class="hero-checks">
        <li>Easy appointment scheduling</li>
        <li>Real-time grade tracking</li>
        <li>Smart fee management</li>
      </ul>
    </div>

    <div class="hero-foot">Trusted by the Marist Brothers community of Nyanga</div>
  </div>

  <!-- Right form panel -->
  <div class="auth-form-wrap">
    <div class="auth-card">
      <h1>Welcome back</h1>
      <p class="sub">Sign in to your school portal account</p>

      <?php if ($error && isset($errorMessages[$error])): ?>
        <div class="form-error"><?= h($errorMessages[$error]) ?></div>
      <?php endif; ?>

      <div class="role-tabs">
        <button type="button" class="active" data-role="student">Student</button>
        <button type="button" data-role="teacher">Teacher</button>
        <button type="button" data-role="admin">Admin</button>
      </div>

      <form action="login_process.php" method="POST">
        <input type="hidden" name="role" id="role-input" value="student">

        <div class="field">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="your@mbn.ac.zw" required>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••••••" required>
        </div>

        <div class="field-foot">
          <a href="#">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary">Sign In to Portal →</button>
      </form>

      <div class="divider">OR</div>
      <button type="button" class="btn-google">Continue with Google SSO</button>

      <p class="auth-secure">🔒 256-bit SSL encrypted · Your data is safe</p>
      <p class="auth-note">New student? Contact your administrator for access.</p>
      <p class="auth-foot">MBN Portal v1.0 · Privacy Policy · Support</p>
    </div>
  </div>

</div>

<script src="assets/js/main.js"></script>
<script src="assets/js/theme.js"></script>
</body>
</html>
