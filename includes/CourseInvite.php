<?php
// Function to generate a unique course join code
function generateJoinCode($pdo, $length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        // Check if code is unique
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE join_code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    
    return $code;
}

// Function to validate join code and enroll student
function joinCourseByCode($pdo, $code, $student_id) {
    $stmt = $pdo->prepare("SELECT id, title, max_students FROM courses WHERE join_code = ? AND status = 'active'");
    $stmt->execute([$code]);
    $course = $stmt->fetch();
    
    if (!$course) {
        return ['success' => false, 'message' => 'Invalid or inactive course code'];
    }
    
    // Check if already enrolled
    $enrollStmt = $pdo->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
    $enrollStmt->execute([$course['id'], $student_id]);
    
    if ($enrollStmt->fetch()) {
        return ['success' => false, 'message' => 'You are already enrolled in this course'];
    }
    
    // Check max students
    $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
    $countStmt->execute([$course['id']]);
    $count = $countStmt->fetch()['count'];
    
    if ($count >= $course['max_students']) {
        return ['success' => false, 'message' => 'This course is full'];
    }
    
    // Enroll student
    $insertStmt = $pdo->prepare(
        "INSERT INTO enrollments (course_id, student_id, enrollment_date, status) 
         VALUES (?, ?, CURDATE(), 'enrolled')"
    );
    $insertStmt->execute([$course['id'], $student_id]);
    
    return ['success' => true, 'message' => 'Successfully enrolled in ' . $course['title']];
}

// Export functions for use in other files
if (!function_exists('generateJoinCode')) {
    function generateJoinCode($pdo, $length = 6) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE join_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        
        return $code;
    }
}

if (!function_exists('joinCourseByCode')) {
    function joinCourseByCode($pdo, $code, $student_id) {
        $stmt = $pdo->prepare("SELECT id, title, max_students FROM courses WHERE join_code = ? AND status = 'active'");
        $stmt->execute([$code]);
        $course = $stmt->fetch();
        
        if (!$course) {
            return ['success' => false, 'message' => 'Invalid or inactive course code'];
        }
        
        $enrollStmt = $pdo->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
        $enrollStmt->execute([$course['id'], $student_id]);
        
        if ($enrollStmt->fetch()) {
            return ['success' => false, 'message' => 'You are already enrolled in this course'];
        }
        
        $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
        $countStmt->execute([$course['id']]);
        $count = $countStmt->fetch()['count'];
        
        if ($count >= $course['max_students']) {
            return ['success' => false, 'message' => 'This course is full'];
        }
        
        $insertStmt = $pdo->prepare(
            "INSERT INTO enrollments (course_id, student_id, enrollment_date, status) 
             VALUES (?, ?, CURDATE(), 'enrolled')"
        );
        $insertStmt->execute([$course['id'], $student_id]);
        
        return ['success' => true, 'message' => 'Successfully enrolled in ' . $course['title']];
    }
}
?>
