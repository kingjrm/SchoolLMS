-- Seed Data for School LMS
-- Insert sample data for testing purposes

-- Clean up existing sample data (if any)
DELETE FROM grades WHERE student_id = 7;
DELETE FROM announcements WHERE course_id IN (SELECT id FROM courses WHERE code IN ('CS101', 'MATH201', 'ENG102', 'PHYS101', 'BIO101'));
DELETE FROM assignments WHERE course_id IN (SELECT id FROM courses WHERE code IN ('CS101', 'MATH201', 'ENG102', 'PHYS101', 'BIO101'));
DELETE FROM enrollments WHERE course_id IN (SELECT id FROM courses WHERE code IN ('CS101', 'MATH201', 'ENG102', 'PHYS101', 'BIO101'));
DELETE FROM courses WHERE code IN ('CS101', 'MATH201', 'ENG102', 'PHYS101', 'BIO101');
DELETE FROM student_tasks WHERE student_id = 7;
DELETE FROM user_profiles WHERE user_id IN (2, 7);
DELETE FROM academic_terms WHERE name = 'Spring 2024';

-- Create a default academic term first (required by courses table)
INSERT INTO academic_terms (name, start_date, end_date, is_active) VALUES
('Spring 2024', '2024-01-15', '2024-05-15', 1);

-- Sample Courses
INSERT INTO courses (code, title, description, teacher_id, term_id, credits, status) VALUES
('CS101', 'Introduction to Computer Science', 'Learn the fundamentals of programming and computer science concepts.', 2, 1, 3, 'active'),
('MATH201', 'Calculus I', 'Differential and integral calculus for engineers and scientists.', 2, 1, 4, 'active'),
('ENG102', 'English Literature', 'Study classic and contemporary works of English literature.', 2, 1, 3, 'active'),
('PHYS101', 'Physics I', 'Mechanics, waves, and thermodynamics.', 2, 1, 4, 'active'),
('BIO101', 'General Biology', 'Cell biology, genetics, and evolution.', 2, 1, 3, 'active');

-- Sample Enrollments (assuming student with ID 7 is enrolled)
INSERT INTO enrollments (student_id, course_id, status, enrollment_date) VALUES
(7, 1, 'enrolled', CURDATE()),
(7, 2, 'enrolled', CURDATE()),
(7, 3, 'enrolled', CURDATE()),
(7, 4, 'enrolled', CURDATE()),
(7, 5, 'enrolled', CURDATE());

-- Sample Assignments for CS101
INSERT INTO assignments (course_id, title, description, due_date, max_score, created_by) VALUES
(1, 'Python Basics', 'Write a program to calculate factorial', '2024-02-15 23:59:00', 100, 2),
(1, 'Data Structures', 'Implement linked list with insert and delete operations', '2024-03-15 23:59:00', 100, 2),
(1, 'Object-Oriented Programming', 'Design a banking system using OOP principles', '2024-04-15 23:59:00', 100, 2);

-- Sample Assignments for MATH201
INSERT INTO assignments (course_id, title, description, due_date, max_score, created_by) VALUES
(2, 'Limits and Continuity', 'Solve 20 calculus problems on limits', '2024-02-10 23:59:00', 100, 2),
(2, 'Derivatives', 'Find derivatives using differentiation rules', '2024-03-10 23:59:00', 100, 2),
(2, 'Integration', 'Evaluate definite and indefinite integrals', '2024-04-10 23:59:00', 100, 2);

-- Sample Assignments for ENG102
INSERT INTO assignments (course_id, title, description, due_date, max_score, created_by) VALUES
(3, 'Character Analysis Essay', 'Analyze a character from assigned reading', '2024-02-20 23:59:00', 100, 2),
(3, 'Book Report', 'Write a critical review of one novel', '2024-03-20 23:59:00', 100, 2);

-- Sample Assignments for PHYS101
INSERT INTO assignments (course_id, title, description, due_date, max_score, created_by) VALUES
(4, 'Kinematics Problems', 'Solve motion and acceleration problems', '2024-02-12 23:59:00', 100, 2),
(4, 'Energy Conservation', 'Apply conservation of energy principles', '2024-03-12 23:59:00', 100, 2);

-- Sample Assignments for BIO101
INSERT INTO assignments (course_id, title, description, due_date, max_score, created_by) VALUES
(5, 'Cell Biology Lab Report', 'Document microscope observations', '2024-02-18 23:59:00', 100, 2),
(5, 'Genetics Problems', 'Solve Mendelian genetics problems', '2024-03-18 23:59:00', 100, 2);

-- Sample Grades (for student ID 7)
INSERT INTO grades (student_id, assignment_id, score, feedback, graded_by) VALUES
(7, 1, 85, 'Good work! Code is clean and well-documented.', 2),
(7, 2, 92, 'Excellent implementation. Consider edge cases.', 2),
(7, 4, 78, 'Correct approach but some calculation errors.', 2),
(7, 5, 88, 'Good understanding of differentiation. Well done!', 2),
(7, 7, 82, 'Decent analysis. Could be more specific.', 2),
(7, 9, 90, 'Excellent kinematics solutions. All correct!', 2),
(7, 11, 87, 'Good lab report. Observations are clear.', 2);

-- Sample Announcements
INSERT INTO announcements (course_id, title, content, posted_by, posted_at) VALUES
(1, 'Welcome to CS101', 'Welcome to Introduction to Computer Science! This course covers programming fundamentals. Looking forward to an exciting semester!', 2, NOW()),
(1, 'Assignment 1 Extended', 'Due to technical difficulties, Assignment 1 deadline has been extended to February 18.', 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'Midterm Exam Scheduled', 'The midterm exam is scheduled for March 25. It will cover chapters 1-5.', 2, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 'Library Resources', 'Please make use of the library database for your literature research. Access details available on LMS.', 2, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(4, 'Lab Safety Reminder', 'Reminder: All students must follow lab safety protocols. Goggles and lab coats are mandatory.', 2, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 'Guest Lecture', 'Join us for a guest lecture by Dr. Smith on evolutionary biology next Friday at 2 PM.', 2, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Sample Tasks for student ID 7
INSERT INTO student_tasks (student_id, title, due_date, priority, is_completed) VALUES
(7, 'Review Chapter 3 for CS101', '2024-02-25 17:00:00', 'medium', 0),
(7, 'Complete Calculus practice problems', '2024-02-22 18:00:00', 'high', 0),
(7, 'Read assigned novel chapters', '2024-02-28 19:00:00', 'medium', 0),
(7, 'Prepare for Physics lab', '2024-02-21 16:00:00', 'high', 0),
(7, 'Organize Biology notes', '2024-02-27 20:00:00', 'low', 1),
(7, 'Study group meeting - CS101', '2024-02-23 14:00:00', 'medium', 0);

-- Sample user profile (if using separate profiles table)
INSERT INTO user_profiles (user_id, bio, phone) VALUES
(7, 'Computer Science student passionate about learning.', '+1-555-0123'),
(2, 'Teacher - School LMS Instructor.', '+1-555-0124')
ON DUPLICATE KEY UPDATE bio=VALUES(bio), phone=VALUES(phone);
