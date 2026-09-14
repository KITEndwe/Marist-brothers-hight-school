# Marist Brothers and Nyanga High School — Portal

A role-based school management portal (Student / Teacher / Admin) built with
PHP, MySQL, HTML, CSS and JavaScript, based on the EduPortal UI design.

## 1. Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB
- A local server: XAMPP, MAMP, WAMP, or PHP's built-in server

## 2. Set up the database

1. Create the database and tables by importing `schema.sql`:
   ```bash
   mysql -u root -p < schema.sql
   ```
   This creates a `mbn_portal` database, all tables, and seed data.

2. Open `config/database.php` and update the four constants if your MySQL
   username/password/host differ from the defaults (`root` / empty password).

## 3. Run the app

**Using PHP's built-in server (fastest way to test):**
```bash
cd eduportal
php -S localhost:8000
```
Then visit `http://localhost:8000`.

**Using XAMPP/MAMP/WAMP:** copy the `eduportal` folder into your `htdocs`
(or `www`) directory and visit `http://localhost/eduportal`.

## 4. Public website + portal login

The site now has two layers:

- **`index.php`** — the public website: **Home**, **Services**, and
  **Contact Us** sections on one scrolling page, plus a **Login** button in
  the nav that takes visitors to the portal.
- **`login.php`** — the role-based portal sign-in (Student / Teacher / Admin
  tabs) that used to be at `index.php`. Everything that used to redirect to
  `index.php` for login now redirects to `login.php`; `login.php` itself
  links back to `index.php` ("← Back to website").
- **`contact_process.php`** — saves public contact-form submissions into the
  new `contact_messages` table (name, email, phone, subject, message). There
  isn't an admin screen for these yet — view them via phpMyAdmin/MySQL CLI,
  or build `admin/contact.php` the same way the other admin pages query the
  database.

## 5. Demo logins

All seed accounts use the password: **Password123**

| Role    | Email                              |
|---------|-------------------------------------|
| Student | tendai.chikafu@student.mbn.ac.zw     |
| Student | rufaro.marimo@student.mbn.ac.zw      |
| Teacher | j.mutasa@mbn.ac.zw                   |
| Teacher | a.chirwa@mbn.ac.zw                   |
| Admin   | admin@mbn.ac.zw                      |

Pick the matching tab (Student / Teacher / Admin) on the login screen before
signing in — the tab controls which `role` is checked against the database.

## 6. Project structure

```
eduportal/
├── schema.sql               # Full DB schema + seed data
├── config/database.php      # PDO connection settings
├── includes/
│   ├── auth.php             # Session bootstrap + require_role() guard
│   ├── functions.php        # Small view helpers (h(), grade_color(), ...)
│   ├── upload.php           # handle_upload() + avatar_html() helpers
│   └── sidebar.php          # Shared sidebar, adapts nav items per role
├── assets/css/style.css     # Full design system incl. light/dark theme
├── assets/js/main.js        # Login role-tab switcher
├── assets/js/theme.js       # Light/dark theme toggle + localStorage
├── assets/js/site.js        # Mobile nav toggle for the public website
├── uploads/avatars/         # Profile photos land here
├── uploads/past_papers/     # Past paper uploads land here
├── index.php                # PUBLIC WEBSITE — Home / Services / Contact Us
├── login.php                 # Portal sign-in (Student/Teacher/Admin tabs)
├── login_process.php        # Verifies credentials, starts session
├── contact_process.php       # Saves public contact-form messages to DB
├── logout.php
├── student/
│   ├── dashboard.php        # Live stats, report card, today's timetable
│   ├── subjects.php         # Enrolled subjects + teacher + running average
│   ├── fees.php             # Billing history + exam docket gate
│   ├── docket.php           # Printable docket (only if fees fully paid)
│   ├── resources.php        # Past papers uploaded by their teachers
│   ├── announcements.php    # Notices from admin + teachers
│   ├── profile.php          # Student record + photo upload
│   └── photo_upload.php
├── teacher/
│   ├── dashboard.php        # Class performance, pending reviews, schedule
│   ├── attendance.php        # Mark daily attendance per class
│   ├── past-papers.php      # Upload past papers for their subjects
│   ├── announcements.php    # Post notices to students / everyone
│   ├── profile.php          # Staff record + photo upload
│   └── photo_upload.php
└── admin/
    ├── dashboard.php         # School-wide stats, fee collection by grade
    ├── teachers.php          # Staff directory + attendance rate
    ├── attendance.php        # Mark teacher attendance + view learner
    │                         #   attendance by grade and by class
    └── announcements.php     # Post/manage school-wide notices
```

## 7. What's fully wired vs. placeholder

- **Dashboards** (`student/dashboard.php`, `teacher/dashboard.php`,
  `admin/dashboard.php`) run real queries against MySQL: grades, attendance,
  fees, class rank, today's timetable, pending assignment reviews, fee
  collection by grade, etc.
- **Profile photo uploads** — `student/profile.php` and `teacher/profile.php`
  let each user upload a JPG/PNG/WEBP (max 3MB) via `photo_upload.php` in
  their folder. Files land in `uploads/avatars/` and are shown everywhere via
  `avatar_html()` in `includes/upload.php` (falls back to initials if no
  photo has been set).
- **Attendance tracking (admin)** — `admin/attendance.php` lets the admin
  mark every teacher present/late/absent for the day (`teacher_attendance`
  table) and shows learner attendance rolled up **by grade** and by class.
- **Attendance tracking (teacher)** — `teacher/attendance.php` lets a teacher
  pick one of their classes and mark each learner present/late/absent for
  the day; this recalculates that student's `attendance_pct` automatically.
- **Fees & exam docket (student)** — `student/fees.php` shows billing
  history and paid %. If a balance is outstanding, the **Exam Docket is
  blocked** with an explanation; `student/docket.php` (a printable page) only
  ever renders once fees are fully settled — it hard-redirects back to
  `fees.php` otherwise.
- **Subjects (student)** — `student/subjects.php` lists each enrolled
  subject, its teacher (with photo), and the student's running average.
- **Past papers** — teachers upload PDFs/DOCs from `teacher/past-papers.php`
  (stored in `uploads/past_papers/`, table `past_papers`); students see them
  instantly on `student/resources.php`, scoped to their own class.
- **Announcements** — both `admin/announcements.php` and
  `teacher/announcements.php` can post a notice (title, category, audience,
  message); students read the combined feed on `student/announcements.php`.
  Teachers can only address "My students" or "Everyone" — admin/teacher-only
  audiences are reserved for the admin account.
- **Student Number** — a student's `admission_no` (e.g. `MBN-2024-001`) is
  shown on their profile page and printed on the exam docket as their
  official Student Number.
- **Light / dark theme** — every page has a 🌓 toggle (bottom-right) that
  flips `data-theme="dark"` on `<html>` and remembers the choice in
  `localStorage`; the navy/gold brand colours stay fixed in both modes, only
  neutral surfaces and text swap.
- **Remaining placeholder pages** (`admin/students.php`, `admin/fees.php`,
  `admin/reports.php`, `admin/settings.php`, `admin/add-student.php`,
  `teacher/classes.php`, `teacher/grades.php`, `teacher/assignments.php`,
  `teacher/messages.php`, `student/grades.php`, `student/timetable.php`,
  `student/messages.php`) are protected, themed, and linked correctly, but
  still contain placeholder content. Build these out the same way the
  dashboards were built: write a query, loop over the results, and drop them
  into the existing `.panel` / `.data-table` / `.stat-card` classes.
- **Quick Grade Entry** on the teacher dashboard submits to
  `teacher/grade_save.php`, which doesn't exist yet — create it to `INSERT`
  or `UPDATE` a row in the `grades` table.

## 8. Uploads folder

`uploads/avatars/` and `uploads/past_papers/` must be **writable by PHP**
(`chmod -R 775 uploads` on Linux/Mac). They already contain three sample
past-paper placeholder files referenced by the seed data.

## 9. Security notes for going to production

- Passwords are hashed with bcrypt (`password_hash` / `password_verify`) —
  never store plain text passwords.
- All dynamic values are inserted via PDO prepared statements to prevent
  SQL injection.
- All output is escaped with `h()` (a thin `htmlspecialchars` wrapper) to
  prevent XSS.
- Before deploying: set real DB credentials, use HTTPS, set
  `session.cookie_secure` / `httponly`, and disable PHP error display.
