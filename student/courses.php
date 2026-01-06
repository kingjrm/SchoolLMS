<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - School LMS</title>
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
                <li class="nav-item"><a href="courses.php" class="nav-link active">Courses</a></li>
                <li class="nav-item"><a href="assignments.php" class="nav-link">Assignments</a></li>
                <li class="nav-item"><a href="grades.php" class="nav-link">Grades</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link">Announcements</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>My Courses</h1>
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
                    <h3 class="card-title">Enrolled Courses</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT c.*, t.name as term_name, u.first_name, u.last_name,
                        (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count,
                        (SELECT COUNT(*) FROM course_materials WHERE course_id = c.id) as material_count
                        FROM enrollments e 
                        JOIN courses c ON e.course_id = c.id 
                        JOIN academic_terms t ON c.term_id = t.id 
                        JOIN users u ON c.teacher_id = u.id 
                        WHERE e.student_id = ? AND e.status = 'enrolled' 
                        ORDER BY c.created_at DESC
                    ")->bind('i', $student_id)->execute();
                    $courses = $db->fetchAll();

                    if (!empty($courses)):
                    ?>
                        <div class="grid grid-2">
                            <?php foreach ($courses as $course): ?>
                            <div class="card">
                                <div class="card-header">
                                    <div>
                                        <h4 style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <p style="color: #9ca3af; font-size: 0.875rem;"><?php echo htmlspecialchars($course['code']); ?></p>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p><strong>Teacher:</strong> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></p>
                                    <p><strong>Term:</strong> <?php echo htmlspecialchars($course['term_name']); ?></p>
                                    <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f3f4f6;">
                                        <p style="color: #9ca3af; font-size: 0.875rem;">
                                            <?php echo $course['material_count']; ?> Materials • 
                                            <?php echo $course['assignment_count']; ?> Assignments
                                        </p>
                                    </div>
                                    <div style="margin-top: 1rem;">
                                        <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-primary" style="width: 100%;">View Course</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #9ca3af;">You are not enrolled in any courses</p>
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
