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

    Auth::requireRole('teacher');
    $user = Auth::getCurrentUser();
    $db = new Database();
    $teacher_id = $user['id'];

    $message = '';

    // Handle post announcement
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        $pinned = isset($_POST['pinned']) ? 1 : 0;

        if (empty($title) || empty($content) || $course_id === 0) {
            $message = 'All fields are required';
        } else {
            // Verify teacher owns this course
            $db->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?")->bind('ii', $course_id, $teacher_id)->execute();
            if ($db->getResult()->num_rows === 0) {
                $message = 'Invalid course';
            } else {
                $query = "INSERT INTO announcements (course_id, posted_by, title, content, pinned) VALUES (?, ?, ?, ?, ?)";
                $db->prepare($query)
                    ->bind('iissi', $course_id, $teacher_id, $title, $content, $pinned)
                    ->execute();
                $message = 'Announcement posted successfully';
            }
        }
    }

    // Handle delete
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        $db->prepare("DELETE a FROM announcements a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.teacher_id = ?")->bind('ii', $delete_id, $teacher_id)->execute();
        $message = 'Announcement deleted';
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

            <?php if ($message): ?>
                <?php echo showAlert(strpos($message, 'Error') === false ? 'success' : 'error', $message); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Post Announcement</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="course_id">Course</label>
                            <select id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php
                                $db->prepare("
                                    SELECT id, code, title FROM courses 
                                    WHERE teacher_id = ? AND status = 'active' 
                                    ORDER BY title
                                ")->bind('i', $teacher_id)->execute();
                                $courses = $db->fetchAll();
                                foreach ($courses as $c):
                                ?>
                                    <option value="<?php echo $c['id']; ?>">
                                        <?php echo htmlspecialchars($c['code'] . ' - ' . $c['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>

                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="pinned">
                                Pin this announcement
                            </label>
                        </div>

                        <button type="submit" name="submit" class="btn btn-primary">Post</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your Announcements</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT a.*, c.code, c.title as course_title 
                        FROM announcements a 
                        JOIN courses c ON a.course_id = c.id 
                        WHERE a.posted_by = ? 
                        ORDER BY a.pinned DESC, a.posted_at DESC
                    ")->bind('i', $teacher_id)->execute();
                    $announcements = $db->fetchAll();

                    if (!empty($announcements)):
                        foreach ($announcements as $ann):
                    ?>
                        <div style="background-color: #f9fafb; padding: 1.5rem; border-radius: 0.375rem; margin-bottom: 1rem; border-left: 4px solid <?php echo $ann['pinned'] ? '#3b82f6' : '#e5e7eb'; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <div>
                                    <h4 style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                    <p style="color: #9ca3af; font-size: 0.875rem;">
                                        <?php echo htmlspecialchars($ann['code'] . ' - ' . $ann['course_title']); ?>
                                        <?php echo $ann['pinned'] ? '<span class="badge badge-primary">Pinned</span>' : ''; ?>
                                    </p>
                                </div>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $ann['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                            <p><?php echo htmlspecialchars(truncateText($ann['content'], 150)); ?></p>
                            <p style="color: #9ca3af; font-size: 0.75rem;">Posted: <?php echo formatDate($ann['posted_at']); ?></p>
                        </div>
                        <?php 
                        endforeach;
                    else: ?>
                        <p style="text-align: center; color: #9ca3af;">No announcements</p>
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
