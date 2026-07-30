-- =====================================================================
-- Marist Brothers and Nyanga High School — Portal Database Schema
-- =====================================================================
DROP DATABASE IF EXISTS mbn_portal;
CREATE DATABASE mbn_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mbn_portal;

-- ---------------------------------------------------------------------
-- Core account table (all roles log in through here)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','teacher','admin') NOT NULL,
    avatar_initials VARCHAR(4) DEFAULT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,   -- relative path under /uploads/avatars/
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Classes (e.g. Grade 10A)
-- ---------------------------------------------------------------------
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,          -- e.g. '10A'
    grade_level INT NOT NULL,           -- e.g. 10
    class_teacher_id INT DEFAULT NULL,
    FOREIGN KEY (class_teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Students (extends users)
-- ---------------------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    admission_no VARCHAR(20) NOT NULL UNIQUE,  -- this doubles as the student's Student Number
    class_id INT NOT NULL,
    roll_no VARCHAR(20) NOT NULL,
    attendance_pct DECIMAL(5,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Teachers (extends users)
-- ---------------------------------------------------------------------
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    staff_no VARCHAR(20) NOT NULL UNIQUE,
    department VARCHAR(80) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Subjects taught in a class by a teacher
-- ---------------------------------------------------------------------
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Grades / report card entries
-- ---------------------------------------------------------------------
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    term VARCHAR(20) NOT NULL,         -- e.g. 'Term 2 2025/26'
    score DECIMAL(5,2) NOT NULL,
    letter_grade VARCHAR(3) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Daily attendance log
-- ---------------------------------------------------------------------
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_date DATE NOT NULL,
    status ENUM('present','absent','late') NOT NULL DEFAULT 'present',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_student_date (student_id, class_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Teacher attendance log (marked by admin)
-- ---------------------------------------------------------------------
CREATE TABLE teacher_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent','late') NOT NULL DEFAULT 'present',
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_teacher_date (teacher_id, attendance_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Fees
-- ---------------------------------------------------------------------
CREATE TABLE fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    term VARCHAR(20) NOT NULL,
    amount_billed DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Timetable
-- ---------------------------------------------------------------------
CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    day_of_week TINYINT NOT NULL,   -- 1=Mon .. 7=Sun
    start_time TIME NOT NULL,
    room VARCHAR(30) DEFAULT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Assignments + submissions
-- ---------------------------------------------------------------------
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    due_date DATE NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    is_late TINYINT(1) DEFAULT 0,
    score DECIMAL(5,2) DEFAULT NULL,
    status ENUM('pending','submitted','graded') DEFAULT 'pending',
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Announcements
-- ---------------------------------------------------------------------
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    body TEXT DEFAULT NULL,
    category VARCHAR(40) NOT NULL,     -- Exams, Events, Parents, Info
    audience ENUM('all','students','teachers','admin') DEFAULT 'all',
    posted_by INT DEFAULT NULL,        -- users.id of admin/teacher who posted it
    posted_date DATE NOT NULL,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Past papers (uploaded by teachers, browsed by students)
-- ---------------------------------------------------------------------
CREATE TABLE past_papers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    term VARCHAR(20) DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,   -- relative path under /uploads/past_papers/
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Contact form submissions from the public website
-- ---------------------------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(40) DEFAULT NULL,
    subject VARCHAR(150) DEFAULT NULL,
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Password for every seed account below is:  Password123
-- (bcrypt hash generated with PHP password_hash())
INSERT INTO users (full_name, email, password_hash, role, avatar_initials) VALUES
('Tendai Chikafu', 'tendai.chikafu@student.mbn.ac.zw', '$2b$10$B0UVhlkLNmjjbU.Wqs589eL7ePtCn9tm1uGcu5uCrHRtmKm.Qyt3m', 'student', 'TC'),
('Rufaro Marimo', 'rufaro.marimo@student.mbn.ac.zw', '$2b$10$B0UVhlkLNmjjbU.Wqs589eL7ePtCn9tm1uGcu5uCrHRtmKm.Qyt3m', 'student', 'RM'),
('Farai Gumbo', 'farai.gumbo@student.mbn.ac.zw', '$2b$10$B0UVhlkLNmjjbU.Wqs589eL7ePtCn9tm1uGcu5uCrHRtmKm.Qyt3m', 'student', 'FG'),
('Br. Joseph Mutasa', 'j.mutasa@mbn.ac.zw', '$2b$10$B0UVhlkLNmjjbU.Wqs589eL7ePtCn9tm1uGcu5uCrHRtmKm.Qyt3m', 'teacher', 'JM'),
('Mrs. Anesu Chirwa', 'a.chirwa@mbn.ac.zw', '$2b$10$B0UVhlkLNmjjbU.Wqs589eL7ePtCn9tm1uGcu5uCrHRtmKm.Qyt3m', 'teacher', 'AC'),
('Admin Office', 'admin@mbn.ac.zw', '$2b$10$B0UVhlkLNmjjbU.Wqs589eL7ePtCn9tm1uGcu5uCrHRtmKm.Qyt3m', 'admin', 'AD');

INSERT INTO classes (name, grade_level, class_teacher_id) VALUES
('10A', 10, 4),
('11B', 11, 5),
('12A', 12, 4),
('9C', 9, 5);

INSERT INTO students (user_id, admission_no, class_id, roll_no, attendance_pct) VALUES
(1, 'MBN-2024-001', 1, '2024-001', 94.00),
(2, 'MBN-2024-002', 1, '2024-002', 88.50),
(3, 'MBN-2023-014', 2, '2023-014', 91.00);

INSERT INTO teachers (user_id, staff_no, department) VALUES
(4, 'STF-001', 'Mathematics & Physics'),
(5, 'STF-002', 'Sciences');

INSERT INTO subjects (name, class_id, teacher_id) VALUES
('Mathematics', 1, 1),
('Physics', 1, 1),
('Biology', 1, 2),
('English Literature', 1, 2),
('Chemistry', 1, 2),
('History', 1, 1);

INSERT INTO grades (student_id, subject_id, term, score, letter_grade) VALUES
(1, 1, 'Term 2 2025/26', 92.00, 'A'),
(1, 2, 'Term 2 2025/26', 94.00, 'A'),
(1, 3, 'Term 2 2025/26', 88.00, 'B+'),
(1, 4, 'Term 2 2025/26', 83.00, 'B'),
(1, 5, 'Term 2 2025/26', 87.00, 'B+'),
(1, 6, 'Term 2 2025/26', 76.00, 'B-');

INSERT INTO fees (student_id, term, amount_billed, amount_paid) VALUES
(1, 'Annual 2025/26', 2400.00, 2400.00),
(2, 'Annual 2025/26', 2400.00, 1600.00),
(3, 'Annual 2025/26', 2400.00, 2400.00);

INSERT INTO timetable (class_id, subject_id, day_of_week, start_time, room) VALUES
(1, 1, 1, '08:00:00', 'Rm 101'),
(1, 4, 1, '09:00:00', 'Rm 204'),
(1, 3, 1, '10:00:00', 'Lab 3');

INSERT INTO assignments (subject_id, teacher_id, title, due_date) VALUES
(1, 1, 'Homework 3 — Quadratics', '2026-05-20'),
(2, 1, 'Problem Set 2 — Kinematics', '2026-05-21');

INSERT INTO assignment_submissions (assignment_id, student_id, submitted_at, is_late, status) VALUES
(1, 1, '2026-05-19 20:00:00', 1, 'submitted'),
(2, 2, '2026-05-18 10:00:00', 0, 'submitted');

INSERT INTO announcements (title, body, category, audience, posted_by, posted_date) VALUES
('Term 2 Exams Schedule Released', 'The Term 2 examination timetable has been published. Check your class noticeboard for room allocations.', 'Exams', 'all', 6, '2026-05-14'),
('Sports Day — 3 June', 'Inter-house sports day will be held on 3 June starting 8:00 AM. All students to be in house colours.', 'Events', 'all', 6, '2026-05-12'),
('Parent-Teacher Conference', 'Parents are invited to meet class teachers on the scheduled date to discuss Term 2 progress.', 'Parents', 'all', 6, '2026-05-09'),
('Library Now Open 24/7', 'The main library is now open around the clock for the exam period.', 'Info', 'all', 6, '2026-05-07'),
('Mathematics Revision Session', 'Extra revision for 10A on quadratic equations this Friday after school in Rm 101.', 'Academic', 'students', 4, '2026-05-15');

INSERT INTO teacher_attendance (teacher_id, attendance_date, status) VALUES
(1, '2026-05-18', 'present'),
(1, '2026-05-19', 'present'),
(1, '2026-05-20', 'late'),
(2, '2026-05-18', 'present'),
(2, '2026-05-19', 'absent'),
(2, '2026-05-20', 'present');

INSERT INTO past_papers (subject_id, teacher_id, title, term, file_path, uploaded_at) VALUES
(1, 1, 'Mathematics — Term 1 2025/26 Final Exam', 'Term 1 2025/26', 'past_papers/sample-mathematics-term1.pdf', '2026-04-02 09:00:00'),
(2, 1, 'Physics — Term 1 2025/26 Final Exam', 'Term 1 2025/26', 'past_papers/sample-physics-term1.pdf', '2026-04-02 09:10:00'),
(3, 2, 'Biology — Mid-Term Test', 'Term 2 2025/26', 'past_papers/sample-biology-midterm.pdf', '2026-05-10 14:00:00');
