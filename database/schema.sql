-- Learning Management System Database Schema
-- Created for managing courses, users, enrollments, assignments, and grades

CREATE DATABASE IF NOT EXISTS school_lms;
USE school_lms;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    phone VARCHAR(20),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic Terms Table
CREATE TABLE IF NOT EXISTS academic_terms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses Table
CREATE TABLE IF NOT EXISTS courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description LONGTEXT,
    teacher_id INT NOT NULL,
    term_id INT NOT NULL,
    credits INT,
    max_students INT,
    status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (term_id) REFERENCES academic_terms(id) ON DELETE RESTRICT,
    INDEX idx_teacher (teacher_id),
    INDEX idx_term (term_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollments Table
CREATE TABLE IF NOT EXISTS enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    enrollment_date DATE NOT NULL DEFAULT CURDATE(),
    status ENUM('enrolled', 'dropped', 'completed') NOT NULL DEFAULT 'enrolled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (course_id, student_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course Materials Table
CREATE TABLE IF NOT EXISTS course_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    file_path VARCHAR(255),
    file_type VARCHAR(50),
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_course (course_id),
    INDEX idx_upload_date (upload_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignments Table
CREATE TABLE IF NOT EXISTS assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    due_date DATETIME NOT NULL,
    max_score DECIMAL(5,2) DEFAULT 100.00,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_course (course_id),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignment Submissions Table
CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_file VARCHAR(255),
    submission_text LONGTEXT,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_late BOOLEAN DEFAULT FALSE,
    status ENUM('submitted', 'graded', 'returned') NOT NULL DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_submission (assignment_id, student_id),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grades Table
CREATE TABLE IF NOT EXISTS grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    score DECIMAL(5,2),
    feedback LONGTEXT,
    graded_by INT NOT NULL,
    graded_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_grade (assignment_id, student_id),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_assignment (assignment_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements Table
CREATE TABLE IF NOT EXISTS announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    posted_by INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_course (course_id),
    INDEX idx_posted_at (posted_at),
    INDEX idx_pinned (pinned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quizzes Table
CREATE TABLE IF NOT EXISTS quizzes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    due_date DATETIME,
    max_score DECIMAL(5,2) DEFAULT 100.00,
    pass_score DECIMAL(5,2) DEFAULT 60.00,
    shuffle_questions BOOLEAN DEFAULT FALSE,
    show_results BOOLEAN DEFAULT TRUE,
    time_limit INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_course (course_id),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz Questions Table
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    question_text LONGTEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer') NOT NULL DEFAULT 'multiple_choice',
    points DECIMAL(5,2) DEFAULT 1.00,
    question_order INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_quiz (quiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz Answers (Options) Table
CREATE TABLE IF NOT EXISTS quiz_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    answer_text VARCHAR(500),
    is_correct BOOLEAN DEFAULT FALSE,
    answer_order INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz Submissions Table
CREATE TABLE IF NOT EXISTS quiz_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    student_id INT NOT NULL,
    score DECIMAL(5,2),
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP,
    status ENUM('in_progress', 'submitted', 'graded') NOT NULL DEFAULT 'in_progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_quiz_submission (quiz_id, student_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_quiz (quiz_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz Student Answers Table
CREATE TABLE IF NOT EXISTS quiz_student_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_id INT,
    answer_text VARCHAR(500),
    is_correct BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES quiz_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (answer_id) REFERENCES quiz_answers(id) ON DELETE SET NULL,
    INDEX idx_submission (submission_id),
    INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Indexes for Performance
CREATE INDEX idx_users_role_status ON users(role, status);
CREATE INDEX idx_courses_active ON courses(status) WHERE status = 'active';
CREATE INDEX idx_enrollments_course_status ON enrollments(course_id, status);

-- Sample Data Insert

-- Insert Admin User
INSERT INTO users (username, email, password, first_name, last_name, role, status)
VALUES ('admin', 'admin@schoollms.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye4c4PbL4F8vLXPvDqKzU1ztxVfJS8zR.', 'System', 'Administrator', 'admin', 'active');

-- Insert Sample Teachers
INSERT INTO users (username, email, password, first_name, last_name, role, status)
VALUES 
('jsmith', 'jsmith@schoollms.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye4c4PbL4F8vLXPvDqKzU1ztxVfJS8zR.', 'John', 'Smith', 'teacher', 'active'),
('mdavis', 'mdavis@schoollms.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye4c4PbL4F8vLXPvDqKzU1ztxVfJS8zR.', 'Mary', 'Davis', 'teacher', 'active');

-- Insert Sample Students
INSERT INTO users (username, email, password, first_name, last_name, role, status)
VALUES 
('astudent', 'astudent@schoollms.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye4c4PbL4F8vLXPvDqKzU1ztxVfJS8zR.', 'Alice', 'Johnson', 'student', 'active'),
('bstudent', 'bstudent@schoollms.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye4c4PbL4F8vLXPvDqKzU1ztxVfJS8zR.', 'Bob', 'Wilson', 'student', 'active'),
('cstudent', 'cstudent@schoollms.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye4c4PbL4F8vLXPvDqKzU1ztxVfJS8zR.', 'Charlie', 'Brown', 'student', 'active');

-- Insert Academic Terms
INSERT INTO academic_terms (name, start_date, end_date, is_active)
VALUES 
('Fall 2025', '2025-09-01', '2025-12-15', FALSE),
('Spring 2026', '2026-01-01', '2026-04-30', TRUE),
('Summer 2026', '2026-06-01', '2026-08-15', FALSE);

-- Insert Sample Courses
INSERT INTO courses (code, title, description, teacher_id, term_id, credits, max_students, status)
VALUES 
('CS101', 'Introduction to Programming', 'Learn the basics of programming with Python', 2, 2, 3, 30, 'active'),
('CS201', 'Web Development', 'Learn to build responsive web applications', 2, 2, 3, 25, 'active'),
('MATH101', 'College Algebra', 'Essential mathematics for college students', 3, 2, 4, 35, 'active');

-- Insert Sample Enrollments
INSERT INTO enrollments (course_id, student_id, enrollment_date, status)
VALUES 
(1, 4, '2026-01-05', 'enrolled'),
(1, 5, '2026-01-06', 'enrolled'),
(1, 6, '2026-01-07', 'enrolled'),
(2, 4, '2026-01-05', 'enrolled'),
(2, 5, '2026-01-06', 'enrolled'),
(3, 5, '2026-01-06', 'enrolled'),
(3, 6, '2026-01-07', 'enrolled');

-- Insert Sample Materials
INSERT INTO course_materials (course_id, title, description, file_type, uploaded_by, upload_date)
VALUES 
(1, 'Python Basics Chapter 1', 'Introduction to Python programming language', 'pdf', 2, NOW()),
(1, 'Variables and Data Types', 'Understanding variables and basic data types', 'pdf', 2, NOW()),
(2, 'HTML Fundamentals', 'Getting started with HTML', 'pdf', 2, NOW());

-- Insert Sample Assignments
INSERT INTO assignments (course_id, title, description, due_date, max_score, created_by)
VALUES 
(1, 'Assignment 1: Hello World', 'Write your first Python program', DATE_ADD(NOW(), INTERVAL 7 DAY), 100, 2),
(1, 'Assignment 2: Variables', 'Create a program using variables', DATE_ADD(NOW(), INTERVAL 14 DAY), 100, 2),
(2, 'Assignment 1: Personal Website', 'Create a simple personal website using HTML and CSS', DATE_ADD(NOW(), INTERVAL 10 DAY), 100, 2);

-- Insert Sample Announcements
INSERT INTO announcements (course_id, posted_by, title, content, posted_at, pinned)
VALUES 
(1, 2, 'Welcome to CS101', 'Welcome to Introduction to Programming! Please review the syllabus and course materials.', NOW(), TRUE),
(1, 2, 'Assignment 1 Released', 'Assignment 1 has been released. Please submit by the due date.', NOW(), FALSE),
(2, 2, 'Class Schedule Updated', 'Our class schedule has been updated. See the course details for more information.', NOW(), TRUE);

-- News Table (for system-wide news articles)
CREATE TABLE IF NOT EXISTS news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    content LONGTEXT NOT NULL,
    image_url VARCHAR(255),
    author VARCHAR(100),
    posted_by INT NOT NULL,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_published_at (published_at),
    INDEX idx_views (views)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Announcements Table (for system-wide announcements)
CREATE TABLE IF NOT EXISTS system_announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    priority ENUM('normal', 'medium', 'high') NOT NULL DEFAULT 'normal',
    posted_by INT NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'expired') NOT NULL DEFAULT 'active',
    target_audience ENUM('all', 'students', 'teachers', 'admins') NOT NULL DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_posted_at (posted_at),
    INDEX idx_expires_at (expires_at),
    INDEX idx_target_audience (target_audience)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample News Articles
INSERT INTO news (title, summary, content, image_url, author, posted_by, published_at, status)
VALUES 
('New Semester Registration Now Open', 'Register for Spring 2026 courses starting January 10th', 
'We are excited to announce that registration for the Spring 2026 semester is now open! Students can begin selecting their courses through the student portal. Please review the course catalog and consult with your academic advisor to ensure you are on track for graduation. Registration will remain open until January 31st, 2026. Early registration is recommended as some popular courses fill up quickly.', 
NULL, 'Admin Team', (SELECT id FROM users WHERE username = 'admin' LIMIT 1), '2026-01-05 09:00:00', 'published'),

('School LMS Receives Excellence Award', 'Our platform recognized for innovation in educational technology', 
'We are proud to announce that School LMS has been recognized with the Educational Technology Excellence Award for 2025! This prestigious award acknowledges our commitment to providing innovative, user-friendly solutions that enhance the learning experience for students and educators alike. Thank you to our amazing community of users who have made this possible. We remain committed to continuous improvement and innovation in educational technology.', 
NULL, 'Communications Team', (SELECT id FROM users WHERE username = 'admin' LIMIT 1), '2026-01-03 10:30:00', 'published'),

('Winter Break Schedule and Important Dates', 'Campus operations and support during the winter break', 
'As we approach the winter break, please note the following important dates and information: Campus offices will be closed from December 23rd through January 2nd. Limited technical support will be available via email during this period. Regular operations resume on January 3rd, 2026. The Spring 2026 semester begins on January 15th, 2026. All students are encouraged to review their course schedules and prepare for the upcoming term. Have a wonderful winter break!', 
NULL, 'Academic Affairs', (SELECT id FROM users WHERE username = 'admin' LIMIT 1), '2025-12-15 14:00:00', 'published');

-- Insert Sample System Announcements
INSERT INTO system_announcements (title, content, priority, posted_by, posted_at, status, target_audience)
VALUES 
('System Maintenance Scheduled', 'The LMS will undergo scheduled maintenance on January 10th from 2:00 AM to 6:00 AM EST. The system will be temporarily unavailable during this time. Please plan accordingly and save your work before the maintenance window.', 'high', (SELECT id FROM users WHERE username = 'admin' LIMIT 1), '2026-01-05 08:00:00', 'active', 'all'),

('New Mobile App Available', 'We are excited to announce the launch of our new mobile app for iOS and Android! Access your courses, assignments, and grades on the go. Download it now from the App Store or Google Play.', 'medium', (SELECT id FROM users WHERE username = 'admin' LIMIT 1), '2026-01-04 10:00:00', 'active', 'all'),

('Academic Calendar Update', 'The academic calendar for Spring 2026 has been updated. Please check the important dates section for exam schedules, holidays, and deadlines.', 'normal', (SELECT id FROM users WHERE username = 'admin' LIMIT 1), '2026-01-03 09:00:00', 'active', 'all'),

('Library Extended Hours During Finals', 'The library will be open 24/7 during the final exam period (April 15-30, 2026). Additional study rooms and resources will be available. Good luck with your finals!', 'normal', (SELECT id FROM users WHERE username = 'admin' LIMIT 1), '2026-01-02 11:00:00', 'active', 'students'),

('Faculty Development Workshop', 'Join us for a professional development workshop on innovative teaching strategies. January 20th, 2026 at 3:00 PM in the Faculty Center. RSVP required.', 'normal', (SELECT id FROM users WHERE username = 'admin' LIMIT 1), '2026-01-01 13:00:00', 'active', 'teachers');

-- Note: Password hashes are bcrypt hashes for password 'password123'
-- To verify: password_verify('password123', hash) should return true
