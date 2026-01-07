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
                    $message = 'Assignment created';
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

        <form method="POST" class="form-container">
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

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="assignments.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php teacherLayoutEnd(); ?>
