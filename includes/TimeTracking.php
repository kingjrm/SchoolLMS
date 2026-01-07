<?php
/**
 * TimeTracking helper class
 * Tracks student time spent on courses and assignments
 */

class TimeTracking {
    /**
     * Start a session for a student on a course/assignment
     */
    public static function startSession($pdo, $student_id, $course_id = null, $assignment_id = null, $page_type = 'general') {
        try {
            $stmt = $pdo->prepare("INSERT INTO student_time_logs (student_id, course_id, assignment_id, session_start, page_type) VALUES (?, ?, ?, NOW(), ?)");
            return $stmt->execute([$student_id, $course_id, $assignment_id, $page_type]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * End the most recent session for a student
     */
    public static function endSession($pdo, $student_id) {
        try {
            $stmt = $pdo->prepare("
                UPDATE student_time_logs 
                SET session_end = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, session_start, NOW())
                WHERE student_id = ? AND session_end IS NULL
                ORDER BY session_start DESC
                LIMIT 1
            ");
            return $stmt->execute([$student_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get total time spent by student today (in seconds)
     */
    public static function getTodayTotalSeconds($pdo, $student_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(duration_seconds), 0) as total 
                FROM student_time_logs 
                WHERE student_id = ? 
                AND DATE(session_start) = CURDATE()
                AND duration_seconds IS NOT NULL
            ");
            $stmt->execute([$student_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get total time spent by student this week (in seconds)
     */
    public static function getWeekTotalSeconds($pdo, $student_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(duration_seconds), 0) as total 
                FROM student_time_logs 
                WHERE student_id = ? 
                AND session_start >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND duration_seconds IS NOT NULL
            ");
            $stmt->execute([$student_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Format seconds to human-readable time (e.g., "1h 39m")
     */
    public static function formatSeconds($seconds) {
        $seconds = (int)$seconds;
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes . 'm';
        }
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return $hours . 'h ' . $mins . 'm';
    }
}
