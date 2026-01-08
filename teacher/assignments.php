<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $course_id = intval($_POST['course_id'] ?? 0);
    $due_date = trim($_POST['due_date'] ?? '');
    $max_score = floatval($_POST['max_score'] ?? 100);
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $links = isset($_POST['links']) && is_array($_POST['links']) ? $_POST['links'] : [];
    $link_titles = isset($_POST['link_titles']) && is_array($_POST['link_titles']) ? $_POST['link_titles'] : [];

    if (empty($title) || $course_id === 0 || empty($due_date)) {
        $error = 'Required fields missing';
    } else {
        try {
            // Verify teacher owns this course
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$course_id, $teacher_id]);
            
            if (!$stmt->fetch()) {
                $error = 'Invalid course';
            } else {
                if ($assignment_id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ?, max_score = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $due_date, $max_score, $assignment_id]);
                    $message = 'Assignment updated';
                } else {
                    // Create
                    $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, due_date, max_score, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$course_id, $title, $description, $due_date, $max_score, $teacher_id]);
                    $assignment_id = (int)$pdo->lastInsertId();
                    $message = 'Assignment created';
                }

                // Handle uploads directory for assignments
                $assignmentsUploadDir = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . 'assignments' . DIRECTORY_SEPARATOR;
                if (!is_dir($assignmentsUploadDir)) {
                    @mkdir($assignmentsUploadDir, 0755, true);
                }

                // Allowed file types and size
                $allowedExtensions = ['pdf','doc','docx','ppt','pptx','xls','xlsx','zip','png','jpg','jpeg'];
                $maxFileSize = 10 * 1024 * 1024; // 10MB

                // Process file uploads (multiple)
                if (isset($_FILES['attachments']) && isset($_FILES['attachments']['name']) && $assignment_id) {
                    $names = $_FILES['attachments']['name'];
                    $tmp_names = $_FILES['attachments']['tmp_name'];
                    $sizes = $_FILES['attachments']['size'];
                    $errorsArr = $_FILES['attachments']['error'];
                    $types = $_FILES['attachments']['type'];

                    for ($i = 0; $i < count($names); $i++) {
                        if (!isset($names[$i]) || $names[$i] === '') continue;
                        if ($errorsArr[$i] !== UPLOAD_ERR_OK) continue;
                        if ($sizes[$i] > $maxFileSize) continue;

                        $originalName = $names[$i];
                        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowedExtensions, true)) continue;

                        $safeBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                        $unique = $safeBase . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                        $targetPath = $assignmentsUploadDir . $unique;

                        if (move_uploaded_file($tmp_names[$i], $targetPath)) {
                            // Save to DB
                            $relPath = 'assignments/' . $unique;
                            $stmt = $pdo->prepare("INSERT INTO assignment_resources (assignment_id, type, title, file_name, file_path, mime_type, file_size) VALUES (?, 'file', ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $assignment_id,
                                $originalName,
                                $unique,
                                $relPath,
                                $types[$i] ?? null,
                                (int)$sizes[$i]
                            ]);
                        }
                    }
                }

                // Process links
                if (!empty($links) && $assignment_id) {
                    for ($i = 0; $i < count($links); $i++) {
                        $url = trim($links[$i] ?? '');
                        if ($url === '') continue;
                        // Basic URL validation
                        if (!preg_match('/^https?:\/\//i', $url)) {
                            $url = 'http://' . $url; // attempt to normalize
                        }
                        if (filter_var($url, FILTER_VALIDATE_URL)) {
                            $title = trim($link_titles[$i] ?? '');
                            if ($title === '') $title = $url;
                            $stmt = $pdo->prepare("INSERT INTO assignment_resources (assignment_id, type, title, url) VALUES (?, 'link', ?, ?)");
                            $stmt->execute([$assignment_id, $title, $url]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE a FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.teacher_id = ?");
        $stmt->execute([$assignment_id, $teacher_id]);
        $message = 'Assignment deleted';
    } catch (Exception $e) {
        $error = 'Error deleting assignment';
    }
}

// Get assignment for edit
$edit_assignment = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $assignment_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT a.* FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.teacher_id = ?");
    $stmt->execute([$assignment_id, $teacher_id]);
    $edit_assignment = $stmt->fetch(PDO::FETCH_ASSOC);
}

teacherLayoutStart('assignments', 'Assignments');
?>


<div class="content-card">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <div class="card-header">
            <h2>Assignments</h2>
            <a href="assignments.php?action=add" class="btn btn-primary btn-sm">+ New Assignment</a>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Due Date</th>
                        <th>Max Score</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get all assignments for this teacher's courses
                    $stmt = $pdo->prepare("
                        SELECT a.*, c.title as course_title 
                        FROM assignments a 
                        JOIN courses c ON a.course_id = c.id 
                        WHERE c.teacher_id = ? 
                        ORDER BY a.due_date DESC
                    ");
                    $stmt->execute([$teacher_id]);
                    
                    $has_assignments = false;
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        $has_assignments = true;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['course_title']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($row['due_date'])); ?></td>
                            <td><?php echo $row['max_score']; ?></td>
                            <td class="actions">
                                <a href="assignments.php?action=edit&id=<?php echo $row['id']; ?>" class="btn-small">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this assignment?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="assignment_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-small btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; 
                    if (!$has_assignments): ?>
                        <tr><td colspan="5" style="text-align:center;color:#9ca3af;">No assignments created</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="card-header">
            <h2><?php echo $action === 'edit' ? 'Edit Assignment' : 'New Assignment'; ?></h2>
            <a href="assignments.php" class="btn btn-secondary btn-sm">← Back</a>
        </div>

        <form method="POST" class="form-container" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <?php if ($edit_assignment): ?>
                <input type="hidden" name="assignment_id" value="<?php echo $edit_assignment['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="course_id">Course</label>
                <select id="course_id" name="course_id" required>
                    <option value="">Select a course...</option>
                    <?php
                    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ? ORDER BY title");
                    $stmt->execute([$teacher_id]);
                    while ($course = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <option value="<?php echo $course['id']; ?>" 
                                <?php echo ($edit_assignment && $edit_assignment['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" 
                       value="<?php echo htmlspecialchars($edit_assignment['title'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_assignment['description'] ?? ''); ?></textarea>
            </div>

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

            <div class="form-group">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Attachments (optional)</label>
                <div style="position: relative; border: 2px dashed #d1d5db; border-radius: 0.5rem; padding: 2rem; text-align: center; background: #f9fafb; transition: all 0.2s; cursor: pointer;" 
                     onmouseover="this.style.borderColor='#3b82f6'; this.style.background='#eff6ff';"
                     onmouseout="this.style.borderColor='#d1d5db'; this.style.background='#f9fafb';"
                     onclick="document.querySelector('input[name=&quot;attachments[]&quot;]').click();">
                    <svg style="width: 2rem; height: 2rem; margin: 0 auto 0.5rem; color: #6b7280;" viewBox="0 0 24 24"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    <div style="font-size: 0.85rem; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">Click to upload or drag and drop</div>
                    <p style="color: #6b7280; font-size: 0.75rem; margin: 0;">PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP, PNG, JPG (Max 10MB each)</p>
                    <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.png,.jpg,.jpeg" style="display: none;">
                </div>
            </div>

            <div class="form-group">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Links (optional)</label>
                <div id="links-container">
                    <div class="link-row" style="display:flex; gap:0.5rem; margin-bottom:0.75rem;">
                        <input type="text" name="link_titles[]" placeholder="Link title (optional)" style="flex:1; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                        <input type="url" name="links[]" placeholder="https://example.com" style="flex:2; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addLinkRow()" style="background: #6b7280; color: white; padding: 0.4rem 0.8rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.8rem; font-weight: 500;">+ Add another link</button>
            </div>

            <?php
            // Show existing resources when editing
            if ($edit_assignment) {
                $stmt = $pdo->prepare("SELECT * FROM assignment_resources WHERE assignment_id = ? ORDER BY created_at DESC");
                $stmt->execute([$edit_assignment['id']]);
                $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($resources) {
                    echo '<div class="form-group"><label>Existing Resources</label><ul style="margin:0; padding-left:1rem;">';
                    foreach ($resources as $res) {
                        if ($res['type'] === 'file' && !empty($res['file_path'])) {
                            $url = UPLOAD_URL . $res['file_path'];
                            echo '<li><a href="' . htmlspecialchars($url) . '" target="_blank">' . htmlspecialchars($res['title'] ?: $res['file_name']) . '</a></li>';
                        } elseif ($res['type'] === 'link' && !empty($res['url'])) {
                            echo '<li><a href="' . htmlspecialchars($res['url']) . '" target="_blank">' . htmlspecialchars($res['title'] ?: $res['url']) . '</a></li>';
                        }
                    }
                    echo '</ul><p style="color:#6b7280;font-size:0.875rem;">Existing resources cannot be removed here yet.</p></div>';
                }
            }
            ?>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="assignments.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <script>
        function addLinkRow(){
            var container = document.getElementById('links-container');
            var div = document.createElement('div');
            div.className = 'link-row';
            div.style.cssText = 'display:flex; gap:0.5rem; margin-bottom:0.5rem;';
            div.innerHTML = '<input type="text" name="link_titles[]" placeholder="Link title (optional)" style="flex:1;">' +
                            '<input type="url" name="links[]" placeholder="https://example.com" style="flex:2;">';
            container.appendChild(div);
        }
        </script>
    <?php endif; ?>
</div>

<?php teacherLayoutEnd(); ?>
