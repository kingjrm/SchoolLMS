<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - School LMS</title>
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
                <li class="nav-item"><a href="grades.php" class="nav-link">Grades</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link active">Announcements</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>Announcements</h1>
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
                    <h3 class="card-title">Course Announcements</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT a.*, c.code, c.title as course_title, u.first_name, u.last_name 
                        FROM announcements a 
                        JOIN courses c ON a.course_id = c.id 
                        JOIN users u ON a.posted_by = u.id 
                        JOIN enrollments e ON c.id = e.course_id 
                        WHERE e.student_id = ? AND e.status = 'enrolled'
                        ORDER BY a.pinned DESC, a.posted_at DESC
                    ")->bind('i', $student_id)->execute();
                    $announcements = $db->fetchAll();

                    if (!empty($announcements)):
                        foreach ($announcements as $ann):
                    ?>
                        <div style="background-color: #f9fafb; padding: 1.5rem; border-radius: 0.375rem; margin-bottom: 1rem; border-left: 4px solid <?php echo $ann['pinned'] ? '#3b82f6' : '#e5e7eb'; ?>;">
                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                    <div>
                                        <h4 style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                        <p style="color: #9ca3af; font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($ann['code'] . ' - ' . $ann['course_title']); ?>
                                            <?php echo $ann['pinned'] ? '<span class="badge badge-primary">Pinned</span>' : ''; ?>
                                        </p>
                                    </div>
                                </div>
                                <p style="color: #4b5563; margin: 1rem 0;"><?php echo htmlspecialchars($ann['content']); ?></p>
                                <p style="color: #9ca3af; font-size: 0.75rem;">
                                    Posted by <?php echo htmlspecialchars($ann['first_name'] . ' ' . $ann['last_name']); ?> 
                                    on <?php echo formatDate($ann['posted_at']); ?>
                                </p>
                            </div>
                        </div>
                        <?php 
                        endforeach;
                    else: ?>
                        <p style="text-align: center; color: #9ca3af;">No announcements in your courses</p>
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
