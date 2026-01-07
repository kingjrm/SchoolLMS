<?php
require_once 'includes/config.php';
require_once 'includes/CourseInvite.php';

echo "<h2>Generating invitation codes for all courses...</h2>";

// Get all courses without codes
$stmt = $pdo->query("SELECT id, code, title FROM courses WHERE join_code IS NULL");
$courses = $stmt->fetchAll();

if (empty($courses)) {
    echo "<p>✅ All courses already have invitation codes!</p>";
} else {
    foreach ($courses as $course) {
        $code = generateJoinCode($pdo);
        $updateStmt = $pdo->prepare("UPDATE courses SET join_code = ? WHERE id = ?");
        $updateStmt->execute([$code, $course['id']]);
        echo "✅ {$course['title']} ({$course['code']}) → Code: <strong>$code</strong><br>";
    }
    echo "<p style='margin-top: 20px;'><strong>" . count($courses) . " courses updated!</strong></p>";
}

echo "<hr>";
echo "<p><a href='teacher/manage-invite-codes.php'>View invitation codes</a> | <a href='student/dashboard.php'>Student Dashboard</a> | <a href='join-course.php'>Join a course</a></p>";
?>
