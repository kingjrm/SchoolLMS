<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials - School LMS</title>
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
    $course_id = (int)($_GET['course_id'] ?? 0);

    // Handle upload
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $course_id = (int)($_POST['course_id'] ?? 0);

        if (empty($title) || $course_id === 0) {
            $message = 'Title and course are required';
        } else {
            // Verify teacher owns this course
            $db->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?")->bind('ii', $course_id, $teacher_id)->execute();
            if ($db->getResult()->num_rows === 0) {
                $message = 'Invalid course';
            } else {
                // Handle file upload if present
                $file_path = null;
                $file_type = null;
                if (isset($_FILES['material_file']) && $_FILES['material_file']['size'] > 0) {
                    $upload = uploadFile($_FILES['material_file'], 'materials/');
                    if ($upload['success']) {
                        $file_path = $upload['file'];
                        $file_type = pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION);
                    } else {
                        $message = $upload['message'];
                    }
                }

                if (empty($message)) {
                    $query = "INSERT INTO course_materials (course_id, title, description, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)";
                    $db->prepare($query)
                        ->bind('ssssss', $course_id, $title, $description, $file_path, $file_type, $teacher_id)
                        ->execute();
                    $message = 'Material uploaded successfully';
                }
            }
        }
    }

    // Handle delete
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
        $material_id = (int)$_POST['delete_id'];
        $db->prepare("SELECT file_path FROM course_materials WHERE id = ?")->bind('i', $material_id)->execute();
        $material = $db->fetch();
        
        if ($material && $material['file_path']) {
            deleteFile($material['file_path']);
        }
        $db->prepare("DELETE FROM course_materials WHERE id = ?")->bind('i', $material_id)->execute();
        $message = 'Material deleted';
    }
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link">Courses</a></li>
                <li class="nav-item"><a href="materials.php" class="nav-link active">Materials</a></li>
                <li class="nav-item"><a href="assignments.php" class="nav-link">Assignments</a></li>
                <li class="nav-item"><a href="quizzes.php" class="nav-link">Quizzes</a></li>
                <li class="nav-item"><a href="grades.php" class="nav-link">Grades</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link">Announcements</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>Course Materials</h1>
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
                    <h3 class="card-title">Upload Material</h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
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
                            <label for="description">Description</label>
                            <textarea id="description" name="description"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="material_file">Upload File (Optional)</label>
                            <input type="file" id="material_file" name="material_file">
                            <span class="help-text">Supported: PDF, DOC, DOCX, TXT, PPT, PPTX, XLS, XLSX, ZIP, JPG, PNG, GIF (Max 50MB)</span>
                        </div>

                        <button type="submit" name="submit" class="btn btn-primary">Upload</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Materials</h3>
                </div>
                <div class="card-body">
                    <?php
                    $query = "
                        SELECT m.*, c.title as course_title, c.code 
                        FROM course_materials m 
                        JOIN courses c ON m.course_id = c.id 
                        WHERE c.teacher_id = ? 
                        ORDER BY m.upload_date DESC
                    ";
                    $db->prepare($query)->bind('i', $teacher_id)->execute();
                    $materials = $db->fetchAll();

                    if (!empty($materials)):
                    ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($material['title']); ?></td>
                                    <td><?php echo htmlspecialchars($material['code'] . ' - ' . $material['course_title']); ?></td>
                                    <td><?php echo htmlspecialchars(strtoupper($material['file_type'] ?? 'link')); ?></td>
                                    <td><?php echo formatDate($material['upload_date'], 'M d, Y'); ?></td>
                                    <td>
                                        <?php if ($material['file_path']): ?>
                                            <a href="../<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank" class="btn btn-secondary btn-sm">Download</a>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this material?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $material['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #9ca3af;">No materials uploaded</p>
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
