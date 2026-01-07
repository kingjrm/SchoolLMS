<?php
/**
 * Tasks helper for student personal task management
 */

class Tasks {
    public static function ensureTable($pdo) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS student_tasks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            notes TEXT NULL,
            due_date DATETIME NULL,
            priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
            is_completed TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_student (student_id),
            INDEX idx_completed (is_completed),
            INDEX idx_due (due_date),
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public static function list($pdo, $student_id, $completed = null, $limit = null) {
        $sql = "SELECT * FROM student_tasks WHERE student_id = :sid";
        if ($completed !== null) {
            $sql .= " AND is_completed = :completed";
        }
        $sql .= " ORDER BY is_completed ASC, COALESCE(due_date, '9999-12-31') ASC, created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':sid', $student_id, PDO::PARAM_INT);
        if ($completed !== null) {
            $stmt->bindValue(':completed', $completed ? 1 : 0, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function add($pdo, $student_id, $title, $notes = null, $due_date = null, $priority = 'medium') {
        $stmt = $pdo->prepare("INSERT INTO student_tasks (student_id, title, notes, due_date, priority) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$student_id, $title, $notes, $due_date, $priority]);
    }

    public static function toggleComplete($pdo, $student_id, $task_id) {
        $stmt = $pdo->prepare("UPDATE student_tasks SET is_completed = 1 - is_completed WHERE id = ? AND student_id = ?");
        return $stmt->execute([$task_id, $student_id]);
    }

    public static function delete($pdo, $student_id, $task_id) {
        $stmt = $pdo->prepare("DELETE FROM student_tasks WHERE id = ? AND student_id = ?");
        return $stmt->execute([$task_id, $student_id]);
    }
}
