<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades - School LMS</title>
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
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link">Courses</a></li>
                <li class="nav-item"><a href="assignments.php" class="nav-link">Assignments</a></li>
                <li class="nav-item"><a href="grades.php" class="nav-link active">Grades</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link">Announcements</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>Grades</h1>
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

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">My Grades by Course</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT c.id, c.code, c.title, u.first_name, u.last_name,
                        AVG(g.score) as average_grade,
                        COUNT(DISTINCT a.id) as total_assignments,
                        COUNT(DISTINCT g.id) as graded_count
                        FROM enrollments e 
                        JOIN courses c ON e.course_id = c.id 
                        JOIN users u ON c.teacher_id = u.id 
                        LEFT JOIN assignments a ON c.id = a.course_id 
                        LEFT JOIN grades g ON a.id = g.assignment_id AND g.student_id = ?
                        WHERE e.student_id = ? AND e.status = 'enrolled'
                        GROUP BY c.id
                        ORDER BY c.created_at DESC
                    ")->bind('ii', $student_id, $student_id)->execute();
                    $courses = $db->fetchAll();

                    if (!empty($courses)):
                    ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Teacher</th>
                                    <th>Assignments</th>
                                    <th>Average</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($course['code']); ?></strong><br>
                                        <span style="color: #9ca3af; font-size: 0.875rem;">
                                            <?php echo htmlspecialchars(truncateText($course['title'], 30)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></td>
                                    <td><?php echo $course['graded_count'] . ' / ' . $course['total_assignments']; ?></td>
                                    <td>
                                        <?php 
                                        if ($course['average_grade']) {
                                            echo round($course['average_grade'], 2) . '%';
                                        } else {
                                            echo 'No grades yet';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #9ca3af;">No courses enrolled</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detailed Grades</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT a.title as assignment_title, a.max_score, c.code, c.title as course_title,
                               g.score, g.feedback, g.graded_at
                        FROM grades g 
                        JOIN assignments a ON g.assignment_id = a.id 
                        JOIN courses c ON a.course_id = c.id 
                        JOIN enrollments e ON c.id = e.course_id 
                        WHERE g.student_id = ? AND e.student_id = ? AND e.status = 'enrolled'
                        ORDER BY g.graded_at DESC
                    ")->bind('ii', $student_id, $student_id)->execute();
                    $grades = $db->fetchAll();

                    if (!empty($grades)):
                        foreach ($grades as $grade):
                    ?>
                        <div style="background-color: #f9fafb; padding: 1.5rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h4 style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($grade['assignment_title']); ?></h4>
                                    <p style="color: #9ca3af; font-size: 0.875rem;">
                                        <?php echo htmlspecialchars($grade['course_title']); ?>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <p style="font-size: 1.5rem; font-weight: 700; color: #3b82f6;">
                                        <?php echo round($grade['score'], 2); ?> 
                                    </p>
                                    <p style="color: #9ca3af; font-size: 0.75rem;">
                                        out of <?php echo $grade['max_score']; ?>
                                    </p>
                                </div>
                            </div>

                            <?php if ($grade['feedback']): ?>
                            <div style="background-color: #fff; padding: 1rem; border-radius: 0.375rem;">
                                <p style="font-weight: 500; margin-bottom: 0.5rem;">Feedback:</p>
                                <p style="color: #4b5563;"><?php echo htmlspecialchars($grade['feedback']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php 
                        endforeach;
                    else: ?>
                        <p style="text-align: center; color: #9ca3af;">No grades yet</p>
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
