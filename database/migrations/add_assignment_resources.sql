-- Migration: add_assignment_resources.sql
-- Creates a table for assignment resources (files and links)

CREATE TABLE IF NOT EXISTS assignment_resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    type ENUM('file','link') NOT NULL,
    title VARCHAR(255),
    url TEXT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    mime_type VARCHAR(100),
    file_size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
