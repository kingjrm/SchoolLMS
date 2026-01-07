<?php
require_once 'includes/config.php';

echo "<h2>📊 System Data Check</h2>";
echo "<hr>";

// Check active term
$termStmt = $pdo->query("SELECT id, name, is_active FROM academic_terms WHERE is_active = 1 LIMIT 1");
$activeTerm = $termStmt->fetch();

echo "<h3>Active Term:</h3>";
if ($activeTerm) {
    echo "✅ ID: {$activeTerm['id']}, Name: {$activeTerm['name']}<br>";
} else {
    echo "❌ No active term found!<br>";
    echo "<p>Setting Spring 2024 as active...</p>";
    $pdo->query("UPDATE academic_terms SET is_active = 1 LIMIT 1");
    echo "✅ Done!<br>";
}

echo "<hr>";

// Check courses
$courseStmt = $pdo->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
$courseCount = $courseStmt->fetch()['count'];
echo "<h3>Active Courses: $courseCount</h3>";

if ($courseCount == 0) {
    echo "❌ No courses found! Running sample data generator...<br>";
    echo "<a href='insert-sample-data.php'>Click here to generate courses</a>";
} else {
    $courses = $pdo->query("SELECT id, code, title, status, join_code FROM courses WHERE status = 'active' LIMIT 5")->fetchAll();
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Code</th><th>Title</th><th>Join Code</th></tr>";
    foreach ($courses as $course) {
        echo "<tr>";
        echo "<td>{$course['id']}</td>";
        echo "<td>{$course['code']}</td>";
        echo "<td>{$course['title']}</td>";
        echo "<td><strong>" . ($course['join_code'] ?? 'NONE') . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";

// Check students
$studentStmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$studentCount = $studentStmt->fetch()['count'];
echo "<h3>Student Accounts: $studentCount</h3>";

echo "<hr>";

// Check teachers
$teacherStmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
$teacherCount = $teacherStmt->fetch()['count'];
echo "<h3>Teacher Accounts: $teacherCount</h3>";

echo "<hr>";
echo "<p><a href='student/dashboard.php'>→ Back to Student Dashboard</a></p>";
?>
