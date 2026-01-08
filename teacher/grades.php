<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/teacher_layout.php';

Auth::requireRole('teacher');
$user = Auth::getCurrentUser();
$teacher_id = $user['id'];

$message = '';
$error = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade') {
    $score = floatval($_POST['score'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);

    if ($score < 0) {
        $error = 'Score cannot be negative';
    } else {
        try {
            // Verify teacher owns this course
            $stmt = $pdo->prepare("
                SELECT a.id, a.max_score FROM assignments a 
                JOIN courses c ON a.course_id = c.id 
                WHERE a.id = ? AND c.teacher_id = ?
            ");
            $stmt->execute([$assignment_id, $teacher_id]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assignment) {
                $error = 'Invalid assignment';
            } elseif ($score > $assignment['max_score']) {
                $error = 'Score exceeds maximum of ' . $assignment['max_score'];
            } else {
                // Check if grade exists
                $stmt = $pdo->prepare("SELECT id FROM grades WHERE assignment_id = ? AND student_id = ?");
                $stmt->execute([$assignment_id, $student_id]);
                $existing_grade = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_grade) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE grades SET score = ?, feedback = ?, graded_by = ?, graded_at = NOW() WHERE assignment_id = ? AND student_id = ?");
                    $stmt->execute([$score, $feedback, $teacher_id, $assignment_id, $student_id]);
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO grades (assignment_id, student_id, score, feedback, graded_by, graded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$assignment_id, $student_id, $score, $feedback, $teacher_id]);
                }
                
                // Update submission status to graded
                $stmt = $pdo->prepare("UPDATE assignment_submissions SET status = 'graded' WHERE assignment_id = ? AND student_id = ?");
                $stmt->execute([$assignment_id, $student_id]);
                
                $message = 'Grade submitted successfully';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$filter_course = $_GET['course'] ?? '';
$filter_assignment = $_GET['assignment'] ?? '';
$filter_status = $_GET['status'] ?? 'ungraded'; // Default to ungraded
$filter_search = trim($_GET['search'] ?? '');
$group_by = $_GET['group'] ?? 'assignment'; // 'assignment' or 'course'

// Get courses for filter
$coursesStmt = $pdo->prepare("SELECT DISTINCT c.id, c.title, c.code FROM courses c JOIN assignments a ON c.id = a.course_id WHERE c.teacher_id = ? ORDER BY c.title");
$coursesStmt->execute([$teacher_id]);
$courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

teacherLayoutStart('grades', 'Grades');
?>

<style>
    .filter-section {
        background: white;
        padding: 1rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        margin-bottom: 1.5rem;
    }
    .filter-row {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: end;
    }
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    .filter-group label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.35rem;
    }
    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 0.5rem;
        border: 1.5px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.85rem;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.6rem;
        border-radius: 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .status-ungraded {
        background: #fef3c7;
        color: #92400e;
    }
    .status-graded {
        background: #d1fae5;
        color: #065f46;
    }
    .submission-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .submission-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .submission-info h3 {
        margin: 0 0 0.25rem 0;
        font-size: 0.9rem;
        font-weight: 600;
        color: #1f2937;
    }
    .submission-info p {
        margin: 0.15rem 0;
        font-size: 0.75rem;
        color: #6b7280;
    }
    .group-section {
        margin-bottom: 2rem;
    }
    .group-header {
        background: #f8f9fa;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem 0.5rem 0 0;
        border: 1px solid #e5e7eb;
        border-bottom: none;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .group-header:hover {
        background: #f1f5f9;
    }
    .group-header h3 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #1f2937;
    }
    .group-content {
        border: 1px solid #e5e7eb;
        border-top: none;
        border-radius: 0 0 0.5rem 0.5rem;
        padding: 1rem;
        background: white;
    }
    .group-content.hidden {
        display: none;
    }
    .stats-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .stat-card {
        flex: 1;
        min-width: 200px;
        background: white;
        padding: 1rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
    }
    .stat-card h4 {
        margin: 0 0 0.5rem 0;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
    }
    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
    }
</style>

<div class="content-card">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card-header">
        <h2>Manage Student Assignments</h2>
    </div>

    <?php
    // Build query with filters
    $where_conditions = ["c.teacher_id = ?"];
    $params = [$teacher_id];

    if ($filter_course) {
        $where_conditions[] = "c.id = ?";
        $params[] = (int)$filter_course;
    }

    if ($filter_assignment) {
        $where_conditions[] = "a.id = ?";
        $params[] = (int)$filter_assignment;
    }

    if ($filter_status === 'ungraded') {
        $where_conditions[] = "(g.score IS NULL OR s.status = 'submitted')";
    } elseif ($filter_status === 'graded') {
        $where_conditions[] = "g.score IS NOT NULL";
    }

    if ($filter_search) {
        $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR a.title LIKE ? OR c.title LIKE ?)";
        $search_param = '%' . $filter_search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

    // Get submissions
    $stmt = $pdo->prepare("
        SELECT s.*, a.id as assignment_id, a.title as assignment_title, a.max_score, a.due_date,
               c.id as course_id, c.title as course_title, c.code as course_code,
               u.first_name, u.last_name, u.id as student_id,
               g.score, g.feedback, g.graded_at
        FROM assignment_submissions s 
        JOIN assignments a ON s.assignment_id = a.id 
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON s.student_id = u.id 
        LEFT JOIN grades g ON a.id = g.assignment_id AND s.student_id = g.student_id
        $where_clause
        ORDER BY a.due_date DESC, s.submitted_at DESC
    ");
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get assignments for filter (based on selected course)
    $assignmentsStmt = $pdo->prepare("
        SELECT DISTINCT a.id, a.title, a.course_id 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE c.teacher_id = ? " . ($filter_course ? "AND c.id = ?" : "") . "
        ORDER BY a.title
    ");
    if ($filter_course) {
        $assignmentsStmt->execute([$teacher_id, (int)$filter_course]);
    } else {
        $assignmentsStmt->execute([$teacher_id]);
    }
    $assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate stats
    $totalSubmissions = count($submissions);
    $ungradedCount = 0;
    $gradedCount = 0;
    foreach ($submissions as $sub) {
        if ($sub['score'] === null) {
            $ungradedCount++;
        } else {
            $gradedCount++;
        }
    }
    ?>

    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-card">
            <h4>Total Submissions</h4>
            <div class="stat-value"><?php echo $totalSubmissions; ?></div>
        </div>
        <div class="stat-card">
            <h4>Ungraded</h4>
            <div class="stat-value" style="color: #f59e0b;"><?php echo $ungradedCount; ?></div>
        </div>
        <div class="stat-card">
            <h4>Graded</h4>
            <div class="stat-value" style="color: #10b981;"><?php echo $gradedCount; ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <form method="GET" class="filter-row">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Student, assignment, or course..." value="<?php echo htmlspecialchars($filter_search); ?>">
            </div>
            <div class="filter-group">
                <label>Course</label>
                <select name="course" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $filter_course == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['title'] . ' (' . $course['code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Assignment</label>
                <select name="assignment" onchange="this.form.submit()">
                    <option value="">All Assignments</option>
                    <?php foreach ($assignments as $assignment): ?>
                        <option value="<?php echo $assignment['id']; ?>" <?php echo $filter_assignment == $assignment['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($assignment['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="ungraded" <?php echo $filter_status === 'ungraded' ? 'selected' : ''; ?>>Ungraded</option>
                    <option value="graded" <?php echo $filter_status === 'graded' ? 'selected' : ''; ?>>Graded</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Group By</label>
                <select name="group" onchange="this.form.submit()">
                    <option value="assignment" <?php echo $group_by === 'assignment' ? 'selected' : ''; ?>>By Assignment</option>
                    <option value="course" <?php echo $group_by === 'course' ? 'selected' : ''; ?>>By Course</option>
                </select>
            </div>
            <div class="filter-group" style="flex: 0 0 auto;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            </div>
            <?php if ($filter_course || $filter_assignment || $filter_status !== 'ungraded' || $filter_search): ?>
            <div class="filter-group" style="flex: 0 0 auto;">
                <label>&nbsp;</label>
                <a href="grades.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; text-decoration: none; display: inline-block;">Reset</a>
            </div>
            <?php endif; ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($filter_search); ?>">
        </form>
    </div>

    <!-- Submissions -->
    <?php if (!empty($submissions)): ?>
        <?php
        // Group submissions
        $grouped = [];
        foreach ($submissions as $sub) {
            if ($group_by === 'assignment') {
                $key = $sub['assignment_id'] . '_' . $sub['assignment_title'];
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'title' => $sub['assignment_title'],
                        'course' => $sub['course_title'],
                        'due_date' => $sub['due_date'],
                        'submissions' => []
                    ];
                }
                $grouped[$key]['submissions'][] = $sub;
            } else {
                $key = $sub['course_id'] . '_' . $sub['course_title'];
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'title' => $sub['course_title'],
                        'code' => $sub['course_code'],
                        'submissions' => []
                    ];
                }
                $grouped[$key]['submissions'][] = $sub;
            }
        }
        ?>

        <?php foreach ($grouped as $groupKey => $group): ?>
            <div class="group-section">
                <div class="group-header" onclick="toggleGroup('<?php echo htmlspecialchars($groupKey); ?>')">
                    <h3>
                        <?php if ($group_by === 'assignment'): ?>
                            <?php echo htmlspecialchars($group['title']); ?>
                            <span style="font-size: 0.75rem; font-weight: 400; color: #6b7280; margin-left: 0.5rem;">
                                - <?php echo htmlspecialchars($group['course']); ?>
                            </span>
                        <?php else: ?>
                            <?php echo htmlspecialchars($group['title']); ?>
                            <span style="font-size: 0.75rem; font-weight: 400; color: #6b7280; margin-left: 0.5rem;">
                                (<?php echo htmlspecialchars($group['code']); ?>)
                            </span>
                        <?php endif; ?>
                    </h3>
                    <span style="font-size: 0.75rem; color: #6b7280;">
                        <?php echo count($group['submissions']); ?> submission(s)
                        <span id="toggle-<?php echo htmlspecialchars($groupKey); ?>" style="margin-left: 0.5rem;">▼</span>
                    </span>
                </div>
                <div class="group-content" id="group-<?php echo htmlspecialchars($groupKey); ?>">
                    <?php foreach ($group['submissions'] as $sub): ?>
                        <div class="submission-card">
                            <div class="submission-header">
                                <div class="submission-info">
                                    <h3><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></h3>
                                    <?php if ($group_by === 'course'): ?>
                                        <p><strong>Assignment:</strong> <?php echo htmlspecialchars($sub['assignment_title']); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Submitted:</strong> <?php echo date('M d, Y g:i A', strtotime($sub['submitted_at'])); ?></p>
                                    <?php if ($sub['submission_text']): ?>
                                        <p style="margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 0.25rem; font-size: 0.8rem;">
                                            <?php echo nl2br(htmlspecialchars(substr($sub['submission_text'], 0, 200))); ?>
                                            <?php echo strlen($sub['submission_text']) > 200 ? '...' : ''; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($sub['score'] !== null): ?>
                                        <span class="status-badge status-graded">✓ Graded</span>
                                    <?php else: ?>
                                        <span class="status-badge status-ungraded">⏳ Ungraded</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($sub['submission_file']): ?>
                                <div style="margin-bottom: 1rem;">
                                    <a href="<?php echo UPLOAD_URL . htmlspecialchars($sub['submission_file']); ?>" target="_blank" class="btn btn-secondary btn-sm">
                                        📄 View Submission File
                                    </a>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="form-container">
                                <input type="hidden" name="action" value="grade">
                                <input type="hidden" name="assignment_id" value="<?php echo $sub['assignment_id']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $sub['student_id']; ?>">

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group">
                                        <label>Score (out of <?php echo $sub['max_score']; ?>)</label>
                                        <input type="number" name="score" step="0.01" max="<?php echo $sub['max_score']; ?>" 
                                               value="<?php echo htmlspecialchars($sub['score'] ?? ''); ?>" required
                                               style="width: 100%; padding: 0.5rem; border: 1.5px solid #d1d5db; border-radius: 0.375rem;">
                                    </div>
                                    <?php if ($sub['score'] !== null && $sub['graded_at']): ?>
                                        <div class="form-group">
                                            <label>Graded At</label>
                                            <input type="text" value="<?php echo date('M d, Y g:i A', strtotime($sub['graded_at'])); ?>" 
                                                   readonly style="width: 100%; padding: 0.5rem; border: 1.5px solid #d1d5db; border-radius: 0.375rem; background: #f3f4f6;">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>Feedback</label>
                                    <textarea name="feedback" rows="3" style="width: 100%; padding: 0.5rem; border: 1.5px solid #d1d5db; border-radius: 0.375rem; font-family: inherit;"><?php echo htmlspecialchars($sub['feedback'] ?? ''); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <?php echo $sub['score'] !== null ? 'Update Grade' : 'Submit Grade'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #9ca3af;">
            <p style="font-size: 1rem; margin-bottom: 0.5rem;">No submissions found</p>
            <p style="font-size: 0.85rem;">Try adjusting your filters</p>
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleGroup(key) {
        const content = document.getElementById('group-' + key);
        const toggle = document.getElementById('toggle-' + key);
        content.classList.toggle('hidden');
        toggle.textContent = content.classList.contains('hidden') ? '▶' : '▼';
    }
    
    // Auto-submit search on Enter
    document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.form.submit();
        }
    });
</script>

<?php teacherLayoutEnd(); ?>
