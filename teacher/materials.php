<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];

$message = '';
$error = '';
$course_id = (int)($_GET['course_id'] ?? 0);

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $course_id = intval($_POST['course_id'] ?? 0);

    if (empty($title)) {
        $error = 'Title is required';
    } elseif ($course_id === 0) {
        $error = 'Please select a course';
    } else {
        try {
            // Verify teacher owns this course
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$course_id, $teacher_id]);
            
            if (!$stmt->fetch()) {
                $error = 'Invalid course';
            } else {
                $file_path = null;
                $file_type = null;
                
                // Handle file upload if present
                if (isset($_FILES['material_file']) && $_FILES['material_file']['size'] > 0) {
                    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip'];
                    $file_ext = strtolower(pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION));
                    
                    if (!in_array($file_ext, $allowed)) {
                        $error = 'File type not allowed';
                    } elseif ($_FILES['material_file']['size'] > 50 * 1024 * 1024) {
                        $error = 'File too large (max 50MB)';
                    } else {
                        $upload_dir = '../assets/uploads/materials/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['material_file']['name']);
                        $file_path = 'materials/' . $file_name;
                        
                        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $upload_dir . $file_name)) {
                            $file_type = $file_ext;
                        } else {
                            $error = 'Failed to upload file';
                        }
                    }
                }
                
                if (empty($error)) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO course_materials (course_id, title, description, file_path, file_type, uploaded_by) 
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([$course_id, $title, $description, $file_path, $file_type, $teacher_id]);
                    $message = 'Material uploaded successfully!';
                }
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $material_id = intval($_POST['material_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("SELECT file_path FROM course_materials WHERE id = ? AND course_id IN (SELECT id FROM courses WHERE teacher_id = ?)");
        $stmt->execute([$material_id, $teacher_id]);
        $material = $stmt->fetch();
        
        if ($material) {
            if ($material['file_path'] && file_exists('../assets/uploads/' . $material['file_path'])) {
                unlink('../assets/uploads/' . $material['file_path']);
            }
            $stmt = $pdo->prepare("DELETE FROM course_materials WHERE id = ?");
            $stmt->execute([$material_id]);
            $message = 'Material deleted successfully!';
        } else {
            $error = 'Material not found';
        }
    } catch (Exception $e) {
        $error = 'Error deleting material: ' . $e->getMessage();
    }
}

teacherLayoutStart('materials', 'Course Materials');
?>

<div class="content-card">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="card" style="margin-bottom:2rem">
        <div class="card-header">
            <h2>Upload Material</h2>
        </div>
        <form method="POST" enctype="multipart/form-data" class="form-container">
            <input type="hidden" name="action" value="upload">
            
            <div class="form-group">
                <label>Title <span style="color:#ef4444">*</span></label>
                <input type="text" name="title" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Course <span style="color:#ef4444">*</span></label>
                <select name="course_id" required>
                    <option value="">Select a course...</option>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ? AND status = 'active' ORDER BY title");
                        $stmt->execute([$teacher_id]);
                        while ($course = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . $course['id'] . '">' . htmlspecialchars($course['title']) . '</option>';
                        }
                    } catch (Exception $e) {
                        echo '<option value="">Error loading courses</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>File (Optional)</label>
                <input type="file" name="material_file">
                <p style="font-size:0.75rem;color:#6b7280;margin-top:0.25rem">Max 50MB. Allowed: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, ZIP</p>
            </div>
            
            <button type="submit" class="btn btn-primary">Upload Material</button>
        </form>
    </div>

    <!-- Materials List -->
    <div class="card">
        <div class="card-header">
            <h2>Course Materials</h2>
        </div>
        <div class="table-container">
            <?php
            try {
                $stmt = $pdo->prepare("
                    SELECT cm.*, c.title as course_title
                    FROM course_materials cm
                    JOIN courses c ON cm.course_id = c.id
                    WHERE c.teacher_id = ?
                    ORDER BY cm.upload_date DESC
                ");
                $stmt->execute([$teacher_id]);
                $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($materials)):
            ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Course</th>
                            <th>Description</th>
                            <th>File</th>
                            <th>Uploaded</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $material): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($material['title']); ?></td>
                            <td><?php echo htmlspecialchars($material['course_title']); ?></td>
                            <td style="max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars(substr($material['description'] ?? '', 0, 30)); ?></td>
                            <td>
                                <?php if ($material['file_path']): ?>
                                    <a href="../assets/uploads/<?php echo htmlspecialchars($material['file_path']); ?>" style="color:#3b82f6;text-decoration:none;font-size:0.75rem" download>Download</a>
                                <?php else: ?>
                                    <span style="color:#9ca3af;font-size:0.75rem">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($material['upload_date'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
                                    <button type="submit" class="btn-small btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php
                else:
            ?>
                <p style="text-align:center;color:#9ca3af;padding:2rem;font-size:0.85rem">No materials uploaded yet.</p>
            <?php
                endif;
            } catch (Exception $e) {
                echo '<div style="color:#991b1b">Error loading materials</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php teacherLayoutEnd(); ?>
