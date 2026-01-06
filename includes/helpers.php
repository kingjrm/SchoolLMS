<?php
/**
 * Helper functions for the application
 */

require_once __DIR__ . '/Database.php';

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get alert HTML
 */
function showAlert($type, $message) {
    $colors = [
        'success' => 'bg-green-50 border-l-4 border-green-500 text-green-700',
        'error' => 'bg-red-50 border-l-4 border-red-500 text-red-700',
        'warning' => 'bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700',
        'info' => 'bg-blue-50 border-l-4 border-blue-500 text-blue-700'
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    
    return <<<HTML
    <div class="$color p-4 mb-4" role="alert">
        <p class="font-medium">$message</p>
    </div>
    HTML;
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d H:i') {
    if (!$date) return '';
    try {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Get user role badge
 */
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Admin</span>',
        'teacher' => '<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Teacher</span>',
        'student' => '<span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Student</span>'
    ];
    
    return $badges[$role] ?? '';
}

/**
 * Get status badge
 */
function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Active</span>',
        'inactive' => '<span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Inactive</span>',
        'archived' => '<span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">Archived</span>',
        'enrolled' => '<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Enrolled</span>',
        'completed' => '<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Completed</span>',
        'submitted' => '<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Submitted</span>',
        'graded' => '<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Graded</span>'
    ];
    
    return $badges[$status] ?? '';
}

/**
 * Upload file safely
 */
function uploadFile($file, $folder = '') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }

    $allowed_types = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'jpg', 'jpeg', 'png', 'gif'];
    $max_size = 50 * 1024 * 1024; // 50MB

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_size = $file['size'];

    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }

    if ($file_size > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit'];
    }

    $upload_dir = UPLOAD_DIR . $folder;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
    $file_path = $upload_dir . $file_name;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }

    $relative_path = 'assets/uploads/' . $folder . $file_name;
    return ['success' => true, 'file' => $relative_path, 'name' => $file_name];
}

/**
 * Delete file safely
 */
function deleteFile($file_path) {
    $full_path = __DIR__ . '/../' . $file_path;
    if (file_exists($full_path) && is_file($full_path)) {
        return unlink($full_path);
    }
    return false;
}

/**
 * Get course progress
 */
function getCourseProgress($course_id, $student_id) {
    try {
        $db = new Database();

        // Get total assignments
        $query = "SELECT COUNT(*) as total FROM assignments WHERE course_id = ?";
        $db->prepare($query)->bind('i', $course_id)->execute();
        $total_assignments = $db->fetch()['total'];

        if ($total_assignments == 0) {
            return 0;
        }

        // Get completed assignments
        $query = "SELECT COUNT(*) as completed FROM grades 
                 WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?) 
                 AND student_id = ?";
        $db->prepare($query)->bind('ii', $course_id, $student_id)->execute();
        $completed = $db->fetch()['completed'];

        return round(($completed / $total_assignments) * 100);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get average grade
 */
function getAverageGrade($course_id, $student_id) {
    try {
        $db = new Database();

        $query = "SELECT AVG(score) as average FROM grades 
                 WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?) 
                 AND student_id = ?";
        $db->prepare($query)->bind('ii', $course_id, $student_id)->execute();
        $result = $db->fetch();

        return $result['average'] ? round($result['average'], 2) : 'N/A';
    } catch (Exception $e) {
        return 'N/A';
    }
}

/**
 * Check if deadline has passed
 */
function isDeadlinePassed($due_date) {
    try {
        $due = new DateTime($due_date);
        $now = new DateTime();
        return $now > $due;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get days remaining
 */
function getDaysRemaining($due_date) {
    try {
        $due = new DateTime($due_date);
        $now = new DateTime();
        $interval = $now->diff($due);
        
        if ($due < $now) {
            return 'Overdue';
        }
        
        if ($interval->d == 0 && $interval->h > 0) {
            return $interval->h . 'h remaining';
        }
        
        return $interval->d . 'd remaining';
    } catch (Exception $e) {
        return 'N/A';
    }
}
