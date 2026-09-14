<?php
/**
 * Expects $role ('student'|'teacher'|'admin') and $active (nav key) to be set
 * by the including page, plus current_user() available via auth.php.
 */
require_once __DIR__ . '/upload.php';
$u = current_user();

$navItems = [
    'student' => [
        'dashboard'      => ['🏠', 'Dashboard'],
        'subjects'       => ['📘', 'Subjects'],
        'grades'         => ['📊', 'My Grades'],
        'timetable'      => ['🗓', 'Timetable'],
        'fees'           => ['💳', 'Fees'],
        'resources'      => ['📚', 'Past Papers'],
        'announcements'  => ['📣', 'Announcements'],
        'messages'       => ['✉️', 'Messages'],
        'profile'        => ['👤', 'Profile'],
    ],
    'teacher' => [
        'dashboard'      => ['🏠', 'Dashboard'],
        'classes'        => ['🏫', 'My Classes'],
        'attendance'     => ['✅', 'Attendance'],
        'grades'         => ['⭐', 'Grades'],
        'past-papers'    => ['📚', 'Past Papers'],
        'announcements'  => ['📣', 'Announcements'],
        'assignments'    => ['📝', 'Assignments'],
        'messages'       => ['✉️', 'Messages'],
        'profile'        => ['👤', 'Profile'],
    ],
    'admin' => [
        'dashboard'      => ['🏠', 'Dashboard'],
        'students'       => ['🎓', 'Students'],
        'teachers'       => ['🧑‍🏫', 'Teachers'],
        'attendance'     => ['✅', 'Attendance'],
        'fees'           => ['💰', 'Fees'],
        'announcements'  => ['📣', 'Announcements'],
        'reports'        => ['📈', 'Reports'],
        'add-student'    => ['➕', 'Add Student'],
        'settings'       => ['⚙️', 'Settings'],
    ],
];

$portalLabel = ['student' => 'Student Portal', 'teacher' => 'Teacher Portal', 'admin' => 'Admin Portal'][$role];
?>
<aside class="sidebar">
  <div>
    <div class="brand-row">
      <div class="seal">MBN</div>
      <div class="brand-name" style="font-size:14px;">Marist Brothers<br>&amp; Nyanga HS</div>
    </div>
    <span class="portal-tag"><?= h($portalLabel) ?></span>
  </div>

  <ul class="nav">
    <?php foreach ($navItems[$role] as $key => [$icon, $label]): ?>
      <li>
        <a href="<?= $key === 'dashboard' ? 'dashboard.php' : $key . '.php' ?>"
           class="<?= $active === $key ? 'active' : '' ?>">
          <span><?= $icon ?></span> <?= h($label) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="sidebar-user">
    <?= avatar_html($u['photo'] ?? null, $u['initials'] ?? '??') ?>
    <div class="who">
      <b><?= h($u['full_name']) ?></b>
      <span><?= h(ucfirst($role)) ?></span>
    </div>
    <a href="../logout.php" class="logout-link" title="Sign out">Exit</a>
  </div>
</aside>
