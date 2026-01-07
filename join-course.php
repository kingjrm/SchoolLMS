<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/CourseInvite.php';

// Redirect if not logged in
if (!Auth::isLoggedIn()) {
    header('Location: login.php', true, 302);
    exit;
}

$user = Auth::getCurrentUser();
$user_id = $_SESSION['user_id'] ?? null;

// Redirect if not a student
if (!$user || $user['role'] !== 'student') {
    header('Location: login.php', true, 302);
    exit;
}

$error = '';
$success = '';

// Handle join code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $join_code = trim($_POST['join_code'] ?? '');
    
    if (empty($join_code)) {
        $error = 'Please enter a course code';
    } else {
        $result = joinCourseByCode($pdo, strtoupper($join_code), $user_id);
        if ($result['success']) {
            $success = $result['message'];
            $_POST['join_code'] = ''; // Clear the input
        } else {
            $error = $result['message'];
        }
    }
}

// Get list of available courses (not enrolled)
$enrolledStmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
$enrolledStmt->execute([$user_id]);
$enrolledCourses = $enrolledStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($enrolledCourses)) {
    $coursesStmt = $pdo->query(
        "SELECT c.id, c.code, c.title, c.join_code, u.first_name, u.last_name, 
                (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled,
                c.max_students
         FROM courses c
         JOIN users u ON c.teacher_id = u.id
         WHERE c.status = 'active'
         ORDER BY c.title ASC"
    );
} else {
    $placeholders = implode(',', array_fill(0, count($enrolledCourses), '?'));
    $sql = "SELECT c.id, c.code, c.title, c.join_code, u.first_name, u.last_name, 
                (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled,
                c.max_students
         FROM courses c
         JOIN users u ON c.teacher_id = u.id
         WHERE c.status = 'active' AND c.id NOT IN ($placeholders)
         ORDER BY c.title ASC";
    $coursesStmt = $pdo->prepare($sql);
    $coursesStmt->execute($enrolledCourses);
}

$availableCourses = $coursesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Course - School LMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            margin-top: 40px;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group input::placeholder {
            color: #999;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .course-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.3s;
        }

        .course-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .course-card h3 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 1.2em;
        }

        .course-card .code {
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
            color: #666;
            display: inline-block;
            margin-bottom: 10px;
        }

        .course-card .teacher {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .course-card .join-code {
            background: #e8eaf6;
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
            text-align: center;
            font-family: monospace;
            font-size: 1.1em;
            color: #667eea;
            font-weight: 600;
        }

        .course-card .enrollment {
            color: #999;
            font-size: 0.85em;
            margin: 10px 0;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.3s;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        .no-courses {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }

        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #ddd, transparent);
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="student/dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="header">
            <h1>Join a Course</h1>
            <p>Enter the course code provided by your instructor</p>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px; color: #333;">📝 Enter Course Code</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="join_code">Course Code</label>
                    <input 
                        type="text" 
                        id="join_code" 
                        name="join_code" 
                        placeholder="E.g., ABC123" 
                        maxlength="10"
                        style="text-transform: uppercase;"
                        value="<?php echo htmlspecialchars($_POST['join_code'] ?? ''); ?>"
                    >
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Codes are case-insensitive
                    </small>
                </div>
                <button type="submit" class="btn">Join Course</button>
            </form>
        </div>

        <?php if (!empty($availableCourses)): ?>
            <div class="card">
                <h2 style="margin-bottom: 20px; color: #333;">📚 Available Courses</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Here are the courses you can join. Ask your instructor for the course code.
                </p>

                <div class="courses-grid">
                    <?php foreach ($availableCourses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                            <span class="code"><?php echo htmlspecialchars($course['code']); ?></span>
                            
                            <div class="teacher">
                                👨‍🏫 <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                            </div>

                            <?php if ($course['join_code']): ?>
                                <div class="join-code"><?php echo htmlspecialchars($course['join_code']); ?></div>
                            <?php else: ?>
                                <div style="color: #999; font-size: 0.9em; padding: 10px; text-align: center;">
                                    No code available
                                </div>
                            <?php endif; ?>

                            <div class="enrollment">
                                👥 <?php echo $course['enrolled']; ?>/<?php echo $course['max_students']; ?> students
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card no-courses">
                <h3>✅ No Available Courses</h3>
                <p>You are already enrolled in all available courses!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
