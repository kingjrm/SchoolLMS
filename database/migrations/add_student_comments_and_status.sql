-- Migration: add_student_comments_and_status.sql
-- Adds student_comment field and is_completed flag to assignment_submissions

ALTER TABLE assignment_submissions 
ADD COLUMN student_comment LONGTEXT COMMENT 'Private comment from student to teacher',
ADD COLUMN is_completed BOOLEAN DEFAULT FALSE COMMENT 'Flag to mark as done without submission',
ADD INDEX idx_is_completed (is_completed);
