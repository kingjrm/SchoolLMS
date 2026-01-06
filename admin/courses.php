<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses Management - School LMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
    require_once '../includes/config.php';
    require_once '../includes/Auth.php';
    require_once '../includes/Database.php';
    require_once '../includes/helpers.php';

    Auth::requireRole('admin');
    $user = Auth::getCurrentUser();
    $db = new Database();

    $message = '';
    $action = $_GET['action'] ?? 'list';

    // Handle delete
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        $db->prepare("UPDATE courses SET status = 'archived' WHERE id = ?")->bind('i', $delete_id)->execute();
        $message = 'Course archived successfully';
    }

    // Handle add/edit
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $code = sanitize($_POST['code'] ?? '');
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $teacher_id = (int)($_POST['teacher_id'] ?? 0);
        $term_id = (int)($_POST['term_id'] ?? 0);
        $credits = (int)($_POST['credits'] ?? 0);
        $max_students = (int)($_POST['max_students'] ?? 0);
        $course_id = $_POST['course_id'] ?? null;

        if (empty($code) || empty($title) || $teacher_id === 0 || $term_id === 0) {
            $message = 'Required fields are missing';
        } else {
            if ($course_id) {
                // Update course
                $query = "UPDATE courses SET code = ?, title = ?, description = ?, teacher_id = ?, term_id = ?, credits = ?, max_students = ? WHERE id = ?";
                $db->prepare($query)
                    ->bind('ssssiii', $code, $title, $description, $teacher_id, $term_id, $credits, $max_students, $course_id)
                    ->execute();
                $message = 'Course updated successfully';
            } else {
                // Add new course
                $query = "INSERT INTO courses (code, title, description, teacher_id, term_id, credits, max_students, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
                $db->prepare($query)
                    ->bind('ssssiii', $code, $title, $description, $teacher_id, $term_id, $credits, $max_students)
                    ->execute();
                $message = 'Course created successfully';
            }
        }

        $action = 'list';
    }

    // Get course for edit
    $edit_course = null;
    if ($action == 'edit' && isset($_GET['id'])) {
        $edit_id = (int)$_GET['id'];
        $db->prepare("SELECT * FROM courses WHERE id = ?")->bind('i', $edit_id)->execute();
        $edit_course = $db->fetch();
    }
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link">Users</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link active">Courses</a></li>
                <li class="nav-item"><a href="terms.php" class="nav-link">Academic Terms</a></li>
                <li class="nav-item"><a href="enrollments.php" class="nav-link">Enrollments</a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link">Reports</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1><?php echo $action === 'edit' ? 'Edit Course' : 'Courses Management'; ?></h1>
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
                    <h3 class="card-title">All Courses</h3>
                    <a href="courses.php?action=add" class="btn btn-primary btn-sm">Add Course</a>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("SELECT c.*, u.first_name, u.last_name, t.name as term_name FROM courses c JOIN users u ON c.teacher_id = u.id JOIN academic_terms t ON c.term_id = t.id WHERE c.status = 'active' ORDER BY c.created_at DESC")->execute();
                    $courses = $db->fetchAll();
                    ?>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Teacher</th>
                                <th>Term</th>
                                <th>Credits</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['code']); ?></td>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['term_name']); ?></td>
                                <td><?php echo $course['credits']; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="courses.php?action=edit&id=<?php echo $course['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Archive this course?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $course['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Archive</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($courses)): ?>
                        <p style="text-align: center; color: #9ca3af;">No active courses</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action === 'edit' ? 'Edit Course' : 'Add New Course'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_course): ?>
                            <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="code">Course Code</label>
                                <input type="text" id="code" name="code" 
                                       value="<?php echo htmlspecialchars($edit_course['code'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="title">Course Title</label>
                                <input type="text" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($edit_course['title'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($edit_course['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="teacher_id">Teacher</label>
                                <select id="teacher_id" name="teacher_id" required>
                                    <option value="">Select Teacher</option>
                                    <?php
                                    $db->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY first_name")->execute();
                                    $teachers = $db->fetchAll();
                                    foreach ($teachers as $t):
                                    ?>
                                        <option value="<?php echo $t['id']; ?>" 
                                            <?php echo (($edit_course['teacher_id'] ?? '') == $t['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="term_id">Academic Term</label>
                                <select id="term_id" name="term_id" required>
                                    <option value="">Select Term</option>
                                    <?php
                                    $db->prepare("SELECT id, name FROM academic_terms ORDER BY start_date DESC")->execute();
                                    $terms = $db->fetchAll();
                                    foreach ($terms as $t):
                                    ?>
                                        <option value="<?php echo $t['id']; ?>"
                                            <?php echo (($edit_course['term_id'] ?? '') == $t['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="credits">Credits</label>
                                <input type="number" id="credits" name="credits" 
                                       value="<?php echo htmlspecialchars($edit_course['credits'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="max_students">Max Students</label>
                                <input type="number" id="max_students" name="max_students" 
                                       value="<?php echo htmlspecialchars($edit_course['max_students'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="submit" class="btn btn-primary">Save</button>
                            <a href="courses.php" class="btn btn-secondary">Cancel</a>
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
