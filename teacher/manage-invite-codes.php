<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/CourseInvite.php';

// Redirect if not logged in
if (!Auth::isLoggedIn()) {
    header('Location: ../login.php', true, 302);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user = Auth::getCurrentUser();

// Redirect if not a teacher
if (!$user || $user['role'] !== 'teacher') {
    header('Location: ../student/dashboard.php', true, 302);
    exit;
}

$message = '';
$error = '';

// Handle generate code request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $course_id = intval($_POST['course_id'] ?? 0);
    
    if ($_POST['action'] === 'generate_code') {
        // Check if course belongs to this teacher
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$course_id, $user_id]);
        
        if ($stmt->fetch()) {
            $code = generateJoinCode($pdo);
            $updateStmt = $pdo->prepare("UPDATE courses SET join_code = ? WHERE id = ?");
            $updateStmt->execute([$code, $course_id]);
            $message = "✅ Code generated: <strong>$code</strong>";
        } else {
            $error = "Course not found or you don't have permission";
        }
    } elseif ($_POST['action'] === 'regenerate_code') {
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$course_id, $user_id]);
        
        if ($stmt->fetch()) {
            $code = generateJoinCode($pdo);
            $updateStmt = $pdo->prepare("UPDATE courses SET join_code = ? WHERE id = ?");
            $updateStmt->execute([$code, $course_id]);
            $message = "✅ Code regenerated: <strong>$code</strong>";
        } else {
            $error = "Course not found or you don't have permission";
        }
    } elseif ($_POST['action'] === 'remove_code') {
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$course_id, $user_id]);
        
        if ($stmt->fetch()) {
            $updateStmt = $pdo->prepare("UPDATE courses SET join_code = NULL WHERE id = ?");
            $updateStmt->execute([$course_id]);
            $message = "✅ Join code removed";
        } else {
            $error = "Course not found or you don't have permission";
        }
    }
}

// Get teacher's courses
$coursesStmt = $pdo->prepare(
    "SELECT c.id, c.code, c.title, c.join_code, c.max_students,
            (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled_count
     FROM courses c
     WHERE c.teacher_id = ? AND c.status = 'active'
     ORDER BY c.title ASC"
);
$coursesStmt->execute([$user_id]);
$courses = $coursesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Invitation Codes - School LMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        .courses-list {
            display: grid;
            gap: 20px;
        }

        .course-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }

        .course-title {
            flex: 1;
        }

        .course-title h3 {
            color: #333;
            font-size: 1.3em;
            margin-bottom: 5px;
        }

        .course-code {
            color: #666;
            font-size: 0.9em;
        }

        .enrollment-count {
            background: #f0f0f0;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9em;
            color: #666;
            white-space: nowrap;
        }

        .code-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #eee;
            margin-bottom: 15px;
        }

        .code-section label {
            display: block;
            color: #666;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.9em;
        }

        .code-display {
            background: white;
            border: 2px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 1.3em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        .no-code {
            background: white;
            border: 2px dashed #ddd;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            color: #999;
            margin-bottom: 15px;
        }

        .buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e8e8e8;
        }

        .btn-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-danger:hover {
            background: #f5c6cb;
        }

        .instructions {
            background: #e8eaf6;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
        }

        .instructions h4 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .instructions p {
            color: #555;
            font-size: 0.9em;
            margin-bottom: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85em;
            margin-left: 10px;
        }

        .copy-btn:hover {
            background: #764ba2;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="header">
            <h1>📝 Course Invitation Codes</h1>
            <p>Create and manage invitation codes for your courses (like Google Classroom)</p>
        </div>

        <div class="instructions">
            <h4>How it works:</h4>
            <p>✅ Generate unique codes for each of your courses</p>
            <p>✅ Share the code with students via email or announcement</p>
            <p>✅ Students enter the code in "Join Course" to enroll automatically</p>
            <p>✅ Regenerate codes anytime if needed</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($courses)): ?>
            <div class="empty-state">
                <h3>No Courses Found</h3>
                <p>You don't have any active courses yet.</p>
            </div>
        <?php else: ?>
            <div class="courses-list">
                <?php foreach ($courses as $course): ?>
                    <div class="course-item">
                        <div class="course-header">
                            <div class="course-title">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <div class="course-code"><?php echo htmlspecialchars($course['code']); ?></div>
                            </div>
                            <div class="enrollment-count">
                                👥 <?php echo $course['enrolled_count']; ?>/<?php echo $course['max_students']; ?> enrolled
                            </div>
                        </div>

                        <div class="code-section">
                            <label>Invitation Code</label>
                            
                            <?php if ($course['join_code']): ?>
                                <div class="code-display" id="code-<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['join_code']); ?>
                                </div>
                                <div class="buttons">
                                    <button class="copy-btn" onclick="copyToClipboard('code-<?php echo $course['id']; ?>')">
                                        📋 Copy Code
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <input type="hidden" name="action" value="regenerate_code">
                                        <button type="submit" class="btn btn-secondary" onclick="return confirm('Regenerate code? Old code will stop working.')">
                                            🔄 Regenerate
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <input type="hidden" name="action" value="remove_code">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Remove invitation code?')">
                                            ✕ Remove
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="no-code">No invitation code generated yet</div>
                                <form method="POST">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <input type="hidden" name="action" value="generate_code">
                                    <button type="submit" class="btn btn-primary">
                                        ✨ Generate Code
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent.trim();
            
            navigator.clipboard.writeText(text).then(() => {
                alert('Code copied: ' + text);
            }).catch(() => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Code copied: ' + text);
            });
        }
    </script>
</body>
</html>
