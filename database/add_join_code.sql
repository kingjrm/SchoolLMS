-- Add join_code column to courses table
-- Run this script to fix the "Unknown column 'join_code'" error

ALTER TABLE courses ADD COLUMN join_code VARCHAR(10) UNIQUE AFTER max_students;
ALTER TABLE courses ADD INDEX idx_join_code (join_code);
