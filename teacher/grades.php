<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submissions - School LMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
    require_once '../includes/config.php';
    require_once '../includes/Auth.php';
    require_once '../includes/Database.php';
    require_once '../includes/helpers.php';

    Auth::requireRole('teacher');
    $user = Auth::getCurrentUser();
    $db = new Database();
    $teacher_id = $user['id'];

    $message = '';

    // Handle grade submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_grade'])) {
        $submission_id = (int)($_POST['submission_id'] ?? 0);
        $score = (float)($_POST['score'] ?? 0);
        $feedback = sanitize($_POST['feedback'] ?? '');
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        $student_id = (int)($_POST['student_id'] ?? 0);

        if ($score < 0) {
            $message = 'Score cannot be negative';
        } else {
            // Verify teacher owns this course
            $db->prepare("
                SELECT a.id FROM assignments a 
                JOIN courses c ON a.course_id = c.id 
                WHERE a.id = ? AND c.teacher_id = ?
            ")->bind('ii', $assignment_id, $teacher_id)->execute();
            
            if ($db->getResult()->num_rows > 0) {
                // Update grade
                $db->prepare("
                    SELECT id FROM grades WHERE assignment_id = ? AND student_id = ?
                ")->bind('ii', $assignment_id, $student_id)->execute();
                
                if ($db->getResult()->num_rows > 0) {
                    // Update existing grade
                    $query = "UPDATE grades SET score = ?, feedback = ?, graded_by = ?, graded_at = NOW() WHERE assignment_id = ? AND student_id = ?";
                    $db->prepare($query)
                        ->bind('dssii', $score, $feedback, $teacher_id, $assignment_id, $student_id)
                        ->execute();
                } else {
                    // Insert new grade
                    $query = "INSERT INTO grades (assignment_id, student_id, score, feedback, graded_by, graded_at) VALUES (?, ?, ?, ?, ?, NOW())";
                    $db->prepare($query)
                        ->bind('iidss', $assignment_id, $student_id, $score, $feedback, $teacher_id)
                        ->execute();
                }

                // Update submission status
                $db->prepare("UPDATE assignment_submissions SET status = 'graded' WHERE id = ?")->bind('i', $submission_id)->execute();
                $message = 'Grade submitted successfully';
            } else {
                $message = 'Invalid assignment';
            }
        }
    }
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link">Courses</a></li>
                <li class="nav-item"><a href="materials.php" class="nav-link">Materials</a></li>
                <li class="nav-item"><a href="assignments.php" class="nav-link">Assignments</a></li>
                <li class="nav-item"><a href="quizzes.php" class="nav-link">Quizzes</a></li>
                <li class="nav-item"><a href="grades.php" class="nav-link active">Grades</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link">Announcements</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>Grade Submissions</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleDropdown()">Menu</button>
                        <div class="dropdown-menu" id="dropdown">
                            <a href="../logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <?php echo showAlert(strpos($message, 'Error') === false ? 'success' : 'error', $message); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pending Submissions</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT s.*, a.title as assignment_title, a.max_score, c.code, c.title as course_title,
                               u.first_name, u.last_name, g.score, g.feedback
                        FROM assignment_submissions s 
                        JOIN assignments a ON s.assignment_id = a.id 
                        JOIN courses c ON a.course_id = c.id 
                        JOIN users u ON s.student_id = u.id 
                        LEFT JOIN grades g ON a.id = g.assignment_id AND s.student_id = g.student_id
                        WHERE c.teacher_id = ? AND (s.status = 'submitted' OR g.score IS NULL)
                        ORDER BY s.submitted_at DESC
                    ")->bind('i', $teacher_id)->execute();
                    $submissions = $db->fetchAll();

                    if (!empty($submissions)):
                    ?>
                        <?php foreach ($submissions as $sub): ?>
                        <div style="background-color: #f9fafb; padding: 1.5rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <p><strong><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></strong></p>
                                    <p style="color: #9ca3af; font-size: 0.875rem;"><?php echo htmlspecialchars($sub['course_title']); ?></p>
                                </div>
                                <div>
                                    <p><strong><?php echo htmlspecialchars($sub['assignment_title']); ?></strong></p>
                                    <p style="color: #9ca3af; font-size: 0.875rem;">Submitted: <?php echo formatDate($sub['submitted_at']); ?></p>
                                </div>
                            </div>

                            <?php if ($sub['submission_file']): ?>
                            <a href="../<?php echo htmlspecialchars($sub['submission_file']); ?>" target="_blank" class="btn btn-secondary btn-sm" style="margin-bottom: 1rem;">View Submission</a>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                <input type="hidden" name="assignment_id" value="<?php echo $sub['assignment_id']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $sub['student_id']; ?>">

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Score (out of <?php echo $sub['max_score']; ?>)</label>
                                        <input type="number" name="score" step="0.01" max="<?php echo $sub['max_score']; ?>" 
                                               value="<?php echo htmlspecialchars($sub['score'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Feedback</label>
                                    <textarea name="feedback" style="min-height: 80px;"><?php echo htmlspecialchars($sub['feedback'] ?? ''); ?></textarea>
                                </div>

                                <button type="submit" name="submit_grade" class="btn btn-primary">Submit Grade</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #9ca3af;">No pending submissions</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById('dropdown').classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('dropdown');
            const userMenu = document.querySelector('.user-menu');
            if (!userMenu.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>
