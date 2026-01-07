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
                $message = 'Grade submitted successfully';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

teacherLayoutStart('grades', 'Grades');
?>


<div class="content-card">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card-header">
        <h2>Grade Submissions</h2>
    </div>

    <div style="display:flex;flex-direction:column;gap:1rem;">
        <?php
        try {
            $stmt = $pdo->prepare("
                SELECT s.*, a.title as assignment_title, a.max_score, c.title as course_title,
                       u.first_name, u.last_name, g.score, g.feedback
                FROM assignment_submissions s 
                JOIN assignments a ON s.assignment_id = a.id 
                JOIN courses c ON a.course_id = c.id 
                JOIN users u ON s.student_id = u.id 
                LEFT JOIN grades g ON a.id = g.assignment_id AND s.student_id = g.student_id
                WHERE c.teacher_id = ? AND (s.status = 'submitted' OR g.score IS NULL)
                ORDER BY s.submitted_at DESC
            ");
            $stmt->execute([$teacher_id]);
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($submissions)):
                foreach ($submissions as $sub):
        ?>
            <div style="background-color:#f9fafb;padding:1rem;border-radius:0.4rem;border:1px solid #e5e7eb;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;font-size:0.8rem">
                    <div>
                        <p style="font-weight:600;margin:0;"><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></p>
                        <p style="color:#9ca3af;font-size:0.75rem;margin:0.25rem 0 0 0;"><?php echo htmlspecialchars($sub['course_title']); ?></p>
                    </div>
                    <div>
                        <p style="font-weight:600;margin:0;"><?php echo htmlspecialchars($sub['assignment_title']); ?></p>
                        <p style="color:#9ca3af;font-size:0.75rem;margin:0.25rem 0 0 0;">Submitted: <?php echo date('M d, Y H:i', strtotime($sub['submitted_at'])); ?></p>
                    </div>
                </div>

                <?php if ($sub['submission_file']): ?>
                <a href="../<?php echo htmlspecialchars($sub['submission_file']); ?>" target="_blank" class="btn btn-secondary btn-sm" style="margin-bottom:1rem;">View Submission</a>
                <?php endif; ?>

                <form method="POST" class="form-container">
                    <input type="hidden" name="action" value="grade">
                    <input type="hidden" name="assignment_id" value="<?php echo $sub['assignment_id']; ?>">
                    <input type="hidden" name="student_id" value="<?php echo $sub['student_id']; ?>">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div class="form-group">
                            <label>Score (out of <?php echo $sub['max_score']; ?>)</label>
                            <input type="number" name="score" step="0.01" max="<?php echo $sub['max_score']; ?>" 
                                   value="<?php echo htmlspecialchars($sub['score'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Feedback</label>
                        <textarea name="feedback" rows="3"><?php echo htmlspecialchars($sub['feedback'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Grade</button>
                </form>
            </div>
        <?php 
                endforeach;
            else:
        ?>
            <p style="text-align:center;color:#9ca3af;padding:2rem;font-size:0.85rem">No pending submissions</p>
        <?php 
            endif;
        } catch (Exception $e) {
            echo '<p style="color:red;">Error loading submissions: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
</div>

<?php teacherLayoutEnd(); ?>
