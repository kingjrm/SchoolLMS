<?php
require_once 'includes/config.php';

echo "<h2>📊 Inserting Sample Data</h2>";

try {
    // Get the active term
    $termStmt = $pdo->query("SELECT id FROM academic_terms WHERE is_active = 1 LIMIT 1");
    $term = $termStmt->fetch();
    $term_id = $term['id'] ?? 3;

    // Insert additional teachers
    $teachers = [
        ['john_doe', 'john.doe@schoollms.com', 'John', 'Doe', 'password123'],
        ['jane_smith', 'jane.smith@schoollms.com', 'Jane', 'Smith', 'password123'],
        ['bob_wilson', 'bob.wilson@schoollms.com', 'Bob', 'Wilson', 'password123'],
    ];

    echo "<h3>Adding Teachers:</h3>";
    foreach ($teachers as $teacher) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->execute([$teacher[0]]);
        
        if (!$checkStmt->fetch()) {
            $hashedPass = password_hash($teacher[4], PASSWORD_BCRYPT);
            $insertStmt = $pdo->prepare(
                "INSERT INTO users (username, email, password, first_name, last_name, role, status, is_verified) 
                 VALUES (?, ?, ?, ?, ?, 'teacher', 'active', 1)"
            );
            $insertStmt->execute([$teacher[0], $teacher[1], $hashedPass, $teacher[2], $teacher[3]]);
            echo "✅ Added teacher: {$teacher[2]} {$teacher[3]}<br>";
        }
    }

    // Get teacher IDs
    $teacherStmt = $pdo->query("SELECT id, first_name FROM users WHERE role = 'teacher' LIMIT 4");
    $teachers_list = $teacherStmt->fetchAll();

    // Insert courses
    echo "<h3>Adding Courses:</h3>";
    $courses = [
        ['CS101', 'Introduction to Computer Science', 'Learn the fundamentals of programming and computer science.', 3],
        ['MATH201', 'Calculus I', 'Differential and integral calculus for engineering students.', 4],
        ['ENG101', 'English Composition', 'Develop writing and communication skills.', 3],
        ['PHYS101', 'Physics I', 'Mechanics, waves, and thermodynamics.', 4],
        ['CHEM101', 'Chemistry I', 'General chemistry covering atomic structure and reactions.', 3],
        ['HIST201', 'World History', 'Survey of major historical events and civilizations.', 3],
    ];

    foreach ($courses as $key => $course) {
        $checkStmt = $pdo->prepare("SELECT id FROM courses WHERE code = ?");
        $checkStmt->execute([$course[0]]);
        
        if (!$checkStmt->fetch()) {
            $teacher_id = $teachers_list[$key % count($teachers_list)]['id'];
            $insertStmt = $pdo->prepare(
                "INSERT INTO courses (code, title, description, teacher_id, term_id, credits, max_students, status) 
                 VALUES (?, ?, ?, ?, ?, ?, 30, 'active')"
            );
            $insertStmt->execute([$course[0], $course[1], $course[2], $teacher_id, $term_id, $course[3]]);
            echo "✅ Added course: {$course[1]}<br>";
        }
    }

    // Enroll students in courses
    echo "<h3>Enrolling Students in Courses:</h3>";
    $studentStmt = $pdo->query("SELECT id FROM users WHERE role = 'student' LIMIT 5");
    $students = $studentStmt->fetchAll();
    
    $courseStmt = $pdo->query("SELECT id FROM courses LIMIT 6");
    $courses_list = $courseStmt->fetchAll();

    $enrolled = 0;
    foreach ($students as $student) {
        foreach ($courses_list as $course) {
            $checkStmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $checkStmt->execute([$student['id'], $course['id']]);
            
            if (!$checkStmt->fetch()) {
                $insertStmt = $pdo->prepare(
                    "INSERT INTO enrollments (course_id, student_id, enrollment_date, status) 
                     VALUES (?, ?, CURDATE(), 'enrolled')"
                );
                $insertStmt->execute([$course['id'], $student['id']]);
                $enrolled++;
            }
        }
    }
    echo "✅ Enrolled students in courses ($enrolled enrollments created)<br>";

    echo "<hr><h3>✨ Sample Data Inserted Successfully!</h3>";
    echo "<a href='admin/dashboard.php'>Go to Admin Dashboard</a>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
