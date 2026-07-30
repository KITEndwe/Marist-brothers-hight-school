<?php
require_once __DIR__ . '/includes/auth.php';

$contactStatus = $_GET['contact'] ?? null; // 'sent' | 'error' | null
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marist Brothers and Nyanga High School — Nyanga, Zimbabwe</title>
<meta name="description" content="Marist Brothers and Nyanga High School: Catholic secondary education in Nyanga, Manicaland, Zimbabwe. Academics, boarding, sports and a digital school portal for students, teachers and parents.">
<script>(function(){try{var t=localStorage.getItem('mbn-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<button id="theme-toggle-btn" class="theme-fab" title="Toggle light / dark theme" aria-label="Toggle theme">🌓</button>

<!-- ===== NAV ===== -->
<header class="site-nav">
  <div class="site-nav-inner">
    <a href="index.php#home" class="brand-row" style="text-decoration:none;">
      <div class="seal">MBN</div>
      <div class="brand-name">Marist Brothers<br>&amp; Nyanga High School</div>
    </a>

    <ul class="site-nav-links" id="site-nav-links">
      <li><a href="index.php#home">Home</a></li>
      <li><a href="index.php#services">Services</a></li>
      <li><a href="index.php#contact">Contact Us</a></li>
      <li><a href="login.php" class="btn-login">Login →</a></li>
    </ul>

    <button class="nav-toggle" id="nav-toggle" aria-label="Toggle menu">☰</button>
  </div>
</header>

<!-- ===== HOME / HERO ===== -->
<section id="home" class="site-hero">
  <div class="site-hero-inner">
    <div>
      <span class="site-eyebrow">🇿🇼 Nyanga · Manicaland Province · Zimbabwe</span>
      <h1>Forming young men and women of <span>faith, discipline and purpose</span>.</h1>
      <p class="lead">
        Marist Brothers and Nyanga High School provides Catholic secondary education
        for the ZIMSEC O-Level and A-Level curriculum, combining strong academics,
        boarding life and pastoral care in the highlands of Nyanga.
      </p>
      <div class="hero-cta-row">
        <a href="index.php#services" class="btn-hero-primary">Explore Our Services</a>
        <a href="login.php" class="btn-hero-ghost">Student / Staff Login →</a>
      </div>
    </div>

    <div class="hero-panel">
      <div class="hero-stats">
        <div class="hero-stat"><b>1,240</b><span>Students</span></div>
        <div class="hero-stat"><b>86</b><span>Teachers</span></div>
        <div class="hero-stat"><b>32</b><span>Classes</span></div>
      </div>
      <p class="hero-quote">"Ut vitam habeant" — that they may have life, in the Marist tradition of Champagnat.</p>
      <ul class="hero-checks">
        <li>ZIMSEC O-Level &amp; A-Level curriculum</li>
        <li>Boarding &amp; day scholar places</li>
        <li>Online portal for grades, fees &amp; attendance</li>
      </ul>
    </div>
  </div>
</section>

<!-- ===== SERVICES ===== -->
<section id="services" class="site-section">
  <div class="section-head">
    <div class="section-eyebrow">What We Offer</div>
    <h2>Services &amp; School Life</h2>
    <p>A well-rounded Marist education — academic, spiritual, physical and digital — under one roof in Nyanga.</p>
  </div>

  <div class="services-grid">
    <div class="service-card">
      <div class="ic">📘</div>
      <h3>Academic Excellence</h3>
      <p>Full ZIMSEC O-Level and A-Level teaching across Sciences, Commercials and Arts, with small class sizes and dedicated subject teachers.</p>
    </div>
    <div class="service-card">
      <div class="ic">🏠</div>
      <h3>Boarding &amp; Pastoral Care</h3>
      <p>Safe, disciplined boarding houses for boys and girls, supervised by matrons and Brothers who provide day-to-day pastoral guidance.</p>
    </div>
    <div class="service-card">
      <div class="ic">⛪</div>
      <h3>Catholic Formation</h3>
      <p>Daily prayer, chapel services and moral formation rooted in the charism of St Marcellin Champagnat and the Marist Brothers.</p>
    </div>
    <div class="service-card">
      <div class="ic">🏉</div>
      <h3>Sports &amp; Co-curricular</h3>
      <p>Rugby, football, athletics, netball and inter-house competitions, alongside clubs and societies that build teamwork and character.</p>
    </div>
    <div class="service-card">
      <div class="ic">💻</div>
      <h3>ICT &amp; Digital Learning</h3>
      <p>Computer laboratories and our own school management portal — live grades, timetables, fee statements and past papers online.</p>
    </div>
    <div class="service-card">
      <div class="ic">📚</div>
      <h3>Library &amp; Study Resources</h3>
      <p>A stocked library plus digital past papers and revision material shared by teachers directly through the student portal.</p>
    </div>
  </div>
</section>

<!-- ===== VALUES STRIP ===== -->
<section class="site-section alt" style="padding-top:48px; padding-bottom:48px;">
  <div class="values-strip">
    <div class="val"><b>1,240</b><span>Enrolled Students</span></div>
    <div class="val"><b>86</b><span>Teaching Staff</span></div>
    <div class="val"><b>32</b><span>Classes, Forms 1–6</span></div>
    <div class="val"><b>98%</b><span>ZIMSEC Pass Rate</span></div>
  </div>
</section>

<!-- ===== CONTACT US ===== -->
<section id="contact" class="site-section">
  <div class="section-head">
    <div class="section-eyebrow">Get In Touch</div>
    <h2>Contact Us</h2>
    <p>Questions about admissions, fees or the school portal? Reach the administration office directly.</p>
  </div>

  <div class="contact-grid">
    <div class="contact-info-card">
      <h3>Administration Office</h3>

      <div class="contact-row">
        <div class="ic">📍</div>
        <div><b>Address</b><span>Marist Brothers and Nyanga High School<br>P.O. Box 50, Nyanga, Manicaland Province, Zimbabwe</span></div>
      </div>
      <div class="contact-row">
        <div class="ic">📞</div>
        <div><b>Phone</b><span>+263 29 2 8412 · +263 77 234 5678 (WhatsApp)</span></div>
      </div>
      <div class="contact-row">
        <div class="ic">✉️</div>
        <div><b>Email</b><span>admin@mbn.ac.zw · admissions@mbn.ac.zw</span></div>
      </div>
      <div class="contact-row">
        <div class="ic">🕑</div>
        <div><b>Office Hours</b><span>Monday – Friday, 7:30 AM – 4:00 PM (CAT)</span></div>
      </div>
      <div class="contact-row">
        <div class="ic">🔐</div>
        <div><b>Already a student, teacher or admin?</b><span><a href="login.php" style="color:var(--gold-soft); font-weight:700;">Sign in to the portal →</a></span></div>
      </div>
    </div>

    <div class="contact-form-card">
      <h3>Send Us a Message</h3>
      <p>We'll get back to you within one working day.</p>

      <?php if ($contactStatus === 'sent'): ?>
        <div class="panel" style="border-color:var(--sage); background:#E4F0EA; color:#2F6D4E; padding:12px 18px; margin-bottom:18px;">✔ Thank you — your message has been received.</div>
      <?php elseif ($contactStatus === 'error'): ?>
        <div class="form-error" style="margin-bottom:18px;">Please fill in your name, email and message.</div>
      <?php endif; ?>

      <form action="contact_process.php" method="POST">
        <div class="form-grid-2">
          <div class="field">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" placeholder="Your name" required>
          </div>
          <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required>
          </div>
        </div>
        <div class="form-grid-2">
          <div class="field">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" placeholder="+263 7...">
          </div>
          <div class="field">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" placeholder="Admissions, Fees, General...">
          </div>
        </div>
        <div class="field">
          <label for="message">Message</label>
          <textarea id="message" name="message" rows="5" placeholder="How can we help?" required></textarea>
        </div>
        <button type="submit" class="btn-small" style="width:100%;">Send Message</button>
      </form>
    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="site-footer">
  <div class="footer-grid">
    <div>
      <div class="brand-row" style="margin-bottom:14px;">
        <div class="seal">MBN</div>
        <div class="brand-name" style="color:var(--paper);">Marist Brothers<br>&amp; Nyanga High School</div>
      </div>
      <p>Catholic secondary education in Nyanga, Manicaland Province, Zimbabwe — forming the whole person in faith, academics and community.</p>
    </div>
    <div>
      <h4>Quick Links</h4>
      <ul class="footer-links">
        <li><a href="index.php#home">Home</a></li>
        <li><a href="index.php#services">Services</a></li>
        <li><a href="index.php#contact">Contact Us</a></li>
        <li><a href="login.php">Portal Login</a></li>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul class="footer-links">
        <li>P.O. Box 50, Nyanga, Zimbabwe</li>
        <li>+263 29 2 8412</li>
        <li>admin@mbn.ac.zw</li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?= date('Y') ?> Marist Brothers and Nyanga High School. All rights reserved.</span>
    <span>MBN Portal v1.0</span>
  </div>
</footer>

<script src="assets/js/site.js"></script>
<script src="assets/js/theme.js"></script>
</body>
</html>
