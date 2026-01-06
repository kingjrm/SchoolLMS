<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollments - School LMS</title>
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

    // Handle enroll
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $course_id = (int)($_POST['course_id'] ?? 0);

        if ($student_id === 0 || $course_id === 0) {
            $message = 'Please select both student and course';
        } else {
            // Check if already enrolled
            $db->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?")->bind('ii', $student_id, $course_id)->execute();
            if ($db->getResult()->num_rows > 0) {
                $message = 'Student is already enrolled in this course';
            } else {
                $query = "INSERT INTO enrollments (course_id, student_id, enrollment_date, status) VALUES (?, ?, CURDATE(), 'enrolled')";
                $db->prepare($query)
                    ->bind('ii', $course_id, $student_id)
                    ->execute();
                $message = 'Enrollment created successfully';
                $action = 'list';
            }
        }
    }

    // Handle drop
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        $db->prepare("UPDATE enrollments SET status = 'dropped' WHERE id = ?")->bind('i', $delete_id)->execute();
        $message = 'Student dropped successfully';
    }
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link">Users</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link">Courses</a></li>
                <li class="nav-item"><a href="terms.php" class="nav-link">Academic Terms</a></li>
                <li class="nav-item"><a href="enrollments.php" class="nav-link active">Enrollments</a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link">Reports</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>Enrollments Management</h1>
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
                    <h3 class="card-title">Active Enrollments</h3>
                    <a href="enrollments.php?action=add" class="btn btn-primary btn-sm">Add Enrollment</a>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT e.*, u.first_name, u.last_name, c.title, c.code 
                        FROM enrollments e 
                        JOIN users u ON e.student_id = u.id 
                        JOIN courses c ON e.course_id = c.id 
                        WHERE e.status = 'enrolled'
                        ORDER BY e.enrollment_date DESC
                    ")->execute();
                    $enrollments = $db->fetchAll();
                    ?>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Code</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['title']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['code']); ?></td>
                                <td><?php echo formatDate($enrollment['enrollment_date'], 'Y-m-d'); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Drop this student?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $enrollment['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Drop</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($enrollments)): ?>
                        <p style="text-align: center; color: #9ca3af;">No active enrollments</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Enrollment</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="student_id">Student</label>
                            <select id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php
                                $db->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'student' AND status = 'active' ORDER BY first_name")->execute();
                                $students = $db->fetchAll();
                                foreach ($students as $s):
                                ?>
                                    <option value="<?php echo $s['id']; ?>">
                                        <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="course_id">Course</label>
                            <select id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php
                                $db->prepare("
                                    SELECT c.id, c.code, c.title, u.first_name, u.last_name 
                                    FROM courses c 
                                    JOIN users u ON c.teacher_id = u.id 
                                    WHERE c.status = 'active' 
                                    ORDER BY c.title
                                ")->execute();
                                $courses = $db->fetchAll();
                                foreach ($courses as $c):
                                ?>
                                    <option value="<?php echo $c['id']; ?>">
                                        <?php echo htmlspecialchars($c['code'] . ' - ' . $c['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="submit" class="btn btn-primary">Enroll</button>
                            <a href="enrollments.php" class="btn btn-secondary">Cancel</a>
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
