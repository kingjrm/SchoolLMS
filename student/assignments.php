<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - School LMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
    require_once '../includes/config.php';
    require_once '../includes/Auth.php';
    require_once '../includes/Database.php';
    require_once '../includes/helpers.php';

    Auth::requireRole('student');
    $user = Auth::getCurrentUser();
    $db = new Database();
    $student_id = $user['id'];

    $message = '';

    // Handle submit assignment
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_assignment'])) {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        $submission_text = sanitize($_POST['submission_text'] ?? '');

        // Verify student is enrolled in course
        $db->prepare("
            SELECT a.id FROM assignments a 
            JOIN courses c ON a.course_id = c.id 
            JOIN enrollments e ON c.id = e.course_id 
            WHERE a.id = ? AND e.student_id = ? AND e.status = 'enrolled'
        ")->bind('ii', $assignment_id, $student_id)->execute();

        if ($db->getResult()->num_rows === 0) {
            $message = 'Invalid assignment';
        } else {
            // Check if already submitted
            $db->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?")->bind('ii', $assignment_id, $student_id)->execute();
            
            if ($db->getResult()->num_rows > 0) {
                // Update submission
                $query = "UPDATE assignment_submissions SET submission_text = ?, submitted_at = NOW(), status = 'submitted' WHERE assignment_id = ? AND student_id = ?";
                $db->prepare($query)
                    ->bind('sii', $submission_text, $assignment_id, $student_id)
                    ->execute();
                $message = 'Assignment updated';
            } else {
                // Create new submission
                $query = "INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, status) VALUES (?, ?, ?, 'submitted')";
                $db->prepare($query)
                    ->bind('iis', $assignment_id, $student_id, $submission_text)
                    ->execute();
                $message = 'Assignment submitted successfully';
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
                <li class="nav-item"><a href="assignments.php" class="nav-link active">Assignments</a></li>
                <li class="nav-item"><a href="grades.php" class="nav-link">Grades</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link">Announcements</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>Assignments</h1>
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
                    <h3 class="card-title">My Assignments</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT a.*, c.code, c.title as course_title, 
                        s.status as submission_status, s.submitted_at,
                        g.score, g.feedback
                        FROM assignments a 
                        JOIN courses c ON a.course_id = c.id 
                        JOIN enrollments e ON c.id = e.course_id 
                        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
                        LEFT JOIN grades g ON a.id = g.assignment_id AND g.student_id = ?
                        WHERE e.student_id = ? AND e.status = 'enrolled'
                        ORDER BY a.due_date DESC
                    ")->bind('iii', $student_id, $student_id, $student_id)->execute();
                    $assignments = $db->fetchAll();

                    if (!empty($assignments)):
                        foreach ($assignments as $assignment):
                    ?>
                        <div style="background-color: #f9fafb; padding: 1.5rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h4 style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                    <p style="color: #9ca3af; font-size: 0.875rem;">
                                        <?php echo htmlspecialchars($assignment['code']); ?> • 
                                        Due: <?php echo formatDate($assignment['due_date']); ?>
                                    </p>
                                </div>
                                <span style="
                                    padding: 0.25rem 0.75rem;
                                    border-radius: 9999px;
                                    font-size: 0.75rem;
                                    font-weight: 600;
                                    <?php
                                    if ($assignment['submission_status'] === 'graded') {
                                        echo 'background-color: #d1fae5; color: #065f46;';
                                    } elseif ($assignment['submission_status'] === 'submitted') {
                                        echo 'background-color: #dbeafe; color: #0c2d6b;';
                                    } elseif (isDeadlinePassed($assignment['due_date'])) {
                                        echo 'background-color: #fee2e2; color: #991b1b;';
                                    } else {
                                        echo 'background-color: #fef3c7; color: #92400e;';
                                    }
                                    ?>
                                ">
                                    <?php
                                    if ($assignment['submission_status'] === 'graded') {
                                        echo 'Graded';
                                    } elseif ($assignment['submission_status'] === 'submitted') {
                                        echo 'Submitted';
                                    } elseif (isDeadlinePassed($assignment['due_date'])) {
                                        echo 'Overdue';
                                    } else {
                                        echo 'Pending';
                                    }
                                    ?>
                                </span>
                            </div>

                            <p style="margin-bottom: 1rem; color: #4b5563;"><?php echo htmlspecialchars(truncateText($assignment['description'], 100)); ?></p>

                            <?php if ($assignment['submission_status'] === 'graded' && $assignment['score'] !== null): ?>
                            <div style="background-color: #fff; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; border-left: 4px solid #10b981;">
                                <p><strong>Score: <?php echo $assignment['score']; ?> / <?php echo $assignment['max_score']; ?></strong></p>
                                <?php if ($assignment['feedback']): ?>
                                <p style="margin-top: 0.5rem; color: #4b5563;">
                                    <strong>Feedback:</strong> <?php echo htmlspecialchars($assignment['feedback']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <form method="POST" style="background-color: #fff; padding: 1rem; border-radius: 0.375rem;">
                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                <div class="form-group">
                                    <label>Your Submission</label>
                                    <textarea name="submission_text" style="min-height: 80px;" placeholder="Enter your submission here..."><?php echo htmlspecialchars($assignment['submission_text'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" name="submit_assignment" class="btn btn-primary">Submit Assignment</button>
                            </form>
                        </div>
                        <?php 
                        endforeach;
                    else: ?>
                        <p style="text-align: center; color: #9ca3af;">No assignments in your enrolled courses</p>
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
