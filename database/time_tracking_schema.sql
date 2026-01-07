-- Time Tracking Schema for Student LMS
-- Run this SQL to add time tracking capabilities

-- Table to track student session time on courses
CREATE TABLE IF NOT EXISTS student_time_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT,
    assignment_id INT,
    session_start DATETIME NOT NULL,
    session_end DATETIME,
    duration_seconds INT,
    page_type ENUM('course','assignment','general') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_assignment (assignment_id),
    INDEX idx_session_start (session_start),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Summary table for faster dashboard queries (aggregate view)
CREATE TABLE IF NOT EXISTS student_daily_time_summary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    summary_date DATE NOT NULL,
    total_seconds INT DEFAULT 0,
    course_count INT DEFAULT 0,
    assignment_count INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_date (student_id, summary_date),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
