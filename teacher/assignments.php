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

    Auth::requireRole('teacher');
    $user = Auth::getCurrentUser();
    $db = new Database();
    $teacher_id = $user['id'];

    $message = '';
    $action = $_GET['action'] ?? 'list';

    // Handle add/edit
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $course_id = (int)($_POST['course_id'] ?? 0);
        $due_date = sanitize($_POST['due_date'] ?? '');
        $max_score = (float)($_POST['max_score'] ?? 100);
        $assignment_id = $_POST['assignment_id'] ?? null;

        if (empty($title) || $course_id === 0 || empty($due_date)) {
            $message = 'Required fields missing';
        } else {
            // Verify teacher owns this course
            $db->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?")->bind('ii', $course_id, $teacher_id)->execute();
            if ($db->getResult()->num_rows === 0) {
                $message = 'Invalid course';
            } else {
                if ($assignment_id) {
                    // Update
                    $query = "UPDATE assignments SET title = ?, description = ?, due_date = ?, max_score = ? WHERE id = ?";
                    $db->prepare($query)
                        ->bind('sssdi', $title, $description, $due_date, $max_score, $assignment_id)
                        ->execute();
                    $message = 'Assignment updated';
                } else {
                    // Create
                    $query = "INSERT INTO assignments (course_id, title, description, due_date, max_score, created_by) VALUES (?, ?, ?, ?, ?, ?)";
                    $db->prepare($query)
                        ->bind('isssdi', $course_id, $title, $description, $due_date, $max_score, $teacher_id)
                        ->execute();
                    $message = 'Assignment created';
                }
                $action = 'list';
            }
        }
    }

    // Get assignment for edit
    $edit_assignment = null;
    if ($action == 'edit' && isset($_GET['id'])) {
        $assignment_id = (int)$_GET['id'];
        $db->prepare("SELECT a.* FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.teacher_id = ?")->bind('ii', $assignment_id, $teacher_id)->execute();
        $edit_assignment = $db->fetch();
    }

    // Handle delete
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
        $del_id = (int)$_POST['delete_id'];
        $db->prepare("DELETE a FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.teacher_id = ?")->bind('ii', $del_id, $teacher_id)->execute();
        $message = 'Assignment deleted';
    }
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link">Courses</a></li>
                <li class="nav-item"><a href="materials.php" class="nav-link">Materials</a></li>
                <li class="nav-item"><a href="assignments.php" class="nav-link active">Assignments</a></li>
                <li class="nav-item"><a href="quizzes.php" class="nav-link">Quizzes</a></li>
                <li class="nav-item"><a href="grades.php" class="nav-link">Grades</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link">Announcements</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1><?php echo $action === 'edit' ? 'Edit Assignment' : 'Assignments'; ?></h1>
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

            <?php if ($action === 'list'): ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assignments</h3>
                    <a href="assignments.php?action=add" class="btn btn-primary btn-sm">Create Assignment</a>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT a.*, c.code, c.title as course_title,
                        (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count
                        FROM assignments a 
                        JOIN courses c ON a.course_id = c.id 
                        WHERE c.teacher_id = ? 
                        ORDER BY a.due_date DESC
                    ")->bind('i', $teacher_id)->execute();
                    $assignments = $db->fetchAll();

                    if (!empty($assignments)):
                    ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Course</th>
                                    <th>Due Date</th>
                                    <th>Submissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(truncateText($a['title'], 25)); ?></td>
                                    <td><?php echo htmlspecialchars($a['code']); ?></td>
                                    <td><?php echo formatDate($a['due_date']); ?></td>
                                    <td><?php echo $a['submission_count']; ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="assignments.php?action=edit&id=<?php echo $a['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete?');">
                                                <input type="hidden" name="delete_id" value="<?php echo $a['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #9ca3af;">No assignments created</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action === 'edit' ? 'Edit Assignment' : 'Create Assignment'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_assignment): ?>
                            <input type="hidden" name="assignment_id" value="<?php echo $edit_assignment['id']; ?>">
                        <?php endif; ?>

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
                                    <option value="<?php echo $c['id']; ?>" 
                                        <?php echo (($edit_assignment['course_id'] ?? '') == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['code'] . ' - ' . $c['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($edit_assignment['title'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($edit_assignment['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="due_date">Due Date & Time</label>
                                <input type="datetime-local" id="due_date" name="due_date" 
                                       value="<?php echo $edit_assignment ? str_replace(' ', 'T', $edit_assignment['due_date']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="max_score">Max Score</label>
                                <input type="number" id="max_score" name="max_score" step="0.01"
                                       value="<?php echo htmlspecialchars($edit_assignment['max_score'] ?? '100'); ?>">
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="submit" class="btn btn-primary">Save</button>
                            <a href="assignments.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php endif; ?>
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
